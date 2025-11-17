<?php
// add_content_to_cart.php
session_start();
include 'koneksi.php';

if (!isset($_SESSION['kd_cs'])) {
    // harus login dulu
    header('Location: user/login.php');
    exit;
}

$article_id = isset($_GET['article_id']) ? (int)$_GET['article_id'] : 0;
$qty        = isset($_GET['qty']) ? (int)$_GET['qty'] : 1;
if ($qty < 1) $qty = 1;

$stmt = $conn->prepare("
    SELECT article_id, title, product_price, image_url
    FROM community_articles
    WHERE article_id = ? AND is_published = 1
    LIMIT 1
");
$stmt->bind_param('i', $article_id);
$stmt->execute();
$res = $stmt->get_result();
$article = $res->fetch_assoc();
$stmt->close();

if (!$article) {
    header('Location: community.php');
    exit;
}

// SIMPAN DI SESSION KERANJANG KHUSUS KONTEN
if (!isset($_SESSION['content_cart'])) {
    $_SESSION['content_cart'] = [];
}

if (isset($_SESSION['content_cart'][$article_id])) {
    $_SESSION['content_cart'][$article_id]['qty'] += $qty;
} else {
    $_SESSION['content_cart'][$article_id] = [
        'article_id' => $article['article_id'],
        'name'       => $article['title'],
        'price'      => (int)$article['product_price'],
        'image'      => $article['image_url'],
        'qty'        => $qty,
    ];
}

// langsung ke cart
header('Location: cart.php');
exit;
