<?php

declare(strict_types=1);
session_start();
require_once __DIR__ . '/../koneksi.php';

// =====================================================
// CONFIG BITESHIP
// =====================================================
const BITESHIP_API_KEY = 'biteship_test.eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJuYW1lIjoiU3R5cmtfaW5kdXN0cmllcyIsInVzZXJJZCI6IjY5MjA3ZmI0YzMxM2VmYTUyZTM5OThlNCIsImlhdCI6MTc2Mzc4NTQ0OH0.dBPLQHoBBV4gnXux-OMziAO5yr1TBzXTf4T-Js2b0ak';
const BITESHIP_CREATE_ORDER_URL = 'https://api.biteship.com/v1/orders';

// Origin / shipper fixed (Bandung)
const ORIGIN_CONTACT_NAME   = 'Styrk Industries';
const ORIGIN_CONTACT_PHONE  = '081312663058';
const ORIGIN_CONTACT_EMAIL  = 'styrk.industries@gmail.com';
const ORIGIN_ORGANIZATION   = 'Styrk Industries';
const ORIGIN_ADDRESS        = 'Jl. Prof. drg. Surya Sumantri No. 65, Kecamatan Bandung Kulon, Kota Bandung, Jawa Barat';
const ORIGIN_POSTAL_CODE    = '40164';

// =====================================================
// 0) AUTH GUARD
// =====================================================
if (!isset($_SESSION['kd_cs'])) {
    $_SESSION['message'] = 'Anda harus login terlebih dahulu.';
    header('Location: produk.php');
    exit();
}
$customer_id = (int)$_SESSION['kd_cs'];

// =====================================================
// 1) VALIDASI REQUEST & MODE (AUCTION vs CART)
// =====================================================
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['message'] = 'Metode tidak valid.';
    header('Location: cart.php');
    exit();
}

// Cek apakah ini pembayaran lelang?
$auction_id = isset($_POST['auction_id']) ? (int)$_POST['auction_id'] : 0;
$is_auction = ($auction_id > 0);

$selected_cart_ids = [];

// Jika BUKAN lelang, wajib ada item cart
if (!$is_auction) {
    if (empty($_POST['selected_items']) || !is_array($_POST['selected_items'])) {
        $_SESSION['message'] = 'Pilih setidaknya satu barang untuk checkout.';
        header('Location: cart.php');
        exit();
    }

    $selected_cart_ids = array_map('intval', (array)$_POST['selected_items']);
    $selected_cart_ids = array_values(array_filter($selected_cart_ids, fn($v) => $v > 0));

    if (!$selected_cart_ids) {
        $_SESSION['message'] = 'Item cart tidak valid.';
        header('Location: cart.php');
        exit();
    }
}

// =====================================================
// 2) AMBIL DATA CUSTOMER (DEST CONTACT UNTUK BITESHIP)
// =====================================================
$dest_name  = '';
$dest_phone = '';
$dest_email = '';

$stmtCust = $conn->prepare("SELECT nama, no_telepon, email FROM customer WHERE customer_id = ? LIMIT 1");
$stmtCust->bind_param("i", $customer_id);
$stmtCust->execute();
$resCust = $stmtCust->get_result();
if ($c = $resCust->fetch_assoc()) {
    $dest_name  = (string)($c['nama'] ?? '');
    $dest_phone = (string)($c['no_telepon'] ?? '');
    $dest_email = (string)($c['email'] ?? '');
}
$stmtCust->close();

// =====================================================
// 3) AMBIL DATA BARANG (MODE AUCTION vs CART)
// =====================================================
$items        = [];
$subtotal     = 0;
$total_weight = 0;

if ($is_auction) {
    // --- QUERY LELANG ---
    // Ambil data produk berdasarkan auction_id
    // Harga diambil dari current_bid
    $sqlAuction = "
        SELECT 
            p.product_id,
            p.nama_produk,
            p.weight,
            a.current_bid as harga,
            1 as jumlah_barang
        FROM auctions a
        JOIN products p ON a.product_id = p.product_id
        WHERE a.auction_id = ? AND a.current_winner_id = ?
    ";
    $stmtAuc = $conn->prepare($sqlAuction);
    $stmtAuc->bind_param("ii", $auction_id, $customer_id);
    $stmtAuc->execute();
    $resAuc = $stmtAuc->get_result();
    
    if ($row = $resAuc->fetch_assoc()) {
        $row['nama_produk'] = $row['nama_produk'] . ' (Lelang #' . $auction_id . ')';
        $items[] = $row;
    } else {
        $_SESSION['message'] = 'Data lelang tidak valid atau Anda bukan pemenang.';
        header('Location: riwayat_belanja.php');
        exit();
    }
    $stmtAuc->close();

} else {
    // --- QUERY CART (Logic Lama) ---
    $in_clause = implode(',', $selected_cart_ids);
    $sqlCart = "
        SELECT 
            c.cart_id,
            c.product_id,
            c.jumlah_barang,
            p.nama_produk,
            p.harga,
            p.weight
        FROM carts c
        INNER JOIN products p ON p.product_id = c.product_id
        WHERE c.customer_id = ?
          AND c.cart_id IN ($in_clause)
    ";
    $stmtCart = $conn->prepare($sqlCart);
    $stmtCart->bind_param("i", $customer_id);
    $stmtCart->execute();
    $resCart = $stmtCart->get_result();

    while ($row = $resCart->fetch_assoc()) {
        $items[] = $row;
    }
    $stmtCart->close();
}

