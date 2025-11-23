<?php
declare(strict_types=1);
session_start();
require_once __DIR__ . '/../koneksi.php';

// =====================================================
// CONFIG BITESHIP
// =====================================================
const BITESHIP_API_KEY = 'biteship_test.eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJuYW1lIjoiU3R5cmtfaW5kdXN0cmllcyIsInVzZXJJZCI6IjY5MjA3ZmI0YzMxM2VmYTUyZTM5OThlNCIsImlhdCI6MTc2Mzc4NTQ0OH0.dBPLQHoBBV4gnXux-OMziAO5yr1TBzXTf4T-Js2b0ak'; // GANTI KE KEY LU
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
// 1) VALIDASI REQUEST + ITEM CART
// =====================================================
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['message'] = 'Metode tidak valid.';
    header('Location: cart.php');
    exit();
}

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

$in_clause = implode(',', $selected_cart_ids);

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
// 3) AMBIL DATA CARTS + PRODUK
// =====================================================
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

$items        = [];
$subtotal     = 0;
$total_weight = 0;

while ($row = $resCart->fetch_assoc()) {
    $qty    = (int)$row['jumlah_barang'];
    $harga  = (int)$row['harga'];
    $weight = (int)$row['weight'];

    $line_total   = $harga * $qty;
    $subtotal    += $line_total;
    $total_weight += $weight * $qty;

    $row['jumlah_barang'] = $qty;
    $row['harga']         = $harga;
    $row['weight']        = $weight;

    $items[] = $row;
}
$stmtCart->close();

if (!$items) {
    $_SESSION['message'] = 'Item tidak ditemukan di cart.';
    header('Location: cart.php');
    exit();
}

// =====================================================
// 4) VOUCHER (SESSION)
// =====================================================
$voucher_code = $_SESSION['voucher_code'] ?? null;
$voucher_tipe = $_SESSION['voucher_tipe'] ?? null; // 'persen' / 'rupiah'
$voucher_rp   = (int)($_SESSION['voucher_nilai_rupiah'] ?? 0);
$voucher_pct  = (int)($_SESSION['voucher_nilai_persen'] ?? 0);

$discount = 0;
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
$provinsi   = trim((string)($_POST['provinsi']   ?? ''));
$kota       = trim((string)($_POST['kota']       ?? ''));
$kecamatan  = trim((string)($_POST['kecamatan']  ?? ''));
$kelurahan  = trim((string)($_POST['kelurahan']  ?? ''));
$postal_code = trim((string)(
    $_POST['kodepos']              // kalau JS lu kirim nama ini
    ?? $_POST['postal_code']       // atau ini
    ?? $_POST['destination_postal_code'] // just in case
    ?? ''
));
$alamat     = trim((string)($_POST['alamat']     ?? ''));

$code_courier  = trim((string)($_POST['code_courier']  ?? ''));
$shipping_type = trim((string)($_POST['service_courier'] ?? ''));
$ongkos_kirim  = (int)($_POST['shipping_cost'] ?? ($_SESSION['shipping_cost'] ?? 0));

if ($provinsi === '' || $kota === '' || $kecamatan === '' || $alamat === '') {
    $_SESSION['message'] = 'Alamat pengiriman belum lengkap.';
    header('Location: payment.php');
    exit();
}
if ($kelurahan === '')  $kelurahan  = '-';
if ($postal_code === '') $postal_code = '40164'; // fallback: Bandung, tapi idealnya kirim beneran dari form

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
function createBiteshipOrder(array $payload): array {
    $ch = curl_init(BITESHIP_CREATE_ORDER_URL);

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'Accept: application/json',
            'Authorization: ' . BITESHIP_API_KEY,
        ],
        CURLOPT_POSTFIELDS     => json_encode($payload),
        CURLOPT_TIMEOUT        => 30,
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    if ($response === false) {
        $err = curl_error($ch);
        curl_close($ch);
        return ['ok' => false, 'http_code' => $httpCode, 'error' => $err];
    }

    curl_close($ch);
    $json = json_decode($response, true);

    if (!is_array($json)) {
        return ['ok' => false, 'http_code' => $httpCode, 'error' => 'Respon biteship bukan JSON', 'raw' => $response];
    }

    return [
        'ok'        => ($httpCode >= 200 && $httpCode < 300),
        'http_code' => $httpCode,
        'data'      => $json,
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

    "courier_company" => strtolower($code_courier),   // jne, jnt, pos, jne_regular, dst (sesuaikan sama data Biteship lu)
    "courier_type"    => strtolower($shipping_type), // reg/yes/express dsb

    "delivery_type" => "now",

    "items"        => $biteshipItems,

    "order_note"   => "Order Styrk: $order_id",
    "metadata"     => [
        "order_id"    => $order_id,
        "customer_id" => $customer_id,
    ]
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

    // FIX: type string harus 12 char (sissssssssii), sesuai 12 parameter
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
        $pid   = (string)$it['product_id'];   // asumsi product_id VARCHAR
        $jml   = (int)$it['jumlah_barang'];
        $harga = (int)$it['harga'];
        $sub   = $harga * $jml;

        $stmtDetail->bind_param("ssiii", $order_id, $pid, $jml, $harga, $sub);
        if (!$stmtDetail->execute()) {
            throw new Exception("Gagal insert detail produk ID $pid: " . $stmtDetail->error);
        }

        // Kurangi stok
        $sqlStok = "UPDATE products SET stok = stok - ? WHERE product_id = ?";
        $stmtStok = $conn->prepare($sqlStok);
        if ($stmtStok) {
            $stmtStok->bind_param("is", $jml, $pid);
            $stmtStok->execute();
            $stmtStok->close();
        }
    }
    $stmtDetail->close();

    // --- hapus carts yang sudah checkout ---
    $sqlDel = "DELETE FROM carts WHERE customer_id = ? AND cart_id IN ($in_clause)";
    $stmtDel = $conn->prepare($sqlDel);
    if (!$stmtDel) {
        throw new Exception("Gagal prepare delete carts: " . $conn->error);
    }
    $stmtDel->bind_param("i", $customer_id);
    if (!$stmtDel->execute()) {
        throw new Exception("Gagal hapus cart: " . $stmtDel->error);
    }
    $stmtDel->close();

    $conn->commit();

} catch (Throwable $e) {
    $conn->rollback();
    $_SESSION['message'] = 'Gagal membuat order: ' . $e->getMessage();
    header('Location: payment.php');
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
//      - GAGAL BITESHIP TIDAK NGE-GAGALIN ORDER
// =====================================================
try {
    $bs = createBiteshipOrder($biteshipPayload);

    if (!empty($bs['ok']) && $bs['ok'] === true) {
        $bsData = $bs['data'];

        $providerOrderId = $bsData['id'] ?? ($bsData['order_id'] ?? null);
        $trackingCode    = $bsData['courier']['waybill_id'] ?? ($bsData['tracking_id'] ?? null);
        $statusShip      = $bsData['status'] ?? 'confirmed';

        // kalau kolom ini belum ada di DB lu, ya tambahin dulu:
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
    } else {
        // DI SINI LU SEBENARNYA UDAH BISA LOG, TAPI JANGAN DI-THROW
        // Biar user tetap lanjut ke halaman pembayaran
        // error detail: $bs['error'] ?? $bs['http_code']
    }
} catch (Throwable $e) {
    // diem bae; paling lu logging manual kalau mau
}

// =====================================================
// 12) REDIRECT KE HALAMAN PEMBAYARAN SESUAI METODE
// =====================================================

header("Location: riwayat.belanja.php");

exit();
