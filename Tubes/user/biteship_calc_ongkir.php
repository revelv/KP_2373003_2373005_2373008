<?php
// biteship_calc_ongkir.php
declare(strict_types=1);
session_start();
require_once '../koneksi.php';

header('Content-Type: application/json');

// ====== Cek login ======
if (!isset($_SESSION['kd_cs'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Anda harus login terlebih dahulu.'
    ]);
    exit;
}

$customer_id = (int) $_SESSION['kd_cs'];

// ====== Ambil data POST dari JS ======
$destination_postal = trim($_POST['destination_postal_code'] ?? '');
$courier_code       = trim($_POST['code_courier'] ?? '');
$base_total         = (int)($_POST['base_total'] ?? 0);

// selected_items[] dari payment.php (cart)
$selected_ids = $_POST['selected_items'] ?? [];
if (!is_array($selected_ids)) {
    $selected_ids = [$selected_ids];
}
$selected_ids = array_map('intval', $selected_ids);
$selected_ids = array_values(array_filter($selected_ids, fn($v) => $v > 0));

// ====== Validasi dasar ======
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

// ====== CONFIG TOKO ======
$origin_postal = '40161';

// API key Biteship (sandbox)
$BITESHIP_API_KEY = 'biteship_test.eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJuYW1lIjoiU3R5cmtfaW5kdXN0cmllcyIsInVzZXJJZCI6IjY5MjA3ZmI0YzMxM2VmYTUyZTM5OThlNCIsImlhdCI6MTc2Mzc4NTQ0OH0.dBPLQHoBBV4gnXux-OMziAO5yr1TBzXTf4T-Js2b0ak';

// ==========================
// AMBIL DATA CART DARI DB
// ==========================
$in_clause = implode(',', $selected_ids);

// Ambil: nama_produk, harga, berat, qty
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
    // FIX: kolomnya weight, bukan berat
    $berat  = (int)($row['weight'] ?? 0); // gram per unit

    // Fallback kalau berat belum diisi
    if ($berat <= 0) {
        $berat = 500; // 500 gram default per unit
    }

    // Total berat = berat per unit * qty
    $total_weight_gram += $berat * $qty;

    // Item per produk untuk dikirim ke Biteship
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

// ==========================
// SUSUN PAYLOAD BITESHIP
// ==========================
$payload = [
    'origin_postal_code'      => $origin_postal,
    'destination_postal_code' => $destination_postal,
    'couriers'                => $courier_code,
    'items'                   => $itemsPayload,
];

// ==========================
// PANGGIL API BITESHIP
// ==========================
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

// ====== Handle Error CURL ======
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

// Kalau HTTP code bukan 200, lempar pesan dari Biteship
if ($httpCode !== 200) {
    $msg = 'API Biteship gagal: HTTP ' . $httpCode;
    if (isset($decoded['errors'])) {
        $msgDetail = is_array($decoded['errors'])
            ? json_encode($decoded['errors'])
            : (string)$decoded['errors'];
        $msg .= ' | ' . $msgDetail;
    } elseif (isset($decoded['message'])) {
        $msg .= ' | ' . $decoded['message'];
    }

    echo json_encode([
        'success' => false,
        'message' => $msg,
        'raw'     => $decoded,
    ]);
    exit;
}

// ====== Ambil services dari Biteship ======
$pricingList = [];
if (isset($decoded['pricing']) && is_array($decoded['pricing'])) {
    $pricingList = $decoded['pricing']; // format umum Biteship sandbox
} elseif (isset($decoded['rates']) && is_array($decoded['rates'])) {
    $pricingList = $decoded['rates'];   // jaga-jaga format lain
}

$services = [];

foreach ($pricingList as $p) {
    $rawName = trim((string)($p['courier_service_name'] ?? '')); // contoh: "REG PACK"

    // ambil kata pertama aja → "REG PACK" => "reg"
    $firstWord = '';
    if ($rawName !== '') {
        $parts = preg_split('/\s+/', $rawName);
        $firstWord = strtolower($parts[0] ?? '');
    }

    // fallback, kalau entah kenapa kosong
    if ($firstWord === '') {
        $firstWord = strtolower((string)($p['courier_service_code'] ?? ''));
    }

    $services[] = [
        'courier'      => strtolower((string)($p['courier_code'] ?? '')), // jne, lion, jnt
        'service'      => $firstWord,      // ← dipakai ke order, misal "reg"
        'service_name' => $rawName,        // ← buat tampilan: "REG PACK"
        'cost'         => (int)($p['price'] ?? 0),
        'etd'          => trim(
            (string)($p['shipment_duration_range'] ?? '') . ' ' .
            (string)($p['shipment_duration_unit'] ?? '')
        ),
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
exit;
