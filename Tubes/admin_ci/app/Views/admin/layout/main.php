<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= esc($title ?? 'Admin Dashboard') ?></title>
    <link rel="stylesheet" href="<?= base_url('assets/css/style.css') ?>">
</head>
<body>

    <header>
        <div style="display: flex; justify-content: space-between; align-items: center;">
            <h1 style="color: #ffd60a; margin: 0;">STYRK INDUSTRIES</h1>
            <nav>
                <a href="<?= base_url('admin') ?>">Dashboard</a>
                <a href="<?= base_url('admin/products') ?>">Produk</a>
                <a href="<?= base_url('admin/orders') ?>">Order</a>
                <a href="<?= base_url('admin/customer') ?>">Customer</a>
                <a href="<?= base_url('admin/struk') ?>">Struk</a>
            </nav>
        </div>
    </header>

    <main>
        <div class="container">
            <?= $this->renderSection('content') ?>
        </div>
    </main>

    <footer style="text-align:center; padding:16px; background-color:#1c2541; margin-top:40px;">
        <p style="color:#ccc; font-size:14px;">
            © <?= date('Y') ?> STYRK Industries Admin Panel
        </p>
    </footer>

</body>
</html>
