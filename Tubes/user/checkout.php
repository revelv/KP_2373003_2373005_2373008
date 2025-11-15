<?php
// user/checkout.php
declare(strict_types=1);
session_start();
include '../koneksi.php';
require_once __DIR__ . '/komship_api.php'; // <-- KOMSHIP HELPER

if (!isset($_SESSION['kd_cs'])) die("Anda harus login terlebih dahulu.");
$customer_id = (int)$_SESSION['kd_cs'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') die("Metode tidak valid.");
if (!isset($_POST['selected_items']) || empty($_POST['selected_items'])) die("Tidak ada item dipilih.");

$payment_method = $_POST['metode'] ?? '';
if (!in_array($payment_method, ['Transfer', 'QRIS'], true)) die("Metode pembayaran tidak valid.");

// ==== Ambil & validasi item yang dipilih ====
$selected_cart_ids = array_map('intval', (array)$_POST['selected_items']);
$selected_cart_ids = array_values(array_filter($selected_cart_ids, fn($v) => $v > 0));
if (!$selected_cart_ids) die("Item tidak valid.");
$in_clause = implode(',', $selected_cart_ids);

// ==== Ambil info pengiriman dari POST (dikirim dari payment page) ====
$shipping_cost    = isset($_POST['shipping_cost']) ? max(0, (int)$_POST['shipping_cost']) : 0;
$shipping_courier = trim((string)($_POST['shipping_courier'] ?? ''));   // simpan ke komship_courier
$shipping_service = trim((string)($_POST['shipping_service'] ?? ''));   // simpan ke komship_service

$alamat_mode = $_POST['alamat_mode'] ?? ''; // 'profil'|'custom'
$ship_prov   = trim((string)($_POST['provinsi'] ?? ''));
$ship_kota   = trim((string)($_POST['kota'] ?? ''));
$ship_alamat = trim((string)($_POST['alamat'] ?? ''));

// Validasi minimal (wajib pilih kurir & alamat lengkap)
if ($shipping_courier === '') die("Kurir belum dipilih.");
if ($ship_prov === '' || $ship_kota === '' || $ship_alamat === '') die("Alamat pengiriman belum lengkap.");

// Simpan biaya kirim ke session (sinkronisasi)
$_SESSION['shipping_cost'] = $shipping_cost;

// ==== Voucher (guard backend) ====
$voucher_code = $_SESSION['voucher_code'] ?? null;
$voucher_tipe = $_SESSION['voucher_tipe'] ?? null; // 'persen'|'rupiah'
$voucher_rp   = (int)($_SESSION['voucher_nilai_rupiah']  ?? 0);
$voucher_pct  = (int)($_SESSION['voucher_nilai_persen']  ?? 0);

// ==== Ambil item keranjang ====
$sql = "SELECT c.cart_id, c.product_id, c.jumlah_barang, p.harga, p.stok, p.nama_produk
        FROM carts c
        JOIN products p ON p.product_id = c.product_id
        WHERE c.customer_id=? AND c.cart_id IN ($in_clause)";
$stmt = $conn->prepare($sql);
if (!$stmt) die("Gagal prepare cart: " . $conn->error);
$stmt->bind_param('i', $customer_id);
$stmt->execute();
$res = $stmt->get_result();

$items = [];
$total_barang = 0;
while ($r = $res->fetch_assoc()) {
    if ((int)$r['stok'] < (int)$r['jumlah_barang']) {
        $stmt->close();
        die("Stok produk {$r['nama_produk']} tidak cukup.");
    }
    $jumlah = (int)$r['jumlah_barang'];
    $harga  = (int)$r['harga'];
    $sub    = $jumlah * $harga;
    $items[] = [
        'product_id' => (string)$r['product_id'],
        'jumlah'     => $jumlah,
        'harga'      => $harga,
        'subtotal'   => $sub
    ];
    $total_barang += $sub;
}
$stmt->close();
if (!$items) die("Keranjang kosong / tidak ditemukan.");

// ==== Hitung diskon ====
$voucher_discount = 0;
if ($voucher_code && $voucher_tipe === 'persen') {
    $voucher_discount = (int) round($total_barang * ($voucher_pct / 100));
} elseif ($voucher_code && $voucher_tipe === 'rupiah') {
    $voucher_discount = $voucher_rp;
}
if ($voucher_discount > $total_barang) $voucher_discount = $total_barang;

// ==== Util: generate order_id unik ====
function generate_awb(mysqli $conn): string
{
    do {
        $awb = 'STYRK' . time() . str_pad((string)mt_rand(0, 99999), 5, '0', STR_PAD_LEFT);
        $q = $conn->prepare("SELECT 1 FROM orders WHERE order_id=? LIMIT 1");
        $q->bind_param('s', $awb);
        $q->execute();
        $exists = (bool)$q->get_result()->fetch_row();
        $q->close();
    } while ($exists);
    return $awb;
}

$tanggal = date("Y-m-d H:i:s");

// ==== Transaksi lokal (DB sendiri) ====
$conn->begin_transaction();
try {
    // 1) Insert header orders
    $order_id    = generate_awb($conn);
    $grand_total = max(0.0, (float)$total_barang - (float)$voucher_discount + (float)$shipping_cost);

    $insOrder = $conn->prepare("
        INSERT INTO orders
            (order_id, customer_id, tgl_order, provinsi, kota, alamat,
             ongkos_kirim, total_harga, komship_courier, komship_service)
        VALUES
            (?,        ?,           ?,         ?,        ?,    ?,
             ?,            ?,           ?,              ?)
    ");
    if (!$insOrder) throw new Exception("Gagal prepare insert orders: " . $conn->error);

    // types: s i s s s s i d s s
    $insOrder->bind_param(
        'sissssidss',
        $order_id,
        $customer_id,
        $tanggal,
        $ship_prov,
        $ship_kota,
        $ship_alamat,
        $shipping_cost,
        $grand_total,
        $shipping_courier,   // komship_courier
        $shipping_service    // komship_service
    );

    if (!$insOrder->execute()) throw new Exception("Gagal insert orders: " . $insOrder->error);
    $insOrder->close();

    // 2) Insert order_details & update stok
    $insDet   = $conn->prepare("INSERT INTO order_details (order_id, product_id, jumlah, harga_satuan, subtotal)
                                VALUES (?, ?, ?, ?, ?)");
    $updStock = $conn->prepare("UPDATE products SET stok = stok - ? WHERE product_id = ?");

    if (!$insDet || !$updStock) throw new Exception("Gagal prepare detail/stock: " . $conn->error);

    foreach ($items as $it) {
        $insDet->bind_param('ssiii', $order_id, $it['product_id'], $it['jumlah'], $it['harga'], $it['subtotal']);
        if (!$insDet->execute()) throw new Exception("Gagal insert detail: " . $insDet->error);

        $updStock->bind_param('is', $it['jumlah'], $it['product_id']);
        if (!$updStock->execute()) throw new Exception("Gagal update stok: " . $updStock->error);
    }
    $insDet->close();
    $updStock->close();

    // 3) Payments
    if ($payment_method === 'Transfer') {
        if (!isset($_FILES['bukti']) || $_FILES['bukti']['error'] !== UPLOAD_ERR_OK) {
            throw new Exception("Bukti pembayaran wajib diupload.");
        }
        $dir = "../payment_proofs/";
        if (!is_dir($dir)) mkdir($dir, 0755, true);

        $ext = strtolower(pathinfo($_FILES['bukti']['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ['jpg', 'jpeg', 'png', 'gif'], true)) throw new Exception("Format file tidak didukung.");
        if ((int)$_FILES['bukti']['size'] > 2_000_000) throw new Exception("Ukuran file maksimal 2MB.");

        $proof = $dir . "proof_" . $order_id . "_" . time() . "." . $ext;
        if (!move_uploaded_file($_FILES['bukti']['tmp_name'], $proof)) {
            throw new Exception("Gagal upload bukti.");
        }

        $pay = $conn->prepare("INSERT INTO payments (order_id, metode, jumlah_dibayar, tanggal_bayar, payment_proof, payment_status)
                               VALUES (?, 'Transfer Bank', ?, ?, ?, 'pending')");
        if (!$pay) throw new Exception("Gagal prepare payment: " . $conn->error);
        $pay->bind_param('sdss', $order_id, $grand_total, $tanggal, $proof);
        if (!$pay->execute()) throw new Exception("Gagal insert payment: " . $pay->error);
        $pay->close();
    } else { // QRIS
        $kode = $_POST['kode_transaksi'] ?? '';
        if ($kode === '') throw new Exception("Kode transaksi QRIS wajib diisi.");

        $pay = $conn->prepare("INSERT INTO payments (order_id, metode, jumlah_dibayar, tanggal_bayar, payment_proof, payment_status)
                               VALUES (?, 'QRIS', ?, ?, ?, 'proses')");
        if (!$pay) throw new Exception("Gagal prepare payment (QRIS): " . $conn->error);
        $pay->bind_param('sdss', $order_id, $grand_total, $tanggal, $kode);
        if (!$pay->execute()) throw new Exception("Gagal insert payment: " . $pay->error);
        $pay->close();
    }

    // 4) Tandai voucher terpakai (jika ada)
    if (!empty($voucher_code)) {
        $v = $conn->prepare("UPDATE vouchers SET status='terpakai' WHERE kode_voucher=?");
        $v->bind_param('s', $voucher_code);
        $v->execute();
        $v->close();

        unset(
            $_SESSION['voucher_code'],
            $_SESSION['voucher_tipe'],
            $_SESSION['voucher_nilai_rupiah'],
            $_SESSION['voucher_nilai_persen']
        );
    }

    // 5) Hapus item dari cart user
    $esc_customer = mysqli_real_escape_string($conn, (string)$customer_id);
    if (!$conn->query("DELETE FROM carts WHERE customer_id='{$esc_customer}' AND cart_id IN ($in_clause)")) {
        throw new Exception("Gagal hapus cart: " . $conn->error);
    }

    // Commit transaksi lokal
    $conn->commit();

    // 6) Setelah commit: kirim ke Komship (non-fatal kalau gagal)
    try {
        if (function_exists('komship_create_order')) {
            $kom = komship_create_order($conn, $order_id);
            if ($kom && !empty($kom['order_no'])) {
                $upd = $conn->prepare("
                    UPDATE orders 
                    SET komship_order_no = ?, 
                        komship_awb      = ?, 
                        komship_status   = ?
                    WHERE order_id = ?
                ");
                if ($upd) {
                    $awb   = $kom['awb']   ?? null;
                    $stts  = $kom['status'] ?? null;
                    $ordNo = $kom['order_no'];
                    $upd->bind_param('ssss', $ordNo, $awb, $stts, $order_id);
                    $upd->execute();
                    $upd->close();
                }
            }
        }
    } catch (Throwable $eKom) {
        // Bisa lu log ke file / tabel lain kalo mau, biar ga ganggu user
        // error_log('Komship error: ' . $eKom->getMessage());
    }

    echo "<script>
        alert('Order sukses! Nomor order: " . htmlspecialchars($order_id, ENT_QUOTES) . "');
        window.location.href = 'riwayat_belanja.php';
    </script>";
    exit;
} catch (Exception $e) {
    $conn->rollback();
    die("Transaksi gagal: " . $e->getMessage());
}
