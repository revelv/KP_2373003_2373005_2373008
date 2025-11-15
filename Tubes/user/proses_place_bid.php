<?php
session_start();
include 'koneksi.php'; // Pastikan path koneksi ini benar

// 1. Cek User & Metode
if (!isset($_SESSION['kd_cs'])) {
    die("Anda harus login untuk menawar.");
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die("Metode tidak diizinkan.");
}

$customer_id = $_SESSION['kd_cs'];
$auction_id = (int)$_POST['auction_id'];
$bid_amount = (float)$_POST['bid_amount'];

// Mulai transaksi untuk mencegah race condition (tawaran bersamaan)
$conn->begin_transaction();

try {
    // 2. Ambil data lelang saat ini & KUNCI ROW untuk update
    $stmt = $conn->prepare("SELECT * FROM auctions WHERE auction_id = ? FOR UPDATE");
    $stmt->bind_param("i", $auction_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows === 0) {
        throw new Exception("Lelang tidak ditemukan.");
    }
    $auction = $result->fetch_assoc();
    $stmt->close();

    // 3. Validasi
    if ($auction['status'] !== 'active' || strtotime($auction['end_time']) <= time()) {
        throw new Exception("Lelang ini sudah berakhir.");
    }
    if ($bid_amount <= $auction['current_bid']) {
        throw new Exception("Tawaran Anda harus lebih tinggi dari tawaran saat ini (Rp " . number_format($auction['current_bid'], 0, ',', '.') . ").");
    }

    // 4. Simpan bid baru ke history (tabel 'bids')
    $stmt_bid = $conn->prepare("INSERT INTO bids (auction_id, customer_id, bid_amount) VALUES (?, ?, ?)");
    $stmt_bid->bind_param("iid", $auction_id, $customer_id, $bid_amount);
    $stmt_bid->execute();
    $stmt_bid->close();

    // 5. Update tawaran tertinggi di lelang (tabel 'auctions')
    $stmt_auc = $conn->prepare("UPDATE auctions SET current_bid = ?, current_winner_id = ? WHERE auction_id = ?");
    $stmt_auc->bind_param("dii", $bid_amount, $customer_id, $auction_id);
    $stmt_auc->execute();
    $stmt_auc->close();

    // 6. Jika semua sukses, commit
    $conn->commit();
    // Redirect kembali ke halaman detail
    header('Location: auction_detail.php?id=' . $auction_id);
    exit();

} catch (Exception $e) {
    // 7. Jika ada error, batalkan semua
    $conn->rollback();
    // Simpan pesan error di session untuk ditampilkan di halaman detail
    $_SESSION['alert'] = $e->getMessage();
    header('Location: auction_detail.php?id=' . $auction_id);
    exit();
}
?>