<?php
session_start();
include 'koneksi.php';

// Pastikan user sudah login
if (!isset($_SESSION['kd_cs'])) {
    header('Location: login.php');
    exit();
}

// Pastikan kode voucher dikirim
if (isset($_POST['kode_voucher']) && !empty($_POST['kode_voucher'])) {
    $kode_voucher = $_POST['kode_voucher'];
    $customer_id = $_SESSION['kd_cs'];

    // Cari voucher di database
    $stmt = $conn->prepare("SELECT * FROM vouchers WHERE kode_voucher = ? AND customer_id = ?");
    $stmt->bind_param("si", $kode_voucher, $customer_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $voucher = $result->fetch_assoc();

        // Cek status voucher
        if ($voucher['status'] == 'terpakai') {
            $_SESSION['alert'] = "Voucher sudah pernah digunakan.";
        } 
        // Cek tanggal kadaluarsa
        else if (strtotime($voucher['tgl_kadaluarsa']) < time()) {
            $_SESSION['alert'] = "Voucher sudah kadaluarsa.";
        } 
        // Jika voucher valid
        else {
            $_SESSION['voucher_code'] = $voucher['kode_voucher'];
            $_SESSION['voucher_discount'] = $voucher['nilai_rupiah'];
            $_SESSION['message'] = "Voucher berhasil digunakan!";
        }
    } else {
        $_SESSION['alert'] = "Kode voucher tidak valid atau bukan milik Anda.";
    }
    $stmt->close();
} else {
    $_SESSION['alert'] = "Silakan masukkan kode voucher.";
}

header('Location: cart.php');
exit();
?>