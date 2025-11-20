<?php
// pickup_test.php
// File test sederhana untuk coba endpoint Pickup Request Komship (sandbox)

// ====== KONFIGURASI ======
$apiKey  = '3I7kuf7B3e00fb2d23c692a69owo8BSW'; // ganti kalau perlu
$orderNo = 'KOM77918202511200024';                // GANTI ke komship_order_no lu sendiri (hasil create order)

// Biar ga kena error "pickup time at least 90 minutes before pickup",
// kita set default pickup minimal +2 jam dari sekarang
date_default_timezone_set('Asia/Jakarta');

$now      = time();
$pickupTs = $now + 2 * 3600; // +2 jam

$pickupDate = date('Y-m-d', $pickupTs); // format YYYY-MM-DD
$pickupTime = date('H:i',   $pickupTs); // format HH:MM


$payload = [
    "pickup_date"    => $pickupDate,
    "pickup_time"    => $pickupTime,
    "pickup_vehicle" => "Motor",        // atau "Mobil", "Truk"
    "orders"         => [
        [
            "order_no" => $orderNo
        ]
    ]
];

$ch = curl_init();

curl_setopt_array($ch, [
    CURLOPT_URL            => 'https://api-sandbox.collaborator.komerce.id/order/api/v1/pickup/request',
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_ENCODING       => '',
    CURLOPT_MAXREDIRS      => 10,
    CURLOPT_TIMEOUT        => 0,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
    CURLOPT_CUSTOMREQUEST  => 'POST',
    CURLOPT_POSTFIELDS     => json_encode($payload),
    CURLOPT_HTTPHEADER     => [
        'Content-Type: application/json',
        'Accept: application/json',
        'x-api-key: ' . $apiKey,
    ],
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error    = curl_error($ch);

curl_close($ch);

// ====== OUTPUT HASIL TEST ======
header('Content-Type: text/plain; charset=utf-8');

echo "HTTP CODE: {$httpCode}\n\n";

if ($error) {
    echo "cURL ERROR:\n{$error}\n";
    exit;
}

echo "PAYLOAD YANG DIKIRIM:\n";
echo json_encode($payload, JSON_PRETTY_PRINT) . "\n\n";

echo "RESPONSE DARI KOMSHIP:\n";
echo $response . "\n";
