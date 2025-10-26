<?php
include 'header.php';

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
    ORDER BY COALESCE(last_reply_time, t.created_at) DESC
";
$result_threads = mysqli_query($conn, $query_threads);
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forum Komunitas - Styrk Industries</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.5.0/font/bootstrap-icons.css" rel="stylesheet">

    <style>
        :root {
            --gold: #ffdc73;
            /* sebelumnya #d4af37 */
            --dark-gray: #1f1f1f;
        }

        body {
            background-color: #f8f9fa;
        }

        .forum-container {
            max-width: 900px;
            margin: 40px auto;
            background: #fff;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
        }

        .btn-new-thread {
            background-color: var(--gold);
            border: none;
            color: var(--dark-gray);
            font-weight: 600;
            transition: all 0.2s ease-in-out;
        }

        .btn-new-thread:hover {
            background-color: #ffe58a;
            /* sedikit lebih terang saat hover */
            transform: translateY(-1px);
        }

        /* === THREAD LIST === */
        .thread-list {
            margin-top: 20px;
        }

        .thread-item {
            background: #fff;
            border: 1px solid #eee;
            border-radius: 8px;
            padding: 18px 20px;
            margin-bottom: 14px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: all 0.25s ease-in-out;
            cursor: pointer;
        }

        .thread-item:hover {
            border-color: var(--gold);
            box-shadow: 0 6px 18px rgba(212, 175, 55, 0.15);
            transform: translateY(-2px);
            background-color: #fffbea;
        }

        .thread-info {
            flex: 1;
        }

        .thread-title {
            margin-bottom: 6px;
        }

        .thread-title a {
            text-decoration: none;
            color: #212529;
            font-weight: 600;
            font-size: 1.05rem;
            transition: color 0.2s ease;
        }

        .thread-item:hover .thread-title a {
            color: var(--gold);
        }

        .thread-meta {
            font-size: 0.85rem;
            color: #6c757d;
        }

        .thread-stats {
            text-align: right;
            min-width: 120px;
        }

        .replies {
            font-size: 0.9rem;
            font-weight: 500;
        }

        .last-reply {
            font-size: 0.8rem;
            color: #6c757d;
        }

        @media (max-width: 600px) {
            .thread-item {
                flex-direction: column;
                align-items: flex-start;
                gap: 6px;
            }

            .thread-stats {
                text-align: left;
            }
        }
    </style>
</head>

<body>

    <div class="forum-container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="fw-semibold mb-0">
                <i class="bi bi-chat-dots-fill me-2"></i> Forum Komunitas Keyboard
            </h2>
            <a href="create_thread_form.php" class="btn btn-new-thread">
                <i class="bi bi-plus-lg me-1"></i> Buat Topik Baru
            </a>
        </div>

        <?php if ($result_threads && mysqli_num_rows($result_threads) > 0): ?>
            <div class="thread-list">
                <?php while ($thread = mysqli_fetch_assoc($result_threads)): ?>
                    <div class="thread-item" onclick="window.location='thread.php?id=<?= $thread['thread_id']; ?>'">
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