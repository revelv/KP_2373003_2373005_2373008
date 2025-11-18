<?php
// user/checkout.php
declare(strict_types=1);

session_start();
include '../koneksi.php';
require_once __DIR__ . '/komship_destination.php'; // <- file helper barusan

// ====================== VALIDASI DASAR ======================
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die('Metode tidak valid.');
}

if (!isset($_SESSION['kd_cs'])) {
    $_SESSION['message'] = 'Anda harus login terlebih dahulu.';
    header('Location: produk.php');
    exit();
}

$customer_id = (int)$_SESSION['kd_cs'];

// ====================== AMBIL CART_ID DARI POST ======================
$rawItems = $_POST['selected_items'] ?? [];
if (!is_array($rawItems)) {
    $rawItems = [];
}

$selected_cart_ids = array_map('intval', $rawItems);
$selected_cart_ids = array_values(array_filter($selected_cart_ids, fn($v) => $v > 0));

if (empty($selected_cart_ids)) {
    $_SESSION['message'] = 'Tidak ada item yang dipilih untuk checkout.';
    header('Location: cart.php');
    exit();
}

$in_clause = implode(',', $selected_cart_ids);

// ====================== DATA ONGKIR & ALAMAT DARI PAYMENT ======================
$shipping_cost    = (int)($_POST['shipping_cost']    ?? 0);
$shipping_courier = trim((string)($_POST['shipping_courier'] ?? '')); // jne / jnt / sap
$shipping_service = trim((string)($_POST['shipping_service'] ?? '')); // REG / SAPFlat, dll

$alamat_mode = $_POST['alamat_mode'] ?? 'profil';
$provinsi    = trim((string)($_POST['provinsi']   ?? ''));
$kota        = trim((string)($_POST['kota']       ?? ''));
$kecamatan   = trim((string)($_POST['kecamatan']  ?? ''));
$alamat      = trim((string)($_POST['alamat']     ?? ''));

// ID dari sisi RajaOngkir (opsional, kalau nanti mau dipakai)
$dest_prov_id     = trim((string)($_POST['dest_prov_id']     ?? ''));
$dest_city_id     = trim((string)($_POST['dest_city_id']     ?? ''));
$dest_district_id = trim((string)($_POST['dest_district_id'] ?? ''));

// metode pembayaran (QRIS / Transfer) – kalau mau disimpan, nanti tinggal tambah kolom di `orders`
$metode_pembayaran = $_POST['metode'] ?? 'Transfer';

// Validasi minimal alamat + ongkir
if ($provinsi === '' || $kota === '' || $alamat === '') {
    $_SESSION['message'] = 'Alamat pengiriman belum lengkap.';
    header('Location: payment.php');
    exit();
}

if ($shipping_courier === '' || $shipping_cost <= 0 || $shipping_service === '') {
    $_SESSION['message'] = 'Data ongkir / kurir belum lengkap.';
    header('Location: payment.php');
    exit();
}

// ====================== AMBIL ITEM DARI CART ======================
$sqlCart = "
    SELECT 
        c.cart_id,
        c.product_id,
        c.jumlah_barang,
        p.nama_produk,
        p.harga,
        p.link_gambar,
        p.stok,
        IFNULL(p.weight, 0) AS weight
    FROM carts c
    JOIN products p ON c.product_id = p.product_id
    WHERE c.customer_id = ?
      AND c.cart_id IN ($in_clause)
";

$stmtCart = $conn->prepare($sqlCart);
if (!$stmtCart) {
    die('Query cart error: ' . $conn->error);
}
$stmtCart->bind_param('i', $customer_id);
$stmtCart->execute();
$resCart = $stmtCart->get_result();

$items    = [];
$subtotal = 0;

while ($row = $resCart->fetch_assoc()) {
    $qty   = (int)$row['jumlah_barang'];
    $harga = (int)$row['harga'];

    // Cek stok
    if ((int)$row['stok'] < $qty) {
        die('Stok produk ' . htmlspecialchars($row['nama_produk']) . ' tidak cukup.');
    }

    $itemSub = $qty * $harga;
    $subtotal += $itemSub;

    $row['item_subtotal'] = $itemSub;
    $items[] = $row;
}
$stmtCart->close();

if ($subtotal <= 0 || empty($items)) {
    die('Data cart tidak valid (subtotal 0 / item kosong).');
}

// ====================== VOUCHER (DARI SESSION) ======================
$voucher_code = $_SESSION['voucher_code']         ?? null;
$voucher_tipe = $_SESSION['voucher_tipe']         ?? null; // 'persen' | 'rupiah'
$voucher_rp   = (int)($_SESSION['voucher_nilai_rupiah'] ?? 0);
$voucher_pct  = (int)($_SESSION['voucher_nilai_persen'] ?? 0);

