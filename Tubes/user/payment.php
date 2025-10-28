<?php
session_start();
include '../koneksi.php';

<<<<<<< HEAD
// --- Validasi item terpilih dari cart.php ---
=======
// --- VALIDASI ITEM YANG DIPILIH DARI CART ---
>>>>>>> f56171cc7fe3b94ce37fb32765b3e529fa3a696c
if (!isset($_POST['selected_items']) || empty($_POST['selected_items'])) {
    $_SESSION['message'] = 'Pilih setidaknya satu barang untuk checkout.';
    header('Location: cart.php');
    exit();
}

<<<<<<< HEAD
// --- Siapkan data dasar ---
$selected_cart_ids = array_map('intval', $_POST['selected_items']);
$in_clause = implode(',', $selected_cart_ids);

$voucher_discount = $_SESSION['voucher_discount'] ?? 0;
$voucher_code     = $_SESSION['voucher_code'] ?? null;
$customer_id      = $_SESSION['kd_cs'] ?? null;

// --- Ambil item yang dipilih ---
$query = "
    SELECT c.*, p.nama_produk, p.harga, p.link_gambar
    FROM carts c
    JOIN products p ON c.product_id = p.product_id
    WHERE c.customer_id = '" . mysqli_real_escape_string($conn, $customer_id) . "'
      AND c.cart_id IN ($in_clause)
";
$result   = mysqli_query($conn, $query);
$subtotal = 0;

$items = [];
while ($row = mysqli_fetch_assoc($result)) {
    $row['item_subtotal'] = (int)$row['harga'] * (int)$row['jumlah_barang'];
    $subtotal += $row['item_subtotal'];
    $items[] = $row;
}

// --- Hitung total dasar (tanpa ongkir; ongkir di-apply setelah pilih layanan) ---
$base_total  = max(0, $subtotal - (int)$voucher_discount);
$ongkir_init = 0; // default sebelum pilih layanan
$grand_total = $base_total + $ongkir_init; // digunakan awal (mis. QRIS sebelum layanan dipilih)

// --- Ambil daftar kurir ---
$current_courier = $_REQUEST['code_courier'] ?? ($_SESSION['checkout_courier'] ?? '');
if (!preg_match('/^[a-z0-9_\-]*$/i', $current_courier)) $current_courier = '';

$kurir_res = mysqli_query($conn, "SELECT code_courier, nama_kurir FROM courier ORDER BY code_courier ASC");

// --- Ambil next order id untuk prefix QR ---
$order_query   = mysqli_query($conn, "SELECT MAX(order_id) AS last_id FROM orders");
$order_data    = mysqli_fetch_assoc($order_query);
$next_order_id = (int)($order_data['last_id'] ?? 0) + 1;
=======
// Ambil array cart_id yang dipilih
$selected_cart_ids = $_POST['selected_items'];
// Ubah array jadi string aman buat SQL (misal: 1,5,7)
$in_clause = implode(',', array_map('intval', $selected_cart_ids));

// Ambil data voucher dari session (di-set waktu user apply voucher di cart)
$voucher_code        = $_SESSION['voucher_code']            ?? null;   // contoh: STYRKIKUZO
$voucher_tipe        = $_SESSION['voucher_tipe']            ?? null;   // 'persen' atau 'rupiah'
$voucher_rp          = $_SESSION['voucher_nilai_rupiah']    ?? 0;      // nominal potongan rupiah
$voucher_pct         = $_SESSION['voucher_nilai_persen']    ?? 0;      // angka persen, ex 10
// catatan: $_SESSION['voucher_discount'] lama kamu gak dipake lagi,
// karena kita akan hitung ulang diskon beneran di sini supaya akurat

// Normal page display
$customer_id = $_SESSION['kd_cs'];

// Query item hanya yg dipilih
$query = "SELECT carts.*, products.nama_produk, products.harga, products.link_gambar 
          FROM carts 
          JOIN products ON carts.product_id = products.product_id 
          WHERE carts.customer_id = '$customer_id' 
            AND carts.cart_id IN ($in_clause)";
$result = mysqli_query($conn, $query);

