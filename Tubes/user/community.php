<?php
include 'header.php';

// Pastikan user sudah login
if (!isset($_SESSION['kd_cs'])) {
    echo "<script>alert('Anda harus login untuk mengakses forum.'); window.location.href='produk.php';</script>";
    exit();
}
$customer_id = (int)$_SESSION['kd_cs'];

// ================== THREAD (forum) ==================
$query_threads = "
    SELECT t.thread_id, t.title, t.created_at, c.nama as author_name,
           (SELECT COUNT(*) FROM posts p WHERE p.thread_id = t.thread_id) as reply_count,
           (SELECT MAX(p.created_at) FROM posts p WHERE p.thread_id = t.thread_id) as last_reply_time
    FROM threads t
    JOIN customer c ON t.customer_id = c.customer_id
    ORDER BY COALESCE(last_reply_time, t.created_at) DESC
";
$result_threads = mysqli_query($conn, $query_threads);

// ================== FEATURED PRODUCT DARI KONTEN ==================
$featured_article = null;
$featured_sql = "
    SELECT article_id, title, content, image_url, product_price
    FROM community_articles
    WHERE is_published = 1
    ORDER BY created_at DESC
    LIMIT 1
";
$featured_res = mysqli_query($conn, $featured_sql);
if ($featured_res && mysqli_num_rows($featured_res) > 0) {
    $featured_article = mysqli_fetch_assoc($featured_res);
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Komunitas - Styrk Industries</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.5.0/font/bootstrap-icons.css" rel="stylesheet">

    <style>
        :root {
            --gold: #ffdc73;
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
            transform: translateY(-1px);
        }

        /* ---------- Featured Product dari Konten ---------- */
        .featured-card {
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 8px 22px rgba(0, 0, 0, 0.08);
            margin-bottom: 24px;
            border: 1px solid #f3e1a0;
        }

        .featured-img-wrapper {
            background: #000;
            display: flex;
            align-items: center;
            justify-content: center;
            height: 260px;
            overflow: hidden;
        }

        .featured-img-wrapper img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .featured-body {
            background: #fffef7;
        }

        .featured-desc {
            font-size: 0.9rem;
            color: #444;
            display: -webkit-box;
            -webkit-line-clamp: 7;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        /* ---------- Thread List ---------- */
        .thread-list {
            margin-top: 10px;
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

        .thread-title a {
            text-decoration: none;
            color: #212529;
            font-weight: 600;
            font-size: 1.05rem;
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
                <i class="bi bi-chat-dots-fill me-2"></i> Komunitas
            </h2>
            <div>
                <a href="create_thread_form.php" class="btn btn-new-thread">
                    <i class="bi bi-plus-lg me-1"></i> Buat Topik
                </a>
            </div>
        </div>

        <!-- ============ FEATURED PRODUCT (NO ARTICLE LIST) ============ -->
        <?php if ($featured_article && (int)$featured_article['product_price'] > 0): ?>
            <div class="featured-card">
                <div class="row g-0">
                    <div class="col-md-5 featured-img-wrapper">
                        <img
                            src="<?= htmlspecialchars($featured_article['image_url'] ?: 'https://i.postimg.cc/855ZSty7/no-bg.png'); ?>"
                            alt="<?= htmlspecialchars($featured_article['title']); ?>">
                    </div>

                    <div class="col-md-7 featured-body p-4">
                        <h5 class="mb-2"><?= htmlspecialchars($featured_article['title']); ?></h5>

                        <p class="mb-2 text-muted" style="font-size: 0.85rem;">
                            Limited / Community Exclusive Product
                        </p>

                        <p class="featured-desc mb-3">
                            <?= nl2br(htmlspecialchars($featured_article['content'])); ?>
                        </p>

                        <p class="fw-semibold mb-3">
                            Harga: Rp <?= number_format($featured_article['product_price'], 0, ',', '.'); ?>
                        </p>

                        <a href="add_content_to_cart.php?article_id=<?= $featured_article['article_id']; ?>&qty=1"
                           class="btn btn-warning fw-semibold">
                           <i class="bi bi-cart-plus me-1"></i> Add to Cart
                        </a>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- ================= THREAD AREA ================= -->
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
                                Dimulai oleh <?= htmlspecialchars($thread['author_name']); ?> •
                                <?= date('d M Y, H:i', strtotime($thread['created_at'])); ?>
                            </p>
                        </div>

                        <div class="thread-stats">
                            <span class="d-block"><?= $thread['reply_count']; ?> Balasan</span>
                            <?php if ($thread['last_reply_time']): ?>
                                <span class="text-muted" style="font-size: .8rem;">
                                    Terakhir: <?= date('d M Y, H:i', strtotime($thread['last_reply_time'])); ?>
                                </span>
                            <?php endif; ?>
                        </div>

                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <p class="text-center text-muted mt-3">Belum ada topik diskusi. Jadilah yang pertama!</p>
        <?php endif; ?>

    </div>

</body>
</html>
