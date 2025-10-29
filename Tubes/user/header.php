<?php
// ====== SESSION & DB ======
if (session_status() !== PHP_SESSION_ACTIVE) {
    // Opsional: cookie path biar sesi kebaca di semua page
    session_set_cookie_params(['path' => '/', 'httponly' => true, 'samesite' => 'Lax']);
    session_start();
}
require_once __DIR__ . '/koneksi.php';

// ====== BASE_URL (sesuaikan kalau perlu) ======
if (!defined('BASE_URL')) {
    // Contoh kalau semua file ada di folder yang sama:
    define('BASE_URL', './');
    // Kalau proyeknya di /Tubes/user/, pakai:
    // define('BASE_URL', '/Tubes/user/');
}

// ====== HANDLE LOGIN ======
if (isset($_POST['login'])) {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    // Prepared query by email
    $stmt = $conn->prepare("SELECT customer_id, email, nama, password FROM customer WHERE email = ?");
    if (!$stmt) {
        die("Query error: " . $conn->error);
    }
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res && $res->num_rows > 0) {
        $user = $res->fetch_assoc();
        if (password_verify($password, $user['password'])) {
            // Regenerate session id biar stabil & aman
            session_regenerate_id(true);

            $_SESSION['kd_cs'] = $user['customer_id'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['nama']  = $user['nama'];

            // Update last_login (prepared)
            $customer_id_login = (int)$user['customer_id'];
            $upd = $conn->prepare("UPDATE customer SET last_login = NOW() WHERE customer_id = ?");
            if ($upd) {
                $upd->bind_param("i", $customer_id_login);
                $upd->execute();
                $upd->close();
            }

            header("Location: " . BASE_URL . "produk.php");
            exit();
        } else {
            $login_error = "Password salah!";
        }
    } else {
        $login_error = "Akun tidak ditemukan!";
    }
    $stmt->close();
}

// ====== HANDLE LOGOUT ======
if (isset($_GET['logout'])) {
    // Optional: clear session cookie
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params["path"], $params["domain"], $params["secure"], $params["httponly"]);
    }
    session_destroy();
    header("Location: " . BASE_URL . "produk.php");
    exit();
}

// ====== CART COUNT (prepared) ======
$cart_count = 0;
if (isset($_SESSION['kd_cs'])) {
    $kode_cs = (int)$_SESSION['kd_cs'];
    $stmtC = $conn->prepare("SELECT COUNT(cart_id) AS cnt FROM carts WHERE customer_id = ?");
    if ($stmtC) {
        $stmtC->bind_param("i", $kode_cs);
        $stmtC->execute();
        $stmtC->bind_result($cnt);
        if ($stmtC->fetch()) $cart_count = (int)$cnt;
        $stmtC->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Styrk Industries</title>

    <link rel="stylesheet" href="<?= BASE_URL ?>css/header.css">

    <!-- BOOTSTRAP CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- BOOTSTRAP ICONS -->
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
            <a class="navbar-brand" href="<?= BASE_URL ?>HOME/index.php">
                <img src="https://i.postimg.cc/855ZSty7/no-bg.png" alt="Styrk Industries">
            </a>

            <button class="navbar-toggler" type="button"
                data-bs-toggle="collapse"
                data-bs-target="#navbarContent"
                aria-controls="navbarContent"
                aria-expanded="false"
                aria-label="Toggle navigation">
                <i class="bi bi-list" style="color: var(--primary-yellow); font-size: 2rem;"></i>
            </button>

            <div class="collapse navbar-collapse" id="navbarContent">
                <ul class="navbar-nav ms-auto mb-2 mb-lg-0" style="gap: 2rem;">

                    <li class="nav-item"><a class="nav-link" href="<?= BASE_URL ?>HOME/index.php">Home</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= BASE_URL ?>produk.php">Products</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= BASE_URL ?>community.php">Community</a></li>

                    <li class="nav-item">
                        <a class="nav-link" href="<?= BASE_URL ?>cart.php">
                            <i class="bi-cart-fill me-2"></i>
                            Carts [<?= (int)$cart_count ?>]
                        </a>
                    </li>

                    <li class="nav-item dropdown">
                        <?php if (isset($_SESSION['kd_cs'])): ?>
                            <!-- USER LOGGED IN -->
                            <a class="nav-link dropdown-toggle"
                                href="#"
                                id="navbarDropdown"
                                role="button"
                                data-bs-toggle="dropdown"
                                data-bs-auto-close="outside"
                                aria-expanded="false">
                                <i class="bi bi-person-fill me-2"></i>
                                <?= htmlspecialchars($_SESSION['nama'] ?? 'User'); ?>
                            </a>

                            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                                <li><a class="dropdown-item" href="<?= BASE_URL ?>profile.php"><i class="bi bi-person-circle me-2"></i>Profil Saya</a></li>
                                <li><a class="dropdown-item" href="<?= BASE_URL ?>riwayat_belanja.php"><i class="bi bi-receipt me-2"></i>Riwayat Belanja</a></li>
                                <li>
                                    <hr class="dropdown-divider">
                                </li>
                                <li><a class="dropdown-item" href="?logout=1"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
                            </ul>
                        <?php else: ?>
                            <!-- LOGIN FORM DROPDOWN -->
                            <a class="nav-link dropdown-toggle"
                                href="#"
                                id="loginDropdown"
                                role="button"
                                data-bs-toggle="dropdown"
                                data-bs-auto-close="outside"
                                aria-expanded="false">
                                <i class="bi bi-box-arrow-in-right me-2"></i>
                                Login
                            </a>

                            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="loginDropdown">
                                <li>
                                    <div class="login-form">
                                        <form method="post" action="">
                                            <div class="mb-3">
                                                <label for="username" class="form-label">Email</label>
                                                <input type="email" class="form-control" id="username" name="username" required>
                                            </div>

                                            <div class="mb-3">
                                                <label for="password" class="form-label">Password</label>
                                                <input type="password" class="form-control" id="password" name="password" required>
                                            </div>

                                            <?php if (isset($login_error)): ?>
                                                <div class="login-error">
                                                    <?= htmlspecialchars($login_error); ?>
                                                    <a href="<?= BASE_URL ?>forgot_password.php">forgot password?</a>
                                                </div>
                                            <?php endif; ?>

                                            <button type="submit" name="login" class="btn btn-warning w-100">
                                                <i class="bi bi-box-arrow-in-right me-2"></i>
                                                Login
                                            </button>
                                        </form>

                                        <div class="text-center mt-3">
                                            <a href="<?= BASE_URL ?>Login/registrasi.php"
                                                style="color: var(--black); text-decoration: none; font-size: 1.1rem;">
                                                <i class="bi bi-person-plus me-2"></i>
                                                Belum punya akun? Daftar
                                            </a>
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

    <!-- WA FLOATING BUTTON -->
    <a href="https://wa.me/6281223830598?text=Halo%20Styrk%20Industries%2C%20saya%20mau%20bertanya..."
        class="floating-wa" target="_blank">
        <i class="bi bi-whatsapp"></i>
    </a>
</body>