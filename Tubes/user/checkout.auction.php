<?php
session_start();
include 'koneksi.php';

if (!isset($_SESSION['kd_cs'])) {
    die("Anda harus login terlebih dahulu.");
}
$customer_id = (int)$_SESSION['kd_cs'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $payment_method = $_POST['metode'] ?? '';
    $order_id = $_POST['order_id'] ?? '';

    if (empty($order_id)) {
        die("Order ID tidak ada.");
    }

    // Ambil data order untuk verifikasi
    $order_stmt = $conn->prepare("SELECT * FROM orders WHERE order_id = ? AND customer_id = ? AND status = 'pending'");
    $order_stmt->bind_param("si", $order_id, $customer_id);
    $order_stmt->execute();
    $order_data = $order_stmt->get_result()->fetch_assoc();
    
    if (!$order_data) {
        die("Order tidak valid, sudah dibayar, atau bukan milik Anda.");
    }
    
    $grand_total = (float)$order_data['total_harga'];
    $tanggal = date("Y-m-d H:i:s");
    $status_baru = 'pending';
    $payment_proof = '';

    // (Logika handle Transfer Bank: upload bukti, dll)
    if ($payment_method === 'Transfer') {
        // ... (kode upload bukti) ...
    }
    // (Logika handle QRIS)
    elseif ($payment_method === 'QRIS') {
        // ... (kode ambil kode_transaksi) ...
    } else {
        die("Metode pembayaran tidak valid.");
    }

    $conn->begin_transaction();
    try {
        // 1. Masukkan ke tabel payments
        $pay_stmt = $conn->prepare("INSERT INTO payments (order_id, metode, jumlah_dibayar, tanggal_bayar, payment_proof, payment_status) VALUES (?, ?, ?, ?, ?, ?)");
        $pay_stmt->bind_param("ssdsss", $order_id, $payment_method, $grand_total, $tanggal, $payment_proof, $status_baru);
        $pay_stmt->execute();
        $pay_stmt->close();

        // 2. Update status di tabel orders
        $order_update_stmt = $conn->prepare("UPDATE orders SET status = ? WHERE order_id = ?");
        $order_update_stmt->bind_param("ss", $status_baru, $order_id);
        $order_update_stmt->execute();
        $order_update_stmt->close();
        
        // 3. Update status lelang jadi 'paid'
        if (strpos($order_id, 'STYRK_AUC_') === 0) {
            $parts = explode('_', $order_id);
            if (isset($parts[2]) && is_numeric($parts[2])) {
                $auction_id = (int)$parts[2];
                $conn->query("UPDATE auctions SET status = 'paid' WHERE auction_id = $auction_id");
            }
        }
        
        $conn->commit();

    } catch (Exception $e) {
        $conn->rollback();
        die("Terjadi error: ". $e->getMessage());
    }

    echo "<script>
        alert('Pembayaran berhasil diproses! Order Anda akan segera diverifikasi.');
        window.location.href = 'riwayat_belanja.php';
    </script>";
    exit;
}
?>