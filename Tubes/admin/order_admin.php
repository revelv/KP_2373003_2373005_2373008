<?php
include 'koneksi.php';
include 'header_admin.php';

// ==============================================================================
// 1. HANDLER AKSI (LOGIC PHP)
// ==============================================================================

// --- Hapus Order ---
if (isset($_GET['hapus'])) {
    $id = mysqli_real_escape_string($conn, $_GET['hapus']);
    // Hapus data berelasi biar bersih
    mysqli_query($conn, "DELETE FROM order_details WHERE order_id='$id'");
    mysqli_query($conn, "DELETE FROM payments WHERE order_id='$id'");
    mysqli_query($conn, "DELETE FROM orders WHERE order_id='$id'");
    echo "<script>alert('Order berhasil dihapus'); window.location='order_admin.php';</script>";
    exit();
}

// --- VERIFIKASI / TOLAK PEMBAYARAN ---
if (isset($_POST['verifikasi_pembayaran'])) {
    $payment_id = mysqli_real_escape_string($conn, $_POST['payment_id']);
    $aksi       = mysqli_real_escape_string($conn, $_POST['aksi']); // 'verify' atau 'reject'
    
    // Tentukan status baru
    $status_baru = ($aksi === 'verify') ? 'verified' : 'rejected';

    mysqli_begin_transaction($conn);
    try {
        // 1. Update status di tabel payments
        mysqli_query($conn, "UPDATE payments SET payment_status='$status_baru' WHERE payment_id='$payment_id'");
        
        // Ambil data order_id dari payment ini buat update tabel orders juga
        $qry = mysqli_query($conn, "SELECT order_id FROM payments WHERE payment_id='$payment_id'");
        $dt  = mysqli_fetch_assoc($qry);
        $order_id = $dt['order_id'];

        // 2. Kalau REJECT (Ditolak), balikin stok (Opsional)
        if ($aksi === 'reject') {
             // Logic balikin stok bisa taruh sini kalau mau
             mysqli_query($conn, "UPDATE orders SET shipping_status='cancelled' WHERE order_id='$order_id'");
        } 
        // 3. Kalau VERIFIED (Diterima) -> Ubah status pengiriman jadi 'confirmed'
        else {
             // Biar di tabel order keliatan kalau udah dibayar
             mysqli_query($conn, "UPDATE orders SET shipping_status='confirmed' WHERE order_id='$order_id'");
        }

        mysqli_commit($conn);
        // Refresh halaman ke tab payment, filter order id yg sama biar user tetap di situ
        echo "<script>alert('Status pembayaran diubah jadi: $status_baru'); window.location='order_admin.php?view_payments=1&order_id=$order_id';</script>";
    } catch (Exception $e) {
        mysqli_rollback($conn);
        echo "<script>alert('Gagal: " . $e->getMessage() . "');</script>";
    }
}

// ==============================================================================
// 2. FILTER & PENCARIAN
// ==============================================================================
$search        = $_GET['search']     ?? '';
$search_by     = $_GET['search_by']  ?? 'customer';
$status_filter = $_GET['status']     ?? '';

// Query Dasar Orders
$query = "
  SELECT 
    o.order_id,
    c.customer_id,
    c.nama AS customer,
    o.tgl_order,
    o.total_harga,
    o.shipping_status,
    o.shipping_tracking_code,
    o.alamat, o.kelurahan, o.kecamatan, o.kota, o.provinsi
  FROM orders o
  JOIN customer c ON o.customer_id = c.customer_id
  WHERE 1=1
";

if (!empty($search)) {
    $safe_search = mysqli_real_escape_string($conn, $search);
    if ($search_by === 'customer') $query .= " AND c.nama LIKE '%$safe_search%'";
    if ($search_by === 'order_id') $query .= " AND o.order_id LIKE '%$safe_search%'";
}
if (!empty($status_filter)) {
    $safe_status = mysqli_real_escape_string($conn, $status_filter);
    $query .= " AND o.shipping_status = '$safe_status'";
}
$query .= " ORDER BY o.tgl_order DESC";
$result = mysqli_query($conn, $query);

