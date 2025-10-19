<?php
session_start();

unset($_SESSION['voucher_code']);
unset($_SESSION['voucher_discount']);

$_SESSION['message'] = "Voucher berhasil dihapus.";
header('Location: cart.php');
exit();
?>