<?php
include 'header.php';

if (!isset($_SESSION['kd_cs'])) {
    echo "<script>alert('Anda harus login terlebih dahulu.'); window.location.href='produk.php';</script>";
    exit();
}

$customer_id = (int)$_SESSION['kd_cs'];

// --- ambil ID artikel ---
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo "<script>alert('Artikel tidak ditemukan.'); window.location.href='community.php';</script>";
    exit();
}

$article_id = (int)$_GET['id'];

// --- proses submit komentar ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_comment'])) {
    $comment_text = trim($_POST['comment_text'] ?? '');

    if ($comment_text === '') {
        $error_message = 'Komentar tidak boleh kosong.';
    } else {
        $comment_text_safe = mysqli_real_escape_string($conn, $comment_text);

        $sql_insert = "
            INSERT INTO community_article_comments (article_id, customer_id, comment_text)
            VALUES ($article_id, $customer_id, '$comment_text_safe')
        ";
        if (!mysqli_query($conn, $sql_insert)) {
            $error_message = 'Gagal menyimpan komentar. Coba lagi nanti.';
        } else {
            // biar gak double submit pas refresh
            header("Location: community_article_detail.php?id=" . $article_id);
            exit();
        }
    }
}

// --- ambil data artikel ---
$sql_article = "
    SELECT article_id, title, content, image_url, product_price, created_at
    FROM community_articles
    WHERE article_id = $article_id AND is_published = 1
    LIMIT 1
";
$res_article = mysqli_query($conn, $sql_article);

if (!$res_article || mysqli_num_rows($res_article) === 0) {
    echo "<script>alert('Artikel tidak ditemukan.'); window.location.href='community.php';</script>";
    exit();
}

$article = mysqli_fetch_assoc($res_article);

// --- ambil komentar-komentar ---
$sql_comments = "
    SELECT cac.comment_id,
           cac.comment_text,
           cac.created_at,
           c.nama AS customer_name
    FROM community_article_comments cac
    JOIN customer c ON cac.customer_id = c.customer_id
    WHERE cac.article_id = $article_id
    ORDER BY cac.created_at ASC
";
$res_comments = mysqli_query($conn, $sql_comments);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($article['title']); ?> - Diskusi Komunitas</title>
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

        .article-container {
            max-width: 900px;
            margin: 30px auto;
            background: #fff;
            padding: 24px;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.05);
        }

        .article-header {
            border-bottom: 1px solid #eee;
            margin-bottom: 16px;
            padding-bottom: 10px;
        }

        .article-img {
            max-height: 320px;
            overflow: hidden;
            border-radius: 10px;
            margin-bottom: 20px;
        }

        .article-img img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .article-meta {
            font-size: 0.9rem;
            color: #777;
        }

        .article-price {
            font-weight: 600;
            font-size: 1rem;
        }

        .comment-card {
            border-radius: 10px;
            border: 1px solid #eee;
            margin-bottom: 10px;
            padding: 10px 12px;
            background-color: #fff;
        }

        .comment-author {
            font-weight: 600;
            font-size: 0.9rem;
        }

        .comment-date {
            font-size: 0.8rem;
            color: #888;
        }

        .comment-text {
            margin-top: 4px;
            font-size: 0.92rem;
        }

        .comment-form textarea {
            resize: vertical;
        }

        .btn-gold {
            background-color: var(--gold);
            border: none;
            color: var(--dark-gray);
            font-weight: 600;
        }

        .btn-gold:hover {
            background-color: #ffe58a;
        }
    </style>
</head>
<body>

<div class="article-container">

    <div class="d-flex justify-content-between align-items-center mb-3">
        <a href="community.php" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Kembali ke Komunitas
        </a>
        <a href="add_content_to_cart.php?article_id=<?= (int)$article['article_id']; ?>&qty=1"
           class="btn btn-sm btn-gold">
            <i class="bi bi-cart-plus me-1"></i> Add to Cart
        </a>
    </div>

    <!-- Artikel -->
    <div class="article-header">
        <h3 class="mb-1"><?= htmlspecialchars($article['title']); ?></h3>
        <div class="article-meta">
            Diposting pada <?= date('d M Y, H:i', strtotime($article['created_at'])); ?>
        </div>
    </div>

    <?php
    $img = $article['image_url'] ?: 'https://i.postimg.cc/855ZSty7/no-bg.png';
    ?>
    <div class="article-img">
        <img src="<?= htmlspecialchars($img); ?>" alt="<?= htmlspecialchars($article['title']); ?>">
    </div>

    <div class="mb-3">
        <p class="article-price">
            Harga: Rp <?= number_format((int)$article['product_price'], 0, ',', '.'); ?>
        </p>
        <div style="font-size: 0.96rem;">
            <?= nl2br(htmlspecialchars($article['content'])); ?>
        </div>
    </div>

    <hr class="my-4">

    <!-- Komentar -->
    <h5 class="mb-3"><i class="bi bi-chat-dots me-1"></i> Diskusi Produk</h5>

    <?php if (!empty($error_message)): ?>
        <div class="alert alert-danger py-2"><?= htmlspecialchars($error_message); ?></div>
    <?php endif; ?>

    <!-- Form komentar -->
    <div class="comment-form mb-4">
        <form action="community_article_detail.php?id=<?= $article_id; ?>" method="post">
            <div class="mb-2">
                <label for="comment_text" class="form-label">Tulis komentar kamu</label>
                <textarea
                    name="comment_text"
                    id="comment_text"
                    class="form-control"
                    rows="3"
                    placeholder="Tanya soal spesifikasi, pengalaman pakai, atau share pendapatmu..."
                    required
                ></textarea>
            </div>
            <button type="submit" name="add_comment" class="btn btn-gold">
                <i class="bi bi-send-fill me-1"></i> Kirim Komentar
            </button>
        </form>
    </div>

    <!-- List komentar -->
    <?php if ($res_comments && mysqli_num_rows($res_comments) > 0): ?>
        <?php while ($cm = mysqli_fetch_assoc($res_comments)): ?>
            <div class="comment-card">
                <div class="d-flex justify-content-between">
                    <div class="comment-author">
                        <?= htmlspecialchars($cm['customer_name']); ?>
                    </div>
                    <div class="comment-date">
                        <?= date('d M Y, H:i', strtotime($cm['created_at'])); ?>
                    </div>
                </div>
                <div class="comment-text">
                    <?= nl2br(htmlspecialchars($cm['comment_text'])); ?>
                </div>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <p class="text-muted" style="font-size: 0.9rem;">
            Belum ada komentar. Jadilah yang pertama memberikan pendapatmu.
        </p>
    <?php endif; ?>

</div>

</body>
</html>