$voucher_discount = 0;
if ($voucher_code && $voucher_tipe === 'persen') {
    $voucher_discount = (int)round($subtotal * ($voucher_pct / 100));
} elseif ($voucher_code && $voucher_tipe === 'rupiah') {
    $voucher_discount = $voucher_rp;
}
if ($voucher_discount > $subtotal) {
    $voucher_discount = $subtotal;
}

// ====================== TOTAL AKHIR ======================
$base_total  = max(0, $subtotal - $voucher_discount); // nilai barang
$total_harga = $base_total + $shipping_cost;          // barang + ongkir

// ====================== KOMSHIP DESTINATION ID ======================

// 1) Coba pakai kalau sudah dikirim dari frontend (kalau suatu saat lu set di JS)
$komship_destination_id = 0;
if (!empty($_POST['komship_destination_id']) && ctype_digit((string)$_POST['komship_destination_id'])) {
    $komship_destination_id = (int)$_POST['komship_destination_id'];
}

// 2) Kalau masih 0, baru kita hit API Komship via helper
if ($komship_destination_id <= 0) {
    // Supaya lebih aman matching, bisa lu uppercase semua
    $komship_destination_id = getKomshipDestinationId(
        strtoupper($provinsi),
        strtoupper($kota),
        strtoupper($kecamatan)
    );
    // Kalau tetap 0, yaudah simpan 0 dulu — nanti di admin bisa lu benahin manual atau re-sync
}

// ====================== BUAT ID ORDER LOKAL ======================
$order_id = 'ORD-' . date('YmdHis') . '-' . rand(100, 999);

// Status awal Komship
$komship_status = 'pending';

// ====================== INSERT KE TABEL ORDERS ======================
$sqlOrder = "
    INSERT INTO orders (
        order_id,
        customer_id,
        tgl_order,
        provinsi,
        kota,
        kecamatan,
        komship_destination_id,
        alamat,
        code_courier,
        shipping_type,
        ongkos_kirim,
        total_harga,
        komship_status
    ) VALUES (
        ?, ?, NOW(),
        ?, ?, ?, ?,
        ?, ?, ?, ?, ?, ?
    )
";

$stmtOrder = $conn->prepare($sqlOrder);
if (!$stmtOrder) {
    die('Query insert orders error: ' . $conn->error);
}

$stmtOrder->bind_param(
    'sisssissiiis',
    $order_id,
    $customer_id,
    $provinsi,
    $kota,
    $kecamatan,
    $komship_destination_id,
    $alamat,
    $shipping_courier,   // ex: 'SAP'
    $shipping_service,   // ex: 'SAPFlat'
    $shipping_cost,
    $total_harga,
    $komship_status
);
$stmtOrder->execute();
$stmtOrder->close();

// ====================== INSERT ORDER DETAILS ======================
$sqlDetail = "
    INSERT INTO order_details (
        order_id,
        product_id,
        jumlah,
        harga_satuan,
        subtotal
    ) VALUES (?, ?, ?, ?, ?)
";

$stmtDetail = $conn->prepare($sqlDetail);
if (!$stmtDetail) {
    die('Query insert order_details error: ' . $conn->error);
}

foreach ($items as $it) {
    $pid   = (string)$it['product_id'];       // VARCHAR
    $qty   = (int)$it['jumlah_barang'];
    $harga = (int)$it['harga'];
    $sub   = (int)$it['item_subtotal'];

    $stmtDetail->bind_param(
        'ssiii',
        $order_id,
        $pid,
        $qty,
        $harga,
        $sub
    );
    $stmtDetail->execute();
}
$stmtDetail->close();

// ====================== HAPUS ITEM DARI CART ======================
$conn->query("DELETE FROM carts WHERE customer_id = {$customer_id} AND cart_id IN ($in_clause)");

// ====================== BERSIHIN SESSION VOUCHER ======================
unset(
    $_SESSION['voucher_code'],
    $_SESSION['voucher_tipe'],
    $_SESSION['voucher_nilai_rupiah'],
    $_SESSION['voucher_nilai_persen']
);

// Simpan info order terakhir
$_SESSION['last_order_id']       = $order_id;
$_SESSION['last_order_total']    = $total_harga;
$_SESSION['last_order_shipping'] = $shipping_cost;

// Redirect ke riwayat belanja
header('Location: riwayat_belanja.php');
exit;
