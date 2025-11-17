<?php
// user/calc_ongkir.php
declare(strict_types=1);

session_start();
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../koneksi.php';

/*
 * ========================== KONFIG KOMSHIP (SANDBOX) ==========================
 * x-api-key  : API key Komship sandbox
 * shipper_destination_id : ID asal (gudang) dari endpoint:
 *   GET https://api-sandbox.collaborator.komerce.id/tariff/api/v1/destination/search?keyword=...
 * ===================================================================
 */
const KOMSHIP_API_KEY         = '3I7kuf7B3e00fb2d23c692a69owo8BSW'; // TODO: ganti pakai API key sandbox lu
const KOMSHIP_BASE_TARIFF     = 'https://api-sandbox.collaborator.komerce.id/tariff/api/v1';
const KOMSHIP_SHIPPER_DEST_ID = 4944;        // TODO: ganti ke destination_id gudang asal lu

// (OPSIONAL) pin point origin gudang (latitude,longitude)
const ORIGIN_PIN_POINT = '-7.279849431298132,109.35114360314475'; // TODO: ubah kalau perlu

/* ---------- Helper JSON ---------- */
function json_fail(string $msg, array $extra = []): void
{
    echo json_encode(['success' => false, 'message' => $msg] + $extra);
    exit;
}

function json_ok(array $payload): void
{
    echo json_encode(['success' => true] + $payload);
    exit;
}

/* ---------- Validasi login ---------- */
if (!isset($_SESSION['kd_cs'])) {
    json_fail('Silakan login dulu.');
}
$customerId = (int) $_SESSION['kd_cs'];

/* ---------- Ambil & validasi kode kurir dari FE ---------- */
$codeCourier = $_POST['code_courier'] ?? '';
if (!preg_match('/^[a-z0-9_\-]{2,20}$/i', $codeCourier)) {
    json_fail('Kode kurir tidak valid.');
}
$selectedCourier = strtoupper($codeCourier);

// simpan di session (biar ke-load lagi di payment)
$_SESSION['checkout_courier'] = $selectedCourier;

/* ---------- Ambil item (cart / order) ---------- */
$rawItems = $_POST['selected_items'] ?? [];
$orderId  = isset($_POST['order_id']) ? trim((string)$_POST['order_id']) : '';

if (!is_array($rawItems)) {
    $rawItems = [];
}
$cartIds = array_values(array_unique(array_map('intval', $rawItems)));
$cartIds = array_filter($cartIds, fn($v) => $v > 0);

if (!$cartIds && $orderId === '') {
    json_fail('Item tidak dikirim.');
}

/* ---------- Ambil data alamat dari FE (nama, bukan ID) ---------- */
$alamatMode    = $_POST['alamat_mode'] ?? '';
$provName      = trim((string)($_POST['provinsi'] ?? ''));
$cityName      = trim((string)($_POST['kota'] ?? ''));
$districtName  = trim((string)($_POST['kecamatan'] ?? ''));
$alamatDetail  = trim((string)($_POST['alamat'] ?? ''));

/**
 * Kalau nama dari FE kosong (misal user pilih "alamat profil"),
 * fallback ke tabel customer (yang sekarang simpan NAMA provinsi + kota + kecamatan).
 */
if ($provName === '' || $cityName === '' || $districtName === '') {
    $stmt = $conn->prepare("SELECT provinsi, kota, kecamatan, alamat FROM customer WHERE customer_id = ? LIMIT 1");
    if ($stmt) {
        $stmt->bind_param('i', $customerId);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res && ($row = $res->fetch_assoc())) {
            if ($provName === '')     $provName     = (string)($row['provinsi'] ?? '');
            if ($cityName === '')     $cityName     = (string)($row['kota'] ?? '');
            if ($districtName === '') $districtName = (string)($row['kecamatan'] ?? '');
            if ($alamatDetail === '') $alamatDetail = (string)($row['alamat'] ?? '');
        }
        $stmt->close();
    }
}

if ($cityName === '' && $districtName === '' && $provName === '') {
    json_fail('Alamat kosong, tidak bisa menentukan destinasi.');
}

/* ===================================================================
 * 1. Tentukan receiver_destination_id (Komship) dari NAMA
 *    - Pake /destination/search sandbox
 * =================================================================== */
$keyword = $districtName !== '' ? $districtName : ($cityName !== '' ? $cityName : $provName);

$destUrl = KOMSHIP_BASE_TARIFF . '/destination/search?keyword=' . urlencode($keyword);

$ch = curl_init($destUrl);
curl_setopt_array($ch, [
    CURLOPT_HTTPHEADER     => [
        'Accept: application/json',
        'x-api-key: ' . KOMSHIP_API_KEY,
    ],
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT        => 20,
    CURLOPT_FOLLOWLOCATION => true,
]);
$resp = curl_exec($ch);
$http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$cerr = ($resp === false) ? curl_error($ch) : null;
curl_close($ch);

if ($resp === false) {
    json_fail('Gagal konek ke Komship (destination): ' . $cerr, [
        'debug' => ['url' => $destUrl, 'http' => $http]
    ]);
}

