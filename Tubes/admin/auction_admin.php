<?php
include 'auth_admin.php';
include 'header_admin.php'; // Navbar
include 'koneksi.php'; // Koneksi

// --------------------------------------------------------
// LOGIKA CRUD LELANG
// --------------------------------------------------------

$edit = null;

// --- 1. HANDLE HAPUS/BATALKAN LELANG (DELETE) ---
if (isset($_GET['hapus'])) {
    $auction_id = (int)$_GET['hapus'];
    
    $conn->begin_transaction();
    try {
        // 1. Ambil product_id yang terkait
        $stmt_get = $conn->prepare("SELECT product_id, status FROM auctions WHERE auction_id = ?");
        $stmt_get->bind_param("i", $auction_id);
        $stmt_get->execute();
        $auction = $stmt_get->get_result()->fetch_assoc();
        $stmt_get->close();
        
        if ($auction && $auction['product_id'] && $auction['status'] == 'active') {
            // 2. Buka Kunci Produk (kembalikan ke 'dijual')
            $stmt_unlock = $conn->prepare("UPDATE products SET status_jual = 'dijual' WHERE product_id = ?");
            $stmt_unlock->bind_param("s", $auction['product_id']);
            $stmt_unlock->execute();
            $stmt_unlock->close();
        }
        
        // 3. Hapus Lelangnya
        $stmt_del = $conn->prepare("DELETE FROM auctions WHERE auction_id = ?");
        $stmt_del->bind_param("i", $auction_id);
        $stmt_del->execute();
        $stmt_del->close();
        
        $conn->commit();
        echo "<script>alert('Lelang berhasil dibatalkan/dihapus.'); window.location='auction_admin.php';</script>";
    } catch (Exception $e) {
        $conn->rollback();
        echo "<script>alert('Gagal membatalkan lelang: " . $e->getMessage() . "'); window.location='auction_admin.php';</script>";
    }
    exit;
}

// --- 2. HANDLE AMBIL DATA UNTUK EDIT (READ FOR UPDATE) ---
if (isset($_GET['edit'])) {
    $auction_id = (int)$_GET['edit'];
    $res = $conn->prepare("SELECT a.*, p.nama_produk FROM auctions a LEFT JOIN products p ON a.product_id = p.product_id WHERE a.auction_id = ?");
    $res->bind_param("i", $auction_id);
    $res->execute();
    $edit = $res->get_result()->fetch_assoc();
}

// --- 3. HANDLE SIMPAN PERUBAHAN (UPDATE) ---
if (isset($_POST['update'])) {
    $auction_id = (int)$_POST['auction_id'];
    $start_price = (float)$_POST['start_price'];
    $end_time = $_POST['end_time'];
    
    // Validasi
    if (strtotime($end_time) <= time()) {
        echo "<script>alert('Waktu berakhir lelang harus di masa depan.'); window.location='auction_admin.php?edit=$auction_id';</script>";
        exit;
    }
    
    $stmt = $conn->prepare("UPDATE auctions SET start_price = ?, end_time = ? WHERE auction_id = ?");
    $stmt->bind_param("dsi", $start_price, $end_time, $auction_id);
    $stmt->execute();
    
    echo "<script>alert('Lelang berhasil diperbarui.'); window.location='auction_admin.php';</script>";
    exit;
}

