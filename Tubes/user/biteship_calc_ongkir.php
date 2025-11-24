<?php
// biteship_calc_ongkir.php
declare(strict_types=1);
session_start();
require_once '../koneksi.php';

header('Content-Type: application/json');

// ===================== CEK LOGIN =====================
if (!isset($_SESSION['kd_cs'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Anda harus login terlebih dahulu.'
    ]);
    exit;
}

$customer_id = (int) $_SESSION['kd_cs'];

// ===================== AMBIL DATA POST =====================
$destination_postal = trim((string)($_POST['destination_postal_code'] ?? ''));
$courier_code       = trim((string)($_POST['code_courier'] ?? ''));
$base_total         = (int)($_POST['base_total'] ?? 0); // kalau mau dipakai nanti, sekarang belum kepake

// selected_items[] dari payment.php (cart)
$selected_ids = $_POST['selected_items'] ?? [];
if (!is_array($selected_ids)) {
    $selected_ids = [$selected_ids];
}
$selected_ids = array_map('intval', $selected_ids);
$selected_ids = array_values(array_filter($selected_ids, fn($v) => $v > 0));

// ===================== VALIDASI DASAR =====================
if ($destination_postal === '') {
    echo json_encode([
        'success' => false,
        'message' => 'Kode pos tujuan kosong, tidak bisa hitung ongkir.'
    ]);
    exit;
}

if ($courier_code === '') {
    echo json_encode([
        'success' => false,
        'message' => 'Kurir belum dipilih.'
    ]);
    exit;
}

if (empty($selected_ids)) {
    echo json_encode([
        'success' => false,
        'message' => 'Tidak ada item cart yang dikirim ke kalkulasi ongkir.'
    ]);
    exit;
}

// ===================== CONFIG TOKO & API KEY =====================
$origin_postal    = '40161'; // asal Bandung, sesuaikan kalau mau
$BITESHIP_API_KEY = 'biteship_test.eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJuYW1lIjoiU3R5cmtfaW5kdXN0cmllcyIsInVzZXJJZCI6IjY5MjA3ZmI0YzMxM2VmYTUyZTM5OThlNCIsImlhdCI6MTc2Mzc4NTQ0OH0.dBPLQHoBBV4gnXux-OMziAO5yr1TBzXTf4T-Js2b0ak';

// ===================== AMBIL DATA CART DARI DB =====================
$in_clause = implode(',', $selected_ids);

$sql = "
    SELECT 
        c.cart_id,
        c.jumlah_barang,
        p.nama_produk,
        p.harga,
        p.weight
    FROM carts c
    JOIN products p ON p.product_id = c.product_id
    WHERE c.customer_id = ?
      AND c.cart_id IN ($in_clause)
";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    echo json_encode([
        'success' => false,
        'message' => 'Gagal prepare query cart: ' . $conn->error
    ]);
    exit;
}

$stmt->bind_param('i', $customer_id);
$stmt->execute();
$res = $stmt->get_result();

$itemsPayload      = [];
$total_weight_gram = 0;

while ($row = $res->fetch_assoc()) {
    $nama   = (string)($row['nama_produk'] ?? 'Item');
    $harga  = (int)($row['harga'] ?? 0);
    $qty    = max(1, (int)($row['jumlah_barang'] ?? 1));
    $berat  = (int)($row['weight'] ?? 0); // gram per unit

    // fallback kalau belum diisi
    if ($berat <= 0) {
        $berat = 500; // default 500 gram per unit
    }

    $total_weight_gram += $berat * $qty;

    $itemsPayload[] = [
        'name'        => $nama,
        'value'       => $harga,       // harga per unit
        'weight'      => $berat,       // gram per unit
        'quantity'    => $qty,
        'length'      => 10,
        'width'       => 10,
        'height'      => 10,
        'description' => 'Produk dari cart Styrk Industries',
    ];
}
$stmt->close();

if (empty($itemsPayload)) {
    echo json_encode([
        'success' => false,
        'message' => 'Cart kosong atau produk tidak ditemukan untuk kalkulasi ongkir.'
    ]);
    exit;
}

