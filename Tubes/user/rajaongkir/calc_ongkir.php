<?php
// user/calc_ongkir.php
declare(strict_types=1);

session_start();
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../koneksi.php';

// ===== CONFIG =====
const RO_API_KEY         = 'KlJTvKcb3e00fb2d23c692a6dYH8Lv1z'; // header "key: ..."
const ORIGIN_LOCATION_ID = 55; // Bandung

// ===== Helper =====
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

// ===== Validasi input dari FE =====
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

// ===== Ambil tujuan: customer.kota (id lokasi domestic) =====
if (!isset($_SESSION['kd_cs'])) json_fail('Silakan login dulu.');
$customerId = (int)$_SESSION['kd_cs'];

$destId = null;
if ($stmt = mysqli_prepare($conn, "SELECT kota FROM customer WHERE customer_id = ? LIMIT 1")) {
  mysqli_stmt_bind_param($stmt, 'i', $customerId);
  mysqli_stmt_execute($stmt);
  $res = mysqli_stmt_get_result($stmt);
  if ($res && ($row = mysqli_fetch_assoc($res))) {
    $destId = (int)($row['kota'] ?? 0);
    mysqli_free_result($res);
  }
  mysqli_stmt_close($stmt);
}
if (!$destId) json_fail('ID tujuan (kota) tidak ditemukan di profil.');

// ===== Hitung total berat (gram) dari carts.jumlah_barang × products.weight =====
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
$totalWeight = max(1, $totalWeight);

// ===== CALL: Komerce v1 calculate/domestic-cost =====
$endpoint = 'https://rajaongkir.komerce.id/api/v1/calculate/domestic-cost';
$postBody = http_build_query([
  'origin'      => ORIGIN_LOCATION_ID,
  'destination' => $destId,
  'weight'      => $totalWeight,
  'courier'     => $courier,
  // 'price'    => 'lowest' | 'highest' // optional
]);

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
]);
$resp = curl_exec($ch);
$http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$cerr = ($resp === false) ? curl_error($ch) : null;
curl_close($ch);

if ($resp === false) json_fail('Gagal konek ke RajaOngkir: ' . $cerr);

$body = json_decode($resp, true);
if ($http >= 400 || !is_array($body)) {
  json_fail('Respon tidak valid dari RajaOngkir (HTTP ' . $http . ').', ['raw' => $resp]);
}

// ===== Parse: data[] { code, service, cost, etd, ... } =====
$services = [];
if (isset($body['data']) && is_array($body['data'])) {
  foreach ($body['data'] as $row) {
    if (!isset($row['service'], $row['cost'])) continue;
    $services[] = [
      'courier' => strtolower((string)($row['code'] ?? $courier)),
      'service' => (string)$row['service'],
      'etd'     => isset($row['etd']) ? (string)$row['etd'] : '',
      'cost'    => (int)$row['cost'],
    ];
  }
}
if (!$services) {
  $metaMsg = $body['meta']['message'] ?? 'Tidak ada layanan';
  json_fail('Tidak ada layanan yang tersedia: ' . $metaMsg, ['raw' => $body]);
}

// sort dari termurah
usort($services, fn($a, $b) => $a['cost'] <=> $b['cost']);

json_ok([
  'services' => $services,
  'debug' => [
    'hit_url'     => $endpoint,
    'http'        => $http,
    'origin_id'   => ORIGIN_LOCATION_ID,
    'dest_id'     => $destId,
    'weight_gram' => $totalWeight,
    'courier'     => $courier,
  ]
]);