// --- 4. HANDLE BUAT LELANG BARU (CREATE) ---
if (isset($_POST['insert'])) {
    $product_id_lelang = $_POST['product_id'];
    $admin_as_customer_id = 16; // Akun "Doni Salmanan" / ID 16
    $start_price = (float)$_POST['start_price'];
    $end_time = $_POST['end_time'];

    if (empty($product_id_lelang) || $start_price <= 0 || empty($end_time)) {
        echo "<script>alert('Semua field wajib diisi.'); window.location='auction_admin.php';</script>";
        exit;
    }
    if (strtotime($end_time) <= time()) {
        echo "<script>alert('Waktu berakhir lelang harus di masa depan.'); window.location='auction_admin.php';</script>";
        exit;
    }

    $conn->begin_transaction();
    try {
        // 1. Ambil data produk
        $stmt_prod = $conn->prepare("SELECT * FROM products WHERE product_id = ? AND stok > 0 AND status_jual = 'dijual' FOR UPDATE");
        $stmt_prod->bind_param("s", $product_id_lelang);
        $stmt_prod->execute();
        $product = $stmt_prod->get_result()->fetch_assoc();
        $stmt_prod->close();
        if (!$product) throw new Exception("Produk tidak ditemukan, stok habis, atau sedang dilelang.");

        // 2. Kunci produk
        $stmt_lock = $conn->prepare("UPDATE products SET status_jual = 'dilelang' WHERE product_id = ?");
        $stmt_lock->bind_param("s", $product_id_lelang);
        $stmt_lock->execute();
        $stmt_lock->close();

        // 3. Buat lelang
        $sql_auc = "INSERT INTO auctions (customer_id, product_id, title, description, image_url, start_price, current_bid, end_time, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'active')";
        $stmt_auc = $conn->prepare($sql_auc);
        $stmt_auc->bind_param("issssdds", $admin_as_customer_id, $product['product_id'], $product['nama_produk'], $product['deskripsi_produk'], $product['link_gambar'], $start_price, $start_price, $end_time);
        $stmt_auc->execute();
        $stmt_auc->close();

        $conn->commit();
        echo "<script>alert('Lelang berhasil dibuat.'); window.location='auction_admin.php';</script>";
    } catch (Exception $e) {
        $conn->rollback();
        echo "<script>alert('Gagal: " . $e->getMessage() . "'); window.location='auction_admin.php';</script>";
    }
    exit;
}

// --- 5. AMBIL DATA LELANG (READ FOR LIST) ---
$query_list = "
    SELECT a.*, p.nama_produk, c.nama as winner_name 
    FROM auctions a 
    LEFT JOIN products p ON a.product_id = p.product_id 
    LEFT JOIN customer c ON a.current_winner_id = c.customer_id
    ORDER BY a.status ASC, a.end_time DESC
";
$result_list = mysqli_query($conn, $query_list);

