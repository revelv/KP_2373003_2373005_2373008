<?php
// 1. PENGAMAN SESSION
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// 2. DEFINISI PATH ROOT (Penting biar gak salah alamat)
// __DIR__ = D:\...\Tubes\user
// $root_dir = D:\...\Tubes\  (Naik satu folder)
$root_dir = __DIR__ . '/../';

// Include koneksi utama dari root
if (file_exists($root_dir . 'koneksi.php')) {
    include_once $root_dir . 'koneksi.php';
} elseif (file_exists('koneksi.php')) {
    include_once 'koneksi.php';
}

// ==================================================================
// [OTOMATISASI] VERSI AGRESIF (PASTI JALAN)
// ==================================================================
// Kita pakai ob_start() cuma buat nahan teks "Email terkirim", 
// TAPI kita HAPUS try-catch yang menelan error.
ob_start();

// Daftar file yang mau dijalankan
$scripts_to_run = [
    'abandoned_cart.php',
    'otomatis_kirim.php',
    'proses_end_auctions.php'
];

foreach ($scripts_to_run as $script) {
    $target_file = $root_dir . $script;

    if (file_exists($target_file)) {
        // PENTING: Pakai 'include' biasa (bukan include_once) 
        // Supaya kalau kamu refresh halaman berkali-kali, dia tetap coba jalanin.
        include $target_file;
    }
}

// Buang semua teks sampah (hasil echo dari skrip otomatis) biar header bersih
ob_end_clean();




// --- KODE ASLI LU ---

if (isset($_SESSION['kd_cs'])) {
    $kode_cs = $_SESSION['kd_cs'];
}

if (isset($_POST['login'])) {
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = mysqli_real_escape_string($conn, $_POST['password']);

    $query = "SELECT * FROM customer WHERE email ='$username'";
    $result = mysqli_query($conn, $query);

    if ($result === false) {
        die("Query error: " . mysqli_error($conn));
    }

    if (mysqli_num_rows($result) > 0) {
        $user = mysqli_fetch_assoc($result);
        if (password_verify($password, $user['password'])) {
            $_SESSION['kd_cs'] = $user['customer_id'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['nama'] = $user['nama'];

            // UPDATE LAST LOGIN
            $uid_login = $user['customer_id'];
            mysqli_query($conn, "UPDATE customer SET last_login = NOW() WHERE customer_id = '$uid_login'");

            header("Location: produk.php");
            exit();
        } else {
            $login_error = "Password salah!";
        }
    } else {
        $login_error = "Akun tidak ditemukan!";
    }
}

if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: produk.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Styrk Industries</title>
    <link rel="stylesheet" href="./css/header.css">
    <link rel="stylesheet" href="./css/produk.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.5.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        .floating-wa {
            position: fixed;
            bottom: 25px;
            right: 25px;
            background-color: #25D366;
            color: white;
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 30px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
            z-index: 1000;
            transition: transform 0.2s ease;
        }

        .floating-wa:hover {
            transform: scale(1.1);
        }

        .login-form {
            padding: 1rem 1.25rem;
            min-width: 250px;
        }

        .login-error {
            color: red;
            font-size: 0.9rem;
        }
    </style>
</head>

<body>

    <nav class="navbar navbar-expand-lg">
        <div class="container_header">
            <a class="navbar-brand" href="home.php">
                <img src="https://i.postimg.cc/855ZSty7/no-bg.png" alt="Styrk Industries">
            </a>

            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarContent" aria-controls="navbarContent" aria-expanded="false" aria-label="Toggle navigation">
                <i class="bi bi-list" style="color: var(--primary-yellow); font-size: 2rem;"></i>
            </button>

            <div class="collapse navbar-collapse" id="navbarContent">
                <ul class="navbar-nav ms-auto mb-2 mb-lg-0" style="gap: 1rem;">

                    <li class="nav-item"><a class="nav-link" href="./produk.php">Products</a></li>
                    <li class="nav-item"><a class="nav-link" href="./community.php">Community</a></li>
                    <li class="nav-item"><a class="nav-link" href="./auction.php">Auction</a></li>

                    <?php
                    $cart_count = 0;
                    if (isset($_SESSION['kd_cs'])) {
                        $kode_cs = $_SESSION['kd_cs'];
                        if (!isset($conn) || !$conn) include $root_dir . 'koneksi.php';
                        $result = mysqli_query($conn, "SELECT COUNT(cart_id) as count FROM carts WHERE customer_id ='$kode_cs'");
                        if ($result) {
                            $row = mysqli_fetch_assoc($result);
                            $cart_count = $row['count'];
                        }
                    }
                    ?>

                    <li class="nav-item">
                        <a class="nav-link" href="./cart.php">
                            <i class="bi-cart-fill me-2"></i> Carts [<?= $cart_count ?>]
                        </a>
                    </li>

                    <li class="nav-item dropdown">
                        <?php if (isset($_SESSION['kd_cs'])): ?>
                            <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false">
                                <i class="bi bi-person-fill me-2"></i> <?= $_SESSION['nama'] ?>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                                <li><a class="dropdown-item" href="profile.php"><i class="bi bi-person-circle me-2"></i>Profil Saya</a></li>
                                <li><a class="dropdown-item" href="riwayat_belanja.php"><i class="bi bi-receipt me-2"></i>Riwayat Belanja</a></li>
                                <li>
                                    <hr class="dropdown-divider">
                                </li>
                                <li><a class="dropdown-item" href="?logout=1"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
                            </ul>
                        <?php else: ?>
                            <a class="nav-link dropdown-toggle" href="#" id="loginDropdown" role="button" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false">
                                <i class="bi bi-box-arrow-in-right me-2"></i> Login
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="loginDropdown">
                                <li>
                                    <div class="login-form">
                                        <form method="post" action="">
                                            <div class="mb-3">
                                                <label for="username" class="form-label">Email</label>
                                                <input type="text" class="form-control" id="username" name="username" required>
                                            </div>
                                            <div class="mb-3">
                                                <label for="password" class="form-label">Password</label>
                                                <input type="password" class="form-control" id="password" name="password" required>
                                            </div>
                                            <?php if (isset($login_error)): ?>
                                                <div class="login-error"><?= $login_error ?> <a href="forgot_password.php">forgot password?</a></div>
                                            <?php endif; ?>
                                            <button type="submit" name="login" class="btn btn-warning w-100"><i class="bi bi-box-arrow-in-right me-2"></i> Login</button>
                                        </form>
                                        <div class="text-center mt-3">
                                            <a href="./Login/registrasi.php" style="color: var(--black); text-decoration: none; font-size: 1.1rem;"><i class="bi bi-person-plus me-2"></i> Belum punya akun? Daftar</a>
                                        </div>
                                    </div>
                                </li>
                            </ul>
                        <?php endif; ?>
                    </li>

                </ul>
            </div>
        </div>
    </nav>

    <a href="https://wa.me/6281223830598?text=Halo%20Styrk%20Industries%2C%20saya%20mau%20bertanya..." class="floating-wa" target="_blank">
        <i class="bi bi-whatsapp"></i>
    </a>
</body>

</html>