// Hitung subtotal
$subtotal = 0;
$tmp_rows = []; // simpen dulu biar nanti bisa diprint lagi di tabel tanpa panggil query ulang
while ($row = mysqli_fetch_assoc($result)) {
    $tmp_rows[] = $row;
    $harga = $row['harga'];
    $item_subtotal = $harga * $row['jumlah_barang'];
    $subtotal += $item_subtotal;
}

// Hitung diskon voucher
$voucher_discount = 0;
if ($voucher_code && $voucher_tipe === 'persen') {
    // diskon persen, misal 10%
    $voucher_discount = $subtotal * ($voucher_pct / 100);
} elseif ($voucher_code && $voucher_tipe === 'rupiah') {
    // diskon nominal langsung
    $voucher_discount = $voucher_rp;
}

// pastikan diskon tidak melebihi subtotal
if ($voucher_discount > $subtotal) {
    $voucher_discount = $subtotal;
}

// Hitung ongkir 1%
$ongkir = $subtotal * 0.01;

// Hitung grand total
$grand_total = ($subtotal - $voucher_discount) + $ongkir;
if ($grand_total < 0) {
    $grand_total = 0;
}

>>>>>>> f56171cc7fe3b94ce37fb32765b3e529fa3a696c
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

<<<<<<< HEAD
<body class="container mt-4">
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
                <?php foreach ($items as $row): ?>
                    <tr>
                        <td><img src="<?= htmlspecialchars($row['link_gambar']) ?>" width="80" alt=""></td>
                        <td><?= htmlspecialchars($row['nama_produk']) ?></td>
                        <td><?= (int)$row['jumlah_barang'] ?></td>
                        <td>Rp <?= number_format((int)$row['harga'], 0, ',', '.') ?></td>
                        <td>Rp <?= number_format((int)$row['item_subtotal'], 0, ',', '.') ?></td>
                    </tr>
                <?php endforeach; ?>
=======
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

                    <?php foreach ($tmp_rows as $row): 
                        $harga = $row['harga'];
                        $item_subtotal = $row['harga'] * $row['jumlah_barang'];
                    ?>
                        <tr>
                            <td><img src="<?= $row['link_gambar']; ?>" width="80"></td>
                            <td><?= $row['nama_produk']; ?></td>
                            <td><?= $row['jumlah_barang']; ?></td>
                            <td>Rp <?= number_format($harga, 0, ',', '.'); ?></td>
                            <td>Rp <?= number_format($item_subtotal, 0, ',', '.'); ?></td>
                        </tr>
                    <?php endforeach; ?>

                    <?php if ($voucher_discount > 0): ?>
                        <tr>
                            <td colspan="4" class="text-end text-success">
                                <strong>
                                    Diskon (<?= htmlspecialchars($voucher_code); ?>
                                    <?php if ($voucher_tipe === 'persen'): ?>
                                        - <?= (int)$voucher_pct; ?>%
                                    <?php endif; ?>)
                                </strong>
                            </td>
                            <td class="text-success">
                                <strong>- Rp <?= number_format($voucher_discount, 0, ',', '.'); ?></strong>
                            </td>
                        </tr>
                    <?php endif; ?>
>>>>>>> f56171cc7fe3b94ce37fb32765b3e529fa3a696c

                    <tr>
<<<<<<< HEAD
                        <td colspan="4" class="text-end text-success">
                            <strong>Diskon (<?= htmlspecialchars($voucher_code) ?>)</strong>
                        </td>
                        <td class="text-success">
                            <strong>- Rp <?= number_format((int)$voucher_discount, 0, ',', '.') ?></strong>
                        </td>
=======
                        <td colspan="4" class="text-end"><strong>Ongkir (1%)</strong></td>
                        <td>Rp <?= number_format($ongkir, 0, ',', '.'); ?></td>
>>>>>>> f56171cc7fe3b94ce37fb32765b3e529fa3a696c
                    </tr>

