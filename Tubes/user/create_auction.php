<?php
include 'header.php';
if (!isset($_SESSION['kd_cs'])) {
    echo "<script>alert('Anda harus login untuk memulai lelang.'); window.location.href='auctions.php';</script>";
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mulai Lelang Baru - Styrk Industries</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5" style="max-width: 700px;">
        <h2 class="mb-4">Mulai Lelang Barang Anda</h2>
        <form action="proses_create_auction.php" method="POST">
            <div class="mb-3">
                <label for="title" class="form-label">Judul Barang</label>
                <input type="text" class="form-control" id="title" name="title" required placeholder="Cth: Keycaps GMK Red Samurai (Bekas)">
            </div>
            <div class="mb-3">
                <label for="description" class="form-label">Deskripsi</label>
                <textarea class="form-control" id="description" name="description" rows="5" required placeholder="Jelaskan kondisi barang, kelengkapan, dll..."></textarea>
            </div>
            <div class="mb-3">
                <label for="image_url" class="form-label">Link Gambar</label>
                <input type="url" class="form-control" id="image_url" name="image_url" required placeholder="https://i.postimg.cc/gambar-anda.jpg">
                <small class="form-text">Upload gambar Anda ke (cth: postimg.cc) dan paste link-nya di sini.</small>
            </div>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="start_price" class="form-label">Harga Buka (Rp)</label>
                    <input type="number" class="form-control" id="start_price" name="start_price" min="1000" required placeholder="Cth: 100000">
                </div>
                <div class="col-md-6 mb-3">
                    <label for="end_time" class="form-label">Waktu Berakhir</label>
                    <input type="datetime-local" class="form-control" id="end_time" name="end_time" required>
                </div>
            </div>
            <button type="submit" class="btn btn-lg w-100" style="background-color: var(--gold); color: var(--dark-gray);">Mulai Lelang</button>
        </form>
    </div>
</body>
</html>