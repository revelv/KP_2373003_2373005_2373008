<?php
// user/checkout.php
declare(strict_types=1);

session_start();
include '../koneksi.php';

// Optional: kalau lu sudah punya config komship terpisah
// pastiin path-nya bener
if (file_exists(__DIR__ . '/../koneksi_komship.php')) {
    require_once __DIR__ . '/../koneksi_komship.php';
}

/* ============================================================
   KONFIG KOMSHIP (fallback kalau belum didefinisikan)
   ============================================================ */
if (!defined('KOMSHIP_API_KEY')) {
    // TODO: ganti dengan API key Komship lu
    define('KOMSHIP_API_KEY', 'ISI_API_KEY_KOMSHIP_MU_DI_SINI');
}
if (!defined('KOMSHIP_BASE_URL')) {
    define('KOMSHIP_BASE_URL', 'https://api-sandbox.collaborator.komerce.id');
}

/**
 * Cari destination_id Komship berdasarkan teks alamat
 * (pakai endpoint /tariff/api/v1/destination/search?keyword=...)
 */
if (!function_exists('komshipSearchDestinationIdFromAddress')) {
    function komshipSearchDestinationIdFromAddress(
        string $provinsi,
        string $kota,
        string $kecamatan = ''
    ): ?int {
        $keyword = trim($kecamatan !== '' ? "$kecamatan, $kota, $provinsi" : "$kota, $provinsi");
        if ($keyword === '') {
            return null;
        }

        $url = KOMSHIP_BASE_URL . '/tariff/api/v1/destination/search?keyword=' . rawurlencode($keyword);

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_HTTPHEADER     => [
                'x-api-key: ' . KOMSHIP_API_KEY,
                'Accept: application/json',
            ],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 20,
        ]);
        $resp = curl_exec($ch);
        $http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err  = ($resp === false) ? curl_error($ch) : null;
        curl_close($ch);

        if ($resp === false || $http >= 400) {
            error_log('Komship destination search error: ' . ($err ?: "HTTP $http; BODY=$resp"));
            return null;
        }

        $body = json_decode($resp, true);
        if (!is_array($body)) {
            return null;
        }

        $data = $body['data'] ?? null;
        if (!is_array($data) || empty($data)) {
            return null;
        }

        $kotaL = mb_strtolower($kota);
        $kecL  = mb_strtolower($kecamatan);

        // Prioritas: cocokkan district + city kalau bisa
        foreach ($data as $row) {
            $id = $row['destination_id'] ?? $row['id'] ?? null;
            if (!$id) {
                continue;
            }
            $city     = mb_strtolower((string)($row['city'] ?? $row['city_name'] ?? ''));
            $district = mb_strtolower((string)($row['district'] ?? $row['district_name'] ?? ''));

            if ($kecL && $district && str_contains($district, $kecL) && str_contains($city, $kotaL)) {
                return (int)$id;
            }
        }

        // Fallback: ambil record pertama
        $first = $data[0];
        $id    = $first['destination_id'] ?? $first['id'] ?? null;
        return $id ? (int)$id : null;
    }
}

/**
 * Kirim order ke Komship (Delivery Order API - store_order)
 */
