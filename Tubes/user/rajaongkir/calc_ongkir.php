<?php
// user/calc_ongkir.php
declare(strict_types=1);

session_start();
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../koneksi.php';

/**
 * ===== KONFIG =====
 * NOTE: Banyak kasus harga “sama” terjadi karena origin/destination salah.
 * Pastikan ORIGIN_LOCATION_ID = CITY_ID Bandung versi API Komerce kamu.
 * Kalau ragu, sementara isi dengan city_id Bandung yang valid di lingkunganmu.
 */
const RO_API_KEY         = 'KlJTvKcb3e00fb2d23c692a6dYH8Lv1z';
const ORIGIN_LOCATION_ID = 55; // <- ganti kalau perlu (CITY_ID Bandung di Komerce/RajaOngkir-mu)

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

/* ========== Validasi input dari FE ========== */
$codeCourier = $_POST['code_courier'] ?? '';
if (!preg_match('/^[a-z0-9_\-]{2,20}$/i', $codeCourier)) {
  json_fail('Kode kurir tidak valid.');
}
$courier = strtolower($codeCourier);

$rawItems = $_POST['selected_items'] ?? [];
if (!is_array($rawItems) || !$rawItems) json_fail('Item tidak dikirim.');
$cartIds = array_values(array_unique(array_map('intval', $rawItems)));
$cartIds = array_filter($cartIds, fn($v) => $v > 0);
if (!$cartIds) json_fail('Daftar item tidak valid.');

$_SESSION['checkout_courier'] = $courier;

if (!isset($_SESSION['kd_cs'])) json_fail('Silakan login dulu.');
$customerId = (int)$_SESSION['kd_cs'];

/* ========== Tentukan DESTINATION CITY_ID dengan prioritas:
 * 1) dest_city_id dari FE (alamat custom/profil yang punya ID),
 * 2) kota (city_id) dari tabel customer
 * 3) (opsional) kalau tetap kosong => gagal (jangan fallback sembarangan)
 * =============================================== */
$destCityId = trim((string)($_POST['dest_city_id'] ?? '')); // dari FE
if ($destCityId !== '' && !ctype_digit($destCityId)) {
  json_fail('Format dest_city_id tidak valid.');
}

if ($destCityId === '') {
  // fallback ke profil user
  $stmt = $conn->prepare("SELECT kota FROM customer WHERE customer_id = ? LIMIT 1");
  if (!$stmt) json_fail('Gagal mempersiapkan query profil: ' . mysqli_error($conn));
  $stmt->bind_param('i', $customerId);
  $stmt->execute();
  $res = $stmt->get_result();
  if ($res && ($row = $res->fetch_assoc())) {
    // ASSUMPTION: kolom customer.kota = city_id (bukan nama)
    $tmp = (string)($row['kota'] ?? '');
    if ($tmp !== '' && ctype_digit($tmp)) $destCityId = $tmp;
  }
  $stmt->close();
}

if ($destCityId === '' || (int)$destCityId <= 0) {
  json_fail('ID tujuan (kota) tidak ditemukan / tidak valid. Lengkapi alamat dulu.');
}

/* ========== Hitung total berat (gram) dari cart ========== */
$idsIn = implode(',', $cartIds);
$sql = "
  SELECT c.jumlah_barang AS qty, p.weight
  FROM carts c
  JOIN products p ON p.product_id = c.product_id
  WHERE c.cart_id IN ($idsIn)
";
$res = mysqli_query($conn, $sql);
if (!$res) json_fail('Gagal ambil data keranjang: ' . mysqli_error($conn));

$totalWeight = 0;
while ($row = mysqli_fetch_assoc($res)) {
  $qty = max(1, (int)$row['qty']);
  $w   = max(0, (int)$row['weight']); // gram
  $totalWeight += ($w * $qty);
}
mysqli_free_result($res);
/* Minimal 1 gram supaya nggak 0 */
$totalWeight = max(1, $totalWeight);

/* ========== Panggil API Komerce: domestic-cost ========== */
$endpoint = 'https://rajaongkir.komerce.id/api/v1/calculate/domestic-cost';

