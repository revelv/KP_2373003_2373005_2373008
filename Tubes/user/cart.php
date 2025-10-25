<?php
include 'header.php';

// Cek Sesi Login
if (!isset($_SESSION['kd_cs'])) {
    header("Location: login.php");
    exit();
}

// Menampilkan pesan dari session
if (isset($_SESSION['message'])) {
    echo "<script>alert('" . $_SESSION['message'] . "');</script>";
    unset($_SESSION['message']);
}
if (isset($_SESSION['alert'])) {
    echo "<script>alert('" . $_SESSION['alert'] . "');</script>";
    unset($_SESSION['alert']);
}

// Ambil data keranjang
$customer_id = $_SESSION['kd_cs'];
$query = "SELECT p.*, c.jumlah_barang, c.cart_id 
          FROM carts c 
          JOIN products p ON c.product_id = p.product_id 
          WHERE c.customer_id = '$customer_id'";
$result = mysqli_query($conn, $query);

// Inisialisasi total & voucher
$subtotal = 0;
$voucher_discount = $_SESSION['voucher_discount'] ?? 0;
$voucher_code = $_SESSION['voucher_code'] ?? null;
?>

<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="./css/cart.css">
    <title>Styrk Industries - Keranjang Belanja</title>

    <style>
        /* biar tombol bener2 nempel kanan di layar lebar tapi center di HP */
        .checkout-wrap {
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        @media (min-width: 768px) {
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
    </style>
</head>

<body>
    <div class="container_cart mt-4">
        <h2 class="mb-4">Your Shopping Cart</h2>

        <?php if ($result && mysqli_num_rows($result) > 0): ?>
            <form action="payment.php" method="POST" id="cart-form">
                <table class="table table-bordered">
                    <thead class="table-dark">
                        <tr>
                            <th style="width: 5%;"><input type="checkbox" id="select-all" checked></th>
                            <th>Product</th>
                            <th>Price</th>
                            <th>Quantity</th>
                            <th>Total</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = mysqli_fetch_assoc($result)):
                            $harga_numerik = (float)$row['harga'];
                            $total_per_item = $harga_numerik * $row['jumlah_barang'];
                            $subtotal += $total_per_item;
                        ?>
                            <tr>
                                <td>
                                    <input type="checkbox"
                                           class="item-checkbox"
                                           name="selected_items[]"
                                           value="<?= $row['cart_id']; ?>" 
                                           data-price="<?= $harga_numerik; ?>"
                                           data-quantity="<?= $row['jumlah_barang']; ?>"
                                           checked>
                                </td>
                                <td><?= htmlspecialchars($row['nama_produk']); ?></td>
                                <td>Rp <?= number_format($harga_numerik, 0, ',', '.'); ?></td>
                                <td>
                                    <div class="input-group" style="width: 120px;">
                                        <button type="button"
                                                class="btn btn-outline-secondary btn-sm quantity-minus"
                                                data-id="<?= $row['cart_id']; ?>">
                                            <span>-</span>
                                        </button>

                                        <input type="number"
                                            name="quantity[<?= $row['cart_id']; ?>]"
                                            value="<?= $row['jumlah_barang']; ?>"
                                            min="1"
                                            class="form-control text-center"
                                            readonly>

                                        <button type="button"
                                                class="btn btn-outline-secondary btn-sm quantity-plus"
                                                data-id="<?= $row['cart_id']; ?>">
                                            <span>+</span>
                                        </button>
                                    </div>
                                </td>
                                <td>Rp <?= number_format($total_per_item, 0, ',', '.'); ?></td>
                                <td>
                                    <a href="remove_from_cart.php?cart_id=<?= $row['cart_id']; ?>"
                                       class="btn btn-danger btn-sm">
                                        <i class="bi bi-trash"></i> Remove
                                    </a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
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
                                        <a href="remove_voucher.php"
                                           style="color:red; text-decoration:none; font-size:12px;">
                                            Remove
                                        </a>)
                                    </small>
                                <?php endif; ?>
                            </th>
                            <th colspan="2"
                                id="cart-total-display"
                                data-voucher-discount="<?= $voucher_discount; ?>">
                                
                                Rp <?= number_format($subtotal - $voucher_discount, 0, ',', '.'); ?>

                                <?php if ($voucher_discount > 0): ?>
                                    <br>
                                    <small style="font-weight:normal; color: #28a745;">
                                        (Savings:
                                        Rp <?= number_format($voucher_discount, 0, ',', '.'); ?>)
                                    </small>
                                <?php endif; ?>
                            </th>
                        </tr>
                    </tfoot>
                </table>
            </form>

            <!-- BAGIAN VOUCHER + TOMBOL BAYAR -->
            <div class="checkout-wrap mt-4">

                <!-- kiri: voucher -->
                <div class="voucher-col mb-3 mb-md-0">
                    <form action="apply_voucher.php"
                          method="POST"
                          class="d-flex flex-column flex-sm-row align-items-sm-start align-items-center">

                        <input type="text"
                            name="kode_voucher"
                            class="form-control me-sm-2 mb-2 mb-sm-0"
                            placeholder="Masukkan Kode Voucher"
                            required
                            style="max-width:300px;">

                        <button type="submit"
                                class="btn btn-primary">
                            Use
                        </button>
                    </form>

                    <?php if ($voucher_code): ?>
                        <div class="mt-2" style="font-size: 0.9rem;">
                            <span class="text-success fw-semibold">Voucher aktif:</span>
                            <span class="fw-semibold"><?= htmlspecialchars($voucher_code); ?></span>
                            <a href="remove_voucher.php"
                               style="color:red; text-decoration:none; font-size:0.8rem; margin-left:8px;">
                               Remove
                            </a>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- kanan: tombol bayar -->
                <div class="pay-col">
                    <button type="submit"
                        name="proceed_payment"
                        class="btn btn-success d-inline-flex align-items-center gap-2 px-4 py-2 fw-semibold"
                        form="cart-form"
                        style="min-width:230px;">
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

    <!-- Ubah Qty (+/-), update ke DB, reload total -->
    <script>
        document.querySelectorAll('.quantity-plus, .quantity-minus').forEach(button => {
            button.addEventListener('click', function() {
                const input = this.parentElement.querySelector('input');
                let currentQty = parseInt(input.value);
                const cartId = this.dataset.id;

                if (this.classList.contains('quantity-plus')) {
                    currentQty += 1;
                } else if (currentQty > 1) {
                    currentQty -= 1;
                }

                input.value = currentQty;

                fetch('update_cart.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `cart_id=${cartId}&quantity=${currentQty}`
                })
                .then(response => response.text())
                .then(data => {
                    // reload biar total & subtotal ikut update
                    location.reload();
                })
                .catch(error => console.error('Error:', error));
            });
        });
    </script>

    <!-- Hitung ulang total pas user uncheck item -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const selectAllCheckbox = document.getElementById('select-all');
            const itemCheckboxes = document.querySelectorAll('.item-checkbox');
            const totalDisplay = document.getElementById('cart-total-display');
            const voucherDiscount = parseFloat(totalDisplay.dataset.voucherDiscount) || 0;

            function formatRupiah(number) {
                return new Intl.NumberFormat('id-ID', {
                    style: 'currency',
                    currency: 'IDR',
                    minimumFractionDigits: 0
                }).format(number);
            }

            function updateTotalPrice() {
                let newSubtotal = 0;

                itemCheckboxes.forEach(checkbox => {
                    if (checkbox.checked) {
                        const price = parseFloat(checkbox.dataset.price);
                        const quantity = parseInt(checkbox.dataset.quantity);
                        newSubtotal += price * quantity;
                    }
                });

                const finalTotal = newSubtotal - voucherDiscount;

                let totalHTML = formatRupiah(finalTotal);

                if (voucherDiscount > 0) {
                    totalHTML += `<br><small style="font-weight:normal; color: #28a745;">
                        (Savings: ${formatRupiah(voucherDiscount)})
                    </small>`;
                }

                totalDisplay.innerHTML = totalHTML;

                const allChecked = Array.from(itemCheckboxes).every(cb => cb.checked);
                selectAllCheckbox.checked = allChecked;
            }

            // Select all toggle
            selectAllCheckbox.addEventListener('change', function() {
                itemCheckboxes.forEach(checkbox => {
                    checkbox.checked = selectAllCheckbox.checked;
                });
                updateTotalPrice();
            });

            // Checkbox individual
            itemCheckboxes.forEach(checkbox => {
                checkbox.addEventListener('change', updateTotalPrice);
            });

            // Init pertama
            updateTotalPrice();
        });
    </script>
</body>
</html>
