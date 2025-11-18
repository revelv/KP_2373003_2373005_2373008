<?php

declare(strict_types=1);
session_start();
include '../koneksi.php';

// ====================== CEK LOGIN ======================
if (!isset($_SESSION['kd_cs'])) {
    $_SESSION['message'] = 'Anda harus login terlebih dahulu.';
    header('Location: ../user/produk.php');
    exit();
}
$customer_id = (int) $_SESSION['kd_cs'];

// ====================== DETEK MODE: CART vs REPAY vs AUCTION ======================
$order_id_php = isset($_GET['order_id']) ? trim((string)$_GET['order_id']) : '';
$is_repay     = ($order_id_php !== '');

$auction_id_php = isset($_GET['auction_id']) ? (int)$_GET['auction_id'] : 0;
$is_auction     = (!$is_repay && $auction_id_php > 0);

// ====================== INISIALISASI ======================
$profil_nama    = '';
$profil_prov    = '';
$profil_kota    = '';
$profil_kec     = '';
$profil_alamat  = '';
$profil_prov_id = '';
$profil_kota_id = '';

$selected_cart_ids = [];
$rows              = [];
$subtotal          = 0;
$voucher_code      = null;
$voucher_tipe      = null;
$voucher_rp        = 0;
$voucher_pct       = 0;
$voucher_discount  = 0;
$base_total        = 0;
$init_ongkir       = 0;
$grand_total       = 0;
$current_courier   = '';

// ====================== AMBIL DATA CUSTOMER (NAMA & PROFIL) ======================
$stmt = $conn->prepare("SELECT nama, provinsi, kota, kecamatan, alamat FROM customer WHERE customer_id = ?");
if ($stmt) {
    $stmt->bind_param("i", $customer_id);
    $stmt->execute();
    $resCust = $stmt->get_result();
    if ($rowCust = $resCust->fetch_assoc()) {
        $profil_nama   = $rowCust['nama'] ?? '';
        $profil_prov   = $rowCust['provinsi'] ?? '';
        $profil_kota   = $rowCust['kota'] ?? '';
        $profil_kec    = $rowCust['kecamatan'] ?? '';
        $profil_alamat = $rowCust['alamat'] ?? '';
    }
    $stmt->close();
}

