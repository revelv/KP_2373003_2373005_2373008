<?php
include 'koneksi.php';
include 'header_admin.php';

// --- Hapus Order ---
if (isset($_GET['hapus'])) {
  $id = mysqli_real_escape_string($conn, $_GET['hapus']);
  mysqli_query($conn, "DELETE FROM orders WHERE order_id='$id'");
  echo "<script>alert('Order berhasil dihapus'); window.location='order_admin.php';</script>";
  exit();
}


// --- Update Payment Status (handler lama, skrg ga kepake krn form-nya dihapus) ---
if (isset($_POST['update_payment_status'])) {
  $payment_id = mysqli_real_escape_string($conn, $_POST['payment_id']);
  $status     = mysqli_real_escape_string($conn, $_POST['payment_status']);

  mysqli_begin_transaction($conn);

  try {
    // Update payment status
    mysqli_query($conn, "UPDATE payments SET payment_status='$status' WHERE payment_id='$payment_id'");

    if ($status === 'rejected') {
      // Ambil order_id
      $payment_query = mysqli_query($conn, "SELECT order_id FROM payments WHERE payment_id='$payment_id'");
      $payment_data  = mysqli_fetch_assoc($payment_query);
      $order_id      = $payment_data['order_id'];

      // Restore stok produk
      $order_items = mysqli_query($conn, "SELECT product_id, jumlah FROM order_details WHERE order_id='$order_id'");
      while ($item = mysqli_fetch_assoc($order_items)) {
        $product_id = $item['product_id'];
        $quantity   = (int) $item['jumlah'];
        mysqli_query($conn, "UPDATE products SET stok = stok + $quantity WHERE product_id='$product_id'");
      }

      // Tracking (kalau mau pakai)
      mysqli_query($conn, "INSERT INTO order_tracking (order_id, status, description) 
                           VALUES ('$order_id', 'batal', 'Pembayaran ditolak, silahkan belanja kembali.')");
    }

    mysqli_commit($conn);

    $redirect_url = 'order_admin.php';
    if (isset($_GET['view_payments'])) {
      $redirect_url .= '?view_payments=1';
    }
    echo "<script>alert('Status pembayaran diperbarui'); window.location='$redirect_url';</script>";
    exit();
  } catch (Exception $e) {
    mysqli_rollback($conn);
    echo "<script>alert('Error: " . addslashes($e->getMessage()) . "'); window.location='order_admin.php';</script>";
    exit();
  }
}

// --- Filter Pencarian ---
$search        = $_GET['search']     ?? '';
$search_by     = $_GET['search_by']  ?? 'customer';
$status_filter = $_GET['status']     ?? '';

// Query Orders (pakai komship_status)
$query = "
  SELECT 
    o.order_id,
    c.customer_id,
    c.nama AS customer,
    o.tgl_order,
    o.total_harga,
    o.komship_status,
    o.komship_order_no,
    o.komship_awb,
    o.provinsi,
    o.kota,
    o.kecamatan,
    o.kelurahan,
    o.alamat
  FROM orders o
  JOIN customer c ON o.customer_id = c.customer_id
  WHERE 1=1
";

if (!empty($search)) {
  $safe_search = mysqli_real_escape_string($conn, $search);
  switch ($search_by) {
    case 'customer':
      $query .= " AND c.nama LIKE '%$safe_search%'";
      break;
    case 'order_id':
      $query .= " AND o.order_id LIKE '%$safe_search%'";
      break;
    case 'total':
      $query .= " AND o.total_harga LIKE '%$safe_search%'";
      break;
  }
}

if (!empty($status_filter)) {
  $safe_status = mysqli_real_escape_string($conn, $status_filter);
  $query .= " AND o.komship_status = '$safe_status'";
}

$query  .= " ORDER BY o.tgl_order DESC";
$result = mysqli_query($conn, $query);

// Query Payment Proofs kalau view_payments=1
if (isset($_GET['view_payments'])) {
  $order_filter = isset($_GET['order_id'])
    ? "AND p.order_id = '" . mysqli_real_escape_string($conn, $_GET['order_id']) . "'"
    : "";

  $payments_query = "
    SELECT 
      p.*,
      c.nama AS customer,
      o.total_harga,
      o.komship_order_no,
      o.komship_status,
      o.komship_awb
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
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Data Order - Stryk Admin</title>
  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>

<body class="bg-gray-900 text-white p-6">

  <h1 class="text-2xl font-bold text-yellow-400 mb-6">Order Dashboard</h1>

  <!-- Filter Pencarian -->
  <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
    <div>
      <input type="text" name="search"
        class="w-full px-3 py-2 rounded bg-gray-700 text-white border border-gray-600"
        placeholder="Cari order..."
        value="<?= htmlspecialchars($search) ?>">
    </div>
    <div>
      <select name="search_by"
        class="w-full px-3 py-2 rounded bg-gray-700 text-white border border-gray-600">
        <option value="customer" <?= $search_by === 'customer' ? 'selected' : '' ?>>Customer</option>
        <option value="order_id" <?= $search_by === 'order_id' ? 'selected' : '' ?>>ID Order</option>
        <option value="total"    <?= $search_by === 'total'    ? 'selected' : '' ?>>Total Harga</option>
      </select>
    </div>
    <div>
      <select name="status"
        class="w-full px-3 py-2 rounded bg-gray-700 text-white border border-gray-600">
        <option value="">Semua Status Komship</option>
        <option value="pending" <?= $status_filter === 'pending' ? 'selected' : '' ?>>pending</option>
        <option value="SUCCESS" <?= $status_filter === 'SUCCESS' ? 'selected' : '' ?>>SUCCESS</option>
        <option value="FAILED"  <?= $status_filter === 'FAILED'  ? 'selected' : '' ?>>FAILED</option>
      </select>
    </div>
    <div class="grid grid-cols-2 gap-2">
      <button type="submit"
        class="bg-yellow-500 hover:bg-yellow-600 text-gray-900 font-bold py-2 px-4 rounded">
        🔍 Cari
      </button>
      <a href="order_admin.php"
        class="bg-gray-600 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded text-center">
        🔄 Refresh
      </a>
    </div>
  </form>

  <!-- Tab Navigation -->
  <div class="flex border-b border-gray-700 mb-6">
    <a href="order_admin.php"
      class="px-4 py-2 <?= !isset($_GET['view_payments']) ? 'border-b-2 border-yellow-400 text-yellow-400' : 'text-gray-400' ?> font-medium">
      Orders
    </a>
    <a href="order_admin.php?view_payments=1"
      class="px-4 py-2 <?= isset($_GET['view_payments']) ? 'border-b-2 border-yellow-400 text-yellow-400' : 'text-gray-400' ?> font-medium">
      Payment Proofs
    </a>
  </div>

  <?php if (!isset($_GET['view_payments'])): ?>
    <!-- Tabel Orders -->
    <div class="bg-gray-800 rounded-lg shadow overflow-hidden">
      <table class="w-full">
        <thead class="bg-gray-700 text-yellow-400">
          <tr>
            <th class="py-3 px-4 text-left">ID Order</th>
            <th class="py-3 px-4 text-left">Customer</th>
            <th class="py-3 px-4 text-left">Tanggal Order</th>
            <th class="py-3 px-4 text-left">Total</th>
            <th class="py-3 px-4 text-left">Alamat</th>
            <th class="py-3 px-4 text-left">Status Komship</th>
            <th class="py-3 px-4 text-center">Aksi</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-700">
          <?php while ($row = mysqli_fetch_assoc($result)) : ?>
            <?php
              $kstat      = (string)($row['komship_status'] ?? '');
              $kstatLower = strtolower($kstat);
              $badgeClass = match ($kstatLower) {
                  'pending'                    => 'bg-yellow-500 text-gray-900',
                  'success', 'delivered'       => 'bg-green-500 text-white',
                  'failed', 'error', 'batal',
                  'canceled'                   => 'bg-red-500 text-white',
                  default                      => 'bg-gray-600 text-white',
              };
              $alreadyKomship = !empty($row['komship_order_no']);

              // Susun alamat lengkap
              $alamat = trim((string)($row['alamat'] ?? ''));
              $kel    = trim((string)($row['kelurahan'] ?? ''));
              $kec    = trim((string)($row['kecamatan'] ?? ''));
              $kota   = trim((string)($row['kota'] ?? ''));
              $prov   = trim((string)($row['provinsi'] ?? ''));

              $alamatLengkap = $alamat;
              if ($kel  !== '') $alamatLengkap .= ', ' . $kel;
              if ($kec  !== '') $alamatLengkap .= ', ' . $kec;
              if ($kota !== '') $alamatLengkap .= ', ' . $kota;
              if ($prov !== '') $alamatLengkap .= ', ' . $prov;
            ?>
            <tr class="hover:bg-gray-700">
              <td class="py-3 px-4"><?= htmlspecialchars($row['order_id']) ?></td>
              <td class="py-3 px-4"><?= htmlspecialchars($row['customer']) ?></td>
              <td class="py-3 px-4"><?= date('d-m-Y H:i', strtotime($row['tgl_order'])) ?></td>
              <td class="py-3 px-4">Rp <?= number_format($row['total_harga'], 0, ',', '.') ?></td>

              <!-- Alamat -->
              <td class="py-3 px-4 text-sm">
                <?= nl2br(htmlspecialchars($alamatLengkap !== '' ? $alamatLengkap : '-')) ?>
              </td>

              <!-- KOMSHIP STATUS + info -->
              <td class="py-3 px-4 text-sm">
                <span class="inline-block px-2 py-1 rounded <?= $badgeClass ?>">
                  <?= $kstat !== '' ? htmlspecialchars($kstat) : 'belum dibuat' ?>
                </span>
                <?php if (!empty($row['komship_order_no'])): ?>
                  <div class="mt-1 text-xs text-gray-300">
                    Order No: <?= htmlspecialchars($row['komship_order_no']) ?><br>
                    AWB: <?= htmlspecialchars($row['komship_awb'] ?? '-') ?>
                  </div>
                <?php endif; ?>
              </td>

              <td class="py-3 px-4 text-center">
                <div class="flex flex-col items-center space-y-2">             

                  <div class="flex space-x-2">
                    <a href="order_admin.php?view_payments=1&order_id=<?= urlencode($row['order_id']) ?>"
                      class="bg-blue-500 hover:bg-blue-600 text-white px-3 py-1 rounded text-sm">
                      Payment
                    </a>

                    <a href="order_admin.php?hapus=<?= urlencode($row['order_id']) ?>"
                      onclick="return confirm('Yakin hapus order ini?')"
                      class="bg-red-500 hover:bg-red-600 text-white px-3 py-1 rounded text-sm">
                      Hapus
                    </a>
                  </div>
                </div>
              </td>
            </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    </div>

  <?php else: ?>
    <!-- Payment Proofs Section -->
    <div class="bg-gray-800 rounded-lg shadow overflow-hidden">
      <table class="w-full">
        <thead class="bg-gray-700 text-yellow-400">
          <tr>
            <th class="py-3 px-4 text-left">Order ID</th>
            <th class="py-3 px-4 text-left">Customer</th>
            <th class="py-3 px-4 text-left">Payment Method</th>
            <th class="py-3 px-4 text-left">Amount</th>
            <th class="py-3 px-4 text-left">Payment Date</th>
            <th class="py-3 px-4 text-left">Status & Komship</th>
            <th class="py-3 px-4 text-left">Proof</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-700">
          <?php while ($payment = mysqli_fetch_assoc($payments_result)): ?>
            <?php
              $pstat = (string)$payment['payment_status'];
              $pBadge = match ($pstat) {
                'pending'  => 'bg-yellow-500 text-gray-900',
                'verified' => 'bg-green-500 text-white',
                'rejected' => 'bg-red-500 text-white',
                default    => 'bg-gray-600 text-white',
              };

              $kstat         = (string)($payment['komship_status'] ?? '');
              $alreadyKomship = !empty($payment['komship_order_no']);
            ?>
            <tr class="hover:bg-gray-700">
              <td class="py-3 px-4"><?= htmlspecialchars($payment['order_id']) ?></td>
              <td class="py-3 px-4"><?= htmlspecialchars($payment['customer']) ?></td>
              <td class="py-3 px-4"><?= htmlspecialchars($payment['metode']) ?></td>
              <td class="py-3 px-4">Rp <?= number_format($payment['jumlah_dibayar'], 0, ',', '.') ?></td>
              <td class="py-3 px-4"><?= date('d-m-Y H:i', strtotime($payment['tanggal_bayar'])) ?></td>

              <!-- STATUS + BUTTON CREATE ORDER -->
              <td class="py-3 px-4 text-sm">
                <div class="mb-1">
                  <span class="inline-block px-2 py-1 rounded <?= $pBadge ?>">
                    <?= htmlspecialchars($pstat) ?>
                  </span>
                </div>
                <div class="text-xs text-gray-300 mb-2">
                  Komship:
                  <?= $kstat !== '' ? htmlspecialchars($kstat) : 'belum dibuat' ?>
                  <?php if (!empty($payment['komship_order_no'])): ?>
                    <br>Order No: <?= htmlspecialchars($payment['komship_order_no']) ?>
                    <br>AWB: <?= htmlspecialchars($payment['komship_awb'] ?? '-') ?>
                  <?php endif; ?>
                </div>

              <!-- PROOF -->
              <td class="py-3 px-4">
                <?php if ($payment['metode'] === 'Transfer Bank'): ?>
                  <?php if (!empty($payment['payment_proof']) && file_exists($payment['payment_proof'])): ?>
                    <a href="#"
                      onclick="openModal('<?= $payment['payment_proof'] ?>')"
                      class="text-blue-400 hover:text-blue-300">
                      View Proof
                    </a>
                  <?php else: ?>
                    No proof uploaded
                  <?php endif; ?>
                <?php elseif ($payment['metode'] === 'QRIS'): ?>
                  <span class="text-green-400 break-all">
                    <?= htmlspecialchars($payment['payment_proof']) ?>
                  </span>
                <?php else: ?>
                  -
                <?php endif; ?>
              </td>
            </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    </div>

    <!-- Image Modal -->
    <div id="imageModal"
      class="fixed inset-0 bg-black bg-opacity-75 flex items-center justify-center z-50 hidden">
      <div class="bg-gray-800 p-4 rounded-lg max-w-4xl max-h-screen">
        <div class="flex justify-between items-center mb-4">
          <h3 class="text-xl font-bold">Payment Proof</h3>
          <button onclick="closeModal()" class="text-gray-400 hover:text-white">
            <i class="fas fa-times"></i>
          </button>
        </div>
        <img id="modalImage" src="" alt="Payment Proof" class="max-w-full max-h-[80vh]">
      </div>
    </div>

    <script>
      function openModal(imageSrc) {
        document.getElementById('modalImage').src = imageSrc;
        document.getElementById('imageModal').classList.remove('hidden');
      }

      function closeModal() {
        document.getElementById('imageModal').classList.add('hidden');
      }
    </script>
  <?php endif; ?>
</body>

</html>
