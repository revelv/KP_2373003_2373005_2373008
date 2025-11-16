<?php
include 'header.php';

// Pastikan user sudah login
if (!isset($_SESSION['kd_cs'])) {
    echo "<script>alert('Anda harus login untuk mengakses forum.'); window.location.href='produk.php';</script>";
    exit();
}
$customer_id = (int)$_SESSION['kd_cs'];

// THREAD (tetap sama)
$query_threads = "
    SELECT t.thread_id, t.title, t.created_at, c.nama as author_name,
           (SELECT COUNT(*) FROM posts p WHERE p.thread_id = t.thread_id) as reply_count,
           (SELECT MAX(p.created_at) FROM posts p WHERE p.thread_id = t.thread_id) as last_reply_time
    FROM threads t
    JOIN customer c ON t.customer_id = c.customer_id
    ORDER BY COALESCE(last_reply_time, t.created_at) DESC
";
$result_threads = mysqli_query($conn, $query_threads);

// LELANG:
// - tampilkan semua yang masih aktif
// - PLUS lelang yang sudah berakhir, user ini pemenang,
//   BELUM pernah dibuat order lelang, dan max 1 hari dari end_time.
$query_auctions = "
    SELECT 
        a.*,
        c.nama AS seller_name,
        o.order_id AS linked_order_id,
        o.status   AS order_status
    FROM auctions a
    JOIN customer c ON a.customer_id = c.customer_id
    LEFT JOIN orders o
       ON o.customer_id = ?
      AND o.order_id LIKE CONCAT('STYRK_AUC_', a.auction_id, '_%')
    WHERE
      (a.status = 'active' AND a.end_time > NOW())
      OR (
            a.status <> 'active'
        AND a.end_time <= NOW()                            -- lelang sudah benar-benar selesai
        AND a.end_time >= DATE_SUB(NOW(), INTERVAL 1 DAY)  -- max 1 hari dari kemenangan
        AND a.current_winner_id = ?                        -- user ini pemenangnya
        AND (o.order_id IS NULL OR o.status = 'pending')
      )
    ORDER BY a.end_time ASC
";

$stmt_auc = $conn->prepare($query_auctions);
if (!$stmt_auc) {
    die('Gagal load lelang: ' . $conn->error);
}
$stmt_auc->bind_param('ii', $customer_id, $customer_id);
$stmt_auc->execute();
$result_auctions = $stmt_auc->get_result();
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Komunitas & Lelang - Styrk Industries</title>

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

        /* Lelang */
        .nav-pills .nav-link {
            color: #6c757d;
        }

        .nav-pills .nav-link.active {
            background-color: var(--gold);
            color: var(--dark-gray);
            font-weight: 500;
        }

        .auction-card {
            border: 1px solid #eee;
            border-radius: 8px;
            background: #fff;
            transition: all 0.25s ease-in-out;
            cursor: pointer;
            display: flex;
            flex-direction: column;
            height: 100%;
        }

        .auction-card:hover {
            border-color: var(--gold);
            box-shadow: 0 6px 18px rgba(212, 175, 55, 0.15);
            transform: translateY(-2px);
            background-color: #fffbea;
        }

        .auction-img {
            height: 200px;
            width: 100%;
            object-fit: cover;
            border-radius: 8px 8px 0 0;
        }

        .auction-card .card-body {
            padding: 1rem;
            flex-grow: 1;
        }

        .auction-card .card-footer {
            background: none;
            border-top: 1px solid #eee;
            padding: 1rem;
        }

        .countdown-timer {
            font-weight: bold;
            color: #d9534f;
        }
    </style>
</head>

