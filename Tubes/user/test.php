<?php
// GANTI pake API KEY sandbox kamu
const KOMSHIP_API_KEY = '3I7kuf7B3e00fb2d23c692a69owo8BSW';

$payload = [
    "order_date"            => "2025-05-21",
    "brand_name"            => "Xiaomi Official",
    "shipper_name"          => "XIAOMI",
    "shipper_phone"         => "82121669737",
    "shipper_destination_id"=> 5969,
    "shipper_address"       => "Alamat pengirim",
    "shipper_email"         => "garut@gmail.com",
    "origin_pin_point"      => "-7.274631, 109.207174",
    "receiver_name"         => "Buyer Bandung",
    "receiver_phone"        => "8123458282",
    "receiver_destination_id"=> 4956,
    "receiver_address"      => "Alamat penerima",
    "receiver_email"        => "buyer@test.com",
    "shipping"              => "JNE",
    "shipping_type"         => "REG23",
    "payment_method"        => "BANK TRANSFER",
    "shipping_cost"         => 16000,
    "shipping_cashback"     => 4000,
    "service_fee"           => 0,
    "additional_cost"       => 0,
    "grand_total"           => 516000,
    "cod_value"             => 0,
    "insurance_value"       => 5631.11,
    "order_details" => [
        [
            "product_name"          => "Xiaomi Redmi Note 99",
            "product_variant_name"  => "Blue 8/256",
            "product_price"         => 315555,
            "product_weight"        => 1000,
            "product_width"         => 10,
            "product_height"        => 8,
            "product_length"        => 50,
            "qty"                   => 1,
            "subtotal"              => 315555
        ]
    ]
];

$ch = curl_init('https://api-sandbox.collaborator.komerce.id/order/api/v1/orders/store');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_HTTPHEADER     => [
        'x-api-key: ' . KOMSHIP_API_KEY,
        'Content-Type: application/json',
        'Accept: application/json',
    ],
    CURLOPT_POSTFIELDS     => json_encode($payload),
]);

$body  = curl_exec($ch);
$err   = curl_error($ch);
$code  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

header('Content-Type: application/json');
echo json_encode([
    'http_code' => $code,
    'curl_error'=> $err,
    'body'      => json_decode($body, true),
], JSON_PRETTY_PRINT);
