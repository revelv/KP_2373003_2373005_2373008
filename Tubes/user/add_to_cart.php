<?php
// 1. WAJIB session_start dulu
session_start();
include 'koneksi.php';

// 2. Cek login
if (!isset($_SESSION['kd_cs'])) {
    header("Location: login.php");
    exit();
}

// 3. Cek param product_id
if (!isset($_GET['product_id'])) {
    header("Location: produk.php");
    exit();
}

// Ambil data dari session & GET
$customer_id = intval($_SESSION['kd_cs']);   // ini kemungkinan INT (ID user)
$product_id  = mysqli_real_escape_string($conn, $_GET['product_id']); // <-- STRING, contoh 'KK002'

// (Opsional tapi sehat) cek stok barang
$stok_q = mysqli_query(
    $conn,
    "SELECT stok FROM products WHERE product_id = '$product_id'"
);
if ($stok_q && mysqli_num_rows($stok_q) > 0) {
    $rowStok = mysqli_fetch_assoc($stok_q);
    if ((int)$rowStok['stok'] < 1) {
        $_SESSION['message'] = "Stok produk habis.";
        header("Location: produk.php");
        exit();
    }
}

// Cek apakah barang ini sudah ada di cart user
$check_query = "
    SELECT jumlah_barang 
    FROM carts 
    WHERE customer_id = $customer_id 
      AND product_id = '$product_id'
";
$check_result = mysqli_query($conn, $check_query);

if ($check_result && mysqli_num_rows($check_result) > 0) {
    // Sudah ada -> update jumlah_barang = jumlah_barang + 1
    $update_query = "
        UPDATE carts 
        SET jumlah_barang = jumlah_barang + 1 
        WHERE customer_id = $customer_id 
          AND product_id = '$product_id'
    ";
    mysqli_query($conn, $update_query);
} else {
    // Belum ada -> insert baru
    $insert_query = "
        INSERT INTO carts (customer_id, product_id, jumlah_barang) 
        VALUES ($customer_id, '$product_id', 1)
    ";
    mysqli_query($conn, $insert_query);
}

// Kasih feedback kalau mau dipakai di halaman produk
$_SESSION['message'] = "Produk berhasil ditambahkan ke keranjang!";

// Redirect balik biar user gak spam refresh nambah qty lagi
header("Location: produk.php");
exit();