// Hitung Subtotal & Weight Loop
if (!$items) {
    $_SESSION['message'] = 'Item tidak ditemukan.';
    header('Location: cart.php');
    exit();
}

// Loop ulang $items yang sudah distandarisasi untuk hitung total
// Kita reset $items agar strukturnya konsisten valuenya
$final_items = [];
foreach ($items as $row) {
    $qty    = (int)$row['jumlah_barang'];
    $harga  = (int)$row['harga'];
    $weight = (int)$row['weight'];

    if ($weight <= 0) $weight = 1000; // Default weight 1kg

    $line_total   = $harga * $qty;
    $subtotal    += $line_total;
    $total_weight += $weight * $qty;

    $row['jumlah_barang'] = $qty;
    $row['harga']         = $harga;
    $row['weight']        = $weight;
    
    $final_items[] = $row;
}
$items = $final_items;


// =====================================================
// 4) VOUCHER (SESSION)
// =====================================================
$voucher_code = $_SESSION['voucher_code'] ?? null;
$voucher_tipe = $_SESSION['voucher_tipe'] ?? null; // 'persen' / 'rupiah'
$voucher_rp   = (int)($_SESSION['voucher_nilai_rupiah'] ?? 0);
$voucher_pct  = (int)($_SESSION['voucher_nilai_persen'] ?? 0);

$discount = 0;
// Voucher biasanya gak berlaku buat lelang, tapi kalau mau dienable biarin aja logic ini
if ($voucher_code) {
    if ($voucher_tipe === 'persen' && $voucher_pct > 0) {
        $discount = (int) round($subtotal * ($voucher_pct / 100));
    } elseif ($voucher_tipe === 'rupiah' && $voucher_rp > 0) {
        $discount = $voucher_rp;
    }
}
if ($discount > $subtotal) $discount = $subtotal;

$subtotal_after_discount = $subtotal - $discount;

// =====================================================
// 5) DATA SHIPPING + COURIER
// =====================================================
$provinsi    = trim((string)($_POST['provinsi']    ?? ''));
$kota        = trim((string)($_POST['kota']        ?? ''));
$kecamatan   = trim((string)($_POST['kecamatan']   ?? ''));
$kelurahan   = trim((string)($_POST['kelurahan']   ?? ''));
$postal_code = trim((string)(
    $_POST['kodepos']                    // dari JS (alamat custom)
    ?? $_POST['postal_code']             // fallback
    ?? $_POST['destination_postal_code'] // just in case
    ?? ''
));
$alamat      = trim((string)($_POST['alamat']      ?? ''));

$code_courier  = trim((string)($_POST['code_courier']    ?? '')); 
$shipping_type = trim((string)($_POST['service_courier'] ?? '')); 
$ongkos_kirim  = (int)($_POST['shipping_cost'] ?? ($_SESSION['shipping_cost'] ?? 0));

if ($provinsi === '' || $kota === '' || $kecamatan === '' || $alamat === '') {
    $_SESSION['message'] = 'Alamat pengiriman belum lengkap.';
    header('Location: payment.php');
    exit();
}
if ($kelurahan === '')  $kelurahan  = '-';
if ($postal_code === '') $postal_code = ORIGIN_POSTAL_CODE; 

if ($code_courier === '' || $shipping_type === '' || $ongkos_kirim <= 0) {
    $_SESSION['message'] = 'Pilih kurir dan service dulu sebelum bayar.';
    header('Location: payment.php');
    exit();
}

$_SESSION['shipping_cost'] = $ongkos_kirim;