if (!function_exists('kirimOrderKeKomship')) {
    function kirimOrderKeKomship(array $payload): array
    {
        $url = KOMSHIP_BASE_URL . '/delivery-order/api/v1/store-order';

        $json = json_encode($payload);
        if ($json === false) {
            return [
                'komship_status' => 'JSON_ENCODE_ERROR',
                'meta_message'   => json_last_error_msg(),
            ];
        }

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_CUSTOMREQUEST  => 'POST',
            CURLOPT_POSTFIELDS     => $json,
            CURLOPT_HTTPHEADER     => [
                'x-api-key: ' . KOMSHIP_API_KEY,
                'Content-Type: application/json',
                'Accept: application/json',
            ],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 30,
        ]);

        $resp = curl_exec($ch);
        $http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err  = ($resp === false) ? curl_error($ch) : null;
        curl_close($ch);

        if ($resp === false) {
            return [
                'komship_status' => 'ERROR_CURL',
                'http_code'      => $http,
                'meta_message'   => $err,
            ];
        }

        $body = json_decode($resp, true);
        if (!is_array($body)) {
            return [
                'komship_status' => 'INVALID_JSON',
                'http_code'      => $http,
                'meta_message'   => 'Respon bukan JSON valid',
                'raw'            => $resp,
            ];
        }

        $meta    = $body['meta'] ?? [];
        $status  = $meta['status'] ?? $meta['code'] ?? null;
        $message = (string)($meta['message'] ?? '');

        $data = $body['data'] ?? [];

        $orderNo = $data['order_no']  ?? $data['order_number'] ?? null;
        $awb     = $data['awb']       ?? $data['waybill']      ?? null;

        $isSuccess = ($http === 200 || $http === 201)
            && (strtolower((string)$status) === 'success' || (int)$status === 200);

        return [
            'komship_status'   => $isSuccess ? 'SUCCESS' : 'FAILED',
            'http_code'        => $http,
            'meta_status'      => $status,
            'meta_message'     => $message,
            'komship_order_no' => $orderNo,
            'komship_awb'      => $awb,
            'raw'              => $body,
        ];
    }
}

/* ============================================================
   VALIDASI REQUEST & SESSION
   ============================================================ */

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die('Metode tidak valid.');
}

if (!isset($_SESSION['kd_cs'])) {
    $_SESSION['message'] = 'Anda harus login terlebih dahulu.';
    header('Location: produk.php');
    exit();
}

$customer_id = (int)$_SESSION['kd_cs'];

/* ===== Item yang dipilih dari cart ===== */
if (!isset($_POST['selected_items']) || empty($_POST['selected_items'])) {
    $_SESSION['message'] = 'Tidak ada item yang dipilih untuk checkout.';
    header('Location: cart.php');
    exit();
}

$selected_cart_ids = array_map('intval', $_POST['selected_items']);
$selected_cart_ids = array_filter($selected_cart_ids, fn($v) => $v > 0);
$in_clause         = implode(',', $selected_cart_ids);

/* ===== Data ongkir + kurir dari payment.php ===== */
$shipping_cost    = (int)($_POST['shipping_cost']    ?? 0);
$shipping_courier = trim((string)($_POST['shipping_courier'] ?? ''));
$shipping_service = trim((string)($_POST['shipping_service'] ?? ''));

if ($shipping_cost < 0) $shipping_cost = 0;

/* ===== Data alamat dari payment.php (MODE profil/custom) ===== */
$alamat_mode = $_POST['alamat_mode'] ?? 'profil';
$provinsi    = trim((string)($_POST['provinsi']   ?? ''));
$kota        = trim((string)($_POST['kota']       ?? ''));
$kecamatan   = trim((string)($_POST['kecamatan']  ?? ''));
$alamat      = trim((string)($_POST['alamat']     ?? ''));

// ID tujuan dari FE (kalau suatu saat lu simpan destination_id Komship)
$dest_prov_id     = trim((string)($_POST['dest_prov_id']     ?? ''));
$dest_city_id     = trim((string)($_POST['dest_city_id']     ?? ''));
$dest_district_id = trim((string)($_POST['dest_district_id'] ?? ''));

/* ===== Metode pembayaran dari payment.php ===== */
$metode_pembayaran = $_POST['metode'] ?? 'Transfer';

// Validasi basic
if ($provinsi === '' || $kota === '' || $alamat === '') {
    $_SESSION['message'] = 'Alamat pengiriman belum lengkap.';
    header('Location: payment.php');
    exit();
}
if ($shipping_courier === '' || $shipping_service === '' || $shipping_cost <= 0) {
    $_SESSION['message'] = 'Data ongkir / kurir belum lengkap.';
    header('Location: payment.php');
    exit();
}

/* ============================================================
   AMBIL ITEM DARI CART
   ============================================================ */
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

$items       = [];
$subtotal    = 0;
$totalBeratG = 0; // gram

