<?php
// user/rajaongkir/track-resi.php
declare(strict_types=1);

session_start();
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../koneksi.php';

// ==== KONFIG ====
const RO_API_KEY = 'KlJTvKcb3e00fb2d23c692a6dYH8Lv1z'; // ganti kalau beda

function fail(string $m, array $extra = []) {
  echo json_encode(['success' => false, 'message' => $m] + $extra);
  exit;
}
function ok(array $p) {
  echo json_encode(['success' => true] + $p);
  exit;
}

// ==== INPUT ====
$orderId = trim($_POST['order_id'] ?? '');
$courier = strtolower(trim($_POST['courier'] ?? ''));

// alamat/tujuan dikirim dari FE (opsional untuk log)
$alamat   = trim($_POST['alamat']   ?? '');
$cityprov = trim($_POST['cityprov'] ?? '');
$provRaw  = trim($_POST['prov_raw'] ?? '');
$cityRaw  = trim($_POST['city_raw'] ?? '');

if ($orderId === '' || $courier === '') {
  fail('order_id / courier kosong.');
}

// Pastikan order exist + ambil courier name (opsional, tapi bagus buat sanity)
$sql = "SELECT o.order_id, o.code_courier, COALESCE(c.nama_kurir,'') AS nama_kurir
        FROM orders o
        LEFT JOIN courier c ON c.code_courier = o.code_courier
        WHERE o.order_id = ? LIMIT 1";
$st = $conn->prepare($sql);
$st->bind_param('s', $orderId);
$st->execute();
$ord = $st->get_result()->fetch_assoc();
$st->close();
if (!$ord) fail('Order tidak ditemukan.');

// ==== CALL TRACKING API ====
$endpoint = 'https://rajaongkir.komerce.id/api/v1/track/waybill';
$ch = curl_init($endpoint);
curl_setopt_array($ch, [
  CURLOPT_POST           => true,
  CURLOPT_POSTFIELDS     => http_build_query(['awb' => $orderId, 'courier' => $courier]),
  CURLOPT_HTTPHEADER     => ['key: ' . RO_API_KEY, 'Accept: application/json'],
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_TIMEOUT        => 25,
]);
$resp = curl_exec($ch);
$http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$cerr = ($resp === false) ? curl_error($ch) : null;
curl_close($ch);

if ($resp === false) fail('Gagal konek: ' . $cerr);
$body = json_decode($resp, true);
if ($http >= 400 || !is_array($body)) {
  fail('Respon bukan JSON / HTTP ' . $http, ['raw' => $resp]);
}

// ==== PARSE ====
$summary = [
  'courier' => $body['data']['courier'] ?? $ord['nama_kurir'] ?? strtoupper($courier),
  'waybill' => $body['data']['waybill'] ?? $orderId,
  'status'  => $body['data']['status']  ?? ($body['meta']['message'] ?? ''),
];

$events = [];
if (!empty($body['data']['events']) && is_array($body['data']['events'])) {
  foreach ($body['data']['events'] as $ev) {
    $events[] = [
      'time' => $ev['datetime'] ?? ($ev['date'] ?? ''),
      'desc' => $ev['desc'] ?? ($ev['description'] ?? ''),
      'loc'  => $ev['location'] ?? ($ev['city'] ?? ''),
    ];
  }
}

// ==== SIMPAN KE order_tracking ====
/**
 * Asumsi tabel:
 * order_tracking(
 *   id BIGINT AI PK,
 *   order_id VARCHAR(64),
 *   courier  VARCHAR(32),
 *   event_time DATETIME NULL,
 *   description TEXT,
 *   location VARCHAR(255),
 *   status_summary VARCHAR(64),
 *   dest_address TEXT,
 *   dest_cityprov VARCHAR(255),
 *   dest_city_raw VARCHAR(64),
 *   dest_prov_raw VARCHAR(64),
 *   created_at DATETIME
 * )
 *
 * Kalau schema lo beda, tinggal sesuaikan kolom INSERT di bawah.
 */
$ins = $conn->prepare(
  "INSERT INTO order_tracking
    (order_id, courier, event_time, description, location, status_summary,
     dest_address, dest_cityprov, dest_city_raw, dest_prov_raw, created_at)
   VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())"
);

$statusSummary = $summary['status'] ?? '';
$destText = trim($alamat . ($cityprov ? ' — ' . $cityprov : ''));
if (!$events) {
  // tetap log 1 baris ringkasan supaya ada jejak
  $null = null;
  $ins->bind_param(
    'ssssssssss',
    $orderId, $courier, $null, $statusSummary, $null, $statusSummary,
    $alamat, $cityprov, $cityRaw, $provRaw
  );
  $ins->execute();
} else {
  foreach ($events as $e) {
    $when = $e['time'] !== '' ? date('Y-m-d H:i:s', strtotime($e['time'])) : null;
    $desc = $e['desc'] ?? '';
    $loc  = $e['loc']  ?? '';
    $ins->bind_param(
      'ssssssssss',
      $orderId, $courier, $when, $desc, $loc, $statusSummary,
      $alamat, $cityprov, $cityRaw, $provRaw
    );
    $ins->execute();
  }
}
$ins->close();

// (Opsional) update status orders dari summary
if ($statusSummary !== '') {
  $up = $conn->prepare("UPDATE orders SET status=? WHERE order_id=?");
  $up->bind_param('ss', $statusSummary, $orderId);
  $up->execute();
  $up->close();
}

// ==== RESPON KE FE ====
ok([
  'summary'   => $summary,
  'events'    => $events,
  'dest_text' => $destText
]);
