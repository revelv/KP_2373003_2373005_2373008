<?= $this->extend('layouts/admin_template') ?>
<?= $this->section('content') ?>

<div class="container mt-4">
    <h3>Detail Produk</h3>
    <div class="card shadow-sm mt-3 p-4">
        <div class="row">
            <div class="col-md-4">
                <img src="<?= esc($product['link_gambar']) ?>" alt="Produk" class="img-fluid rounded">
            </div>
            <div class="col-md-8">
                <h4><?= esc($product['nama_produk']) ?></h4>
                <p><?= esc($product['deskripsi_produk']) ?></p>
                <p><strong>Harga:</strong> $<?= esc(number_format($product['harga'], 2)) ?></p>
                <p><strong>Stok:</strong> <?= esc($product['stok']) ?></p>
                <a href="<?= base_url('productadmin/edit/'.$product['product_id']) ?>" class="btn btn-warning">Edit</a>
                <a href="<?= base_url('productadmin') ?>" class="btn btn-secondary">Kembali</a>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>
