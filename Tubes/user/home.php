<?php
// MULAI SESSION
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// INCLUDE KONEKSI & LOGIKA HEADER
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
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700;900&family=Space+Grotesk:wght@400;600;700&display=swap" rel="stylesheet">

    <style>
        /* === MODERN PREMIUM THEME 2025 === */
        :root {
            --black: #0a0a0a;
            --dark-gray: #151515;
            --neon-pink: #FF00FF;
            --neon-purple: #8A2BE2;
            --neon-cyan: #00F5FF;
            --text-main: #e8e8e8;
            --text-muted: #a0a0a0;
            --white: #ffffff;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Outfit', sans-serif;
            background-color: var(--black);
            color: var(--text-main);
            overflow-x: hidden;
            line-height: 1.6;
        }

        html {
            scroll-behavior: smooth;
        }

        h1, h2, h3, h4, h5, h6 {
            font-family: 'Space Grotesk', sans-serif;
            color: var(--white);
            letter-spacing: -1px;
        }

        /* === HERO SECTION WITH PARALLAX === */
        .hero-section {
            position: relative;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }

        .hero-bg {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: url('https://i.postimg.cc/2jHnVCpR/aesthetic-pink-purple-keyboard-upcs1h9i14iu7fn3.jpg') no-repeat center center/cover;
            filter: brightness(0.4);
            transform: scale(1.1);
            transition: transform 0.5s ease-out;
        }

        .hero-section:hover .hero-bg {
            transform: scale(1.15);
        }

        .hero-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, rgba(255, 0, 255, 0.15) 0%, rgba(138, 43, 226, 0.15) 50%, rgba(0, 0, 0, 0.8) 100%);
            z-index: 1;
        }

        /* Animated gradient border */
        .hero-section::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 2px;
            background: linear-gradient(90deg, var(--neon-pink), var(--neon-purple), var(--neon-cyan), var(--neon-pink));
            background-size: 200% 100%;
            animation: gradientShift 3s linear infinite;
        }

        @keyframes gradientShift {
            0% { background-position: 0% 50%; }
            100% { background-position: 200% 50%; }
        }

        .hero-content {
            position: relative;
            z-index: 2;
            text-align: center;
            max-width: 900px;
            padding: 0 20px;
            animation: fadeInUp 1.2s cubic-bezier(0.16, 1, 0.3, 1);
        }

        .hero-title {
            font-size: clamp(3rem, 8vw, 5.5rem);
            font-weight: 900;
            line-height: 1;
            margin-bottom: 1.5rem;
            text-transform: uppercase;
            background: linear-gradient(135deg, var(--neon-pink) 0%, var(--neon-purple) 50%, var(--neon-cyan) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            filter: drop-shadow(0 0 30px rgba(255, 0, 255, 0.5));
            animation: textGlow 2s ease-in-out infinite alternate;
        }

        @keyframes textGlow {
            from { filter: drop-shadow(0 0 20px rgba(255, 0, 255, 0.4)); }
            to { filter: drop-shadow(0 0 40px rgba(255, 0, 255, 0.8)); }
        }

        .hero-subtitle {
            font-size: clamp(1.1rem, 2.5vw, 1.5rem);
            color: var(--text-main);
            margin-bottom: 3rem;
            font-weight: 300;
            letter-spacing: 1px;
            animation: fadeInUp 1.2s cubic-bezier(0.16, 1, 0.3, 1) 0.2s backwards;
        }

        .btn-premium {
            background: linear-gradient(135deg, var(--neon-pink) 0%, var(--neon-purple) 100%);
            color: var(--white);
            padding: 18px 50px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 2px;
            border: none;
            border-radius: 50px;
            transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1);
            text-decoration: none;
            display: inline-block;
            box-shadow: 0 10px 40px rgba(255, 0, 255, 0.4);
            position: relative;
            overflow: hidden;
            animation: fadeInUp 1.2s cubic-bezier(0.16, 1, 0.3, 1) 0.4s backwards;
        }

        .btn-premium::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
            transition: left 0.5s;
        }

        .btn-premium:hover::before {
            left: 100%;
        }

        .btn-premium:hover {
            transform: translateY(-5px) scale(1.05);
            box-shadow: 0 15px 50px rgba(255, 0, 255, 0.6);
        }

        .btn-premium:active {
            transform: translateY(-2px) scale(1.02);
        }

        /* === SCROLL INDICATOR === */
        .scroll-indicator {
            position: absolute;
            bottom: 40px;
            left: 50%;
            transform: translateX(-50%);
            z-index: 3;
            animation: bounce 2s infinite;
        }

        .scroll-indicator i {
            font-size: 2rem;
            color: var(--neon-pink);
            filter: drop-shadow(0 0 10px rgba(255, 0, 255, 0.6));
        }

        @keyframes bounce {
            0%, 20%, 50%, 80%, 100% { transform: translateX(-50%) translateY(0); }
            40% { transform: translateX(-50%) translateY(-20px); }
            60% { transform: translateX(-50%) translateY(-10px); }
        }

        /* === SECTIONS === */
        .section-padding {
            padding: 120px 0;
            position: relative;
        }

        .section-title {
            font-size: clamp(2.5rem, 5vw, 3.5rem);
            margin-bottom: 1rem;
            text-transform: uppercase;
            font-weight: 900;
            position: relative;
            display: inline-block;
        }

        .section-title::after {
            content: '';
            position: absolute;
            bottom: -10px;
            left: 0;
            width: 60%;
            height: 4px;
            background: linear-gradient(90deg, var(--neon-pink), var(--neon-purple));
            border-radius: 2px;
        }

        .section-subtitle {
            font-size: 1rem;
            color: var(--neon-pink);
            margin-bottom: 5rem;
            text-transform: uppercase;
            letter-spacing: 4px;
            font-weight: 600;
        }

        /* === SERVICE CARDS WITH HOVER EFFECTS === */
        .service-card {
            background: rgba(20, 20, 20, 0.8);
            backdrop-filter: blur(10px);
            padding: 50px 35px;
            border: 1px solid rgba(255, 0, 255, 0.1);
            border-radius: 20px;
            transition: all 0.5s cubic-bezier(0.16, 1, 0.3, 1);
            height: 100%;
            position: relative;
            overflow: hidden;
            cursor: pointer;
        }

        .service-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, rgba(255, 0, 255, 0.05), rgba(138, 43, 226, 0.05));
            opacity: 0;
            transition: opacity 0.5s;
        }

        .service-card:hover::before {
            opacity: 1;
        }

        .service-card:hover {
            border-color: var(--neon-pink);
            transform: translateY(-15px);
            box-shadow: 0 20px 60px rgba(255, 0, 255, 0.3);
        }

        .service-icon {
            font-size: 3.5rem;
            background: linear-gradient(135deg, var(--neon-pink), var(--neon-purple));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 30px;
            display: inline-block;
            transition: transform 0.5s;
            filter: drop-shadow(0 0 15px rgba(255, 0, 255, 0.4));
        }

        .service-card:hover .service-icon {
            transform: scale(1.1) rotateY(360deg);
        }

        .service-title {
            font-size: 1.6rem;
            margin-bottom: 20px;
            color: var(--white);
            font-weight: 700;
        }

        .service-desc {
            color: var(--text-muted);
            font-size: 1rem;
            line-height: 1.8;
        }

        /* === ABOUT SECTION === */
        .about-section {
            background: linear-gradient(180deg, var(--black) 0%, var(--dark-gray) 50%, var(--black) 100%);
            position: relative;
        }

        .about-img-wrapper {
            position: relative;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5);
        }
        
        .about-img {
            width: 100%;
            border-radius: 20px;
            filter: grayscale(30%) brightness(0.8);
            transition: all 0.6s cubic-bezier(0.16, 1, 0.3, 1);
            display: block;
        }

        .about-img-wrapper::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, rgba(255, 0, 255, 0.3), transparent);
            opacity: 0;
            transition: opacity 0.6s;
            z-index: 1;
            border-radius: 20px;
        }

        .about-img-wrapper:hover::before {
            opacity: 1;
        }

        .about-img-wrapper:hover .about-img {
            filter: grayscale(0%) brightness(1);
            transform: scale(1.05);
        }

        .about-text {
            color: var(--text-muted);
            font-size: 1.1rem;
            line-height: 1.9;
        }

        .about-text p {
            margin-bottom: 1.5rem;
        }

        .about-text strong {
            color: var(--neon-pink);
            font-weight: 600;
        }

        /* === TEAM SECTION === */
        .team-member {
            text-align: center;
            margin-bottom: 2rem;
            background: rgba(20, 20, 20, 0.6);
            backdrop-filter: blur(10px);
            padding: 40px 30px;
            border: 1px solid rgba(255, 0, 255, 0.1);
            border-radius: 20px;
            transition: all 0.5s cubic-bezier(0.16, 1, 0.3, 1);
            position: relative;
            overflow: hidden;
        }

        .team-member::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255, 0, 255, 0.1) 0%, transparent 70%);
            opacity: 0;
            transition: opacity 0.5s;
        }

        .team-member:hover::before {
            opacity: 1;
        }

        .team-member:hover {
            border-color: var(--neon-purple);
            transform: translateY(-10px);
            box-shadow: 0 20px 50px rgba(138, 43, 226, 0.3);
        }

        .team-img {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
            margin-bottom: 25px;
            border: 4px solid transparent;
            background: linear-gradient(var(--dark-gray), var(--dark-gray)) padding-box,
                        linear-gradient(135deg, var(--neon-pink), var(--neon-purple)) border-box;
            padding: 5px;
            filter: grayscale(100%);
            transition: all 0.5s;
        }

        .team-member:hover .team-img {
            filter: grayscale(0%);
            transform: scale(1.1);
        }

        .team-name {
            color: var(--white);
            margin-bottom: 8px;
            font-size: 1.4rem;
            font-weight: 700;
        }

        .team-role {
            background: linear-gradient(135deg, var(--neon-pink), var(--neon-purple));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            font-size: 0.95rem;
            text-transform: uppercase;
            letter-spacing: 2px;
            font-weight: 600;
        }

        /* === SOCIAL SECTION === */
        .social-section {
            background: var(--dark-gray);
            padding: 80px 0;
            border-top: 1px solid rgba(255, 0, 255, 0.1);
            border-bottom: 1px solid rgba(255, 0, 255, 0.1);
        }

        .social-title {
            text-align: center;
            font-size: 2rem;
            margin-bottom: 3rem;
            color: var(--white);
            text-transform: uppercase;
            letter-spacing: 2px;
        }

        .social-link {
            display: inline-block;
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: rgba(30, 30, 30, 0.8);
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1);
            border: 2px solid rgba(255, 0, 255, 0.2);
            margin: 10px;
        }

        .social-link img {
            height: 35px;
            width: auto;
            filter: brightness(0.6);
            transition: all 0.4s;
        }

        .social-link:hover {
            background: linear-gradient(135deg, var(--neon-pink), var(--neon-purple));
            border-color: var(--neon-cyan);
            transform: translateY(-10px) rotate(5deg);
            box-shadow: 0 15px 40px rgba(255, 0, 255, 0.4);
        }

        .social-link:hover img {
            filter: brightness(1.2);
            transform: scale(1.2);
        }

        /* === FOOTER === */
        .footer {
            background-color: var(--black);
            padding: 60px 0 30px;
            color: var(--text-muted);
            font-size: 0.95rem;
            border-top: 2px solid rgba(255, 0, 255, 0.1);
        }

        .footer-brand {
            font-size: 1.8rem;
            font-weight: 900;
            background: linear-gradient(135deg, var(--neon-pink), var(--neon-purple));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 1rem;
        }

        .footer a {
            color: var(--text-muted);
            transition: all 0.3s;
            text-decoration: none;
            margin: 0 15px;
        }

        .footer a:hover {
            color: var(--neon-pink);
            text-shadow: 0 0 10px rgba(255, 0, 255, 0.5);
        }

        /* === ANIMATIONS === */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(50px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .fade-in {
            opacity: 0;
            transform: translateY(30px);
            transition: all 0.8s cubic-bezier(0.16, 1, 0.3, 1);
        }

        .fade-in.visible {
            opacity: 1;
            transform: translateY(0);
        }

        /* === RESPONSIVE === */
        @media (max-width: 768px) {
            .section-padding {
                padding: 80px 0;
            }

            .hero-subtitle {
                font-size: 1.1rem;
            }

            .btn-premium {
                padding: 15px 35px;
                font-size: 0.9rem;
            }

            .service-card {
                margin-bottom: 30px;
            }

            .social-link {
                width: 60px;
                height: 60px;
            }

            .social-link img {
                height: 25px;
            }
        }
    </style>
</head>

<body id="page-top">

    <!-- HERO SECTION -->
    <section class="hero-section" id="home">
        <div class="hero-bg"></div>
        <div class="hero-overlay"></div>
        <div class="hero-content">
            <h1 class="hero-title">Craft Your Legacy</h1>
            <p class="hero-subtitle">Precision engineered mechanical keyboards. Designed for enthusiasts, built for perfection.</p>
            <a class="btn-premium" href="produk.php">Explore Collection</a>
        </div>
        <div class="scroll-indicator">
            <i class="fas fa-chevron-down"></i>
        </div>
    </section>

    <!-- SERVICES SECTION -->
    <section class="section-padding" id="services">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="section-title fade-in">Our Expertise</h2>
                <p class="section-subtitle fade-in">Beyond just typing</p>
            </div>
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="service-card fade-in">
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
                    <div class="service-card fade-in">
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
                    <div class="service-card fade-in">
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

    <!-- ABOUT SECTION -->
    <section class="section-padding about-section">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6 mb-5 mb-lg-0">
                    <h2 class="section-title mb-4 fade-in">The Styrk Vision</h2>
                    <div class="about-text fade-in">
                        <p>
                            Lahir di garasi kecil pada tahun 2023, <strong>Styrk Industries</strong> dimulai dari frustrasi tiga enthusiast terhadap keyboard pasaran yang "begitu-begitu saja".
                        </p>
                        <p>
                            Bagi kami, keyboard bukan sekadar alat input. Ia adalah perpanjangan dari pikiran Anda. Suara "thock" yang sempurna, respons taktil yang presisi, dan estetika yang memukau adalah standar mati kami.
                        </p>
                        <p>
                            Kami hadir untuk mendefinisikan ulang pengalaman mengetik Anda. Dari gaming kompetitif hingga coding maraton, Styrk ada untuk menemani setiap keystroke.
                        </p>
                    </div>
                    <a href="produk.php" class="btn-premium mt-4">Lihat Karya Kami</a>
                </div>
                <div class="col-lg-6">
                    <div class="about-img-wrapper fade-in">
                        <img src="https://images.unsplash.com/photo-1595225476474-87563907a212?q=80&w=2071&auto=format&fit=crop" 
                             alt="Keyboard Workshop" class="about-img">
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- TEAM SECTION -->
    <section class="section-padding" id="team">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="section-title fade-in">The Architects</h2>
                <p class="section-subtitle fade-in">Brains behind the build</p>
            </div>
            <div class="row g-4 justify-content-center">
                <div class="col-lg-4 col-md-6">
                    <div class="team-member fade-in">
                        <img src="home/assets/img/team/1.jpg" alt="Igris" class="team-img" onerror="this.src='https://via.placeholder.com/150'">
                        <h4 class="team-name">Igris</h4>
                        <p class="team-role">Lead Designer</p>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6">
                    <div class="team-member fade-in">
                        <img src="home/assets/img/team/2.jpg" alt="Thomas" class="team-img" onerror="this.src='https://via.placeholder.com/150'">
                        <h4 class="team-name">Thomas</h4>
                        <p class="team-role">Master Mechanic</p>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6">
                    <div class="team-member fade-in">
                        <img src="home/assets/img/team/3.jpg" alt="Sung Andre" class="team-img" onerror="this.src='https://via.placeholder.com/150'">
                        <h4 class="team-name">Sung Andre</h4>
                        <p class="team-role">Lead Developer</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- SOCIAL SECTION -->
    <div class="social-section">
        <div class="container">
            <h3 class="social-title fade-in">Connect With Us</h3>
            <div class="row align-items-center justify-content-center text-center">
                <div class="col-auto">
                    <a href="https://www.instagram.com/tonyck_gaming" target="_blank" class="social-link fade-in">
                        <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/e/e7/Instagram_logo_2016.svg/2048px-Instagram_logo_2016.svg.png" alt="Instagram" />
                    </a>
                </div>
                <div class="col-auto">
                    <a href="https://x.com/TonyCK169" target="_blank" class="social-link fade-in">
                        <img src="https://upload.wikimedia.org/wikipedia/commons/5/5a/X_icon_2.svg" alt="Twitter" style="filter: invert(1) brightness(0.6);" />
                    </a>
                </div>
                <div class="col-auto">
                    <a href="https://www.youtube.com/@JessNoLimit" target="_blank" class="social-link fade-in">
                        <img src="https://upload.wikimedia.org/wikipedia/commons/b/b8/YouTube_Logo_2017.svg" alt="YouTube" />
                    </a>
                </div>
                <div class="col-auto">
                    <a href="https://www.tiktok.com/@tonyckgaming" target="_blank" class="social-link fade-in">
                        <img src="https://i.postimg.cc/Qd2MZSbF/1000_F_576083591_j-O2u-WDr-W843l-L8e-FMe9a-DZlo-Iri7ghc4.jpg" alt="TikTok" />
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- FOOTER -->
    <footer class="footer">
        <div class="container">
            <div class="text-center mb-4">
                <div class="footer-brand">STYRK INDUSTRIES</div>
            </div>
            <div class="row align-items-center">
                <div class="col-lg-4 text-lg-start text-center mb-3 mb-lg-0">
                    Copyright &copy; Styrk Industries <?= date('Y'); ?>
                </div>
                <div class="col-lg-4 text-center mb-3 mb-lg-0">
                    <p class="mb-0" style="color: var(--neon-pink); font-size: 0.9rem;">Built for Perfection</p>
                </div>
                <div class="col-lg-4 text-lg-end text-center">
                    <a href="#!">Privacy Policy</a>
                    <a href="#!">Terms of Use</a>
                </div>
            </div>
        </div>
    </footer>

    <!-- SCRIPTS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // SCROLL ANIMATION UNTUK FADE-IN ELEMENTS
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };

        const observer = new IntersectionObserver(function(entries) {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('visible');
                }
            });
        }, observerOptions);

        // Observe semua elemen dengan class fade-in
        document.querySelectorAll('.fade-in').forEach(el => {
            observer.observe(el);
        });

        // PARALLAX EFFECT UNTUK HERO BACKGROUND
        window.addEventListener('scroll', () => {
            const scrolled = window.pageYOffset;
            const heroBg = document.querySelector('.hero-bg');
            if (heroBg) {
                heroBg.style.transform = `translateY(${scrolled * 0.5}px) scale(1.1)`;
            }
        });

        // HIDE SCROLL INDICATOR SAAT SCROLL
        window.addEventListener('scroll', () => {
            const scrollIndicator = document.querySelector('.scroll-indicator');
            if (scrollIndicator) {
                if (window.pageYOffset > 100) {
                    scrollIndicator.style.opacity = '0';
                } else {
                    scrollIndicator.style.opacity = '1';
                }
            }
        });
    </script>
</body>

</html>
