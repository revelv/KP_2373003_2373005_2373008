<?php
session_start();
include 'koneksi.php';

// Cek Login
if (!isset($_SESSION['kd_cs'])) {
    echo "<script>alert('Harap login terlebih dahulu!'); window.location='login.php';</script>";
    exit();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Buat Postingan Baru</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-lite.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-lite.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <style>
        body { background-color: #f8f9fa; }
        .form-container {
            max-width: 900px;
            margin: 50px auto;
            background: white;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
        }
    </style>
</head>
<body>

    <?php include 'header.php'; // Navbar ?>

    <div class="container">
        <div class="form-container">
            <h2 class="mb-4 fw-bold"><i class="fas fa-edit me-2"></i>Buat Thread Baru</h2>
            
            <form action="proses_create_thread.php" method="POST">
                
                <div class="mb-3">
                    <label class="form-label fw-bold">Judul</label>
                    <input type="text" name="title" class="form-control form-control-lg" placeholder="Judul menarik..." required>
                </div>

                <div class="mb-4">
                    <label class="form-label fw-bold">Konten</label>

                    <textarea name="content" id="summernote" required></textarea>
                </div>

                <div class="d-grid mt-4">
                    <button type="submit" class="btn btn-primary btn-lg fw-bold" onclick="this.innerHTML='Loading...'">
                        <i class="fas fa-paper-plane me-2"></i> Posting Sekarang
                    </button>
                </div>

            </form>
        </div>
    </div>

    <?php include 'footer.php'; ?>

    <script>
        $(document).ready(function() {
            $('#summernote').summernote({
                placeholder: 'Tulis isi thread kamu di sini...',
                tabsize: 2,
                height: 400,
                toolbar: [
                    ['style', ['style']],
                    ['font', ['bold', 'underline', 'clear']],
                    ['color', ['color']],
                    ['para', ['ul', 'ol', 'paragraph']],
                    ['table', ['table']],
                    ['insert', ['link', 'picture']],
                    ['view', ['fullscreen', 'codeview']]
                ]
            });
        });
    </script>

</body>
</html>