<body>

    <div class="forum-container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="fw-semibold mb-0">
                <i class="bi bi-chat-dots-fill me-2"></i> Komunitas
            </h2>
            <div class="btn-group">
                <a href="create_thread_form.php" class="btn btn-new-thread">
                    <i class="bi bi-plus-lg me-1"></i> Buat Topik
                </a>
            </div>
        </div>

        <ul class="nav nav-pills nav-fill mb-3" id="communityTab" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="forum-tab" data-bs-toggle="tab" data-bs-target="#forum-pane"
                    type="button" role="tab" aria-controls="forum-pane" aria-selected="true">
                    <i class="bi bi-chat-dots-fill me-1"></i> Diskusi Forum
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="lelang-tab" data-bs-toggle="tab" data-bs-target="#lelang-pane"
                    type="button" role="tab" aria-controls="lelang-pane" aria-selected="false">
                    <i class="bi bi-gavel me-1"></i> Lelang
                </button>
            </li>
        </ul>

        <div class="tab-content" id="communityTabContent">
            <!-- Forum -->
            <div class="tab-pane fade show active" id="forum-pane" role="tabpanel" aria-labelledby="forum-tab">
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
                                    <span class="replies d-block"><?= $thread['reply_count']; ?> Replies</span>
                                    <?php if ($thread['last_reply_time']): ?>
                                        <span class="last-reply">
                                            Last: <?= date('d M Y, H:i', strtotime($thread['last_reply_time'])); ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <p class="text-center text-muted">Belum ada topik diskusi. Jadilah yang pertama!</p>
                <?php endif; ?>
            </div>

            <!-- Lelang -->
            <div class="tab-pane fade" id="lelang-pane" role="tabpanel" aria-labelledby="lelang-tab">
                <div class="row g-4">
                    <?php if ($result_auctions && mysqli_num_rows($result_auctions) > 0): ?>
                        <?php while ($auction = mysqli_fetch_assoc($result_auctions)):
                            // selesai kalau end_time sudah lewat
                            $ended    = (strtotime($auction['end_time']) <= time());
                            $isWinner = ((int)$auction['current_winner_id'] === $customer_id);
                            $hasOrder = !empty($auction['linked_order_id']);
                        ?>
                            <div class="col-md-6 col-lg-4">
                                <div class="auction-card"
                                    onclick="window.location='auction_detail.php?id=<?= $auction['auction_id']; ?>'">
                                    <img src="<?= htmlspecialchars($auction['image_url'] ?? 'https://i.postimg.cc/855ZSty7/no-bg.png'); ?>"
                                        class="auction-img"
                                        alt="<?= htmlspecialchars($auction['title']); ?>">
                                    <div class="card-body">
                                        <h5 class="thread-title mb-1">
                                            <a href="auction_detail.php?id=<?= $auction['auction_id']; ?>">
                                                <?= htmlspecialchars($auction['title']); ?>
                                            </a>
                                        </h5>
                                        <h6 class="mb-1" style="font-size: 0.9rem;">Tawaran Saat Ini:</h6>
                                        <h4 class="fw-bold" style="color: #28a745;">
                                            Rp <?= number_format($auction['current_bid'], 0, ',', '.'); ?>
                                        </h4>
                                    </div>
                                    <div class="card-footer">
                                        <?php if (!$ended): ?>
                                            <!-- Lelang masih berjalan: countdown -->
                                            <p class="mb-1" style="font-size: 0.85rem; color: #6c757d;">Berakhir dalam:</p>
                                            <div class="countdown-timer" data-endtime="<?= htmlspecialchars($auction['end_time']); ?>">
                                                Menghitung...
                                            </div>
                                        <?php else: ?>
                                            <!-- Lelang sudah berakhir -->
                                            <span class="badge bg-danger mb-1">Lelang berakhir</span>
                                            <?php if ($isWinner && !$hasOrder): ?>
                                                <p class="mb-0" style="font-size: 0.85rem;">
                                                    Kamu pemenang. Klik kartu ini untuk proses pembayaran
                                                    (batas 1×24 jam).
                                                </p>
                                            <?php else: ?>
                                                <p class="mb-0" style="font-size: 0.85rem;">
                                                    Lelang sudah selesai.
                                                </p>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <p class="text-center text-muted mt-3">Belum ada barang yang dilelang saat ini.</p>
                    <?php endif; ?>
                </div>
            </div>

        </div>
    </div>

    <?php
    // pastikan statement auctions ditutup
    if (isset($stmt_auc) && $stmt_auc instanceof mysqli_stmt) {
        $stmt_auc->close();
    }
    ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        function startCountdown() {
            document.querySelectorAll('.countdown-timer').forEach(timer => {
                const endTime = new Date(timer.dataset.endtime).getTime();

                if (timer.dataset.intervalId) {
                    clearInterval(timer.dataset.intervalId);
                }

                const updateTimer = () => {
                    const now = new Date().getTime();
                    const distance = endTime - now;

                    if (distance < 0) {
                        clearInterval(timer.dataset.intervalId);
                        timer.innerHTML = "LELANG BERAKHIR";
                        return;
                    }

                    const days = Math.floor(distance / (1000 * 60 * 60 * 24));
                    const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                    const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
                    const seconds = Math.floor((distance % (1000 * 60)) / 1000);

                    timer.innerHTML = `${days}h ${hours}j ${minutes}m ${seconds}d`;
                };

                updateTimer();
                timer.dataset.intervalId = setInterval(updateTimer, 1000);
            });
        }

        startCountdown();

        const lelangTab = document.querySelector('#lelang-tab');
        if (lelangTab) {
            lelangTab.addEventListener('shown.bs.tab', function() {
                startCountdown();
            });
        }
    </script>

</body>

</html>