<<<<<<< HEAD
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
                                    <?php endwhile;
                                    mysqli_free_result($kurir_res); ?>
                                <?php else: ?>
                                    <option disabled>Gagal load data kurir</option>
                                <?php endif; ?>
                            </select>

                            <div id="shippingServices" class="mt-2"></div>
                        </form>
                    </td>
                </tr>
                <tr>
                    <td colspan="4" class="text-end"><strong>Ongkir</strong></td>
                    <td id="ongkirCell">Rp <?= number_format($ongkir_init, 0, ',', '.') ?></td>
                </tr>
                <tr class="fw-bold table-group-divider" id="grandRow">
                    <td colspan="4" class="text-end"><strong>Total</strong></td>
                    <td><strong id="grandTotalCell">Rp <?= number_format($grand_total, 0, ',', '.') ?></strong></td>
                </tr>
            </tbody>
        </table>
    </div>
=======
                    <tr class="fw-bold table-group-divider">
                        <td colspan="4" class="text-end"><strong>Total</strong></td>
                        <td><strong>Rp <?= number_format($grand_total, 0, ',', '.'); ?></strong></td>
                    </tr>
                </tbody>
            </table>
        </div>
>>>>>>> f56171cc7fe3b94ce37fb32765b3e529fa3a696c

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