// Kalau entah kenapa total_weight_gram masih 0, kasih default 1kg
if ($total_weight_gram <= 0) {
    $total_weight_gram = 1000;
}

// ===================== SUSUN PAYLOAD BITESHIP =====================
$payload = [
    'origin_postal_code'      => $origin_postal,
    'destination_postal_code' => $destination_postal,
    'couriers'                => $courier_code,
    'items'                   => $itemsPayload,
];

// ===================== PANGGIL API BITESHIP =====================
$ch = curl_init('https://api.biteship.com/v1/rates/couriers');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_HTTPHEADER     => [
        'Authorization: ' . $BITESHIP_API_KEY,
        'Content-Type: application/json',
        'Accept: application/json',
    ],
    CURLOPT_POSTFIELDS     => json_encode($payload),
    CURLOPT_TIMEOUT        => 20,
]);

$responseBody = curl_exec($ch);
$curlErr      = curl_error($ch);
$httpCode     = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

// ===================== HANDLE ERROR CURL =====================
if ($curlErr) {
    echo json_encode([
        'success' => false,
        'message' => 'CURL error: ' . $curlErr,
    ]);
    exit;
}

$decoded = json_decode($responseBody, true);
if (!is_array($decoded)) {
    echo json_encode([
        'success' => false,
        'message' => 'Respon Biteship bukan JSON valid.',
        'raw'     => $responseBody,
    ]);
    exit;
}

// ===================== HANDLE HTTP ERROR DARI BITESHIP =====================
if ($httpCode !== 200) {
    $msg = 'API Biteship gagal: HTTP ' . $httpCode;

    if (isset($decoded['errors'])) {
        $msgDetail = is_array($decoded['errors'])
            ? json_encode($decoded['errors'])
            : (string)$decoded['errors'];
        $msg .= ' | ' . $msgDetail;
    } elseif (isset($decoded['message'])) {
        $msg .= ' | ' . (string)$decoded['message'];
    }

    echo json_encode([
        'success' => false,
        'message' => $msg,
        'raw'     => $decoded,
    ]);
    exit;
}

// ===================== AMBIL LIST PRICING / RATES =====================
$pricingList = [];
if (isset($decoded['pricing']) && is_array($decoded['pricing'])) {
    $pricingList = $decoded['pricing'];      // format umum Biteship
} elseif (isset($decoded['rates']) && is_array($decoded['rates'])) {
    $pricingList = $decoded['rates'];        // jaga-jaga format lain
}

if (empty($pricingList)) {
    echo json_encode([
        'success' => false,
        'message' => 'Tidak ada pricing/rates dari Biteship.',
        'raw'     => $decoded,
    ]);
    exit;
}

// ===================== BENTUK SERVICES UNTUK FRONTEND =====================
$services = [];
foreach ($pricingList as $p) {
    $services[] = [
        'courier'      => strtolower((string)($p['courier_code'] ?? $courier_code)),
        'courier_name' => (string)($p['courier_name'] ?? strtoupper($courier_code)),

        // INI DIPAKAI SEBAGAI value DI <option> DAN DIKIRIM KE checkout.php → courier_type
        'service_code' => (string)($p['courier_service_code'] ?? ''),

        // INI CUMA LABEL YANG DILIHAT USER
        'service_name' => (string)($p['courier_service_name'] ?? ($p['service_type'] ?? '')),

        // HARGA ONGKIR
        'cost'         => (int)($p['price'] ?? $p['final_price'] ?? 0),

        // ESTIMASI
        'etd'          => (string)($p['etd'] ?? ($p['courier_etd'] ?? '')),
    ];
}

// Filter kalau bener-bener kosong / gak valid
$services = array_values(array_filter($services, fn($s) =>
    !empty($s['service_code']) && $s['cost'] > 0
));

if (empty($services)) {
    echo json_encode([
        'success' => false,
        'message' => 'Tidak ada layanan yang tersedia dari Biteship.',
        'raw'     => $decoded,
    ]);
    exit;
}

// ===================== KIRIM KE FRONTEND =====================
echo json_encode([
    'success'  => true,
    'services' => $services,
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
exit;
