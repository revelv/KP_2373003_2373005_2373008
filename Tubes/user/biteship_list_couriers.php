<?php
// user/biteship_list_couriers.php
declare(strict_types=1);

header('Content-Type: application/json');

// ==== GANTI API KEY DI SINI ====
const BITESHIP_API_KEY = 'biteship_test.eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJuYW1lIjoiU3R5cmtfaW5kdXN0cmllcyIsInVzZXJJZCI6IjY5MjA3ZmI0YzMxM2VmYTUyZTM5OThlNCIsImlhdCI6MTc2Mzc4NTQ0OH0.dBPLQHoBBV4gnXux-OMziAO5yr1TBzXTf4T-Js2b0ak';

// Endpoint resmi: GET /v1/couriers (list kurir) – lihat docs Biteship
// https://biteship.com/id/docs/api/couriers/overview

$ch = curl_init('https://api.biteship.com/v1/couriers');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER     => [
        'Authorization: Bearer ' . BITESHIP_API_KEY,
        'Accept: application/json',
    ],
    CURLOPT_TIMEOUT        => 30,
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

if ($response === false) {
    $err = curl_error($ch);

    echo json_encode([
        'success' => false,
        'message' => 'Gagal konek ke Biteship: ' . $err,
    ]);
    exit;
}


$data = json_decode($response, true);
if (!is_array($data) || !($data['success'] ?? false) || !isset($data['couriers']) || !is_array($data['couriers'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Respon Biteship tidak valid / tidak sukses. HTTP ' . $httpCode,
    ]);
    exit;
}

// Biar ringan, kirim field penting aja ke frontend
$out = [];
foreach ($data['couriers'] as $c) {
    $out[] = [
        'courier_code'         => $c['courier_code']         ?? '',
        'courier_name'         => $c['courier_name']         ?? '',
        'courier_service_name' => $c['courier_service_name'] ?? '',
        'courier_service_code' => $c['courier_service_code'] ?? '',
        'tier'                 => $c['tier']                 ?? '',
        'description'          => $c['description']          ?? '',
    ];
}

echo json_encode([
    'success'  => true,
    'couriers' => $out,
]);
