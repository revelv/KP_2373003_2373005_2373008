<?php
// MULAI SESSION
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// INCLUDE KONEKSI & LOGIKA HEADER
// Pastikan path ke header.php benar sesuai struktur folder lu
require_once 'header.php'; 
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <meta name="description" content="Styrk Industries - Premium Custom Mechanical Keyboards" />
    <meta name="author" content="Styrk Industries" />
    <title>Styrk Industries | Premium Custom Keyboards</title>

    <link rel="icon" type="image/x-icon" href="assets/favicon.ico" />

    <script src="https://use.fontawesome.com/releases/v6.3.0/js/all.js" crossorigin="anonymous"></script>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700&family=Space+Grotesk:wght@400;600;700&display=swap" rel="stylesheet">

    <style>
        /* === MODERN PREMIUM THEME === */
        :root {
            --black: #0a0a0a;
            --dark-gray: #1a1a1a;
            --gold: #D4AF37;
            --gold-hover: #b59226;
            --text-main: #e0e0e0;
            --text-muted: #a0a0a0;
            --white: #ffffff;
            --neon-pink: #FF00FF; /* Warna neon dari keyboard */
            --neon-purple: #8A2BE2; /* Warna neon kedua */
        }

        body {
            font-family: 'Outfit', sans-serif;
            background-color: var(--black);
            color: var(--text-main);
            overflow-x: hidden;
        }

        h1, h2, h3, h4, h5, h6 {
            font-family: 'Space Grotesk', sans-serif;
            color: var(--white);
            letter-spacing: -0.5px;
        }

        /* === HERO SECTION === */
        .hero-section {
            position: relative;
            height: 100vh;
            min-height: 600px;
            display: flex;
            align-items: center;
            justify-content: center;
            /* BACKGROUND IMAGE DARI GAMBAR KEYBOARD LU */
            background: url('https://i.postimg.cc/2jHnVCpR/aesthetic-pink-purple-keyboard-upcs1h9i14iu7fn3.jpg') no-repeat center center/cover; 
            overflow: hidden;
        }

        .hero-section::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
            /* Gradient disesuaikan biar gambar tetap keliatan */
            background: linear-gradient(180deg, rgba(0,0,0,0.6) 0%, rgba(0,0,0,0.8) 70%, var(--black) 100%);
            z-index: 1;
        }

        .hero-content {
            position: relative;
            z-index: 2;
            text-align: center;
            max-width: 800px;
            padding: 0 20px;
            animation: fadeInUp 1s ease-out;
        }

        .hero-title {
            font-size: 4rem;
            font-weight: 700;
            line-height: 1.1;
            margin-bottom: 1.5rem;
            text-transform: uppercase;
            /* Warna teks disesuaikan dengan neon keyboard */
            color: var(--neon-pink); 
            text-shadow: 0 0 10px rgba(255, 0, 255, 0.6), 0 0 20px rgba(255, 0, 255, 0.4); /* Efek glow */
        }

        .hero-subtitle {
            font-size: 1.5rem;
            color: #f0f0f0; /* Lebih terang biar jelas */
            margin-bottom: 2.5rem;
            font-weight: 300;
            text-shadow: 0 0 5px rgba(0,0,0,0.8);
        }

        .btn-premium {
            background-color: var(--neon-purple); /* Warna tombol dari keyboard */
            color: var(--white);
            padding: 15px 40px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 2px;
            border: 2px solid var(--neon-pink); /* Border neon */
            border-radius: 4px; /* Sedikit rounded */
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
            box-shadow: 0 0 15px rgba(138, 43, 226, 0.4); /* Glow ringan */
        }

        .btn-premium:hover {
            background-color: var(--neon-pink); /* Warna tombol hover dari keyboard */
            border-color: var(--neon-purple);
            transform: translateY(-3px);
            box-shadow: 0 0 25px rgba(255, 0, 255, 0.6); /* Glow lebih kuat */
        }

        /* === SECTIONS GENERAL === */
        .section-padding {
            padding: 100px 0;
        }

        .section-title {
            font-size: 2.5rem;
            margin-bottom: 1rem;
            text-transform: uppercase;
            color: var(--white);
        }

        .section-subtitle {
            font-size: 1.1rem;
            color: var(--neon-pink); /* Subtitle juga pakai neon */
            margin-bottom: 4rem;
            text-transform: uppercase;
            letter-spacing: 3px;
        }

        /* === SERVICES CARDS === */
        .service-card {
            background: #111; /* Lebih gelap dari dark-gray */
            padding: 40px 30px;
            border: 1px solid #333;
            transition: all 0.3s ease;
            height: 100%;
            position: relative;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0,0,0,0.3);
        }

        .service-card:hover {
            border-color: var(--neon-pink); /* Border neon saat hover */
            transform: translateY(-10px);
            box-shadow: 0 0 20px rgba(255, 0, 255, 0.2); /* Glow halus */
        }

        .service-icon {
            font-size: 3rem;
            color: var(--neon-purple); /* Icon juga neon */
            margin-bottom: 25px;
            text-shadow: 0 0 8px rgba(138, 43, 226, 0.4);
        }

        .service-title {
            font-size: 1.5rem;
            margin-bottom: 15px;
            color: var(--white);
        }

        .service-desc {
            color: var(--text-muted);
            font-size: 0.95rem;
            line-height: 1.6;
        }

        /* === ABOUT SECTION === */
        .about-img-wrapper {
            position: relative;
            perspective: 1000px; /* Untuk efek 3D */
        }
        
        .about-img {
            width: 100%;
            border-radius: 4px;
            filter: brightness(0.7) contrast(1.2); /* Lebih gelap & kontras */
            transition: 0.5s ease;
            transform: rotateY(0deg);
        }

        .about-img:hover {
            filter: brightness(1) contrast(1);
            transform: rotateY(5deg);
        }

        .about-text {
            color: var(--text-muted);
            font-size: 1.05rem;
            line-height: 1.8;
        }

        /* === TEAM SECTION === */
        .team-member {
            text-align: center;
            margin-bottom: 2rem;
            background: #151515; /* Lebih gelap */
            padding: 30px;
            border: 1px solid #333;
            transition: 0.3s;
            box-shadow: 0 5px 10px rgba(0,0,0,0.3);
        }

        .team-member:hover {
            border-color: var(--neon-purple); /* Border neon saat hover */
            background: #1f1f1f;
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.4), 0 0 15px rgba(138, 43, 226, 0.2);
        }

        .team-img {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            margin-bottom: 20px;
            border: 3px solid var(--neon-pink); /* Border foto team juga neon */
            padding: 5px;
            filter: grayscale(100%);
            transition: filter 0.3s ease;
        }

        .team-img:hover {
            filter: grayscale(0%);
        }

        .team-name {
            color: var(--white);
            margin-bottom: 5px;
        }

        .team-role {
            color: var(--neon-purple); /* Role juga neon */
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        /* === SOCIALS / CLIENTS === */
        .clients-section {
            background-color: #000;
            padding: 50px 0;
            border-top: 1px solid #222;
            border-bottom: 1px solid #222;
        }

        .img-brand {
            height: 40px;
            width: auto;
            filter: grayscale(100%) brightness(0.5);
            opacity: 0.6;
            transition: all 0.3s ease;
        }

        .img-brand:hover {
            filter: grayscale(0%) brightness(1);
            opacity: 1;
            transform: scale(1.1);
        }

        /* === FOOTER === */
        .footer {
            background-color: var(--black);
            padding: 40px 0;
            color: var(--text-muted);
            font-size: 0.9rem;
            border-top: 1px solid #222;
        }

        .footer a {
            color: var(--text-muted);
            transition: 0.2s;
        }

        .footer a:hover {
            color: var(--neon-pink); /* Link footer juga neon */
        }

        /* === ANIMATION === */
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* === RESPONSIVE === */
        @media (max-width: 768px) {
            .hero-title { font-size: 2.5rem; }
            .hero-subtitle { font-size: 1.1rem; }
            .section-title { font-size: 2rem; }
        }
    </style>
</head>

<body id="page-top">

    <section class="hero-section" id="home">
        <div class="hero-content">
            <h1 class="hero-title">Craft Your Legacy</h1>
            <p class="hero-subtitle">Precision engineered mechanical keyboards. Designed for enthusiasts, built for perfection.</p>
            <a class="btn-premium" href="produk.php">Shop Collection</a>
        </div>
    </section>

    <section class="section-padding" id="services">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="section-title">Our Expertise</h2>
                <p class="section-subtitle">Beyond just typing</p>
            </div>
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="service-card">
                        <div class="service-icon">
                            <i class="fas fa-cubes"></i>
                        </div>
                        <h3 class="service-title">Custom Bundles</h3>
                        <p class="service-desc">
                            Curated kits ready to assemble. Case, PCB, plates, and premium switches picked by experts for the ultimate thocky sound signature.
                        </p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="service-card">
                        <div class="service-icon">
                            <i class="fas fa-screwdriver-wrench"></i>
                        </div>
                        <h3 class="service-title">Modding Services</h3>
                        <p class="service-desc">
                            Professional lube service (Krytox 205g0), stabilizer tuning, foam modding, and switch filming. We make your board sound heavenly.
                        </p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="service-card">
                        <div class="service-icon">
                            <i class="fas fa-microchip"></i>
                        </div>
                        <h3 class="service-title">Premium Parts</h3>
                        <p class="service-desc">
                            Sourced from top manufacturers: GMK, Gateron, Kailh, and more. Everything you need to build your endgame keyboard.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="section-padding" style="background-color: #111;">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6 mb-4 mb-lg-0">
                    <h2 class="section-title mb-4">The Styrk Vision</h2>
                    <div class="about-text">
                        <p class="mb-6">
                            Lahir di garasi kecil pada tahun 2023, <strong>Styrk Industries</strong> dimulai dari frustrasi tiga enthusiast terhadap keyboard pasaran yang "begitu-begitu saja".
                        </p>
                        <p class="mb-4">
                            Bagi kami, keyboard bukan sekadar alat input. Ia adalah perpanjangan dari pikiran Anda. Suara "thock" yang sempurna, respons taktil yang presisi, dan estetika yang memukau adalah standar mati kami.
                        </p>
                        <p>
                            Kami hadir untuk mendefinisikan ulang pengalaman mengetik Anda. Dari *gaming* kompetitif hingga *coding* maraton, Styrk ada untuk menemani setiap keystroke.
                        </p>
                    </div>
                    <a href="produk.php" class="btn-premium mt-3" style="padding: 10px 30px; font-size: 0.9rem; border: none; background-color: var(--neon-pink); box-shadow: 0 0 10px rgba(255, 0, 255, 0.4);">Lihat Karya Kami</a>
                </div>
                <div class="col-lg-6">
                    <div class="about-img-wrapper">
                        <img src="https://images.unsplash.com/photo-1595225476474-87563907a212?q=80&w=2071&auto=format&fit=crop" 
                             alt="Keyboard Workshop" class="about-img">
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="section-padding" id="team">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="section-title">The Architects</h2>
                <p class="section-subtitle">Brains behind the build</p>
            </div>
            <div class="row g-4 justify-content-center">
                <div class="col-lg-4 col-md-6">
                    <div class="team-member">
                        <img src="home/assets/img/team/1.jpg" alt="Igris" class="team-img" onerror="this.src='https://via.placeholder.com/150'">
                        <h4 class="team-name">Igris</h4>
                        <p class="team-role">Lead Designer</p>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6">
                    <div class="team-member">
                        <img src="home/assets/img/team/2.jpg" alt="Thomas" class="team-img" onerror="this.src='https://via.placeholder.com/150'">
                        <h4 class="team-name">Thomas</h4>
                        <p class="team-role">Master Mechanic</p>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6">
                    <div class="team-member">
                        <img src="home/assets/img/team/3.jpg" alt="Sung Andre" class="team-img" onerror="this.src='https://via.placeholder.com/150'">
                        <h4 class="team-name">Sung Andre</h4>
                        <p class="team-role">Lead Developer</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <div class="clients-section">
        <div class="container">
            <div class="row align-items-center justify-content-center text-center">
                <div class="col-6 col-md-3 my-2">
                    <a href="https://www.instagram.com/tonyck_gaming" target="_blank">
                        <img class="img-fluid img-brand" src="https://upload.wikimedia.org/wikipedia/commons/thumb/e/e7/Instagram_logo_2016.svg/2048px-Instagram_logo_2016.svg.png" alt="Instagram" />
                    </a>
                </div>
                <div class="col-6 col-md-3 my-2">
                    <a href="https://x.com/TonyCK169" target="_blank">
                        <img class="img-fluid img-brand" src="https://upload.wikimedia.org/wikipedia/commons/5/5a/X_icon_2.svg" alt="Twitter" style="filter: invert(1); opacity: 0.7;" />
                    </a>
                </div>
                <div class="col-6 col-md-3 my-2">
                    <a href="https://www.youtube.com/@JessNoLimit" target="_blank">
                        <img class="img-fluid img-brand" src="https://upload.wikimedia.org/wikipedia/commons/b/b8/YouTube_Logo_2017.svg" alt="YouTube" />
                    </a>
                </div>
                <div class="col-6 col-md-3 my-2">
                    <a href="https://www.tiktok.com/@tonyckgaming" target="_blank">
                        <img class="img-fluid img-brand" src="https://i.postimg.cc/Qd2MZSbF/1000_F_576083591_j-O2u-WDr-W843l-L8e-FMe9a-DZlo-Iri7ghc4.jpg" alt="TikTok" />
                    </a>
                </div>
            </div>
        </div>
    </div>

    <footer class="footer">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-4 text-lg-start">
                    Copyright &copy; Styrk Industries <?= date('Y'); ?>
                </div>
                <div class="col-lg-4 my-3 my-lg-0 text-center">
                    </div>
                <div class="col-lg-4 text-lg-end">
                    <a class="text-decoration-none " href="#!">Privacy Policy</a>
                    <a class="text-decoration-none" href="#!">Terms of Use</a>
                </div>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
```http://googleusercontent.com/image_generation_content/0