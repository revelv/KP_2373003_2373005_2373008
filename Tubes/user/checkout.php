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
// 1) VALIDASI REQUEST & MODE
// =====================================================
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['message'] = 'Metode tidak valid.';
    header('Location: cart.php');
    exit();
}

$auction_id = isset($_POST['auction_id']) ? (int)$_POST['auction_id'] : 0;
$is_auction = ($auction_id > 0);
$selected_cart_ids = [];

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
// 2) DATA CUSTOMER
// =====================================================
$dest_name  = ''; $dest_phone = ''; $dest_email = '';
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
// 3) AMBIL DATA BARANG
// =====================================================
$items        = [];
$subtotal     = 0;
$total_weight = 0;

if ($is_auction) {
    $sqlAuction = "
        SELECT p.product_id, p.nama_produk, p.weight, a.current_bid as harga, 1 as jumlah_barang
        FROM auctions a
        JOIN products p ON a.product_id = p.product_id
        WHERE a.auction_id = ? AND a.current_winner_id = ?
    ";
    $stmtAuc = $conn->prepare($sqlAuction);
    $stmtAuc->bind_param("ii", $auction_id, $customer_id);
    $stmtAuc->execute();
    $resAuc = $stmtAuc->get_result();
    if ($row = $resAuc->fetch_assoc()) {
        $row['nama_produk'] .= ' (Lelang #' . $auction_id . ')';
        $items[] = $row;
    } else {
        $_SESSION['message'] = 'Lelang tidak valid.';
        header('Location: riwayat_belanja.php');
        exit();
    }
    $stmtAuc->close();
} else {
    $in_clause = implode(',', $selected_cart_ids);
    $sqlCart = "
        SELECT c.cart_id, c.product_id, c.jumlah_barang, p.nama_produk, p.harga, p.weight
        FROM carts c INNER JOIN products p ON p.product_id = c.product_id
        WHERE c.customer_id = ? AND c.cart_id IN ($in_clause)
    ";
    $stmtCart = $conn->prepare($sqlCart);
    $stmtCart->bind_param("i", $customer_id);
    $stmtCart->execute();
    $resCart = $stmtCart->get_result();
    while ($row = $resCart->fetch_assoc()) $items[] = $row;
    $stmtCart->close();
}

if (!$items) {
    $_SESSION['message'] = 'Item tidak ditemukan.';
    header('Location: cart.php'); exit();
}

$final_items = [];
foreach ($items as $row) {
    $qty    = (int)$row['jumlah_barang'];
    $harga  = (int)$row['harga'];
    $weight = (int)$row['weight'] <= 0 ? 1000 : (int)$row['weight'];
    $subtotal    += $harga * $qty;
    $total_weight += $weight * $qty;
    $row['weight'] = $weight;
    $final_items[] = $row;
}
$items = $final_items;

// =====================================================
// 4) HITUNG TOTAL
// =====================================================
$discount = 0; 
$subtotal_after_discount = $subtotal - $discount;

$provinsi    = trim($_POST['provinsi'] ?? '');
$kota        = trim($_POST['kota'] ?? '');
$kecamatan   = trim($_POST['kecamatan'] ?? '');
$kelurahan   = trim($_POST['kelurahan'] ?? '-');
$postal_code = trim($_POST['kodepos'] ?? $_POST['postal_code'] ?? ORIGIN_POSTAL_CODE);
$alamat      = trim($_POST['alamat'] ?? '');
$code_courier  = trim($_POST['code_courier'] ?? '');
$shipping_type = trim($_POST['service_courier'] ?? '');
$ongkos_kirim  = (int)($_POST['shipping_cost'] ?? 0);

if (!$provinsi || !$kota || !$alamat || !$code_courier || !$shipping_type) {
    $_SESSION['message'] = 'Data pengiriman tidak lengkap.';
    header('Location: payment.php'); exit();
}

$grand_total = $subtotal_after_discount + $ongkos_kirim;
$payment_method = trim($_POST['metode'] ?? '');

// =====================================================
// 5) HANDLE UPLOAD BUKTI BAYAR & STATUS LOGIC
// =====================================================
$proof_filename = null;

