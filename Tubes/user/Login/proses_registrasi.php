<?php
include '../koneksi.php';
// Path ke voucher_manager.php mungkin perlu disesuaikan jika tidak di root
include '../../voucher_manager.php';

session_start();

if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

// ... (kode Anda untuk mengambil data form dan validasi tidak berubah) ...
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
    $newUserId = $conn->insert_id; // ID customer baru

    // --- MULAI MODIFIKASI: AUTO LOGIN & UPDATE last_login ---

    // 1. Langsung set session agar dianggap login
    $_SESSION['kd_cs'] = $newUserId;
    $_SESSION['nama'] = $nama;
    $_SESSION['email'] = $email;

    // 2. Update last_login untuk user baru ini
    $update_stmt = $conn->prepare("UPDATE customer SET last_login = NOW() WHERE customer_id = ?");
    if ($update_stmt) {
        $update_stmt->bind_param("i", $newUserId);
        $update_stmt->execute();
        $update_stmt->close();
    }
    // --- AKHIR MODIFIKASI ---


    // Kirim voucher selamat datang (kode asli Anda)
    $pilihanNominal = [10000]; // Anda bisa tambahkan nominal lain di sini
    $nilaiVoucher = $pilihanNominal[array_rand($pilihanNominal)];
    $voucher = buatVoucherDb($newUserId, $nilaiVoucher, 14, "Voucher Selamat Datang");

    if ($voucher) {
        $subjek = "Selamat Datang di Styrk Industries! Ini Hadiah Untukmu";
        $pesan = "Terima kasih telah bergabung dengan kami. Kami senang Anda ada di sini!";
        kirimVoucherEmail($email, $nama, $subjek, $pesan, $voucher);
    }

    // Tampilkan pesan sukses dan countdown (kode asli Anda)
    echo "
        <div style='font-family: Arial, sans-serif; text-align: center; padding: 50px;'>
            <h2>Registrasi Berhasil!</h2>
            <p>Selamat datang, " . htmlspecialchars($nama) . ". Anda sekarang sudah login.</p>
            <p>Sebuah voucher selamat datang telah kami kirimkan ke email Anda.</p>
            <p>Anda akan diarahkan ke halaman produk dalam <span id='countdown'>3</span> detik...</p>
        </div>
    ";

    echo "
        <script>
            let seconds = 3;
            const countdownElement = document.getElementById('countdown');
            
            const interval = setInterval(() => {
                seconds--; 
                countdownElement.textContent = seconds; 
                
                if (seconds <= 0) {
                    clearInterval(interval); 
                    window.location.href = '../produk.php'; 
                }
            }, 1000); 
        </script>
    ";
} else {
    echo "<div class='alert alert-danger'>Error: " . $stmt->error . "</div>";
    echo "<a href='javascript:history.back()'>Kembali</a>";
}

$stmt->close();
$conn->close();
