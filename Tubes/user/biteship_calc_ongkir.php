<?php
// biteship_calc_ongkir.php
declare(strict_types=1);
session_start();
require_once '../koneksi.php';

// ====== Cek login (opsional, sama kayak file lain) ======
if (!isset($_SESSION['kd_cs'])) {
    http_response_code(200);
    echo json_encode([
        'success' => false,
        'message' => 'Anda harus login terlebih dahulu.'
    ]);
    exit;
}

header('Content-Type: application/json');

// ====== Ambil data POST dari JS ======
$destination_postal = trim($_POST['destination_postal_code'] ?? '');
$courier_code       = trim($_POST['code_courier'] ?? '');
$base_total         = (int)($_POST['base_total'] ?? 0);

// VALIDASI PALING PENTING
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

// ====== CONFIG TOKO (EDIT SESUAI TOKO LU) ======
// contoh: Bandung 40218
$origin_postal = '40161';

// API key Biteship (SANDBOX / LIVE)
$BITESHIP_API_KEY = 'biteship_test.eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJuYW1lIjoiU3R5cmtfaW5kdXN0cmllcyIsInVzZXJJZCI6IjY5MjA3ZmI0YzMxM2VmYTUyZTM5OThlNCIsImlhdCI6MTc2Mzc4NTQ0OH0.dBPLQHoBBV4gnXux-OMziAO5yr1TBzXTf4T-Js2b0ak'; // ganti bro

// ====== Hitung weight (sementara 1 kg dulu) ======
// Kalau mau serius, nanti tarik dari DB carts + products.berat per item
$total_weight_gram = 1000; // 1kg dummy biar API nggak 400 "weight required"
if ($total_weight_gram <= 0) {
    $total_weight_gram = 1000;
}

// ====== Susun payload buat Biteship /v1/rates/couriers ======
$payload = [
    'origin_postal_code'      => $origin_postal,
    'destination_postal_code' => $destination_postal,
    'couriers'                => $courier_code, // contoh: "jne"
    'items'                   => [
        [
            'name'        => 'Cart Items',
            'value'       => max(10000, $base_total), // nilai barang
            'weight'      => $total_weight_gram,
            'quantity'    => 1,
            'length'      => 10,
            'width'       => 10,
            'height'      => 10,
            'description' => 'Checkout dari Styrk Industries',
        ],
    ],
];

// ====== Panggil API Biteship ======
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
$httpCode     = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

// ====== Handle Error CURL ======
if ($curlErr) {
    echo json_encode([
        'success' => false,
        'message' => 'CURL error: ' . $curlErr,
    ]);
    exit;
}

// ====== Parse response Biteship ======
$decoded = json_decode($responseBody, true);

// Kalau HTTP code bukan 200, lempar pesan dari Biteship biar kelihatan salahnya apa
if ($httpCode !== 200) {
    $msg = 'API Biteship gagal: HTTP ' . $httpCode;
    if (is_array($decoded) && isset($decoded['errors'])) {
        // errors biasanya array of detail
        $msgDetail = is_array($decoded['errors'])
            ? json_encode($decoded['errors'])
            : (string)$decoded['errors'];
        $msg .= ' | ' . $msgDetail;
    } elseif (is_array($decoded) && isset($decoded['message'])) {
        $msg .= ' | ' . $decoded['message'];
    }

    echo json_encode([
        'success' => false,
        'message' => $msg,
        'raw'     => $decoded,  // boleh dihapus kalau udah beres debug
    ]);
    exit;
}

// ====== Ambil services dari Biteship ======
$services = [];
if (isset($decoded['pricing']) && is_array($decoded['pricing'])) {
    // format lama
    $pricingList = $decoded['pricing'];
} elseif (isset($decoded['rates']) && is_array($decoded['rates'])) {
    // kalau nanti Biteship ganti field name
    $pricingList = $decoded['rates'];
} else {
    $pricingList = [];
}

foreach ($pricingList as $p) {
    $services[] = [
        'courier' => $p['courier_name']   ?? ($p['courier'] ?? $courier_code),
        'service' => $p['courier_service_name'] ?? ($p['service_type'] ?? 'REG'),
        'cost'    => (int)($p['price']    ?? ($p['final_price'] ?? 0)),
        'etd'     => $p['estimated_days'] ?? ($p['estimated_duration'] ?? ''),
    ];
}

if (empty($services)) {
    echo json_encode([
        'success' => false,
        'message' => 'Tidak ada layanan yang tersedia dari Biteship.',
        'raw'     => $decoded,
    ]);
    exit;
}

// ====== Sukses ======
echo json_encode([
    'success'  => true,
    'services' => $services,
]);
