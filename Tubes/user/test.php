<?php
// ====== CONFIG ======
$apiKey = 'biteship_test.eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJuYW1lIjoiU3R5cmtfaW5kdXN0cmllcyIsInVzZXJJZCI6IjY5MjA3ZmI0YzMxM2VmYTUyZTM5OThlNCIsImlhdCI6MTc2Mzc4NTQ0OH0.dBPLQHoBBV4gnXux-OMziAO5yr1TBzXTf4T-Js2b0ak'; // ganti sama API key lu

// Endpoint create order Biteship
$url = 'https://api.biteship.com/v1/orders';

// ====== DUMMY DATA ORDER UNTUK TEST ======
$payload = [
    "shipper_contact_name"   => "Styrk Industries",
    "shipper_contact_phone"  => "081234567890",
    "shipper_contact_email"  => "admin@styrk.test",
    "shipper_organization"   => "Styrk Industries",
    "origin_contact_name"    => "Gudang Styrk",
    "origin_contact_phone"   => "081234567890",
    "origin_address"         => "Jl. Gudang Styrk No. 10",
    "origin_postal_code"     => "40212",        // Bandung (contoh, sesuaikan kalau mau)

    "destination_contact_name"   => "Dylan Test",
    "destination_contact_phone"  => "081234567891",
    "destination_contact_email"  => "dylan@test.com",
    "destination_address"        => "Jl. Test Order No. 123",
    "destination_postal_code"    => "10210",    // Jakarta (contoh, Biteship butuh KODE POS)

    "courier_company" => "jne",                // pastikan pakai salah satu yang muncul di list couriers lu
    "courier_type"    => "reg",            // contoh: "regular" / "express" (lihat dari API Biteship)

    "delivery_type"   => "now",                // atau "later"

    // item wajib: name, value, quantity, weight
    "items" => [
        [
            "name"        => "Tofu60 Redux Case",
            "description" => "Keyboard case test order",
            "value"       => 1850000,  // harga per item (Rp)
            "quantity"    => 1,
            "weight"      => 1500,     // gram
        ]
    ],

    // optional metadata
    "order_note"      => "Test create order dari Styrk",
];

// ====== CURL REQUEST ======
$ch = curl_init($url);

curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_HTTPHEADER     => [
        'Content-Type: application/json',
        'Accept: application/json',
        'Authorization: ' . $apiKey,
    ],
    CURLOPT_POSTFIELDS     => json_encode($payload),
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

if ($response === false) {
    echo "cURL Error: " . curl_error($ch);
    curl_close($ch);
    exit;
}

curl_close($ch);

// ====== TAMPILKAN HASIL ======
header('Content-Type: application/json; charset=utf-8');

echo json_encode([
    'http_code' => $httpCode,
    'raw_response' => json_decode($response, true),
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
