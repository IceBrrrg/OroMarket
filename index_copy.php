<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>Oroquieta Marketplace - Fresh. Local. Connected.</title>
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <meta content="marketplace, organic, produce, oroquieta, fresh food, local farmers" name="keywords">
    <meta content="Discover fresh, organic produce from local farmers in Oroquieta City. Connect directly with sellers and support your community." name="description">

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Playfair+Display:wght@400;500;600;700&display=swap" rel="stylesheet">

    <!-- Icons & Bootstrap -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        :root {
            --primary-green: #1B4332;
            --secondary-green: #2D5A3D;
            --accent-green: #52B788;
            --light-green: #B7E4C7;
            --cream: #F8F6F0;
            --warm-white: #FEFFFE;
            --charcoal: #2B2D2F;
            --gold: #D4AF37;
            --gradient-hero: linear-gradient(135deg, rgba(27, 67, 50, 0.95) 0%, rgba(45, 90, 61, 0.9) 50%, rgba(82, 183, 136, 0.85) 100%);
            --shadow-elegant: 0 8px 32px rgba(27, 67, 50, 0.12);
            --shadow-hover: 0 12px 48px rgba(27, 67, 50, 0.18);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            line-height: 1.6;
            color: var(--charcoal);
            overflow-x: hidden;
        }

        /* Smooth scrolling */
        html {
            scroll-behavior: smooth;
        }

        /* Loading Screen */
        .loading-screen {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: var(--gradient-hero);
            display: flex;
            align-items: center;
            justify-content: center;
            flex-direction: column;
            z-index: 9999;
            transition: opacity 0.8s ease-out;
        }

        .loading-screen.fade-out {
            opacity: 0;
            pointer-events: none;
        }

        .loading-spinner {
            width: 60px;
            height: 60px;
            border: 3px solid rgba(255, 255, 255, 0.3);
            border-top: 3px solid var(--gold);
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin-bottom: 20px;
        }

        .loading-text {
            color: white;
            font-size: 1.2rem;
            font-weight: 300;
            letter-spacing: 2px;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* Navigation */
        .navbar-premium {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-bottom: 1px solid rgba(27, 67, 50, 0.1);
            transition: all 0.3s ease;
            padding: 1rem 0;
        }

        .navbar-premium.scrolled {
            background: rgba(255, 255, 255, 0.98);
            box-shadow: var(--shadow-elegant);
        }

        .navbar-brand {
            font-family: 'Playfair Display', serif;
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--primary-green) !important;
            text-decoration: none;
        }

        .nav-link {
            color: var(--charcoal) !important;
            font-weight: 500;
            margin: 0 1rem;
            transition: color 0.3s ease;
            position: relative;
        }

        .nav-link:hover {
            color: var(--accent-green) !important;
        }

        .nav-link::after {
            content: '';
            position: absolute;
            width: 0;
            height: 2px;
            bottom: -5px;
            left: 50%;
            background: var(--accent-green);
            transition: all 0.3s ease;
            transform: translateX(-50%);
        }

        .nav-link:hover::after {
            width: 100%;
        }

        .btn-premium {
            background: var(--primary-green);
            border: none;
            color: white;
            padding: 12px 30px;
            border-radius: 50px;
            font-weight: 600;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
        }

        .btn-premium:hover {
            background: var(--secondary-green);
            transform: translateY(-2px);
            box-shadow: var(--shadow-hover);
            color: white;
        }

        /* Hero Section */
        .hero-section {
            min-height: 100vh;
            display: flex;
            align-items: flex-start;
            justify-content: center;
            flex-direction: column;
            position: relative;
            background: var(--gradient-hero);
            overflow: visible;
            padding: 140px 0 100px;
        }

        .hero-background {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: url('https://images.unsplash.com/photo-1542838132-92c53300491e?w=1920&h=1080&fit=crop&q=80') center/cover;
            z-index: -1;
        }

        .hero-content {
            position: relative;
            z-index: 2;
            color: white;
            max-width: 600px;
            padding-bottom: 60px;
            margin-top: auto;
            margin-bottom: auto;
        }

        .hero-badge {
            display: inline-block;
            background: rgba(255, 255, 255, 0.2);
            color: white;
            padding: 8px 20px;
            border-radius: 50px;
            font-size: 0.9rem;
            font-weight: 500;
            margin-bottom: 2rem;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.3);
        }

        .hero-title {
            font-family: 'Playfair Display', serif;
            font-size: 4.5rem;
            font-weight: 700;
            line-height: 1.1;
            margin-bottom: 1.5rem;
            background: linear-gradient(45deg, #ffffff, #B7E4C7);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .hero-subtitle {
            font-size: 1.3rem;
            font-weight: 300;
            margin-bottom: 2.5rem;
            opacity: 0.9;
            line-height: 1.6;
        }

        .hero-cta {
            display: flex;
            gap: 1.5rem;
            align-items: center;
            flex-wrap: wrap;
            margin-top: 2rem;
            padding-bottom: 20px;
        }

        .btn-hero-primary {
            background: var(--gold);
            color: var(--primary-green);
            padding: 15px 35px;
            border-radius: 50px;
            font-weight: 700;
            font-size: 1.1rem;
            text-decoration: none;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 10px;
        }

        .btn-hero-primary:hover {
            background: #F4C430;
            color: var(--primary-green);
            transform: translateY(-3px);
            box-shadow: 0 10px 30px rgba(212, 175, 55, 0.4);
        }

        .btn-hero-secondary {
            color: white;
            padding: 15px 35px;
            border: 2px solid rgba(255, 255, 255, 0.5);
            border-radius: 50px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
            backdrop-filter: blur(10px);
        }

        .btn-hero-secondary:hover {
            background: rgba(255, 255, 255, 0.1);
            border-color: white;
            color: white;
        }

        /* Product Showcase */
        .product-showcase {
            position: absolute;
            right: 5%;
            top: 50%;
            transform: translateY(-50%);
            width: 450px;
            z-index: 2;
        }

        .product-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .product-card {
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(15px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 20px;
            padding: 1.5rem;
            text-align: center;
            transition: all 0.4s ease;
            color: white;
        }

        .product-card:hover {
            transform: translateY(-10px) scale(1.05);
            background: rgba(255, 255, 255, 0.25);
        }

        .product-card img {
            width: 100%;
            height: 120px;
            object-fit: cover;
            border-radius: 15px;
            margin-bottom: 1rem;
        }

        .product-card h6 {
            margin-bottom: 0.5rem;
            font-weight: 600;
        }

        .product-price {
            color: var(--gold);
            font-weight: 700;
        }

        /* Stats Section */
        .stats-section {
            background: white;
            padding: 6rem 0;
            margin-top: -60px;
            position: relative;
            z-index: 3;
        }

        .stats-container {
            background: white;
            border-radius: 30px;
            box-shadow: var(--shadow-elegant);
            padding: 4rem 3rem;
            margin: 0 auto;
            max-width: 1000px;
        }

        .stat-item {
            text-align: center;
        }

        .stat-number {
            font-family: 'Playfair Display', serif;
            font-size: 3rem;
            font-weight: 700;
            color: var(--accent-green);
            display: block;
        }

        .stat-label {
            color: var(--charcoal);
            font-weight: 500;
            margin-top: 0.5rem;
        }

        /* Features Section */
        .features-section {
            background: var(--cream);
            padding: 8rem 0;
        }

        .section-header {
            text-align: center;
            max-width: 700px;
            margin: 0 auto 5rem;
        }

        .section-badge {
            display: inline-block;
            background: var(--light-green);
            color: var(--primary-green);
            padding: 8px 20px;
            border-radius: 50px;
            font-size: 0.9rem;
            font-weight: 600;
            margin-bottom: 1rem;
        }

        .section-title {
            font-family: 'Playfair Display', serif;
            font-size: 3rem;
            font-weight: 700;
            color: var(--primary-green);
            margin-bottom: 1.5rem;
        }

        .section-subtitle {
            font-size: 1.2rem;
            color: var(--charcoal);
            opacity: 0.8;
        }

        .feature-card {
            background: white;
            border-radius: 25px;
            padding: 3rem 2rem;
            text-align: center;
            box-shadow: var(--shadow-elegant);
            transition: all 0.4s ease;
            border: 1px solid rgba(27, 67, 50, 0.05);
            height: 100%;
        }

        .feature-card:hover {
            transform: translateY(-10px);
            box-shadow: var(--shadow-hover);
        }

        .feature-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, var(--accent-green), var(--light-green));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 2rem;
            font-size: 1.8rem;
            color: white;
        }

        .feature-title {
            font-size: 1.3rem;
            font-weight: 700;
            color: var(--primary-green);
            margin-bottom: 1rem;
        }

        .feature-description {
            color: var(--charcoal);
            opacity: 0.8;
            line-height: 1.6;
        }

        /* CTA Section */
        .cta-section {
            background: var(--primary-green);
            padding: 6rem 0;
            color: white;
            text-align: center;
        }

        .cta-title {
            font-family: 'Playfair Display', serif;
            font-size: 3rem;
            font-weight: 700;
            margin-bottom: 1.5rem;
        }

        .cta-subtitle {
            font-size: 1.2rem;
            margin-bottom: 3rem;
            opacity: 0.9;
            max-width: 600px;
            margin-left: auto;
            margin-right: auto;
        }

        .cta-buttons {
            display: flex;
            gap: 1.5rem;
            justify-content: center;
            flex-wrap: wrap;
        }

        .btn-cta-primary {
            background: var(--gold);
            color: var(--primary-green);
            padding: 18px 40px;
            border-radius: 50px;
            font-weight: 700;
            font-size: 1.1rem;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .btn-cta-primary:hover {
            background: #F4C430;
            color: var(--primary-green);
            transform: translateY(-3px);
        }

        .btn-cta-secondary {
            color: white;
            padding: 18px 40px;
            border: 2px solid rgba(255, 255, 255, 0.5);
            border-radius: 50px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .btn-cta-secondary:hover {
            background: rgba(255, 255, 255, 0.1);
            border-color: white;
            color: white;
        }

        /* Footer */
        .footer-premium {
            background: var(--charcoal);
            color: white;
            padding: 5rem 0 2rem;
        }

        .footer-brand {
            font-family: 'Playfair Display', serif;
            font-size: 2rem;
            font-weight: 700;
            color: var(--accent-green);
            margin-bottom: 1rem;
        }

        .footer-description {
            opacity: 0.8;
            margin-bottom: 2rem;
            line-height: 1.6;
        }

        .footer-title {
            font-weight: 700;
            margin-bottom: 1.5rem;
            color: var(--accent-green);
        }

        .footer-link {
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            display: block;
            margin-bottom: 0.5rem;
            transition: color 0.3s ease;
        }

        .footer-link:hover {
            color: var(--accent-green);
        }

        .social-links {
            display: flex;
            gap: 1rem;
            margin-top: 2rem;
        }

        .social-link {
            width: 45px;
            height: 45px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .social-link:hover {
            background: var(--accent-green);
            color: white;
            transform: translateY(-3px);
        }

        .footer-bottom {
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            padding-top: 2rem;
            margin-top: 3rem;
            text-align: center;
            opacity: 0.6;
        }

        /* Animations */
        .fade-up {
            opacity: 0;
            transform: translateY(50px);
            transition: all 0.8s ease;
        }

        .fade-up.visible {
            opacity: 1;
            transform: translateY(0);
        }

        /* Responsive Design */
        @media (max-width: 1200px) {
            .product-showcase {
                display: none;
            }
            
            .hero-content {
                max-width: 100%;
                text-align: center;
            }
            
            .hero-section {
                padding: 120px 0 120px;
            }
        }

        @media (max-width: 768px) {
            .hero-section {
                padding: 100px 0 100px;
                min-height: auto;
                height: auto;
            }
            
            .hero-title {
                font-size: 3rem;
            }
            
            .section-title {
                font-size: 2.5rem;
            }
            
            .cta-title {
                font-size: 2.5rem;
            }
            
            .hero-cta, .cta-buttons {
                flex-direction: column;
                align-items: center;
                gap: 1rem;
                width: 100%;
            }
            
            .stats-container {
                padding: 3rem 2rem;
                margin-top: 2rem;
            }
            
            .stat-number {
                font-size: 2.5rem;
            }
            
            .hero-content {
                padding-bottom: 40px;
            }
            
            .stats-section {
                margin-top: -40px;
            }
        }

        @media (max-width: 576px) {
            .hero-section {
                padding: 90px 15px 80px;
                height: auto;
                min-height: auto;
            }
            
            .hero-title {
                font-size: 2.5rem;
                line-height: 1.2;
            }
            
            .section-title {
                font-size: 2rem;
            }
            
            .cta-title {
                font-size: 2rem;
            }
            
            .feature-card {
                padding: 2rem 1.5rem;
            }
            
            .btn-hero-primary, .btn-hero-secondary {
                padding: 15px 30px;
                font-size: 1rem;
                width: 100%;
                max-width: 280px;
                text-align: center;
                justify-content: center;
            }
            
            .hero-cta {
                width: 100%;
                align-items: stretch;
            }
            
            .hero-content {
                padding-bottom: 30px;
            }
        }
    </style>
</head>

<body>
    <!-- Loading Screen -->
    <div class="loading-screen" id="loadingScreen">
        <div class="loading-spinner"></div>
        <div class="loading-text">OROQUIETA MARKETPLACE</div>
    </div>

    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-premium fixed-top">
        <div class="container">
            <a class="navbar-brand" href="/">Oroquieta Marketplace</a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <i class="fas fa-bars"></i>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav mx-auto">
                    <li class="nav-item"><a class="nav-link" href="#home">Home</a></li>
                    <li class="nav-item"><a class="nav-link" href="#features">Features</a></li>
                    <li class="nav-item"><a class="nav-link" href="#marketplace">Marketplace</a></li>
                    <li class="nav-item"><a class="nav-link" href="#about">About</a></li>
                    <li class="nav-item"><a class="nav-link" href="#contact">Contact</a></li>
                </ul>
                
                <div class="d-flex gap-3">
                    <a href="seller/login.php" class="btn-premium">Seller Portal</a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero-section" id="home">
        <div class="hero-background"></div>
        
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-7">
                    <div class="hero-content fade-up">
                        <span class="hero-badge">🌱 100% Fresh & Organic</span>
                        
                        <h1 class="hero-title">Fresh Market.<br>Local Connection.</h1>
                        
                        <p class="hero-subtitle">
                            Discover the finest organic produce directly from Oroquieta's local farmers and vendors. 
                            Experience transparent pricing, direct communication, and community-driven commerce.
                        </p>
                        
                        <div class="hero-cta">
                            <a href="customer/index.php" class="btn-hero-primary">
                                <i class="fas fa-shopping-basket"></i>
                                Explore Marketplace
                            </a>
                            <a href="#features" class="btn-hero-secondary">Learn More</a>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-5 d-none d-lg-block">
                    <div class="product-showcase fade-up">
                        <div class="product-grid">
                            <div class="product-card">
                                <img src="https://images.unsplash.com/photo-1560472354-b33ff0c44a43?w=300&h=200&fit=crop" alt="Fresh Fruits">
                                <h6>Fresh Fruits</h6>
                                <p class="mb-1">Premium Quality</p>
                                <span class="product-price">₱45/kg</span>
                            </div>
                            
                            <div class="product-card">
                                <img src="https://images.unsplash.com/photo-1540420773420-3366772f4999?w=300&h=200&fit=crop" alt="Vegetables">
                                <h6>Organic Veggies</h6>
                                <p class="mb-1">Farm Fresh</p>
                                <span class="product-price">₱35/kg</span>
                            </div>
                            
                            <div class="product-card">
                                <img src="https://images.unsplash.com/photo-1586201375761-83865001e31c?w=300&h=200&fit=crop" alt="Rice">
                                <h6>Premium Rice</h6>
                                <p class="mb-1">Local Variety</p>
                                <span class="product-price">₱52/kg</span>
                            </div>
                            
                            <div class="product-card">
                                <img src="https://images.unsplash.com/photo-1544943150-4c4f1ea0dcb4?w=300&h=200&fit=crop" alt="Seafood">
                                <h6>Fresh Seafood</h6>
                                <p class="mb-1">Daily Catch</p>
                                <span class="product-price">₱180/kg</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Stats Section -->
    <section class="stats-section">
        <div class="container">
            <div class="stats-container fade-up">
                <div class="row">
                    <div class="col-lg-3 col-md-6 mb-4 mb-lg-0">
                        <div class="stat-item">
                            <span class="stat-number" data-target="500">0</span>
                            <div class="stat-label">Local Farmers</div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6 mb-4 mb-lg-0">
                        <div class="stat-item">
                            <span class="stat-number" data-target="50">0</span>
                            <div class="stat-label">Product Categories</div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6 mb-4 mb-lg-0">
                        <div class="stat-item">
                            <span class="stat-number" data-target="10000">0</span>
                            <div class="stat-label">Happy Customers</div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6">
                        <div class="stat-item">
                            <span class="stat-number" data-target="98">0</span>
                            <div class="stat-label">Satisfaction Rate %</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="features-section" id="features">
        <div class="container">
            <div class="section-header fade-up">
                <span class="section-badge">Why Choose Us</span>
                <h2 class="section-title">Experience the Future of Local Shopping</h2>
                <p class="section-subtitle">
                    We're revolutionizing how you connect with local farmers and vendors, 
                    bringing transparency, quality, and community to your fingertips.
                </p>
            </div>
            
            <div class="row g-4">
                <div class="col-lg-3 col-md-6 fade-up">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <h4 class="feature-title">Price Transparency</h4>
                        <p class="feature-description">
                            Real-time price monitoring and comparison tools help you make informed purchasing decisions and budget effectively.
                        </p>
                    </div>
                </div>
                
                <div class="col-lg-3 col-md-6 fade-up">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-handshake"></i>
                        </div>
                        <h4 class="feature-title">Direct Connection</h4>
                        <p class="feature-description">
                            Connect directly with local farmers and vendors, ensuring freshness and supporting your community.
                        </p>
                    </div>
                </div>
                
                <div class="col-lg-3 col-md-6 fade-up">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-leaf"></i>
                        </div>
                        <h4 class="feature-title">100% Organic</h4>
                        <p class="feature-description">
                            Verified organic produce from trusted local sources, promoting healthy living and sustainable farming.
                        </p>
                    </div>
                </div>
                
                <div class="col-lg-3 col-md-6 fade-up">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-mobile-alt"></i>
                        </div>
                        <h4 class="feature-title">Easy Access</h4>
                        <p class="feature-description">
                            User-friendly platform that works seamlessly across all devices, making local shopping simple and convenient.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="cta-section">
        <div class="container">
            <div class="fade-up">
                <h2 class="cta-title">Ready to Transform Your Shopping Experience?</h2>
                <p class="cta-subtitle">
                    Join thousands of satisfied customers who have discovered the joy of fresh, local, and organic produce. 
                    Start your journey with Oroquieta Marketplace today.
                </p>
                
                <div class="cta-buttons">
                    <a href="customer/index.php" class="btn-cta-primary">Start Shopping Now</a>
                    <a href="seller/signup.php" class="btn-cta-secondary">Become a Seller</a>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer-premium">
        <div class="container">
            <div class="row g-5">
                <div class="col-lg-4">
                    <h3 class="footer-brand">Oroquieta Marketplace</h3>
                    <p class="footer-description">
                        Connecting local farmers with conscious consumers through innovative technology. 
                        Fresh, organic, and sustainable - that's our promise to the Oroquieta community.
                    </p>
                    
                    <div class="social-links">
                        <a href="#" class="social-link"><i class="fab fa-facebook-f"></i></a>
                        <a href="#" class="social-link"><i class="fab fa-instagram"></i></a>
                        <a href="#" class="social-link"><i class="fab fa-twitter"></i></a>
                        <a href="#" class="social-link"><i class="fab fa-youtube"></i></a>
                    </div>
                </div>
                
                <div class="col-lg-2 col-md-6">
                    <h5 class="footer-title">Quick Links</h5>
                    <a href="#" class="footer-link">Home</a>
                    <a href="#features" class="footer-link">Features</a>
                    <a href="customer/index.php" class="footer-link">Marketplace</a>
                    <a href="#" class="footer-link">About Us</a>
                    <a href="#contact" class="footer-link">Contact</a>
                </div>
                
                <div class="col-lg-2 col-md-6">
                    <h5 class="footer-title">For Sellers</h5>
                    <a href="seller/signup.php" class="footer-link">Become a Seller</a>
                    <a href="seller/login.php" class="footer-link">Seller Portal</a>
                    <a href="#" class="footer-link">Seller Guidelines</a>
                    <a href="#" class="footer-link">Commission Rates</a>
                    <a href="#" class="footer-link">Support Center</a>
                </div>
                
                <div class="col-lg-2 col-md-6">
                    <h5 class="footer-title">Support</h5>
                    <a href="#" class="footer-link">Help Center</a>
                    <a href="#" class="footer-link">Privacy Policy</a>
                    <a href="#" class="footer-link">Terms of Service</a>
                    <a href="#" class="footer-link">Refund Policy</a>
                    <a href="admin/login.php" class="footer-link">Admin Portal</a>
                </div>
                
                <div class="col-lg-2 col-md-6">
                    <h5 class="footer-title">Contact Info</h5>
                    <div class="footer-link" style="cursor: default;">
                        <i class="fas fa-map-marker-alt me-2"></i>
                        Barrientos St, Oroquieta City
                    </div>
                    <div class="footer-link" style="cursor: default;">
                        <i class="fas fa-envelope me-2"></i>
                        info@oroquieta-market.com
                    </div>
                    <div class="footer-link" style="cursor: default;">
                        <i class="fas fa-phone me-2"></i>
                        +63 912 345 6789
                    </div>
                    <div class="footer-link" style="cursor: default;">
                        <i class="fas fa-clock me-2"></i>
                        Daily: 6AM - 6PM
                    </div>
                </div>
            </div>
            
            <div class="footer-bottom">
                <div class="row align-items-center">
                    <div class="col-md-6">
                        <p class="mb-0">&copy; 2024 Oroquieta Marketplace. All rights reserved.</p>
                    </div>
                    <div class="col-md-6 text-md-end">
                    </div>
                </div>
            </div>
        </div>
    </footer>

    <!-- Back to Top Button -->
    <a href="#home" class="btn btn-success position-fixed bottom-0 end-0 m-4 rounded-circle shadow-lg" 
       style="width: 55px; height: 55px; display: none; align-items: center; justify-content: center; z-index: 1000; background: var(--accent-green); border: none;" 
       id="backToTop">
        <i class="fas fa-arrow-up"></i>
    </a>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Loading screen
        window.addEventListener('load', function() {
            setTimeout(() => {
                document.getElementById('loadingScreen').classList.add('fade-out');
            }, 1000);
        });

        // Navbar scroll effect
        window.addEventListener('scroll', function() {
            const navbar = document.querySelector('.navbar-premium');
            const backToTopBtn = document.getElementById('backToTop');
            
            if (window.scrollY > 100) {
                navbar.classList.add('scrolled');
                backToTopBtn.style.display = 'flex';
            } else {
                navbar.classList.remove('scrolled');
                backToTopBtn.style.display = 'none';
            }
        });

        // Scroll animations
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

        // Observe all fade-up elements
        document.querySelectorAll('.fade-up').forEach(el => {
            observer.observe(el);
        });

        // Animated counters
        function animateCounter(element, target, duration) {
            let start = 0;
            const increment = target / (duration / 16);
            
            function updateCounter() {
                start += increment;
                element.textContent = Math.floor(start);
                
                if (start < target) {
                    requestAnimationFrame(updateCounter);
                } else {
                    element.textContent = target + (target === 98 ? '%' : '+');
                }
            }
            
            updateCounter();
        }

        // Stats animation
        const statsObserver = new IntersectionObserver(function(entries) {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const statNumbers = entry.target.querySelectorAll('.stat-number');
                    statNumbers.forEach(stat => {
                        const target = parseInt(stat.dataset.target);
                        animateCounter(stat, target, 2000);
                    });
                    statsObserver.unobserve(entry.target);
                }
            });
        });

        const statsSection = document.querySelector('.stats-section');
        if (statsSection) {
            statsObserver.observe(statsSection);
        }

        // Smooth scrolling for navigation links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    const navHeight = document.querySelector('.navbar-premium').offsetHeight;
                    const targetPosition = target.offsetTop - navHeight;
                    
                    window.scrollTo({
                        top: targetPosition,
                        behavior: 'smooth'
                    });
                }
            });
        });

        // Mobile menu close on link click
        document.querySelectorAll('.nav-link').forEach(link => {
            link.addEventListener('click', function() {
                const navbarCollapse = document.getElementById('navbarNav');
                if (navbarCollapse.classList.contains('show')) {
                    const bsCollapse = new bootstrap.Collapse(navbarCollapse);
                    bsCollapse.hide();
                }
            });
        });

        // Product cards hover effect
        document.querySelectorAll('.product-card').forEach(card => {
            card.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-10px) scale(1.05)';
            });
            
            card.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0) scale(1)';
            });
        });

        // Parallax effect for hero background
        window.addEventListener('scroll', function() {
            const scrolled = window.pageYOffset;
            const heroBackground = document.querySelector('.hero-background');
            if (heroBackground && scrolled < window.innerHeight) {
                heroBackground.style.transform = `translateY(${scrolled * 0.5}px)`;
            }
        });

        // Add loading animation to buttons
        document.querySelectorAll('[href*=".php"]').forEach(link => {
            link.addEventListener('click', function(e) {
                // Add loading state
                const originalText = this.innerHTML;
                this.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Loading...';
                this.style.pointerEvents = 'none';
                
                // Reset after 3 seconds if page hasn't loaded
                setTimeout(() => {
                    this.innerHTML = originalText;
                    this.style.pointerEvents = 'auto';
                }, 3000);
            });
        });

        // Initialize tooltips if Bootstrap is available
        if (typeof bootstrap !== 'undefined') {
            const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            const tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
        }
    </script>
</body>
</html>