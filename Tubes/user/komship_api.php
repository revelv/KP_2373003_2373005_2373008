<?php
// user/komship_api.php
declare(strict_types=1);

require_once __DIR__ . '/../koneksi.php';

// === KONFIG KOMSHIP ===
const KOMSHIP_API_KEY  = 'nN6Mv6Areb8ec09cadbb3a60uWRzzahg';
const KOMSHIP_BASE_URL = 'https://api.collaborator.komerce.id';

/**
 * Helper request ke Komship (GET/POST).
 */
function komship_request(string $method, string $path, array $data = []): array
{
    $url = rtrim(KOMSHIP_BASE_URL, '/') . '/' . ltrim($path, '/');

    $ch = curl_init();
    $headers = [
        'Accept: application/json',
        'x-api-key: ' . KOMSHIP_API_KEY,
    ];

    if (strtoupper($method) === 'GET') {
        if (!empty($data)) {
            $url .= '?' . http_build_query($data);
        }
    } else {
        $headers[] = 'Content-Type: application/json';
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, strtoupper($method));
    }

    curl_setopt_array($ch, [
        CURLOPT_URL            => $url,
        CURLOPT_HTTPHEADER     => $headers,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 30,
    ]);

    $resp = curl_exec($ch);
    $http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err  = ($resp === false) ? curl_error($ch) : null;
    curl_close($ch);

    if ($resp === false) {
        return [
            'success' => false,
            'http'    => $http,
            'error'   => $err,
        ];
    }

    $body = json_decode($resp, true);
    if (!is_array($body)) {
        return [
            'success' => false,
            'http'    => $http,
            'error'   => 'Response bukan JSON',
            'raw'     => $resp,
        ];
    }

    return [
        'success' => ($http >= 200 && $http < 300),
        'http'    => $http,
        'body'    => $body,
    ];
}

function komship_create_order(mysqli $conn, string $orderId): ?array
{
    // === 1. Ambil data order + customer ===
    $sql = "SELECT o.order_id,
                   o.customer_id,
                   o.tgl_order,
                   o.provinsi,
                   o.kota,
                   o.alamat,
                   o.ongkos_kirim,
                   o.total_harga,
                   o.komship_courier,
                   o.komship_service,
                   c.nama        AS customer_name,
                   c.no_telp     AS customer_phone,
                   c.email       AS customer_email
            FROM orders o
            JOIN customer c ON c.customer_id = o.customer_id
            WHERE o.order_id = ?
            LIMIT 1";

    $st = $conn->prepare($sql);
    $st->bind_param('s', $orderId);
    $st->execute();
    $order = $st->get_result()->fetch_assoc();
    $st->close();

    if (!$order) {
        return null; // order ga ketemu
    }

    // === 2. Ambil detail barang ===
    // SESUAIKAN nama tabel & kolom sama punya lu
    $sqlDet = "SELECT d.product_id,
                      d.qty       AS quantity,
                      d.harga     AS price,
                      p.nama_produk,
                      p.berat     AS weight_gram
               FROM order_detail d
               JOIN products p ON p.product_id = d.product_id
               WHERE d.order_id = ?";

    $st2 = $conn->prepare($sqlDet);
    $st2->bind_param('s', $orderId);
    $st2->execute();
    $resDet = $st2->get_result();

    $details = [];
    $totalWeight = 0;
    while ($row = $resDet->fetch_assoc()) {
        $weight = (int)($row['weight_gram'] ?? 0);
        $qty    = (int)$row['quantity'];
        $totalWeight += $weight * $qty;

        $details[] = [
            'product_name' => $row['nama_produk'],
            'quantity'     => $qty,
            'price'        => (int)$row['price'],
            'weight'       => $weight,  // gram per item
        ];
    }
    $st2->close();

    if (!$details) {
        return null; // ga ada detail barang
    }

    if ($totalWeight <= 0) {
        $totalWeight = 1000; // fallback 1kg kalau belum isi berat
    }

    // === 3. Susun payload buat Komship ===
    // NOTE: ini contoh minimal, sesuaikan sama docs Komship lu.
    $payload = [
        'order_date'            => $order['tgl_order'] ?? date('Y-m-d H:i:s'),
        'brand_name'            => 'Styrk Industries',

        // Data pengirim (gudang) – ganti manual
        'shipper_name'          => 'Styrk Industries',
        'shipper_phone'         => '6281312663058',
        'shipper_destination_id'=> null, // ID tujuan versi Komship (kota asal) -> set kalau sudah mapping
        'shipper_address'       => 'Bandung, Jawa Barat',
        'shipper_email'         => 'support@styrk.com',

        // Data penerima (customer)
        'receiver_name'         => $order['customer_name'],
        'receiver_phone'        => $order['customer_phone'],
        'receiver_destination_id'=> null, // ID kota customer versi Komship (nanti lu mapping dari provinsi/kota)
        'receiver_address'      => $order['alamat'] . ', ' . $order['kota'] . ', ' . $order['provinsi'],

        // Info pengiriman
        'shipping'              => $order['komship_courier'], // contoh: JNT, JNE, SICEPAT
        'shipping_type'         => $order['komship_service'], // contoh: REG, EZ
        'payment_method'        => 'BANK TRANSFER',           // atau COD kalau pake COD

        // Biaya
        'shipping_cost'         => (int)$order['ongkos_kirim'],
        'shipping_cashback'     => 0,
        'service_fee'           => 0,
        'additional_cost'       => 0,
        'grand_total'           => (int)$order['total_harga'],
        'cod_value'             => 0, // isi grand_total kalau COD
        'insurance_value'       => 0,

        // Berat & barang
        'total_weight'          => $totalWeight,  // gram
        'order_details'         => $details,
    ];

    // === 4. Call Komship Store Order ===
    $result = komship_request('POST', '/order/api/v1/orders/store', $payload);
    if (!$result['success']) {
        // bisa lu log ke table lain kalau mau
        return null;
    }

    $body = $result['body'] ?? [];
    $data = $body['data'] ?? [];

    return [
        'order_no' => $data['order_no']      ?? null,
        'awb'      => $data['airway_bill']   ?? null,
        'courier'  => $data['shipping']      ?? $order['komship_courier'],
        'service'  => $data['shipping_type'] ?? $order['komship_service'],
        'status'   => $data['status']        ?? null,
        'raw'      => $body, // buat debug kalau mau
    ];
}
