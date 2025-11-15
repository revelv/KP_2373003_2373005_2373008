<?php
// Skrip ini harus dijalankan oleh Cron Job / Task Scheduler
include 'koneksi.php'; // Sesuaikan path jika file ini ada di root

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
    $product_id = $auction['product_id']; // ID produk asli dari tabel products

    if (!$product_id) {
        echo "Lelang ID $auction_id dilewati (tidak ada product_id terkait).\n";
        continue;
    }

    // 1. Update status lelang jadi 'ended'
    $conn->query("UPDATE auctions SET status = 'ended' WHERE auction_id = $auction_id");

    if ($winner_id) {
        // --- LELANG LAKU TERJUAL ---
        
        // 2. Kurangi stok di tabel products
        $conn->query("UPDATE products SET stok = stok - 1 WHERE product_id = '$product_id'");
        
        // 3. Buka kunci produknya (status_jual kembali 'dijual')
        $conn->query("UPDATE products SET status_jual = 'dijual' WHERE product_id = '$product_id'");
        
        echo "Lelang ID $auction_id berakhir. Pemenang: $winner_id. Stok $product_id dikurangi 1 dan kunci dibuka.\n";
        
        // TODO: Kirim email ke pemenang, buat order, dll.
        
    } else {
        // --- LELANG GAGAL (tidak ada penawar) ---
        
        // 2. STOK TIDAK BERKURANG
        
        // 3. Cukup buka kuncinya aja, balikin ke toko
        $conn->query("UPDATE products SET status_jual = 'dijual' WHERE product_id = '$product_id'");
        
        echo "Lelang ID $auction_id berakhir. Tidak ada pemenang. Kunci $product_id dibuka.\n";
    }
}

echo "Skrip selesai.\n";
$conn->close();
?>