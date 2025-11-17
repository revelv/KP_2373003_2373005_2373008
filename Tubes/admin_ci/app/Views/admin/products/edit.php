<?= $this->extend('admin/layout/main') ?>

<?= $this->section('content') ?>
<h2>Edit Produk</h2>

<form action="<?= base_url('admin/products/update/'.$product['product_id']) ?>" method="post">
    <div class="form-row">
        <div class="form-group">
            <label>ID Produk</label>
            <input type="text" name="product_id" value="<?= esc($product['product_id']) ?>" readonly>
        </div>
        <div class="form-group">
            <label>Nama Produk</label>
            <input type="text" name="nama_produk" value="<?= esc($product['nama_produk']) ?>" required>
        </div>
    </div>

    <div class="form-group">
        <label>Deskripsi</label>
        <textarea name="deskripsi_produk" required><?= esc($product['deskripsi_produk']) ?></textarea>
    </div>

    <div class="form-row">
        <div class="form-group">
            <label>Harga</label>
            <input type="number" step="0.01" name="harga" value="<?= esc($product['harga']) ?>" required>
        </div>
        <div class="form-group">
            <label>Stok</label>
            <input type="number" name="stok" value="<?= esc($product['stok']) ?>" required>
        </div>
        <div class="form-group">
            <label>Berat (gram)</label>
            <input type="number" name="weight" value="<?= esc($product['weight']) ?>" required>
        </div>
    </div>

    <div class="form-group">
        <label>Link Gambar</label>
        <input type="text" name="link_gambar" value="<?= esc($product['link_gambar']) ?>">
        <img src="<?= esc($product['link_gambar']) ?>" class="preview" alt="Preview">
    </div>

    <div class="form-row">
        <div class="form-group">
            <label>Kategori</label>
            <select name="category_id" required>
                <?php foreach ($categories as $c): ?>
                    <option value="<?= $c['category_id'] ?>"
                        <?= $c['category_id'] == $product['category_id'] ? 'selected' : '' ?>>
                        <?= esc($c['category']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label>Status Jual</label>
            <select name="status_jual" required>
                <option value="dijual" <?= $product['status_jual'] == 'dijual' ? 'selected' : '' ?>>Dijual</option>
                <option value="dilelang" <?= $product['status_jual'] == 'dilelang' ? 'selected' : '' ?>>Dilelang</option>
            </select>
        </div>
    </div>

    <button type="submit" class="btn btn-save">Update</button>
    <a href="<?= base_url('admin/products') ?>" class="btn btn-cancel">Batal</a>
</form>
<?= $this->endSection() ?>