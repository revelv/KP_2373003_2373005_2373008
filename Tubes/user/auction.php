<?php
include 'header.php';

// Pastikan user sudah login
if (!isset($_SESSION['kd_cs'])) {
    echo "<script>alert('Anda harus login untuk mengakses halaman lelang.'); window.location.href='produk.php';</script>";
    exit();
}
$customer_id = (int)$_SESSION['kd_cs'];

// LELANG:
// - tampilkan semua yang masih aktif
// - PLUS lelang yang sudah berakhir, user ini pemenang,
//   BELUM pernah dibuat order lelang / status pending, dan max 1 hari dari end_time.
$query_auctions = "
    SELECT 
        a.*,
        c.nama AS seller_name,
        o.order_id AS linked_order_id,
        o.komship_status   AS order_status
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
        AND (o.order_id IS NULL OR o.komship_status = 'pending')
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
    <title>Lelang - Styrk Industries</title>

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

        .auction-container {
            max-width: 1000px;
            margin: 40px auto;
            background: #fff;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
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

    <div class="auction-container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="fw-semibold mb-0">
                <i class="bi bi-gavel me-2"></i> Auction
            </h2>
        </div>

        <div class="row g-4">
            <?php if ($result_auctions && mysqli_num_rows($result_auctions) > 0): ?>
                <?php while ($auction = mysqli_fetch_assoc($result_auctions)):
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
                                <h5 class="mb-1">
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

    <?php
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
    </script>

</body>
</html>
