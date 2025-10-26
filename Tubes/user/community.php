<?php
include 'header.php'; // Pastikan header.php sudah punya koneksi $conn

// Pastikan user sudah login untuk bisa akses forum
if (!isset($_SESSION['kd_cs'])) {
    echo "<script>alert('Anda harus login untuk mengakses forum.'); window.location.href='produk.php';</script>";
    exit();
}

// Ambil daftar threads dari database, gabungkan dengan nama pembuatnya
$query_threads = "
    SELECT t.thread_id, t.title, t.created_at, c.nama as author_name,
           (SELECT COUNT(*) FROM posts p WHERE p.thread_id = t.thread_id) as reply_count,
           (SELECT MAX(p.created_at) FROM posts p WHERE p.thread_id = t.thread_id) as last_reply_time
    FROM threads t
    JOIN customer c ON t.customer_id = c.customer_id
    ORDER BY COALESCE(last_reply_time, t.created_at) DESC -- Urutkan berdasarkan aktivitas terakhir
";
$result_threads = mysqli_query($conn, $query_threads);

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forum Komunitas - Styrk Industries</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.5.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
        }

        .forum-container {
            max-width: 900px;
            margin: 30px auto;
            background: #fff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .thread-item {
            border-bottom: 1px solid #eee;
            padding: 15px 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .thread-item:last-child {
            border-bottom: none;
        }

        .thread-title a {
            text-decoration: none;
            color: #333;
            font-weight: 600;
            font-size: 1.1rem;
        }

        .thread-title a:hover {
            color: var(--gold);
        }

        .thread-meta {
            font-size: 0.85rem;
            color: #6c757d;
        }

        .thread-stats {
            text-align: right;
            min-width: 100px;
        }

        .replies {
            font-size: 0.9rem;
        }

        .last-reply {
            font-size: 0.8rem;
            color: #6c757d;
        }

        .btn-new-thread {
            background-color: var(--gold);
            border: none;
            color: var(--dark-gray);
        }

        .btn-new-thread:hover {
            background-color: #c49a2a;
            color: var(--black);
           
        }
    </style>
</head>

<body>

    <div class="forum-container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="bi bi-chat-dots-fill me-2"></i> Forum Komunitas Keyboard</h2>
            <a href="create_thread_form.php" class="btn btn-new-thread">
                <i class="bi bi-plus-lg me-1"></i> Buat Topik Baru
            </a>
        </div>

        <?php if ($result_threads && mysqli_num_rows($result_threads) > 0): ?>
            <div class="thread-list">
                <?php while ($thread = mysqli_fetch_assoc($result_threads)): ?>
                    <div class="thread-item">
                        <div class="thread-info">
                            <h5 class="thread-title mb-1">
                                <a href="thread.php?id=<?= $thread['thread_id']; ?>">
                                    <?= htmlspecialchars($thread['title']); ?>
                                </a>
                            </h5>
                            <p class="thread-meta mb-0">
                                Dimulai oleh <?= htmlspecialchars($thread['author_name']); ?> • <?= date('d M Y, H:i', strtotime($thread['created_at'])); ?>
                            </p>
                        </div>
                        <div class="thread-stats">
                            <span class="replies d-block"><?= $thread['reply_count']; ?> Replies</span>
                            <?php if ($thread['last_reply_time']): ?>
                                <span class="last-reply">Last: <?= date('d M Y, H:i', strtotime($thread['last_reply_time'])); ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <p class="text-center text-muted">Belum ada topik diskusi. Jadilah yang pertama!</p>
        <?php endif; ?>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>