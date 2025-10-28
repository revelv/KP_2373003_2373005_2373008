<?php
require 'voucher.php'; 

$voucher = buatVoucherSTYRKIKUZO();

if ($voucher) {
    echo "<h3>Voucher berhasil dibuat!</h3>";
    echo "<p>Kode: <strong>{$voucher['kode']}</strong></p>";
    echo "<p>Diskon: {$voucher['persen']}%</p>";
    echo "<p>Berlaku sampai: {$voucher['kadaluarsa']}</p>";
} else {
    echo "<h3>Voucher sudah ada atau gagal dibuat.</h3>";
}
?>
