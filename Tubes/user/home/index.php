<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <meta name="description" content="Styrk Industries - Premium Custom Mechanical Keyboards" />
    <meta name="author" content="" />
    <title>Styrk Industries | Premium Custom Keyboards</title>

    <link rel="icon" type="image/x-icon" href="assets/favicon.ico" />

    <script src="https://use.fontawesome.com/releases/v6.3.0/js/all.js" crossorigin="anonymous"></script>
    <link href="https://fonts.googleapis.com/css2?family=Raleway:wght@400;600;700&display=swap" rel="stylesheet">
    <!-- Google fonts-->
    <link href="https://fonts.googleapis.com/css?family=Montserrat:400,700" rel="stylesheet" type="text/css" />
    <link href="https://fonts.googleapis.com/css?family=Roboto+Slab:400,100,300,700" rel="stylesheet" type="text/css" />

    <style>
        :root {
            --primary: #1a1a1a;
            --secondary: #ff6b35;
            --accent: #4a4a4a;
            --light: #f8f9fa;
            --dark: #121212;
        }
        
        body {
            font-family: 'Raleway', sans-serif;
            color: #333;
        }
        
        .navbar {
            background-color: rgba(26, 26, 26, 0.95) !important;
            padding: 15px 0;
            transition: all 0.3s;
        }
        
        .navbar-brand img {
            height: 50px;
        }
        
        .nav-link {
            color: #fff !important;
            font-weight: 600;
            margin: 0 10px;
            transition: color 0.3s;
        }
        
        .nav-link:hover {
            color: var(--secondary) !important;
        }
        
        .hero-section {
            background: linear-gradient(rgba(0, 0, 0, 0.7), rgba(0, 0, 0, 0.7)), url('https://images.unsplash.com/photo-1607799279861-4dd421887fb3?ixlib=rb-4.0.3&auto=format&fit=crop&w=2070&q=80');
            background-size: cover;
            background-position: center;
            height: 90vh;
            display: flex;
            align-items: center;
            color: white;
            text-align: center;
        }
        
        .hero-content {
            max-width: 800px;
            margin: 0 auto;
        }
        
        .hero-title {
            font-size: 3.5rem;
            font-weight: 700;
            margin-bottom: 1.5rem;
            text-transform: uppercase;
            letter-spacing: 2px;
        }
        
        .hero-subtitle {
            font-size: 1.5rem;
            margin-bottom: 2rem;
            font-weight: 300;
        }
        
        .btn-primary {
            background-color: var(--secondary);
            border-color: var(--secondary);
            padding: 12px 30px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
            transition: all 0.3s;
        }
        
        .btn-primary:hover {
            background-color: #e55a2b;
            border-color: #e55a2b;
            transform: translateY(-2px);
        }
        
        .section-title {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 1rem;
            text-transform: uppercase;
            position: relative;
        }
        
        .section-title:after {
            content: '';
            display: block;
            width: 80px;
            height: 4px;
            background: var(--secondary);
            margin: 15px auto 30px;
        }
        
        .section-subtitle {
            font-size: 1.2rem;
            color: #777;
            margin-bottom: 3rem;
        }
        
        .product-card {
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            transition: all 0.3s;
            margin-bottom: 30px;
            height: 100%;
        }
        
        .product-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.15);
        }
        
        .product-image {
            height: 250px;
            background-size: cover;
            background-position: center;
        }
        
        .product-info {
            padding: 20px;
        }
        
        .product-name {
            font-size: 1.3rem;
            font-weight: 700;
            margin-bottom: 10px;
        }
        
        .product-description {
            color: #777;
            margin-bottom: 15px;
            font-size: 0.9rem;
        }
        
        .product-price {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--secondary);
            margin-bottom: 15px;
        }
        
        .sold-out {
            background-color: #f8f9fa;
            color: #6c757d;
            padding: 5px 10px;
            border-radius: 4px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        
        .services-section {
            padding: 100px 0;
            background-color: var(--light);
        }
        
        .service-card {
            text-align: center;
            padding: 30px 20px;
            border-radius: 10px;
            background: white;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            transition: all 0.3s;
            height: 100%;
        }
        
        .service-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }
        
        .service-icon {
            font-size: 3rem;
            color: var(--secondary);
            margin-bottom: 20px;
        }
        
        .service-title {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 15px;
        }
        
        .about-section {
            padding: 100px 0;
        }
        
        .team-section {
            padding: 100px 0;
            background-color: var(--light);
        }
        
        .team-member {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .team-member img {
            width: 200px;
            height: 200px;
            object-fit: cover;
            border-radius: 50%;
            margin-bottom: 20px;
            border: 5px solid white;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        
        .team-member h4 {
            font-weight: 700;
            margin-bottom: 5px;
        }
        
        .team-member p {
            color: #777;
        }
        
        .clients-section {
            padding: 80px 0;
            background-color: var(--dark);
        }
        
        .footer {
            background-color: var(--primary);
            color: white;
            padding: 50px 0 20px;
        }
        
        .filter-section {
            background-color: var(--light);
            padding: 30px 0;
            margin-bottom: 50px;
        }
        
        .filter-title {
            font-weight: 700;
            margin-bottom: 15px;
            color: var(--primary);
        }
        
        .filter-options {
            list-style: none;
            padding: 0;
        }
        
        .filter-options li {
            margin-bottom: 8px;
        }
        
        .filter-options a {
            color: #555;
            text-decoration: none;
            transition: color 0.3s;
        }
        
        .filter-options a:hover {
            color: var(--secondary);
        }
        
        @media (max-width: 768px) {
            .hero-title {
                font-size: 2.5rem;
            }
            
            .hero-subtitle {
                font-size: 1.2rem;
            }
            
            .section-title {
                font-size: 2rem;
            }
        }
    </style>

    <link href="css/styles.css" rel="stylesheet" />
</head>

<body id="page-top">
    <!-- Navigation-->
    <nav class="navbar navbar-expand-lg navbar-dark fixed-top" id="mainNav">
        <div class="container">
            <a class="navbar-brand" href="#page-top" id="logo">
                <img src="https://i.postimg.cc/855ZSty7/no-bg.png" alt="Styrk Industries Logo">
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarResponsive" aria-controls="navbarResponsive" aria-expanded="false" aria-label="Toggle navigation">
                <i class="fas fa-bars"></i>
            </button>
            <div class="collapse navbar-collapse" id="navbarResponsive">
                <ul class="navbar-nav text-uppercase ms-auto py-4 py-lg-0">
                    <li class="nav-item"><a class="nav-link" href="#home">Home</a></li>
                    <li class="nav-item"><a class="nav-link" href="#products">Products</a></li>
                    <li class="nav-item"><a class="nav-link" href="#services">Services</a></li>
                    <li class="nav-item"><a class="nav-link" href="#about">About</a></li>
                    <li class="nav-item"><a class="nav-link" href="#team">Team</a></li>
                    <li class="nav-item"><a class="nav-link" href="#contact">Contact</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero-section" id="home">
        <div class="container">
            <div class="hero-content">
                <h1 class="hero-title">Craft Your Perfect Keyboard</h1>
                <p class="hero-subtitle">Premium custom mechanical keyboards tailored to your typing experience</p>
                <a class="btn btn-primary btn-xl text-uppercase" href="../produk.php">Explore Collections</a>
            </div>
        </div>
    </section>

    <!-- Products Section -->
    <section class="page-section" id="products">
        <div class="container">
            <div class="text-center">
                <h2 class="section-title">Our Products</h2>
                <p class="section-subtitle">Handcrafted keyboards for enthusiasts and professionals</p>
            </div>

            <!-- Filter Section -->
            <div class="filter-section">
                <div class="container">
                    <div class="row">
                        <div class="col-md-3">
                            <h4 class="filter-title">Filter by Category:</h4>
                            <ul class="filter-options">
                                <li><a href="#">All Categories</a></li>
                                <li><a href="#">Custom Keyboards</a></li>
                                <li><a href="#">Keyboard Kits</a></li>
                                <li><a href="#">Keycaps</a></li>
                                <li><a href="#">Switches</a></li>
                                <li><a href="#">Accessories</a></li>
                            </ul>
                        </div>
                        <div class="col-md-9">
                            <div class="input-group">
                                <input type="text" class="form-control" placeholder="Search Product...">
                                <button class="btn btn-primary" type="button">Filter & Search</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Products Grid -->
            <div class="row">
                <?php
                // Database connection
                require_once '../koneksi.php';

                // Get 3 products with highest stock AND oldest in database
                $query = "SELECT * FROM products 
                         ORDER BY stok DESC, product_id ASC 
                         LIMIT 3";
                $result = mysqli_query($conn, $query);

                $counter = 1;
                while ($row = mysqli_fetch_assoc($result)) {
                    $product_name = htmlspecialchars($row['nama_produk']);
                    $product_price = "Rp " . number_format($row['harga'], 0, ',', '.');
                    $product_image = $row['link_gambar'];
                    
                    // Default image if none provided
                    if (empty($product_image)) {
                        $product_image = "https://images.unsplash.com/photo-1587829741301-dc798b83add3?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80";
                    }
                    
                    // Check if product is sold out
                    $is_sold_out = $row['stok'] <= 0;
                    
                    echo '
                    <div class="col-lg-4 col-md-6 mb-4">
                        <div class="product-card">
                            <div class="product-image" style="background-image: url(\'' . $product_image . '\')"></div>
                            <div class="product-info">
                                <h3 class="product-name">' . $product_name . '</h3>
                                <p class="product-description">' . (isset($row['deskripsi']) ? htmlspecialchars($row['deskripsi']) : 'Premium custom mechanical keyboard') . '</p>
                                <div class="product-price">' . $product_price . '</div>';
                    
                    if ($is_sold_out) {
                        echo '<div class="sold-out">SOLD OUT</div>';
                    } else {
                        echo '<button class="btn btn-primary w-100">Login to Add</button>';
                    }
                    
                    echo '
                            </div>
                        </div>
                    </div>
                    ';
                    $counter++;
                }
                ?>
            </div>
        </div>
    </section>

    <!-- Services Section -->
    <section class="services-section" id="services">
        <div class="container">
            <div class="text-center">
                <h2 class="section-title">Our Services</h2>
                <p class="section-subtitle">Comprehensive solutions for all your keyboard needs</p>
            </div>
            <div class="row">
                <div class="col-md-4 mb-4">
                    <div class="service-card">
                        <div class="service-icon">
                            <i class="fas fa-keyboard"></i>
                        </div>
                        <h3 class="service-title">Custom Keyboard</h3>
                        <p class="text-muted">Create your dream keyboard with our customization service. Choose every component from switches to keycaps for a truly personalized typing experience.</p>
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <div class="service-card">
                        <div class="service-icon">
                            <i class="fas fa-box-open"></i>
                        </div>
                        <h3 class="service-title">Bundling Keyboard</h3>
                        <p class="text-muted">Get our exclusive keyboard bundles that include premium keycaps, selected switches, and stabilizers - all in one affordable package ready to assemble.</p>
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <div class="service-card">
                        <div class="service-icon">
                            <i class="fas fa-cogs"></i>
                        </div>
                        <h3 class="service-title">Keyboard Components</h3>
                        <p class="text-muted">We provide various high-quality mechanical keyboard components, from switches, keycaps, PCBs, cases to stabilizers. All parts available separately.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- About Section -->
    <section class="about-section" id="about">
        <div class="container">
            <div class="text-center">
                <h2 class="section-title">Our Story</h2>
                <p class="section-subtitle">From garage project to industry leader</p>
            </div>
            <div class="row">
                <div class="col-lg-6">
                    <h3 class="mb-4">Crafting Legends Since 2023</h3>
                    <p class="mb-4">What started as a passion project in a small garage has evolved into Styrk Industries - a leading name in custom mechanical keyboards. Our journey began with three keyboard enthusiasts frustrated with mainstream options.</p>
                    <p class="mb-4">We believe that a keyboard is more than just an input device - it's an extension of your personality, a tool that should inspire with every keystroke.</p>
                    <p>Today, we continue to push boundaries, creating innovative keyboard solutions that blend aesthetics, performance, and unparalleled typing experiences.</p>
                </div>
                <div class="col-lg-6">
                    <img src="https://images.unsplash.com/photo-1563297007-0686b7003af7?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80" alt="Keyboard Workshop" class="img-fluid rounded shadow">
                </div>
            </div>
        </div>
    </section>

    <!-- Team Section -->
    <section class="team-section" id="team">
        <div class="container">
            <div class="text-center">
                <h2 class="section-title">Our Team</h2>
                <p class="section-subtitle">The passionate creators behind every keyboard</p>
            </div>
            <div class="row">
                <div class="col-lg-4">
                    <div class="team-member">
                        <img src="assets/img/team/1.jpg" alt="Igris">
                        <h4>Igris</h4>
                        <p class="text-muted">Lead Designer</p>
                        <div class="social-links">
                            <a class="btn btn-dark btn-social mx-2" href="#!"><i class="fab fa-twitter"></i></a>
                            <a class="btn btn-dark btn-social mx-2" href="#!"><i class="fab fa-facebook-f"></i></a>
                            <a class="btn btn-dark btn-social mx-2" href="#!"><i class="fab fa-linkedin-in"></i></a>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="team-member">
                        <img src="assets/img/team/2.jpg" alt="Thomas">
                        <h4>Thomas</h4>
                        <p class="text-muted">The Mechanic</p>
                        <div class="social-links">
                            <a class="btn btn-dark btn-social mx-2" href="#!"><i class="fab fa-twitter"></i></a>
                            <a class="btn btn-dark btn-social mx-2" href="#!"><i class="fab fa-facebook-f"></i></a>
                            <a class="btn btn-dark btn-social mx-2" href="#!"><i class="fab fa-linkedin-in"></i></a>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="team-member">
                        <img src="assets/img/team/3.jpg" alt="Sung Andre">
                        <h4>Sung Andre</h4>
                        <p class="text-muted">Lead Developer</p>
                        <div class="social-links">
                            <a class="btn btn-dark btn-social mx-2" href="#!"><i class="fab fa-twitter"></i></a>
                            <a class="btn btn-dark btn-social mx-2" href="#!"><i class="fab fa-facebook-f"></i></a>
                            <a class="btn btn-dark btn-social mx-2" href="#!"><i class="fab fa-linkedin-in"></i></a>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row mt-5">
                <div class="col-lg-8 mx-auto text-center">
                    <p class="large text-muted">Passion in every switch, dedication in every layout - these are the faces behind your custom keyboard.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Clients Section -->
    <div class="clients-section">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-3 col-sm-6 my-3">
                    <a href="https://www.instagram.com/tonyck_gaming"><img class="img-fluid img-brand d-block mx-auto" src="https://i.postimg.cc/DyBd2dXs/insta.png" alt="Instagram" /></a>
                </div>
                <div class="col-md-3 col-sm-6 my-3">
                    <a href="https://x.com/TonyCK169"><img class="img-fluid img-brand d-block mx-auto" src="https://i.postimg.cc/SK8Mz8Jg/eks.png" alt="Twitter" /></a>
                </div>
                <div class="col-md-3 col-sm-6 my-3">
                    <a href="https://www.youtube.com/@JessNoLimit"><img class="img-fluid img-brand d-block mx-auto" src="https://i.postimg.cc/wMc2319D/free-youtube-icon-123-thumb.png" alt="YouTube" /></a>
                </div>
                <div class="col-md-3 col-sm-6 my-3">
                    <a href="https://www.tiktok.com/@tonyckgaming"><img class="img-fluid img-brand d-block mx-auto" src="https://i.postimg.cc/Qd2MZSbF/1000_F_576083591_j-O2u-WDr-W843l-L8e-FMe9a-DZlo-Iri7ghc4.jpg" alt="TikTok" /></a>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-4 text-lg-start">Copyright &copy; Styrk Industries 2025</div>
                <div class="col-lg-4 my-3 my-lg-0 text-center">
                    <a href="#page-top" class="btn btn-primary btn-sm">Back to Top</a>
                </div>
                <div class="col-lg-4 text-lg-end">
                    <a class="link-light text-decoration-none me-3" href="#!">Privacy Policy</a>
                    <a class="link-light text-decoration-none" href="#!">Terms of Use</a>
                </div>
            </div>
        </div>
    </footer>

    <!-- Bootstrap core JS-->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Core theme JS-->
    <script src="js/scripts.js"></script>
    <!-- * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *-->
    <!-- * *                               SB Forms JS                               * *-->
    <!-- * * Activate your form at https://startbootstrap.com/solution/contact-forms * *-->
    <!-- * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *-->
    <script src="https://cdn.startbootstrap.com/sb-forms-latest.js"></script>
</body>

</html>