while ($row = $resCart->fetch_assoc()) {
    $qty   = (int)$row['jumlah_barang'];
    $harga = (int)$row['harga'];

    if ((int)$row['stok'] < $qty) {
        die('Stok produk ' . htmlspecialchars($row['nama_produk']) . ' tidak cukup.');
    }

    $itemSub   = $qty * $harga;
    $subtotal += $itemSub;

    $beratItem = (int)$row['weight'] * $qty;
    if ($beratItem < 0) $beratItem = 0;
    $totalBeratG += $beratItem;

    $row['item_subtotal'] = $itemSub;
    $row['berat_item']    = $beratItem;

    $items[] = $row;
}
$stmtCart->close();

if ($subtotal <= 0 || empty($items)) {
    die('Data cart tidak valid.');
}

/* ============================================================
   VOUCHER (DARI SESSION)
   ============================================================ */
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

/* ============================================================
   TOTAL AKHIR LOKAL
   ============================================================ */
$base_total  = max(0, $subtotal - $voucher_discount);
$total_harga = $base_total + $shipping_cost;

/* ============================================================
   INSERT KE TABEL ORDERS
   ============================================================ */
$order_id = 'ORD-' . date('YmdHis') . '-' . rand(100, 999);

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
        ?, ?, NOW(), ?, ?, ?, 
        NULL, NULL, NULL, NULL,
        ?, ?, ?
    )
";

$stmtOrder = $conn->prepare($sqlOrder);
if (!$stmtOrder) {
    die('Query insert orders error: ' . $conn->error);
}

