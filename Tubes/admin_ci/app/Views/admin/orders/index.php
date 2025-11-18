<?= $this->extend('admin/layout/main') ?>

<?= $this->section('content') ?>
<h2>Daftar Pesanan</h2>

<?php if (session()->getFlashdata('success')): ?>
    <div class="alert"><?= session()->getFlashdata('success') ?></div>
<?php endif; ?>

<form action="<?= base_url('admin/orders/search') ?>" method="get" style="margin-bottom:20px;">
    <div class="form-row" style="gap:10px; flex-wrap:wrap;">
        <input type="text" name="keyword" placeholder="Masukkan kata kunci..." 
               value="<?= esc($keyword ?? '') ?>" style="flex:2;" class="form-control">
        
        <select name="filter" style="flex:1;">
            <option value="nama_customer" <?= ($filter ?? 'nama_customer') === 'nama_customer' ? 'selected' : '' ?>>Nama Customer</option>
            <option value="order_id" <?= ($filter ?? '') === 'order_id' ? 'selected' : '' ?>>Order ID</option>
            <option value="status" <?= ($filter ?? '') === 'status' ? 'selected' : '' ?>>Status</option>
        </select>

        <button type="submit" class="btn-save" style="flex:0 0 120px;">Cari</button>
    </div>
</form>

<?php if (!empty($keyword)): ?>
    <p style="color:#aaa; margin-top:10px; margin-bottom: 20px;">
        Hasil pencarian untuk <strong>"<?= esc($keyword) ?>"</strong> berdasarkan <em><?= esc(ucfirst(str_replace('_', ' ', $filter))) ?></em>:
    </p>
<?php endif; ?>


<table>
    <thead>
    <tr>
        <th>ID</th>
        <th>Customer</th>
        <th>Tanggal</th>
        <th>Total</th>
        <th>Ongkir</th> <th>Alamat</th> <th>Status</th>
        <th>Aksi</th>
    </tr>
    </thead>
    <tbody>
        <?php if (!empty($orders)): ?>
            <?php foreach ($orders as $order): ?>
            <tr>
                <td style="word-break: break-all; max-width: 150px;"><?= esc($order['order_id']) ?></td>
                
                <td><?= esc($order['nama_customer']) ?></td>
                
                <td><?= date('d M Y H:i', strtotime($order['tgl_order'])) ?></td>
                
                <td>Rp <?= esc(number_format($order['total_harga'], 0, ',', '.')) ?></td>
                
                <td>Rp <?= esc(number_format($order['ongkos_kirim'], 0, ',', '.')) ?></td>
                
                <td style="max-width: 200px; white-space: normal; line-height: 1.4;">
                    <?= esc($order['alamat']) ?><br>
                    <small style="color:#555;"><?= esc($order['kota']) ?>, <?= esc($order['provinsi']) ?></small>
                </td>
                
                <td>
                    <form class="status-form" action="<?= base_url('admin/orders/updateStatus/'.$order['order_id']) ?>" method="post" style="display:flex; gap: 5px;">
                        <select name="status" style="flex:1;">
                            <option value="pending" <?= $order['status']=='pending'?'selected':'' ?>>Pending</option>
                            <option value="proses" <?= $order['status']=='proses'?'selected':'' ?>>Proses</option>
                            <option value="selesai" <?= $order['status']=='selesai'?'selected':'' ?>>Selesai</option>
                            <option value="batal" <?= $order['status']=='batal'?'selected':'' ?>>Batal</option>
                        </select>
                        <button type="submit" class="btn-primary" style="flex:0;">Update</button>
                    </form>
                </td>
                
                <td>
                    <?php if ($order['status'] == 'pending' || $order['status'] == 'batal'): ?>
                        <a href="#" class="btn-cancel" 
                           style="opacity: 0.6; cursor: not-allowed; text-decoration: none;" 
                           onclick="return false;">
                           Lacak
                        </a>
                    <?php else: ?>
                        <a href="<?= base_url('admin/tracking/show/'.$order['order_id']) ?>" class="btn-info">Lacak</a>
                    <?php endif; ?>
                    
                    </td>
            </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr>
                <td colspan="8" style="text-align: center;">Tidak ada data pesanan yang ditemukan.</td>
            </tr>
        <?php endif; ?>
    </tbody>
</table>
<?= $this->endSection() ?>