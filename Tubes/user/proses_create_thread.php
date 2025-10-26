<?php
session_start();
include 'koneksi.php'; // Pastikan path ini benar

// 1. Pastikan user sudah login
if (!isset($_SESSION['kd_cs'])) {
    die("Anda harus login untuk membuat topik baru.");
}

// 2. Pastikan requestnya POST (dari form)
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: community.php'); // Kembali ke forum jika bukan POST
    exit();
}

// 3. Ambil data dari form dan validasi sederhana
$customer_id = $_SESSION['kd_cs'];
$title = trim($_POST['title'] ?? '');
$content = trim($_POST['content'] ?? '');

if (empty($title) || empty($content)) {
    // Sebaiknya berikan pesan error yang lebih baik di session dan redirect
    die("Judul dan isi pesan tidak boleh kosong.");
}

// 4. Mulai transaksi database (opsional tapi bagus untuk konsistensi)
$conn->begin_transaction();

try {
    // 5. Simpan data thread baru ke tabel 'threads'
    $sql_thread = "INSERT INTO threads (customer_id, title) VALUES (?, ?)";
    $stmt_thread = $conn->prepare($sql_thread);
    if (!$stmt_thread) throw new Exception("Gagal menyiapkan statement thread: " . $conn->error);

    $stmt_thread->bind_param("is", $customer_id, $title);
    if (!$stmt_thread->execute()) throw new Exception("Gagal menyimpan thread: " . $stmt_thread->error);

    // Ambil ID thread yang baru saja dibuat
    $new_thread_id = $conn->insert_id;
    $stmt_thread->close();

    // 6. Simpan pesan pertama ke tabel 'posts'
    $sql_post = "INSERT INTO posts (thread_id, customer_id, content) VALUES (?, ?, ?)";
    $stmt_post = $conn->prepare($sql_post);
    if (!$stmt_post) throw new Exception("Gagal menyiapkan statement post: " . $conn->error);

    $stmt_post->bind_param("iis", $new_thread_id, $customer_id, $content);
    if (!$stmt_post->execute()) throw new Exception("Gagal menyimpan post pertama: " . $stmt_post->error);

    $stmt_post->close();

    // 7. Jika semua berhasil, commit transaksi
    $conn->commit();

    // 8. Redirect ke halaman thread yang baru dibuat
    header('Location: thread.php?id=' . $new_thread_id);
    exit();
} catch (Exception $e) {
    // 9. Jika ada error, batalkan semua perubahan (rollback)
    $conn->rollback();
    // Tampilkan pesan error (sebaiknya dicatat ke log di aplikasi nyata)
    die("Terjadi kesalahan: " . $e->getMessage());
}

$conn->close();