$stmtOrder->bind_param(
    'sissssiid',
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

/* ============================================================
   INSERT ORDER DETAILS
   ============================================================ */
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
    $pid   = (string)$it['product_id'];      // VARCHAR
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

/* ============================================================
   PERSIAPAN DATA UNTUK KOMSHIP
   ============================================================ */

// Berat dalam KG (dibulatkan ke atas, minimal 1kg)
$weightKg   = max(1, (int)ceil($totalBeratG / 1000));
$item_value = $base_total; // nilai barang tanpa ongkir

// Konversi alamat -> destination_id Komship
// Prioritas: kalau FE sudah kirim dest_district_id/dest_city_id dan itu memang destination_id,
// lu bisa pakai langsung. Tapi di sini kita tetap search biar aman.
$receiver_dest_id = komshipSearchDestinationIdFromAddress($provinsi, $kota, $kecamatan);
if (!$receiver_dest_id) {
    $_SESSION['message'] = 'Gagal menentukan destination_id Komship dari alamat kamu. Cek lagi kota/kecamatan.';
    header('Location: payment.php');
    exit();
}

// SHIPPER (asal kirim)
// TODO: GANTI shipper_destination_id & origin_pin_point SESUAI GUDANG LU
$shipper_destination_id = 31597; // contoh: destination_id gudang Bandung
$shipper_name           = 'Styrk Industries';
$shipper_phone          = '628123456789';
$shipper_email          = 'support@styrk.test';
$shipper_address        = 'Alamat gudang Styrk di Bandung';
$origin_pin_point       = '-7.279849431298132,109.35114360314475'; // TODO: titik koordinat gudang

// RECEIVER
$receiver_name    = $_SESSION['nama_cs']       ?? 'Customer';
$receiver_phone   = $_SESSION['no_telepon_cs'] ?? '628000000000';
$receiver_email   = $_SESSION['email_cs']      ?? ''; // kalau ada
$receiver_address = $alamat;
$destination_pin_point = ''; // kalau nanti punya koordinat user, isi di sini

// SHIPPING
$shipping_name = strtoupper($shipping_courier); // jne -> JNE
$shipping_type = $shipping_service;             // misal: REG / SAPFlat

// PAYMENT METHOD di Komship: COD / BANK TRANSFER
$metodeUpper = strtoupper($metode_pembayaran);
if ($metodeUpper === 'COD') {
    $payment_method = 'COD';
    $cod            = 'yes';
    $cod_value      = $total_harga;
} else {
    // QRIS & Transfer -> BANK TRANSFER
    $payment_method = 'BANK TRANSFER';
    $cod            = 'no';
    $cod_value      = 0;
}

// Biaya lain-lain
$shipping_cashback = 0;
$service_fee       = 0;
$additional_cost   = 0;
$insurance_value   = 0;

// DETAIL PRODUK UNTUK KOMSHIP
$order_details = [];
foreach ($items as $it) {
    $order_details[] = [
        'product_name'         => $it['nama_produk'],
        'product_variant_name' => '', // belum pakai varian
        'product_price'        => (int)$it['harga'],
        'product_width'        => 10,                    // dummy, bisa lu ganti nanti
        'product_height'       => 5,
        'product_weight'       => (int)$it['weight'],    // gram per item
        'product_length'       => 30,
        'qty'                  => (int)$it['jumlah_barang'],
        'subtotal'             => (int)$it['item_subtotal'],
    ];
}

// ORDER DATE harus format Y-m-d H:i:s
$order_date   = date('Y-m-d H:i:s');
$brand_name   = 'Styrk Industries';
$referenceInv = $order_id; // referensi invoice di sistem lu

// Payload final sesuai dokumen store_order
$komshipPayload = [
    'order_date'              => $order_date,
    'brand_name'              => $brand_name,
    'shipper_name'            => $shipper_name,
    'shipper_phone'           => $shipper_phone,
    'shipper_destination_id'  => $shipper_destination_id,
    'shipper_address'         => $shipper_address,
    'shipper_email'           => $shipper_email,
    'receiver_name'           => $receiver_name,
    'receiver_phone'          => $receiver_phone,
    'receiver_destination_id' => $receiver_dest_id,
    'receiver_address'        => $receiver_address,
    'receiver_email'          => $receiver_email,
    'shipping'                => $shipping_name,
    'shipping_type'           => $shipping_type,
    'payment_method'          => $payment_method,
    'shipping_cost'           => $shipping_cost,
    'shipping_cashback'       => $shipping_cashback,
    'service_fee'             => $service_fee,
    'additional_cost'         => $additional_cost,
    'grand_total'             => $total_harga,
    'cod_value'               => $cod_value,
    'insurance_value'         => $insurance_value,
    'order_details'           => $order_details,
    // field tambahan yang ada di docs tapi tidak wajib (*)
    'reference_invoice'       => $referenceInv,
    'weight'                  => $weightKg,
    'item_value'              => $item_value,
    'cod'                     => $cod,
    'origin_pin_point'        => $origin_pin_point,
    'destination_pin_point'   => $destination_pin_point,
];

/* ============================================================
   KIRIM ORDER KE KOMSHIP
   ============================================================ */
$kom = kirimOrderKeKomship($komshipPayload);

$komship_order_no = $kom['komship_order_no'] ?? null;
$komship_awb      = $kom['komship_awb']      ?? null;
$komship_status   = $kom['komship_status']   ?? ('ERROR_HTTP_' . ($kom['http_code'] ?? ''));
$meta_message     = $kom['meta_message']     ?? '';

if ($komship_status !== 'SUCCESS') {
    $_SESSION['komship_last_error'] = trim($komship_status . ' ' . $meta_message);
}

/* ============================================================
   UPDATE TABEL ORDERS DENGAN INFO KOMSHIP
   ============================================================ */
$sqlUpd = "
    UPDATE orders
    SET komship_order_no = ?,
        komship_awb      = ?,
        komship_status   = ?,
        komship_last_sync= NOW()
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

/* ============================================================
   BERSIHKAN CART & REDIRECT
   ============================================================ */
// Hapus item yang sudah di-checkout dari carts
$conn->query("DELETE FROM carts WHERE customer_id = {$customer_id} AND cart_id IN ($in_clause)");

// Simpan info order terakhir untuk halaman sukses
$_SESSION['last_order_id'] = $order_id;

header('Location: order_success.php?order_id=' . urlencode($order_id));
exit;
