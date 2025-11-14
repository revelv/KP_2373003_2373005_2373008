<?php
session_start();
include 'koneksi.php';

if (!isset($_SESSION['kd_cs'])) {
    die("Anda harus login.");
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die("Metode tidak diizinkan.");
}

$customer_id = $_SESSION['kd_cs'];
$title = trim($_POST['title']);
$description = trim($_POST['description']);
$image_url = trim($_POST['image_url']);
$start_price = (float)($_POST['start_price']);
$end_time = $_POST['end_time'];

// Validasi
if (empty($title) || empty($description) || empty($image_url) || $start_price <= 0 || empty($end_time)) {
    die("Semua field wajib diisi.");
}
if (strtotime($end_time) <= time()) {
    die("Waktu berakhir lelang harus di masa depan.");
}

$stmt = $conn->prepare("INSERT INTO auctions (customer_id, title, description, image_url, start_price, current_bid, end_time, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'active')");
if (!$stmt) {
    die("Prepare failed: " . $conn->error);
}

$stmt->bind_param("isssdds", $customer_id, $title, $description, $image_url, $start_price, $start_price, $end_time);

if ($stmt->execute()) {
    $new_auction_id = $conn->insert_id;
    header('Location: auction_detail.php?id=' . $new_auction_id);
    exit();
} else {
    die("Gagal membuat lelang: " . $stmt->error);
}
$stmt->close();
?>