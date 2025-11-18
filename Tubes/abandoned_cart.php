<?php
// kirim_email_abandoned.php

// Panggil autoloader dari Composer
// Ganti include/require di file otomatisasi jadi kayak gini:
require_once __DIR__ . '/vendor/autoload.php';
include_once __DIR__ . '/koneksi.php'; // Atau sesuaikan lokasi koneksi root lu

// Import class PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;


// Cek koneksi
if (!$conn) {
  die("Koneksi Gagal: " . mysqli_connect_error());
}

// --- 2. Logika untuk Mencari Abandoned Cart ---
// Cari customer yang memiliki item di keranjang yang 'terbengkalai'
// (lebih dari 1 hari dan belum pernah dinotifikasi)
$sql_customers = "
    SELECT DISTINCT 
        c.customer_id, 
        cust.nama, 
        cust.email
    FROM carts c
    JOIN customer cust ON c.customer_id = cust.customer_id
    WHERE 
        c.updated_at <= NOW() - INTERVAL 1 DAY
        AND c.notified_at IS NULL
";

$result_customers = mysqli_query($conn, $sql_customers);

if ($result_customers && mysqli_num_rows($result_customers) > 0) {
  echo "Ditemukan " . mysqli_num_rows($result_customers) . " customer dengan keranjang terbengkalai.\n";

  // Loop untuk setiap customer yang ditemukan
  while ($customer = mysqli_fetch_assoc($result_customers)) {
    $customer_id = $customer['customer_id'];
    $customer_nama = $customer['nama'];
    $customer_email = $customer['email'];

    // Ambil SEMUA item dari keranjang customer ini untuk ditampilkan di email
    $sql_items = "
            SELECT p.nama_produk, c.jumlah_barang 
            FROM carts c
            JOIN products p ON c.product_id = p.product_id
            WHERE c.customer_id = '$customer_id'
        ";
    $result_items = mysqli_query($conn, $sql_items);

    $items_list_html = '<ul>';
    while ($item = mysqli_fetch_assoc($result_items)) {
      $items_list_html .= '<li>' . htmlspecialchars($item['nama_produk']) . ' (Qty: ' . $item['jumlah_barang'] . ')</li>';
    }
    $items_list_html .= '</ul>';

    // Kirim email menggunakan fungsi di bawah
    if (kirimEmailNotifikasi($customer_email, $customer_nama, $items_list_html)) {
      echo "Email berhasil dikirim ke " . $customer_email . "\n";

      // Tandai SEMUA item di keranjang customer ini agar tidak dikirimi email lagi
      $sql_update = "UPDATE carts SET notified_at = NOW() WHERE customer_id = '$customer_id'";
      mysqli_query($conn, $sql_update);
    } else {
      echo "Gagal mengirim email ke " . $customer_email . "\n";
    }
  }
} else {
  echo "Tidak ada keranjang terbengkalai yang ditemukan.\n";
}


// --- 3. Fungsi untuk Mengirim Email dengan PHPMailer ---
// GANTI FUNGSI kirimEmailNotifikasi DENGAN INI
function kirimEmailNotifikasi($email_penerima, $nama_penerima, $daftar_item_html)
{
  $mail = new PHPMailer(true);

  try {
    // Debugging: Aktifin ini kalau mau liat proses detail SMTP
    // $mail->SMTPDebug = 2; 
    // $mail->Debugoutput = 'html';

    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = 'styrk.industries@gmail.com';
    $mail->Password   = 'fexu yqdy woef kepl'; // Pastikan ini App Password yang valid (16 karakter tanpa spasi sebenernya, tapi PHPMailer biasanya handle spasi)
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
    $mail->Port       = 465;

    $mail->setFrom('no-reply@styrkindustries.com', 'Styrk Industries');
    $mail->addAddress($email_penerima, $nama_penerima);

    $mail->isHTML(true);
    $mail->Subject = 'Your Cart Is Waiting';

    // ... (Isi Body HTML sama kayak punya lu, gak perlu diubah) ...
    $mail->Body = "
        <html>
          <body style='margin:0;padding:0;background:#0b0b0d;font-family:Arial,Helvetica,sans-serif;color:#e6e6e6;'>
            <table role='presentation' width='100%' cellpadding='0' cellspacing='0' border='0' style='background:#0b0b0d;'>
              <tr>
                <td align='center' style='padding:24px;'>
                  <table width='600' cellpadding='0' cellspacing='0' border='0' style='width:600px;max-width:600px;background:#111315;border-radius:14px;overflow:hidden;box-shadow:0 6px 24px rgba(0,0,0,.35);'>
                    <tr>
                      <td align='center' style='background:#000;'>
                        <img src='https://i.postimg.cc/qM5FMdxz/styrk-banner-jpg.jpg' alt='STYRK Industries' style='display:block;width:100%;height:auto;border:0;'>
                      </td>
                    </tr>
                    <tr>
                      <td style='padding:26px 30px 8px 30px;'>
                        <h2 style='margin:0 0 8px 0;font-size:22px;color:#f5f5f5;font-weight:700;'>
                          Halo, " . htmlspecialchars($nama_penerima) . " 👋
                        </h2>
                        <p style='margin:0;color:#cfcfcf;line-height:1.6;font-size:14px;'>
                          We saved your picks. Lengkapi build kamu sebelum kehabisan stok.
                        </p>
                      </td>
                    </tr>
                    <tr>
                      <td style='padding:12px 30px 6px 30px;'>
                        <div style='background:#0f1012;border:1px solid #2a2d33;border-radius:10px;padding:16px;'>
                          " . $daftar_item_html . "
                        </div>
                      </td>
                    </tr>
                    <tr>
                      <td align='center' style='padding:18px 30px 6px 30px;'>
                        <a href='http://localhost:3000/Tubes/user/cart.php'
                           style='background:#d4af37;color:#111315;padding:14px 22px;font-size:16px;font-weight:700;border-radius:10px;display:inline-block;text-decoration:none;'>
                           Continue to Checkout
                        </a>
                        <p style='margin:10px 0 0 0;font-size:12px;color:#aaaaaa;'>
                          Popular items are limited. We can’t hold them for long ⚡
                        </p>
                      </td>
                    </tr>
                    <tr>
                      <td align='center' style='padding:20px 20px 26px 20px;background:#0b0b0d;border-top:1px solid #1e2127;'>
                        <p style='margin:0;font-size:12px;color:#8c8f96;'>© " . date("Y") . " STYRK Industries</p>
                      </td>
                    </tr>
                  </table>
                </td>
              </tr>
            </table>
          </body>
        </html>
    ";
    $mail->AltBody = 'Hi, ' . htmlspecialchars($nama_penerima) . '! Your selected items are still in your cart at STYRK Industries.';

    $mail->send();
    return true;
  } catch (Exception $e) {
    // TAMPILKAN ERROR KE LAYAR BIAR KETAUAN
    echo "Mailer Error: " . $mail->ErrorInfo;
    return false;
  }
}
