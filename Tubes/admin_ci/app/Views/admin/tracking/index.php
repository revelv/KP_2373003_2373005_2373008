<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Lacak Pesanan</title>
    <style>
        body {
            background-color: #121728;
            color: #fff;
            font-family: 'Segoe UI', sans-serif;
            margin: 0;
            padding: 0;
        }

        h2 {
            color: #F5A300;
            margin-bottom: 20px;
        }

        .container {
            width: 90%;
            margin: 40px auto;
            background-color: #1b2233;
            padding: 30px;
            border-radius: 10px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background-color: #222b40;
            border-radius: 8px;
            overflow: hidden;
        }

        table thead {
            background-color: #0f1524;
        }

        table th, table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #2c3650;
        }

        table tr:hover {
            background-color: #2a3450;
        }

        th {
            color: #F5A300;
            font-weight: 600;
        }

        .btn-info {
            background-color: #3c6ff7;
            color: #fff;
            padding: 6px 10px;
            border: none;
            border-radius: 5px;
            text-decoration: none;
            font-size: 14px;
        }

        .btn-info:hover {
            background-color: #5c85ff;
        }

        .alert {
            background-color: #0f1524;
            border-left: 5px solid #F5A300;
            padding: 10px 15px;
            margin-bottom: 15px;
            border-radius: 5px;
        }

        .status-badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 6px;
            font-weight: 600;
            font-size: 13px;
        }

        .status-proses { background-color: #3c6ff7; color: #fff; }
        .status-dikirim { background-color: #F5A300; color: #000; }
        .status-selesai { background-color: #28a745; color: #fff; }
    </style>
</head>
<body>
<div class="container">
    <h2>Lacak Pesanan</h2>

    <?php if (session()->getFlashdata('success')): ?>
        <div class="alert"><?= session()->getFlashdata('success') ?></div>
    <?php endif; ?>

    <table>
        <thead>
        <tr>
            <th>ID Pesanan</th>
            <th>Nama Customer</th>
            <th>Tanggal Order</th>
            <th>Status</th>
            <th>Aksi</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($orders as $order): ?>
            <tr>
                <td><?= esc($order['order_id']) ?></td>
                <td><?= esc($order['nama_customer']) ?></td>
                <td><?= esc($order['tgl_order']) ?></td>
                <td>
                    <?php 
                        $statusClass = 'status-'.$order['status']; 
                    ?>
                    <span class="status-badge <?= $statusClass ?>">
                        <?= ucfirst($order['status']) ?>
                    </span>
                </td>
                <td>
                    <a href="<?= base_url('admin/tracking/show/'.$order['order_id']) ?>" class="btn-info">Detail</a>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
</body>
</html>
