<?php
session_start();
include '../koneksi.php';
include '../../voucher.php';

if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

// Ambil data dari form (pakai ?? '' biar nggak notice kalau key hilang)
$nama        = trim($_POST['nama']       ?? '');
$email       = trim($_POST['email']      ?? '');
$provinsi    = trim($_POST['provinsi']   ?? '');   // NAMA provinsi
$kota        = trim($_POST['kota']       ?? '');   // NAMA kota
$kecamatan   = trim($_POST['kecamatan']  ?? '');   // NAMA kecamatan
$kelurahan   = trim($_POST['kelurahan']  ?? '');   // NAMA kelurahan
$alamat      = trim($_POST['alamat']     ?? '');
$no_telepon  = trim($_POST['telp']       ?? '');
$postal_code = trim($_POST['postal_code'] ?? '');  // KODE POS (BARU)
$password    = $_POST['password']        ?? '';
$konfirmasi  = $_POST['konfirmasi']      ?? '';

$errors = [];

// ================= VALIDASI DASAR =================
if (
    $nama === '' || $email === '' ||
    $provinsi === '' || $kota === '' || $kecamatan === '' || $kelurahan === '' ||
    $alamat === '' || $no_telepon === '' ||
    $postal_code === '' ||             // WAJIB ADA KODE POS
    $password === '' || $konfirmasi === ''
) {
    $errors[] = "Semua field wajib diisi, termasuk alamat lengkap dan kode pos.";
}

// Password harus sama
if ($password !== $konfirmasi) {
    $errors[] = "Password dan konfirmasi password tidak sama.";
}

// (Opsional) Validasi sederhana kode pos 5 digit
if ($postal_code !== '' && !preg_match('/^[0-9]{5}$/', $postal_code)) {
    $errors[] = "Kode pos harus berupa 5 digit angka.";
}

// Cek email sudah terdaftar atau belum
if ($email !== '') {
    $check_email = $conn->prepare("SELECT email FROM customer WHERE email = ?");
    if ($check_email) {
        $check_email->bind_param("s", $email);
        $check_email->execute();
        $check_email->store_result();

        if ($check_email->num_rows > 0) {
            $errors[] = "Email sudah terdaftar.";
        }
        $check_email->close();
    } else {
        $errors[] = "Gagal menyiapkan query pengecekan email.";
    }
}

// Kalau ada error, lempar balik ke form dengan data lama
if (!empty($errors)) {
    $_SESSION['register_error'] = implode('<br>', $errors);
    $_SESSION['form_data']      = $_POST;
    header("Location: registrasi.php");
    exit;
}

// ================= HASH PASSWORD =================
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

// ================= INSERT CUSTOMER BARU =================
// provinsi / kota / kecamatan / kelurahan disimpan sebagai NAMA (string)
// postal_code disimpan sebagai KODE POS (bukan lagi destination_id komship)
$stmt = $conn->prepare("
    INSERT INTO customer (
        nama, 
        password, 
        email, 
        no_telepon, 
        provinsi, 
        kota, 
        kecamatan,
        kelurahan,
        postal_code,
        alamat
    ) 
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
");

if (!$stmt) {
    echo "<div class='alert alert-danger'>Error: " . htmlspecialchars($conn->error) . "</div>";
    echo "<a href='javascript:history.back()'>Kembali</a>";
    exit;
}

// 10 kolom → 10 parameter string
$stmt->bind_param(
    "ssssssssss",
    $nama,
    $hashed_password,
    $email,
    $no_telepon,
    $provinsi,    // NAMA provinsi
    $kota,        // NAMA kota
    $kecamatan,   // NAMA kecamatan
    $kelurahan,   // NAMA kelurahan
    $postal_code, // KODE POS (BARU)
    $alamat
);

if ($stmt->execute()) {
    $newUserId = $conn->insert_id;

    // Set sesi user
    $_SESSION['kd_cs']  = $newUserId;
    $_SESSION['nama']   = $nama;
    $_SESSION['email']  = $email;

    // Update last_login
    $update_stmt = $conn->prepare("UPDATE customer SET last_login = NOW() WHERE customer_id = ?");
    if ($update_stmt) {
        $update_stmt->bind_param("i", $newUserId);
        $update_stmt->execute();
        $update_stmt->close();
    }

    // Generate voucher selamat datang
    $pilihanNominal = [10000];
    $nilaiVoucher   = $pilihanNominal[array_rand($pilihanNominal)];
    $voucher        = buatVoucherDb($newUserId, $nilaiVoucher, 14, "Voucher Selamat Datang");

    if ($voucher) {
        $subjek = "Selamat Datang di Styrk Industries! Ini Hadiah Untukmu";
        $pesan  = "Terima kasih telah bergabung dengan kami. Kami senang Anda ada di sini!";
        kirimVoucherEmail($email, $nama, $subjek, $pesan, $voucher);
    }

    // Tampilan sukses + redirect 3 detik
    echo "
        <div style='font-family: Arial, sans-serif; text-align: center; padding: 50px; color:#fff; background:#121212; min-height:100vh;'>
            <h2>Registrasi Berhasil!</h2>
            <p>Selamat datang, " . htmlspecialchars($nama) . ".</p>
            <p>Sebuah voucher selamat datang telah kami kirimkan ke email Anda.</p>
            <p>Anda akan diarahkan ke halaman produk dalam <span id='countdown'>3</span> detik...</p>
        </div>
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
    echo "<div class='alert alert-danger'>Error: " . htmlspecialchars($stmt->error) . "</div>";
    echo "<a href='javascript:history.back()'>Kembali</a>";
}

$stmt->close();
$conn->close();