// Default Status (Untuk Transfer)
$order_status = 'pending';
$pay_status   = 'pending';

if ($payment_method === 'Transfer') {
    if (isset($_FILES['bukti']) && $_FILES['bukti']['error'] === UPLOAD_ERR_OK) {
        $tmp_name = $_FILES['bukti']['tmp_name'];
        $name     = $_FILES['bukti']['name'];
        $ext      = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        
        $allowed = ['jpg', 'jpeg', 'png'];
        if (!in_array($ext, $allowed)) {
            $_SESSION['message'] = 'Format gambar bukti harus JPG atau PNG.';
            header('Location: payment.php'); exit();
        }

        $new_name = 'proof_' . date('YmdHis') . '_' . rand(1000, 9999) . '.' . $ext;
        
        $upload_dir = '../carts/payment_proofs/'; 
        
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        $target_file = $upload_dir . $new_name;

        if (move_uploaded_file($tmp_name, $target_file)) {
            $proof_filename = $new_name;
        } else {
            $_SESSION['message'] = 'Gagal menyimpan bukti pembayaran.';
            header('Location: payment.php'); exit();
        }
    } else {
        $_SESSION['message'] = 'Wajib upload bukti transfer.';
        header('Location: payment.php'); exit();
    }
} 
elseif ($payment_method === 'QRIS') {
    // Logic khusus QRIS: Langsung Confirmed
    $proof_filename = $_POST['kode_transaksi'] ?? 'QRIS-TRX-AUTO';
    $order_status   = 'confirmed';
    $pay_status     = 'verified';
}

// =====================================================
// 6) HELPER BITESHIP
// =====================================================
function createBiteshipOrder(array $payload): array {
    $ch = curl_init(BITESHIP_CREATE_ORDER_URL);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: ' . BITESHIP_API_KEY
        ],
        CURLOPT_POSTFIELDS => json_encode($payload)
    ]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return ['ok' => ($httpCode >= 200 && $httpCode < 300), 'data' => json_decode($response, true)];
}

// =====================================================
// 7) INSERT DB & API CALL
// =====================================================
$order_id = 'ORD-' . date('YmdHis') . '-' . random_int(100, 999);
if ($is_auction) {
    $order_id = 'STYRK_AUC_' . $auction_id . '_' . date('His');
}

$conn->begin_transaction();

