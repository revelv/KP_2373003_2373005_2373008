<?php
include 'header.php'; // Pastikan header.php sudah punya koneksi $conn

// Pastikan user sudah login untuk bisa buat topik
if (!isset($_SESSION['kd_cs'])) {
    echo "<script>alert('Anda harus login untuk membuat topik baru.'); window.location.href='community.php';</script>";
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Buat Topik Baru - Forum Komunitas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.5.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
        }

        .form-container {
            max-width: 800px;
            margin: 30px auto;
            background: #fff;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.05);
        }

        /* Tombol submit cerah */
        .btn-submit-thread {
            border: none;
            background-color: #ffdc73;
            color: #1f1f1f;
            font-weight: 600;
            padding: 10px 20px;
            border-radius: 8px;
            transition: all 0.2s ease-in-out;
        }

        .btn-submit-thread:hover {
            background-color: #ffe58a;
            transform: translateY(-1px);
        }

        /* Tombol batal */
        .btn-secondary {
            background-color: #e9ecef;
            border: none;
            color: #333;
            font-weight: 500;
            border-radius: 8px;
        }

        .btn-secondary:hover {
            background-color: #dee2e6;
        }
    </style>
</head>

<body>

    <div class="form-container">
        <h2 class="mb-4"><i class="bi bi-pencil-square me-2"></i> Buat Topik Diskusi Baru</h2>

        <form action="proses_create_thread.php" method="POST">
            <div class="mb-3">
                <label for="threadTitle" class="form-label">Judul Topik</label>
                <input type="text" class="form-control" id="threadTitle" name="title" required placeholder="Masukkan judul yang jelas dan menarik">
            </div>
            <div class="mb-3">
                <label for="threadContent" class="form-label">Pesan Pertama</label>
                <textarea class="form-control" id="threadContent" name="content" rows="8" required placeholder="Tuliskan pertanyaan atau topik diskusi Anda di sini..."></textarea>
            </div>
            <div class="d-flex justify-content-between">
                <a href="community.php" class="btn btn-secondary">Batal</a>
                <button type="submit" class="btn btn-submit-thread">
                    <i class="bi bi-send-fill me-1"></i> Publikasikan Topik
                </button>
            </div>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>