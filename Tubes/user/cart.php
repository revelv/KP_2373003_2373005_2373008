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
</head>

<body>
    <div class="container_cart mt-4">
        <h2 class="mb-4">Your Shopping Cart</h2>

        <?php if ($result && mysqli_num_rows($result) > 0): ?>
            <table class="table table-bordered">
                <thead class="table-dark">
                    <tr>
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
                            <td><?= htmlspecialchars($row['nama_produk']); ?></td>
                            <td>Rp <?= number_format($harga_numerik, 0, ',', '.'); ?></td>
                            <td>
                                <div class="input-group" style="width: 120px;">
                                    <button type="button" class="btn btn-outline-secondary btn-sm quantity-minus" data-id="<?= $row['cart_id']; ?>"><span>-</span></button>
                                    <input type="number" name="quantity[<?= $row['cart_id']; ?>]"
                                        value="<?= $row['jumlah_barang']; ?>"
                                        min="1" class="form-control text-center" readonly>
                                    <button type="button" class="btn btn-outline-secondary btn-sm quantity-plus" data-id="<?= $row['cart_id']; ?>"><span>+</span></button>
                                </div>
                            </td>
                            <td>Rp <?= number_format($total_per_item, 0, ',', '.'); ?></td>
                            <td>
                                <a href="remove_from_cart.php?cart_id=<?= $row['cart_id']; ?>" class="btn btn-danger btn-sm">
                                    <i class="bi bi-trash"></i> Remove
                                </a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
                <tfoot>
                    <tr class="table-group-divider fw-bold">
                        <th colspan="3" class="text-end">
                            Total
                            <?php if ($voucher_code): ?>
                                <br><small style="font-weight:normal;">(Voucher: <?= htmlspecialchars($voucher_code); ?> <a href="remove_voucher.php" style="color:red; text-decoration:none; font-size:12px;">Remove</a>)</small>
                            <?php endif; ?>
                        </th>
                        <th colspan="2">
                            Rp <?= number_format($subtotal - $voucher_discount, 0, ',', '.'); ?>
                            <?php if ($voucher_discount > 0): ?>
                                <br><small style="font-weight:normal; color: #28a745;">(Savings: Rp <?= number_format($voucher_discount, 0, ',', '.'); ?>)</small>
                            <?php endif; ?>
                        </th>
                    </tr>
                </tfoot>
            </table>

            <div class="row mt-4 align-items-center">
                <div class="col-md-6">
                    <form action="apply_voucher.php" method="POST" class="d-flex">
                        <input type="text" name="kode_voucher" class="form-control me-2" placeholder="Masukkan Kode Voucher" required>
                        <button type="submit" class="btn btn-primary ">Use</button>
                    </form>
                </div>
                <div class="col-md-6 text-end">
                    <form action="payment.php" method="POST">
                        <button type="submit" name="proceed_payment" class="btn btn-success">
                            <i class="bi bi-credit-card"></i> Lanjut ke Pembayaran
                        </button>
                    </form>
                </div>
            </div>

        <?php else: ?>
            <div class="alert alert-info">Your cart is empty. <a href="produk.php">Browse Products</a></div>
        <?php endif; ?>
    </div>

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
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: `cart_id=${cartId}&quantity=${currentQty}`
                    })
                    .then(response => response.text())
                    .then(data => {
                        console.log('Server response:', data);
                        location.reload();
                    })
                    .catch(error => console.error('Error:', error));
            });
        });
    </script>
</body>

</html>