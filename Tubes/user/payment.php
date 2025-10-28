<?php
session_start();
include '../koneksi.php';

// === Validasi item yang dipilih ===
if (!isset($_POST['selected_items']) || empty($_POST['selected_items'])) {
    $_SESSION['message'] = 'Pilih setidaknya satu barang untuk checkout.';
    header('Location: cart.php');
    exit();
}
$selected_cart_ids = array_map('intval', $_POST['selected_items']);
$in_clause         = implode(',', $selected_cart_ids);

// === Voucher dari session (versi terbaru) ===
$voucher_code = $_SESSION['voucher_code']         ?? null;  // ex: STYRKIKUZO
$voucher_tipe = $_SESSION['voucher_tipe']         ?? null;  // 'persen' | 'rupiah'
$voucher_rp   = (int)($_SESSION['voucher_nilai_rupiah']  ?? 0);
$voucher_pct  = (int)($_SESSION['voucher_nilai_persen']  ?? 0);

// === Customer ===
$customer_id = $_SESSION['kd_cs'] ?? null;

// === Ambil item yang dipilih ===
$query = "
    SELECT c.*, p.nama_produk, p.harga, p.link_gambar
    FROM carts c
    JOIN products p ON c.product_id = p.product_id
    WHERE c.customer_id = '".mysqli_real_escape_string($conn, $customer_id)."'
      AND c.cart_id IN ($in_clause)
";
$result   = mysqli_query($conn, $query);

$subtotal = 0;
$rows     = [];
while ($row = mysqli_fetch_assoc($result)) {
    $row['harga']         = (int)$row['harga'];
    $row['jumlah_barang'] = (int)$row['jumlah_barang'];
    $row['item_subtotal'] = $row['harga'] * $row['jumlah_barang'];
    $subtotal            += $row['item_subtotal'];
    $rows[]               = $row;
}

// === Hitung diskon voucher ===
$voucher_discount = 0;
if ($voucher_code && $voucher_tipe === 'persen') {
    $voucher_discount = $subtotal * ($voucher_pct / 100);
} elseif ($voucher_code && $voucher_tipe === 'rupiah') {
    $voucher_discount = $voucher_rp;
}
if ($voucher_discount > $subtotal) $voucher_discount = $subtotal;

// === Total dasar (tanpa ongkir; ongkir dari API setelah pilih layanan) ===
$base_total  = max(0, $subtotal - (int)$voucher_discount);
$init_ongkir = 0;
$grand_total = $base_total + $init_ongkir;

// === Daftar kurir ===
$current_courier = $_REQUEST['code_courier'] ?? ($_SESSION['checkout_courier'] ?? '');
if (!preg_match('/^[a-z0-9_\-]*$/i', $current_courier)) $current_courier = '';
$kurir_res = mysqli_query($conn, "SELECT code_courier, nama_kurir FROM courier ORDER BY code_courier ASC");

