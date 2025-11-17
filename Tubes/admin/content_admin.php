<?php
include 'auth_admin.php';
include 'header_admin.php';
include 'koneksi.php';

// ========== CREATE / UPDATE ==========
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id            = isset($_POST['article_id']) ? (int)$_POST['article_id'] : 0;
    $title         = trim($_POST['title'] ?? '');
    $content       = trim($_POST['content'] ?? '');
    $image_url     = trim($_POST['image_url'] ?? '');
    $product_price = (int)($_POST['product_price'] ?? 0);
    $publish       = isset($_POST['is_published']) ? 1 : 0;

    if ($title === '' || $content === '' || $product_price <= 0) {
        $error = "Judul, konten, dan harga wajib diisi dan harga harus > 0.";
    } else {
        if ($id > 0) {
            // UPDATE
            $stmt = $conn->prepare("
                UPDATE community_articles 
                SET title = ?, content = ?, image_url = ?, product_price = ?, is_published = ?
                WHERE article_id = ?
            ");
            $stmt->bind_param('sssiii', $title, $content, $image_url, $product_price, $publish, $id);
        } else {
            // INSERT BARU
            $stmt = $conn->prepare("
                INSERT INTO community_articles (title, content, image_url, product_price, is_published)
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->bind_param('sssii', $title, $content, $image_url, $product_price, $publish);
        }

        if ($stmt->execute()) {
            header('Location: content_admin.php?success=1');
            exit;
        } else {
            $error = "Gagal menyimpan: " . $stmt->error;
        }
        $stmt->close();
    }
}

// ========== DELETE ==========
if (isset($_GET['delete'])) {
    $del_id = (int)$_GET['delete'];
    $conn->query("DELETE FROM community_articles WHERE article_id = {$del_id}");
    header('Location: content_admin.php?deleted=1');
    exit;
}

// ========== LOAD UNTUK EDIT ==========
$edit_data = null;
if (isset($_GET['edit'])) {
    $edit_id = (int)$_GET['edit'];
    $resEdit = $conn->query("
        SELECT * FROM community_articles 
        WHERE article_id = {$edit_id} 
        LIMIT 1
    ");
    $edit_data = $resEdit->fetch_assoc();
}

// ========== LIST DATA ==========
$list = $conn->query("
    SELECT * FROM community_articles 
    ORDER BY created_at DESC
");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Manajemen Konten Komunitas - Stryk Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>

<body class="bg-gray-900 text-white p-6">

    <h1 class="text-2xl font-bold text-yellow-400 mb-2">Manajemen Konten Komunitas</h1>
    <p class="text-gray-400 mb-6 text-sm">
        Buat konten produk untuk komunitas. 
    </p>

    <?php if (!empty($error)): ?>
        <div class="mb-4 px-4 py-3 rounded bg-red-500 text-white text-sm">
            <?= htmlspecialchars($error); ?>
        </div>
    <?php elseif (isset($_GET['success'])): ?>
        <div class="mb-4 px-4 py-3 rounded bg-green-500 text-gray-900 text-sm">
            Konten berhasil disimpan.
        </div>
    <?php elseif (isset($_GET['deleted'])): ?>
        <div class="mb-4 px-4 py-3 rounded bg-green-500 text-gray-900 text-sm">
            Konten berhasil dihapus.
        </div>
    <?php endif; ?>

    <!-- FORM KONTEN -->
    <div class="bg-gray-800 rounded-lg shadow p-6 mb-8">
        <h2 class="text-xl font-semibold text-yellow-400 mb-2">
            <?= $edit_data ? 'Edit Konten: ' . htmlspecialchars($edit_data['title']) : 'Publikasikan Konten Produk Baru'; ?>
        </h2>

        <form action="content_admin.php" method="POST" class="space-y-4">
            <input type="hidden" name="article_id" value="<?= $edit_data['article_id'] ?? 0; ?>">

            <div>
                <label class="block text-gray-300 mb-1">Nama Produk / Judul Konten</label>
                <input
                    type="text"
                    name="title"
                    required
                    placeholder='Nama Keyboard'
                    value="<?= htmlspecialchars($edit_data['title'] ?? ''); ?>"
                    class="w-full px-3 py-2 rounded bg-gray-700 text-white border border-gray-600 placeholder-gray-400 focus:outline-none focus:ring-1 focus:ring-yellow-400"
                >
            </div>

            <div>
                <label class="block text-gray-300 mb-1">Deskripsi Singkat</label>
                <textarea
                    name="content"
                    rows="6"
                    required
                    placeholder="Tulis keunikan keyboard, fitur wireless, gasket mount, MOQ terbatas, dll..."
                    class="w-full px-3 py-2 rounded bg-gray-700 text-white border border-gray-600 placeholder-gray-400 focus:outline-none focus:ring-1 focus:ring-yellow-400"
                ><?= htmlspecialchars($edit_data['content'] ?? ''); ?></textarea>
            </div>

            <div>
                <label class="block text-gray-300 mb-1">URL Gambar Produk</label>
                <input
                    type="text"
                    name="image_url"
                    placeholder="https://contoh.com/noir-timeless-ntl.jpg"
                    value="<?= htmlspecialchars($edit_data['image_url'] ?? ''); ?>"
                    class="w-full px-3 py-2 rounded bg-gray-700 text-white border border-gray-600 placeholder-gray-400 focus:outline-none focus:ring-1 focus:ring-yellow-400"
                >
            </div>

            <div>
                <label class="block text-gray-300 mb-1">Harga Produk (Rp)</label>
                <input
                    type="number"
                    name="product_price"
                    min="1000"
                    required
                    placeholder="Cth: 4500000"
                    value="<?= htmlspecialchars($edit_data['product_price'] ?? ''); ?>"
                    class="w-full px-3 py-2 rounded bg-gray-700 text-white border border-gray-600 placeholder-gray-400 focus:outline-none focus:ring-1 focus:ring-yellow-400"
                >
            </div>

            <div class="flex items-center space-x-2">
                <input
                    id="publish"
                    type="checkbox"
                    name="is_published"
                    class="h-4 w-4 text-yellow-400 bg-gray-700 border-gray-600 rounded focus:ring-yellow-500"
                    <?= isset($edit_data['is_published']) ? ($edit_data['is_published'] ? 'checked' : '') : 'checked'; ?>
                >
                <label for="publish" class="text-sm text-gray-300">
                    Publish konten (tampilkan di halaman komunitas)
                </label>
            </div>

            <div class="flex pt-2 space-x-3">
                <button
                    type="submit"
                    class="bg-yellow-500 hover:bg-yellow-600 text-gray-900 font-bold py-2 px-6 rounded"
                >
                    <?= $edit_data ? 'Simpan Perubahan' : 'Publikasikan Konten'; ?>
                </button>

                <?php if ($edit_data): ?>
                    <a
                        href="content_admin.php"
                        class="bg-gray-600 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded"
                    >
                        Batal Edit
                    </a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <!-- LIST KONTEN -->
    <div class="bg-gray-800 rounded-lg shadow overflow-hidden">
        <h2 class="text-xl font-semibold text-yellow-400 p-6 pb-3">Daftar Konten Komunitas</h2>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-700 text-yellow-400">
                    <tr>
                        <th class="py-3 px-4 text-left">Judul</th>
                        <th class="py-3 px-4 text-left">Harga</th>
                        <th class="py-3 px-4 text-left">Dibuat</th>
                        <th class="py-3 px-4 text-left">Status</th>
                        <th class="py-3 px-4 text-center" style="width: 150px;">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-700">
                    <?php if ($list && $list->num_rows > 0): ?>
                        <?php while ($row = $list->fetch_assoc()): ?>
                            <tr class="hover:bg-gray-700">
                                <td class="py-3 px-4">
                                    <?= htmlspecialchars($row['title']); ?>
                                </td>
                                <td class="py-3 px-4">
                                    Rp <?= number_format($row['product_price'], 0, ',', '.'); ?>
                                </td>
                                <td class="py-3 px-4">
                                    <?= date('d M Y, H:i', strtotime($row['created_at'])); ?>
                                </td>
                                <td class="py-3 px-4">
                                    <?php if ($row['is_published']): ?>
                                        <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-500 text-green-900">
                                            Published
                                        </span>
                                    <?php else: ?>
                                        <span class="px-2 py-1 text-xs font-semibold rounded-full bg-gray-500 text-gray-900">
                                            Draft
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="py-3 px-4 text-center">
                                    <div class="flex justify-center space-x-2">
                                        <a
                                            href="content_admin.php?edit=<?= $row['article_id']; ?>"
                                            class="bg-blue-500 hover:bg-blue-600 text-white px-3 py-1 rounded text-xs font-semibold"
                                        >
                                            Edit
                                        </a>
                                        <a
                                            href="content_admin.php?delete=<?= $row['article_id']; ?>"
                                            onclick="return confirm('Hapus konten ini?');"
                                            class="bg-red-500 hover:bg-red-600 text-white px-3 py-1 rounded text-xs font-semibold"
                                        >
                                            Hapus
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="text-center py-4 text-gray-400">
                                Belum ada konten komunitas. Buat artikel produk pertama di form atas.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</body>
</html>
