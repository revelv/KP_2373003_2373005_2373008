
<?php
include 'header.php';
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("ID lelang tidak valid.");
}
$auction_id = (int)$_GET['id'];

// Ambil data lelang
$query_auc = "
    SELECT a.*, w.nama as winner_name
    FROM auctions a
    LEFT JOIN customer w ON a.current_winner_id = w.customer_id
    WHERE a.auction_id = ?
";
$stmt_auc = $conn->prepare($query_auc);
$stmt_auc->bind_param("i", $auction_id);
$stmt_auc->execute();
$result_auc = $stmt_auc->get_result();

if ($result_auc->num_rows === 0) {
    die("Lelang tidak ditemukan.");
}
$auction = $result_auc->fetch_assoc();
$stmt_auc->close();

// Ambil data bid history
$query_bids = "
    SELECT b.*, c.nama as bidder_name
    FROM bids b
    JOIN customer c ON b.customer_id = c.customer_id
    WHERE b.auction_id = ?
    ORDER BY b.bid_time DESC
";
$stmt_bids = $conn->prepare($query_bids);
$stmt_bids->bind_param("i", $auction_id);
$stmt_bids->execute();
$result_bids = $stmt_bids->get_result();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($auction['title']); ?> - Lelang</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.5.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        .auction-image-detail {
            max-height: 500px;
            width: 100%;
            object-fit: cover;
            border-radius: 8px;
        }

        .bid-box {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
        }

        .countdown-timer-detail {
            font-size: 1.5rem;
            font-weight: bold;
            color: var(--gold);
        }
    </style>
</head>

<body>
    <div class="container mt-5">
        <div class="row">
            <div class="col-lg-7">
                <img src="<?= htmlspecialchars($auction['image_url']); ?>" class="auction-image-detail" alt="<?= htmlspecialchars($auction['title']); ?>">
            </div>
            <div class="col-lg-5">
                <a href="community.php" class="btn btn-sm btn-outline-secondary mb-2">← Kembali ke Daftar Lelang</a>
                <h2><?= htmlspecialchars($auction['title']); ?></h2>
                <p><?= nl2br(htmlspecialchars($auction['description'])); ?></p>

                <hr>

                <div class="bid-box">
                    <?php if ($auction['status'] == 'active' && strtotime($auction['end_time']) > time()): ?>
                        <h5 class="text-success">Tawaran Tertinggi Saat Ini:</h5>
                        <h3 class="fw-bold">Rp <?= number_format($auction['current_bid'], 0, ',', '.'); ?></h3>
                        <?php if ($auction['winner_name']): ?>
                            <p class="mb-3">oleh: <?= htmlspecialchars($auction['winner_name']); ?></p>
                        <?php endif; ?>

                        <p class="text-danger mb-1">Berakhir dalam:</p>
                        <div class="countdown-timer-detail mb-3" data-endtime="<?= $auction['end_time']; ?>">Menghitung...</div>

                        <?php if (isset($_SESSION['alert'])): ?>
                            <div class="alert alert-danger"><?= $_SESSION['alert'];
                                                            unset($_SESSION['alert']); ?></div>
                        <?php endif; ?>

                        <form action="proses_place_bid.php" method="POST">
                            <input type="hidden" name="auction_id" value="<?= $auction_id; ?>">
                            <div class="mb-3">
                                <label for="bid_amount" class="form-label">Tawaran Anda (Minimal Rp <?= number_format($auction['current_bid'] + 1, 0, ',', '.'); ?>)</label>
                                <input type="number" class="form-control" id="bid_amount" name="bid_amount"
                                    min="<?= $auction['current_bid'] + 1; ?>"
                                    placeholder="<?= number_format($auction['current_bid'] + 1, 0, ',', ''); ?>" required>
                            </div>
                            <button type="submit" class="btn btn-lg w-100" style="background-color: var(--gold); color: var(--dark-gray);">Tawar Sekarang (Bid)</button>
                        </form>

                    <?php else: // Lelang berakhir 
                    ?>
                        <h5 class="text-danger">Lelang Telah Berakhir</h5>
                        <h3 class="fw-bold">Dimenangkan oleh:</h3>
                        <h4 class="text-success"><?= htmlspecialchars($auction['winner_name'] ?? 'Tidak ada pemenang'); ?></h4>
                        <p>dengan tawaran akhir:</p>
                        <h4 class="fw-bold">Rp <?= number_format($auction['current_bid'], 0, ',', '.'); ?></h4>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="mt-5">
            <h4>Riwayat Tawaran</h4>
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Penawar</th>
                        <th>Jumlah Tawaran</th>
                        <th>Waktu</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($bid = $result_bids->fetch_assoc()): ?>
                        <tr>
                            <td><?= htmlspecialchars($bid['bidder_name']); ?></td>
                            <td>Rp <?= number_format($bid['bid_amount'], 0, ',', '.'); ?></td>
                            <td><?= date('d M Y, H:i:s', strtotime($bid['bid_time'])); ?></td>
                        </tr>
                    <?php endwhile;
                    $stmt_bids->close(); ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        // Script countdown yang sama dari auctions.php
        document.querySelectorAll('.countdown-timer-detail').forEach(timer => {
            const endTime = new Date(timer.dataset.endtime).getTime();
            const x = setInterval(function() {
                const now = new Date().getTime();
                const distance = endTime - now;

                const days = Math.floor(distance / (1000 * 60 * 60 * 24));
                const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
                const seconds = Math.floor((distance % (1000 * 60)) / 1000);

                timer.innerHTML = `${days}h ${hours}j ${minutes}m ${seconds}d`;

                if (distance < 0) {
                    clearInterval(x);
                    timer.innerHTML = "LELANG BERAKHIR";
                    if (distance > -5000) location.reload(); // Auto refresh halaman setelah 5 detik lelang berakhir
                }
            }, 1000);
        });
    </script>
</body>

</html>