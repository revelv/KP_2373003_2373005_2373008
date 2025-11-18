<?php

declare(strict_types=1);
session_start();
include '../koneksi.php';

// ===================== KONFIG KOMSHIP =====================
const KOMSHIP_API_KEY   = '3I7kuf7B3e00fb2d23c692a69owo8BSW';
const KOMSHIP_BASE_URL  = 'https://api-sandbox.collaborator.komerce.id/order/api/v1/orders/store';
// ==========================================================

// --- Wajib POST ---
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die('Metode tidak valid.');
}

// --- Wajib login customer ---
if (!isset($_SESSION['kd_cs'])) {
    $_SESSION['message'] = 'Anda harus login terlebih dahulu.';
    header('Location: produk.php');
    exit();
}
$customer_id = (int)$_SESSION['kd_cs'];

// --- Validasi item cart ---
if (!isset($_POST['selected_items']) || empty($_POST['selected_items'])) {
    $_SESSION['message'] = 'Tidak ada item yang dipilih untuk checkout.';
    header('Location: cart.php');
    exit();
}
$selected_cart_ids = array_map('intval', $_POST['selected_items']);
$selected_cart_ids = array_filter($selected_cart_ids, fn($v) => $v > 0);
$in_clause         = implode(',', $selected_cart_ids);

// --- Ambil data cart & produk (untuk hitung subtotal & berat) ---
$sqlCart = "
    SELECT c.cart_id, c.product_id, c.jumlah_barang,
           p.nama_produk, p.harga, p.link_gambar,
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

$items       = [];
$subtotal    = 0;
$totalBeratG = 0; // gram

while ($row = $resCart->fetch_assoc()) {
    $qty   = (int)$row['jumlah_barang'];
    $harga = (int)$row['harga'];

    if ((int)$row['stok'] < $qty) {
        die('Stok produk ' . htmlspecialchars($row['nama_produk']) . ' tidak cukup.');
    }

    $itemSub = $qty * $harga;
    $subtotal += $itemSub;

    // berat total (gram)
    $beratItem = (int)$row['weight'] * $qty;
    $totalBeratG += $beratItem;

    $row['item_subtotal'] = $itemSub;
    $row['berat_item']    = $beratItem;
    $items[] = $row;
}
$stmtCart->close();

if ($subtotal <= 0) {
    die('Subtotal tidak valid.');
}

// --- Voucher dari session (sudah diset di cart.php) ---
$voucher_code = $_SESSION['voucher_code']         ?? null;
$voucher_tipe = $_SESSION['voucher_tipe']         ?? null; // 'persen' | 'rupiah'
$voucher_rp   = (int)($_SESSION['voucher_nilai_rupiah'] ?? 0);
$voucher_pct  = (int)($_SESSION['voucher_nilai_persen'] ?? 0);

// hitung diskon
$voucher_discount = 0;
if ($voucher_code && $voucher_tipe === 'persen') {
    $voucher_discount = (int)round($subtotal * ($voucher_pct / 100));
} elseif ($voucher_code && $voucher_tipe === 'rupiah') {
    $voucher_discount = $voucher_rp;
}
if ($voucher_discount > $subtotal) {
    $voucher_discount = $subtotal;
}

// --- Base total (tanpa ongkir) ---
$base_total = max(0, $subtotal - $voucher_discount);

// --- Ambil data ongkir & kurir dari POST (hasil dari RajaOngkir) ---
$shipping_cost   = (int)($_POST['shipping_cost']   ?? 0);
$shipping_courier = trim((string)($_POST['shipping_courier'] ?? ''));
$shipping_service = trim((string)($_POST['shipping_service'] ?? ''));

if ($shipping_cost < 0) $shipping_cost = 0;

// --- Alamat pengiriman dari payment.php ---
$alamat_mode = $_POST['alamat_mode'] ?? 'profil';
$provinsi    = trim((string)($_POST['provinsi']   ?? ''));
$kota        = trim((string)($_POST['kota']       ?? ''));
$kecamatan   = trim((string)($_POST['kecamatan']  ?? '')); // opsional kolom di DB
$alamat      = trim((string)($_POST['alamat']     ?? ''));

// ID tujuan (RajaOngkir / Komship) -> penting buat Komship
$dest_prov_id     = trim((string)($_POST['dest_prov_id']     ?? ''));
$dest_city_id     = trim((string)($_POST['dest_city_id']     ?? ''));
$dest_district_id = trim((string)($_POST['dest_district_id'] ?? ''));

// --- Validasi alamat minimal ---
if ($provinsi === '' || $kota === '' || $alamat === '') {
    $_SESSION['message'] = 'Alamat pengiriman belum lengkap.';
    header('Location: payment.php');  // sesuaikan path
    exit();
}

