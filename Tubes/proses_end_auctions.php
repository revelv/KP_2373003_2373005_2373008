<?php
/**
 * Skrip pengakhir lelang
 * - Bisa dijalankan via Cron/Task Scheduler (CLI)
 * - Bisa di-include dari header.php sebagai background job
 */

// DETEKSI MODE: CLI atau web
$is_cli = (PHP_SAPI === 'cli');

// === 1. SIAPIN KONEKSI & VOUCHER ===
$base_dir = __DIR__;
$root_dir = realpath($base_dir . '/..'); // kalau file ini di /user, root = ../
// Kalau file ini di root, $root_dir bakal ke atas lagi, jadi kita cek dua-duanya

// koneksi.php
if (file_exists($base_dir . '/koneksi.php')) {
    require_once $base_dir . '/koneksi.php';
} elseif ($root_dir && file_exists($root_dir . '/koneksi.php')) {
    require_once $root_dir . '/koneksi.php';
} else {
    die("koneksi.php tidak ditemukan.\n");
}

// voucher.php
if (file_exists($base_dir . '/voucher.php')) {
    require_once $base_dir . '/voucher.php';
} elseif ($root_dir && file_exists($root_dir . '/voucher.php')) {
    require_once $root_dir . '/voucher.php';
}

// helper log (biar kalau web -> masuk error_log, kalau CLI -> echo)
function log_lelang($msg)
{
    global $is_cli;
    if ($is_cli) {
        echo $msg . PHP_EOL;
    } else {
        error_log('[proses_end_auctions] ' . $msg);
    }
}

log_lelang("Memulai skrip pengakhir lelang...");

// === 2. AMBIL SEMUA LELANG YANG SUDAH HABIS WAKTU TAPI MASIH ACTIVE ===
$sql = "SELECT * FROM auctions WHERE status = 'active' AND end_time < NOW()";
$result = mysqli_query($conn, $sql);

if (!$result) {
    log_lelang("Query gagal: " . mysqli_error($conn));
    // jangan matiin aplikasi utama
    return;
}

while ($auction = mysqli_fetch_assoc($result)) {
    $auction_id    = (int)$auction['auction_id'];
    $winner_id     = (int)$auction['current_winner_id'];
    $product_id    = (int)$auction['product_id'];
    $final_bid     = (int)$auction['current_bid'];
    $auction_title = $auction['title'];

    // 1. Update status lelang jadi 'ended'
    $conn->query("UPDATE auctions SET status = 'ended' WHERE auction_id = {$auction_id}");

    if ($winner_id > 0 && $product_id > 0 && $final_bid > 0) {
        // === LELANG ADA PEMENANG ===

        // 2. Kurangi stok di tabel products
        $conn->query("UPDATE products SET stok = stok - 1 WHERE product_id = {$product_id}");

        // 3. (Opsional) Buka kunci produknya lagi
        $conn->query("UPDATE products SET status_jual = 'dijual' WHERE product_id = {$product_id}");

        // 4. Ambil data customer
        $cust_stmt = $conn->prepare("SELECT * FROM customer WHERE customer_id = ? LIMIT 1");
        if ($cust_stmt) {
            $cust_stmt->bind_param("i", $winner_id);
            $cust_stmt->execute();
            $customer = $cust_stmt->get_result()->fetch_assoc();
            $cust_stmt->close();
        } else {
            $customer = null;
        }

        if ($customer) {
            // Buat Order ID unik
            $order_id   = "STYRK_AUC_" . $auction_id . "_" . time();
            $tgl_order  = date("Y-m-d H:i:s");
            $ongkir     = 0;
            $total_harga = $final_bid + $ongkir;

            // Data alamat (fallback ke string kosong biar gak null error)
            $prov = $customer['provinsi']    ?? '';
            $kota = $customer['kota']        ?? '';
            $kec  = $customer['kecamatan']   ?? '';
            $kel  = $customer['kelurahan']   ?? '';
            $pos  = $customer['postal_code'] ?? '';
            $addr = $customer['alamat']      ?? '';

            // 5. INSERT ke orders
            $sql_insert = "
                INSERT INTO orders 
                (order_id, customer_id, tgl_order, provinsi, kota, kecamatan, kelurahan, postal_code, alamat, code_courier, ongkos_kirim, total_harga)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'jne', ?, ?)
            ";

            $order_stmt = $conn->prepare($sql_insert);
            if ($order_stmt) {
                // 11 parameter: s i s s s s s s s i i
                $order_stmt->bind_param(
                    "sisssssssii",
                    $order_id,
                    $winner_id,
                    $tgl_order,
                    $prov,
                    $kota,
                    $kec,
                    $kel,
                    $pos,
                    $addr,
                    $ongkir,
                    $total_harga
                );

                if ($order_stmt->execute()) {
                    // 6. INSERT ke order_details
                    $detail_stmt = $conn->prepare("
                        INSERT INTO order_details (order_id, product_id, jumlah, harga_satuan, subtotal)
                        VALUES (?, ?, 1, ?, ?)
                    ");
                    if ($detail_stmt) {
                        // order_id (s), product_id (i), harga (i), subtotal (i)
                        $detail_stmt->bind_param("siii", $order_id, $product_id, $final_bid, $final_bid);
                        $detail_stmt->execute();
                        $detail_stmt->close();
                    }

                    // 7. Kirim email ke pemenang kalau fungsi tersedia
                    if (function_exists('kirimEmailPemenangLelang')) {
                        kirimEmailPemenangLelang(
                            $customer['email'],
                            $customer['nama'],
                            $auction_title,
                            $total_harga,
                            $order_id
                        );
                    }

                    log_lelang("Lelang ID {$auction_id} berakhir. Pemenang: {$winner_id}. Order {$order_id} dibuat.");
                } else {
                    log_lelang("Gagal execute insert order lelang {$auction_id}: " . $order_stmt->error);
                }

                $order_stmt->close();
            } else {
                log_lelang("Gagal prepare insert order: " . $conn->error);
            }
        } else {
            log_lelang("Data customer pemenang {$winner_id} tidak ditemukan untuk lelang {$auction_id}.");
        }
    } else {
        // === LELANG GAGAL (tidak ada penawar) ===
        $conn->query("UPDATE products SET status_jual = 'dijual' WHERE product_id = {$product_id}");
        log_lelang("Lelang ID {$auction_id} berakhir tanpa pemenang. Produk {$product_id} dibuka kembali.");
    }
}

log_lelang("Skrip pengakhir lelang selesai.");

// PENTING: JANGAN TUTUP KONEKSI KALAU DIPANGGIL DARI WEB
if ($is_cli) {
    $conn->close();
}
