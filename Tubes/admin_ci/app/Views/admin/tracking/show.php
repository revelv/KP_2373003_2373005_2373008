<?= $this->extend('admin/layout/main') ?>

<?= $this->section('content') ?>
<h2><?= $title ?></h2>

<a href="<?= base_url('admin/orders') ?>" class="btn btn-cancel" style="display: inline-block; margin-bottom: 20px;">
    &laquo; Kembali ke Daftar Pesanan
</a>

<?php if (session()->getFlashdata('success')): ?>
    <div class="alert"><?= session()->getFlashdata('success') ?></div>
<?php endif; ?>
<?php if (session()->getFlashdata('error')): ?>
    <div class="alert alert-error"><?= session()->getFlashdata('error') ?></div>
<?php endif; ?>

<div class="card">
    <h3>Detail Pesanan</h3>
    <p>
        <strong>Customer:</strong> <?= esc($order['nama_customer']) ?><br>
        <strong>Email:</strong> <?= esc($order['email']) ?><br>
        <strong>Alamat Kirim:</strong> <?= esc($order['alamat']) ?>, <?= esc($order['kota']) ?>, <?= esc($order['provinsi']) ?>
    </p>
</div>

<div class="card" style="margin-top: 20px;">
    <h3>Riwayat Pelacakan</h3>
    
    <?php if (empty($tracking_history)): ?>
        <p style="color: #888;">Belum ada riwayat pelacakan untuk pesanan ini.</p>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Waktu</th>
                    <th>Status</th>
                    <th>Deskripsi/Catatan</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($tracking_history as $history): ?>
                    <tr>
                        <td style="white-space: nowrap;"><?= date('d M Y H:i', strtotime($history['timestamp'])) ?></td>
                        <td style="font-weight: bold; color: #F5A300;"><?= esc($history['status']) ?></td>
                        <td><?= esc($history['description']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

    <p style="margin-top: 15px; color: #aaa; font-style: italic; font-size: 14px;">
        Riwayat pelacakan diperbarui otomatis oleh sistem.
    </p>
</div>

<?= $this->endSection() ?>