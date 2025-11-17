<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>History Pembayaran</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background-color: #1c2541;
            color: #fff;
        }
        .card {
            border: 1px solid #2c3035;
        }
        .card-header {
            font-weight: bold;
        }
        input, button {
            border-radius: 8px !important;
        }
    </style>
</head>
<body>
<div class="container my-4">
    <h3 class="text-warning fw-bold mb-3">History Pembayaran</h3>

     <a href="<?= base_url('admin') ?>" class="btn btn-outline-light mb-4 d-inline-flex align-items-center">
        ←  Back to Dashboard
    </a>
    

    <form action="<?= base_url('paymentadmin/search') ?>" method="get" class="d-flex mb-4">
        <input type="text" name="keyword" class="form-control me-2" placeholder="Cari berdasarkan nama customer" value="<?= esc($keyword ?? '') ?>">
        <button type="submit" class="btn btn-warning d-flex align-items-center gap-1">
            <i class="bi bi-search"></i> Cari
        </button>
    </form>

    <div class="d-flex flex-wrap gap-3">
        <?php if (!empty($payments)) : ?>
            <?php foreach ($payments as $p) : ?>
                <div class="card bg-dark text-white" style="width: 22rem;">
                    <div class="card-header bg-warning text-dark fw-bold d-flex justify-content-between align-items-center">
                        <span>Struk ID: <?= esc($p['payment_id']) ?> | <?= date('d/m/Y H:i', strtotime($p['tanggal_bayar'])) ?></span>
                        <a href="<?= base_url('paymentadmin/print/' . $p['payment_id']) ?>" class="btn btn-dark btn-sm">
                            <i class="bi bi-printer"></i> Cetak
                        </a>
                    </div>

                    <div class="card-body">
                        <h5 class="text-center fw-bold">STRUK PEMBAYARAN Stryk Admin</h5>
                        <p class="text-center small mb-3"><?= date('d/m/Y H:i', strtotime($p['tanggal_bayar'])) ?></p>
                        <hr class="border-secondary">

                        <p><strong>No. Transaksi:</strong> <?= esc($p['payment_id']) ?></p>
                        <p><strong>Order ID:</strong> <?= esc($p['order_id']) ?></p>
                        <p><strong>Customer:</strong> <?= esc($p['nama']) ?></p>
                        <p><strong>Alamat:</strong> <?= esc($p['alamat']) ?></p>
                        <p><strong>Metode Bayar:</strong> <?= esc($p['metode']) ?></p>
                        <p><strong>Status:</strong> <?= ucfirst(esc($p['payment_status'])) ?></p>
                        <p><strong>Tgl. Order:</strong> <?= date('d/m/Y H:i', strtotime($p['tgl_order'])) ?></p>
                        <p><strong>Jumlah Bayar:</strong> $<?= esc($p['jumlah_dibayar']) ?></p>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else : ?>
            <div class="alert alert-secondary">Tidak ada data pembayaran.</div>
        <?php endif; ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