// === Prefix order untuk QRIS ===
$order_query   = mysqli_query($conn, "SELECT MAX(order_id) AS last_id FROM orders");
$order_data    = mysqli_fetch_assoc($order_query);
$next_order_id = (int)($order_data['last_id'] ?? 0) + 1;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Checkout - Payment</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="./css/payment.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="mt-4">
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="m-0">Checkout - Payment</h2>
        <a href="cart.php" class="btn btn-secondary">← Back to Cart</a>
    </div>

    <div class="mb-4">
        <h4>Barang yang akan Dibayar</h4>
        <table class="table table-bordered">
            <thead>
            <tr>
                <th>Gambar</th>
                <th>Nama</th>
                <th>Jumlah</th>
                <th>Harga</th>
                <th>Subtotal</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($rows as $row): ?>
                <tr>
                    <td><img src="<?= htmlspecialchars($row['link_gambar']) ?>" width="80" alt=""></td>
                    <td><?= htmlspecialchars($row['nama_produk']) ?></td>
                    <td><?= (int)$row['jumlah_barang'] ?></td>
                    <td>Rp <?= number_format((int)$row['harga'], 0, ',', '.') ?></td>
                    <td>Rp <?= number_format((int)$row['item_subtotal'], 0, ',', '.') ?></td>
                </tr>
            <?php endforeach; ?>

            <?php if ($voucher_discount > 0): ?>
                <tr>
                    <td colspan="4" class="text-end text-success">
                        <strong>
                            Diskon (<?= htmlspecialchars((string)$voucher_code) ?>
                            <?php if ($voucher_tipe === 'persen'): ?> - <?= (int)$voucher_pct; ?>%<?php endif; ?>)
                        </strong>
                    </td>
                    <td class="text-success"><strong>- Rp <?= number_format((int)$voucher_discount, 0, ',', '.') ?></strong></td>
                </tr>
            <?php endif; ?>

            <!-- Pilih Kurir -->
            <tr>
                <td colspan="1">Pilih Courier</td>
                <td colspan="4">
                    <form action="" method="post" id="courierForm" onsubmit="return false;">
                        <?php foreach ($selected_cart_ids as $cid): ?>
                            <input type="hidden" name="selected_items[]" value="<?= (int)$cid ?>">
                        <?php endforeach; ?>

                        <select name="code_courier" id="code_courier" class="form-select" required>
                            <option value="" disabled <?= $current_courier === '' ? 'selected' : '' ?>>-- Pilih Kurir --</option>
                            <?php if ($kurir_res): ?>
                                <?php while ($k = mysqli_fetch_assoc($kurir_res)): ?>
                                    <option value="<?= htmlspecialchars($k['code_courier']) ?>" <?= $current_courier === $k['code_courier'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($k['nama_kurir']) ?>
                                    </option>
                                <?php endwhile; mysqli_free_result($kurir_res); ?>
                            <?php else: ?>
                                <option disabled>Gagal load data kurir</option>
                            <?php endif; ?>
                        </select>

                        <div id="shippingServices" class="mt-2"></div>
                    </form>
                </td>
            </tr>

            <!-- Ringkasan Ongkir & Total -->
            <tr>
                <td colspan="4" class="text-end"><strong>Ongkir</strong></td>
                <td id="ongkirCell">Rp <?= number_format($init_ongkir, 0, ',', '.') ?></td>
            </tr>
            <tr class="fw-bold table-group-divider" id="grandRow">
                <td colspan="4" class="text-end"><strong>Total</strong></td>
                <td><strong id="grandTotalCell">Rp <?= number_format($grand_total, 0, ',', '.') ?></strong></td>
            </tr>
            </tbody>
        </table>
    </div>

    <div class="mb-4">
        <h4 class="mb-3 text-center">Pilih Metode Pembayaran</h4>
        <div class="payment-methods justify-content-center">
            <div class="payment-option">
                <input type="radio" name="metode" id="qris" value="QRIS" class="payment-input">
                <label for="qris" class="payment-label">
                    <div class="payment-content">
                        <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/d/d0/QR_code_for_mobile_English_Wikipedia.svg/1200px-QR_code_for_mobile_English_Wikipedia.svg.png" alt="QRIS" class="payment-icon">
                        <span>QRIS</span>
                    </div>
                </label>
            </div>
            <div class="payment-option">
                <input type="radio" name="metode" id="transfer" value="Transfer" class="payment-input" checked>
                <label for="transfer" class="payment-label">
                    <div class="payment-content">
                        <img src="./css/bca_logo.png" alt="Transfer Bank" class="payment-icon">
                        <span>Transfer Bank</span>
                    </div>
                </label>
            </div>
        </div>
    </div>

    <div id="paymentContainer"></div>

    <div class="text-center mb-4">
        <button class="btn btn-lg btn-warning" onclick="mulaiPembayaran()">Pay</button>
    </div>

    <script>
        // === Data dasar dari PHP ===
        const selectedItems   = <?= json_encode($selected_cart_ids) ?>;
        const baseSubtotal    = <?= (int)$subtotal ?>;
        const voucherDiscount = <?= (int)$voucher_discount ?>;
        const baseTotal       = Math.max(0, baseSubtotal - voucherDiscount); // tanpa ongkir

        // === Elemen DOM ===
        const courierSelect = document.getElementById('code_courier');
        const svcBox        = document.getElementById('shippingServices');
        const ongkirCell    = document.getElementById('ongkirCell');
        const grandCell     = document.getElementById('grandTotalCell');

        function updateTotals(cost = 0) {
            const ongkir = Number(cost) || 0;
            const grand  = Math.max(0, baseTotal + ongkir);
            if (ongkirCell) ongkirCell.textContent = 'Rp ' + ongkir.toLocaleString('id-ID');
            if (grandCell)  grandCell.textContent  = 'Rp ' + grand.toLocaleString('id-ID');
        }
        updateTotals(0);

        async function loadServices(courier) {
            if (!courier) return;

            const formData = new FormData();
            selectedItems.forEach(id => formData.append('selected_items[]', id));
            formData.append('code_courier', courier);

            svcBox.textContent = 'Menghitung ongkir...';
            updateTotals(0);

            try {
                const res  = await fetch('./rajaongkir/calc_ongkir.php', { method: 'POST', body: formData });
                const text = await res.text();
                let data;
                try { data = JSON.parse(text); } catch {
                    throw new Error('Respon tidak valid dari RajaOngkir (bukan JSON).');
                }

                if (!data.success || !Array.isArray(data.services) || data.services.length === 0) {
                    svcBox.innerHTML = `<div class="text-danger">Gagal: ${data.message || 'Tidak ada layanan.'}</div>`;
                    updateTotals(0);
                    return;
                }

                svcBox.innerHTML = `
                    <label class="form-label">Pilih Layanan</label>
                    <select id="serviceSelect" class="form-select">
                        ${data.services.map(s => `
                            <option value="${s.service}" data-cost="${s.cost}">
                                ${(s.courier || courier).toUpperCase()} - ${s.service}
                                ${s.etd ? `(ETD ${s.etd} hari)` : ''} - Rp ${Number(s.cost).toLocaleString('id-ID')}
                            </option>
                        `).join('')}
                    </select>
                    <small class="text-muted">Harga & ETD berdasarkan API.</small>
                `;

                const svc = document.getElementById('serviceSelect');
                function applyCost() {
                    const cost = parseInt(svc.selectedOptions[0]?.dataset.cost || '0', 10);
                    updateTotals(cost);
                }
                applyCost();
                svc.addEventListener('change', applyCost);

            } catch (err) {
                svcBox.innerHTML = `<div class="text-danger">Error koneksi: ${String(err.message || err)}</div>`;
                updateTotals(0);
            }
        }

        // Load layanan saat user pilih kurir / saat halaman punya pilihan dari session
        courierSelect?.addEventListener('change', (e) => loadServices(e.target.value));
        <?php if (!empty($current_courier)): ?>
        if (courierSelect && courierSelect.value) loadServices(courierSelect.value);
        <?php endif; ?>

        // =======================
        //         PEMBAYARAN
        // =======================
        let qrTimer;
        let qrContent = "";
        const fixedOrderID = "STYRK_ORDER<?= $next_order_id ?>_";

        function generateQRContent() {
            const randomCode = Math.floor(Math.random() * 900) + 100;
            return encodeURIComponent(fixedOrderID + randomCode);
        }

        function hiddenSelectedInputs() {
            return selectedItems.map(id => `<input type="hidden" name="selected_items[]" value="${id}">`).join('');
        }

        function mulaiPembayaran() {
            const metode    = document.querySelector('input[name="metode"]:checked');
            const container = document.getElementById("paymentContainer");
            if (!metode) { alert("Silakan pilih metode pembayaran terlebih dahulu."); return; }

            // Ambil total saat ini (sesuai ongkir terpilih)
            const currentGrand = (() => {
                const txt = (document.getElementById('grandTotalCell')?.textContent || "").replace(/[^\d]/g, '');
                return txt ? parseInt(txt, 10) : <?= (int)$grand_total ?>;
            })();

            let html = "";
            if (metode.value === "QRIS") {
                qrContent = generateQRContent();
                html = `
                    <div class="payment-box">
                        <h5>QRIS</h5>
                        <img id="qrImage" src="https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=${qrContent}" alt="QRIS"><br>
                        <div class="qris-timer" id="timer">02:00</div>
                        <form action="checkout.php" method="post">
                            ${hiddenSelectedInputs()}
                            <input type="hidden" name="metode" value="QRIS">
                            <input type="hidden" name="total" value="${currentGrand}">
                            <input type="hidden" name="kode_transaksi" value="${qrContent}">
                            <button type="submit" class="btn btn-primary mt-2">Cek Pembayaran</button>
                        </form>
                    </div>
                `;
                container.innerHTML = html;
                startQRISTimer();
            } else {
                html = `
                    <div class="payment-box">
                        <h5>Transfer Bank</h5>
                        <p>Silakan transfer ke rekening:</p>
                        <p><strong>BANK BCA 1234567890 a.n STYRK INDUSTRIES</strong></p>
                        <form action="checkout.php" method="post" enctype="multipart/form-data">
                            ${hiddenSelectedInputs()}
                            <input type="hidden" name="metode" value="Transfer">
                            <div class="mb-3">
                                <label for="bukti" class="form-label">Upload Bukti Transfer</label>
                                <input type="file" name="bukti" class="form-control" required accept="image/*">
                                <div class="form-text">Format: JPG, PNG (max 2MB)</div>
                            </div>
                            <button type="submit" name="pay_bank" class="btn btn-success w-100">
                                <i class="fas fa-upload me-2"></i> Upload & Cek Pembayaran
                            </button>
                        </form>
                    </div>
                `;
                container.innerHTML = html;
            }
        }

        function startQRISTimer() {
            clearInterval(qrTimer);
            let duration = 120; // 2 menit
            const timerDisplay = document.getElementById("timer");
            qrTimer = setInterval(() => {
                const minutes = Math.floor(duration / 60);
                const seconds = duration % 60;
                if (timerDisplay) {
                    timerDisplay.textContent =
                        (minutes < 10 ? "0" : "") + minutes + ":" +
                        (seconds < 10 ? "0" : "") + seconds;
                }
                if (--duration < 0) {
                    clearInterval(qrTimer);
                    // regenerate QR
                    qrContent = generateQRContent();
                    const qrImg = document.getElementById("qrImage");
                    if (qrImg) qrImg.src = "https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=" + qrContent;
                    const kodeTransaksiInput = document.querySelector("input[name='kode_transaksi']");
                    if (kodeTransaksiInput) kodeTransaksiInput.value = qrContent;
                    startQRISTimer();
                    alert("QR baru telah digenerate karena timeout.");
                }
            }, 1000);
        }
    </script>

    <?php include 'footer.php'; ?>
</div>
</body>
</html>