/**
 * PENTING:
 * - origin/destination adalah CITY_ID versi Komerce
 * - weight = gram
 * - courier = kode kurir (jne, jnt, sicepat, pos, tiki, anteraja, dll)
 */
$postBody = http_build_query([
  'origin'      => (int)ORIGIN_LOCATION_ID,
  'destination' => (int)$destCityId,
  'weight'      => (int)$totalWeight,
  'courier'     => $courier,
  // 'price'    => 'lowest', // opsional
], '', '&', PHP_QUERY_RFC3986);

$ch = curl_init($endpoint);
curl_setopt_array($ch, [
  CURLOPT_POST           => true,
  CURLOPT_POSTFIELDS     => $postBody,
  CURLOPT_HTTPHEADER     => [
    'key: ' . RO_API_KEY,
    'Content-Type: application/x-www-form-urlencoded',
    'Accept: application/json',
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
  json_fail('Gagal konek ke RajaOngkir: ' . $cerr, [
    'debug' => [
      'hit_url'     => $endpoint,
      'http'        => $http,
      'origin_id'   => ORIGIN_LOCATION_ID,
      'dest_id'     => $destCityId,
      'weight_gram' => $totalWeight,
      'courier'     => $courier,
      'post_body'   => $postBody,
    ]
  ]);
}

$body = json_decode($resp, true);
/* Kadang server balikin HTML (error gateway) -> bikin harga sama sebelumnya */
if (!is_array($body)) {
  json_fail('Respon tidak valid dari RajaOngkir (bukan JSON).', [
    'http'  => $http,
    'raw'   => $resp,
    'debug' => [
      'origin_id'   => ORIGIN_LOCATION_ID,
      'dest_id'     => $destCityId,
      'weight_gram' => $totalWeight,
      'courier'     => $courier,
    ]
  ]);
}

/* Validasi meta/status kalau ada */
$metaStatus  = $body['meta']['status']  ?? $body['meta']['code'] ?? null;
$metaMessage = $body['meta']['message'] ?? null;
if ($http >= 400 || ($metaStatus !== null && (int)$metaStatus >= 400)) {
  json_fail('API error: ' . ($metaMessage ?: 'HTTP ' . $http), ['raw' => $body]);
}

/* ===== Normalisasi bentuk data layanan =====
 * Komerce biasanya: data[] => { code, service, cost, etd }
 * Tapi jaga-jaga: key bisa beda (courier, price/value, service_name)
 */
$services = [];
if (isset($body['data']) && is_array($body['data'])) {
  foreach ($body['data'] as $row) {
    $svcName = (string)($row['service'] ?? $row['service_name'] ?? $row['name'] ?? '');
    $cost    = (int)($row['cost'] ?? $row['value'] ?? $row['price'] ?? 0);
    if ($svcName === '' || $cost <= 0) continue;

    $services[] = [
      'courier' => strtolower((string)($row['code'] ?? $row['courier'] ?? $courier)),
      'service' => $svcName,
      'etd'     => isset($row['etd']) ? (string)$row['etd'] : '',
      'cost'    => $cost,
    ];
  }
}

/* Kalau kosong, jangan fallback — kasi tau alasan biar UI jelas */
if (!$services) {
  json_fail('Tidak ada layanan yang tersedia' . ($metaMessage ? (': ' . $metaMessage) : ''), [
    'raw' => $body,
    'debug' => [
      'origin_id'   => ORIGIN_LOCATION_ID,
      'dest_id'     => $destCityId,
      'weight_gram' => $totalWeight,
      'courier'     => $courier,
    ]
  ]);
}

/* Urut termurah dulu */
usort($services, fn($a, $b) => $a['cost'] <=> $b['cost']);

json_ok([
  'services' => $services,
  'debug' => [
    'hit_url'     => $endpoint,
    'http'        => $http,
    'origin_id'   => ORIGIN_LOCATION_ID,
    'dest_id'     => (int)$destCityId,
    'weight_gram' => (int)$totalWeight,
    'courier'     => $courier,
  ]
]);
