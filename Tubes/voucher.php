<?php

require 'vendor/autoload.php';
require 'koneksi.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;


function buatVoucherDb($customerId, $nilai, $masaAktifHari, $keterangan)
{
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


function kirimVoucherEmail($emailPenerima, $namaPenerima, $subjek, $pesanBody, $voucherData)
{
  $mail = new PHPMailer(true);

  $nilaiFormatted = "Rp " . number_format($voucherData['nilai'], 0, ',', '.');
  $kadaluarsaFormatted = date('d F Y', strtotime($voucherData['kadaluarsa']));

  try {
    // SMTP
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = 'styrk.industries@gmail.com';
    $mail->Password   = 'cudw nbsm vxwo wfnm';
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
    $mail->Port       = 465;

    // From / To
    $mail->setFrom('no-reply@styrkindustries.com', 'Styrk Industries');
    $mail->addAddress($emailPenerima, $namaPenerima);

    // CONTENT
    $mail->isHTML(true);

    // SUBJECT (Inggris)
    $mail->Subject = $subjek ?: 'A Thank-You Voucher Just for You — STYRK Industries';

    // BODY (HTML Indonesia + banner + kartu voucher)
    $mail->Body = "
<!DOCTYPE html>
<html lang='id'>
<head>
  <meta http-equiv='Content-Type' content='text/html; charset=UTF-8'>
  <meta name='color-scheme' content='light dark'>
  <meta name='supported-color-schemes' content='light dark'>
  <title>STYRK Industries</title>
  <style>
    @media only screen and (max-width:600px){
      .wrapper{width:100%!important}
      .content{padding:20px!important}
      .cta{display:block!important;width:100%!important}
    }
    a{text-decoration:none}
  </style>
</head>
<body style='margin:0;padding:0;background:#0b0b0d;font-family:Arial,Helvetica,sans-serif;color:#e6e6e6;'>
  <!-- Preheader (tersembunyi di inbox preview) -->
  <div style='display:none;max-height:0;overflow:hidden;opacity:0;'>
    Voucher spesial dari STYRK — gunakan sebelum {$kadaluarsaFormatted}.
  </div>

  <table role='presentation' width='100%' cellpadding='0' cellspacing='0' border='0' style='background:#0b0b0d;'>
    <tr>
      <td align='center' style='padding:24px;'>
        <table class='wrapper' width='600' cellpadding='0' cellspacing='0' border='0'
               style='width:600px;max-width:600px;background:#111315;border-radius:14px;overflow:hidden;box-shadow:0 6px 24px rgba(0,0,0,.35);'>

          <!-- Banner -->
          <tr>
            <td align='center' style='background:#000;'>
              <img src='https://i.postimg.cc/qM5FMdxz/styrk-banner-jpg.jpg' alt='STYRK Industries'
                   style='display:block;width:100%;height:auto;border:0;'>
            </td>
          </tr>

          <!-- Salam & pesan -->
          <tr>
            <td class='content' style='padding:26px 30px 8px 30px;'>
              <h2 style='margin:0 0 8px 0;font-size:22px;color:#f5f5f5;font-weight:700;'>
                Halo, " . htmlspecialchars($namaPenerima) . " 👋
              </h2>
              <p style='margin:0;color:#cfcfcf;line-height:1.6;font-size:14px;'>
                " . nl2br(htmlspecialchars($pesanBody)) . "
              </p>
            </td>
          </tr>

          <!-- Kartu Voucher -->
          <tr>
            <td style='padding:14px 30px 6px 30px;'>
              <table role='presentation' width='100%' cellpadding='0' cellspacing='0' border='0'
                     style='background:#0f1012;border:1px solid #2a2d33;border-radius:12px;'>
                <tr>
                  <td style='padding:18px 20px;'>
                    <p style='margin:0 0 6px 0;font-size:14px;color:#cfcfcf;'>
                      Hadiah untuk Anda:
                    </p>
                    <div style='margin:0 0 12px 0;font-size:28px;font-weight:800;letter-spacing:3px;text-align:center;
                                color:#111315;background:#d4af37;border-radius:10px;padding:14px 16px;'>
                      " . htmlspecialchars($voucherData['kode']) . "
                    </div>
                    <table role='presentation' width='100%' cellpadding='0' cellspacing='0' border='0' style='font-size:14px;color:#d7d7d7;'>
                      <tr>
                        <td style='padding:4px 0;'>Nilai voucher</td>
                        <td style='padding:4px 0;' align='right'><strong>{$nilaiFormatted}</strong></td>
                      </tr>
                      <tr>
                        <td style='padding:4px 0;'>Berlaku hingga</td>
                        <td style='padding:4px 0;' align='right'><strong>{$kadaluarsaFormatted}</strong></td>
                      </tr>
                    </table>
                    <p style='margin:10px 0 0 0;font-size:12px;color:#9ea1a8;'>
                      Gunakan kode di atas saat checkout. Syarat & ketentuan berlaku.
                    </p>
                  </td>
                </tr>
              </table>
            </td>
          </tr>

          <!-- CTA -->
          <tr>
            <td align='center' style='padding:20px 30px 10px 30px;'>
              <a class='cta' href='http://localhost/Tubes/user/produk.php'
                 style='background:#d4af37;color:#111315;padding:14px 24px;font-size:16px;font-weight:700;border-radius:10px;display:inline-block;'>
                 Belanja Sekarang
              </a>
              <p style='margin:10px 0 0 0;font-size:12px;color:#aaaaaa;'>
                Yuk cek koleksi terbaru kami. Stok terbatas ⚡
              </p>
            </td>
          </tr>

          <!-- Footer -->
          <tr>
            <td align='center' style='padding:18px 20px 26px 20px;background:#0b0b0d;border-top:1px solid #1e2127;'>
              <p style='margin:0;font-size:12px;color:#8c8f96;'>
                © " . date('Y') . " STYRK Industries — All rights reserved.
              </p>
            </td>
          </tr>

        </table>
      </td>
    </tr>
  </table>
</body>
</html>
";

    // ALT BODY (plaintext fallback)
    $mail->AltBody =
      'Halo, ' . $namaPenerima . '!' . "\n" .
      strip_tags($pesanBody) . "\n\n" .
      'Kode Voucher: ' . $voucherData['kode'] . ' | Nilai: ' . $nilaiFormatted . ' | Berlaku hingga: ' . $kadaluarsaFormatted . "\n" .
      'Belanja sekarang: http://localhost/Tubes/user/produk.php';

    // SEND
    $mail->send();
    return true;
  } catch (Exception $e) {
    error_log("PHPMailer Error: {$mail->ErrorInfo}");
    return false;
  }
}




/* ===============================================================
   Tambahan: Voucher STYRKIKUZO (10% global, unlimited use)
   =============================================================== */
function buatVoucherSTYRKIKUZO()
{
  global $conn;

  $kodeVoucher = 'STYRKIKUZO';
  $persen = 10;
  $customerId = 0; // global, semua bisa pakai
  $tglKadaluarsa = date('Y-m-d H:i:s', strtotime('+365 days')); // berlaku 1 tahun, bisa lo ubah
  $keterangan = 'Voucher global diskon 10% - STYRKIKUZO';

  // Cek dulu biar gak dobel
  $cek = $conn->prepare("SELECT kode_voucher FROM vouchers WHERE kode_voucher = ?");
  $cek->bind_param("s", $kodeVoucher);
  $cek->execute();
  $hasil = $cek->get_result();

  if ($hasil && $hasil->num_rows > 0) {
    return ['kode' => $kodeVoucher, 'persen' => $persen, 'kadaluarsa' => $tglKadaluarsa];
  }

  // Insert baru kalau belum ada
  $sql = "INSERT INTO vouchers (customer_id, kode_voucher, tipe, nilai_rupiah, nilai_persen, tgl_kadaluarsa, status, keterangan)
            VALUES (?, ?, 'persen', 0, ?, ?, 'aktif', ?)";
  $stmt = $conn->prepare($sql);
  if ($stmt) {
    $stmt->bind_param("isiss", $customerId, $kodeVoucher, $persen, $tglKadaluarsa, $keterangan);
    if ($stmt->execute()) {
      return ['kode' => $kodeVoucher, 'persen' => $persen, 'kadaluarsa' => $tglKadaluarsa];
    }
  }

  return null;
}

// --- TAMBAHKAN FUNGSI BARU INI DI voucher_manager.php ---

function kirimEmailPemenangLelang($emailPenerima, $namaPenerima, $namaBarang, $totalTagihan, $order_id)
{
  $mail = new PHPMailer(true);

  $totalFormatted = "Rp " . number_format($totalTagihan, 0, ',', '.');
  // Ganti URL ini ke halaman riwayat belanja Anda
  $linkPembayaran = "http://localhost/Tubes/riwayat_belanja.php";

  try {
    // Konfigurasi Server SMTP (sesuaikan dengan setting Anda)
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = 'styrk.industries@gmail.com';
    $mail->Password   = 'cudw nbsm vxwo wfnm'; // App Password Anda
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
    $mail->Port       = 465;

    // Penerima & Pengirim
    $mail->setFrom('no-reply@styrkindustries.com', 'Styrk Industries (Lelang)');
    $mail->addAddress($emailPenerima, $namaPenerima);

    // Konten Email
    $mail->isHTML(true);
    $mail->Subject = 'Selamat, Anda Memenangkan Lelang!';
    $mail->Body    = "
            <html><body>
                <h2>Halo, " . htmlspecialchars($namaPenerima) . "!</h2>
                <p>Selamat! Anda telah memenangkan lelang untuk barang:</p>
                <h3 style='padding: 10px; background: #f0f0f0;'>" . htmlspecialchars($namaBarang) . "</h3>
                <p>Total tagihan Anda adalah: <strong>{$totalFormatted}</strong></p>
                <p>Pesanan dengan ID <strong>{$order_id}</strong> telah dibuatkan untuk Anda. Silakan segera selesaikan pembayaran melalui halaman Riwayat Belanja Anda.</p>
                <br>
                <a href='{$linkPembayaran}' style='background-color: #28a745; color: white; padding: 15px 25px; text-decoration: none; border-radius: 5px;'>Bayar Sekarang</a>
            </body></html>
        ";

    $mail->send();
    return true;
  } catch (Exception $e) {
    error_log("PHPMailer Error (Lelang): {$mail->ErrorInfo}");
    return false;
  }
}
