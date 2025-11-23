<?php
// ==== Gunakan header dalam mode partial (navbar saja, tanpa <html><head> dari header) ====
define('HEADER_PARTIAL', true);
require_once __DIR__ . '/header.php';

// ==== Cek Sesi Login ====
if (!isset($_SESSION['kd_cs'])) {
    echo "<script>alert('Anda Harus Login Terlebih Dahulu'); window.location.href='produk.php';</script>";
    exit();
}

// ==== Alert dari Session (aman via json_encode) ====
if (isset($_SESSION['message'])) {
    $msg = json_encode($_SESSION['message'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    echo "<script>alert($msg);</script>";
    unset($_SESSION['message']);
}
if (isset($_SESSION['alert'])) {
    $alt = json_encode($_SESSION['alert'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    echo "<script>alert($alt);</script>";
    unset($_SESSION['alert']);
}

// ==== Ambil data keranjang (Prepared Statement) ====
$customer_id = (int)($_SESSION['kd_cs'] ?? 0);
$rows = [];
$subtotal = 0.0;

$sql = "SELECT p.*, c.jumlah_barang, c.cart_id
        FROM carts c
        JOIN products p ON c.product_id = p.product_id
        WHERE c.customer_id = ?";
$stmt = mysqli_prepare($conn, $sql);
if (!$stmt) die("Query error: " . mysqli_error($conn));
mysqli_stmt_bind_param($stmt, 'i', $customer_id);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);

if ($res && mysqli_num_rows($res) > 0) {
    while ($row = mysqli_fetch_assoc($res)) {
        $rows[] = $row;
        $harga_numerik = (float)$row['harga'];
        $subtotal += $harga_numerik * (int)$row['jumlah_barang'];
    }
}
mysqli_stmt_close($stmt);

// ==== Voucher session data ====
$voucher_code   = $_SESSION['voucher_code']         ?? null;
$voucher_tipe   = $_SESSION['voucher_tipe']         ?? null;   // 'persen' | 'rupiah' | null
$voucher_persen = (float)($_SESSION['voucher_nilai_persen'] ?? 0);
$voucher_rupiah = (float)($_SESSION['voucher_nilai_rupiah'] ?? 0);

// ==== Hitung diskon awal (server-side) ====
$voucher_discount = 0.0;
if ($voucher_code && $voucher_tipe === 'persen') {
    $voucher_discount = $subtotal * ($voucher_persen / 100);
} elseif ($voucher_code && $voucher_tipe === 'rupiah') {
    $voucher_discount = $voucher_rupiah;
}
if ($voucher_discount > $subtotal) $voucher_discount = $subtotal;

$total_setelah_diskon = $subtotal - $voucher_discount;
if ($total_setelah_diskon < 0) $total_setelah_diskon = 0;
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Styrk Industries - Keranjang Belanja</title>

    <!-- CSS Global (urut: Bootstrap -> header.css -> cart.css) -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="./css/cart.css">

    <!-- Icons & Font -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.5.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">

    <style>
        .checkout-wrap {
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        @media (min-width:768px) {
            .checkout-wrap {
                flex-direction: row;
                justify-content: space-between;
                align-items: flex-start;
            }

            .voucher-col {
                flex: 1;
            }

            .pay-col {
                min-width: 250px;
                text-align: right;
                display: flex;
                justify-content: flex-end;
            }
        }

        /* (opsional) samakan container jika cart.css butuh scope tertentu */
        body {
            font-family: 'Poppins', sans-serif;
        }
    </style>
</head>

<body>

    <div class="container_cart mt-4">
        <h2 class="mb-4">Your Shopping Cart</h2>

        <?php if (!empty($rows)): ?>
            <form action="payment.php" method="POST" id="cart-form">
                <table class="table table-bordered">
                    <thead class="table-dark">
                        <tr>
                            <th style="width:5%;"><input type="checkbox" id="select-all" checked></th>
                            <th>Product</th>
                            <th>Price</th>
                            <th>Quantity</th>
                            <th>Total</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($rows as $row):
                            $harga_numerik   = (float)$row['harga'];
                            $qty             = (int)$row['jumlah_barang'];
                            $total_per_item  = $harga_numerik * $qty;
                        ?>
                            <tr>
                                <td>
                                    <input
                                        type="checkbox"
                                        class="item-checkbox"
                                        name="selected_items[]"
                                        value="<?= (int)$row['cart_id']; ?>"
                                        data-price="<?= htmlspecialchars((string)$harga_numerik, ENT_QUOTES); ?>"
                                        data-quantity="<?= (int)$qty; ?>"
                                        checked>
                                </td>
                                <td><?= htmlspecialchars($row['nama_produk']); ?></td>
                                <td>Rp <?= number_format($harga_numerik, 0, ',', '.'); ?></td>
                                <td>
                                    <div class="input-group" style="width:120px;">
                                        <button type="button" class="btn btn-outline-secondary btn-sm quantity-minus" data-id="<?= (int)$row['cart_id']; ?>">
                                            <span>-</span>
                                        </button>
                                        <input type="number" name="quantity[<?= (int)$row['cart_id']; ?>]" value="<?= (int)$qty; ?>" min="1" class="form-control text-center" readonly>
                                        <button type="button" class="btn btn-outline-secondary btn-sm quantity-plus" data-id="<?= (int)$row['cart_id']; ?>">
                                            <span>+</span>
                                        </button>
                                    </div>
                                </td>
                                <td>Rp <?= number_format($total_per_item, 0, ',', '.'); ?></td>
                                <td>
                                    <a href="remove_from_cart.php?cart_id=<?= (int)$row['cart_id']; ?>" class="btn btn-danger btn-sm">
                                        <i class="bi bi-trash"></i> Remove
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr class="table-group-divider fw-bold">
                            <th colspan="4" class="text-end">
                                Total
                                <?php if ($voucher_code): ?>
                                    <br>
                                    <small style="font-weight:normal;">
                                        (Voucher:
                                        <?= htmlspecialchars($voucher_code); ?>
                                        <?php if ($voucher_tipe === 'persen'): ?>
                                            - <?= (int)$voucher_persen; ?>%
                                        <?php endif; ?>
                                        <a href="remove_voucher.php" style="color:red; text-decoration:none; font-size:12px;">Remove</a>)
                                    </small>
                                <?php endif; ?>
                            </th>
                            <th colspan="2"
                                id="cart-total-display"
                                data-voucher-code="<?= htmlspecialchars($voucher_code ?? '', ENT_QUOTES); ?>"
                                data-voucher-tipe="<?= htmlspecialchars($voucher_tipe ?? '', ENT_QUOTES); ?>"
                                data-voucher-pct="<?= htmlspecialchars((string)$voucher_persen, ENT_QUOTES); ?>"
                                data-voucher-rp="<?= htmlspecialchars((string)$voucher_rupiah, ENT_QUOTES); ?>">
                                Rp <?= number_format($total_setelah_diskon, 0, ',', '.'); ?>
                                <?php if ($voucher_discount > 0): ?>
                                    <br>
                                    <small style="font-weight:normal; color:#28a745;">
                                        (Savings: Rp <?= number_format($voucher_discount, 0, ',', '.'); ?>)
                                    </small>
                                <?php endif; ?>
                            </th>
                        </tr>
                    </tfoot>
                </table>
            </form>

            <div class="checkout-wrap mt-4">
                <div class="voucher-col mb-3 mb-md-0">
                    <form action="apply_voucher.php" method="POST" class="d-flex flex-column flex-sm-row align-items-sm-start align-items-center">
                        <input type="text" name="kode_voucher" class="form-control me-sm-2 mb-2 mb-sm-0" placeholder="Masukkan Kode Voucher" required style="max-width:300px;">
                        <button type="submit" class="btn btn-primary">Use</button>
                    </form>
                </div>

                <div class="pay-col">
                    <button
                        type="button"
                        class="btn btn-success d-inline-flex align-items-center gap-2 px-4 py-2 fw-semibold"
                        id="proceed-payment-btn"
                        style="min-width:230px;"
                        onclick="proceedPayment()">
                        <i class="bi bi-credit-card"></i>
                        <span>Lanjut ke Pembayaran</span>
                    </button>


                </div>
            </div>

        <?php else: ?>
            <div class="alert alert-info">
                Your cart is empty.
                <a href="produk.php">Browse Products</a>
            </div>
        <?php endif; ?>
    </div>

    <!-- WA FLOATING BUTTON (kalau biasanya ada di header full, taruh di sini untuk halaman ini) -->
    <a href="https://wa.me/6281223830598?text=Halo%20Styrk%20Industries%2C%20saya%20mau%20bertanya..."
        class="floating-wa" target="_blank"
        style="position: fixed; bottom: 25px; right: 25px; background-color: #25D366; color: #fff; width: 60px; height: 60px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 30px; box-shadow: 0 4px 10px rgba(0,0,0,0.2); z-index: 1000; transition: transform 0.2s ease;">
        <i class="bi bi-whatsapp"></i>
    </a>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Ubah Qty (+/-), update ke DB, reload total -->
    <script>
        document.querySelectorAll('.quantity-plus, .quantity-minus').forEach(button => {
            button.addEventListener('click', function() {
                const input = this.parentElement.querySelector('input');
                let current = parseInt(input.value || '1', 10);
                const cartId = this.dataset.id;

                if (this.classList.contains('quantity-plus')) {
                    current += 1;
                } else if (current > 1) {
                    current -= 1;
                }
                input.value = current;

                fetch('update_cart.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded'
                        },
                        body: `cart_id=${encodeURIComponent(cartId)}&quantity=${encodeURIComponent(current)}`
                    })
                    .then(r => r.text())
                    .then(() => {
                        location.reload();
                    })
                    .catch(err => console.error('Error:', err));
            });
        });
    </script>

    <!-- Hitung ulang total pas user uncheck item + guard submit -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('cart-form');
            const selectAll = document.getElementById('select-all');
            const itemCheckboxes = document.querySelectorAll('.item-checkbox');
            const totalDisplay = document.getElementById('cart-total-display');
            const payBtn = document.getElementById('proceed-payment-btn');

            const activeVoucherCode = totalDisplay.dataset.voucherCode || '';
            const activeVoucherType = totalDisplay.dataset.voucherTipe || '';
            const activeVoucherPct = parseFloat(totalDisplay.dataset.voucherPct || '0') || 0;
            const activeVoucherRp = parseFloat(totalDisplay.dataset.voucherRp || '0') || 0;

            function formatRupiah(number) {
                return new Intl.NumberFormat('id-ID', {
                    style: 'currency',
                    currency: 'IDR',
                    minimumFractionDigits: 0
                }).format(number);
            }

            function hitungSubtotalTerpilih() {
                let sum = 0;
                itemCheckboxes.forEach(cb => {
                    if (cb.checked) {
                        const price = parseFloat(cb.dataset.price || '0') || 0;
                        const qty = parseInt(cb.dataset.quantity || '0', 10) || 0;
                        sum += price * qty;
                    }
                });
                return sum;
            }

            function getVoucherDiscount(subtotalNow) {
                if (!activeVoucherCode) return 0;
                if (activeVoucherType === 'persen') return subtotalNow * (activeVoucherPct / 100);
                if (activeVoucherType === 'rupiah') return activeVoucherRp;
                return 0;
            }

            function updateUI() {
                const newSubtotal = hitungSubtotalTerpilih();
                let discountNow = getVoucherDiscount(newSubtotal);
                if (discountNow > newSubtotal) discountNow = newSubtotal;

                const finalTotal = Math.max(0, newSubtotal - discountNow);
                let html = formatRupiah(finalTotal);

                if (discountNow > 0) {
                    html += `<br><small style="font-weight:normal; color:#28a745;">(Savings: ${formatRupiah(discountNow)})</small>`;
                }
                totalDisplay.innerHTML = html;

                const allChecked = Array.from(itemCheckboxes).every(cb => cb.checked);
                selectAll.checked = allChecked;

                const anyChecked = Array.from(itemCheckboxes).some(cb => cb.checked);
                payBtn.disabled = !anyChecked;
            }

            selectAll.addEventListener('change', function() {
                itemCheckboxes.forEach(cb => {
                    cb.checked = selectAll.checked;
                });
                updateUI();
            });

            itemCheckboxes.forEach(cb => cb.addEventListener('change', updateUI));

            form.addEventListener('submit', function(e) {
                const anyChecked = Array.from(itemCheckboxes).some(cb => cb.checked);
                if (!anyChecked) {
                    e.preventDefault();
                    alert('Pilih setidaknya satu barang untuk checkout.');
                    return false;
                }
            });

            updateUI();
        });
    </script>

    <script>
        function proceedPayment() {
            const form = document.getElementById('cart-form');
            if (!form) {
                alert('Form cart tidak ditemukan.');
                return;
            }

            // ambil checkbox TERBARU (jangan pake cache)
            const boxes = Array.from(document.querySelectorAll('input.item-checkbox'));
            const checked = boxes.filter(b => b.checked);

            if (checked.length === 0) {
                alert('Pilih setidaknya satu barang untuk checkout.');
                return;
            }

            // bersihin selected_items[] di form biar gak dobel
            form.querySelectorAll('input[name="selected_items[]"]').forEach(x => x.remove());

            // inject hidden dari yang dicentang (source of truth)
            checked.forEach(b => {
                const h = document.createElement('input');
                h.type = 'hidden';
                h.name = 'selected_items[]';
                h.value = b.value; // cart_id
                form.appendChild(h);
            });

            // submit langsung
            form.submit();
        }
    </script>


</body>

</html>