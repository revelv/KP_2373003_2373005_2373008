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

// ====================== CEK JIKA SUDAH BAYAR ======================
if ($is_repay) {
    $stmt = $conn->prepare("SELECT status FROM orders WHERE order_id = ? AND customer_id = ?");
    if ($stmt) {
        $stmt->bind_param('si', $order_id_php, $customer_id);
        $stmt->execute();
        $res = $stmt->get_result();
        $order = $res->fetch_assoc();
        $stmt->close();

        if ($order && $order['status'] !== 'pending') {
            $_SESSION['message'] = 'Order ini sudah dibayar atau diproses.';
            header('Location: riwayat_belanja.php');
            exit();
        }
    }
}

// ====================== INISIALISASI ======================
$profil_nama    = '';
$profil_prov    = '';
$profil_kota    = '';
$profil_kec     = '';
$profil_kel     = '';
$profil_alamat  = '';
$profil_postal  = '';

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

// ====================== AMBIL DATA CUSTOMER ======================
$stmt = $conn->prepare("
    SELECT nama, provinsi, kota, kecamatan, kelurahan, alamat, postal_code
    FROM customer
    WHERE customer_id = ?
");
if ($stmt) {
    $stmt->bind_param("i", $customer_id);
    $stmt->execute();
    $resCust = $stmt->get_result();
    if ($rowCust = $resCust->fetch_assoc()) {
        $profil_nama   = $rowCust['nama']        ?? '';
        $profil_prov   = $rowCust['provinsi']    ?? '';
        $profil_kota   = $rowCust['kota']        ?? '';
        $profil_kec    = $rowCust['kecamatan']   ?? '';
        $profil_kel    = $rowCust['kelurahan']   ?? '';
        $profil_alamat = $rowCust['alamat']      ?? '';
        $profil_postal = isset($rowCust['postal_code'])
            ? (string)$rowCust['postal_code']
            : '';
    }
    $stmt->close();
}

// ====================== MODE 1: BAYAR ULANG ORDER ======================
if ($is_repay) {
    $stmt = $conn->prepare("
        SELECT o.customer_id, o.provinsi, o.kota, o.kecamatan, o.alamat,
               o.code_courier, o.ongkos_kirim, o.total_harga
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

    $profil_prov   = (string)($order['provinsi']  ?? $profil_prov);
    $profil_kota   = (string)($order['kota']      ?? $profil_kota);
    $profil_kec    = (string)($order['kecamatan'] ?? $profil_kec);
    $profil_alamat = (string)($order['alamat']    ?? $profil_alamat);

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

    $voucher_discount = 0;
    $base_total       = max(0, $subtotal - $voucher_discount);
    $init_ongkir      = 0;
    $grand_total      = $base_total + $init_ongkir;
} else {
    // ====================== MODE 2: CHECKOUT BARU ======================
    if ($auction_id_php > 0) {
        // ====== MODE 2a: LELANG ======
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
        if (!$stmt) die('Gagal memuat data lelang.');
        $stmt->bind_param('i', $auction_id_php);
        $stmt->execute();
        $resAuc = $stmt->get_result();
        $auc    = $resAuc->fetch_assoc();
        $stmt->close();

        if (!$auc) die('Lelang tidak ditemukan.');
        if ((int)$auc['current_winner_id'] !== $customer_id) die('Anda bukan pemenang lelang ini.');
        $endedTime = strtotime($auc['end_time']);
        if ($endedTime === false || $endedTime < strtotime('-1 day')) die('Batas waktu pembayaran lelang (1×24 jam) sudah berakhir.');
        if ((int)$auc['stok'] < 1) die('Stok produk lelang tidak cukup.');

        $hargaLelang = (int)$auc['current_bid'];
        $rows[] = [
            'link_gambar'   => $auc['image_url'] ?: $auc['link_gambar'],
            'nama_produk'   => $auc['title'] . ' (Lelang)',
            'jumlah_barang' => 1,
            'harga'         => $hargaLelang,
            'item_subtotal' => $hargaLelang,
        ];
        $subtotal         = $hargaLelang;
        $voucher_discount = 0;
        $base_total       = max(0, $subtotal);
        $init_ongkir      = 0;
        $grand_total      = $base_total + $init_ongkir;
    } else {
        // ====== MODE 2b: CART NORMAL ======
        if (!isset($_POST['selected_items']) || empty($_POST['selected_items'])) {
            // fallback dari session kalau payment dibuka ulang / refresh
            $selected_cart_ids = $_SESSION['checkout_cart_ids'] ?? [];
            if (empty($selected_cart_ids)) {
                $_SESSION['message'] = 'Pilih setidaknya satu barang untuk checkout.';
                header('Location: cart.php');
                exit();
            }
        } else {
            $selected_cart_ids = array_map('intval', $_POST['selected_items']);
            $selected_cart_ids = array_values(array_filter($selected_cart_ids, fn($v) => $v > 0));
            if (!$selected_cart_ids) {
                $_SESSION['message'] = 'Item tidak valid.';
                header('Location: cart.php');
                exit();
            }
            // simpan biar aman kalau payment ke-refresh
            $_SESSION['checkout_cart_ids'] = $selected_cart_ids;
        }

        $in_clause = implode(',', $selected_cart_ids);

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
    }
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>
        <?php
        if ($is_repay) {
            echo 'Pembayaran Order #' . htmlspecialchars($order_id_php);
        } elseif ($auction_id_php > 0) {
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

<div id="selectedItemsPool" style="display:none;">
    <?php foreach ($selected_cart_ids as $cid): ?>
        <input type="hidden" data-cart-id="<?= (int)$cid ?>" value="<?= (int)$cid ?>">
    <?php endforeach; ?>
</div>


<body class="mt-4">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="m-0">
                <?php
                if ($is_repay) {
                    echo 'Pembayaran Order #' . htmlspecialchars($order_id_php);
                } elseif ($auction_id_php > 0) {
                    echo 'Pembayaran Lelang #' . (int)$auction_id_php;
                } else {
                    echo 'Checkout - Payment';
                }
                ?>
            </h2>
            <a href="<?= $is_repay ? 'riwayat_belanja.php' : ($auction_id_php > 0 ? 'auction_detail.php?id=' . (int)$auction_id_php : 'cart.php') ?>" class="btn btn-secondary">← Kembali</a>
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
                            <td><img src="<?= htmlspecialchars((string)$row['link_gambar']) ?>" width="80" alt=""></td>
                            <td><?= htmlspecialchars((string)$row['nama_produk']) ?></td>
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
                                        value="profil" checked>
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
                            <!-- PROFIL: DITAMPILKAN DEFAULT -->
                            <div class="border rounded p-3 mb-3 address-panel" id="cardAlamatProfil" style="display:block;">
                                <strong><?= htmlspecialchars($profil_nama) ?></strong><br>
                                <?= nl2br(htmlspecialchars($profil_alamat)) ?><br>
                                <?= htmlspecialchars($profil_kota) ?>
                                <?= $profil_kec ? ' - ' . htmlspecialchars($profil_kec) : '' ?>
                                <?= $profil_kel ? ' - ' . htmlspecialchars($profil_kel) : '' ?>
                                - <?= htmlspecialchars($profil_prov) ?><br>
                                <?php if ($profil_postal !== ''): ?>
                                    Kode Pos: <?= htmlspecialchars((string)$profil_postal, ENT_QUOTES, 'UTF-8') ?>
                                <?php endif; ?>
                            </div>

                            <!-- ALAMAT LAIN -->
                            <div class="border rounded p-3 mt-2 address-panel" id="formAlamatLain" style="display:none;">
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
                                    <label for="kelurahan_lain" class="form-label">Kelurahan / Desa (Sub-district)</label>
                                    <select id="kelurahan_lain" class="form-select" disabled>
                                        <option value="">-- Pilih Kelurahan --</option>
                                    </select>
                                </div>
                                <div class="mb-2">
                                    <label for="postal_lain" class="form-label">Kode Pos</label>
                                    <input type="text" id="postal_lain" class="form-control"
                                        placeholder="Otomatis dari kelurahan" readonly>
                                </div>
                                <div class="mb-2">
                                    <label for="alamat_lain" class="form-label">Alamat Lengkap</label>
                                    <textarea id="alamat_lain" class="form-control" rows="3"
                                        placeholder="Jalan, No, RT/RW, patokan, dll"></textarea>
                                </div>
                            </div>
                        </td>
                    </tr>

                    <!-- ====================== PILIH KURIR (BITESHIP) ====================== -->
                    <tr>
                        <td colspan="1">Pilih Courier</td>
                        <td colspan="4">
                            <form action="" method="post" id="courierForm" onsubmit="return false;">
                                <?php foreach ($selected_cart_ids as $cid): ?>
                                    <input type="hidden" name="selected_items[]" value="<?= (int)$cid ?>">
                                <?php endforeach; ?>

                                <select name="code_courier" id="code_courier" class="form-select" required>
                                    <option value="" disabled selected>-- Pilih Kurir --</option>
                                </select>
                                <div id="shippingServices" class="mt-2"></div>
                            </form>
                        </td>
                    </tr>

                    <tr>
                        <td colspan="4" class="text-end"><strong>Ongkir</strong></td>
                        <td id="ongkirCell">Rp <?= number_format((int)$init_ongkir, 0, ',', '.') ?></td>
                    </tr>
                    <tr class="fw-bold table-group-divider" id="grandRow">
                        <td colspan="4" class="text-end"><strong>Total</strong></td>
                        <td><strong id="grandTotalCell">Rp <?= number_format((int)$grand_total, 0, ',', '.') ?></strong></td>
                    </tr>
                </tbody>
            </table>
        </div>

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
        const isAuction = <?= $auction_id_php > 0 ? 'true' : 'false' ?>;
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
            kelurahan: <?= json_encode($profil_kel ?? '') ?>,
            alamat: <?= json_encode($profil_alamat) ?>,
            postal_code: <?= json_encode($profil_postal ?? '') ?>
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
        const kelLainSel = document.getElementById('kelurahan_lain');
        const alamatLainTextarea = document.getElementById('alamat_lain');
        const postalLainInput = document.getElementById('postal_lain');

        // ==================== STATE SHIPPING ====================
        let currentShipping = {
            cost: 0,
            courier: '',
            service: '',
            postal: ''
        };

        let shippingAddress = {
            mode: 'profil',
            provinsi: (profileAddress.provinsi || '').trim(),
            kota: (profileAddress.kota || '').trim(),
            kecamatan: (profileAddress.kecamatan || '').trim(),
            kelurahan: (profileAddress.kelurahan || '').trim(),
            alamat: (profileAddress.alamat || '').trim(),
            kodepos: (profileAddress.postal_code || '').trim()
        };

        const show = el => {
            if (el) el.style.display = 'block';
        };
        const hide = el => {
            if (el) el.style.display = 'none';
        };

        // ================== LIST KURIR (BITESHIP) ==================
        async function loadCouriers() {
            if (!courierSelect) return;
            courierSelect.innerHTML = '<option value="" disabled selected>Loading kurir...</option>';
            try {
                const res = await fetch('biteship_list_couriers.php', {
                    cache: 'no-store'
                });
                const txt = await res.text();
                console.log('biteship_list_couriers raw:', txt);

                let data;
                try {
                    data = JSON.parse(txt);
                } catch (e) {
                    console.error('Parse JSON courier error:', e, txt);
                    courierSelect.innerHTML = '<option value="" disabled>Gagal baca data kurir</option>';
                    return;
                }

                if (!data.success || !Array.isArray(data.couriers) || !data.couriers.length) {
                    courierSelect.innerHTML = '<option value="" disabled>Tidak ada kurir tersedia</option>';
                    return;
                }

                // sesuaikan dengan list couriers yang lu kirim tadi
                const allowed = [
                    'jne',
                    'jnt',
                    'sicepat',
                    'tiki',
                    'pos',
                    'wahana',
                    'lion',
                    'idexpress',
                    'sap',
                    'sentralcargo'
                ];

                let list = data.couriers.map(c => ({
                    code: String(c.courier_code || '').toLowerCase(),
                    name: c.courier_name || ''
                }));

                const seen = new Set();
                list = list.filter(c => {
                    if (!c.code) return false;
                    if (allowed.length && !allowed.includes(c.code)) return false;
                    if (seen.has(c.code)) return false;
                    seen.add(c.code);
                    return true;
                });

                courierSelect.innerHTML = '<option value="" disabled selected>-- Pilih Kurir --</option>';
                list.forEach(c => {
                    const opt = document.createElement('option');
                    opt.value = c.code;
                    opt.textContent = c.name || c.code.toUpperCase();
                    courierSelect.appendChild(opt);
                });
            } catch (err) {
                console.error('Error loadCouriers:', err);
                courierSelect.innerHTML = '<option value="" disabled>Gagal load kurir</option>';
            }
        }

        function isAddressValid() {
            if (!shippingAddress.mode) return false;
            if (!shippingAddress.provinsi || !shippingAddress.kota) return false;

            if (shippingAddress.mode === 'custom') {
                if (!shippingAddress.alamat) return false;
                if (kecLainSel && !shippingAddress.kecamatan) return false;
                if (kelLainSel && !shippingAddress.kelurahan) return false;
                if (!shippingAddress.kodepos) return false;
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

            // Mode repay boleh langsung bayar tanpa pilih kurir lagi
            if (isRepay && isAddressValid()) {
                btnPay.disabled = false;
            } else {
                btnPay.disabled = !(currentShipping.service && isAddressValid());
            }
        }
        updateTotals(0);

        function getSelectedItemsSafe() {
            if (Array.isArray(selectedItems) && selectedItems.length) {
                return selectedItems.map(String);
            }
            const pool = document.querySelectorAll('#selectedItemsPool input[data-cart-id]');
            return Array.from(pool).map(i => String(i.value)).filter(Boolean);
        }

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
            put('kelurahan', shippingAddress.kelurahan || '');
            put('alamat', shippingAddress.alamat || '');
            put('kodepos', shippingAddress.kodepos || '');

            // clear dulu biar gak dobel
            formEl.querySelectorAll('input[name="selected_items[]"]').forEach(i => i.remove());
            getSelectedItemsSafe().forEach(id => {
                const i = document.createElement('input');
                i.type = 'hidden';
                i.name = 'selected_items[]';
                i.value = id;
                formEl.appendChild(i);
            });

            if (isRepay && orderIdPHP) put('order_id', orderIdPHP);
            if (isAuction && auctionIdPHP) put('auction_id', auctionIdPHP);

            put('shipping_cost', String(currentShipping.cost || 0));
            put('code_courier', currentShipping.courier || '');
            // PENTING: ini nanti dipakai sebagai courier_type Biteship → harus service_code
            put('service_courier', currentShipping.service || '');
        }

        document.addEventListener('submit', (e) => {
            const f = e.target;
            if (f && (f.id === 'formQRIS' || f.id === 'formTransfer')) {
                ensureHiddenInputs(f);
            }
        }, true);

        function resetOngkir(reload = false) {
            currentShipping.service = '';
            currentShipping.cost = 0;
            updateTotals(0);

            // Mode repay: jangan paksa disable tombol
            if (!isRepay) {
                btnPay.disabled = true;
            }

            if (svcBox) svcBox.innerHTML = '';
            const cur = courierSelect?.value || '';
            if (reload && cur) loadServices(cur);
        }

        // ====== RAJAONGKIR: PROV/KOTA/KEC/KEL (tetap) ======
        let provinceLoaded = false;

        function normalizeList(root) {
            if (!root) return [];
            if (Array.isArray(root)) return root;
            if (Array.isArray(root.data)) return root.data;
            if (root.rajaongkir && Array.isArray(root.rajaongkir.results)) return root.rajaongkir.results;
            if (root.success && Array.isArray(root.results)) return root.results;
            return [];
        }

        function safeId(v) {
            if (v === null || v === undefined) return '';
            const s = String(v).trim();
            if (!s || s.toLowerCase() === 'undefined' || s.toLowerCase() === 'null') return '';
            return s;
        }

        function safeText(v) {
            if (v === null || v === undefined) return '';
            const s = String(v).trim();
            if (!s || s.toLowerCase() === 'undefined' || s.toLowerCase() === 'null') return '';
            return s;
        }

        async function loadProvinces() {
            if (provinceLoaded || !provinsiLainSel) return;

            provinsiLainSel.innerHTML = '<option value="">Loading...</option>';

            try {
                const res = await fetch('./rajaongkir/get-province.php?t=' + Date.now());
                const txt = await res.text();

                let root;
                try {
                    root = JSON.parse(txt);
                } catch (e) {
                    console.error('Province parse error:', e, txt);
                    provinsiLainSel.innerHTML = '<option value="">Gagal load provinsi</option>';
                    return;
                }

                const list = normalizeList(root);
                if (!list.length) {
                    provinsiLainSel.innerHTML = '<option value="">(Provinsi kosong)</option>';
                    return;
                }

                const options = list.map(p => {
                    const id = safeId(p.province_id ?? p.id ?? p.provinceId);
                    const name = safeText(p.province ?? p.name ?? p.provinceName);
                    if (!id || !name) return '';
                    return `<option value="${id}">${name}</option>`;
                }).join('');

                provinsiLainSel.innerHTML = '<option value="">-- Pilih Provinsi --</option>' + options;
                provinceLoaded = true;

            } catch (err) {
                console.error('loadProvinces fetch error:', err);
                provinsiLainSel.innerHTML = '<option value="">Error load provinsi</option>';
            }
        }

        async function loadCities(provId) {
            if (!kotaLainSel) return;

            const pid = safeId(provId);
            if (!pid) {
                kotaLainSel.innerHTML = '<option value="">-- Pilih Kota --</option>';
                kotaLainSel.disabled = true;
                return;
            }

            kotaLainSel.innerHTML = '<option value="">Loading...</option>';
            kotaLainSel.disabled = true;

            if (kecLainSel) {
                kecLainSel.disabled = true;
                kecLainSel.innerHTML = '<option value="">-- Pilih Kecamatan --</option>';
            }
            if (kelLainSel) {
                kelLainSel.disabled = true;
                kelLainSel.innerHTML = '<option value="">-- Pilih Kelurahan --</option>';
            }
            if (postalLainInput) postalLainInput.value = '';

            try {
                const res = await fetch('./rajaongkir/get-cities.php?province=' + encodeURIComponent(pid) + '&t=' + Date.now());
                const txt = await res.text();

                let root;
                try {
                    root = JSON.parse(txt);
                } catch (e) {
                    console.error('City parse error:', e, txt);
                    kotaLainSel.innerHTML = '<option value="">Gagal load kota</option>';
                    return;
                }

                const list = normalizeList(root);
                if (!list.length) {
                    kotaLainSel.innerHTML = '<option value="">(Tidak ada kota)</option>';
                    kotaLainSel.disabled = false;
                    return;
                }

                const options = list.map(c => {
                    const id = safeId(c.city_id ?? c.id ?? c.cityId);
                    const name = safeText(c.city_name ?? c.name ?? c.cityName);
                    if (!id || !name) return '';
                    return `<option value="${id}">${name}</option>`;
                }).join('');

                kotaLainSel.innerHTML = '<option value="">-- Pilih Kota --</option>' + options;
                kotaLainSel.disabled = false;

            } catch (err) {
                console.error('loadCities fetch error:', err);
                kotaLainSel.innerHTML = '<option value="">Error load kota</option>';
            }
        }

        async function loadDistricts(cityId) {
            if (!kecLainSel) return;

            const cid = safeId(cityId);
            if (!cid) {
                kecLainSel.innerHTML = '<option value="">-- Pilih Kecamatan --</option>';
                kecLainSel.disabled = true;
                return;
            }

            kecLainSel.disabled = true;
            kecLainSel.innerHTML = '<option value="">Loading...</option>';

            if (kelLainSel) {
                kelLainSel.disabled = true;
                kelLainSel.innerHTML = '<option value="">-- Pilih Kelurahan --</option>';
            }
            if (postalLainInput) postalLainInput.value = '';

            try {
                const res = await fetch('./rajaongkir/get-district.php?city=' + encodeURIComponent(cid) + '&t=' + Date.now());
                const txt = await res.text();

                let root;
                try {
                    root = JSON.parse(txt);
                } catch (e) {
                    console.error('District parse error:', e, txt);
                    kecLainSel.innerHTML = '<option value="">Gagal load kecamatan</option>';
                    return;
                }

                const list = normalizeList(root);
                if (!list.length) {
                    kecLainSel.innerHTML = '<option value="">(Tidak ada kecamatan)</option>';
                    kecLainSel.disabled = false;
                    return;
                }

                const options = list.map(d => {
                    const id = safeId(d.subdistrict_id ?? d.id ?? d.subdistrictId ?? d.district_id);
                    const name = safeText(d.subdistrict_name ?? d.name ?? d.subdistrictName ?? d.district_name);
                    if (!id || !name) return '';
                    return `<option value="${id}">${name}</option>`;
                }).join('');

                kecLainSel.innerHTML = '<option value="">-- Pilih Kecamatan --</option>' + options;
                kecLainSel.disabled = false;

            } catch (err) {
                console.error('loadDistricts fetch error:', err);
                kecLainSel.innerHTML = '<option value="">Error load kecamatan</option>';
            }
        }

        async function loadSubDistricts(districtId) {
            if (!kelLainSel) return;

            const did = safeId(districtId);
            if (!did) {
                kelLainSel.innerHTML = '<option value="">-- Pilih Kelurahan --</option>';
                kelLainSel.disabled = true;
                return;
            }

            kelLainSel.disabled = true;
            kelLainSel.innerHTML = '<option value="">Loading...</option>';
            if (postalLainInput) postalLainInput.value = '';

            try {
                const res = await fetch('./rajaongkir/get-subdistrict.php?district=' + encodeURIComponent(did) + '&t=' + Date.now());
                const txt = await res.text();

                let root;
                try {
                    root = JSON.parse(txt);
                } catch (e) {
                    console.error('Subdistrict parse error:', e, txt);
                    kelLainSel.innerHTML = '<option value="">Gagal load kelurahan</option>';
                    return;
                }

                if (!root.success) {
                    kelLainSel.innerHTML = '<option value="">Gagal load kelurahan</option>';
                    kelLainSel.disabled = false;
                    return;
                }

                const list = normalizeList(root);
                if (!list.length) {
                    kelLainSel.innerHTML = '<option value="">(Tidak ada kelurahan)</option>';
                    kelLainSel.disabled = false;
                    return;
                }

                const options = list.map(s => {
                    const id = safeId(s.subdistrict_id ?? s.id);
                    const name = safeText(s.subdistrict_name ?? s.name);
                    const zip = safeText(s.zip_code ?? s.postal_code);
                    if (!id || !name) return '';
                    return `<option value="${id}" data-zip="${zip}">${name}${zip ? ' (' + zip + ')' : ''}</option>`;
                }).join('');

                kelLainSel.innerHTML = '<option value="">-- Pilih Kelurahan --</option>' + options;
                kelLainSel.disabled = false;

            } catch (err) {
                console.error('loadSubDistricts fetch error:', err);
                kelLainSel.innerHTML = '<option value="">Error load kelurahan</option>';
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
                shippingAddress.kelurahan = (profileAddress.kelurahan || '').trim();
                shippingAddress.alamat = (profileAddress.alamat || '').trim();
                shippingAddress.kodepos = (profileAddress.postal_code || '').trim();

                if (postalLainInput) postalLainInput.value = '';
                resetOngkir(true);
                return;
            }

            // custom
            hide(panelProfil);
            show(panelLain);
            shippingAddress.mode = 'custom';
            if (!provinceLoaded) loadProvinces().catch(console.error);

            resetOngkir(true);
        }

        document.addEventListener('click', e => {
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

        document.addEventListener('change', e => {
            const t = e.target;

            if (t === provinsiLainSel) {
                const provId = provinsiLainSel.value || '';
                if (!provId) {
                    resetOngkir(true);
                    return;
                }
                loadCities(provId).then(() => {
                    const pOpt = provinsiLainSel.selectedOptions[0];
                    shippingAddress.provinsi = pOpt ? pOpt.text : '';
                    shippingAddress.kota = '';
                    shippingAddress.kecamatan = '';
                    shippingAddress.kelurahan = '';
                    shippingAddress.kodepos = '';
                    resetOngkir(true);
                });
            }

            if (t === kotaLainSel) {
                const cOpt = kotaLainSel.selectedOptions[0];
                shippingAddress.kota = cOpt ? cOpt.text : '';
                loadDistricts(kotaLainSel.value).then(() => {
                    shippingAddress.kecamatan = '';
                    shippingAddress.kelurahan = '';
                    shippingAddress.kodepos = '';
                    resetOngkir(true);
                });
            }

            if (t === kecLainSel) {
                const kOpt = kecLainSel.selectedOptions[0];
                shippingAddress.kecamatan = kOpt ? kOpt.text : '';
                shippingAddress.kelurahan = '';
                shippingAddress.kodepos = '';
                resetOngkir(true);
                loadSubDistricts(kecLainSel.value).catch(console.error);
            }

            if (t === kelLainSel) {
                const lOpt = kelLainSel.selectedOptions[0];
                shippingAddress.kelurahan = lOpt ? lOpt.text : '';

                const zip = lOpt?.getAttribute('data-zip') || '';
                shippingAddress.kodepos = zip.trim();
                if (postalLainInput) postalLainInput.value = zip;

                resetOngkir(true);
            }
        });

        alamatLainTextarea?.addEventListener('input', () => {
            shippingAddress.alamat = (alamatLainTextarea.value || '').trim();
            resetOngkir(true);
        });

        // INIT
        applyAddressMode('profil');
        loadCouriers().catch(console.error);

        // ====================== ONGKIR / LAYANAN (BITESHIP) ======================
        async function loadServices(courier) {
            if (!courier) return;

            if (!isAddressValid()) {
                svcBox.innerHTML = '<div class="text-danger">Lengkapi alamat dulu ya bro.</div>';
                updateTotals(0);
                return;
            }

            const formData = new FormData();
            (selectedItems || []).forEach(id => formData.append('selected_items[]', id));
            if (isRepay && orderIdPHP) formData.append('order_id', orderIdPHP);
            if (isAuction && auctionIdPHP) formData.append('auction_id', auctionIdPHP);

            formData.append('alamat_mode', shippingAddress.mode || '');
            formData.append('provinsi', shippingAddress.provinsi || '');
            formData.append('kota', shippingAddress.kota || '');
            formData.append('kecamatan', shippingAddress.kecamatan || '');
            formData.append('alamat', shippingAddress.alamat || '');

            let destPostal = '';
            if (shippingAddress.mode === 'profil') {
                destPostal = (profileAddress.postal_code || '').trim();
            } else {
                destPostal = (shippingAddress.kodepos || '').trim();
            }
            if (!destPostal) {
                svcBox.innerHTML = '<div class="text-danger">Kode pos tujuan belum terisi atau tidak valid.</div>';
                updateTotals(0);
                return;
            }
            formData.append('destination_postal_code', destPostal);

            formData.append('code_courier', courier);
            formData.append('base_total', String(baseTotal));

            svcBox.textContent = 'Menghitung ongkir...';
            currentShipping.service = '';
            currentShipping.cost = 0;
            currentShipping.postal = destPostal;
            updateTotals(0);

            if (!isRepay) {
                btnPay.disabled = true;
            }

            try {
                const res = await fetch('biteship_calc_ongkir.php', {
                    method: 'POST',
                    body: formData
                });
                const txt = await res.text();
                console.log('biteship_calc_ongkir raw response:', txt);

                let data;
                try {
                    data = JSON.parse(txt);
                } catch {
                    throw new Error('Respon tidak valid dari biteship_calc_ongkir.php');
                }

                if (!data.success || !Array.isArray(data.services) || data.services.length === 0) {
                    svcBox.innerHTML = `<div class="text-danger">Gagal: ${data.message || 'Tidak ada layanan.'}</div>`;
                    updateTotals(0);
                    return;
                }

                // NORMALISASI: support bentuk baru (courier_service_code) & lama (service/cost)
                const servicesNorm = data.services
                    .map(s => {
                        const code = s.courier_service_code || s.service_code || s.service || '';
                        const label = s.courier_service_name || s.service_name || s.service || code;
                        const courierName = s.courier_name || s.courier || courier;
                        const cost = Number(s.price ?? s.cost ?? 0);
                        const etd = s.etd || s.duration || '';
                        return {
                            code,
                            label,
                            courierName,
                            cost,
                            etd
                        };
                    })
                    .filter(s => s.code && s.cost > 0);

                if (!servicesNorm.length) {
                    svcBox.innerHTML = `<div class="text-danger">Tidak ada layanan yang valid.</div>`;
                    updateTotals(0);
                    return;
                }

                svcBox.innerHTML = `
    <label class="form-label">Pilih Layanan</label>
    <select id="serviceSelect" class="form-select">
        ${servicesNorm.map(s => `
        <option value="${s.code}" data-code="${s.code}" data-cost="${s.cost}">
            ${String(s.courierName || courier).toUpperCase()} - ${s.label}
            ${s.etd ? `(ETD ${s.etd})` : ''}
            - Rp ${Number(s.cost).toLocaleString('id-ID')}
        </option>
        `).join('')}
    </select>
    <small class="text-muted">Harga & estimasi berdasarkan API Biteship.</small>
    `;

                const svc = document.getElementById('serviceSelect');
                const applyCost = () => {
                    const opt = svc.selectedOptions[0];
                    const cost = parseInt(opt?.dataset.cost || '0', 10);
                    const code = opt?.dataset.code || svc.value || '';
                    // INI yang nanti dikirim ke checkout.php → courier_type
                    currentShipping.service = code;
                    updateTotals(cost);
                };
                applyCost();
                svc.addEventListener('change', applyCost);

            } catch (err) {
                console.error('Error koneksi biteship_calc_ongkir:', err);
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
            // Mode repay: gak wajib pilih kurir baru
            if (!isRepay && !currentShipping.service) {
                alert("Pilih kurir & layanan pengiriman dulu.");
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
        <img id="qrImage"
            src="https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=${qrContent}"
            alt="QRIS"><br>
        <div class="qris-timer" id="timer">02:00</div>
        <form id="formQRIS" action="checkout.php" method="post">
            <input type="hidden" name="metode" value="QRIS">
            <input type="hidden" name="kode_transaksi" value="${qrContent}">
            <button type="submit" class="btn btn-primary mt-2">Cek Pembayaran</button>
        </form>
    </div>`;
                container.innerHTML = html;

                setTimeout(() => {
                    ensureHiddenInputs(document.getElementById('formQRIS'));
                }, 100);

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

                setTimeout(() => {
                    ensureHiddenInputs(document.getElementById('formTransfer'));
                }, 100);
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
                    if (qrImg)
                        qrImg.src = "https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=" + qrContent;
                    const kodeInput = document.querySelector("#formQRIS input[name='kode_transaksi']");
                    if (kodeInput) kodeInput.value = qrContent;

                    setTimeout(() => {
                        ensureHiddenInputs(document.getElementById('formQRIS'));
                    }, 100);

                    startQRISTimer();
                    alert("QR baru telah digenerate karena timeout.");
                }
            }, 1000);
        }
    </script>

</body>

</html>