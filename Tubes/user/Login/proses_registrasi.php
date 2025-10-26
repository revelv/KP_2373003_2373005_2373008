<?php
include '../koneksi.php';
include '../../voucher.php';

session_start();

if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

$nama = $_POST['nama'];
$email = $_POST['email'];
$provinsi = $_POST['provinsi'];
$kota = $_POST['kota'];
$alamat = $_POST['alamat'];
$no_telepon = $_POST['telp'];
$password = $_POST['password'];
$konfirmasi = $_POST['konfirmasi'];

$errors = [];

if ($password !== $konfirmasi) {
    $errors[] = "Password dan konfirmasi password tidak sama";
}

$check_email = $conn->prepare("SELECT email FROM customer WHERE email = ?");
$check_email->bind_param("s", $email);
$check_email->execute();
$check_email->store_result();

if ($check_email->num_rows > 0) {
    $errors[] = "Email sudah terdaftar";
}

$check_email->close();

if (!empty($errors)) {
    $_SESSION['register_error'] = implode('<br>', $errors);
    $_SESSION['form_data'] = $_POST;
    header("Location: registrasi.php");
    exit;
}

$hashed_password = password_hash($password, PASSWORD_DEFAULT);

$stmt = $conn->prepare("INSERT INTO customer (nama, password, email, no_telepon, provinsi, kota, alamat) VALUES (?, ?, ?, ?, ?, ?, ?)");
$stmt->bind_param("sssssss", $nama, $hashed_password, $email, $no_telepon, $provinsi, $kota, $alamat);

if ($stmt->execute()) {
    $newUserId = $conn->insert_id;

    $_SESSION['kd_cs'] = $newUserId;
    $_SESSION['nama'] = $nama;
    $_SESSION['email'] = $email;

    $update_stmt = $conn->prepare("UPDATE customer SET last_login = NOW() WHERE customer_id = ?");
    if ($update_stmt) {
        $update_stmt->bind_param("i", $newUserId);
        $update_stmt->execute();
        $update_stmt->close();
    }

    $pilihanNominal = [10000];
    $nilaiVoucher = $pilihanNominal[array_rand($pilihanNominal)];
    $voucher = buatVoucherDb($newUserId, $nilaiVoucher, 14, "Voucher Selamat Datang");

    if ($voucher) {
        $subjek = "Selamat Datang di Styrk Industries! Ini Hadiah Untukmu";
        $pesan = "Terima kasih telah bergabung dengan kami. Kami senang Anda ada di sini!";
        kirimVoucherEmail($email, $nama, $subjek, $pesan, $voucher);
    }

    echo "
        <div style='font-family: Arial, sans-serif; text-align: center; padding: 50px;'>
            <h2>Registrasi Berhasil!</h2>
            <p>Selamat datang, " . htmlspecialchars($nama) . ".</p>
            <p>Sebuah voucher selamat datang telah kami kirimkan ke email Anda.</p>
            <p>Anda akan diarahkan ke halaman produk dalam <span id='countdown'>3</span> detik...</p>
        </div>
    ";

    echo "
        <script>
            let seconds = 3;
            const countdownElement = document.getElementById('countdown');
            
            const interval = setInterval(() => {
                seconds--; // Kurangi 1 detik
                countdownElement.textContent = seconds; // Update angka di layar
                
                if (seconds <= 0) {
                    clearInterval(interval); // Hentikan hitungan
                    window.location.href = '../produk.php'; // Redirect ke halaman produk
                }
            }, 1000); // Ulangi setiap 1000ms (1 detik)
        </script>
    ";
} else {
    echo "<div class='alert alert-danger'>Error: " . $stmt->error . "</div>";
    echo "<a href='javascript:history.back()'>Kembali</a>";
}

$stmt->close();
$conn->close();
