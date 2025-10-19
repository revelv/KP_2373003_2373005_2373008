<?php

require 'vendor/autoload.php';
require 'koneksi.php'; 

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;


function buatVoucherDb($customerId, $nilai, $masaAktifHari, $keterangan) {
    global $conn;

    $kodeVoucher = 'STYRK' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 6));
    
    // Tentukan tanggal kadaluarsa
    $tglKadaluarsa = date('Y-m-d H:i:s', strtotime("+$masaAktifHari days"));

    $sql = "INSERT INTO vouchers (customer_id, kode_voucher, nilai_rupiah, tgl_kadaluarsa, keterangan) VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("isiss", $customerId, $kodeVoucher, $nilai, $tglKadaluarsa, $keterangan);
        if ($stmt->execute()) {
            return [
                'kode' => $kodeVoucher,
                'nilai' => $nilai,
                'kadaluarsa' => $tglKadaluarsa
            ];
        }
    }
    return null; 
}


function kirimVoucherEmail($emailPenerima, $namaPenerima, $subjek, $pesanBody, $voucherData) {
    $mail = new PHPMailer(true);

    $nilaiFormatted = "Rp " . number_format($voucherData['nilai'], 0, ',', '.');
    $kadaluarsaFormatted = date('d F Y', strtotime($voucherData['kadaluarsa']));

    try {

        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'styrk.industries@gmail.com';
        $mail->Password   = 'cudw nbsm vxwo wfnm'; 
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port       = 465;


        $mail->setFrom('no-reply@styrkindustries.com', 'Styrk Industries');
        $mail->addAddress($emailPenerima, $namaPenerima);


        $mail->isHTML(true);
        $mail->Subject = $subjek;
        $mail->Body    = "
            <html><body>
                <h2>Halo, " . htmlspecialchars($namaPenerima) . "!</h2>
                <p>{$pesanBody}</p>
                <p>Sebagai hadiah, kami berikan voucher belanja sebesar <strong>{$nilaiFormatted}</strong> yang bisa Anda gunakan pada pesanan berikutnya.</p>
                <h3 style='background: #f0f0f0; padding: 15px; text-align: center; letter-spacing: 2px;'>{$voucherData['kode']}</h3>
                <p>Voucher ini berlaku hingga <strong>{$kadaluarsaFormatted}</strong>. Jangan sampai terlewat!</p>
                <br>
                <a href='http://localhost/Tubes/user/produk.php' style='background-color: #28a745; color: white; padding: 15px 25px; text-decoration: none; border-radius: 5px;'>Belanja Sekarang</a>
            </body></html>
        ";

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("PHPMailer Error: {$mail->ErrorInfo}");
        return false;
    }
}
?>