<?php
session_start();
include 'koneksi.php';

// Pastikan user sudah login
if (!isset($_SESSION['kd_cs'])) {
    header('Location: login.php');
    exit();
}

// Ambil kode voucher dari form
if (!isset($_POST['kode_voucher']) || empty($_POST['kode_voucher'])) {
    $_SESSION['alert'] = "Silakan masukkan kode voucher.";
    header('Location: cart.php');
    exit();
}

$kode_voucher = trim($_POST['kode_voucher']);
$customer_id  = $_SESSION['kd_cs'];

// Ambil voucher dari DB
// Catatan penting:
// - status harus 'aktif'
// - tgl_kadaluarsa belum lewat (atau NULL)
// - kita gak batasi customer_id di query; nanti kita cek manual apakah dia boleh pakai
$stmt = $conn->prepare("
    SELECT *
    FROM vouchers
    WHERE kode_voucher = ?
      AND status = 'aktif'
      AND (tgl_kadaluarsa IS NULL OR tgl_kadaluarsa >= NOW())
    LIMIT 1
");
$stmt->bind_param("s", $kode_voucher);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $_SESSION['alert'] = "Kode voucher tidak ditemukan atau sudah kadaluarsa.";
    header('Location: cart.php');
    exit();
}

$voucher = $result->fetch_assoc();
$stmt->close();

/*
  RULE PAKAI VOUCHER:
  - Kalau voucher.customer_id == 0 → global → siapa saja boleh pakai
  - Kalau voucher.customer_id == {id user} → boleh (voucher personal)
  - Kalau voucher.customer_id bukan 0 dan bukan id user → tolak
*/
if ($voucher['customer_id'] != 0 && $voucher['customer_id'] != $customer_id) {
    $_SESSION['alert'] = "Kode voucher tidak valid atau bukan milik Anda.";
    header('Location: cart.php');
    exit();
}

// Cek lagi status custom 'terpakai' kalau ada skema begitu
// (di kode lama lo, ada status 'terpakai'. Kita respect itu.)
if (isset($voucher['status']) && $voucher['status'] === 'terpakai') {
    $_SESSION['alert'] = "Voucher sudah pernah digunakan.";
    header('Location: cart.php');
    exit();
}

// === Voucher valid ===
// Simpan semua info ke session supaya cart.php & payment.php bisa ngitung
// Kita dukung 2 tipe:
//  - tipe = 'rupiah'   → potong nilai_rupiah (ex: 25000)
//  - tipe = 'persen'   → potong subtotal * (nilai_persen/100), ex: 10%

$_SESSION['voucher_code']             = $voucher['kode_voucher'];        // contoh: STYRKIKUZO
$_SESSION['voucher_tipe']             = $voucher['tipe'] ?? 'rupiah';    // 'persen' / 'rupiah'
$_SESSION['voucher_nilai_persen']     = $voucher['nilai_persen'] ?? 0;   // ex: 10
$_SESSION['voucher_nilai_rupiah']     = $voucher['nilai_rupiah'] ?? 0;   // ex: 20000

// compat lama: sebagian kode kamu masih baca voucher_discount (nominal rupiah fix)
// untuk tipe persen kita set 0, diskonnya bakal dihitung dinamis di cart/payment
if ($_SESSION['voucher_tipe'] === 'rupiah') {
    $_SESSION['voucher_discount'] = $_SESSION['voucher_nilai_rupiah'];
} else {
    $_SESSION['voucher_discount'] = 0;
}

// kasih notifikasi sukses
$_SESSION['message'] = "Voucher berhasil digunakan! ({$voucher['kode_voucher']})";

header('Location: cart.php');
exit();
