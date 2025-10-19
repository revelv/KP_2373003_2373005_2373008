<?php
include 'header.php';
?>

<?php
// --- COPY DARI SINI ---
$recommendations_for_user = [];
if (isset($_SESSION['kd_cs'])) {
    $customer_id = $_SESSION['kd_cs'];

    // 1. Cari order terakhir dari customer yang sedang login
    $order_stmt = $conn->prepare("SELECT order_id FROM orders WHERE customer_id = ? ORDER BY tgl_order DESC LIMIT 1");
    if ($order_stmt) {
        $order_stmt->bind_param("i", $customer_id);
        $order_stmt->execute();
        $order_result = $order_stmt->get_result();
        
        if ($order_result->num_rows > 0) {
            $last_order = $order_result->fetch_assoc();
            $last_order_id = $last_order['order_id'];

            // 2. Cari semua kategori produk yang ada di order terakhir itu
            $categories_in_last_order = [];
            $cat_stmt = $conn->prepare("SELECT DISTINCT p.category_id FROM order_details od JOIN products p ON od.product_id = p.product_id WHERE od.order_id = ?");
            if ($cat_stmt) {
                $cat_stmt->bind_param("i", $last_order_id);
                $cat_stmt->execute();
                $cat_result = $cat_stmt->get_result();
                while ($cat_row = $cat_result->fetch_assoc()) {
                    $categories_in_last_order[] = $cat_row['category_id'];
                }
                $cat_stmt->close();
            }

            // 3. Jika customer membeli Keyboard (category_id = 2), rekomendasikan aksesoris
            if (in_array(2, $categories_in_last_order)) {
                $accessory_categories = [1, 3, 4, 5, 6, 7]; // Case, Keycaps, Kit, Keypad, Stabilizers, Switch
                $recommend_categories = array_diff($accessory_categories, $categories_in_last_order);
                
                if (!empty($recommend_categories)) {
                    $placeholders = implode(',', array_fill(0, count($recommend_categories), '?'));
                    $types = str_repeat('i', count($recommend_categories));
                    
                    // 4. Ambil 3 produk acak dari kategori aksesoris
                    $rec_stmt = $conn->prepare("SELECT * FROM products WHERE category_id IN ($placeholders) ORDER BY RAND() LIMIT 3");
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
// --- SAMPAI SINI ---
?>

<html lang="en">

<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<link rel="stylesheet" href="./css/produk.css">
	<title>Styrk Industries</title>
</head>

<body>

	<script>
		const isLoggedIn = <?= isset($_SESSION['kd_cs']) ? 'true' : 'false'; ?>;
	</script>


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

	<div class="container_produk mb-4">

		<h2 id="judul">Our Products</h2>
		<div class="text-center">
			<?php if (!empty($recommendations_for_user)): ?>
<div class="container_produk mb-4">
    <div class="text-center">
        <h2 class="section-heading text-uppercase">Rekomendasi Untuk Anda</h2>
    </div>
    <div class="row">
        <?php foreach ($recommendations_for_user as $rec_row): ?>
            <div class="col-sm-6 col-md-4">
                <div class="thumbnail">
                    <a href="#" class="product-detail" data-bs-toggle="modal" data-bs-target="#detailModal"
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
                                echo '<div class=""><a href="add_to_cart.php?product_id=' . $rec_row['product_id'] . '" class="btn btn-success btn-block" role="button"><i class="glyphicon glyphicon-shopping-cart"></i> Add to cart</a></div>';
                            } else {
                                echo '<div class=""><a href="#" class="btn btn-success btn-block" role="button"><i class="glyphicon glyphicon-shopping-cart"></i> Login to Add</a></div>';
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
			<h2 class="section-heading text-uppercase">Recommendations</h2>
		</div>
		<div class="row">
			<?php
			// Query untuk mendapatkan 3 produk dengan stok terbanyak DAN terlama (product_id ASC)
			$recommend_query = "SELECT * FROM products ORDER BY stok DESC, product_id ASC LIMIT 3";
			$recommend_result = mysqli_query($conn, $recommend_query);

			while ($rec_row = mysqli_fetch_assoc($recommend_result)) {
			?>
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
                                    <a href="#" class="btn btn-success btn-block" role="button">
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

	<div class="container_produk">
		<h2 id="judul"></h2>
	</div>

	<div class="container_produk">
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

			$query = "SELECT products.*, category.category 
		  FROM products 
		  JOIN category ON products.category_id = category.category_id 
		  $where_clause";

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
								// Jika stok habis
								echo '<div class=""><button class="btn btn-secondary btn-block" disabled>SOLD OUT</button></div>';
							} else {
								if (isset($_SESSION['kd_cs'])) {
									// Jika user login dan stok masih ada
									echo '<div class="">
											<a href="add_to_cart.php?product_id=' . $row['product_id'] . '" class="btn btn-success btn-block" role="button">
												<i class="glyphicon glyphicon-shopping-cart"></i> Add to cart
											</a>
			  								</div>';
								} else {
									// Jika belum login
									echo '<div class="">
											<a href="#" class="btn btn-success btn-block" role="button">
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

	<script>
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

				// Reset tombol
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
			});
		});

		// Toggle Play/Pause
		document.getElementById('toggle-audio').addEventListener('click', function() {
			const audio = document.getElementById('product-audio');
			const button = this;

			if (audio.paused) {
				audio.play();
				button.textContent = '⏸️ Pause Sound';
			} else {
				audio.pause();
				button.textContent = '▶️ Play Sound';
			}

			// Reset button text setelah audio selesai
			audio.onended = () => {
				button.textContent = '▶️ Play Sound';
			};
		});
	</script>

</body>

</html>