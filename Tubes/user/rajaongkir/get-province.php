<?php
header('Content-Type: application/json; charset=utf-8');

$API_KEY = 'j7mlOjpseb8ec09cadbb3a603ywK7H22';
$BASE    = 'https://rajaongkir.komerce.id/api/v1';

$ch = curl_init($BASE . '/destination/province');
curl_setopt_array($ch, [
  CURLOPT_HTTPHEADER     => ['Accept: application/json', 'key: ' . $API_KEY],
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_TIMEOUT        => 20,
]);
$resp = curl_exec($ch);
$err  = curl_error($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($err) {
  http_response_code(500);
  echo json_encode(['error' => "cURL error: $err"]);
  exit;
}
if ($code < 200 || $code >= 300) {
  http_response_code($code);
  echo json_encode(['error' => "API HTTP $code", 'raw' => $resp]);
  exit;
}

$json = json_decode($resp, true);
$data = $json['data'] ?? $json; // dukung 2 bentuk
echo json_encode($data, JSON_UNESCAPED_UNICODE);