try {
    // [FIX 1] INSERT KE TABEL ORDERS
    // Menggunakan variabel $order_status yang sudah ditentukan di atas
    $sqlOrder = "
        INSERT INTO orders
        (order_id, customer_id, tgl_order, provinsi, kota, kecamatan, kelurahan, postal_code,
         alamat, code_courier, shipping_type, ongkos_kirim, total_harga, shipping_status)
        VALUES
        (?, ?, NOW(), ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ";
    
    $stmtOrder = $conn->prepare($sqlOrder);
    if (!$stmtOrder) throw new Exception("Gagal prepare orders: " . $conn->error);

    // types: s i sssssssiii s (Total 12 parameter + 1 status = 13 placeholders di values query?? Cek jumlah ?)
    // Hitung ? di values: 13 tanda tanya.
    // Parameter bind: order_id, cust_id, prov, kota, kec, kel, postal, alamat, courier, ship_type, ongkir, total, status
    // Total variable: 13
    
    $stmtOrder->bind_param(
        "sisssssssiiis",
        $order_id, $customer_id, $provinsi, $kota, $kecamatan, $kelurahan, $postal_code,
        $alamat, $code_courier, $shipping_type, $ongkos_kirim, $grand_total, $order_status
    );
    $stmtOrder->execute();
    $stmtOrder->close();

    // --- Insert Details ---
    $sqlDetail = "INSERT INTO order_details (order_id, product_id, jumlah, harga_satuan, subtotal) VALUES (?, ?, ?, ?, ?)";
    $stmtDetail = $conn->prepare($sqlDetail);
    foreach ($items as $it) {
        $pid = (string)$it['product_id'];
        $jml = (int)$it['jumlah_barang'];
        $hrg = (int)$it['harga'];
        $sub = $hrg * $jml;
        $stmtDetail->bind_param("ssiii", $order_id, $pid, $jml, $hrg, $sub);
        $stmtDetail->execute();

        // Kurangi Stok
        $conn->query("UPDATE products SET stok = stok - $jml WHERE product_id = '$pid'");
    }
    $stmtDetail->close();

    // [FIX 2] INSERT KE TABEL PAYMENTS
    // Menggunakan variabel $pay_status yang sudah ditentukan di atas
    $sqlPayment = "
        INSERT INTO payments 
        (order_id, metode, jumlah_dibayar, tanggal_bayar, payment_proof, payment_status)
        VALUES (?, ?, ?, NOW(), ?, ?)
    ";
    $stmtPay = $conn->prepare($sqlPayment);
    if (!$stmtPay) throw new Exception("Gagal prepare payments: " . $conn->error);
    
    // types: s s i s s
    $stmtPay->bind_param("ssiss", $order_id, $payment_method, $grand_total, $proof_filename, $pay_status);
    $stmtPay->execute();
    $stmtPay->close();


    // --- Hapus Cart (Jika bukan lelang) ---
    if (!$is_auction && !empty($selected_cart_ids)) {
        $ids = implode(',', $selected_cart_ids);
        $conn->query("DELETE FROM carts WHERE customer_id = $customer_id AND cart_id IN ($ids)");
    }
    
    // --- Update Status Lelang (Jika lelang) ---
    if ($is_auction) {
        $conn->query("UPDATE auctions SET status='paid' WHERE auction_id = $auction_id");
    }

    $conn->commit();

} catch (Exception $e) {
    $conn->rollback();
    // Hapus gambar jika DB gagal
    if ($proof_filename && file_exists('../carts/payment_proofs/' . $proof_filename)) {
        unlink('../carts/payment_proofs/' . $proof_filename);
    }
    $_SESSION['message'] = 'Gagal memproses order: ' . $e->getMessage();
    header('Location: payment.php'); exit();
}

// --- Clear Voucher ---
unset($_SESSION['voucher_code'], $_SESSION['voucher_tipe'], $_SESSION['voucher_nilai_rupiah'], $_SESSION['voucher_nilai_persen']);

// --- Request Pickup Biteship ---
$biteshipItems = [];
foreach ($items as $it) {
    $biteshipItems[] = [
        "name" => $it['nama_produk'],
        "value" => (int)$it['harga'],
        "quantity" => (int)$it['jumlah_barang'],
        "weight" => (int)$it['weight']
    ];
}

$biteshipPayload = [
    "shipper_contact_name" => ORIGIN_CONTACT_NAME,
    "shipper_contact_phone" => ORIGIN_CONTACT_PHONE,
    "shipper_contact_email" => ORIGIN_CONTACT_EMAIL,
    "shipper_organization" => ORIGIN_ORGANIZATION,
    "origin_contact_name" => ORIGIN_CONTACT_NAME,
    "origin_contact_phone" => ORIGIN_CONTACT_PHONE,
    "origin_address" => ORIGIN_ADDRESS,
    "origin_postal_code" => ORIGIN_POSTAL_CODE,
    "destination_contact_name" => $dest_name,
    "destination_contact_phone" => $dest_phone,
    "destination_address" => $alamat,
    "destination_postal_code" => $postal_code,
    "courier_company" => $code_courier,
    "courier_type" => $shipping_type,
    "delivery_type" => "now",
    "items" => $biteshipItems,
    "order_note" => "Order $order_id",
    "metadata" => ["order_id" => $order_id]
];

// Call Biteship
try {
    $bs = createBiteshipOrder($biteshipPayload);
    if (!empty($bs['ok'])) {
        $bsData = $bs['data'];
        $awb = $bsData['courier']['waybill_id'] ?? null;
        $shpId = $bsData['id'] ?? null;
        
        $conn->query("UPDATE orders SET shipping_provider_order_id='$shpId', shipping_tracking_code='$awb' WHERE order_id='$order_id'");
    }
} catch (Exception $e) {
    $_SESSION['biteship_error'] = $e->getMessage();
}

// Redirect
header("Location: riwayat_belanja.php");
exit();
?>