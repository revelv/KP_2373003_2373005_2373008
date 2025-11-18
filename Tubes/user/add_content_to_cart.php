<?php
// add_content_to_cart.php
session_start();
require_once 'koneksi.php';

// ================= CEK LOGIN =================
if (!isset($_SESSION['kd_cs'])) {
    $_SESSION['message'] = 'Anda harus login terlebih dahulu.';
    header('Location: login.php');
    exit();
}

$customer_id = (int)$_SESSION['kd_cs'];
$article_id  = isset($_GET['article_id']) ? (int)$_GET['article_id'] : 0;
$qty         = isset($_GET['qty']) ? (int)$_GET['qty'] : 1;
if ($qty < 1) $qty = 1;

if ($article_id <= 0) {
    $_SESSION['message'] = 'Artikel tidak valid.';
    header('Location: community.php');
    exit();
}

// ================= AMBIL DATA ARTIKEL =================
$stmt = $conn->prepare("
    SELECT title, content, image_url, product_price
    FROM community_articles
    WHERE article_id = ? AND is_published = 1
    LIMIT 1
");
$stmt->bind_param('i', $article_id);
$stmt->execute();
$res     = $stmt->get_result();
$article = $res->fetch_assoc();
$stmt->close();

if (!$article) {
    $_SESSION['message'] = 'Artikel atau produk konten tidak ditemukan.';
    header('Location: community.php');
    exit();
}

$title     = $article['title'];
$desc      = $article['content'];
$image_url = $article['image_url'] ?: 'https://i.postimg.cc/855ZSty7/no-bg.png';
$price     = (float)$article['product_price'];

// ================= BATASIN PANJANG NAMA / DESKRIPSI =================
// SESUAIKAN dgn panjang kolom di tabel products
$maxNamaLen = 80;     // misal kolom nama_produk VARCHAR(80)
$maxDescLen = 2000;   // TEXT biasanya aman, ini cuma jaga-jaga

$shortTitle = mb_substr($title, 0, $maxNamaLen);
$shortDesc  = mb_substr($desc, 0, $maxDescLen);

// ================= BUAT / PASTIKAN PRODUK BAYANGAN =================
// product_id khusus untuk artikel ini, misal: COMM-1, COMM-2, dst
$product_id = 'COMM-' . $article_id;

// Cek apakah produk bayangan ini sudah ada di tabel products
$stmt = $conn->prepare("SELECT product_id FROM products WHERE product_id = ? LIMIT 1");
$stmt->bind_param('s', $product_id);
$stmt->execute();
$resProd = $stmt->get_result();
$exists  = $resProd->fetch_assoc();
$stmt->close();

if (!$exists) {
    // Sesuaikan ID kategori default ini dengan kategori "keyboard" di sistemmu
    $defaultCategoryId = 2;   // ganti kalau ID kategorimu beda
    $stokDefault       = 1;
    $weightDefault     = 1000; // gram; SESUAIKAN dengan tipe kolom weight (INT/DECIMAL)

    $stmt = $conn->prepare("
        INSERT INTO products
            (product_id,
             category_id,
             nama_produk,
             deskripsi_produk,
             harga,
             stok,
             link_gambar,
             weight,
             status_jual)
        VALUES
            (?, ?, ?, ?, ?, ?, ?, ?, 'dijual')
    ");

    // 'sissdisi' = s, i, s, s, d, i, s, i
    $stmt->bind_param(
        'sissdisi',
        $product_id,         // s
        $defaultCategoryId,  // i
        $shortTitle,         // s
        $shortDesc,          // s
        $price,              // d
        $stokDefault,        // i
        $image_url,          // s
        $weightDefault       // i
    );

    if (!$stmt->execute()) {
        $_SESSION['message'] = 'Gagal membuat produk konten: ' . $stmt->error;
        $stmt->close();
        header('Location: community.php');
        exit();
    }
    $stmt->close();
}

// ================= MASUKKAN KE CART =================
// Cek apakah produk ini sudah ada di keranjang user
$stmt = $conn->prepare("
    SELECT cart_id, jumlah_barang
    FROM carts
    WHERE customer_id = ? AND product_id = ?
    LIMIT 1
");
$stmt->bind_param('is', $customer_id, $product_id);
$stmt->execute();
$resCart  = $stmt->get_result();
$existing = $resCart->fetch_assoc();
$stmt->close();

if ($existing) {
    // Sudah ada → untuk produk komunitas, paksa max 1 pcs
    $newQty = (int)$existing['jumlah_barang'] + $qty;
    if ($newQty > 1) {
        $newQty = 1;
    }

    $stmt = $conn->prepare("
        UPDATE carts
        SET jumlah_barang = ?
        WHERE cart_id = ?
    ");
    $stmt->bind_param('ii', $newQty, $existing['cart_id']);
    $stmt->execute();
    $stmt->close();
} else {
    // Belum ada → insert baru
    $stmt = $conn->prepare("
        INSERT INTO carts (customer_id, product_id, jumlah_barang)
        VALUES (?, ?, ?)
    ");
    $stmt->bind_param('isi', $customer_id, $product_id, $qty);
    $stmt->execute();
    $stmt->close();
}

$_SESSION['message'] = 'Produk berhasil ditambahkan ke keranjang.';
header('Location: cart.php');
exit();
