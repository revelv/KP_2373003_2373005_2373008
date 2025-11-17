<?= $this->extend('admin/layout/main') ?>

<?= $this->section('content') ?>
<h2>Riwayat Pembayaran (Struk)</h2>

<form action="<?= base_url('admin/struk/search') ?>" method="get" style="margin-bottom:20px;">
    <div class="form-row" style="gap:10px; flex-wrap:wrap;">
        <input type="text" name="keyword" placeholder="Masukkan kata kunci..." 
               value="<?= esc($keyword ?? '') ?>" style="flex:2;" class="form-control">
        
        <select name="filter" style="flex:1;">
            <option value="nama" <?= ($filter ?? '') === 'nama' ? 'selected' : '' ?>>Nama Customer</option>
            <option value="order_id" <?= ($filter ?? '') === 'order_id' ? 'selected' : '' ?>>Order ID</option>
            <option value="payment_id" <?= ($filter ?? '') === 'payment_id' ? 'selected' : '' ?>>Struk ID</option>
        </select>

        <button type="submit" class="btn-save" style="flex:0 0 120px;">Cari</button>
    </div>
</form>

<?php if (!empty($keyword)): ?>
    <p style="color:#aaa;">Hasil pencarian untuk <strong>"<?= esc($keyword) ?>"</strong> berdasarkan <em><?= esc($filter) ?></em>:</p>
<?php endif; ?>

<div class="form-row" style="flex-wrap:wrap; gap:20px; justify-content: flex-start; align-items: flex-start;">
    <?php if (!empty($payments)): ?>
        <?php foreach ($payments as $p): ?>
            
            <div class="card struk-card-wrapper" style="flex:1; min-width:340px; max-width: 380px;">
                
                <div class="struk-header-yellow">
                    <div>
                        <strong>Struk ID: <?= esc($p['payment_id']) ?></strong> |
                        <span><?= date('d/m/Y H:i', strtotime($p['tanggal_bayar'])) ?></span>
                    </div>
                    <a href="<?= base_url('admin/struk/print/'.$p['payment_id']) ?>" class="struk-btn-cetak">
                        🖨 Cetak
                    </a>
                </div>

                <div class="struk-body">
                    
                    <div class="struk-title">
                        <h4>STRUK PEMBAYARAN Stryk Admin</h4>
                        <span><?= date('d/m/Y H:i', strtotime($p['tanggal_bayar'])) ?></span>
                    </div>

                    <hr class="struk-hr">

                    <div class="struk-details">
                        <div class="detail-row">
                            <strong>No. Transaksi:</strong>
                            <span><?= esc($p['payment_id']) ?></span>
                        </div>
                        <div class="detail-row">
                            <strong>Order ID:</strong>
                            <span><?= esc($p['order_id']) ?></span>
                        </div>
                        <div class="detail-row">
                            <strong>Customer:</strong>
                            <span><?= esc($p['nama']) ?></span>
                        </div>
                        <div class="detail-row">
                            <strong>Alamat:</strong>
                            <span><?= esc($p['alamat']) ?></span>
                        </div>
                        <div class="detail-row">
                            <strong>Metode Bayar:</strong>
                            <span><?= esc($p['metode']) ?></span>
                        </div>
                        <div class="detail-row">
                            <strong>Status:</strong>
                            <span><?= ucfirst(esc($p['payment_status'])) ?></span>
                        </div>
                         <div class="detail-row" style="margin-top: 15px;">
                            <strong>Tgl. Order:</strong>
                            <span><?= date('d/m/Y H:i', strtotime($p['tgl_order'])) ?></span>
                        </div>
                    </div>

                    <div class="struk-total">
                        <span>Total</span>
                        <strong>Harga: $<?= esc(number_format($p['jumlah_dibayar'], 0, ',', '.')) ?></strong>
                    </div>

                    <hr class="struk-hr">
                    <div class="struk-footer">
                        <p>Terima kasih atas pembayarannya</p>
                        <p>Stryk Industries &copy; <?= date('Y') ?></p>
                    </div>

                </div>
            </div>

        <?php endforeach; ?>
    <?php else: ?>
        <div class="alert" style="width: 100%;">Tidak ada data ditemukan.</div>
    <?php endif; ?>
</div>
<?= $this->endSection() ?>