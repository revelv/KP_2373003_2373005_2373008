<?php

include 'voucher.php';
global $conn;

$today = new DateTime('now', new DateTimeZone('Asia/Jakarta'));

if ($today->format('Y-m-d') === '2025-12-25') {
    // Ambil semua customer yang belum pernah dapat voucher Natal 2025
    $sqlNatal = "SELECT c.* FROM customer c LEFT JOIN vouchers v ON c.customer_id = v.customer_id AND v.keterangan = 'Voucher Spesial Natal 2025' WHERE v.voucher_id IS NULL";
    $resultNatal = $conn->query($sqlNatal);

    if ($resultNatal->num_rows > 0) {
        while ($user = $resultNatal->fetch_assoc()) {
            $pilihanNominal = [10000, 12500, 15000, 20000, 25000, 30000, 40000, 50000];
            $nilaiVoucher = $pilihanNominal[array_rand($pilihanNominal)];
            $voucher = buatVoucherDb($user['customer_id'], $nilaiVoucher, 30, "Voucher Spesial Natal 2025");
            if ($voucher) {
                $subjek = "🎅 Hadiah Natal Spesial dari Styrk Industries!";
                $pesan = "Selamat Hari Natal! Semoga damai dan sukacita menyertai Anda. Nikmati hadiah kecil dari kami.";
                kirimVoucherEmail($user['email'], $user['nama'], $subjek, $pesan, $voucher);
                echo "Voucher Natal terkirim ke " . $user['email'] . "\n";
            }
        }
    }
}


$tigaBulanLalu = date('Y-m-d H:i:s', strtotime("-3 months"));

// Ambil user yang login terakhirnya lebih dari 3 bulan lalu
// DAN belum pernah dikirimi email re-engagement dalam 3 bulan terakhir
$sqlPasif = "SELECT * FROM customer WHERE last_login <= '$tigaBulanLalu' AND (last_reengagement_sent IS NULL OR last_reengagement_sent <= '$tigaBulanLalu')";
$resultPasif = $conn->query($sqlPasif);

if ($resultPasif->num_rows > 0) {
    while ($user = $resultPasif->fetch_assoc()) {
        $pilihanNominal = [10000, 12500, 15000, 20000, 25000, 30000, 40000, 50000];
        $nilaiVoucher = $pilihanNominal[array_rand($pilihanNominal)];
        $voucher = buatVoucherDb($user['customer_id'], $nilaiVoucher, 7, "Voucher Comeback!");

        if ($voucher) {
            $subjek = "Kami Rindu Kamu Berbelanja, " . $user['nama'] . "!";
            $pesan = "Sudah lama Anda tidak berkunjung. Kami harap semuanya baik-baik saja. Ada banyak produk baru yang mungkin Anda suka!";

            if (kirimVoucherEmail($user['email'], $user['nama'], $subjek, $pesan, $voucher)) {
                // UPDATE penanda waktu agar tidak dikirimi email terus-menerus
                $updateSql = "UPDATE customer SET last_reengagement_sent = NOW() WHERE customer_id = " . $user['customer_id'];
                $conn->query($updateSql);
                echo "Voucher re-engagement terkirim ke " . $user['email'] . "\n";
            }
        }
    }
}
