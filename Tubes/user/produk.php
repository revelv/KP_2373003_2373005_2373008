<?php
include 'header.php';

// --- LOGIC REKOMENDASI --- //
$recommendations_for_user = [];
if (isset($_SESSION['kd_cs'])) {
    $customer_id = $_SESSION['kd_cs'];

    $order_stmt = $conn->prepare("SELECT order_id FROM orders WHERE customer_id = ? ORDER BY tgl_order DESC LIMIT 1");
    if ($order_stmt) {
        $order_stmt->bind_param("i", $customer_id);
        $order_stmt->execute();
        $order_result = $order_stmt->get_result();

        if ($order_result->num_rows > 0) {
            $last_order = $order_result->fetch_assoc();
            $last_order_id = $last_order['order_id'];

            $categories_in_last_order = [];
            $cat_stmt = $conn->prepare("
                SELECT DISTINCT p.category_id 
                FROM order_details od 
                JOIN products p ON od.product_id = p.product_id 
                WHERE od.order_id = ?
            ");
            if ($cat_stmt) {
                $cat_stmt->bind_param("i", $last_order_id);
                $cat_stmt->execute();
                $cat_result = $cat_stmt->get_result();
                while ($cat_row = $cat_result->fetch_assoc()) {
                    $categories_in_last_order[] = $cat_row['category_id'];
                }
                $cat_stmt->close();
            }

            if (in_array(2, $categories_in_last_order)) {
                $accessory_categories = [1, 3, 4, 5, 6, 7];
                $recommend_categories = array_diff($accessory_categories, $categories_in_last_order);

                if (!empty($recommend_categories)) {
                    $placeholders = implode(',', array_fill(0, count($recommend_categories), '?'));
                    $types = str_repeat('i', count($recommend_categories));

                    $rec_stmt = $conn->prepare("
                        SELECT * 
                        FROM products 
                        WHERE category_id IN ($placeholders) 
                        ORDER BY RAND() 
                        LIMIT 3
                    ");
                    if ($rec_stmt) {
                        $rec_stmt->bind_param($types, ...$recommend_categories);
                        $rec_stmt->execute();
                        $rec_result = $rec_stmt->get_result();
                        while ($rec_product = $rec_result->fetch_assoc()) {
                            $recommendations_for_user[] = $rec_product;
                        }
                        $rec_stmt->close();
                    }
                }
            }
        }
        $order_stmt->close();
    }
}

// === LELANG BERLANGSUNG (untuk ditampilkan di paling atas halaman produk) ===
$auction_result = mysqli_query(
    $conn,
    "SELECT auction_id, title, current_bid, image_url, end_time 
     FROM auctions 
     WHERE status = 'active' 
       AND end_time > NOW()
     ORDER BY end_time ASC 
     LIMIT 3"
);
?>

<script>
    const isLoggedIn = <?= isset($_SESSION['kd_cs']) ? 'true' : 'false'; ?>;
</script>

<link rel="stylesheet" href="css/produk.css">

<html>
<div class="container_produk mb-4">
    <form method="GET" class="row g-3 align-items-center">
        <div class="col-auto">
            <label for="kategori" class="col-form-label">Filter by Category:</label>
        </div>

        <div class="col-auto">
            <select name="kategori" id="kategori" class="form-select">
                <option value="">All Categories</option>
                <?php
                $kategori_result = mysqli_query($conn, "SELECT category_id, category FROM category ORDER BY category");
                while ($kategori = mysqli_fetch_assoc($kategori_result)) {
                    $selected = (isset($_GET['kategori']) && $_GET['kategori'] == $kategori['category_id']) ? 'selected' : '';
                    echo "<option value=\"" . $kategori['category_id'] . "\" $selected>" . htmlspecialchars($kategori['category']) . "</option>";
                }
                ?>
            </select>
        </div>

        <div class="col-auto">
            <label for="search" class="col-form-label">Search Product:</label>
        </div>

        <div class="col-auto">
            <input type="text" name="search" id="search" class="form-control" value="<?= isset($_GET['search']) ? htmlspecialchars($_GET['search']) : '' ?>">
        </div>

        <div class="col-auto">
            <button type="submit" class="btn btn-primary">Filter & Search</button>
        </div>
    </form>
</div>

<?php
// === SECTION LELANG BERLANGSUNG (muncul hanya jika ada data) ===
if ($auction_result && mysqli_num_rows($auction_result) > 0): ?>
    <div class="container_produk mb-4">
        <div class="text-center mb-3">
            <h2 id="judul">Lelang Berlangsung</h2>
            <p class="text-muted">Ikuti lelang spesial Styrk Industries, klik kartu untuk ikut bid.</p>
        </div>
        <div class="row">
            <?php while ($auc = mysqli_fetch_assoc($auction_result)): ?>
                <div class="col-sm-6 col-md-4 mb-3">
                    <div class="thumbnail" onclick="window.location='auction_detail.php?id=<?= (int)$auc['auction_id']; ?>'">
                        <a href="auction_detail.php?id=<?= (int)$auc['auction_id']; ?>">
                            <img
                                id="gambar"
                                src="<?= htmlspecialchars($auc['image_url'] ?? 'https://i.postimg.cc/855ZSty7/no-bg.png'); ?>"
                                alt="<?= htmlspecialchars($auc['title']); ?>">
                        </a>
                        <div class="caption">
                            <h3><?= htmlspecialchars($auc['title']); ?></h3>
                            <h4>Current Bid: Rp <?= number_format((int)$auc['current_bid'], 0, ',', '.'); ?></h4>
                            <p class="mb-1"><small>Berakhir dalam:</small></p>
                            <p class="fw-bold text-danger auction-countdown"
                                data-endtime="<?= htmlspecialchars($auc['end_time']); ?>">
                                Menghitung...
                            </p>
                            <a href="auction_detail.php?id=<?= (int)$auc['auction_id']; ?>"
                                class="btn btn-warning btn-block">
                                Lihat Detail Lelang
                            </a>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    </div>
<?php endif; ?>

<div class="container_produk mb-4">

    <div class="text-center">
        <?php if (!empty($recommendations_for_user)): ?>
            <div class="container_produk mb-4">
                <div class="text-center">
                    <h2 id="judul"></h2>
                    <h2 class="section-heading text-uppercase">Rekomendasi Untuk Anda</h2>
                </div>
                <div class="row">
                    <?php foreach ($recommendations_for_user as $rec_row): ?>
                        <div class="col-sm-6 col-md-4">
                            <div class="thumbnail">
                                <a href="#"
                                    class="product-detail"
                                    data-bs-toggle="modal"
                                    data-bs-target="#detailModal"
                                    data-id="<?= $rec_row['product_id']; ?>"
                                    data-nama="<?= htmlspecialchars($rec_row['nama_produk'], ENT_QUOTES); ?>"
                                    data-harga="<?= $rec_row['harga']; ?>"
                                    data-stok="<?= $rec_row['stok']; ?>"
                                    data-kategori="<?= $rec_row['category_id']; ?>"
                                    data-deskripsi="<?= htmlspecialchars($rec_row['deskripsi_produk'] ?? ''); ?>"
                                    data-gambar="<?= $rec_row['link_gambar']; ?>">
                                    <img id="gambar" src="<?= $rec_row['link_gambar']; ?>" alt="<?= $rec_row['nama_produk']; ?>">
                                </a>

                                <div class="caption">
                                    <h3><?= $rec_row['nama_produk']; ?></h3>
                                    <h4>Rp <?= number_format($rec_row['harga'], 0, ',', '.'); ?></h4>
                                </div>

                                <div class="button">
                                    <?php
                                    $rec_stok = (int)$rec_row['stok'];
                                    if ($rec_stok < 1) {
                                        echo '<div class=""><button class="btn btn-secondary btn-block" disabled>SOLD OUT</button></div>';
                                    } else {
                                        if (isset($_SESSION['kd_cs'])) {
                                            echo '<div class="">
                                                <a href="add_to_cart.php?product_id=' . $rec_row['product_id'] . '" class="btn btn-success btn-block" role="button">
                                                    <i class="glyphicon glyphicon-shopping-cart"></i> Add to cart
                                                </a>
                                            </div>';
                                        } else {
                                            echo '<div class="">
                                                <a href="login.php" class="btn btn-success btn-block" role="button">
                                                    <i class="glyphicon glyphicon-shopping-cart"></i> Login to Add
                                                </a>
                                            </div>';
                                        }
                                    }
                                    ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>

</div>

<div class="container_produk">
    <h2 id="judul">Our Products</h2>
    <div class="row">
        <?php
        $where = [];

        if (!empty($_GET['kategori'])) {
            $kategori = mysqli_real_escape_string($conn, $_GET['kategori']);
            $where[] = "products.category_id = '$kategori'";
        }

        if (!empty($_GET['search'])) {
            $search = mysqli_real_escape_string($conn, $_GET['search']);
            $where[] = "products.nama_produk LIKE '%$search%'";
        }

        $where_clause = count($where) ? 'WHERE ' . implode(' AND ', $where) : '';

        $where_conditions = [];
        if (count($where) > 0) {
            $where_conditions[] = implode(' AND ', $where);
        }
        $where_conditions[] = "products.status_jual = 'dijual'";

        $where_clause_final = 'WHERE ' . implode(' AND ', $where_conditions);

        $query = "SELECT products.*, category.category 
          FROM products 
          JOIN category ON products.category_id = category.category_id 
          $where_clause_final";

        $result = mysqli_query($conn, $query);

        while ($row = mysqli_fetch_assoc($result)) {
        ?>
            <div class="col-sm-6 col-md-4">
                <div class="thumbnail">
                    <a href="#"
                        class="product-detail"
                        data-bs-toggle="modal"
                        data-bs-target="#detailModal"
                        data-id="<?= $row['product_id']; ?>"
                        data-nama="<?= htmlspecialchars($row['nama_produk'], ENT_QUOTES); ?>"
                        data-harga="<?= $row['harga']; ?>"
                        data-stok="<?= $row['stok']; ?>"
                        data-kategori="<?= $row['category_id']; ?>"
                        data-deskripsi="<?= htmlspecialchars($row['deskripsi_produk'] ?? ''); ?>"
                        data-gambar="<?= $row['link_gambar']; ?>">
                        <img id="gambar" src="<?= $row['link_gambar']; ?>" alt="<?= $row['nama_produk']; ?>">
                    </a>

                    <div class="caption">
                        <h3><?= $row['nama_produk']; ?></h3>
                        <h4>Rp <?= number_format($row['harga'], 0, ',', '.'); ?></h4>
                    </div>

                    <div class="button">
                        <?php
                        $stok = (int)$row['stok'];

                        if ($stok < 1) {
                            echo '<div class=""><button class="btn btn-secondary btn-block" disabled>SOLD OUT</button></div>';
                        } else {
                            if (isset($_SESSION['kd_cs'])) {
                                echo '<div class="">
                                    <a href="add_to_cart.php?product_id=' . $row['product_id'] . '" class="btn btn-success btn-block" role="button">
                                        <i class="glyphicon glyphicon-shopping-cart"></i> Add to cart
                                    </a>
                                </div>';
                            } else {
                                echo '<div class="">
                                    <a href="login.php" class="btn btn-success btn-block" role="button">
                                        <i class="glyphicon glyphicon-shopping-cart"></i> Login to Add
                                    </a>
                                </div>';
                            }
                        }
                        ?>
                    </div>
                </div>
            </div>
        <?php
        }
        ?>
    </div>
</div>

<!-- MODAL DETAIL PRODUK -->
<div class="modal fade" id="detailModal" tabindex="-1" aria-labelledby="detailModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="detailModalLabel">Detail Produk</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body d-flex flex-column align-items-center text-center">
                <div class="me-4">
                    <img id="modal-gambar" src="" alt="gambar">
                </div>
                <div>
                    <h4 id="modal-nama"></h4>
                    <p><strong>Harga:</strong> Rp<span id="modal-harga"></span></p>
                    <p><strong>Stok:</strong> <span id="modal-stok"></span></p>
                    <p id="modal-deskripsi"></p>

                    <div id="modal-button-container" class="mt-3"></div>

                    <!-- Tombol Audio -->
                    <div id="modal-audio-container" class="mt-3" style="display: none;">
                        <button id="toggle-audio" class="btn btn-primary">
                            ▶️ Play Sound
                        </button>
                        <audio id="product-audio" hidden></audio>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>

</html>

<script>
    // === COUNTDOWN UNTUK KARTU LELANG DI HALAMAN PRODUK ===
    document.querySelectorAll('.auction-countdown').forEach(timer => {
        const endTime = new Date(timer.dataset.endtime).getTime();

        const intervalId = setInterval(() => {
            const now = new Date().getTime();
            const distance = endTime - now;

            if (distance <= 0) {
                clearInterval(intervalId);
                timer.textContent = 'LELANG BERAKHIR';
                return;
            }

            const days = Math.floor(distance / (1000 * 60 * 60 * 24));
            const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
            const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
            const seconds = Math.floor((distance % (1000 * 60)) / 1000);

            timer.textContent = `${days}h ${hours}j ${minutes}m ${seconds}d`;
        }, 1000);
    });

    // handle klik setiap produk (buka modal + isi konten)
    document.querySelectorAll('.product-detail').forEach(el => {
        el.addEventListener('click', function() {
            const nama = this.dataset.nama;
            const harga = this.dataset.harga;
            const stok = this.dataset.stok;
            const deskripsi = this.dataset.deskripsi;
            const gambar = this.dataset.gambar;
            const id = this.dataset.id;
            const kategori = this.dataset.kategori;

            // Isi modal konten
            document.getElementById('modal-nama').textContent = nama;
            document.getElementById('modal-harga').textContent = Number(harga).toLocaleString('id-ID');
            document.getElementById('modal-stok').textContent = stok;
            document.getElementById('modal-deskripsi').textContent = deskripsi;
            document.getElementById('modal-gambar').src = gambar;

            // Tombol Add/SOLD OUT + LOGIN TO ADD
            const container = document.getElementById('modal-button-container');
            container.innerHTML = '';

            if (parseInt(stok) > 0) {
                if (isLoggedIn) {
                    container.innerHTML = `<a href="add_to_cart.php?product_id=${id}" class="btn btn-success">Add to Cart</a>`;
                } else {
                    container.innerHTML = `<a href="login.php" class="btn btn-warning">Login to Add</a>`;
                }
            } else {
                container.innerHTML = `<button class="btn btn-secondary" disabled>SOLD OUT</button>`;
            }

            // Audio kategori dan tombol play/pause
            const audioContainer = document.getElementById('modal-audio-container');
            const audioElement = document.getElementById('product-audio');
            const toggleButton = document.getElementById('toggle-audio');

            if (audioContainer && audioElement && toggleButton) {
                // Reset tombol + audio
                toggleButton.textContent = '▶️ Play Sound';
                audioElement.pause();
                audioElement.currentTime = 0;

                if (kategori === '7') {
                    audioElement.src = '../sounds/switch_sound.mp3';
                    audioContainer.style.display = 'block';
                } else if (kategori === '2') {
                    audioElement.src = '../sounds/keyboard_sound.mp3';
                    audioContainer.style.display = 'block';
                } else {
                    audioElement.src = '';
                    audioContainer.style.display = 'none';
                }
            }
        });
    });

    // toggle play/pause audio di modal
    (function() {
        const toggleBtn = document.getElementById('toggle-audio');
        const audioEl = document.getElementById('product-audio');

        if (toggleBtn && audioEl) {
            toggleBtn.addEventListener('click', function() {
                if (audioEl.paused) {
                    audioEl.play();
                    this.textContent = '⏸️ Pause Sound';
                } else {
                    audioEl.pause();
                    this.textContent = '▶️ Play Sound';
                }

                audioEl.onended = () => {
                    this.textContent = '▶️ Play Sound';
                };
            });
        }
    })();
</script>

<?php include 'footer.php'; ?>