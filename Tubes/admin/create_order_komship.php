<?php
// create_order_komship.php
// Helper untuk create order ke Komship dari data di DB.

declare(strict_types=1);

/**
 * Biar kalau nanti lu define KONSTAN yang sama di file lain, ga tabrakan.
 */
if (!defined('KOMSHIP_API_KEY')) {
    define('KOMSHIP_API_KEY', '3I7kuf7B3e00fb2d23c692a69owo8BSW');
}
if (!defined('KOMSHIP_ORDER_ENDPOINT')) {
    define('KOMSHIP_ORDER_ENDPOINT', 'https://api-sandbox.collaborator.komerce.id/order/api/v1/orders/store');
}
if (!defined('KOMSHIP_SHIPPER_DEST_ID')) {
    // shipper_destination_id gudang asal (samain sama yang dipakai di calc ongkir)
    define('KOMSHIP_SHIPPER_DEST_ID', 4944);
}

// Data toko (silakan ganti sesuai brand lu)
if (!defined('STORE_BRAND_NAME')) {
    define('STORE_BRAND_NAME', 'Styrk Industries');
}
if (!defined('STORE_SHIPPER_NAME')) {
    define('STORE_SHIPPER_NAME', 'Styrk Industries Official');
}
if (!defined('STORE_SHIPPER_TELP')) {
    define('STORE_SHIPPER_TELP', '6281234567890');
}
if (!defined('STORE_SHIPPER_ADDR')) {
    define('STORE_SHIPPER_ADDR', 'Gudang Styrk, Bandung, Jawa Barat');
}
if (!defined('STORE_SHIPPER_MAIL')) {
    define('STORE_SHIPPER_MAIL', 'support@styrk.local');
}

/**
 * Create Komship Order dari tabel orders + order_details + products.
 *
 * @param mysqli $conn
 * @param string $order_id
 * @return array {
 *   success: bool,
 *   http_code: int|null,
 *   meta_message: string|null,
 *   komship_order_no: string|null,
 *   komship_awb: string|null,
 *   raw_response: mixed,
 * }
 */
