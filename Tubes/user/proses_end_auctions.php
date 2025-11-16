<?php
// Skrip ini harus dijalankan oleh Cron Job / Task Scheduler
include 'koneksi.php';
// Pastikan file ini ada dan path-nya benar
include '../voucher.php'; // Ini baris yang benar

echo "Memulai skrip pengakhir lelang...\n";

// Ambil semua lelang yang aktif tapi waktunya sudah habis
$query = "SELECT * FROM auctions WHERE status = 'active' AND end_time < NOW()";
$result = mysqli_query($conn, $query);

if (!$result) {
    die("Query gagal: " . mysqli_error($conn));
}

while ($auction = mysqli_fetch_assoc($result)) {
    $auction_id = $auction['auction_id'];
    $winner_id = $auction['current_winner_id'];
    $product_id = $auction['product_id'];
    $final_bid = $auction['current_bid'];
    $auction_title = $auction['title'];

    // 1. Update status lelang jadi 'ended'
    $conn->query("UPDATE auctions SET status = 'ended' WHERE auction_id = $auction_id");

    if ($winner_id && $product_id) {
        // --- LELANG LAKU TERJUAL ---

        // 2. Kurangi stok di tabel products
        $conn->query("UPDATE products SET stok = stok - 1 WHERE product_id = '$product_id'");

        // 3. Buka kunci produknya (status_jual kembali 'dijual')
        $conn->query("UPDATE products SET status_jual = 'dijual' WHERE product_id = '$product_id'");

        // --- 4. BUAT ORDER UNTUK PEMENANG ---

        // Ambil data alamat si pemenang
        $cust_stmt = $conn->prepare("SELECT * FROM customer WHERE customer_id = ?");
        $cust_stmt->bind_param("i", $winner_id);
        $cust_stmt->execute();
        $customer = $cust_stmt->get_result()->fetch_assoc();
        $cust_stmt->close();

        if ($customer) {
            // Buat Order ID unik (mirip checkout Anda)
            $order_id = "STYRK_AUC_" . $auction_id . "_" . time();
            $tgl_order = date("Y-m-d H:i:s");
            $ongkir = 0; // Kita set 0, asumsi ongkir diurus terpisah/gratis
            $total_harga = $final_bid + $ongkir;

            // Masukkan ke tabel orders
            $order_stmt = $conn->prepare("INSERT INTO orders (order_id, customer_id, tgl_order, provinsi, kota, alamat, code_courier, ongkos_kirim, total_harga, status) VALUES (?, ?, ?, ?, ?, ?, 'jne', ?, ?, 'pending')");
            $order_stmt->bind_param("sissssdd", $order_id, $winner_id, $tgl_order, $customer['provinsi'], $customer['kota'], $customer['alamat'], $ongkir, $total_harga);
            $order_stmt->execute();

            // Masukkan ke tabel order_details
            $detail_stmt = $conn->prepare("INSERT INTO order_details (order_id, product_id, jumlah, harga_satuan, subtotal) VALUES (?, ?, 1, ?, ?)");
            $detail_stmt->bind_param("ssdd", $order_id, $product_id, $final_bid, $final_bid);
            $detail_stmt->execute();

            // 5. Kirim email notifikasi ke pemenang
            kirimEmailPemenangLelang($customer['email'], $customer['nama'], $auction_title, $total_harga, $order_id);

            echo "Lelang ID $auction_id berakhir. Pemenang: $winner_id. Order $order_id dibuat. Stok $product_id dikurangi.\n";
        }
    } else {
        // --- LELANG GAGAL (tidak ada penawar) ---

        // Buka kuncinya aja, balikin ke toko
        $conn->query("UPDATE products SET status_jual = 'dijual' WHERE product_id = '$product_id'");
        echo "Lelang ID $auction_id berakhir. Tidak ada pemenang. Kunci $product_id dibuka.\n";
    }
}

echo "Skrip selesai.\n";
$conn->close();
