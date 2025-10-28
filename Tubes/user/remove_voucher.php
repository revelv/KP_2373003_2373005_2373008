<?php
session_start();

// hapus semua data voucher aktif di session
unset($_SESSION['voucher_code']);
unset($_SESSION['voucher_tipe']);
unset($_SESSION['voucher_nilai_persen']);
unset($_SESSION['voucher_nilai_rupiah']);

// fallback lama, kalau masih ada dari versi sebelumnya
unset($_SESSION['voucher_discount']);

// kasih feedback
$_SESSION['message'] = "Voucher berhasil dihapus.";

// balik ke cart
header('Location: cart.php');
exit();
