<?php

declare(strict_types=1);
require_once __DIR__ . '/header.php';

if (!isset($_SESSION['kd_cs'])) {
    echo "<script>alert('Anda harus login terlebih dahulu.'); window.location.href='produk.php';</script>";
    exit();
}

$customer_id = (int) $_SESSION['kd_cs'];

// ====================== AMBIL DATA ORDER USER ======================
$sql = "
    SELECT 
        o.order_id,
        o.tgl_order,
        o.total_harga,
        o.shipping_status,
        GROUP_CONCAT(
            CONCAT(p.nama_produk, ' (x', od.jumlah, ')')
            SEPARATOR '||'
        ) AS items
    FROM orders o
    JOIN order_details od ON od.order_id = o.order_id
    JOIN products p       ON p.product_id = od.product_id
    WHERE 
        o.customer_id = ?
        -- SEMBUNYIKAN ORDER LELANG YG MASIH PENDING
        AND NOT (o.shipping_status = 'pending' AND o.order_id LIKE 'STYRK_AUC_%')
    GROUP BY o.order_id
    ORDER BY o.tgl_order DESC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $customer_id);
$stmt->execute();
$res = $stmt->get_result();

$orders = [];
while ($row = $res->fetch_assoc()) {
    $orders[] = $row;
}
$stmt->close();
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8" />
    <title>Riwayat Belanja</title>
    <link rel="stylesheet" href="./css/riwayat_belanja.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
</head>

<body>
    <section class="section-bleed">
        <div class="inner">
            <div class="page-head">
                <h2 class="page-title">Riwayat Belanja</h2>
            </div>

            <div class="card-wrap">
                <div class="table-responsive">
                    <table class="table table-bordered table-gold align-middle">
                        <colgroup>
                            <col class="col-date">
                            <col class="col-order">
                            <col class="col-barang">
                            <col class="col-total">
                            <col class="col-status">
                            <col class="col-action">
                        </colgroup>
                        <thead>
                            <tr>
                                <th>Tanggal</th>
                                <th>Order ID</th>
                                <th>Barang</th>
                                <th>Total</th>
                                <th>Status</th>
                                <th class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!$orders): ?>
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4">
                                        Belum ada transaksi.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($orders as $o): ?>
                                    <?php
                                        $tgl   = date('d M Y H:i', strtotime($o['tgl_order']));
                                        $total = (float) $o['total_harga'];

                                        $statusRaw = (string) ($o['shipping_status'] ?? '');
                                        $status    = strtolower($statusRaw);

                                        // olah list barang
                                        $itemsRaw   = (string) ($o['items'] ?? '');
                                        $firstItem  = '—';
                                        $otherCount = 0;

                                        if ($itemsRaw !== '') {
                                            $barangList = explode('||', $itemsRaw);
                                            $barangList = array_filter(
                                                $barangList,
                                                fn($v) => trim($v) !== ''
                                            );
                                            if ($barangList) {
                                                $firstItem  = $barangList[0];
                                                $otherCount = count($barangList) - 1;
                                            }
                                        }

                                        $badgeClass = 'badge-status ' . match ($status) {
                                            'pending' => 'badge-pending',
                                            'proses'  => 'badge-proses',
                                            'selesai' => 'badge-selesai',
                                            'batal'   => 'badge-batal',
                                            default   => 'bg-secondary',
                                        };
                                    ?>
                                    <tr>
                                        <!-- Tanggal -->
                                        <td class="nowrap">
                                            <?= htmlspecialchars($tgl) ?>
                                        </td>

                                        <!-- Order ID -->
                                        <td class="text-mono nowrap">
                                            <?= htmlspecialchars($o['order_id']) ?>
                                        </td>

                                        <!-- Kolom Barang -->
                                        <td>
                                            <div><?= htmlspecialchars($firstItem) ?></div>
                                            <?php if ($otherCount > 0): ?>
                                                <small class="text-muted">
                                                    + <?= $otherCount ?> barang lainnya
                                                </small>
                                            <?php endif; ?>
                                        </td>

                                        <!-- Total -->
                                        <td>
                                            <strong>
                                                Rp <?= number_format((int) round($total), 0, ',', '.') ?>
                                            </strong>
                                        </td>

                                        <!-- Status -->
                                        <td>
                                            <span class="<?= $badgeClass ?> px-2 py-1 rounded-2">
                                                <?= htmlspecialchars($status) ?>
                                            </span>
                                        </td>

                                        <!-- Aksi -->
                                        <td class="td-aksi text-center">
                                            <?php if ($status === 'pending'): ?>
                                                <!-- Pending: bisa lu isi tombol Bayar Ulang nanti -->
                                            <?php else: ?>
                                                <button
                                                    type="button"
                                                    class="btn btn-outline-secondary btn-sm"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#modalTrack"
                                                    data-order="<?= htmlspecialchars($o['order_id']) ?>">
                                                    Lacak
                                                </button>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </section>

    <!-- Modal tracking: struktur FIX, cuma isi body yang diganti via fetch -->
    <div class="modal fade" id="modalTrack" tabindex="-1" aria-hidden="true" aria-labelledby="modalTrackLabel">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTrackLabel">Tracking Pengiriman</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div class="modal-body" id="modalTrackBody">
                    <div class="text-center py-4">
                        <div class="spinner-border text-warning mb-2" role="status"></div>
                        <div>Memuat data tracking...</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const modalEl   = document.getElementById('modalTrack');
            const modalBody = document.getElementById('modalTrackBody');

            if (!modalEl || !modalBody) return;

            modalEl.addEventListener('show.bs.modal', function (event) {
                const button  = event.relatedTarget;
                const orderId = button ? (button.getAttribute('data-order') || '') : '';

                if (!orderId) {
                    modalBody.innerHTML = `
                        <div class="alert alert-danger mb-0">
                            Order ID tidak valid.
                        </div>`;
                    return;
                }

                // state loading
                modalBody.innerHTML = `
                    <div class="text-center py-4">
                        <div class="spinner-border text-warning mb-2" role="status"></div>
                        <div>Memuat data tracking...</div>
                    </div>`;

                fetch('track_biteship_modal.php?order_id=' + encodeURIComponent(orderId), {
                    cache: 'no-store'
                })
                    .then(res => res.text())
                    .then(html => {
                        // track_biteship_modal.php boleh balikin HTML bebas (card, timeline, dll),
                        // kita taruh mentah di dalam body modal.
                        modalBody.innerHTML = html;
                    })
                    .catch(err => {
                        console.error('Error load tracking:', err);
                        modalBody.innerHTML = `
                            <div class="alert alert-danger mb-0">
                                Gagal memuat tracking: ${String(err.message || err)}
                            </div>`;
                    });
            });
        });
    </script>

    <?php include __DIR__ . '/footer.php'; ?>
</body>

</html>
