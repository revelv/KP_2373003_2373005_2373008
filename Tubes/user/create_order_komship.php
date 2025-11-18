<?php
// user/create_order_komship.php
declare(strict_types=1);

require_once __DIR__ . '/../koneksi.php';

// ================== KONFIG KOMSHIP ==================
if (!defined('KOMSHIP_API_KEY')) {
    // TODO: GANTI pake API key sandbox / production lu sendiri
    define('KOMSHIP_API_KEY', '3I7kuf7B3e00fb2d23c692a69owo8BSW');
}

if (!defined('KOMSHIP_ORDER_ENDPOINT')) {
    define('KOMSHIP_ORDER_ENDPOINT', 'https://api-sandbox.collaborator.komerce.id/order/api/v1/orders/store');
}

// Destination ID contoh dari docs (banjarnegara) – cuma fallback test
const KOMSHIP_FALLBACK_DEST_ID = 39947;
// Shipper destination ID contoh (gudang Bandung)
const KOMSHIP_SHIPPER_DEST_ID  = 31597;

// Pin-point contoh (silakan ganti nanti kalau udah punya data bener)
const KOMSHIP_ORIGIN_PIN_POINT      = '-7.279849431298132,109.35114360314475';
const KOMSHIP_DESTINATION_PIN_POINT = '-7.30585,109.36814';

// ===================================================
// Helper: cari receiver_destination_id dari order
// (sementara fallback, nanti bisa lu ganti mapping beneran)
// ===================================================
function findKomshipDestinationIdForOrder(array $orderRow): int
{
    // Kalau nanti lu simpen kolom "receiver_destination_id" di orders:
    // if (!empty($orderRow['receiver_destination_id'])) {
    //     return (int)$orderRow['receiver_destination_id'];
    // }

    // Sekarang fallback dulu ke sandbox sample
    return KOMSHIP_FALLBACK_DEST_ID;
}