function createKomshipOrderFromDb($conn, $order_id)
{
    $result = [
        'success'          => false,
        'http_code'        => null,
        'meta_message'     => null,
        'komship_order_no' => null,
        'komship_awb'      => null,
        'raw_response'     => null,
    ];

    $order_id = trim((string)$order_id);
    if ($order_id === '') {
        $result['meta_message'] = 'order_id kosong.';
        return $result;
    }

    // ========== 1. Ambil data ORDER + CUSTOMER ==========
    $sqlOrder = "
        SELECT 
            o.order_id,
            o.customer_id,
            o.tgl_order,
            o.provinsi,
            o.kota,
            o.kecamatan,
            o.kelurahan,
            o.alamat,
            o.komship_destination_id,
            o.code_courier,
            o.shipping_type,
            o.ongkos_kirim,
            o.total_harga,
            o.komship_status,
            o.komship_order_no,
            o.komship_awb,
            c.nama AS customer_nama
        FROM orders o
        JOIN customer c ON c.customer_id = o.customer_id
        WHERE o.order_id = ?
        LIMIT 1
    ";

    $stmt = $conn->prepare($sqlOrder);
    if (!$stmt) {
        $result['meta_message'] = 'Query order error: ' . $conn->error;
        return $result;
    }
    $stmt->bind_param('s', $order_id);
    $stmt->execute();
    $res   = $stmt->get_result();
    $order = $res->fetch_assoc();
    $stmt->close();

    if (!$order) {
        $result['meta_message'] = 'Order tidak ditemukan.';
        return $result;
    }

    // Kalau sudah punya komship_order_no → anggap sudah dibuat
    if (!empty($order['komship_order_no'])) {
        $result['meta_message'] = 'Order sudah pernah dikirim ke Komship.';
        return $result;
    }

    $komship_destination_id = (int)($order['komship_destination_id'] ?? 0);
    if ($komship_destination_id <= 0) {
        $result['meta_message'] = 'komship_destination_id belum di-set (harus hit ongkir dulu di payment).';
        return $result;
    }

    $shipping_courier = strtoupper((string)($order['code_courier'] ?? '')); // SAP, JNE, dll
    $shipping_type    = (string)($order['shipping_type'] ?? '');           // SAPFlat, REG, dll
    $shipping_cost    = (int)($order['ongkos_kirim'] ?? 0);
    $grand_total      = (int)($order['total_harga'] ?? 0);

    if ($shipping_courier === '' || $shipping_type === '' || $shipping_cost <= 0 || $grand_total <= 0) {
        $result['meta_message'] = 'Data shipping / total order belum lengkap.';
        return $result;
    }

    // ========== 2. Ambil item ORDER (order_details + products) ==========
    $sqlItems = "
        SELECT 
            od.product_id,
            od.jumlah       AS qty,
            od.harga_satuan AS harga_satuan,
            od.subtotal,
            p.nama_produk,
            IFNULL(p.weight, 0) AS weight
        FROM order_details od
        JOIN products p ON p.product_id = od.product_id
        WHERE od.order_id = ?
    ";
    $stmt2 = $conn->prepare($sqlItems);
    if (!$stmt2) {
        $result['meta_message'] = 'Query order_items error: ' . $conn->error;
        return $result;
    }
    $stmt2->bind_param('s', $order_id);
    $stmt2->execute();
    $res2 = $stmt2->get_result();

    $items = [];
    while ($row = $res2->fetch_assoc()) {
        $items[] = $row;
    }
    $stmt2->close();

    if (empty($items)) {
        $result['meta_message'] = 'Order tidak punya item (order_details kosong).';
        return $result;
    }

    // ========== 3. Susun data penerima & alamat ==========
    $receiver_name  = $order['customer_nama'] ?: 'Customer';
    $receiver_phone = '6280000000000'; // TODO: nanti kalau ada kolom no_telp di customer, pakai itu

    $provinsi  = trim((string)($order['provinsi']  ?? ''));
    $kota      = trim((string)($order['kota']      ?? ''));
    $kecamatan = trim((string)($order['kecamatan'] ?? ''));
    $kelurahan = trim((string)($order['kelurahan'] ?? ''));
    $alamat    = trim((string)($order['alamat']    ?? ''));

    $receiver_address = $alamat;
    if ($kelurahan !== '') $receiver_address .= ', ' . $kelurahan;
    if ($kecamatan !== '') $receiver_address .= ', ' . $kecamatan;
    if ($kota      !== '') $receiver_address .= ', ' . $kota;
    if ($provinsi  !== '') $receiver_address .= ', ' . $provinsi;

    // ========== 4. Map payment method ke Komship ==========
    // Use-case lu: payment via Transfer Bank → BANK TRANSFER.
    $payment_method_komship = 'BANK TRANSFER';
    $service_fee            = 0;
    $shipping_cashback      = 0;
    $additional_cost        = 0;
    $insurance_value        = 0;
    $cod_value              = 0; // kalau COD, ini = $grand_total

    // ========== 5. Susun order_details untuk payload Komship ==========
    $orderDetails = [];
    foreach ($items as $it) {
        $qty   = (int)$it['qty'];
        $harga = (int)$it['harga_satuan'];
        $sub   = (int)$it['subtotal'];

        $weight = (int)$it['weight']; // gram per produk
        if ($weight <= 0) $weight = 1000; // fallback 1kg

        $orderDetails[] = [
            'product_name'         => (string)$it['nama_produk'],
            'product_variant_name' => '',
            'product_price'        => $harga,
            'product_width'        => 1,
            'product_height'       => 1,
            'product_weight'       => $weight,
            'product_length'       => 1,
            'qty'                  => max(1, $qty),
            'subtotal'             => max(0, $sub),
        ];
    }

    if (empty($orderDetails)) {
        $result['meta_message'] = 'order_details untuk Komship kosong.';
        return $result;
    }

    // ========== 6. Payload JSON sesuai contoh Komship ==========
    $order_date = $order['tgl_order'] ?: date('Y-m-d H:i:s');

    $payload = [
        'order_date'              => $order_date,
        'brand_name'              => STORE_BRAND_NAME,
        'shipper_name'            => STORE_SHIPPER_NAME,
        'shipper_phone'           => STORE_SHIPPER_TELP,
        'shipper_destination_id'  => KOMSHIP_SHIPPER_DEST_ID,
        'shipper_address'         => STORE_SHIPPER_ADDR,
        'shipper_email'           => STORE_SHIPPER_MAIL,
        'receiver_name'           => $receiver_name,
        'receiver_phone'          => $receiver_phone,
        'receiver_destination_id' => $komship_destination_id,
        'receiver_address'        => $receiver_address,
        'shipping'                => $shipping_courier,
        'shipping_type'           => $shipping_type,
        'payment_method'          => $payment_method_komship, // BANK TRANSFER
        'shipping_cost'           => $shipping_cost,
        'shipping_cashback'       => $shipping_cashback,
        'service_fee'             => $service_fee,
        'additional_cost'         => $additional_cost,
        'grand_total'             => $grand_total,
        'cod_value'               => $cod_value,
        'insurance_value'         => $insurance_value,
        'order_details'           => $orderDetails,
    ];

    // ========== 7. Call API Komship ==========
    $ch = curl_init(KOMSHIP_ORDER_ENDPOINT);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST  => 'POST',
        CURLOPT_POSTFIELDS     => json_encode($payload),
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'x-api-key: ' . KOMSHIP_API_KEY,
        ],
        CURLOPT_TIMEOUT        => 25,
        CURLOPT_FOLLOWLOCATION => true,
    ]);

    $response = curl_exec($ch);
    $err      = curl_error($ch);
    $http     = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $result['http_code']    = $http;
    $result['raw_response'] = $response;

    if ($response === false) {
        $result['meta_message'] = 'Gagal konek ke Komship: ' . $err;
        // optional: tandai gagal di orders
        $conn->query("UPDATE orders SET komship_status = 'FAILED' WHERE order_id = '" . $conn->real_escape_string($order_id) . "'");
        return $result;
    }

    $body = json_decode($response, true);
    if (!is_array($body)) {
        $result['meta_message'] = 'Respon Komship tidak valid: ' . $response;
        $conn->query("UPDATE orders SET komship_status = 'FAILED' WHERE order_id = '" . $conn->real_escape_string($order_id) . "'");
        return $result;
    }

    $metaCode   = (int)($body['meta']['code'] ?? 0);
    $metaStatus = strtolower((string)($body['meta']['status'] ?? ''));
    $metaMsg    = (string)($body['meta']['message'] ?? '');

    $result['meta_message'] = $metaMsg;

    if ($http >= 400 || $metaCode >= 400 || $metaStatus !== 'success') {
        // gagal dari sisi Komship
        $conn->query("UPDATE orders SET komship_status = 'FAILED' WHERE order_id = '" . $conn->real_escape_string($order_id) . "'");
        return $result;
    }

    // Ambil order_no & awb dari data Komship (kalau ada)
    $data   = $body['data'] ?? [];
    $orderNo = $data['order_no'] ?? null;
    $awb     = $data['awb']      ?? ($data['airway_bill'] ?? null);

    // Update DB orders
    $stmtUp = $conn->prepare("
        UPDATE orders 
        SET komship_status = 'SUCCESS',
            komship_order_no = ?,
            komship_awb      = ?
        WHERE order_id = ?
    ");
    if ($stmtUp) {
        $stmtUp->bind_param('sss', $orderNo, $awb, $order_id);
        $stmtUp->execute();
        $stmtUp->close();
    }

    $result['success']          = true;
    $result['komship_order_no'] = $orderNo;
    $result['komship_awb']      = $awb;

    return $result;
}