?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Manajemen Lelang - Stryk Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-900 text-white p-6">

    <h1 class="text-2xl font-bold text-yellow-400 mb-6">Manajemen Lelang</h1>

    <div class="bg-gray-800 rounded-lg shadow p-6 mb-8">
        
        <h2 class="text-xl font-semibold text-yellow-400 mb-4">
          <?= $edit ? 'Edit Lelang: ' . htmlspecialchars($edit['title']) : 'Publikasikan Lelang Baru' ?>
        </h2>
        
        <?php if (!$edit): ?>
        <p class="text-gray-400 mb-4">Pilih produk dari toko untuk dilelang. Produk akan "dikunci" dari toko biasa.</p>
        <?php endif; ?>

        <form action="auction_admin.php" method="POST" class="space-y-4">
            
            <?php if ($edit): ?>
                <input type="hidden" name="auction_id" value="<?= $edit['auction_id'] ?>">
                <div>
                    <label class="block text-gray-300 mb-1">Produk (Tidak bisa diubah)</label>
                    <input type="text" value="<?= htmlspecialchars($edit['nama_produk']) ?>" 
                           class="w-full px-3 py-2 rounded bg-gray-900 text-gray-400 border border-gray-700" readonly>
                </div>
            <?php else: ?>
                <div>
                    <label for="product_id" class="block text-gray-300 mb-1">Pilih Produk untuk Dilelang</label>
                    <select id="product_id" name="product_id" required class="w-full px-3 py-2 rounded bg-gray-700 text-white border border-gray-600">
                        <option value="" selected disabled>-- Pilih Produk (Stok > 0 & Tidak Dilelang) --</option>
                        <?php
                        $query_products = "SELECT product_id, nama_produk, stok, harga FROM products WHERE stok > 0 AND status_jual = 'dijual' ORDER BY nama_produk";
                        $result_products = mysqli_query($conn, $query_products);
                        while ($product = mysqli_fetch_assoc($result_products)) {
                            echo "<option value=\"" . htmlspecialchars($product['product_id']) . "\">" 
                                   . htmlspecialchars($product['nama_produk']) 
                                   . " (Stok: " . $product['stok'] . " | Rp " . number_format($product['harga'], 0, ',', '.') . ")"
                                   . "</option>";
                        }
                        ?>
                    </select>
                </div>
            <?php endif; ?>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="start_price" class="block text-gray-300 mb-1">Harga Buka (Rp)</label>
                    <input type="number" id="start_price" name="start_price" min="1000" required placeholder="Cth: 100000"
                           value="<?= $edit['start_price'] ?? '' ?>"
                           class="w-full px-3 py-2 rounded bg-gray-700 text-white border border-gray-600">
                </div>
                <div>
                    <label for="end_time" class="block text-gray-300 mb-1">Waktu Berakhir</lebel>
                    <?php
                    // Format waktu untuk input datetime-local
                    $end_time_value = $edit ? date('Y-m-d\TH:i', strtotime($edit['end_time'])) : '';
                    ?>
                    <input type="datetime-local" id="end_time" name="end_time" required
                           value="<?= $end_time_value ?>"
                           class="w-full px-3 py-2 rounded bg-gray-700 text-white border border-gray-600" style="color-scheme: dark;">
                </div>
            </div>

            <div class="flex pt-2 space-x-3">
                <button type="submit" name="<?= $edit ? 'update' : 'insert' ?>"
                        class="bg-yellow-500 hover:bg-yellow-600 text-gray-900 font-bold py-2 px-6 rounded">
                    <?= $edit ? 'Simpan Perubahan' : 'Publikasikan Lelang' ?>
                </button>
                <?php if ($edit): ?>
                    <a href="auction_admin.php" class="bg-gray-600 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">Batal Edit</a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <div class="bg-gray-800 rounded-lg shadow overflow-hidden">
        <h2 class="text-xl font-semibold text-yellow-400 p-6">Daftar Lelang Saat Ini</h2>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-700 text-yellow-400">
                    <tr>
                        <th class="py-3 px-4 text-left">Produk</th>
                        <th class="py-3 px-4 text-left">Harga Awal</th>
                        <th class="py-3 px-4 text-left">Harga Sekarang</th>
                        <th class="py-3 px-4 text-left">Pemenang</th>
                        <th class="py-3 px-4 text-left">Waktu Berakhir</th>
                        <th class="py-3 px-4 text-left">Status</th>
                        <th class="py-3 px-4 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-700">
                    <?php if (mysqli_num_rows($result_list) > 0): ?>
                        <?php while ($row = mysqli_fetch_assoc($result_list)): ?>
                        <tr class="hover:bg-gray-700">
                            <td class="py-3 px-4"><?= htmlspecialchars($row['nama_produk'] ?? 'N/A') ?></td>
                            <td class="py-3 px-4">Rp <?= number_format($row['start_price'], 0, ',', '.') ?></td>
                            <td class="py-3 px-4">Rp <?= number_format($row['current_bid'], 0, ',', '.') ?></td>
                            <td class="py-3 px-4"><?= htmlspecialchars($row['winner_name'] ?? 'Belum ada') ?></td>
                            <td class="py-3 px-4"><?= date('d M Y, H:i', strtotime($row['end_time'])) ?></td>
                            <td class="py-3 px-4">
                                <?php if ($row['status'] == 'active'): ?>
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-500 text-green-900">Aktif</span>
                                <?php elseif ($row['status'] == 'ended'): ?>
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full bg-gray-500 text-gray-900">Berakhir</span>
                                <?php else: ?>
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full bg-blue-500 text-blue-900">Terbayar</span>
                                <?php endif; ?>
                            </td>
                            <td class="py-3 px-4 text-center">
                                <div class="flex justify-center space-x-2">
                                    <?php if ($row['status'] == 'active'): // Hanya bisa edit lelang aktif ?>
                                    <a href="auction_admin.php?edit=<?= $row['auction_id'] ?>"
                                       class="bg-blue-500 hover:bg-blue-600 text-white px-3 py-1 rounded text-sm">Edit</a>
                                    <?php endif; ?>
                                    
                                    <a href="auction_admin.php?hapus=<?= $row['auction_id'] ?>" onclick="return confirm('Yakin ingin menghapus/membatalkan lelang ini?')"
                                       class="bg-red-500 hover:bg-red-600 text-white px-3 py-1 rounded text-sm">Hapus</a>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" class="text-center py-4 text-gray-400">Belum ada data lelang.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>