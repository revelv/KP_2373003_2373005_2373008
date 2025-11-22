<?php
/**
 * Tester Biteship Maps Areas
 * Contoh endpoint:
 *   GET /v1/maps/areas?countries=ID&input=Jakarta+Selatan&type=single
 *
 * Cara pakai:
 *   biteship_maps_areas_test.php?input=Jakarta+Selatan&type=single
 *   biteship_maps_areas_test.php?input=40218
 */

// =============== API KEY ===============
$apiKey = 'biteship_test.eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJuYW1lIjoiU3R5cmtfaW5kdXN0cmllcyIsInVzZXJJZCI6IjY5MjA3ZmI0YzMxM2VmYTUyZTM5OThlNCIsImlhdCI6MTc2Mzc4NTQ0OH0.dBPLQHoBBV4gnXux-OMziAO5yr1TBzXTf4T-Js2b0ak'; // atau include 'config_biteship.php';

// =============== PARAM DARI QUERY ===============
$input   = isset($_GET['input'])   ? trim($_GET['input'])   : '';
$country = isset($_GET['country']) ? trim($_GET['country']) : 'ID';
$type    = isset($_GET['type'])    ? trim($_GET['type'])    : 'single';

// default input kalau kosong
if ($input === '') {
    // bisa nama area atau postal code
    $input = '40161';
}

// base URL sandbox
$baseUrl = 'https://api.biteship.com/v1/maps/areas';

$query = http_build_query([
    'countries' => $country,
    'input'     => $input,
    'type'      => $type,      // single / multiple (sesuai docs)
]);

$url = $baseUrl . '?' . $query;

// =============== EXECUTE CURL ===============
$ch = curl_init();

curl_setopt_array($ch, [
    CURLOPT_URL            => $url,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT        => 30,
    CURLOPT_SSL_VERIFYPEER => true,
    CURLOPT_SSL_VERIFYHOST => 2,
    CURLOPT_HTTPHEADER     => [
        'Authorization: Bearer ' . $apiKey,
        'Accept: application/json',
    ],
]);

$responseBody = curl_exec($ch);
$httpCode     = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlErrNo    = curl_errno($ch);
$curlErr      = curl_error($ch);

curl_close($ch);

// =============== OUTPUT ===============
header('Content-Type: text/plain; charset=utf-8');

echo "=== Biteship Maps Areas Test ===\n";
echo "Request URL : {$url}\n";
echo "HTTP Code   : {$httpCode}\n";
echo "cURL Error  : " . ($curlErrNo ? "{$curlErrNo} - {$curlErr}" : 'None') . "\n";
echo str_repeat('=', 60) . "\n\n";

echo "--- Raw Response ---\n";
echo ($responseBody === false ? '[NO RESPONSE]' : $responseBody) . "\n\n";

echo "--- Decoded JSON ---\n";
if ($responseBody !== false) {
    $json = json_decode($responseBody, true);
    if (json_last_error() === JSON_ERROR_NONE) {
        print_r($json);

        echo "\n--- Ringkasan (kalau ada data) ---\n";
        // Kalau type=single biasanya object tunggal, kalau multiple mungkin array
        if (isset($json['areas'])) {
            // beberapa implementasi pakai key 'areas'
            $areas = $json['areas'];
        } else {
            $areas = $json;
        }

        if (isset($areas[0])) {
            $first = $areas[0];
        } else {
            $first = $areas; // kalau langsung object
        }

        if (is_array($first)) {
            $name       = $first['name']        ?? '';
            $postalCode = $first['postal_code'] ?? ($first['zip_code'] ?? '');
            $country    = $first['country']     ?? '';
            $city       = $first['city']        ?? '';
            $state      = $first['administrative_division_level_1'] ?? '';

            echo "Nama Area   : {$name}\n";
            echo "Kota        : {$city}\n";
            echo "Provinsi    : {$state}\n";
            echo "Negara      : {$country}\n";
            echo "Postal Code : {$postalCode}\n";
        }
    } else {
        echo "JSON decode error: " . json_last_error_msg() . "\n";
    }
} else {
    echo "Tidak ada response body.\n";
}
