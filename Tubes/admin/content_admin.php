<?php
// admin/content_admin.php
include 'auth_admin.php';      // kalau ada
include 'header_admin.php';
include 'koneksi.php';

// ====== CREATE / UPDATE ======
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id       = isset($_POST['article_id']) ? (int)$_POST['article_id'] : 0;
    $title    = trim($_POST['title'] ?? '');
    $content  = trim($_POST['content'] ?? '');
    $publish  = isset($_POST['is_published']) ? 1 : 0;

    if ($title === '' || $content === '') {
        $error = "Judul dan konten wajib diisi.";
    } else {
        if ($id > 0) {
            // update
            $stmt = $conn->prepare("UPDATE community_articles 
                                    SET title=?, content=?, is_published=? 
                                    WHERE article_id=?");
            $stmt->bind_param('ssii', $title, $content, $publish, $id);
        } else {
            // insert baru
            $stmt = $conn->prepare("INSERT INTO community_articles (title, content, is_published) 
                                    VALUES (?, ?, ?)");
            $stmt->bind_param('ssi', $title, $content, $publish);
        }

        if ($stmt->execute()) {
            header('Location: content_admin.php?success=1');
            exit();
        } else {
            $error = "Gagal menyimpan: " . $stmt->error;
        }
        $stmt->close();
    }
}

// ====== DELETE ======
if (isset($_GET['delete'])) {
    $del_id = (int)$_GET['delete'];
    $conn->query("DELETE FROM community_articles WHERE article_id = {$del_id}");
    header('Location: content_admin.php?deleted=1');
    exit();
}

// ====== EDIT (load data) ======
$edit_data = null;
if (isset($_GET['edit'])) {
    $edit_id = (int)$_GET['edit'];
    $resEdit = $conn->query("SELECT * FROM community_articles WHERE article_id = {$edit_id} LIMIT 1");
    $edit_data = $resEdit->fetch_assoc();
}

// ====== LIST ======
$list = $conn->query("SELECT * FROM community_articles ORDER BY created_at DESC");
?>

<div class="container mt-4">
    <h2>Content Komunitas</h2>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error); ?></div>
    <?php elseif (isset($_GET['success'])): ?>
        <div class="alert alert-success">Konten berhasil disimpan.</div>
    <?php elseif (isset($_GET['deleted'])): ?>
        <div class="alert alert-success">Konten berhasil dihapus.</div>
    <?php endif; ?>

    <!-- FORM TAMBAH / EDIT -->
    <div class="card mb-4">
        <div class="card-header">
            <?= $edit_data ? 'Edit Konten' : 'Tambah Konten Baru'; ?>
        </div>
        <div class="card-body">
            <form method="post">
                <input type="hidden" name="article_id" value="<?= $edit_data['article_id'] ?? 0; ?>">

                <div class="mb-3">
                    <label class="form-label">Judul</label>
                    <input type="text" name="title" class="form-control"
                        value="<?= htmlspecialchars($edit_data['title'] ?? ''); ?>" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Konten</label>
                    <textarea name="content" rows="6" class="form-control" required><?= htmlspecialchars($edit_data['content'] ?? ''); ?></textarea>
                </div>

                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" id="publish" name="is_published"
                        <?= isset($edit_data['is_published']) ? ($edit_data['is_published'] ? 'checked' : '') : 'checked'; ?>>
                    <label class="form-check-label" for="publish">
                        Publish
                    </label>
                </div>

                <button type="submit" class="btn btn-primary">
                    Simpan
                </button>
                <?php if ($edit_data): ?>
                    <a href="content_admin.php" class="btn btn-secondary">Batal</a>
                <?php endif; ?>
            </form>
        </div>
    </div>

    <!-- LIST KONTEN -->
    <table class="table table-bordered">
        <thead>
            <tr>
                <th>Judul</th>
                <th>Created</th>
                <th>Published</th>
                <th width="140">Aksi</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = $list->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($row['title']); ?></td>
                    <td><?= date('d M Y H:i', strtotime($row['created_at'])); ?></td>
                    <td><?= $row['is_published'] ? 'Ya' : 'Tidak'; ?></td>
                    <td>
                        <a href="content_admin.php?edit=<?= $row['article_id']; ?>" class="btn btn-sm btn-warning">Edit</a>
                        <a href="content_admin.php?delete=<?= $row['article_id']; ?>"
                            class="btn btn-sm btn-danger"
                            onclick="return confirm('Hapus konten ini?');">Hapus</a>
                    </td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

<?php include 'footer_admin.php' ?? ''; ?>