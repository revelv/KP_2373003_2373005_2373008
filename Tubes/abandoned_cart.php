<?php
// kirim_email_abandoned.php

// Panggil autoloader dari Composer
require 'vendor/autoload.php'; // Pastikan path ini benar

// Import class PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// --- 1. Koneksi Database ---
// Menggunakan file koneksi yang sudah ada agar konsisten
include 'koneksi.php'; // Ganti jika nama file koneksi Anda berbeda

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
    while($customer = mysqli_fetch_assoc($result_customers)) {
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
        while($item = mysqli_fetch_assoc($result_items)) {
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

mysqli_close($conn);

// --- 3. Fungsi untuk Mengirim Email dengan PHPMailer ---
function kirimEmailNotifikasi($email_penerima, $nama_penerima, $daftar_item_html) {
    $mail = new PHPMailer(true);

    try {

        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';      
        $mail->SMTPAuth   = true;
        $mail->Username   = 'styrk.industries@gmail.com';   
        $mail->Password   = 'cudw nbsm vxwo wfnm';    
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port       = 465;

        // Penerima & Pengirim
        $mail->setFrom('no-reply@styrkindustries.com', 'Styrk Industries');
        $mail->addAddress($email_penerima, $nama_penerima);

        // Konten Email
        $mail->isHTML(true);
        $mail->Subject = 'Barang Anda Menunggu di Keranjang - Styrk Industries';
        $mail->Body    = "
            <html>
            <body>
                <h2>Halo, " . htmlspecialchars($nama_penerima) . "!</h2>
                <p>Kami melihat Anda masih memiliki beberapa barang menarik di keranjang belanja:</p>
                " . $daftar_item_html . "
                <p>Jangan sampai kehabisan stok! Segera selesaikan pesanan Anda dengan mengklik tombol di bawah ini.</p>
                <br>
                <a href='http://localhost:3000/Tubes/user/cart.php' style='background-color: #28a745; color: white; padding: 15px 25px; text-align: center; text-decoration: none; display: inline-block; font-size: 16px; border-radius: 5px;'>Lanjutkan ke Checkout</a>
                <br><br>
                <p>Terima kasih,</p>
                <p>Tim Styrk Industries</p>
            </body>
            </html>
        ";
        $mail->AltBody = 'Halo, ' . htmlspecialchars($nama_penerima) . '! Anda memiliki barang di keranjang belanja. Segera selesaikan pesanan Anda di https://www.websiteanda.com/cart.php';

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("PHPMailer Error: {$mail->ErrorInfo}"); // Mencatat error ke log server
        return false;
    }

}
?>