// =====================================================
// 6) METODE PEMBAYARAN
// =====================================================
$payment_method = trim((string)($_POST['metode'] ?? ''));
if (!in_array($payment_method, ['Transfer', 'QRIS'], true)) {
    $_SESSION['message'] = 'Metode pembayaran tidak valid.';
    header('Location: payment.php');
    exit();
}

// =====================================================
// 7) TOTAL AKHIR
// =====================================================
$grand_total = $subtotal_after_discount + $ongkos_kirim;

// =====================================================
// 8) HELPER: CALL BITESHIP CREATE ORDER
// =====================================================
function createBiteshipOrder(array $payload): array
{
    $apiKey = BITESHIP_API_KEY;
    $url    = BITESHIP_CREATE_ORDER_URL;

    $jsonPayload = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    if ($jsonPayload === false) {
        return [
            'ok'        => false,
            'http_code' => 0,
            'error'     => 'json_encode gagal: ' . json_last_error_msg(),
        ];
    }

    $ch = curl_init($url);

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'Accept: application/json',
            'Authorization: ' . $apiKey,
        ],
        CURLOPT_POSTFIELDS     => $jsonPayload,
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    if ($response === false) {
        $err = curl_error($ch);
        curl_close($ch);
        return [
            'ok'        => false,
            'http_code' => $httpCode,
            'error'     => 'cURL error: ' . $err,
        ];
    }

    curl_close($ch);

    $decoded = json_decode($response, true);
    if (!is_array($decoded)) {
        return [
            'ok'        => false,
            'http_code' => $httpCode,
            'error'     => 'Respon biteship bukan JSON',
            'raw_body'  => $response,
        ];
    }

    return [
        'ok'        => ($httpCode >= 200 && $httpCode < 300),
        'http_code' => $httpCode,
        'data'      => $decoded,
    ];
}

// =====================================================
// 9) SIAPIN DATA BITESHIP ITEMS & PAYLOAD
// =====================================================
$biteshipItems = [];
foreach ($items as $it) {
    $biteshipItems[] = [
        "name"        => (string)$it['nama_produk'],
        "description" => (string)$it['nama_produk'],
        "value"       => (int)$it['harga'],
        "quantity"    => (int)$it['jumlah_barang'],
        "weight"      => (int)$it['weight'], // gram per item
    ];
}

$order_id = 'ORD-' . date('YmdHis') . '-' . random_int(100, 999);

$biteshipPayload = [
    "shipper_contact_name"   => ORIGIN_CONTACT_NAME,
    "shipper_contact_phone"  => ORIGIN_CONTACT_PHONE,
    "shipper_contact_email"  => ORIGIN_CONTACT_EMAIL,
    "shipper_organization"   => ORIGIN_ORGANIZATION,

    "origin_contact_name"    => ORIGIN_CONTACT_NAME,
    "origin_contact_phone"   => ORIGIN_CONTACT_PHONE,
    "origin_address"         => ORIGIN_ADDRESS,
    "origin_postal_code"     => ORIGIN_POSTAL_CODE,

    "destination_contact_name"   => $dest_name ?: 'Customer',
    "destination_contact_phone"  => $dest_phone ?: '0000000000',
    "destination_contact_email"  => $dest_email ?: 'customer@unknown.test',
    "destination_address"        => $alamat,
    "destination_postal_code"    => $postal_code,

    "courier_company" => $code_courier,   // contoh: "jne"
    "courier_type"    => $shipping_type,  // contoh: "reg"

    "delivery_type"   => "now",

    "items"           => $biteshipItems,

    "order_note"      => "Order Styrk: $order_id" . ($is_auction ? " (Lelang)" : ""),
    "metadata"        => [
        "order_id"    => $order_id,
        "customer_id" => $customer_id,
        "is_auction"  => $is_auction
    ],
];

// =====================================================
// 10) INSERT orders + details + hapus carts (TRANSACTION)
// =====================================================
$conn->begin_transaction();

