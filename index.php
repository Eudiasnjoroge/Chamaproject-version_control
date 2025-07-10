<?php
require_once __DIR__ . '/config/constants.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ChamaPro - Smart Chama Management Platform</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #4a6fa5;
            --primary-dark: #166088;
            --secondary: #4fc3f7;
            --accent: #ff7e5f;
            --light: #f8f9fa;
            --dark: #2c3e50;
            --success: #4caf50;
            --warning: #ff9800;
            --danger: #f44336;
            --gray: #95a5a6;
            --border-radius: 12px;
            --box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            --transition: all 0.3s ease;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
        }

        body {
            background-color: #f5f7ff;
            color: var(--dark);
            line-height: 1.7;
            overflow-x: hidden;
        }

        /* Typography */
        h1, h2, h3, h4 {
            font-weight: 700;
            line-height: 1.3;
        }

        h1 {
            font-size: 3rem;
        }

        h2 {
            font-size: 2.5rem;
            margin-bottom: 1.5rem;
            position: relative;
            display: inline-block;
        }

        h2:after {
            content: '';
            position: absolute;
            bottom: -10px;
            left: 0;
            width: 60px;
            height: 4px;
            background: var(--accent);
            border-radius: 2px;
        }

        h3 {
            font-size: 1.8rem;
        }

        p {
            color: #555;
            font-size: 1.1rem;
        }

        .text-center {
            text-align: center;
        }

        .text-primary {
            color: var(--primary);
        }

        .text-accent {
            color: var(--accent);
        }

        /* Buttons */
        .btn {
            display: inline-block;
            padding: 12px 30px;
            border-radius: 50px;
            font-weight: 600;
            text-decoration: none;
            transition: var(--transition);
            border: none;
            cursor: pointer;
            font-size: 1rem;
            text-align: center;
        }

        .btn-primary {
            background: var(--primary);
            color: white;
        }

        .btn-primary:hover {
            background: var(--primary-dark);
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(74, 111, 165, 0.3);
        }

        .btn-outline {
            background: transparent;
            color: var(--primary);
            border: 2px solid var(--primary);
        }

        .btn-outline:hover {
            background: var(--primary);
            color: white;
        }

        .btn-accent {
            background: var(--accent);
            color: white;
        }

        .btn-accent:hover {
            background: #ff6a45;
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(255, 126, 95, 0.3);
        }

        .btn-lg {
            padding: 15px 40px;
            font-size: 1.1rem;
        }

        /* Layout */
        .container {
            width: 100%;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        section {
            padding: 80px 0;
        }

        .section-header {
            margin-bottom: 50px;
            text-align: center;
        }

        .section-header p {
            max-width: 700px;
            margin: 0 auto;
        }

        /* Header */
        header {
            background: white;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.05);
            position: fixed;
            width: 100%;
            z-index: 1000;
        }

        .navbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px 0;
        }

        .logo {
            display: flex;
            align-items: center;
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--primary);
            text-decoration: none;
        }

        .logo i {
            margin-right: 10px;
            color: var(--accent);
        }

        .nav-links {
            display: flex;
            list-style: none;
        }

        .nav-links li {
            margin-left: 30px;
        }

        .nav-links a {
            color: var(--dark);
            text-decoration: none;
            font-weight: 500;
            transition: var(--transition);
        }

        .nav-links a:hover {
            color: var(--primary);
        }

        .mobile-menu-btn {
            display: none;
            background: none;
            border: none;
            font-size: 1.5rem;
            color: var(--primary);
            cursor: pointer;
        }

        /* Hero Section */
        .hero {
            padding: 180px 0 100px;
            background: linear-gradient(135deg, #f5f7ff 0%, #e8ecff 100%);
            position: relative;
            overflow: hidden;
        }

        .hero-content {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .hero-text {
            flex: 1;
            max-width: 600px;
        }

        .hero-text h1 {
            margin-bottom: 20px;
            font-size: 3.5rem;
        }

        .hero-text h1 span {
            color: var(--accent);
        }

        .hero-text p {
            font-size: 1.2rem;
            margin-bottom: 30px;
        }

        .hero-buttons {
            display: flex;
            gap: 20px;
        }

        .hero-image {
            flex: 1;
            position: relative;
            animation: float 6s ease-in-out infinite;
        }

        .hero-image img {
            max-width: 100%;
            height: auto;
            display: block;
        }

        @keyframes float {
            0% { transform: translateY(0px); }
            50% { transform: translateY(-20px); }
            100% { transform: translateY(0px); }
        }

        /* Features Section */
        .features {
            background: white;
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
        }

        .feature-card {
            background: white;
            border-radius: var(--border-radius);
            padding: 30px;
            box-shadow: var(--box-shadow);
            transition: var(--transition);
            text-align: center;
        }

        .feature-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.15);
        }

        .feature-icon {
            width: 80px;
            height: 80px;
            background: rgba(79, 195, 247, 0.1);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            font-size: 2rem;
            color: var(--primary);
        }

        .feature-card h3 {
            margin-bottom: 15px;
        }

        /* How It Works */
        .how-it-works {
            background: linear-gradient(135deg, #f5f7ff 0%, #e8ecff 100%);
        }

        .steps {
            display: flex;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 20px;
        }

        .step {
            flex: 1;
            min-width: 250px;
            text-align: center;
            position: relative;
            padding: 0 20px;
        }

        .step-number {
            width: 50px;
            height: 50px;
            background: var(--accent);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            font-weight: bold;
            margin: 0 auto 20px;
        }

        .step:not(:last-child):after {
            content: '';
            position: absolute;
            top: 25px;
            right: -30px;
            width: 60px;
            height: 2px;
            background: var(--gray);
            opacity: 0.5;
        }

        /* Testimonials */
        .testimonials {
            background: white;
        }

        .testimonial-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
        }

        .testimonial-card {
            background: white;
            border-radius: var(--border-radius);
            padding: 30px;
            box-shadow: var(--box-shadow);
            position: relative;
        }

        .testimonial-card:before {
            content: '"';
            position: absolute;
            top: 20px;
            left: 20px;
            font-size: 5rem;
            color: rgba(74, 111, 165, 0.1);
            font-family: Georgia, serif;
            line-height: 1;
        }

        .testimonial-content {
            margin-bottom: 20px;
            font-style: italic;
            position: relative;
            z-index: 1;
        }

        .testimonial-author {
            display: flex;
            align-items: center;
        }

        .author-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            overflow: hidden;
            margin-right: 15px;
        }

        .author-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .author-info h4 {
            margin-bottom: 5px;
        }

        .author-info p {
            font-size: 0.9rem;
            color: var(--gray);
        }

        /* Pricing */
        .pricing-card {
            background: white;
            border-radius: var(--border-radius);
            padding: 40px 30px;
            box-shadow: var(--box-shadow);
            text-align: center;
            transition: var(--transition);
            position: relative;
            overflow: hidden;
        }

        .pricing-card:hover {
            transform: translateY(-10px);
        }

        .pricing-card.popular {
            border: 2px solid var(--accent);
        }

        .popular-badge {
            position: absolute;
            top: 15px;
            right: -30px;
            background: var(--accent);
            color: white;
            padding: 5px 30px;
            transform: rotate(45deg);
            font-size: 0.8rem;
            font-weight: bold;
        }

        .pricing-header {
            margin-bottom: 30px;
        }

        .price {
            font-size: 3rem;
            font-weight: 700;
            color: var(--primary);
            margin: 15px 0;
        }

        .price span {
            font-size: 1rem;
            color: var(--gray);
        }

        .pricing-features {
            list-style: none;
            margin: 30px 0;
        }

        .pricing-features li {
            padding: 10px 0;
            border-bottom: 1px solid #eee;
        }

        .pricing-features li:last-child {
            border-bottom: none;
        }

        /* CTA Section */
        .cta {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
            text-align: center;
            padding: 100px 0;
        }

        .cta h2 {
            color: white;
            margin-bottom: 20px;
        }

        .cta h2:after {
            background: var(--accent);
        }

        .cta p {
            color: rgba(255, 255, 255, 0.8);
            max-width: 700px;
            margin: 0 auto 30px;
        }

        /* Footer */
        footer {
            background: var(--dark);
            color: white;
            padding: 60px 0 20px;
        }

        .footer-content {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 40px;
            margin-bottom: 40px;
        }

        .footer-column h3 {
            color: white;
            margin-bottom: 20px;
            font-size: 1.3rem;
        }

        .footer-links {
            list-style: none;
        }

        .footer-links li {
            margin-bottom: 10px;
        }

        .footer-links a {
            color: rgba(255, 255, 255, 0.7);
            text-decoration: none;
            transition: var(--transition);
        }

        .footer-links a:hover {
            color: white;
            padding-left: 5px;
        }

        .social-links {
            display: flex;
            gap: 15px;
        }

        .social-links a {
            color: white;
            background: rgba(255, 255, 255, 0.1);
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: var(--transition);
        }

        .social-links a:hover {
            background: var(--accent);
            transform: translateY(-3px);
        }

        .footer-bottom {
            text-align: center;
            padding-top: 20px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            color: rgba(255, 255, 255, 0.5);
            font-size: 0.9rem;
        }

        /* Responsive Design */
        @media (max-width: 992px) {
            h1 {
                font-size: 2.5rem;
            }
            
            h2 {
                font-size: 2rem;
            }
            
            .hero-content {
                flex-direction: column;
                text-align: center;
            }
            
            .hero-text {
                margin-bottom: 50px;
            }
            
            .hero-buttons {
                justify-content: center;
            }
            
            .step:not(:last-child):after {
                display: none;
            }
        }

        @media (max-width: 768px) {
            .nav-links {
                display: none;
            }
            
            .mobile-menu-btn {
                display: block;
            }
            
            section {
                padding: 60px 0;
            }
            
            .hero {
                padding: 150px 0 80px;
            }
        }

        @media (max-width: 576px) {
            h1 {
                font-size: 2rem;
            }
            
            h2 {
                font-size: 1.8rem;
            }
            
            .hero-buttons {
                flex-direction: column;
                gap: 15px;
            }
            
            .btn {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header>
        <div class="container">
            <nav class="navbar">
                <a href="#" class="logo">
                    <i class="fas fa-hand-holding-usd"></i>ChamaPro
                </a>
                <ul class="nav-links">
                    <li><a href="#features">Features</a></li>
                    <li><a href="#how-it-works">How It Works</a></li>
                    <li><a href="#testimonials">Testimonials</a></li>
                    <li><a href="#pricing">Pricing</a></li>
                    <li><a href="<?= SITE_URL ?>/pages/auth/login.php" class="btn btn-outline">Login</a></li>
                </ul>
                <button class="mobile-menu-btn">
                    <i class="fas fa-bars"></i>
                </button>
            </nav>
        </div>
    </header>

    <!-- Hero Section -->
    <section class="hero">
        <div class="container">
            <div class="hero-content">
                <div class="hero-text">
                    <h1>Smart Chama Management <span>Made Easy</span></h1>
                    <p>Transform how your savings group operates with our all-in-one digital platform. Streamline contributions, track progress, and grow your funds faster than ever before.</p>
                    <div class="hero-buttons">
                        <a href="<?= SITE_URL ?>/pages/auth/register.php" class="btn btn-primary btn-lg">Get Started Free</a>
                        <a href="#how-it-works" class="btn btn-outline btn-lg">Learn More</a>
                    </div>
                </div>
                <div class="hero-image">
                    <img src="https://illustrations.popsy.co/amber/digital-nomad.svg" alt="Chama Management Dashboard">
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="features" id="features">
        <div class="container">
            <div class="section-header">
                <h2>Powerful Features</h2>
                <p>Everything you need to manage your chama efficiently and transparently</p>
            </div>
            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <h3>Group Management</h3>
                    <p>Easily create and manage your chama groups with custom roles, permissions, and member invitations.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <h3>Contribution Tracking</h3>
                    <p>Automatically track member contributions with visual progress indicators and customizable targets.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-bell"></i>
                    </div>
                    <h3>Smart Reminders</h3>
                    <p>Automated reminders and notifications to keep members accountable for their contributions.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-trophy"></i>
                    </div>
                    <h3>Gamification</h3>
                    <p>Motivate members with achievement badges, leaderboards, and visual progress rewards.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-file-invoice-dollar"></i>
                    </div>
                    <h3>Financial Reports</h3>
                    <p>Generate detailed reports and statements for complete financial transparency.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-mobile-alt"></i>
                    </div>
                    <h3>Mobile Friendly</h3>
                    <p>Access your chama from any device, with full functionality on smartphones and tablets.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- How It Works Section -->
    <section class="how-it-works" id="how-it-works">
        <div class="container">
            <div class="section-header">
                <h2>How ChamaPro Works</h2>
                <p>Get started in just a few simple steps</p>
            </div>
            <div class="steps">
                <div class="step">
                    <div class="step-number">1</div>
                    <h3>Create Your Chama</h3>
                    <p>Set up your group in minutes with our simple onboarding process.</p>
                </div>
                <div class="step">
                    <div class="step-number">2</div>
                    <h3>Invite Members</h3>
                    <p>Add members via email or phone and assign roles like admin or treasurer.</p>
                </div>
                <div class="step">
                    <div class="step-number">3</div>
                    <h3>Set Contribution Rules</h3>
                    <p>Define contribution amounts, frequencies, and payment methods.</p>
                </div>
                <div class="step">
                    <div class="step-number">4</div>
                    <h3>Start Growing</h3>
                    <p>Track your progress, celebrate milestones, and achieve your financial goals.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Testimonials Section -->
    <section class="testimonials" id="testimonials">
        <div class="container">
            <div class="section-header">
                <h2>What Our Users Say</h2>
                <p>Hear from chama groups that have transformed their savings with ChamaPro</p>
            </div>
            <div class="testimonial-grid">
                <div class="testimonial-card">
                    <div class="testimonial-content">
                        <p>ChamaPro has completely changed how we manage our group. The transparency and automation have increased our savings by 40% in just 6 months!</p>
                    </div>
                    <div class="testimonial-author">
                        <div class="author-avatar">
                            <img src="https://randomuser.me/api/portraits/women/45.jpg" alt="Sarah M.">
                        </div>
                        <div class="author-info">
                            <h4>Sarah M.</h4>
                            <p>Nairobi Women's Investment Group</p>
                        </div>
                    </div>
                </div>
                <div class="testimonial-card">
                    <div class="testimonial-content">
                        <p>Before ChamaPro, we spent hours tracking contributions on paper. Now everything is automated and accessible to all members. It's been a game-changer!</p>
                    </div>
                    <div class="testimonial-author">
                        <div class="author-avatar">
                            <img src="https://randomuser.me/api/portraits/men/32.jpg" alt="James K.">
                        </div>
                        <div class="author-info">
                            <h4>James K.</h4>
                            <p>Mombasa Youth Business Chama</p>
                        </div>
                    </div>
                </div>
                <div class="testimonial-card">
                    <div class="testimonial-content">
                        <p>The accountability features have helped us maintain 100% contribution consistency for the first time in our 5-year history. Worth every shilling!</p>
                    </div>
                    <div class="testimonial-author">
                        <div class="author-avatar">
                            <img src="https://randomuser.me/api/portraits/women/68.jpg" alt="Grace W.">
                        </div>
                        <div class="author-info">
                            <h4>Grace W.</h4>
                            <p>Kisumu Teachers SACCO</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Pricing Section -->
    <section class="pricing" id="pricing">
        <div class="container">
            <div class="section-header">
                <h2>Simple, Transparent Pricing</h2>
                <p>Choose the plan that fits your chama's needs and budget</p>
            </div>
            <div class="features-grid">
                <div class="pricing-card">
                    <div class="pricing-header">
                        <h3>Basic</h3>
                        <div class="price">Ksh 500<span>/month</span></div>
                        <p>Perfect for small chamas getting started</p>
                    </div>
                    <ul class="pricing-features">
                        <li>Up to 15 members</li>
                        <li>Basic contribution tracking</li>
                        <li>Email reminders</li>
                        <li>Financial reports</li>
                        <li>Email support</li>
                    </ul>
                    <a href="<?= SITE_URL ?>/pages/auth/register.php" class="btn btn-outline">Get Started</a>
                </div>
                <div class="pricing-card popular">
                    <div class="popular-badge">Popular</div>
                    <div class="pricing-header">
                        <h3>Standard</h3>
                        <div class="price">Ksh 1,200<span>/month</span></div>
                        <p>Best for growing chamas</p>
                    </div>
                    <ul class="pricing-features">
                        <li>Up to 30 members</li>
                        <li>Advanced tracking & analytics</li>
                        <li>SMS & email reminders</li>
                        <li>Gamification features</li>
                        <li>Priority support</li>
                        <li>M-Pesa integration</li>
                    </ul>
                    <a href="<?= SITE_URL ?>/pages/auth/register.php" class="btn btn-primary">Get Started</a>
                </div>
                <div class="pricing-card">
                    <div class="pricing-header">
                        <h3>Premium</h3>
                        <div class="price">Ksh 2,500<span>/month</span></div>
                        <p>For large chamas & SACCOs</p>
                    </div>
                    <ul class="pricing-features">
                        <li>Unlimited members</li>
                        <li>All Standard features</li>
                        <li>Custom reporting</li>
                        <li>Multiple admin roles</li>
                        <li>Dedicated account manager</li>
                        <li>API access</li>
                    </ul>
                    <a href="<?= SITE_URL ?>/pages/auth/register.php" class="btn btn-outline">Get Started</a>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="cta">
        <div class="container">
            <h2>Ready to Transform Your Chama?</h2>
            <p>Join thousands of groups already growing their savings with ChamaPro. Start your free 14-day trial today - no credit card required!</p>
            <a href="<?= SITE_URL ?>/pages/auth/register.php" class="btn btn-accent btn-lg">Start Free Trial</a>
        </div>
    </section>

    <!-- Footer -->
    <footer>
        <div class="container">
            <div class="footer-content">
                <div class="footer-column">
                    <h3>ChamaPro</h3>
                    <p>The smartest way to manage your chama or savings group. Digital, transparent, and efficient.</p>
                    <div class="social-links">
                        <a href="#"><i class="fab fa-facebook-f"></i></a>
                        <a href="#"><i class="fab fa-twitter"></i></a>
                        <a href="#"><i class="fab fa-instagram"></i></a>
                        <a href="#"><i class="fab fa-linkedin-in"></i></a>
                    </div>
                </div>
                <div class="footer-column">
                    <h3>Product</h3>
                    <ul class="footer-links">
                        <li><a href="#features">Features</a></li>
                        <li><a href="#pricing">Pricing</a></li>
                        <li><a href="#">Integrations</a></li>
                        <li><a href="#">Roadmap</a></li>
                    </ul>
                </div>
                <div class="footer-column">
                    <h3>Resources</h3>
                    <ul class="footer-links">
                        <li><a href="#">Blog</a></li>
                        <li><a href="#">Guides</a></li>
                        <li><a href="#">Help Center</a></li>
                        <li><a href="#">API Documentation</a></li>
                    </ul>
                </div>
                <div class="footer-column">
                    <h3>Company</h3>
                    <ul class="footer-links">
                        <li><a href="#">About Us</a></li>
                        <li><a href="#">Careers</a></li>
                        <li><a href="#">Contact</a></li>
                        <li><a href="#">Privacy Policy</a></li>
                    </ul>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; <?= date('Y') ?> ChamaPro. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script>
        // Simple mobile menu toggle
        document.querySelector('.mobile-menu-btn').addEventListener('click', function() {
            document.querySelector('.nav-links').classList.toggle('show');
        });
        
        // Smooth scrolling for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                document.querySelector(this.getAttribute('href')).scrollIntoView({
                    behavior: 'smooth'
                });
            });
        });
    </script>
</body>
</html>