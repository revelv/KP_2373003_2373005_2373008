<?php
session_start();
include 'koneksi.php'; // Pastikan path ini benar

// 1. Pastikan user sudah login
if (!isset($_SESSION['kd_cs'])) {
    die("Anda harus login untuk membalas topik.");
}

// 2. Pastikan requestnya POST (dari form)
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: community.php'); // Kembali ke forum jika bukan POST
    exit();
}

// 3. Ambil data dari form dan validasi
$customer_id = $_SESSION['kd_cs'];
$thread_id = isset($_POST['thread_id']) ? (int)$_POST['thread_id'] : 0;
$content = trim($_POST['content'] ?? '');

if ($thread_id <= 0) {
    die("ID Topik tidak valid.");
}

if (empty($content)) {
    // Sebaiknya berikan pesan error via session dan redirect kembali ke thread
    die("Isi balasan tidak boleh kosong.");
}

// 4. Cek apakah thread-nya benar-benar ada (opsional tapi bagus)
$check_thread = $conn->prepare("SELECT thread_id FROM threads WHERE thread_id = ?");
if ($check_thread) {
    $check_thread->bind_param("i", $thread_id);
    $check_thread->execute();
    $check_thread->store_result();
    if ($check_thread->num_rows === 0) {
        die("Topik yang Anda balas tidak ditemukan.");
    }
    $check_thread->close();
}

// 5. Simpan balasan baru ke tabel 'posts'
$sql_post = "INSERT INTO posts (thread_id, customer_id, content) VALUES (?, ?, ?)";
$stmt_post = $conn->prepare($sql_post);

if ($stmt_post) {
    $stmt_post->bind_param("iis", $thread_id, $customer_id, $content);

    if ($stmt_post->execute()) {
        // Berhasil disimpan, redirect kembali ke halaman thread
        header('Location: thread.php?id=' . $thread_id);
        exit();
    } else {
        // Gagal menyimpan
        die("Terjadi kesalahan saat menyimpan balasan: " . $stmt_post->error);
    }
    $stmt_post->close();
} else {
    die("Gagal menyiapkan statement post: " . $conn->error);
}

$conn->close();
?>