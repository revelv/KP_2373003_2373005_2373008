<?php
include 'header.php'; // Pastikan header.php sudah punya koneksi $conn

// Pastikan user sudah login
if (!isset($_SESSION['kd_cs'])) {
    echo "<script>alert('Anda harus login untuk melihat topik forum.'); window.location.href='produk.php';</script>";
    exit();
}

// Ambil ID thread dari URL dan validasi
$thread_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($thread_id <= 0) {
    die("ID Topik tidak valid.");
}

// Ambil detail thread (judul dan pembuat)
$query_thread = "
    SELECT t.thread_id, t.title, t.created_at as thread_created, c.nama as author_name
    FROM threads t
    JOIN customer c ON t.customer_id = c.customer_id
    WHERE t.thread_id = ?
";
$stmt_thread = $conn->prepare($query_thread);
if (!$stmt_thread) die("Gagal menyiapkan query thread: " . $conn->error);
$stmt_thread->bind_param("i", $thread_id);
$stmt_thread->execute();
$result_thread = $stmt_thread->get_result();

if ($result_thread->num_rows === 0) {
    die("Topik tidak ditemukan.");
}
$thread = $result_thread->fetch_assoc();
$stmt_thread->close();

// Ambil semua post (balasan) untuk thread ini, urutkan dari yang terlama
$query_posts = "
    SELECT p.post_id, p.content, p.created_at as post_created, c.nama as author_name
    FROM posts p
    JOIN customer c ON p.customer_id = c.customer_id
    WHERE p.thread_id = ?
    ORDER BY p.created_at ASC
";
$stmt_posts = $conn->prepare($query_posts);
if (!$stmt_posts) die("Gagal menyiapkan query post: " . $conn->error);
$stmt_posts->bind_param("i", $thread_id);
$stmt_posts->execute();
$result_posts = $stmt_posts->get_result();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($thread['title']); ?> - Forum Komunitas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.5.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; }
        .thread-container { max-width: 900px; margin: 30px auto; }
        .thread-header { background: #fff; padding: 20px; border-radius: 8px 8px 0 0; box-shadow: 0 2px 5px rgba(0,0,0,0.05); border-bottom: 1px solid #eee; }
        .thread-title { font-size: 1.8rem; font-weight: 700; margin-bottom: 5px; }
        .thread-meta { font-size: 0.9rem; color: #6c757d; }
        .post-list { margin-top: 0; padding: 0; }
        .post-item { background: #fff; padding: 20px; border-bottom: 1px solid #eee; box-shadow: 0 1px 3px rgba(0,0,0,0.03); }
        .post-item:last-child { border-bottom: none; border-radius: 0 0 8px 8px; }
        .post-author { font-weight: 600; color: var(--gold); }
        .post-time { font-size: 0.8rem; color: #adb5bd; margin-left: 10px; }
        .post-content { margin-top: 10px; line-height: 1.7; color: #495057; white-space: pre-wrap; /* Agar baris baru tampil */ }
        .reply-form { background: #fff; padding: 30px; border-radius: 8px; margin-top: 30px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .btn-submit-reply { background-color: var(--gold); border: none; color: #fff; }
        .btn-submit-reply:hover { background-color: #c49a2a; }
    </style>
</head>
<body>

<div class="thread-container">
    <div class="thread-header">
        <h1 class="thread-title"><?= htmlspecialchars($thread['title']); ?></h1>
        <p class="thread-meta">
            Dimulai oleh <?= htmlspecialchars($thread['author_name']); ?> • <?= date('d M Y, H:i', strtotime($thread['thread_created'])); ?>
            <a href="community.php" class="ms-3 btn btn-sm btn-outline-secondary">← Kembali ke Forum</a>
        </p>
    </div>

    <div class="post-list">
        <?php if ($result_posts && mysqli_num_rows($result_posts) > 0): ?>
            <?php while ($post = $result_posts->fetch_assoc()): ?>
                <div class="post-item">
                    <div class="post-header">
                        <span class="post-author"><?= htmlspecialchars($post['author_name']); ?></span>
                        <span class="post-time"><?= date('d M Y, H:i', strtotime($post['post_created'])); ?></span>
                    </div>
                    <div class="post-content">
                        <?= nl2br(htmlspecialchars($post['content'])); // nl2br untuk menampilkan baris baru ?>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p class="text-center p-3 text-muted">Belum ada balasan.</p>
        <?php endif; ?>
        <?php $stmt_posts->close(); ?>
    </div>

    <div class="reply-form">
        <h4 class="mb-3">Tambahkan Balasan</h4>
        <form action="proses_add_reply.php" method="POST">
            <input type="hidden" name="thread_id" value="<?= $thread['thread_id']; ?>">
            <div class="mb-3">
                <textarea class="form-control" name="content" rows="5" required placeholder="Tulis balasan Anda..."></textarea>
            </div>
            <button type="submit" class="btn btn-submit-reply">
                <i class="bi bi-send-fill me-1"></i> Kirim Balasan
            </button>
        </form>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>