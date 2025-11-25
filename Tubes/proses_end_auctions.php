<?php
/**
 * Skrip pengakhir lelang
 * - Bisa dijalankan via Cron/Task Scheduler (CLI)
 * - Bisa di-include dari header.php sebagai background job
 */

$is_cli = (PHP_SAPI === 'cli');

// === 1. SIAPIN KONEKSI & VOUCHER ===
$base_dir = __DIR__;
$root_dir = realpath($base_dir . '/..');

// koneksi.php
if (file_exists($base_dir . '/koneksi.php')) {
    require_once $base_dir . '/koneksi.php';
} elseif ($root_dir && file_exists($root_dir . '/koneksi.php')) {
    require_once $root_dir . '/koneksi.php';
} else {
    if ($is_cli) {
        echo "koneksi.php tidak ditemukan\n";
    }
    return; // jangan matiin web, cukup keluar dari script ini
}

// voucher.php (opsional, buat kirim email dan fungsi voucher)
if (file_exists($base_dir . '/voucher.php')) {
    require_once $base_dir . '/voucher.php';
} elseif ($root_dir && file_exists($root_dir . '/voucher.php')) {
    require_once $root_dir . '/voucher.php';
}

// helper log
function log_lelang($msg)
{
    global $is_cli;
    if ($is_cli) {
        echo $msg . PHP_EOL;
    } else {
        error_log('[proses_end_auctions] ' . $msg);
    }
}

log_lelang('Memulai skrip pengakhir lelang...');

// === 2. AMBIL SEMUA LELANG YANG SUDAH HABIS WAKTU TAPI MASIH ACTIVE ===
$sql = "SELECT * FROM auctions WHERE status = 'active' AND end_time < NOW()";
$result = mysqli_query($conn, $sql);

if (!$result) {
    log_lelang('Query gagal: ' . mysqli_error($conn));
    return;
}

while ($auction = mysqli_fetch_assoc($result)) {
    $auction_id    = (int)$auction['auction_id'];
    $winner_id     = (int)($auction['current_winner_id'] ?? 0);
    $product_id    = trim((string)($auction['product_id'] ?? '')); // VARCHAR seperti 'COMM-2'
    $final_bid     = (float)$auction['current_bid'];
    $auction_title = $auction['title'];

    // 1) Update status lelang jadi ended
    mysqli_query($conn, "UPDATE auctions SET status = 'ended' WHERE auction_id = {$auction_id}");

    if ($winner_id > 0 && $product_id !== '' && $final_bid > 0) {
        // === LELANG ADA PEMENANG ===

        // 2) Kurangi stok di products (pakai prepared + VARCHAR)
        if ($u1 = $conn->prepare('UPDATE products SET stok = stok - 1 WHERE product_id = ?')) {
            $u1->bind_param('s', $product_id);
            $u1->execute();
            $u1->close();
        }

        // 3) Buka kunci status_jual (balik ke 'dijual')
        if ($u2 = $conn->prepare("UPDATE products SET status_jual = 'dijual' WHERE product_id = ?")) {
            $u2->bind_param('s', $product_id);
            $u2->execute();
            $u2->close();
        }

        // 4) Ambil data customer pemenang
        $customer = null;
        if ($c = $conn->prepare('SELECT * FROM customer WHERE customer_id = ? LIMIT 1')) {
            $c->bind_param('i', $winner_id);
            $c->execute();
            $res_c = $c->get_result();
            $customer = $res_c ? $res_c->fetch_assoc() : null;
            $c->close();
        }

        if ($customer) {
            // 5) Siapkan data order
            $order_id      = 'STYRK_AUC_' . $auction_id . '_' . time();
            $tgl_order     = date('Y-m-d H:i:s');
            $ongkir        = 0;
            $total_harga   = (float)$final_bid + $ongkir;
            $code_courier  = 'manual_lelang'; // bebas, cuma label
            $shipping_type = 'auction';       // WAJIB diisi (NOT NULL di DB)

            $prov = $customer['provinsi']    ?? '';
            $kota = $customer['kota']        ?? '';
            $kec  = $customer['kecamatan']   ?? '';
            $kel  = $customer['kelurahan']   ?? '';
            $pos  = $customer['postal_code'] ?? '';
            $addr = $customer['alamat']      ?? '';

            // 6) INSERT ke orders
            $sql_insert = "
                INSERT INTO orders
                (order_id, customer_id, auction_id, tgl_order, provinsi, kota, kecamatan, kelurahan, postal_code, alamat,
                 code_courier, shipping_type, ongkos_kirim, total_harga)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ";

            if ($stmt = $conn->prepare($sql_insert)) {
                // urutan tipe: s i i s s s s s s s s s i d (14 param)
                $stmt->bind_param(
                    'siisssssssssid',
                    $order_id,      // s
                    $winner_id,     // i
                    $auction_id,    // i
                    $tgl_order,     // s
                    $prov,          // s
                    $kota,          // s
                    $kec,           // s
                    $kel,           // s
                    $pos,           // s
                    $addr,          // s
                    $code_courier,  // s
                    $shipping_type, // s
                    $ongkir,        // i
                    $total_harga    // d
                );

                if ($stmt->execute()) {
                    $stmt->close();

                    // 7) Insert ke order_details (qty 1, harga = final_bid)
                    if ($d = $conn->prepare("
                        INSERT INTO order_details (order_id, product_id, jumlah, harga_satuan, subtotal)
                        VALUES (?, ?, 1, ?, ?)
                    ")) {
                        $harga_int = (int)round($final_bid);
                        $subtotal  = $harga_int;

                        // order_id (s), product_id (s), harga_satuan (i), subtotal (i)
                        $d->bind_param('ssii', $order_id, $product_id, $harga_int, $subtotal);
                        $d->execute();
                        $d->close();
                    }

                    // 8) Kirim email ke pemenang (kalau fungsi-nya ada)
                    if (function_exists('kirimEmailPemenangLelang')) {
                        kirimEmailPemenangLelang(
                            $customer['email'],
                            $customer['nama'],
                            $auction_title,
                            $total_harga,
                            $order_id
                        );
                    }

                    log_lelang("Lelang {$auction_id} berakhir. Pemenang {$winner_id}. Order {$order_id} dibuat.");
                } else {
                    log_lelang('Gagal insert order untuk lelang ' . $auction_id . ': ' . $stmt->error);
                    $stmt->close();
                }
            } else {
                log_lelang('Gagal prepare INSERT orders: ' . $conn->error);
            }
        } else {
            log_lelang("Data customer pemenang {$winner_id} tidak ditemukan untuk lelang {$auction_id}.");
        }
    } else {
        // === LELANG TANPA PEMENANG ===
        if ($product_id !== '') {
            if ($u3 = $conn->prepare("UPDATE products SET status_jual = 'dijual' WHERE product_id = ?")) {
                $u3->bind_param('s', $product_id);
                $u3->execute();
                $u3->close();
            }
        }
        log_lelang("Lelang {$auction_id} berakhir tanpa pemenang. Produk '{$product_id}' dibuka kembali.");
    }
}

log_lelang('Skrip pengakhir lelang selesai.');

// Kalau dipanggil via CLI, boleh tutup koneksi
if ($is_cli && isset($conn) && $conn instanceof mysqli) {
    $conn->close();
}