// --- Total akhir (barang + ongkir) ---
$total_harga = $base_total + $shipping_cost;

// --- Generate order_id (bebas, penting unik) ---
$order_id = 'ORD-' . date('YmdHis') . '-' . rand(100, 999);

// --- Simpan ke tabel orders dulu (tanpa data Komship) ---
$sqlOrder = "
    INSERT INTO orders (
        order_id,
        customer_id,
        tgl_order,
        provinsi,
        kota,
        alamat,
        komship_order_no,
        komship_awb,
        komship_status,
        komship_last_sync,
        code_courier,
        ongkos_kirim,
        total_harga
    ) VALUES (
        ?, ?, NOW(), ?, ?, ?, NULL, NULL, NULL, NULL, ?, ?, ?
    )
";
$stmtOrder = $conn->prepare($sqlOrder);
if (!$stmtOrder) {
    die('Query insert orders error: ' . $conn->error);
}
$stmtOrder->bind_param(
    'sissssid',
    $order_id,
    $customer_id,
    $provinsi,
    $kota,
    $alamat,
    $shipping_courier,
    $shipping_cost,
    $total_harga
);
$stmtOrder->execute();
$stmtOrder->close();

// ======================= KOMSHIP: CREATE ORDER =======================
$komship_status   = 'PENDING';
$komship_order_no = null;
$komship_awb      = null;
// konversi berat ke KG (biasanya API pakai kg, cek dokumen Komship)
$weightKg = max(1, ceil($totalBeratG / 1000)); // minimal 1 kg

// nilai barang untuk asuransi / COD
$item_value = $base_total; // atau $total_harga, sesuaikan kebutuhan

// contoh payload (SAMAKAN dengan dokumen resmi Komship)
$komshipPayload = [
    'order_no'                => $order_id,
    'receiver_destination_id' => $dest_district_id ?: $dest_city_id, // tergantung requirement
    'weight'                  => $weightKg,
    'item_value'              => $item_value,
    'cod'                     => false, // kalau mau COD -> true
    'courier_code'            => $shipping_courier,
    'service_code'            => $shipping_service,
    'receiver_name'           => $_SESSION['nama_cs'] ?? 'Customer',
    'receiver_phone'          => $_SESSION['no_telepon_cs'] ?? '', // sesuaikan field session/no_telepon
    'receiver_address'        => $alamat,
    'receiver_city'           => $kota,
    'receiver_province'       => $provinsi,
    // tambahkan field lain sesuai dokumen Komship:
    // 'receiver_lat'        => '...',
    // 'receiver_lng'        => '...',
    // 'shipper_destination_id' => 'ID_GUDANG_LU', dll.
];

// endpoint contoh, SESUAIKAN path-nya dengan dokumen Komship
$komshipUrl = 'https://api-sandbox.collaborator.komerce.id/order/api/v1/orders/create';

$ch = curl_init($komshipUrl);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_MAXREDIRS      => 5,
    CURLOPT_TIMEOUT        => 30,
    CURLOPT_HTTPHEADER     => [
        'Content-Type: application/json',
        'Accept: application/json',
        'x-api-key: ' . KOMSHIP_API_KEY,  // pastikan constant ini bener
    ],
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => json_encode($payload),
]);

$response  = curl_exec($ch);
$httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

if ($response === false) {
    $komship_status = 'ERROR_CURL';
} else {
    $data = json_decode($response, true);

    if ($httpCode >= 200 && $httpCode < 300 && isset($data['data']['order_no'])) {
        $komship_order_no = $data['data']['order_no'];
        $komship_awb      = $data['data']['awb'] ?? null;
        $komship_status   = 'SUCCESS';
    } else {
        // simpan kode HTTP biar kelihatan di DB
        $komship_status = 'ERROR_HTTP_' . (string)$httpCode;
    }
}

// --- Update record orders dengan data Komship (kalau ada) ---
$sqlUpd = "
    UPDATE orders
    SET komship_order_no = ?,
        komship_awb      = ?,
        komship_status   = ?,
        komship_last_sync= CURDATE()
    WHERE order_id = ?
";
$stmtUpd = $conn->prepare($sqlUpd);
if ($stmtUpd) {
    $stmtUpd->bind_param(
        'ssss',
        $komship_order_no,
        $komship_awb,
        $komship_status,
        $order_id
    );
    $stmtUpd->execute();
    $stmtUpd->close();
}

// =================== BERES: HAPUS ITEM DARI CART, REDIRECT ===================

// hapus cart yg sudah di-checkout
$conn->query("DELETE FROM carts WHERE customer_id = {$customer_id} AND cart_id IN ($in_clause)");

// bisa simpan info order_id di session utk halaman sukses
$_SESSION['last_order_id'] = $order_id;

header('Location: order_success.php'); // bikin halaman terima kasih
exit;
