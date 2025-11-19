<?php
// user/create_order_komship.php
declare(strict_types=1);

const KOMERCE_API_KEY        = '3I7kuf7B3e00fb2d23c692a69owo8BSW';
const KOMERCE_ORDER_BASE_URL = 'https://api-sandbox.collaborator.komerce.id';

const KOMSHIP_BRAND_NAME  = 'Styrk Industries';
const KOMSHIP_SHIPPER_NAME           = 'Styrk Industries Official Store';
const KOMSHIP_SHIPPER_PHONE          = '6281312663058';
const KOMSHIP_SHIPPER_DESTINATION_ID = 4944;
const KOMSHIP_SHIPPER_ADDRESS        = 'Jl. Prof. drg. Surya Sumantri, M.P.H. No. 65, Bandung.';
const KOMSHIP_SHIPPER_EMAIL          = 'styrk.industries@gmail.com';

function createKomshipOrderFromDb(mysqli $conn, string $orderId, string $appPaymentMethod): array
{
    // 1. AMBIL DATA ORDER + CUSTOMER
    $sqlOrder = "
        SELECT 
            o.order_id,
            o.customer_id,
            o.tgl_order,
            o.provinsi,
            o.kota,
            o.kecamatan,
            o.kelurahan,
            o.komship_destination_id  AS order_komship_destination_id,
            o.alamat                   AS order_address,
            o.code_courier,
            o.shipping_type,
            o.ongkos_kirim,
            o.total_harga,
            c.nama                     AS customer_name,
            c.no_telepon               AS customer_phone,
            c.komship_destination_id   AS customer_komship_destination_id,
            c.alamat                   AS customer_address
        FROM `orders` o
        JOIN `customer` c ON c.customer_id = o.customer_id
        WHERE o.order_id = ?
        LIMIT 1
    ";
    $stmt = $conn->prepare($sqlOrder);
    if (!$stmt) {
        return ['success' => false, 'message' => 'Gagal prepare SQL order: ' . $conn->error, 'komship_order_no' => null, 'raw_response' => null];
    }
    $stmt->bind_param('s', $orderId);
    $stmt->execute();
    $res   = $stmt->get_result();
    $order = $res->fetch_assoc();
    $stmt->close();

    if (!$order) {
        return ['success' => false, 'message' => 'Order tidak ditemukan di DB (order_id: ' . $orderId . ')', 'komship_order_no' => null, 'raw_response' => null];
    }

    // 2. DESTINATION
    $receiverDestinationId = (int)($order['order_komship_destination_id'] ?? 0);
    if ($receiverDestinationId <= 0) {
        $receiverDestinationId = (int)($order['customer_komship_destination_id'] ?? 0);
    }
    if ($receiverDestinationId <= 0) {
        return ['success' => false, 'message' => 'komship_destination_id belum di-set (orders/customer).', 'komship_order_no' => null, 'raw_response' => null];
    }

    // 3. DETAIL PRODUK
    $sqlItems = "
        SELECT 
            d.jumlah,
            d.harga_satuan,
            d.subtotal,
            p.nama_produk,
            p.weight
        FROM `order_details` d
        JOIN `products` p ON p.product_id = d.product_id
        WHERE d.order_id = ?
    ";
    $stmtItems = $conn->prepare($sqlItems);
    if (!$stmtItems) {
        return ['success' => false, 'message' => 'Gagal prepare SQL order_details: ' . $conn->error, 'komship_order_no' => null, 'raw_response' => null];
    }
    $stmtItems->bind_param('s', $orderId);
    $stmtItems->execute();
    $resItems = $stmtItems->get_result();

    $orderDetails = [];
    $totalProduct = 0;
    while ($row = $resItems->fetch_assoc()) {
        $qty      = (int)($row['jumlah'] ?? 0);
        $price    = (int)($row['harga_satuan'] ?? 0);
        $subtotal = (int)($row['subtotal'] ?? ($qty * $price));
        $weight   = (int)($row['weight'] ?? 0);

        $totalProduct += $subtotal;

        $orderDetails[] = [
            'product_name'         => $row['nama_produk'] ?? 'Produk Styrk',
            'product_variant_name' => $row['nama_produk'] ?? 'Default Variant',
            'product_price'        => $price,
            'product_width'        => 1,
            'product_height'       => 2,
            'product_weight'       => $weight > 0 ? $weight : 2000,
            'product_length'       => 20,
            'qty'                  => $qty,
            'subtotal'             => $subtotal,
        ];
    }
    $stmtItems->close();

    if (empty($orderDetails)) {
        return ['success' => false, 'message' => 'Order tidak punya item di order_details.', 'komship_order_no' => null, 'raw_response' => null];
    }

    // 4. HITUNG NILAI (COD)
    $shippingCost = (int)($order['ongkos_kirim'] ?? 0);
    $dbTotalRaw   = $order['total_harga'] ?? null;
    if ($dbTotalRaw !== null && $dbTotalRaw !== '') {
        $dbTotal    = (float)$dbTotalRaw;
        $grandTotal = (int)round($dbTotal);
    } else {
        $grandTotal = (int)($totalProduct + $shippingCost);
    }

    $codValue       = $grandTotal;
    $serviceFee     = (int)round($codValue * 0.028);
    $insuranceValue = 0;
    $additionalCost = 0;
    $shippingCash   = 0;

    // 5. PAYLOAD
    $receiverAddress = $order['order_address'] ?: $order['customer_address'];

    $payload = [
        'order_date'              => date('Y-m-d H:i:s', strtotime($order['tgl_order'] ?? 'now')),
        'brand_name'              => KOMSHIP_BRAND_NAME,
        'shipper_name'            => KOMSHIP_SHIPPER_NAME,
        'shipper_phone'           => KOMSHIP_SHIPPER_PHONE,
        'shipper_destination_id'  => (int)KOMSHIP_SHIPPER_DESTINATION_ID,
        'shipper_address'         => KOMSHIP_SHIPPER_ADDRESS,
        'shipper_email'           => KOMSHIP_SHIPPER_EMAIL,

        'receiver_name'           => $order['customer_name'],
        'receiver_phone'          => $order['customer_phone'],
        'receiver_destination_id' => $receiverDestinationId,
        'receiver_address'        => $receiverAddress,

        'shipping'                => strtoupper($order['code_courier']),
        'shipping_type'           => $order['shipping_type'],
        'payment_method'          => 'COD',

        'shipping_cost'           => $shippingCost,
        'shipping_cashback'       => $shippingCash,
        'service_fee'             => $serviceFee,
        'additional_cost'         => $additionalCost,
        'grand_total'             => $grandTotal,
        'cod_value'               => $codValue,
        'insurance_value'         => $insuranceValue,

        'order_details'           => $orderDetails,
    ];

    // 6. CALL KOMERCE
    $url = KOMERCE_ORDER_BASE_URL . '/order/api/v1/orders/store';

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => [
            'x-api-key: ' . KOMERCE_API_KEY,
        ],
        CURLOPT_POSTFIELDS     => json_encode($payload),
        CURLOPT_TIMEOUT        => 30,
    ]);

    $responseBody = curl_exec($ch);
    $httpCode     = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErr      = curl_error($ch);
    curl_close($ch);

    @file_put_contents(
        __DIR__ . '/komship_last_response.log',
        "=== " . date('Y-m-d H:i:s') . " ===\nHTTP: {$httpCode}\nPayload:\n" .
            json_encode($payload, JSON_PRETTY_PRINT) . "\nResponse:\n{$responseBody}\n\n",
        FILE_APPEND
    );

    if ($curlErr) {
        return ['success' => false, 'message' => 'cURL Error: ' . $curlErr, 'komship_order_no' => null, 'raw_response' => $responseBody];
    }

    $json = json_decode($responseBody, true);
    if (!is_array($json)) {
        return ['success' => false, 'message' => 'Respon Komship bukan JSON valid. HTTP ' . $httpCode, 'komship_order_no' => null, 'raw_response' => $responseBody];
    }

    $meta = $json['meta'] ?? [];
    $data = $json['data'] ?? [];

    $metaCode   = (int)($meta['code']   ?? 0);
    $metaStatus = (string)($meta['status'] ?? '');
    $metaMsg    = (string)($meta['message'] ?? 'Unknown error');

    if ($metaCode !== 201 || $metaStatus !== 'success') {
        // Simpan status error
        $sqlErr = "
            UPDATE `orders`
            SET `komship_status` = ?,
                `komship_last_sync` = NOW()
            WHERE `order_id` = ?
        ";
        if ($stmtErr = $conn->prepare($sqlErr)) {
            $statusText = 'error: ' . substr($metaMsg, 0, 40);
            $stmtErr->bind_param('ss', $statusText, $orderId);
            $stmtErr->execute();
            $stmtErr->close();
        }
        return ['success' => false, 'message' => 'Gagal create order ke Komship: ' . $metaMsg, 'komship_order_no' => null, 'raw_response' => $responseBody];
    }

    // 7. AMBIL order_no
    $orderNo = '';
    if (isset($data['order_no'])) {
        $orderNo = (string)$data['order_no'];
    } elseif (isset($data['order']['order_no'])) {
        $orderNo = (string)$data['order']['order_no'];
    }

    if ($orderNo === '') {
        return ['success' => false, 'message' => 'Respon Komship tidak mengandung order_no.', 'komship_order_no' => null, 'raw_response' => $responseBody];
    }

    $statusText = 'created';

    // 8. UPDATE TABEL orders (PASTIIN NAMA KOLOM)
    $sqlUpdate = "
        UPDATE `orders`
        SET `komship_order_no` = ?,
            `komship_status`   = ?,
            `komship_last_sync` = NOW()
        WHERE `order_id` = ?
        LIMIT 1
    ";

    $stmtUpd = $conn->prepare($sqlUpdate);
    if (!$stmtUpd) {
        @file_put_contents(
            __DIR__ . '/komship_update_debug.log',
            "=== " . date('Y-m-d H:i:s') . " ===\norder_id: {$orderId}\nERROR PREPARE: {$conn->error}\n\n",
            FILE_APPEND
        );
        return ['success' => false, 'message' => 'Gagal prepare UPDATE orders: ' . $conn->error, 'komship_order_no' => $orderNo, 'raw_response' => $responseBody];
    }

    $stmtUpd->bind_param('sss', $orderNo, $statusText, $orderId);
    $stmtUpd->execute();
    $affected = $stmtUpd->affected_rows;
    $updErr   = $stmtUpd->error;
    $stmtUpd->close();

    @file_put_contents(
        __DIR__ . '/komship_update_debug.log',
        "=== " . date('Y-m-d H:i:s') . " ===\norder_id: {$orderId}\naffected_rows: {$affected}\nerror: {$updErr}\n\n",
        FILE_APPEND
    );

    return [
        'success' => $affected > 0,
        'message' => $affected > 0
            ? 'Create order ke Komship sukses & DB terupdate.'
            : 'Create order ke Komship sukses, tapi UPDATE DB tidak mengubah baris (cek order_id).',
        'komship_order_no' => $orderNo,
        'raw_response' => $responseBody,
    ];
}


