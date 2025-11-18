<?= $this->extend('admin/layout/main') ?>

<?= $this->section('content') ?>
<h2>Tambah Produk</h2>

<form action="<?= base_url('admin/products/store') ?>" method="post">
    <div class="form-row">
        <div class="form-group">
            <label>ID Produk</label>
            <input type="text" name="product_id" required>
        </div>
        <div class="form-group">
            <label>Nama Produk</label>
            <input type="text" name="nama_produk" required>
        </div>
    </div>

    <div class="form-group">
        <label>Deskripsi</label>
        <textarea name="deskripsi_produk" required></textarea>
    </div>

    <div class="form-row">
        <div class="form-group">
            <label>Harga</label>
            <input type="number" step="0.01" name="harga" required>
        </div>
        <div class="form-group">
            <label>Stok</label>
            <input type="number" name="stok" required>
        </div>
    </div>

    <div class="form-group">
        <label>Link Gambar</label>
        <input type="text" name="link_gambar" placeholder="https://...">
    </div>

    <div class="form-group">
        <label>Kategori</label>
        <select name="category_id" required>
            <option value="">Pilih Kategori</option>
            <?php foreach ($categories as $c): ?>
                <option value="<?= $c['category_id'] ?>"><?= esc($c['category']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>

    <button type="submit" class="btn btn-save">Simpan</button>
    <a href="<?= base_url('admin/products') ?>" class="btn btn-cancel">Batal</a>
</form>
<?= $this->endSection() ?>
