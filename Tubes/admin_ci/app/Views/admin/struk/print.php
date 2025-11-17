<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Cetak Struk #<?= esc($payment['payment_id']) ?></title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; color: #000; }
        h2 { text-align: center; color: #333; }
        .struk-box {
            border: 1px solid #aaa;
            border-radius: 8px;
            padding: 20px;
            width: 400px;
            margin: 0 auto;
        }
        p { margin: 4px 0; }
        hr { border: 0; border-top: 1px dashed #aaa; margin: 10px 0; }
        .center { text-align: center; }
        .print-btn { margin-top: 20px; text-align: center; }
        .print-btn button {
            padding: 8px 20px;
            background: #F5A300;
            border: none;
            border-radius: 6px;
            font-weight: bold;
            cursor: pointer;
        }
    </style>
</head>
<body>

    <div class="struk-box">
        <h2>STRUK PEMBAYARAN</h2>
        <hr>
        <p><strong>ID Struk:</strong> <?= esc($payment['payment_id']) ?></p>
        <p><strong>Tanggal Bayar:</strong> <?= date('d/m/Y H:i', strtotime($payment['tanggal_bayar'])) ?></p>
        <p><strong>Order ID:</strong> <?= esc($payment['order_id']) ?></p>
        <p><strong>Customer:</strong> <?= esc($payment['nama']) ?></p>
        <p><strong>Alamat:</strong> <?= esc($payment['alamat']) ?></p>
        <p><strong>Metode:</strong> <?= esc($payment['metode']) ?></p>
        <p><strong>Status:</strong> <?= ucfirst(esc($payment['payment_status'])) ?></p>
        <p><strong>Jumlah:</strong> $<?= esc($payment['jumlah_dibayar']) ?></p>
        <hr>
        <div class="center">Terima kasih telah berbelanja di <strong>STYRK Industries</strong></div>
    </div>

    <div class="print-btn">
        <button onclick="window.print()">🖨️ Cetak</button>
    </div>

</body>
</html>
