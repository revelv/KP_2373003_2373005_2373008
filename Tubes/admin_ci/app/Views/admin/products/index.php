<?= $this->extend('admin/layout/main') ?>

<?= $this->section('content') ?>
<h2>Daftar Produk</h2>

<?php if (session()->getFlashdata('success')): ?>
    <div class="alert"><?= session()->getFlashdata('success') ?></div>
<?php endif; ?>

<form action="<?= base_url('admin/products/search') ?>" method="get" style="margin-bottom:20px; display:flex; gap:10px; flex-wrap:wrap;">
    <input type="text" name="keyword" placeholder="Masukkan kata kunci..." 
           value="<?= esc($keyword ?? '') ?>" style="flex:2;">

    <select name="filter" style="flex:1; padding:6px; border-radius:6px;">
        <option value="nama_produk" <?= ($filter ?? '') === 'nama_produk' ? 'selected' : '' ?>>Nama Produk</option>
        <option value="product_id" <?= ($filter ?? '') === 'product_id' ? 'selected' : '' ?>>ID Produk</option>
        <option value="category" <?= ($filter ?? '') === 'category' ? 'selected' : '' ?>>Kategori</option>
    </select>

    <button type="submit" class="btn btn-save" style="flex:0 0 120px;">Cari</button>
</form>

<a href="<?= base_url('admin/products/create') ?>" class="btn btn-save">+ Tambah Produk</a>
<br>
<br>

<?php if (!empty($keyword)): ?>
    <p style="color:#aaa; margin-top:10px;">
        Hasil pencarian untuk <strong>"<?= esc($keyword) ?>"</strong> berdasarkan <em><?= esc($filter) ?></em>:
    </p>
<?php endif; ?>

<table>
    <thead>
        <tr>
            <th>ID</th>
            <th>Nama Produk</th>
            <th>Kategori</th>
            <th>Harga</th>
            <th>Stok</th>
            <th>Berat</th> <th>Status</th> <th>Gambar</th>
            <th>Aksi</th>
        </tr>
    </thead>
    <tbody>
        <?php if (!empty($products)): ?>
            <?php foreach ($products as $p): ?>
                <tr>
                    <td><?= esc($p['product_id']) ?></td>
                    <td><?= esc($p['nama_produk']) ?></td>
                    <td><?= esc($p['category'] ?? '-') ?></td>
                    <td>Rp <?= number_format($p['harga'], 0, ',', '.') ?></td>
                    <td><?= esc($p['stok']) ?></td>
                    <td><?= esc($p['weight']) ?> gr</td>
                    <td>
                        <?php if($p['status_jual'] == 'dilelang'): ?>
                            <span style="color: blue; font-weight: bold;">Dilelang</span>
                        <?php else: ?>
                            <span>Dijual</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if (!empty($p['link_gambar'])): ?>
                            <img src="<?= esc($p['link_gambar']) ?>" alt="Gambar" class="preview" style="max-width:60px; border-radius:4px;">
                        <?php else: ?>
                            <span style="color:#888;">Tidak ada</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <a href="<?= base_url('admin/products/edit/'.$p['product_id']) ?>" class="btn btn-warning">Edit</a>
                        <a href="<?= base_url('admin/products/delete/'.$p['product_id']) ?>"
                           class="btn btn-cancel"
                           onclick="return confirm('Yakin ingin hapus produk ini?')">Hapus</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr><td colspan="9" style="text-align:center;">Belum ada data produk.</td></tr>
        <?php endif; ?>
    </tbody>
</table>
<?= $this->endSection() ?>