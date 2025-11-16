<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');

// ================== KONFIG RAJAONGKIR V2 ==================
const RO_API_KEY  = 'KlJTvKcb3e00fb2d23c692a6dYH8Lv1z';    // API key lu
const RO_BASE_URL = 'https://rajaongkir.komerce.id/api/v1';
// ==========================================================

// Ambil city_id dari query string
$cityId = isset($_GET['city']) ? trim((string)$_GET['city']) : '';

if ($cityId === '') {
    echo json_encode([
        'success' => false,
        'message' => 'Parameter city (city_id) kosong',
        'data'    => []
    ]);
    exit;
}

// Endpoint V2 yang benar:
// https://rajaongkir.komerce.id/api/v1/destination/district/{city_id}
$url = RO_BASE_URL . '/destination/district/' . urlencode($cityId);

$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT        => 20,
    CURLOPT_HTTPHEADER     => [
        'key: ' . RO_API_KEY,
        'Accept: application/json',
    ],
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error    = ($response === false) ? curl_error($ch) : null;
curl_close($ch);

// Error koneksi
if ($response === false) {
    echo json_encode([
        'success' => false,
        'message' => 'cURL error: ' . $error,
        'data'    => []
    ]);
    exit;
}

// Parse JSON dari RajaOngkir
$body = json_decode($response, true);

// Error dari sisi RajaOngkir / format aneh
if ($httpCode >= 400 || !is_array($body)) {
    echo json_encode([
        'success' => false,
        'message' => 'Respon tidak valid dari RajaOngkir (HTTP ' . $httpCode . ')',
        'data'    => [],
        'raw'     => $response   // bisa dihapus kalau ga mau kelihatan
    ]);
    exit;
}

// V2: data ada di $body['data']
$rows = $body['data'] ?? [];
$out  = [];

if (is_array($rows)) {
    foreach ($rows as $d) {
        $out[] = [
            // kita normalisasi ke subdistrict_* biar bisa dipakai di mana aja
            'subdistrict_id'   => $d['id'] ?? $d['district_id'] ?? null,
            'subdistrict_name' => $d['name'] ?? $d['district_name'] ?? '',
            'zip_code'         => $d['zip_code'] ?? '',
        ];
    }
}

echo json_encode([
    'success' => true,
    'message' => 'OK',
    'data'    => $out
]);
