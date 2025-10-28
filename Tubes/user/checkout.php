<?php

declare(strict_types=1);
session_start();
include '../koneksi.php';

if (!isset($_SESSION['kd_cs'])) die("Anda harus login terlebih dahulu.");
$customer_id = $_SESSION['kd_cs'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') die("Metode tidak valid.");
if (!isset($_POST['selected_items']) || empty($_POST['selected_items'])) die("Tidak ada item dipilih.");

$payment_method = $_POST['metode'] ?? '';
if (!in_array($payment_method, ['Transfer', 'QRIS'], true)) die("Metode pembayaran tidak valid.");

$selected_cart_ids = array_map('intval', $_POST['selected_items']);
$selected_cart_ids = array_values(array_filter($selected_cart_ids, fn($v) => $v > 0));
$in_clause         = implode(',', $selected_cart_ids);

// ===== Ongkir: dari POST (prioritas), fallback session =====
$shipping_cost = isset($_POST['shipping_cost'])
    ? max(0, (int)$_POST['shipping_cost'])
    : (int)($_SESSION['shipping_cost'] ?? 0);
$_SESSION['shipping_cost'] = $shipping_cost; // sync

// ===== Voucher (backend guard) =====
$voucher_code = $_SESSION['voucher_code'] ?? null;
$voucher_tipe = $_SESSION['voucher_tipe'] ?? null; // 'persen'|'rupiah'
$voucher_rp   = (int)($_SESSION['voucher_nilai_rupiah']  ?? 0);
$voucher_pct  = (int)($_SESSION['voucher_nilai_persen']  ?? 0);

// ===== Ambil item cart =====
$sql = "SELECT c.cart_id, c.product_id, c.jumlah_barang, p.harga, p.stok, p.nama_produk
        FROM carts c
        JOIN products p ON p.product_id = c.product_id
        WHERE c.customer_id=? AND c.cart_id IN ($in_clause)";
$stmt = $conn->prepare($sql);
$stmt->bind_param('s', $customer_id);
$stmt->execute();
$res = $stmt->get_result();

$items = [];
$total_barang = 0;
while ($r = $res->fetch_assoc()) {
    if ((int)$r['stok'] < (int)$r['jumlah_barang']) {
        die("Stok produk {$r['nama_produk']} tidak cukup.");
    }
    $jumlah = (int)$r['jumlah_barang'];
    $harga  = (int)$r['harga'];
    $sub    = $jumlah * $harga;
    $items[] = [
        'product_id' => $r['product_id'],
        'jumlah'     => $jumlah,
        'harga'      => $harga,
        'subtotal'   => $sub
    ];
    $total_barang += $sub;
}
$stmt->close();
if (!$items) die("Keranjang kosong / tidak ditemukan.");

// ===== Hitung diskon rupiah (guard) =====
$voucher_discount = 0;
if ($voucher_code && $voucher_tipe === 'persen') {
    $voucher_discount = (int) round($total_barang * ($voucher_pct / 100));
} elseif ($voucher_code && $voucher_tipe === 'rupiah') {
    $voucher_discount = $voucher_rp;
}
if ($voucher_discount > $total_barang) $voucher_discount = $total_barang;

// ===== Util: generate AWB unik (order_id) =====
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
$status  = ($payment_method === 'Transfer') ? 'pending' : 'proses';

$conn->begin_transaction();
try {
    // 1) Insert header orders (total_harga = 0 dulu, ongkos_kirim diisi sekarang)
    $order_id = generate_awb($conn);
    $insOrder = $conn->prepare("INSERT INTO orders (order_id, customer_id, tgl_order, ongkos_kirim, total_harga, status)
                                VALUES (?, ?, ?, ?, 0, ?)");
    $insOrder->bind_param('sssis', $order_id, $customer_id, $tanggal, $shipping_cost, $status);
    if (!$insOrder->execute()) throw new Exception("Gagal insert orders: " . $insOrder->error);
    $insOrder->close();

    // 2) Insert detail item, update stok
    $insDet   = $conn->prepare("INSERT INTO order_details (order_id, product_id, jumlah, harga_satuan, subtotal)
                                VALUES (?, ?, ?, ?, ?)");
    $updStock = $conn->prepare("UPDATE products SET stok = stok - ? WHERE product_id = ?");

    foreach ($items as $it) {
        $insDet->bind_param('ssiii', $order_id, $it['product_id'], $it['jumlah'], $it['harga'], $it['subtotal']);
        if (!$insDet->execute()) throw new Exception("Gagal insert detail: " . $insDet->error);

        $updStock->bind_param('is', $it['jumlah'], $it['product_id']);
        if (!$updStock->execute()) throw new Exception("Gagal update stok: " . $updStock->error);
    }
    $insDet->close();
    $updStock->close();

    // 3) Hitung total akhir = total_barang - diskon + ongkir
    $grand_total = max(0, (float)$total_barang - (float)$voucher_discount + (float)$shipping_cost);

    // 4) Update total ke header orders
    $upd = $conn->prepare("UPDATE orders SET total_harga=? WHERE order_id=?");
    $upd->bind_param('ds', $grand_total, $order_id);
    if (!$upd->execute()) throw new Exception("Gagal update total order: " . $upd->error);
    $upd->close();

    // 5) Payments
    if ($payment_method === 'Transfer') {
        if (!isset($_FILES['bukti']) || $_FILES['bukti']['error'] !== UPLOAD_ERR_OK) {
            throw new Exception("Bukti pembayaran wajib diupload.");
        }
        $dir = "../payment_proofs/";
        if (!is_dir($dir)) mkdir($dir, 0755, true);

        $ext = strtolower(pathinfo($_FILES['bukti']['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ['jpg', 'jpeg', 'png', 'gif'], true)) throw new Exception("Format file tidak didukung.");
        if ($_FILES['bukti']['size'] > 2_000_000) throw new Exception("Ukuran file maksimal 2MB.");

        $proof = $dir . "proof_" . $order_id . "_" . time() . "." . $ext;
        if (!move_uploaded_file($_FILES['bukti']['tmp_name'], $proof)) {
            throw new Exception("Gagal upload bukti.");
        }

        $pay = $conn->prepare("INSERT INTO payments (order_id, metode, jumlah_dibayar, tanggal_bayar, payment_proof, payment_status)
                               VALUES (?, 'Transfer Bank', ?, ?, ?, 'pending')");
        $pay->bind_param('sdss', $order_id, $grand_total, $tanggal, $proof);
        if (!$pay->execute()) throw new Exception("Gagal insert payment: " . $pay->error);
        $pay->close();
    } else { // QRIS
        $kode = $_POST['kode_transaksi'] ?? '';
        if ($kode === '') throw new Exception("Kode transaksi QRIS wajib diisi.");

        $pay = $conn->prepare("INSERT INTO payments (order_id, metode, jumlah_dibayar, tanggal_bayar, payment_proof, payment_status)
                               VALUES (?, 'QRIS', ?, ?, ?, 'proses')");
        $pay->bind_param('sdss', $order_id, $grand_total, $tanggal, $kode);
        if (!$pay->execute()) throw new Exception("Gagal insert payment: " . $pay->error);
        $pay->close();
    }

    // 6) Tandai voucher terpakai (jika ada)
    if (!empty($voucher_code)) {
        $v = $conn->prepare("UPDATE vouchers SET status='terpakai' WHERE kode_voucher=?");
        $v->bind_param('s', $voucher_code);
        $v->execute();
        $v->close();
        unset($_SESSION['voucher_code'], $_SESSION['voucher_tipe'], $_SESSION['voucher_nilai_rupiah'], $_SESSION['voucher_nilai_persen']);
    }

    // 7) Hapus cart
    if (!$conn->query("DELETE FROM carts WHERE customer_id='" . mysqli_real_escape_string($conn, (string)$customer_id) . "' AND cart_id IN ($in_clause)")) {
        throw new Exception("Gagal hapus cart: " . $conn->error);
    }

    $conn->commit();
    echo "<script>
        alert('Order sukses!');
        window.location.href = 'riwayat_belanja.php';
    </script>";
    exit;
} catch (Exception $e) {
    $conn->rollback();
    die("Transaksi gagal: " . $e->getMessage());
}
