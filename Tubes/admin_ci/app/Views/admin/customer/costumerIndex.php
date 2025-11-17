<?= $this->extend('admin/layout/main') ?>

<?= $this->section('content') ?>
<h2>Daftar Customer</h2>

<?php if (session()->getFlashdata('success')): ?>
    <div class="alert"><?= session()->getFlashdata('success') ?></div>
<?php endif; ?>

<form action="<?= base_url('admin/customer/search') ?>" method="get" style="margin-bottom:20px; display:flex; gap:10px; flex-wrap:wrap;">
    
    <input type="text" name="keyword" placeholder="Masukkan kata kunci..." 
           value="<?= esc($keyword ?? '') ?>" style="flex:2;" class="form-control">

    <select name="filter" style="flex:1;">
        <option value="nama" <?= ($filter ?? 'nama') === 'nama' ? 'selected' : '' ?>>Nama Customer</option>
        <option value="customer_id" <?= ($filter ?? '') === 'customer_id' ? 'selected' : '' ?>>ID Customer</option>
        <option value="email" <?= ($filter ?? '') === 'email' ? 'selected' : '' ?>>Email</option>
        <option value="no_telepon" <?= ($filter ?? '') === 'no_telepon' ? 'selected' : '' ?>>No. Telepon</option>
    </select>

    <button type="submit" class="btn-save" style="flex:0 0 120px;">Cari</button>
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
            <th>Nama</th>
            <th>Email</th>
            <th>No. Telepon</th>
            <th>Alamat</th>
            <th>Aksi</th>
        </tr>
    </thead>
    <tbody>
        <?php if (!empty($customers)): ?>
            <?php foreach ($customers as $c): ?>
                <tr>
                    <td><?= esc($c['customer_id']) ?></td>
                    <td><?= esc($c['nama']) ?></td>
                    <td><?= esc($c['email']) ?></td>
                    <td><?= esc($c['no_telepon']) ?></td>
                    <td><?= esc($c['alamat']) ?></td>
                    <td>
                        <a href="<?= base_url('admin/customer/delete/'.$c['customer_id']) ?>"
                           class="btn-cancel"
                           onclick="return confirm('Yakin ingin hapus customer ini? (Testing only)')">Hapus</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr><td colspan="6" style="text-align:center;">Tidak ada data customer yang ditemukan.</td></tr>
        <?php endif; ?>
    </tbody>
</table>
<?= $this->endSection() ?>