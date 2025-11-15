<?php

declare(strict_types=1);
require_once __DIR__ . '/header.php';

if (!isset($_SESSION['kd_cs'])) {
    echo "<script>alert('Anda harus login terlebih dahulu.'); window.location.href='produk.php';</script>";
    exit();
}
$customer_id = (int) $_SESSION['kd_cs'];

$sql = "SELECT o.order_id, o.tgl_order, o.provinsi, o.kota, o.alamat,
               o.code_courier, o.ongkos_kirim, o.total_harga, o.status,
               COALESCE(c.nama_kurir, '') AS nama_kurir
        FROM orders o
        LEFT JOIN courier c ON c.code_courier = o.code_courier
        WHERE o.customer_id = ?
        ORDER BY o.tgl_order DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $customer_id);
$stmt->execute();
$res = $stmt->get_result();
$orders = [];
while ($row = $res->fetch_assoc()) $orders[] = $row;
$stmt->close();
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8" />
    <title>Riwayat Belanja</title>
    <link rel="stylesheet" href="./css/riwayat_belanja.css">
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
                            <col> <col class="col-courier">
                            <col class="col-ongkir">
                            <col class="col-total">
                            <col class="col-status">
                            <col class="col-action">
                        </colgroup>
                        <thead>
                            <tr>
                                <th>Tanggal</th>
                                <th>Order ID</th>
                                <th>Tujuan</th>
                                <th>Kurir</th>
                                <th>Ongkir</th>
                                <th>Total</th>
                                <th>Status</th>
                                <th class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!$orders): ?>
                                <tr>
                                    <td colspan="8" class="text-center text-muted py-4">Belum ada transaksi.</td>
                                </tr>
                                <?php else: foreach ($orders as $o):
                                    $tgl     = date('d M Y H:i', strtotime($o['tgl_order']));
                                    $alamat  = trim((string)($o['alamat'] ?? ''));
                                    $provRaw = (string)($o['provinsi'] ?? '');
                                    $kotaRaw = (string)($o['kota'] ?? '');
                                    $kurirNm = trim((string)$o['nama_kurir']);
                                    if ($kurirNm === '') $kurirNm = ($o['code_courier'] ? strtoupper($o['code_courier']) : '-');
                                    $ongkir  = (int)$o['ongkos_kirim'];
                                    $total   = (float)$o['total_harga'];
                                    $status  = strtolower((string)$o['status']);

                                    $looksProvId = ctype_digit($provRaw);
                                    $looksCityId = ctype_digit($kotaRaw);
                                    $hasNames    = (!$looksProvId && !$looksCityId && $provRaw !== '' && $kotaRaw !== '');
                                    $cityProvTxt = $hasNames ? ($kotaRaw . ' - ' . $provRaw) : 'Memuat…';
                                    $addrShown   = $alamat !== '' ? htmlspecialchars($alamat) : '—';
                                    $badgeClass  = 'badge-status ' . match ($status) {
                                        'pending' => 'badge-pending',
                                        'proses' => 'badge-proses',
                                        'selesai' => 'badge-selesai',
                                        'batal' => 'badge-batal',
                                        default => 'bg-secondary'
                                    };
                                ?>
                                    <tr>
                                        <td class="nowrap"><?= htmlspecialchars($tgl) ?></td>
                                        <td class="text-mono nowrap"><?= htmlspecialchars($o['order_id']) ?></td>
                                        <td>
                                            <div class="dest" data-prov="<?= htmlspecialchars($provRaw) ?>" data-city="<?= htmlspecialchars($kotaRaw) ?>">
                                                <div class="address"><?= $addrShown ?></div>
                                                <div class="cityprov js-cityprov"><?= htmlspecialchars($cityProvTxt) ?></div>
                                            </div>
                                        </td>
                                        <td><?= htmlspecialchars($kurirNm) ?></td>
                                        <td>Rp <?= number_format($ongkir, 0, ',', '.') ?></td>
                                        <td><strong>Rp <?= number_format((int)round($total), 0, ',', '.') ?></strong></td>
                                        <td><span class="<?= $badgeClass ?> px-2 py-1 rounded-2"><?= htmlspecialchars($status) ?></span></td>
                                        
                                        <td class="td-aksi text-center">
                                            <?php if ($status == 'pending'): ?>
                                                <a href="payment.php?order_id=<?= htmlspecialchars($o['order_id']) ?>" class="btn btn-success btn-sm">
                                                    Bayar Sekarang
                                                </a>
                                            <?php else: ?>
                                                <button
                                                    type="button"
                                                    class="btn btn-outline-secondary btn-sm"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#modalTrack"
                                                    data-order="<?= htmlspecialchars($o['order_id']) ?>"
                                                    data-courier="<?= htmlspecialchars($o['code_courier']) ?>">
                                                    Lacak
                                                </button>
                                            <?php endif; ?>
                                        </td>
                                        </tr>
                                <?php endforeach;
                                endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </section>

    <div class="modal fade" id="modalTrack" tabindex="-1" aria-hidden="true">
        </div>

    <script>
        (async function() {
            // ... (kode JS Anda untuk resolve nama kota/provinsi) ...
        })();

        // ... (kode JS Anda untuk modal tracking) ...
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <?php include __DIR__ . '/footer.php'; ?>
</body>
</html>