$bodyDest = json_decode($resp, true);
if (!is_array($bodyDest) || !isset($bodyDest['data']) || !is_array($bodyDest['data'])) {
    json_fail('Respon Komship (destination) tidak valid.', [
        'http' => $http,
        'raw'  => $resp,
    ]);
}

$candidates = $bodyDest['data'];
if (count($candidates) === 0) {
    json_fail('Destination ID tidak ditemukan dari kota/kecamatan tersebut.', [
        'debug' => [
            'provinsi' => $provName,
            'kota'     => $cityName,
            'kecamatan'=> $districtName,
            'keyword'  => $keyword,
        ]
    ]);
}

$receiverDestinationId = null;
$destinationPinPoint   = null; // kalau API ngasih lat/long di data, bisa dipakai

$provLow = strtolower($provName);
$cityLow = strtolower($cityName);
$distLow = strtolower($districtName);

/**
 * Cari kandidat yang paling match:
 * - provinsi sama
 * - kota mengandung nama kota
 * - kecamatan mengandung nama kecamatan
 */
foreach ($candidates as $row) {
    $id          = (int)($row['id'] ?? 0);
    $cityRow     = strtolower((string)($row['city_name'] ?? ''));
    $subdistrict = strtolower((string)($row['subdistrict_name'] ?? ''));
    $provRow     = strtolower((string)($row['province_name'] ?? ''));

    if ($id <= 0) continue;

    $okProv = $provLow === '' ? true : (strpos($provRow, $provLow) !== false);
    $okCity = $cityLow === '' ? true : (strpos($cityRow, $cityLow) !== false);
    $okDist = $distLow === '' ? true : (strpos($subdistrict, $distLow) !== false);

    if ($okProv && $okCity && $okDist) {
        $receiverDestinationId = $id;
        // kalau di response ada pin point, isi:
        if (!empty($row['pin_point'])) {
            $destinationPinPoint = (string)$row['pin_point']; // pastikan format "lat,lon"
        }
        break;
    }
}

// Kalau belum ketemu yang pas → pakai kandidat pertama aja
if (!$receiverDestinationId) {
    $first = $candidates[0];
    $receiverDestinationId = (int)($first['id'] ?? 0);
    if (!empty($first['pin_point'])) {
        $destinationPinPoint = (string)$first['pin_point'];
    }
}

if ($receiverDestinationId <= 0) {
    json_fail('Destination ID Komship tidak valid.', [
        'debug' => ['candidates' => $candidates]
    ]);
}

/* ===================================================================
 * 2. Hitung total berat & total harga item
 *    - MODE CART      : dari carts + products
 *    - MODE BAYAR ULANG: dari order_details + products
 * =================================================================== */
$totalWeightGram  = 0;
$totalItemValue   = 0; // harga barang (buat item_value di Komship)