// Query Dasar Payments (Hanya jalan kalau tab Payment dibuka)
if (isset($_GET['view_payments'])) {
    $order_filter = "";
    // Kalau ada order_id di URL (hasil klik tombol Payment), filter khusus order itu
    if (isset($_GET['order_id'])) {
        $oid = mysqli_real_escape_string($conn, $_GET['order_id']);
        $order_filter = " AND p.order_id = '$oid' ";
    }

    $payments_query = "
        SELECT 
            p.*,
            c.nama AS customer,
            o.shipping_status
        FROM payments p
        JOIN orders o   ON p.order_id = o.order_id
        JOIN customer c ON o.customer_id = c.customer_id
        WHERE 1=1 $order_filter
        ORDER BY p.tanggal_bayar DESC
    ";
    $payments_result = mysqli_query($conn, $payments_query);
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Data Order - Stryk Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-gray-900 text-white p-6">

    <h1 class="text-2xl font-bold text-yellow-400 mb-6">Order Dashboard</h1>

    <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <input type="text" name="search" class="w-full px-3 py-2 rounded bg-gray-700 border border-gray-600" placeholder="Cari..." value="<?= htmlspecialchars($search) ?>">
        <select name="search_by" class="w-full px-3 py-2 rounded bg-gray-700 border border-gray-600">
            <option value="customer" <?= $search_by=='customer'?'selected':'' ?>>Customer</option>
            <option value="order_id" <?= $search_by=='order_id'?'selected':'' ?>>Order ID</option>
        </select>
        <select name="status" class="w-full px-3 py-2 rounded bg-gray-700 border border-gray-600">
            <option value="">Semua Status</option>
            <option value="pending" <?= $status_filter=='pending'?'selected':'' ?>>Pending</option>
            <option value="confirmed" <?= $status_filter=='confirmed'?'selected':'' ?>>Confirmed</option>
        </select>
        <div class="flex gap-2">
            <button type="submit" class="bg-yellow-500 hover:bg-yellow-600 text-gray-900 font-bold py-2 px-4 rounded w-full">Cari</button>
            <a href="order_admin.php" class="bg-gray-600 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded w-full text-center">Reset</a>
        </div>
    </form>

    <div class="flex border-b border-gray-700 mb-6">
        <a href="order_admin.php" class="px-4 py-2 <?= !isset($_GET['view_payments']) ? 'border-b-2 border-yellow-400 text-yellow-400' : 'text-gray-400' ?>">Orders</a>
        <a href="order_admin.php?view_payments=1" class="px-4 py-2 <?= isset($_GET['view_payments']) ? 'border-b-2 border-yellow-400 text-yellow-400' : 'text-gray-400' ?>">Payment Proofs</a>
    </div>

    <?php if (!isset($_GET['view_payments'])): ?>
        <div class="bg-gray-800 rounded-lg shadow overflow-hidden">
            <table class="w-full">
                <thead class="bg-gray-700 text-yellow-400">
                    <tr>
                        <th class="p-3 text-left">ID Order</th>
                        <th class="p-3 text-left">Customer</th>
                        <th class="p-3 text-left">Tanggal</th>
                        <th class="p-3 text-left">Total</th>
                        <th class="p-3 text-left">Alamat</th>
                        <th class="p-3 text-left">Status</th>
                        <th class="p-3 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-700">
                    <?php while ($row = mysqli_fetch_assoc($result)) : ?>
                        <tr class="hover:bg-gray-700">
                            <td class="p-3"><?= $row['order_id'] ?></td>
                            <td class="p-3"><?= htmlspecialchars($row['customer']) ?></td>
                            <td class="p-3"><?= date('d/m/y H:i', strtotime($row['tgl_order'])) ?></td>
                            <td class="p-3">Rp <?= number_format($row['total_harga']) ?></td>
                            <td class="p-3 text-sm"><?= htmlspecialchars(substr($row['alamat'], 0, 30)) ?>...</td>
                            <td class="p-3">
                                <span class="px-2 py-1 rounded text-xs <?= $row['shipping_status']=='confirmed'?'bg-green-600':'bg-yellow-600' ?>">
                                    <?= htmlspecialchars($row['shipping_status'] ?? 'pending') ?>
                                </span>
                            </td>
                            <td class="p-3 text-center flex justify-center gap-2">
                                <a href="order_admin.php?view_payments=1&order_id=<?= $row['order_id'] ?>" 
                                   class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-1 rounded text-sm">
                                   Payment
                                </a>
                                <a href="order_admin.php?hapus=<?= $row['order_id'] ?>" onclick="return confirm('Hapus permanen?')" class="bg-red-600 hover:bg-red-700 text-white px-3 py-1 rounded text-sm">Hapus</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>

    <?php else: ?>
        <div class="bg-gray-800 rounded-lg shadow overflow-hidden">
            <div class="p-4 bg-gray-700 flex justify-between items-center">
                <h2 class="text-lg font-bold text-white">Detail Pembayaran</h2>
                <?php if(isset($_GET['order_id'])): ?>
                    <a href="order_admin.php" class="text-sm text-blue-300 hover:underline">← Kembali ke Semua Order</a>
                <?php endif; ?>
            </div>
            <table class="w-full">
                <thead class="bg-gray-700 text-yellow-400">
                    <tr>
                        <th class="p-3 text-left">Order ID</th>
                        <th class="p-3 text-left">Customer</th>
                        <th class="p-3 text-left">Metode</th>
                        <th class="p-3 text-left">Jumlah</th>
                        <th class="p-3 text-left">Bukti Bayar</th>
                        <th class="p-3 text-left">Status</th>
                        <th class="p-3 text-center">Verifikasi</th> </tr>
                </thead>
                <tbody class="divide-y divide-gray-700">
                    <?php if (mysqli_num_rows($payments_result) > 0): ?>
                        <?php while ($pay = mysqli_fetch_assoc($payments_result)) : ?>
                            <tr class="hover:bg-gray-700">
                                <td class="p-3 text-sm"><?= $pay['order_id'] ?></td>
                                <td class="p-3 text-sm"><?= htmlspecialchars($pay['customer']) ?></td>
                                <td class="p-3 text-sm"><?= $pay['metode'] ?></td>
                                <td class="p-3 text-sm">Rp <?= number_format($pay['jumlah_dibayar']) ?></td>
                                <td class="p-3">
                                    <?php if (!empty($pay['payment_proof'])): ?>
                                        <a href="#" onclick="openModal('../carts/payment_proofs/<?= $pay['payment_proof'] ?>')" class="flex items-center gap-2 text-blue-400 hover:underline">
                                            <i class="fas fa-image"></i> Lihat Foto
                                        </a>
                                    <?php else: ?>
                                        <span class="text-gray-500">-</span>
                                    <?php endif; ?>
                                </td>
                                <td class="p-3">
                                    <span class="px-2 py-1 rounded text-xs uppercase font-bold 
                                        <?= $pay['payment_status']=='verified'?'bg-green-600 text-white':($pay['payment_status']=='rejected'?'bg-red-600 text-white':'bg-yellow-600 text-black') ?>">
                                        <?= $pay['payment_status'] ?>
                                    </span>
                                </td>
                                
                                <td class="p-3 text-center">
                                    <?php if ($pay['payment_status'] === 'pending'): ?>
                                        <form method="POST" class="inline-flex gap-2">
                                            <input type="hidden" name="payment_id" value="<?= $pay['payment_id'] ?>">
                                            
                                            <button type="submit" name="verifikasi_pembayaran" value="1" onclick="this.form.aksi.value='verify'" 
                                                class="bg-green-500 hover:bg-green-600 text-white p-2 rounded shadow transition" title="Verifikasi">
                                                <i class="fas fa-check"></i>
                                            </button>
                                            
                                            <button type="submit" name="verifikasi_pembayaran" value="1" onclick="this.form.aksi.value='reject'" 
                                                class="bg-red-500 hover:bg-red-600 text-white p-2 rounded shadow transition" title="Tolak">
                                                <i class="fas fa-times"></i>
                                            </button>
                                            
                                            <input type="hidden" name="aksi" value="">
                                        </form>
                                    <?php else: ?>
                                        <span class="text-xs text-gray-400 italic">Selesai</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="7" class="p-4 text-center text-gray-500">Belum ada data pembayaran.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

    <div id="imageModal" class="fixed inset-0 bg-black bg-opacity-75 flex items-center justify-center z-50 hidden" onclick="closeModal()">
        <div class="bg-gray-800 p-2 rounded-lg max-w-4xl max-h-screen relative">
            <button onclick="closeModal()" class="absolute top-2 right-2 text-white bg-red-600 rounded-full w-8 h-8 flex items-center justify-center">
                <i class="fas fa-times"></i>
            </button>
            <img id="modalImage" src="" alt="Payment Proof" class="max-w-full max-h-[80vh] rounded">
        </div>
    </div>

    <script>
      function openModal(src) {
        document.getElementById('modalImage').src = src;
        document.getElementById('imageModal').classList.remove('hidden');
      }
      function closeModal() {
        document.getElementById('imageModal').classList.add('hidden');
      }
    </script>

</body>
</html>