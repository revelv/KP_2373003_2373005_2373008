<?php
declare(strict_types=1);

/**
 * Konfigurasi Komship Sandbox
 * (buat produksi, pindahin API key ke env/.ini)
 */
const KOMSHIP_API_KEY  = '3I7kuf7B3e00fb2d23c692a69owo8BSW';
const KOMSHIP_STORE_URL = 'https://api-sandbox.collaborator.komerce.id/order/api/v1/orders/store';

/**
 * Kirim order ke Komship (Store Order)
 *
 * @param array $orderData payload persis sesuai dokumentasi Komship
 * @return array {
 *   http_code, curl_error, raw_body,
 *   meta_message, komship_order_no, komship_awb, komship_status
 * }
 */
function kirimOrderKeKomship(array $orderData): array
{
    $ch = curl_init(KOMSHIP_STORE_URL);

    $jsonBody = json_encode($orderData, JSON_UNESCAPED_UNICODE);
    if ($jsonBody === false) {
        return [
            'http_code'        => 0,
            'curl_error'       => 'JSON_ENCODE_ERROR',
            'raw_body'         => '',
            'meta_message'     => 'Gagal encode JSON payload.',
            'komship_order_no' => null,
            'komship_awb'      => null,
            'komship_status'   => 'ERROR_JSON',
        ];
    }

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS      => 5,
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'Accept: application/json',
            'x-api-key: ' . KOMSHIP_API_KEY,
        ],
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $jsonBody,
    ]);

    $body      = curl_exec($ch);
    $curlError = curl_error($ch);
    $httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $result = [
        'http_code'        => $httpCode,
        'curl_error'       => $curlError,
        'raw_body'         => $body,
        'meta_message'     => null,
        'komship_order_no' => null,
        'komship_awb'      => null,
        'komship_status'   => null,
    ];

    if ($body === false) {
        $result['komship_status'] = 'ERROR_CURL';
        return $result;
    }

    $json = json_decode($body, true);
    if (!is_array($json)) {
        $result['komship_status'] = 'ERROR_PARSE';
        return $result;
    }

    $meta = $json['meta'] ?? [];
    $data = $json['data'] ?? null;

    if (isset($meta['message'])) {
        $result['meta_message'] = (string)$meta['message'];
    }

    if ($httpCode >= 200 && $httpCode < 300 && is_array($data)) {
        // Sukses create order
        $result['komship_order_no'] = $data['order_no'] ?? null;
        $result['komship_awb']      = $data['awb']      ?? null;
        $result['komship_status']   = 'SUCCESS';
    } else {
        // Gagal, tapi kita tetap simpan HTTP code + meta message
        $result['komship_status'] = 'ERROR_HTTP_' . $httpCode;
    }

    return $result;
}