// ====================== MODE 1: BAYAR ULANG ORDER (DARI RIWAYAT) ======================
if ($is_repay) {
    $stmt = $conn->prepare("
        SELECT o.customer_id, o.provinsi, o.kota, o.alamat,
               o.code_courier, o.ongkos_kirim, o.total_harga, o.status
        FROM orders o
        WHERE o.order_id = ? AND o.customer_id = ?
        LIMIT 1
    ");
    if (!$stmt) {
        $_SESSION['message'] = 'Gagal memuat order.';
        header('Location: riwayat_belanja.php');
        exit();
    }
    $stmt->bind_param('si', $order_id_php, $customer_id);
    $stmt->execute();
    $resOrder = $stmt->get_result();
    $order    = $resOrder->fetch_assoc();
    $stmt->close();

    if (!$order) {
        $_SESSION['message'] = 'Order tidak ditemukan.';
        header('Location: riwayat_belanja.php');
        exit();
    }

    if (strtolower((string)$order['status']) !== 'pending') {
        $_SESSION['message'] = 'Order ini sudah tidak dapat dibayar.';
        header('Location: riwayat_belanja.php');
        exit();
    }

    $profil_prov   = (string)($order['provinsi'] ?? $profil_prov);
    $profil_kota   = (string)($order['kota'] ?? $profil_kota);
    $profil_alamat = (string)($order['alamat'] ?? $profil_alamat);

    $stmt = $conn->prepare("
        SELECT 
            oi.product_id,
            oi.jumlah       AS jumlah_barang,
            oi.harga_satuan AS harga_satuan,
            oi.subtotal     AS item_subtotal,
            p.nama_produk,
            p.link_gambar,
            p.stok
        FROM order_details oi
        JOIN products p ON p.product_id = oi.product_id
        WHERE oi.order_id = ?
    ");
    if ($stmt) {
        $stmt->bind_param('s', $order_id_php);
        $stmt->execute();
        $resItems = $stmt->get_result();
        $rows     = [];
        $subtotal = 0;
        while ($row = $resItems->fetch_assoc()) {
            $row['harga']         = (int)$row['harga_satuan'];
            $row['jumlah_barang'] = (int)$row['jumlah_barang'];
            $row['item_subtotal'] = (int)$row['item_subtotal'];
            $subtotal             += $row['item_subtotal'];
            $rows[]               = $row;
        }
        $stmt->close();
    }

    $voucher_code     = null;
    $voucher_tipe     = null;
    $voucher_discount = 0;
    $base_total       = max(0, $subtotal - $voucher_discount);
    $init_ongkir      = 0;
    $grand_total      = $base_total + $init_ongkir;

    // ====================== MODE 2: CHECKOUT BARU (LELANG / CART) ======================
} else {
    // ====== MODE 2a: DARI LELANG ======
    if ($is_auction) {
        $sql = "
            SELECT 
                a.auction_id,
                a.current_bid,
                a.current_winner_id,
                a.end_time,
                a.status,
                a.title,
                a.image_url,
                a.product_id,
                p.nama_produk,
                p.link_gambar,
                p.stok
            FROM auctions a
            JOIN products p ON p.product_id = a.product_id
            WHERE a.auction_id = ?
            LIMIT 1
        ";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            die('Gagal memuat data lelang.');
        }
        $stmt->bind_param('i', $auction_id_php);
        $stmt->execute();
        $resAuc = $stmt->get_result();
        $auc    = $resAuc->fetch_assoc();
        $stmt->close();

        if (!$auc) {
            die('Lelang tidak ditemukan.');
        }

        if ((int)$auc['current_winner_id'] !== $customer_id) {
            die('Anda bukan pemenang lelang ini.');
        }

        $endedTime = strtotime($auc['end_time']);
        if ($endedTime === false || $endedTime < strtotime('-1 day')) {
            die('Batas waktu pembayaran lelang (1×24 jam) sudah berakhir.');
        }

        if ((int)$auc['stok'] < 1) {
            die('Stok produk lelang tidak cukup.');
        }

        $hargaLelang = (int)$auc['current_bid'];
        $rows[] = [
            'link_gambar'   => $auc['image_url'] ?: $auc['link_gambar'],
            'nama_produk'   => $auc['title'] . ' (Lelang)',
            'jumlah_barang' => 1,
            'harga'         => $hargaLelang,
            'item_subtotal' => $hargaLelang,
        ];
        $subtotal = $hargaLelang;

        $voucher_code     = null;
        $voucher_tipe     = null;
        $voucher_rp       = 0;
        $voucher_pct      = 0;
        $voucher_discount = 0;

        $base_total  = max(0, $subtotal);
        $init_ongkir = 0;
        $grand_total = $base_total + $init_ongkir;

        $current_courier = $_REQUEST['code_courier'] ?? ($_SESSION['checkout_courier'] ?? '');

        // ====== MODE 2b: DARI CART NORMAL ======
    } else {
        if (!isset($_POST['selected_items']) || empty($_POST['selected_items'])) {
            $_SESSION['message'] = 'Pilih setidaknya satu barang untuk checkout.';
            header('Location: cart.php');
            exit();
        }
        $selected_cart_ids = array_map('intval', $_POST['selected_items']);
        $in_clause         = implode(',', $selected_cart_ids);

        $query = "
            SELECT c.cart_id, c.product_id, c.jumlah_barang,
                   p.nama_produk, p.harga, p.link_gambar, p.stok
            FROM carts c
            JOIN products p ON c.product_id = p.product_id
            WHERE c.customer_id = '" . mysqli_real_escape_string($conn, (string)$customer_id) . "'
              AND c.cart_id IN ($in_clause)
        ";
        $result = mysqli_query($conn, $query);

        $rows     = [];
        $subtotal = 0;
        while ($row = mysqli_fetch_assoc($result)) {
            if ((int)$row['stok'] < (int)$row['jumlah_barang']) {
                die("Stok produk {$row['nama_produk']} tidak cukup.");
            }
            $row['harga']         = (int)$row['harga'];
            $row['jumlah_barang'] = (int)$row['jumlah_barang'];
            $row['item_subtotal'] = $row['harga'] * $row['jumlah_barang'];
            $subtotal            += $row['item_subtotal'];
            $rows[]               = $row;
        }

        $voucher_code = $_SESSION['voucher_code']         ?? null;
        $voucher_tipe = $_SESSION['voucher_tipe']         ?? null;
        $voucher_rp   = (int)($_SESSION['voucher_nilai_rupiah']  ?? 0);
        $voucher_pct  = (int)($_SESSION['voucher_nilai_persen']  ?? 0);

        $voucher_discount = 0;
        if ($voucher_code && $voucher_tipe === 'persen') {
            $voucher_discount = (int) round($subtotal * ($voucher_pct / 100));
        } elseif ($voucher_code && $voucher_tipe === 'rupiah') {
            $voucher_discount = $voucher_rp;
        }
        if ($voucher_discount > $subtotal) $voucher_discount = $subtotal;

        $base_total  = max(0, $subtotal - $voucher_discount);
        $init_ongkir = 0;
        $grand_total = $base_total + $init_ongkir;

        $current_courier = $_REQUEST['code_courier'] ?? ($_SESSION['checkout_courier'] ?? '');
        if (!preg_match('/^[a-z0-9_\-]*$/i', $current_courier)) $current_courier = '';
    }
}


// == Daftar kurir (SELALU, baik cart maupun repay/auction) ==
$kurir_res = mysqli_query($conn, "SELECT code_courier, nama_kurir FROM courier ORDER BY code_courier ASC");
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>
        <?php
        if ($is_repay) {
            echo 'Pembayaran Order #' . htmlspecialchars($order_id_php);
        } elseif ($is_auction) {
            echo 'Pembayaran Lelang #' . (int)$auction_id_php;
        } else {
            echo 'Checkout - Payment';
        }
        ?>
    </title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="./css/payment.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>

<body class="mt-4">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="m-0">
                <?php
                if ($is_repay) {
                    echo 'Pembayaran Order #' . htmlspecialchars($order_id_php);
                } elseif ($is_auction) {
                    echo 'Pembayaran Lelang #' . (int)$auction_id_php;
                } else {
                    echo 'Checkout - Payment';
                }
                ?>
            </h2>
            <a href="<?=
                        $is_repay
                            ? 'riwayat_belanja.php'
                            : ($is_auction ? 'auction_detail.php?id=' . (int)$auction_id_php : 'cart.php')
                        ?>" class="btn btn-secondary">← Kembali</a>
        </div>

        <!-- ====================== BARANG YANG DIBAYAR ====================== -->
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
                            <td class="text-success">
                                <strong>- Rp <?= number_format((int)$voucher_discount, 0, ',', '.') ?></strong>
                            </td>
                        </tr>
                    <?php endif; ?>

                    <!-- ====================== ALAMAT PENGIRIMAN ====================== -->
                    <tr>
                        <td>Alamat Pengiriman</td>
                        <td colspan="1">
                            <div class="mb-4">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="alamat_mode" id="alamatProfil"
                                        value="profil" required>
                                    <label class="form-check-label" for="alamatProfil">Gunakan alamat di profil</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="alamat_mode" id="alamatLain"
                                        value="custom">
                                    <label class="form-check-label" for="alamatLain">Gunakan alamat lain</label>
                                </div>
                            </div>
                        </td>
                        <td colspan="3">
                            <div class="border rounded p-3 mb-3 address-panel" id="cardAlamatProfil">
                                <strong><?= htmlspecialchars($profil_nama) ?></strong><br>
                                <?= nl2br(htmlspecialchars($profil_alamat)) ?><br>
                                <?= htmlspecialchars($profil_kota) ?><?= $profil_kec ? ' - ' . htmlspecialchars($profil_kec) : '' ?> - <?= htmlspecialchars($profil_prov) ?>
                            </div>

                            <div class="border rounded p-3 mt-2 address-panel" id="formAlamatLain">
                                <div class="mb-2">
                                    <label for="provinsi_lain" class="form-label">Provinsi</label>
                                    <select id="provinsi_lain" class="form-select">
                                        <option value="">-- Pilih Provinsi --</option>
                                    </select>
                                </div>
                                <div class="mb-2">
                                    <label for="kota_lain" class="form-label">Kota / Kabupaten</label>
                                    <select id="kota_lain" class="form-select" disabled>
                                        <option value="">-- Pilih Kota --</option>
                                    </select>
                                </div>
                                <div class="mb-2">
                                    <label for="kecamatan_lain" class="form-label">Kecamatan</label>
                                    <select id="kecamatan_lain" class="form-select" disabled>
                                        <option value="">-- Pilih Kecamatan --</option>
                                    </select>
                                </div>
                                <div class="mb-2">
                                    <label for="alamat_lain" class="form-label">Alamat Lengkap</label>
                                    <textarea id="alamat_lain" class="form-control" rows="3"
                                        placeholder="Jalan, No, RT/RW, patokan, dll"></textarea>
                                </div>
                            </div>
                        </td>
                    </tr>

                    <!-- ====================== PILIH KURIR ====================== -->
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
                                            <option value="<?= htmlspecialchars($k['code_courier']) ?>"
                                                <?= $current_courier === $k['code_courier'] ? 'selected' : '' ?>>
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
                        <td id="ongkirCell">Rp <?= number_format($init_ongkir, 0, ',', '.') ?></td>
                    </tr>
                    <tr class="fw-bold table-group-divider" id="grandRow">
                        <td colspan="4" class="text-end"><strong>Total</strong></td>
                        <td><strong id="grandTotalCell">Rp <?= number_format($grand_total, 0, ',', '.') ?></strong></td>
                    </tr>
                </tbody>
            </table>
        </div>

        <input type="hidden" id="profile_province_id" value="<?= htmlspecialchars($profil_prov_id ?? '') ?>">
        <input type="hidden" id="profile_city_id" value="<?= htmlspecialchars($profil_kota_id ?? '') ?>">

        <!-- ====================== METODE PEMBAYARAN ====================== -->
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
            <button id="btnPay" class="btn btn-lg btn-warning" onclick="mulaiPembayaran()" disabled>Pay</button>
        </div>

    </div>
    <?php include 'footer.php'; ?>


    <script>
        const isRepay = <?= $is_repay ? 'true' : 'false' ?>;
        const isAuction = <?= $is_auction ? 'true' : 'false' ?>;
        const orderIdPHP = <?= json_encode($order_id_php) ?>;
        const auctionIdPHP = <?= json_encode($auction_id_php) ?>;

        const selectedItems = <?= json_encode($selected_cart_ids) ?>;
        const baseSubtotal = <?= (int)$subtotal ?>;
        const voucherDiscount = <?= (int)$voucher_discount ?>;
        const baseTotal = Math.max(0, baseSubtotal - voucherDiscount);

        const profileAddress = {
            nama: <?= json_encode($profil_nama) ?>,
            provinsi: <?= json_encode($profil_prov) ?>,
            kota: <?= json_encode($profil_kota) ?>,
            kecamatan: <?= json_encode($profil_kec ?? '') ?>,
            alamat: <?= json_encode($profil_alamat) ?>
        };

        const courierSelect = document.getElementById('code_courier');
        const svcBox = document.getElementById('shippingServices');
        const ongkirCell = document.getElementById('ongkirCell');
        const grandCell = document.getElementById('grandTotalCell');
        const btnPay = document.getElementById('btnPay');

        const rProfil = document.getElementById('alamatProfil');
        const rLain = document.getElementById('alamatLain');
        const panelProfil = document.getElementById('cardAlamatProfil');
        const panelLain = document.getElementById('formAlamatLain');

        const provinsiLainSel = document.getElementById('provinsi_lain');
        const kotaLainSel = document.getElementById('kota_lain');
        const kecLainSel = document.getElementById('kecamatan_lain');
        const alamatLainTextarea = document.getElementById('alamat_lain');

        let currentShipping = {
            cost: 0,
            courier: '',
            service: ''
        };

        let shippingAddress = {
            mode: '',
            provinsi: '',
            kota: '',
            kecamatan: '',
            alamat: ''
        };

        const show = el => {
            if (el) el.style.display = 'block';
        };
        const hide = el => {
            if (el) el.style.display = 'none';
        };

        function isAddressValid() {
            if (!shippingAddress.mode) return false;
            if (!shippingAddress.provinsi || !shippingAddress.kota) return false;

            if (shippingAddress.mode === 'custom') {
                if (!shippingAddress.alamat) return false;
                if (kecLainSel && !shippingAddress.kecamatan) return false;
                return true;
            }
            if (shippingAddress.mode === 'profil') return true;
            return false;
        }

        function updateTotals(cost = 0) {
            const ongkir = Number(cost) || 0;
            const grand = Math.max(0, baseTotal + ongkir);
            if (ongkirCell) ongkirCell.textContent = 'Rp ' + ongkir.toLocaleString('id-ID');
            if (grandCell) grandCell.textContent = 'Rp ' + grand.toLocaleString('id-ID');
            currentShipping.cost = ongkir;
            currentShipping.courier = courierSelect?.value || '';
            btnPay.disabled = !(currentShipping.service && isAddressValid());
        }

        updateTotals(0);

        function ensureHiddenInputs(formEl) {
            if (!formEl) return;
            const put = (name, val) => {
                let el = formEl.querySelector(`input[name="${name}"]`);
                if (!el) {
                    el = document.createElement('input');
                    el.type = 'hidden';
                    el.name = name;
                    formEl.appendChild(el);
                }
                el.value = val ?? '';
            };

            put('alamat_mode', shippingAddress.mode || '');
            put('provinsi', shippingAddress.provinsi || '');
            put('kota', shippingAddress.kota || '');
            put('kecamatan', shippingAddress.kecamatan || '');
            put('alamat', shippingAddress.alamat || '');

            put('provinsi_id', provinsiLainSel ? (provinsiLainSel.value || '') : '');
            put('kota_id', kotaLainSel ? (kotaLainSel.value || '') : '');
            put('kecamatan_id', kecLainSel ? (kecLainSel.value || '') : '');

            let destCityId = '';
            let destProvId = '';
            let destDistrictId = '';

            if (shippingAddress.mode === 'custom') {
                destCityId = kotaLainSel ? (kotaLainSel.value || '') : '';
                destProvId = provinsiLainSel ? (provinsiLainSel.value || '') : '';
                destDistrictId = kecLainSel ? (kecLainSel.value || '') : '';
            } else if (shippingAddress.mode === 'profil') {
                const profileCity = document.getElementById('profile_city_id');
                const profileProv = document.getElementById('profile_province_id');
                destCityId = profileCity ? (profileCity.value || '') : '';
                destProvId = profileProv ? (profileProv.value || '') : '';
            }
            put('dest_city_id', destCityId);
            put('dest_prov_id', destProvId);
            put('dest_district_id', destDistrictId);

            put('shipping_cost', String(currentShipping.cost || 0));
            put('shipping_courier', currentShipping.courier || '');
            put('shipping_service', currentShipping.service || '');

            (selectedItems || []).forEach(id => {
                const i = document.createElement('input');
                i.type = 'hidden';
                i.name = 'selected_items[]';
                i.value = String(id);
                formEl.appendChild(i);
            });

            if (isRepay && orderIdPHP) {
                put('order_id', orderIdPHP);
            }
            if (isAuction && auctionIdPHP) {
                put('auction_id', auctionIdPHP);
            }
        }

        function resetOngkir(reload = false) {
            currentShipping.service = '';
            updateTotals(0);
            btnPay.disabled = true;
            if (svcBox) svcBox.innerHTML = '';
            const cur = courierSelect?.value || '';
            if (reload && cur) loadServices(cur);
        }

        let provinceLoaded = false;

        // ====== PROV/KOTA/KEC (masih pakai endpoint RajaOngkir yang lama) ======
        async function loadProvinces() {
            if (provinceLoaded) return;
            if (provinsiLainSel) provinsiLainSel.innerHTML = '<option value="">Loading...</option>';
            const res = await fetch('./rajaongkir/get-province.php?t=' + Date.now());
            const txt = await res.text();
            let data;
            try {
                data = JSON.parse(txt);
            } catch {
                console.error('Province parse error:', txt);
                return;
            }

            let list = [];
            if (Array.isArray(data)) list = data;
            else if (Array.isArray(data.data)) list = data.data;
            else if (data.rajaongkir && Array.isArray(data.rajaongkir.results)) list = data.rajaongkir.results;

            const options = list.map(p => {
                const id = String(p.province_id ?? p.id ?? p.provinceId ?? '');
                const name = String(p.province ?? p.name ?? p.provinceName ?? '');
                return (id && name) ? `<option value="${id}">${name}</option>` : '';
            }).join('');

            if (provinsiLainSel) {
                provinsiLainSel.innerHTML = '<option value="">-- Pilih Provinsi --</option>' + options;
                provinceLoaded = true;
            }
        }

        async function loadCities(provId) {
            if (!kotaLainSel) return;
            kotaLainSel.innerHTML = '<option value="">Loading...</option>';
            kotaLainSel.disabled = true;

            if (!provId) {
                kotaLainSel.innerHTML = '<option value="">-- Pilih Kota --</option>';
                return;
            }
            const res = await fetch('./rajaongkir/get-cities.php?province=' + encodeURIComponent(provId) + '&t=' + Date.now());
            const txt = await res.text();
            let data;
            try {
                data = JSON.parse(txt);
            } catch {
                console.error('City parse error:', txt);
                return;
            }

            let list = [];
            if (Array.isArray(data)) list = data;
            else if (Array.isArray(data.data)) list = data.data;
            else if (data.rajaongkir && Array.isArray(data.rajaongkir.results)) list = data.rajaongkir.results;

            const options = list.map(c => {
                const id = String(c.city_id ?? c.id ?? c.cityId ?? '');
                const name = String(c.city_name ?? c.name ?? c.cityName ?? '').trim();
                return (id && name) ? `<option value="${id}">${name}</option>` : '';
            }).join('');

            kotaLainSel.innerHTML = '<option value="">-- Pilih Kota --</option>' + options;
            kotaLainSel.disabled = false;

            if (kecLainSel) {
                kecLainSel.disabled = true;
                kecLainSel.innerHTML = '<option value="">-- Pilih Kecamatan --</option>';
            }
        }

        async function loadDistricts(cityId) {
            if (!kecLainSel) return;

            kecLainSel.disabled = true;
            kecLainSel.innerHTML = '<option value="">Loading...</option>';

            if (!cityId) {
                kecLainSel.innerHTML = '<option value="">-- Pilih Kecamatan --</option>';
                return;
            }

            try {
                const res = await fetch('./rajaongkir/get-district.php?city=' + encodeURIComponent(cityId) + '&t=' + Date.now());
                const txt = await res.text();
                let data;
                try {
                    data = JSON.parse(txt);
                } catch (e) {
                    console.error('District JSON parse error:', e);
                    kecLainSel.innerHTML = '<option value="">Gagal baca data kecamatan</option>';
                    return;
                }

                let list = [];
                if (Array.isArray(data)) list = data;
                else if (Array.isArray(data.data)) list = data.data;
                else if (data.rajaongkir && Array.isArray(data.rajaongkir.results)) list = data.rajaongkir.results;

                if (!Array.isArray(list) || !list.length) {
                    kecLainSel.innerHTML = '<option value="">(Tidak ada data kecamatan)</option>';
                    return;
                }

                const options = list.map(d => {
                    const id = String(
                        d.subdistrict_id ??
                        d.id ??
                        d.subdistrictId ??
                        d.district_id ??
                        ''
                    );
                    const name = String(
                        d.subdistrict_name ??
                        d.name ??
                        d.subdistrictName ??
                        d.district_name ??
                        ''
                    ).trim();
                    if (!id || !name) return '';
                    return `<option value="${id}">${name}</option>`;
                }).join('');

                if (!options) {
                    kecLainSel.innerHTML = '<option value="">(Data kecamatan kosong)</option>';
                    return;
                }

                kecLainSel.innerHTML = '<option value="">-- Pilih Kecamatan --</option>' + options;
                kecLainSel.disabled = false;

            } catch (err) {
                console.error('District fetch error:', err);
                kecLainSel.innerHTML = '<option value="">Error load kecamatan</option>';
                kecLainSel.disabled = true;
            }
        }

        function applyAddressMode(mode) {
            if (mode === 'profil') {
                show(panelProfil);
                hide(panelLain);
                shippingAddress.mode = 'profil';
                shippingAddress.provinsi = (profileAddress.provinsi || '').trim();
                shippingAddress.kota = (profileAddress.kota || '').trim();
                shippingAddress.kecamatan = (profileAddress.kecamatan || '').trim();
                shippingAddress.alamat = (profileAddress.alamat || '').trim();
                resetOngkir(true);
            } else if (mode === 'custom') {
                hide(panelProfil);
                show(panelLain);
                shippingAddress.mode = 'custom';
                if (!provinceLoaded) loadProvinces().catch(console.error);
                const pOpt = provinsiLainSel?.selectedOptions?.[0];
                const cOpt = kotaLainSel?.selectedOptions?.[0];
                const kOpt = kecLainSel?.selectedOptions?.[0];
                shippingAddress.provinsi = (pOpt && provinsiLainSel.value) ? pOpt.text : '';
                shippingAddress.kota = (cOpt && kotaLainSel.value) ? cOpt.text : '';
                shippingAddress.kecamatan = (kOpt && kecLainSel.value) ? kOpt.text : '';
                shippingAddress.alamat = (alamatLainTextarea?.value || '');
                resetOngkir(true);
            } else {
                hide(panelProfil);
                hide(panelLain);
                shippingAddress.mode = '';
                shippingAddress.provinsi = '';
                shippingAddress.kota = '';
                shippingAddress.kecamatan = '';
                shippingAddress.alamat = '';
                resetOngkir(false);
            }
        }

        (function init() {
            hide(panelProfil);
            hide(panelLain);
            if (rProfil) rProfil.checked = false;
            if (rLain) rLain.checked = false;
            applyAddressMode('');
        })();

        document.addEventListener('change', (e) => {
            const t = e.target;
            if (t && t.name === 'alamat_mode') {
                applyAddressMode(t.value === 'profil' ? 'profil' : 'custom');
            }
            if (t === provinsiLainSel) {
                loadCities(provinsiLainSel.value).then(() => {
                    const pOpt = provinsiLainSel.selectedOptions[0];
                    shippingAddress.provinsi = (pOpt && provinsiLainSel.value) ? pOpt.text : '';
                    shippingAddress.kecamatan = '';
                    if (kecLainSel) {
                        kecLainSel.disabled = true;
                        kecLainSel.innerHTML = '<option value="">-- Pilih Kecamatan --</option>';
                    }
                    resetOngkir(true);
                });
            }
            if (t === kotaLainSel) {
                const cOpt = kotaLainSel.selectedOptions[0];
                shippingAddress.kota = (cOpt && kotaLainSel.value) ? cOpt.text : '';
                loadDistricts(kotaLainSel.value).then(() => {
                    const kOpt = kecLainSel?.selectedOptions?.[0];
                    shippingAddress.kecamatan = (kOpt && kecLainSel.value) ? kOpt.text : '';
                    resetOngkir(true);
                }).catch(() => {
                    resetOngkir(true);
                });
            }
            if (t === kecLainSel) {
                const kOpt = kecLainSel.selectedOptions[0];
                shippingAddress.kecamatan = (kOpt && kecLainSel.value) ? kOpt.text : '';
                resetOngkir(true);
            }
            if (t === alamatLainTextarea) {
                shippingAddress.alamat = (alamatLainTextarea.value || '');
                resetOngkir(true);
            }
        });

        document.addEventListener('click', (e) => {
            const t = e.target;
            if (t && t.matches('label[for="alamatProfil"], #alamatProfil')) {
                if (rProfil) rProfil.checked = true;
                applyAddressMode('profil');
            }
            if (t && t.matches('label[for="alamatLain"], #alamatLain')) {
                if (rLain) rLain.checked = true;
                applyAddressMode('custom');
            }
        });

        // ====================== ONGKIR / LAYANAN (KOMSHIP) ======================
        async function loadServices(courier) {
            if (!courier) return;

            if (!isAddressValid()) {
                svcBox.innerHTML = '<div class="text-danger">Lengkapi alamat dulu ya bro.</div>';
                updateTotals(0);
                return;
            }

            const formData = new FormData();

            // Item yang dipilih (buat hitung berat di backend)
            (selectedItems || []).forEach(id => formData.append('selected_items[]', id));

            // Mode: repay / auction
            if (isRepay && orderIdPHP) {
                formData.append('order_id', orderIdPHP);
            }
            if (isAuction && auctionIdPHP) {
                formData.append('auction_id', auctionIdPHP);
            }

            // Data alamat (NAMA, bukan ID)
            formData.append('alamat_mode', shippingAddress.mode || '');
            formData.append('provinsi', shippingAddress.provinsi || '');
            formData.append('kota', shippingAddress.kota || '');
            formData.append('kecamatan', shippingAddress.kecamatan || '');
            formData.append('alamat', shippingAddress.alamat || '');

            // Kirim ID kota/kecamatan (optional, backend boleh abaikan)
            let destCityId = '';
            let destDistrictId = '';
            if (shippingAddress.mode === 'custom') {
                destCityId = (kotaLainSel && kotaLainSel.value) ? kotaLainSel.value : '';
                destDistrictId = (kecLainSel && kecLainSel.value) ? kecLainSel.value : '';
            } else if (shippingAddress.mode === 'profil') {
                destCityId = (document.getElementById('profile_city_id')?.value || '');
            }
            formData.append('dest_city_id', destCityId);
            formData.append('dest_district_id', destDistrictId);

            // Data kurir & nilai barang (buat Komship)
            formData.append('code_courier', courier);
            formData.append('base_total', String(baseTotal)); // nilai barang total

            svcBox.textContent = 'Menghitung ongkir...';
            currentShipping.service = '';
            updateTotals(0);
            btnPay.disabled = true;

            try {
                const res = await fetch('./rajaongkir/calc_ongkir.php', {
                    method: 'POST',
                    body: formData
                });
                const txt = await res.text();
                let data;
                try {
                    data = JSON.parse(txt);
                } catch {
                    console.error('calc_ongkir RAW:', txt);
                    throw new Error('Respon tidak valid dari calc_ongkir.php');
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
                                ${s.etd ? `(ETD ${s.etd})` : ''}
                                - Rp ${Number(s.cost).toLocaleString('id-ID')}
                            </option>
                        `).join('')}
                    </select>
                    <small class="text-muted">Harga & estimasi berdasarkan Komship.</small>`;

                const svc = document.getElementById('serviceSelect');
                const applyCost = () => {
                    const cost = parseInt(svc.selectedOptions[0]?.dataset.cost || '0', 10);
                    currentShipping.service = svc.value || '';
                    updateTotals(cost);
                    ensureHiddenInputs(document.getElementById('formQRIS'));
                    ensureHiddenInputs(document.getElementById('formTransfer'));
                };
                applyCost();
                svc.addEventListener('change', applyCost);
            } catch (err) {
                svcBox.innerHTML = `<div class="text-danger">Error koneksi: ${String(err.message || err)}</div>`;
                updateTotals(0);
            }
        }

        courierSelect?.addEventListener('change', (e) => {
            resetOngkir(false);
            loadServices(e.target.value);
        });

        // ====================== PAYMENT (QRIS / TRANSFER) ======================
        let qrTimer;
        let qrContent = "";

        function generateQRContent() {
            const rand = Math.floor(Math.random() * 900) + 100;
            return encodeURIComponent("STYRK_QRIS_" + Date.now() + "_" + rand);
        }

        function mulaiPembayaran() {
            if (!currentShipping.service) {
                alert("Pilih kurir & layanan pengiriman dulu ya bro.");
                return;
            }
            if (!isAddressValid()) {
                alert("Alamat pengiriman belum lengkap.");
                return;
            }

            const metode = document.querySelector('input[name="metode"]:checked');
            const container = document.getElementById("paymentContainer");
            if (!metode) {
                alert("Silakan pilih metode pembayaran terlebih dahulu.");
                return;
            }

            let html = "";
            if (metode.value === "QRIS") {
                qrContent = generateQRContent();
                html = `
                    <div class="payment-box">
                        <h5>QRIS</h5>
                        <img id="qrImage" src="https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=${qrContent}" alt="QRIS"><br>
                        <div class="qris-timer" id="timer">02:00</div>
                        <form id="formQRIS" action="checkout.php" method="post">
                            <input type="hidden" name="metode" value="QRIS">
                            <input type="hidden" name="kode_transaksi" value="${qrContent}">
                            <button type="submit" class="btn btn-primary mt-2">Cek Pembayaran</button>
                        </form>
                    </div>`;
                container.innerHTML = html;
                ensureHiddenInputs(document.getElementById('formQRIS'));
                startQRISTimer();
            } else {
                html = `
                    <div class="payment-box">
                        <h5>Transfer Bank</h5>
                        <p>Silakan transfer ke rekening:</p>
                        <p><strong>BANK BCA 1234567890 a.n STYRK INDUSTRIES</strong></p>
                        <form id="formTransfer" action="checkout.php" method="post" enctype="multipart/form-data">
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
                ensureHiddenInputs(document.getElementById('formTransfer'));
            }
        }

        function startQRISTimer() {
            clearInterval(qrTimer);
            let duration = 120;
            const timerDisplay = document.getElementById("timer");
            qrTimer = setInterval(() => {
                const m = Math.floor(duration / 60);
                const s = duration % 60;
                if (timerDisplay) timerDisplay.textContent =
                    (m < 10 ? "0" : "") + m + ":" + (s < 10 ? "0" : "") + s;
                if (--duration < 0) {
                    clearInterval(qrTimer);
                    qrContent = generateQRContent();
                    const qrImg = document.getElementById("qrImage");
                    if (qrImg) qrImg.src = "https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=" + qrContent;
                    const kodeInput = document.querySelector("#formQRIS input[name='kode_transaksi']");
                    if (kodeInput) kodeInput.value = qrContent;
                    startQRISTimer();
                    alert("QR baru telah digenerate karena timeout.");
                }
            }, 1000);
        }
    </script>

</body>

</html>