<<<<<<< HEAD
    <script>
        // --- Data dasar dari PHP ---
        const selectedItems = <?= json_encode($selected_cart_ids) ?>;
        const baseSubtotal = <?= (int)$subtotal ?>;
        const voucherDiscount = <?= (int)$voucher_discount ?>;
        const baseTotal = Math.max(0, baseSubtotal - voucherDiscount); // tanpa ongkir

        // --- Elemen yang sering dipakai ---
        const courierSelect = document.getElementById('code_courier');
        const svcBox = document.getElementById('shippingServices');
        const ongkirCell = document.getElementById('ongkirCell');
        const grandCell = document.getElementById('grandTotalCell');

        // --- Helper update tampilan total ---
        function updateTotals(cost = 0) {
            const ongkir = Number(cost) || 0;
            const grand = Math.max(0, baseTotal + ongkir);
            if (ongkirCell) ongkirCell.textContent = 'Rp ' + ongkir.toLocaleString('id-ID');
            if (grandCell) grandCell.textContent = 'Rp ' + grand.toLocaleString('id-ID');
        }

        // set awal
        updateTotals(0);

        // --- Load layanan berdasarkan kurir ---
        async function loadServices(courier) {
            if (!courier) return;

            const formData = new FormData();
            selectedItems.forEach(id => formData.append('selected_items[]', id));
            formData.append('code_courier', courier);

            svcBox.textContent = 'Menghitung ongkir...';
            updateTotals(0);

            try {
                const res = await fetch('./rajaongkir/calc_ongkir.php', {
                    method: 'POST',
                    body: formData
                });
                // Antisipasi response non-JSON
                const text = await res.text();
                let data;
                try {
                    data = JSON.parse(text);
                } catch (e) {
                    throw new Error('Respon tidak valid dari RajaOngkir (bukan JSON).');
                }

                if (!data.success || !Array.isArray(data.services) || data.services.length === 0) {
                    svcBox.innerHTML = `<div class="text-danger">Gagal: ${data.message || 'Tidak ada layanan.'}</div>`;
                    updateTotals(0);
                    return;
                }

                // Render pilihan layanan
                svcBox.innerHTML = `
                    <label class="form-label">Pilih Layanan</label>
                    <select id="serviceSelect" class="form-select">
                        ${data.services.map(s => `
                            <option value="${s.service}" data-cost="${s.cost}">
                                ${String(s.courier || courier).toUpperCase()} - ${s.service}
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

        courierSelect?.addEventListener('change', (e) => loadServices(e.target.value));
        <?php if (!empty($current_courier)): ?>
            if (courierSelect && courierSelect.value) loadServices(courierSelect.value);
        <?php endif; ?>
        let qrTimer;
        let qrContent = "";
        const fixedOrderID = "STYRK_ORDER<?= $next_order_id ?>_";

        function generateQRContent() {
            const randomCode = Math.floor(Math.random() * 900) + 100;
            return encodeURIComponent(fixedOrderID + randomCode);
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

        function hiddenSelectedInputs() {
            return selectedItems.map(id => `<input type="hidden" name="selected_items[]" value="${id}">`).join('');
        }
=======
        <script>
            let qrTimer,
                qrContent = "",
                paymentChecked = false;

            // total akhir yang harus dibayar user
            const grandTotal = <?= $grand_total ?>;
>>>>>>> f56171cc7fe3b94ce37fb32765b3e529fa3a696c

            // data cart_id yang dipilih (biar bisa dikirim lagi pas submit)
            const selectedItems = <?= json_encode($selected_cart_ids); ?>;

            function mulaiPembayaran() {
                const metode = document.querySelector('input[name="metode"]:checked');
                const container = document.getElementById("paymentContainer");

<<<<<<< HEAD
            // Ambil angka total yang sedang tampil (supaya sinkron dengan ongkir terakhir yang dipilih)
            const totalText = (document.getElementById('grandTotalCell')?.textContent || "").replace(/[^\d]/g, '');
            const currentGrand = totalText ? parseInt(totalText, 10) : <?= (int)$grand_total ?>;

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
    </script>

    <?php include 'footer.php'; ?>
</body>
=======
                if (!metode) {
                    alert("Silakan pilih metode pembayaran terlebih dahulu.");
                    return;
                }
>>>>>>> f56171cc7fe3b94ce37fb32765b3e529fa3a696c

                // hidden input untuk item2 terpilih
                let hiddenInputs = '';
                selectedItems.forEach(id => {
                    hiddenInputs += `<input type="hidden" name="selected_items[]" value="\${id}">`;
                });

                let html = "";

                if (metode.value === "QRIS") {
                    qrContent = generateQRContent();
                    html += `
                        <div class="payment-box">
                            <h5>QRIS</h5>
                            <img id="qrImage" src="https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=\${qrContent}" alt="QRIS"><br>
                            <div class="qris-timer" id="timer">02:00</div>
                            <form action="checkout.php" method="post">
                                \${hiddenInputs}
                                <input type="hidden" name="metode" value="QRIS">
                                <input type="hidden" name="total" value="${grandTotal}">
                                <input type="hidden" name="kode_transaksi" value="\${qrContent}">
                                <button type="submit" class="btn btn-primary mt-2">Cek Pembayaran</button>
                            </form>
                        </div>`;
                    container.innerHTML = html;
                    startQRISTimer();
                } else if (metode.value === "Transfer") {
                    html += `
                        <div class="payment-box">
                            <h5>Transfer Bank</h5>
                            <p>Silakan transfer ke rekening:</p>
                            <p><strong>BANK BCA 1234567890 a.n STYRK INDUSTRIES</strong></p>
                            <form action="checkout.php" method="post" enctype="multipart/form-data">
                                \${hiddenInputs}
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
                        </div>`;
                    container.innerHTML = html;
                }
            }

            <?php
            $order_query = mysqli_query($conn, "SELECT MAX(order_id) AS last_id FROM orders");
            $order_data = mysqli_fetch_assoc($order_query);
            $next_order_id = ($order_data['last_id'] ?? 0) + 1;
            ?>
            const fixedOrderID = "STYRK_ORDER<?= $next_order_id ?>_";

            function generateQRContent() {
                const randomCode = Math.floor(Math.random() * 900) + 100;
                return encodeURIComponent(fixedOrderID + randomCode);
            }

            function startQRISTimer() {
                clearInterval(qrTimer);
                paymentChecked = false;
                let duration = 120;
                const timerDisplay = document.getElementById("timer");
                qrTimer = setInterval(() => {
                    const minutes = Math.floor(duration / 60);
                    const seconds = duration % 60;
                    timerDisplay.textContent =
                        (minutes < 10 ? "0" : "") + minutes + ":" +
                        (seconds < 10 ? "0" : "") + seconds;
                    if (--duration < 0) {
                        clearInterval(qrTimer);
                        qrContent = generateQRContent();
                        document.getElementById("qrImage").src =
                            "https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=" + qrContent;
                        const kodeTransaksiInput = document.querySelector("input[name='kode_transaksi']");
                        if (kodeTransaksiInput) {
                            kodeTransaksiInput.value = qrContent;
                        }
                        startQRISTimer();
                        alert("QR baru telah digenerate karena timeout.");
                    }
                }, 1000);
            }
        </script>
    </div>

    <?php include 'footer.php'; ?>

</body>
</html>