try {
    // --- insert orders lokal ---
    $sqlOrder = "
        INSERT INTO orders
        (order_id, customer_id, tgl_order, provinsi, kota, kecamatan, kelurahan, postal_code,
         alamat, code_courier, shipping_type, ongkos_kirim, total_harga)
        VALUES
        (?, ?, NOW(), ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ";
    $stmtOrder = $conn->prepare($sqlOrder);
    if (!$stmtOrder) {
        throw new Exception("Gagal prepare orders: " . $conn->error);
    }

    $stmtOrder->bind_param(
        "sissssssssii",
        $order_id,
        $customer_id,
        $provinsi,
        $kota,
        $kecamatan,
        $kelurahan,
        $postal_code,
        $alamat,
        $code_courier,
        $shipping_type,
        $ongkos_kirim,
        $grand_total
    );

    if (!$stmtOrder->execute()) {
        throw new Exception("Gagal insert orders: " . $stmtOrder->error);
    }
    $stmtOrder->close();

    // --- insert order_details ---
    $sqlDetail = "
        INSERT INTO order_details
        (order_id, product_id, jumlah, harga_satuan, subtotal)
        VALUES (?, ?, ?, ?, ?)
    ";
    $stmtDetail = $conn->prepare($sqlDetail);
    if (!$stmtDetail) {
        throw new Exception("Gagal prepare order_details: " . $conn->error);
    }

    foreach ($items as $it) {
        $pid   = (string)$it['product_id']; 
        $jml   = (int)$it['jumlah_barang'];
        $harga = (int)$it['harga']; // Ini dinamis (kalau lelang pakai bid price)
        $sub   = $harga * $jml;

        $stmtDetail->bind_param("ssiii", $order_id, $pid, $jml, $harga, $sub);
        if (!$stmtDetail->execute()) {
            throw new Exception("Gagal insert detail produk ID $pid: " . $stmtDetail->error);
        }

        // Kurangi stok (Baik lelang maupun normal tetap kurangi stok master product)
        $sqlStok = "UPDATE products SET stok = stok - ? WHERE product_id = ?";
        $stmtStok = $conn->prepare($sqlStok);
        if ($stmtStok) {
            $stmtStok->bind_param("is", $jml, $pid);
            $stmtStok->execute();
            $stmtStok->close();
        }
    }
    $stmtDetail->close();

    // --- hapus carts yang sudah checkout (HANYA JIKA BUKAN LELANG) ---
    if (!$is_auction && !empty($selected_cart_ids)) {
        $in_clause_del = implode(',', $selected_cart_ids);
        $sqlDel = "DELETE FROM carts WHERE customer_id = ? AND cart_id IN ($in_clause_del)";
        $stmtDel = $conn->prepare($sqlDel);
        if (!$stmtDel) {
            throw new Exception("Gagal prepare delete carts: " . $conn->error);
        }
        $stmtDel->bind_param("i", $customer_id);
        if (!$stmtDel->execute()) {
            throw new Exception("Gagal hapus cart: " . $stmtDel->error);
        }
        $stmtDel->close();
    }
    
    // (Opsional) Update status lelang jadi 'completed' atau 'paid' jika perlu
    if ($is_auction) {
        // Contoh: $conn->query("UPDATE auctions SET status='completed' WHERE auction_id=$auction_id");
    }

    $conn->commit();

} catch (Throwable $e) {
    $conn->rollback();
    $_SESSION['message'] = 'Gagal membuat order: ' . $e->getMessage();
    header('Location: payment.php' . ($is_auction ? '?auction_id='.$auction_id : ''));
    exit();
}

// bersihin voucher
unset(
    $_SESSION['voucher_code'],
    $_SESSION['voucher_tipe'],
    $_SESSION['voucher_nilai_rupiah'],
    $_SESSION['voucher_nilai_persen']
);

// =====================================================
// 11) CALL BITESHIP SETELAH DB COMMIT
// =====================================================
try {
    $bs = createBiteshipOrder($biteshipPayload);

    $_SESSION['biteship_debug'] = [
        'request_payload' => $biteshipPayload,
        'response'        => $bs,
    ];

    if (!empty($bs['ok']) && $bs['ok'] === true) {
        $bsData = $bs['data'];

        $providerOrderId = $bsData['id'] ?? ($bsData['order_id'] ?? null);
        $trackingCode    = $bsData['courier']['waybill_id'] ?? ($bsData['tracking_id'] ?? null);
        $statusShip      = $bsData['status'] ?? 'confirmed';

        $sqlUpd = "
            UPDATE orders
            SET shipping_provider_order_id = ?,
                shipping_tracking_code     = ?,
                shipping_status            = ?,
                shipping_last_sync         = NOW()
            WHERE order_id = ?
            LIMIT 1
        ";
        $stmtUpd = $conn->prepare($sqlUpd);
        if ($stmtUpd) {
            $stmtUpd->bind_param(
                "ssss",
                $providerOrderId,
                $trackingCode,
                $statusShip,
                $order_id
            );
            $stmtUpd->execute();
            $stmtUpd->close();
        }
    }
} catch (Throwable $e) {
    $_SESSION['biteship_debug_exception'] = $e->getMessage();
}

// =====================================================
// 12) REDIRECT KE HALAMAN RIWAYAT
// =====================================================
header("Location: riwayat_belanja.php");
exit();