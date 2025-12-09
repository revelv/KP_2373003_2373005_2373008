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
    SELECT 
        p.post_id, 
        p.content, 
        p.created_at as post_created, 
        c.nama as author_name,
        c.profile_image
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
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($thread['title']); ?> - Forum Komunitas</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.5.0/font/bootstrap-icons.css" rel="stylesheet">

    <style>
        :root {
            --gold: #ffdc73;
            --dark-gray: #1f1f1f;
        }

        body {
            background-color: #f8f9fa; /* Adjusted var(--bg-page) to a standard color for standalone testing */
            font-family: system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
        }

        .thread-container {
            max-width: 900px;
            margin: 30px auto 60px auto;
        }

        /* === CARD THREAD + POSTS === */
        .thread-card {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.05);
            border: 1px solid #e5e7eb; /* Adjusted var(--border-soft) */
            overflow: hidden;
        }

        .thread-header {
            padding: 20px 24px;
            border-bottom: 1px solid #e5e7eb;
            background-color: #fff;
        }

        .thread-title {
            font-size: 1.4rem;
            font-weight: 600;
            margin: 0 0 8px 0;
            color: #111827;
            line-height: 1.3;
        }

        .thread-meta {
            font-size: 0.9rem;
            color: #6b7280;
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 6px 16px;
        }

        .thread-meta .dot {
            width: 4px;
            height: 4px;
            border-radius: 999px;
            background-color: #9ca3af;
            display: inline-block;
        }

        .back-btn-small {
            font-size: 0.8rem;
            line-height: 1.2rem;
            padding: 4px 10px;
        }

        /* === LIST POST === */
        .post-list {
            margin: 0;
            padding: 0;
        }

        .post-item {
            padding: 12px 20px;
            border-bottom: 1px solid #e5e7eb;
            background-color: #fff;
        }

        .post-item:last-child {
            border-bottom: none;
        }

        .post-wrapper {
            display: flex;
            align-items: flex-start;
            gap: 12px;
        }

        /* avatar bulat */
        .post-avatar {
            width: 42px;
            height: 42px;
            border-radius: 999px;
            background-color: #111827;
            background-image: radial-gradient(circle at 20% 20%, rgba(255,255,255,.2) 0%, rgba(0,0,0,0) 60%);
            color: #fff;
            font-size: 0.8rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            justify-content: center;
            text-transform: uppercase;
            flex-shrink: 0;
            overflow: hidden;
        }

        .post-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: inherit;
            display: block;
        }

        .post-body {
            flex: 1;
            min-width: 0;
        }

        .post-headline {
            display: flex;
            flex-wrap: wrap;
            align-items: baseline;
            gap: 8px 12px;
        }

        .post-author {
            font-weight: 600;
            color: #111827;
            font-size: 0.95rem;
        }

        .post-time {
            font-size: 0.8rem;
            color: #9ca3af;
        }

        .post-content {
            margin-top: 4px;
            line-height: 1.5;
        }
        
        /* FIX GAMBAR BIAR GAK OVERFLOW */
        .post-content img {
            max-width: 100%;
            height: auto;
            border-radius: 8px;
            margin-top: 10px;
            margin-bottom: 10px;
        }

        /* === CARD REPLY FORM === */
        .reply-card {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.05);
            border: 1px solid #e5e7eb;
            margin-top: 24px;
            padding: 24px;
        }

        .reply-title {
            font-size: 1rem;
            font-weight: 600;
            color: #111827;
            margin-bottom: 12px;
        }

        textarea.form-control {
            border-radius: 8px;
        }

        .btn-submit-reply {
            background-color: var(--gold) !important;
            border: none !important;
            color: var(--dark-gray) !important;
            font-weight: 600;
            padding: 10px 16px !important;
            border-radius: 8px !important;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            margin-top: 8px;
            cursor: pointer !important;
        }

        .btn-submit-reply:hover {
            filter: brightness(0.95);
        }
    </style>
</head>

<body>

    <div class="thread-container">

        <div class="thread-card">

            <div class="thread-header">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-start align-items-stretch">
                    <div class="me-md-3">
                        <h1 class="thread-title"><?= htmlspecialchars($thread['title']); ?></h1>

                        <div class="thread-meta">
                            <span>Dimulai oleh <strong><?= htmlspecialchars($thread['author_name']); ?></strong></span>
                            <span class="dot"></span>
                            <span><?= date('d M Y, H:i', strtotime($thread['thread_created'])); ?></span>
                        </div>
                    </div>

                    <div class="mt-3 mt-md-0">
                        <a href="community.php" class="btn btn-outline-secondary back-btn-small">
                            ← Kembali ke Forum
                        </a>
                    </div>
                </div>
            </div>

            <div class="post-list">
                <?php if ($result_posts && mysqli_num_rows($result_posts) > 0): ?>
                    <?php while ($post = $result_posts->fetch_assoc()): ?>
                        <?php
                        // ambil inisial buat avatar kalau nggak ada foto
                        $initials    = mb_substr($post['author_name'], 0, 1, 'UTF-8');
                        $profileImg  = $post['profile_image'] ?? '';
                        ?>
                        <div class="post-item">
                            <div class="post-wrapper">
                                <div class="post-avatar">
                                    <?php if (!empty($profileImg)): ?>
                                        <img src="<?= htmlspecialchars($profileImg); ?>" alt="Foto profil <?= htmlspecialchars($post['author_name']); ?>">
                                    <?php else: ?>
                                        <?= htmlspecialchars(strtoupper($initials)); ?>
                                    <?php endif; ?>
                                </div>

                                <div class="post-body">
                                    <div class="post-headline">
                                        <span class="post-author"><?= htmlspecialchars($post['author_name']); ?></span>
                                        <span class="post-time"><?= date('d M Y, H:i', strtotime($post['post_created'])); ?></span>
                                    </div>

                                    <div class="post-content">
                                        <?php 
                                            // Jangan gunakan htmlspecialchars() di sini karena konten berisi HTML (img tag, p tag, dll) dari Summernote
                                            // Gunakan strip_tags() jika ingin membatasi tag tertentu, tapi untuk menampilkan gambar, biarkan raw.
                                            echo $post['content']; 
                                        ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="post-item text-center text-muted">
                        Belum ada balasan.
                    </div>
                <?php endif; ?>
                <?php $stmt_posts->close(); ?>
            </div>

        </div>

        <div class="reply-card">
            <div class="reply-title">Tambahkan Balasan</div>

            <form action="proses_add_reply.php" method="POST">
                <input type="hidden" name="thread_id" value="<?= $thread['thread_id']; ?>">

                <div class="mb-3">
                    <textarea class="form-control" name="content" rows="5" required placeholder="Tulis balasan Anda..."></textarea>
                </div>

                <button type="submit" class="btn btn-submit-reply">
                    <i class="bi bi-send-fill"></i>
                    <span>Kirim Balasan</span>
                </button>
            </form>
        </div>

    </div><script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>