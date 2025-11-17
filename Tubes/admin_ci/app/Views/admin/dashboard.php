<?= $this->extend('admin/layout/main') ?>

<?= $this->section('content') ?>

<h2 style="color:#ffd60a;">Welcome, <?= esc(session()->get('admin_username') ?? 'Admin'); ?>!</h2>
<p></p>

<div class="row" style="display:flex; flex-wrap:wrap; gap:20px; margin-top:30px;">
    <div class="card" style="flex:1; min-width:250px; text-align:center;">
        <h3>Total Produk</h3>
        <p style="font-size:2rem; font-weight:bold;"><?= esc($total_products ?? 0) ?></p>
    </div>

    <div class="card" style="flex:1; min-width:250px; text-align:center;">
        <h3>Total Customer</h3>
        <p style="font-size:2rem; font-weight:bold;"><?= esc($total_customers ?? 0) ?></p>
    </div>

    <div class="card" style="flex:1; min-width:250px; text-align:center;">
        <h3>Total Orders</h3>
        <p style="font-size:2rem; font-weight:bold;"><?= esc($total_orders ?? 0) ?></p>
    </div>
</div>

<!-- Placeholder untuk grafik pendapatan per kategori -->
<div class="card" style="margin-top:40px;">
    <h3></h3>
    <p>Lorem ipsum</p>
    <div style="height:300px; background-color:#161b22; border-radius:8px; display:flex; align-items:center; justify-content:center; color:#888;">
        <em>Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat.</em>
    </div>
</div>

<?= $this->endSection() ?>
