<?php
session_start();
include '../koneksi.php';

// --- VALIDASI ITEM YANG DIPILIH DARI CART ---
if (!isset($_POST['selected_items']) || empty($_POST['selected_items'])) {
    $_SESSION['message'] = 'Pilih setidaknya satu barang untuk checkout.';
    header('Location: cart.php');
    exit();
}

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

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Payment Page</title>
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

                    <tr>
                        <td colspan="4" class="text-end"><strong>Ongkir (1%)</strong></td>
                        <td>Rp <?= number_format($ongkir, 0, ',', '.'); ?></td>
                    </tr>

                    <tr class="fw-bold table-group-divider">
                        <td colspan="4" class="text-end"><strong>Total</strong></td>
                        <td><strong>Rp <?= number_format($grand_total, 0, ',', '.'); ?></strong></td>
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
            let qrTimer,
                qrContent = "",
                paymentChecked = false;

            // total akhir yang harus dibayar user
            const grandTotal = <?= $grand_total ?>;

            // data cart_id yang dipilih (biar bisa dikirim lagi pas submit)
            const selectedItems = <?= json_encode($selected_cart_ids); ?>;

            function mulaiPembayaran() {
                const metode = document.querySelector('input[name="metode"]:checked');
                const container = document.getElementById("paymentContainer");

                if (!metode) {
                    alert("Silakan pilih metode pembayaran terlebih dahulu.");
                    return;
                }

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