// ===================================================
// Fungsi utama: dipanggil dari checkout.php / order_admin.php
// createKomshipOrderFromDb($conn, $order_id)
// ===================================================
function createKomshipOrderFromDb(mysqli $conn, string $order_id): array
{
    $result = [
        'success'          => false,
        'http_code'        => null,
        'meta_message'     => '',
        'komship_order_no' => null,
        'komship_awb'      => null,
        'komship_status'   => null,
        'raw_response'     => null,
    ];

    // ============ Ambil data ORDER + CUSTOMER ============
    $sqlOrder = "
        SELECT 
            o.*,
            c.nama       AS customer_nama,
            c.no_telepon AS customer_phone,
            c.email      AS customer_email
        FROM orders o
        JOIN customer c ON c.customer_id = o.customer_id
        WHERE o.order_id = ?
        LIMIT 1
    ";

    $stmt = $conn->prepare($sqlOrder);
    if (!$stmt) {
        $result['meta_message'] = 'Prepare query orders gagal: ' . $conn->error;
        return $result;
    }

    $stmt->bind_param('s', $order_id);
    $stmt->execute();
    $resOrder = $stmt->get_result();
    $order    = $resOrder->fetch_assoc();
    $stmt->close();

    if (!$order) {
        $result['meta_message'] = 'Order tidak ditemukan.';
        return $result;
    }

    // Kalau sudah pernah punya komship_order_no, jangan buat dua kali
    if (!empty($order['komship_order_no'])) {
        $result['success']          = true;
        $result['komship_order_no'] = $order['komship_order_no'];
        $result['komship_awb']      = $order['komship_awb']      ?? null;
        $result['komship_status']   = $order['komship_status']   ?? null;
        $result['meta_message']     = 'Order sudah pernah dikirim ke Komship.';
        return $result;
    }

    // --- Data wajib dari order ---
    $shippingCourier = trim((string)($order['code_courier']   ?? ''));   // JNE / SAP / dll
    $shippingType    = trim((string)($order['shipping_type']  ?? ''));   // REG, SAPFlat, dst
    $shippingCost    = (int)($order['ongkos_kirim']           ?? 0);
    $grandTotal      = (int)($order['total_harga']            ?? 0);
    $alamat          = trim((string)($order['alamat']         ?? ''));

    if (
        $shippingCourier === '' ||
        $shippingType    === '' ||
        $shippingCost    <= 0   ||
        $grandTotal      <= 0   ||
        $alamat          === ''
    ) {
        $result['meta_message'] = 'Data order belum lengkap untuk Komship (kurir / shipping_type / ongkir / total / alamat).';
        return $result;
    }

    // ============ Ambil ORDER DETAILS + Produk ============
    $sqlDetail = "
        SELECT 
            d.product_id,
            d.jumlah,
            d.harga_satuan,
            d.subtotal,
            p.nama_produk,
            IFNULL(p.weight, 0) AS weight
        FROM order_details d
        JOIN products p ON p.product_id = d.product_id
        WHERE d.order_id = ?
    ";

    $stmtDet = $conn->prepare($sqlDetail);
    if (!$stmtDet) {
        $result['meta_message'] = 'Prepare query order_details gagal: ' . $conn->error;
        return $result;
    }

    $stmtDet->bind_param('s', $order_id);
    $stmtDet->execute();
    $resDet = $stmtDet->get_result();

    $order_details   = [];
    $totalBeratG     = 0;
    $subtotalBarang  = 0;

    while ($row = $resDet->fetch_assoc()) {
        $qty     = (int)$row['jumlah'];
        $harga   = (int)$row['harga_satuan'];
        $sub     = (int)$row['subtotal'];
        $weightG = max(0, (int)$row['weight']); // gram per item

        $totalBeratG    += $weightG * $qty;
        $subtotalBarang += $sub;

        $order_details[] = [
            'product_name'         => (string)$row['nama_produk'],
            'product_variant_name' => 'Default',   // JANGAN kosong, wajib string
            'product_price'        => $harga,
            'product_weight'       => $weightG,
            'product_width'        => 10,         // cm – sementara hardcoded
            'product_height'       => 5,
            'product_length'       => 30,
            'qty'                  => $qty,
            'subtotal'             => $sub,
        ];
    }
    $stmtDet->close();

    if (empty($order_details)) {
        $result['meta_message'] = 'Order belum punya detail barang.';
        return $result;
    }

    // Berat total (kg) – cuma buat info, nggak dikirim eksplisit
    $weightKg = max(1, (int)ceil($totalBeratG / 1000));

    // Nilai barang (tanpa ongkir)
    $itemValue = $subtotalBarang;

    // Receiver destination id (sementara fallback)
    $receiver_destination_id = findKomshipDestinationIdForOrder($order);

    // ================== Data SHIPPER ==================
    $shipper_destination_id = KOMSHIP_SHIPPER_DEST_ID;
    $shipper_name           = 'Styrk Industries';
    $shipper_phone          = '628123456789';          // format sesuai docs (62 / 8, bukan 0)
    $shipper_email          = 'support@styrkindustries.com';
    $shipper_address        = 'Alamat gudang Styrk di Bandung';

    // ================== Data RECEIVER ==================
    $receiver_name   = $order['customer_nama']  ?? 'Customer';
    $receiver_phone  = $order['customer_phone'] ?? ''; // pastikan di DB lu udah 62 / 8xxx
    if ($receiver_phone !== '') {
        // optional: normalisasi nomor supaya nggak mulai dari 0
        $receiver_phone = ltrim($receiver_phone, '+');
        if (strpos($receiver_phone, '0') === 0) {
            $receiver_phone = '62' . substr($receiver_phone, 1);
        }
    }
    $receiver_email  = $order['customer_email'] ?? null;
    $receiver_addr   = $alamat;

    // ================== Payment Method ==================
    // Lu belum pakai COD, jadi BANK TRANSFER
    $payment_method   = 'BANK TRANSFER';
    $cod_value        = 0;
    $shipping_cashback = 0;         // kalau mau promo ongkir, isi di sini
    $service_fee       = 0;         // WAJIB 0 untuk BANK TRANSFER (sesuai docs)
    $additional_cost   = 0;
    $insurance_value   = 0.0;       // float

    // order_date -> format Y-m-d H:i:s
    $order_date_db = $order['tgl_order'] ?? null;
    $order_date    = $order_date_db
        ? date('Y-m-d H:i:s', strtotime($order_date_db))
        : date('Y-m-d H:i:s');

    $brand_name    = 'Styrk Industries';
    $shipping_name = strtoupper($shippingCourier); // JNE / SAP / SICEPAT
    $shipping_type = $shippingType;                // REG / SAPFlat / dll

    // ================== Susun payload PERSIS seperti docs ==================
    $payload = [
        "order_date"              => $order_date,
        "brand_name"              => $brand_name,
        "shipper_name"            => $shipper_name,
        "shipper_phone"           => $shipper_phone,
        "shipper_destination_id"  => $shipper_destination_id,
        "shipper_address"         => $shipper_address,
        "origin_pin_point"        => KOMSHIP_ORIGIN_PIN_POINT,
        "receiver_name"           => $receiver_name,
        "receiver_phone"          => $receiver_phone,
        "receiver_destination_id" => $receiver_destination_id,
        "receiver_address"        => $receiver_addr,
        "shipper_email"           => $shipper_email,
        "destination_pin_point"   => KOMSHIP_DESTINATION_PIN_POINT,
        "shipping"                => $shipping_name,
        "shipping_type"           => $shipping_type,
        "payment_method"          => $payment_method,
        "shipping_cost"           => $shippingCost,
        "shipping_cashback"       => $shipping_cashback,
        "service_fee"             => $service_fee,
        "additional_cost"         => $additional_cost,
        "grand_total"             => $grandTotal,
        "cod_value"               => $cod_value,
        "insurance_value"         => $insurance_value,
        "order_details"           => $order_details,
    ];

    if ($receiver_email) {
        $payload['receiver_email'] = $receiver_email;
    }

    // ================== Kirim ke Komship ==================
    $ch = curl_init(KOMSHIP_ORDER_ENDPOINT);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING       => '',
        CURLOPT_MAXREDIRS      => 10,
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST  => 'POST',
        CURLOPT_POSTFIELDS     => json_encode($payload),
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'x-api-key: ' . KOMSHIP_API_KEY,
        ],
    ]);

    $resp = curl_exec($ch);
    $http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $cerr = ($resp === false) ? curl_error($ch) : null;
    curl_close($ch);

    $result['http_code']    = $http;
    $result['raw_response'] = $resp;

    if ($resp === false) {
        $result['meta_message'] = 'cURL error: ' . $cerr;
        return $result;
    }

    $body = json_decode($resp, true);
    if (!is_array($body)) {
        $result['meta_message'] = 'Respon Komship bukan JSON.';
        return $result;
    }

    $metaCode    = $body['meta']['code']    ?? null;
    $metaMessage = $body['meta']['message'] ?? '';
    $result['meta_message'] = $metaMessage ?: ('HTTP ' . $http);

    if ($http >= 400 || ($metaCode !== null && (int)$metaCode >= 400)) {
        // gagal dari sisi Komship
        return $result;
    }

    $data = $body['data'] ?? [];

    $komship_order_no = $data['order_no'] ?? ($data['order_number'] ?? null);
    $komship_awb      = $data['awb']      ?? ($data['no_resi']      ?? null);
    $komship_status   = $data['status']   ?? 'SUCCESS';

    $result['komship_order_no'] = $komship_order_no;
    $result['komship_awb']      = $komship_awb;
    $result['komship_status']   = $komship_status;
    $result['success']          = true;

    // ================== Update tabel orders ==================
    $sqlUpd = "
        UPDATE orders
        SET 
            komship_order_no  = ?,
            komship_awb       = ?,
            komship_status    = ?,
            komship_last_sync = NOW()
        WHERE order_id = ?
    ";

    $stmtUpd = $conn->prepare($sqlUpd);
    if ($stmtUpd) {
        $stmtUpd->bind_param(
            'ssss',
            $komship_order_no,
            $komship_awb,
            $komship_status,
            $order_id
        );
        $stmtUpd->execute();
        $stmtUpd->close();
    }

    return $result;
}