/**
 * Sync detail order Komship (ambil AWB & status) berdasarkan order_no.
 *
 * @param mysqli $conn
 * @param string $orderId  order_id lokal
 * @param string $orderNo  komship_order_no (contoh: KOM79935202511200007)
 * @return array{
 *   success: bool,
 *   message: string,
 *   awb: ?string,
 *   status: ?string,
 *   raw_response: ?string
 * }
 */
function syncKomshipOrderDetailFromOrderNo(mysqli $conn, string $orderId, string $orderNo): array
{
    // Endpoint detail:
    // GET https://api-sandbox.collaborator.komerce.id/order/api/v1/orders/detail?order_no=KOM...
    $url = KOMERCE_ORDER_BASE_URL . '/order/api/v1/orders/detail?order_no=' . urlencode($orderNo);

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_HTTPHEADER     => [
            'x-api-key: ' . KOMERCE_API_KEY,
        ],
    ]);

    $responseBody = curl_exec($ch);
    $httpCode     = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErr      = curl_error($ch);
    curl_close($ch);

    @file_put_contents(
        __DIR__ . '/komship_detail_last_response.log',
        "=== " . date('Y-m-d H:i:s') . " ===\nHTTP: {$httpCode}\nURL: {$url}\nResponse:\n{$responseBody}\n\n",
        FILE_APPEND
    );

    if ($curlErr) {
        return [
            'success' => false,
            'message' => 'cURL Error (detail): ' . $curlErr,
            'awb' => null,
            'status' => null,
            'raw_response' => $responseBody,
        ];
    }

    $json = json_decode($responseBody, true);
    if (!is_array($json)) {
        return [
            'success' => false,
            'message' => 'Respon detail bukan JSON valid. HTTP ' . $httpCode,
            'awb' => null,
            'status' => null,
            'raw_response' => $responseBody,
        ];
    }

    $meta = $json['meta'] ?? [];
    $data = $json['data'] ?? [];

    $metaCode   = (int)($meta['code']   ?? 0);
    $metaStatus = (string)($meta['status'] ?? '');
    $metaMsg    = (string)($meta['message'] ?? 'Unknown error');

    if ($metaCode !== 200 || $metaStatus !== 'success') {
        return [
            'success' => false,
            'message' => 'Gagal ambil detail order Komship: ' . $metaMsg,
            'awb' => null,
            'status' => null,
            'raw_response' => $responseBody,
        ];
    }

    // Struktur exact mereka gue ga punya, jadi kita bikin robust:
    $orderArr = $data['order'] ?? $data; // jaga-jaga kalau langsung di root

    $awb = '';
    if (isset($orderArr['awb'])) {
        $awb = (string)$orderArr['awb'];
    } elseif (isset($orderArr['airwaybill'])) {
        $awb = (string)$orderArr['airwaybill'];
    } elseif (isset($orderArr['airway_bill'])) {
        $awb = (string)$orderArr['airway_bill'];
    }

    $status = '';
    if (isset($orderArr['status'])) {
        $status = (string)$orderArr['status'];
    }

    // Update ke DB kalau ada minimal 1 informasi
    $sqlUpdate = "
        UPDATE `orders`
        SET 
            `komship_awb`      = IF(? = '', `komship_awb`, ?),
            `komship_status`   = IF(? = '', `komship_status`, ?),
            `komship_last_sync` = NOW()
        WHERE `order_id` = ?
        LIMIT 1
    ";

    $stmtUpd = $conn->prepare($sqlUpdate);
    if ($stmtUpd) {
        // Kita kirim awb & status 2x karena dipakai di dua placeholder IF
        $stmtUpd->bind_param('sssss', $awb, $awb, $status, $status, $orderId);
        $stmtUpd->execute();
        $stmtUpd->close();
    }

    return [
        'success' => true,
        'message' => 'Detail order Komship tersync (AWB/status).',
        'awb' => $awb !== '' ? $awb : null,
        'status' => $status !== '' ? $status : null,
        'raw_response' => $responseBody,
    ];
}
