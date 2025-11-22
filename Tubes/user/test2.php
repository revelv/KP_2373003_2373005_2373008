<?php
// ====== CONFIG ======
$apiKey = 'biteship_test.eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJuYW1lIjoiU3R5cmtfaW5kdXN0cmllcyIsInVzZXJJZCI6IjY5MjA3ZmI0YzMxM2VmYTUyZTM5OThlNCIsImlhdCI6MTc2Mzc4NTQ0OH0.dBPLQHoBBV4gnXux-OMziAO5yr1TBzXTf4T-Js2b0ak'; // Ganti pake API key Biteship lu

$courier = 'jne'; 

$waybill = 'WYB-1763809853530';

if ($waybill === '') {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'success' => false,
        'message' => 'Kasih parameter ?waybill=NO_RESINYA dulu bro, dan ?courier=jne (atau kurir lain).',
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}

// ====== ENDPOINT TRACKING BITESHIP ======
// Pola umum: /v1/trackings/{courier_code}/{waybill}
$url = "https://api.biteship.com/v1/trackings/{$waybill}/couriers/{$courier}";

// ====== CURL REQUEST ======
$ch = curl_init($url);

curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPGET        => true,
    CURLOPT_HTTPHEADER     => [
        'Accept: application/json',
        'Authorization: ' . $apiKey,
    ],
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

if ($response === false) {
    $err = curl_error($ch);
    curl_close($ch);

    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'success'   => false,
        'http_code' => null,
        'message'   => 'cURL Error: ' . $err,
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}

curl_close($ch);

// Decode biar enak dibaca
$decoded = json_decode($response, true);

// ====== OUTPUT TEST ======
header('Content-Type: application/json; charset=utf-8');
echo json_encode([
    'success'    => true,
    'http_code'  => $httpCode,
    'courier'    => $courier,
    'waybill'    => $waybill,
    'raw_body'   => $decoded ?? $response,
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