if ($cartIds) {
    // MODE CART
    $idsIn = implode(',', $cartIds);
    $sql   = "
        SELECT c.jumlah_barang AS qty, p.weight, p.harga
        FROM carts c
        JOIN products p ON p.product_id = c.product_id
        WHERE c.cart_id IN ($idsIn)
    ";
    $res = mysqli_query($conn, $sql);
    if (!$res) {
        json_fail('Gagal ambil data keranjang: ' . mysqli_error($conn));
    }

    while ($row = mysqli_fetch_assoc($res)) {
        $qty   = max(1, (int)$row['qty']);
        $w     = max(0, (int)$row['weight']);   // gram
        $price = max(0, (int)$row['harga']);    // rupiah
        $totalWeightGram += $w * $qty;
        $totalItemValue  += $price * $qty;
    }
    mysqli_free_result($res);

} elseif ($orderId !== '') {
    // MODE BAYAR ULANG
    $stmt = $conn->prepare("
        SELECT od.jumlah AS qty, od.harga_satuan AS price, p.weight
        FROM order_details od
        JOIN products p ON p.product_id = od.product_id
        WHERE od.order_id = ?
    ");
    if (!$stmt) {
        json_fail('Gagal menyiapkan query order_items: ' . mysqli_error($conn));
    }
    $stmt->bind_param('s', $orderId);
    $stmt->execute();
    $res = $stmt->get_result();
    if (!$res || $res->num_rows === 0) {
        $stmt->close();
        json_fail('Item order tidak ditemukan.');
    }
    while ($row = $res->fetch_assoc()) {
        $qty   = max(1, (int)$row['qty']);
        $w     = max(0, (int)$row['weight']);              // gram
        $price = max(0, (int)$row['price']);               // rupiah
        $totalWeightGram += $w * $qty;
        $totalItemValue  += $price * $qty;
    }
    $stmt->close();
}

// Minimal 1 gram dan 1 rupiah biar nggak 0
$totalWeightGram = max(1, $totalWeightGram);
$totalItemValue  = max(1, $totalItemValue);

// Komship pakai kg (boleh decimal), convert dari gram
$weightKg     = $totalWeightGram / 1000;
$weightKg     = max(0.1, $weightKg); // minimal 0.1 kg
$weightParam  = number_format($weightKg, 2, '.', '');

// kalau mau COD → "yes", kalau nggak → "no"
$codFlag = 'yes'; // atau 'no', sesuaikan

/* ===================================================================
 * 3. Call Komship Tariff Calculate (SANDBOX)
 *    Contoh:
 *    https://api-sandbox.collaborator.komerce.id/tariff/api/v1/calculate
 *      ?shipper_destination_id=...
 *      &receiver_destination_id=...
 *      &weight=1
 *      &item_value=300000
 *      &cod=yes
 *      &origin_pin_point=...
 *      &destination_pin_point=...
 * =================================================================== */
$queryParams = [
    'shipper_destination_id'   => KOMSHIP_SHIPPER_DEST_ID,
    'receiver_destination_id'  => $receiverDestinationId,
    'weight'                   => $weightParam,
    'item_value'               => $totalItemValue,
    'cod'                      => $codFlag,
];

if (ORIGIN_PIN_POINT !== '') {
    $queryParams['origin_pin_point'] = ORIGIN_PIN_POINT;
}
if ($destinationPinPoint !== null && $destinationPinPoint !== '') {
    $queryParams['destination_pin_point'] = $destinationPinPoint;
}

$calcUrl = KOMSHIP_BASE_TARIFF . '/calculate?' . http_build_query(
    $queryParams,
    '',
    '&',
    PHP_QUERY_RFC3986
);

$ch = curl_init($calcUrl);
curl_setopt_array($ch, [
    CURLOPT_HTTPHEADER     => [
        'Accept: application/json',
        'x-api-key: ' . KOMSHIP_API_KEY,
    ],
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT        => 25,
    CURLOPT_FOLLOWLOCATION => true,
]);
$resp = curl_exec($ch);
$http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$cerr = ($resp === false) ? curl_error($ch) : null;
curl_close($ch);

if ($resp === false) {
    json_fail('Gagal konek ke Komship (calculate): ' . $cerr, [
        'debug' => [
            'url'    => $calcUrl,
            'http'   => $http,
            'params' => $queryParams,
        ]
    ]);
}

$body = json_decode($resp, true);
if (!is_array($body)) {
    json_fail('Respon Komship (calculate) tidak valid (bukan JSON).', [
        'http' => $http,
        'raw'  => $resp,
    ]);
}

$metaCode    = (int)($body['meta']['code'] ?? 0);
$metaStatus  = strtolower((string)($body['meta']['status'] ?? ''));
$metaMessage = (string)($body['meta']['message'] ?? '');

if ($http >= 400 || $metaCode >= 400 || $metaStatus === 'error') {
    json_fail('Komship mengembalikan error: ' . ($metaMessage ?: 'HTTP ' . $http), [
        'http' => $http,
        'raw'  => $body,
    ]);
}

/* ===================================================================
 * 4. Normalisasi ke format FE:
 *    services[] = [
 *      'courier' => 'JNE',
 *      'service' => 'REG23',
 *      'cost'    => 9300,
 *      'etd'     => ''   // Komship tarif nggak kasih ETD, biarin kosong
 *    ]
 * =================================================================== */
$services = [];
$reguler  = $body['data']['calculate_reguler'] ?? [];

if (is_array($reguler)) {
    foreach ($reguler as $row) {
        $shippingName = strtoupper((string)($row['shipping_name'] ?? ''));
        $serviceName  = (string)($row['service_name'] ?? '');
        $cost         = (int)($row['shipping_cost'] ?? 0);

        if ($shippingName === '' || $serviceName === '' || $cost <= 0) {
            continue;
        }

        // filter: hanya kurir yang dipilih user di payment.php
        if ($selectedCourier && $shippingName !== $selectedCourier) {
            continue;
        }

        $services[] = [
            'courier' => $shippingName,
            'service' => $serviceName,
            'etd'     => '',
            'cost'    => $cost,
        ];
    }
}

if (!$services) {
    json_fail('Tidak ada layanan tersedia untuk kurir ini.', [
        'raw' => $body,
        'debug' => [
            'selected_courier'       => $selectedCourier,
            'shipper_destination_id' => KOMSHIP_SHIPPER_DEST_ID,
            'receiver_destination_id'=> $receiverDestinationId,
            'weight_kg'              => $weightParam,
        ]
    ]);
}

// Urut dari termurah
usort($services, fn($a, $b) => $a['cost'] <=> $b['cost']);

json_ok([
    'services' => $services,
    'debug'    => [
        'url'                    => $calcUrl,
        'shipper_destination_id' => KOMSHIP_SHIPPER_DEST_ID,
        'receiver_destination_id'=> $receiverDestinationId,
        'weight_kg'              => $weightParam,
        'item_value'             => $totalItemValue,
        'cod'                    => $codFlag,
        'provinsi'               => $provName,
        'kota'                   => $cityName,
        'kecamatan'              => $districtName,
    ],
]);
