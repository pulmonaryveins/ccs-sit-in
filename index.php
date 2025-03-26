<?php
session_start();

// Check if user is already logged in, redirect appropriately
if (isset($_SESSION['admin_logged_in'])) {
    header('Location: view/admin_dashboard.php');
    exit();
} elseif (isset($_SESSION['user_logged_in'])) {
    header('Location: view/dashboard.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CCS Sit-In Monitoring System</title>
    <link rel="stylesheet" href="assets/css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/remixicon/4.6.0/remixicon.css">
    <script src="https://unpkg.com/@tailwindcss/browser@4"></script>
    <style>
        :root {
            --primary-color: #7556cc;
            --secondary-color: #d569a7;
            --accent-color: #9b5fb9;
            --text-dark: #1f2937;
            --text-light: #f9fafb;
            --bg-light: #f3f4f6;
            --bg-white: #ffffff;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: var(--text-dark);
            background-color: var(--bg-light);
            overflow-x: hidden;
            opacity: 0;
            animation: fadeIn 0.8s ease-out forwards;
        }
        
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        /* Header and Navigation - Updated to match dashboard style */
        .header {
            background-color: var(--bg-white);
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            position: fixed;
            width: 100%;
            top: 0;
            z-index: 100;
            opacity: 0;
            transform: translateY(-10px);
            animation: navSlideDown 0.5s ease-out forwards;
        }
        
        @keyframes navSlideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .header-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0.75rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .logo {
            display: flex;
            align-items: center;
            text-decoration: none;
            color: var(--primary-color);
            font-weight: 700;
            font-size: 1.25rem;
            gap: 0.5rem;
        }
        
        .logo i {
            font-size: 1.5rem;
        }
        
        .nav-links {
            display: flex;
            gap: 2rem;
        }
        
        .nav-link {
            text-decoration: none;
            color: var(--text-dark);
            font-weight: 500;
            transition: all 0.3s;
            position: relative;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 0.75rem;
            border-radius: 0.375rem;
        }
        
        .nav-link:hover {
            color: var(--primary-color);
            background-color: rgba(117, 86, 204, 0.05);
        }
        
        .nav-link i {
            font-size: 1.25rem;
        }
        
        .auth-buttons {
            display: flex;
            gap: 0.75rem;
        }
        
        .btn {
            padding: 0.5rem 1.25rem;
            border-radius: 0.375rem;
            font-weight: 500;
            transition: all 0.3s;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        
        .btn-outline {
            border: 1px solid var(--primary-color);
            color: var(--primary-color);
            background: transparent;
        }
        
        .btn-outline:hover {
            background-color: var(--primary-color);
            color: var(--bg-white);
            transform: translateY(-2px);
            box-shadow: 0 4px 6px rgba(117, 86, 204, 0.2);
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            color: var(--bg-white);
            border: 1px solid var(--primary-color);
        }
        
        .btn-primary:hover {
            background-color: #6445b8;
            border-color: #6445b8;
            transform: translateY(-2px);
            box-shadow: 0 4px 6px rgba(117, 86, 204, 0.3);
        }
        
        /* Hero Section - Updated with dashboard-like elements */
        .hero {
            padding: 9rem 2rem 6rem;
            background: linear-gradient(135deg, #f9fafb 0%, #e5e7eb 100%);
            text-align: center;
            opacity: 0;
            animation: fadeUp 1s ease-out 0.3s forwards;
        }
        
        @keyframes fadeUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .hero-container {
            max-width: 1000px;
            margin: 0 auto;
            background-color: var(--bg-white);
            border-radius: 0.5rem;
            padding: 3rem 2rem;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        }
        
        .hero-title {
            font-size: 2.5rem;
            font-weight: 800;
            margin-bottom: 1.5rem;
            color: var(--text-dark);
            line-height: 1.2;
        }
        
        .hero-title span {
            color: var(--primary-color);
        }
        
        .hero-subtitle {
            font-size: 1.125rem;
            color: #4b5563;
            max-width: 700px;
            margin: 0 auto 2.5rem;
            line-height: 1.6;
        }
        
        .hero-buttons {
            display: flex;
            gap: 1rem;
            justify-content: center;
        }
        
        /* Features Section - With dashboard card styling */
        .features {
            padding: 5rem 2rem;
            background-color: var(--bg-light);
            opacity: 0;
            animation: fadeUp 1s ease-out 0.6s forwards;
        }
        
        .section-title {
            font-size: 2rem;
            font-weight: 700;
            text-align: center;
            margin-bottom: 3rem;
            color: var(--text-dark);
            position: relative;
            padding-bottom: 1rem;
        }
        
        .section-title:after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 60px;
            height: 3px;
            background: linear-gradient(90deg, var(--primary-color), var(--secondary-color));
            border-radius: 3px;
        }
        
        .section-title span {
            color: var(--primary-color);
        }
        
        .features-container {
            max-width: 1200px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
        }
        
        .feature-card {
            background-color: var(--bg-white);
            border-radius: 0.5rem;
            padding: 2rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            transition: transform 0.3s, box-shadow 0.3s;
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            border-top: 4px solid var(--primary-color);
        }
        
        .feature-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        }
        
        .feature-icon {
            margin-bottom: 1.5rem;
            width: 64px;
            height: 64px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: rgba(117, 86, 204, 0.1);
            color: var(--primary-color);
        }
        
        .feature-icon i {
            font-size: 2rem;
        }
        
        .feature-title {
            font-size: 1.25rem;
            font-weight: 600;
            margin-bottom: 1rem;
            color: var(--text-dark);
        }
        
        .feature-description {
            color: #4b5563;
            line-height: 1.6;
        }
        
        /* How It Works Section - Updated with consistent style */
        .how-it-works {
            padding: 5rem 2rem;
            background-color: var(--bg-white);
            opacity: 0;
            animation: fadeUp 1s ease-out 0.9s forwards;
        }
        
        .steps-container {
            max-width: 900px;
            margin: 0 auto;
            background-color: var(--bg-white);
            border-radius: 0.5rem;
            padding: 2rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }
        
        .step {
            display: flex;
            gap: 1.5rem;
            margin-bottom: 2.5rem;
            align-items: center;
            padding: 1rem;
            border-radius: 0.5rem;
            transition: background-color 0.3s;
        }
        
        .step:hover {
            background-color: rgba(117, 86, 204, 0.05);
        }
        
        .step:last-child {
            margin-bottom: 0;
        }
        
        .step:nth-child(even) {
            flex-direction: row-reverse;
        }
        
        .step-number {
            flex-shrink: 0;
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: var(--bg-white);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
            font-weight: 700;
            box-shadow: 0 4px 6px rgba(117, 86, 204, 0.3);
        }
        
        .step-content {
            flex-grow: 1;
        }
        
        .step-title {
            font-size: 1.25rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: var(--text-dark);
        }
        
        .step-description {
            color: #4b5563;
            line-height: 1.6;
        }
        
        /* About Section - Updated with dashboard styling */
        .about {
            padding: 5rem 2rem;
            background-color: var(--bg-light);
            opacity: 0;
            animation: fadeUp 1s ease-out 1.2s forwards;
        }
        
        .about-container {
            max-width: 1000px;
            margin: 0 auto;
            display: flex;
            gap: 3rem;
            align-items: center;
            background-color: var(--bg-white);
            border-radius: 0.5rem;
            padding: 2rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }
        
        .about-image {
            flex: 1;
            max-width: 450px;
        }
        
        .about-image img {
            width: 100%;
            height: auto;
            border-radius: 0.5rem;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        }
        
        .about-content {
            flex: 1;
        }
        
        .about-title {
            font-size: 1.75rem;
            font-weight: 700;
            margin-bottom: 1.25rem;
            color: var (--text-dark);
        }
        
        .about-description {
            color: #4b5563;
            line-height: 1.8;
            margin-bottom: 1.5rem;
        }
        
        /* Footer - Updated to match dashboard styling */
        .footer {
            background-color: #1f2937;
            color: var(--text-light);
            padding: 4rem 2rem 2rem;
            opacity: 0;
            animation: fadeUp 1s ease-out 1.5s forwards;
        }
        
        .footer-container {
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .footer-top {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 2rem;
            margin-bottom: 3rem;
        }
        
        .footer-logo {
            display: flex;
            align-items: center;
            font-weight: 700;
            font-size: 1.5rem;
            margin-bottom: 1rem;
            color: var(--bg-white);
            gap: 0.5rem;
        }
        
        .footer-tagline {
            color: #d1d5db;
            line-height: 1.6;
        }
        
        .footer-links-title {
            font-size: 1.125rem;
            font-weight: 600;
            margin-bottom: 1.25rem;
            color: var(--bg-white);
            position: relative;
            padding-bottom: 0.75rem;
        }
        
        .footer-links-title:after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 40px;
            height: 2px;
            background-color: var(--primary-color);
        }
        
        .footer-links {
            list-style: none;
            padding: 0;
        }
        
        .footer-link {
            margin-bottom: 0.75rem;
        }
        
        .footer-link a {
            color: #d1d5db;
            text-decoration: none;
            transition: color 0.3s;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .footer-link a:hover {
            color: var(--bg-white);
        }
        
        .footer-link a i {
            font-size: 0.875rem;
        }
        
        .footer-bottom {
            text-align: center;
            padding-top: 2rem;
            border-top: 1px solid #374151;
            color: #9ca3af;
        }
        
        /* Media Queries */
        @media (max-width: 768px) {
            .header-container {
                padding: 0.75rem 1rem;
            }
            
            .nav-links {
                display: none;
            }
            
            .hero {
                padding: 7rem 1rem 4rem;
            }
            
            .hero-container {
                padding: 2rem 1rem;
            }
            
            .hero-title {
                font-size: 1.75rem;
            }
            
            .hero-buttons {
                flex-direction: column;
                align-items: center;
                gap: 0.75rem;
            }
            
            .about-container {
                flex-direction: column;
                padding: 1.5rem;
            }
            
            .about-image {
                order: 1;
            }
            
            .about-content {
                order: 2;
            }
            
            .step {
                flex-direction: column !important;
                text-align: center;
                gap: 1rem;
                padding: 1.5rem 1rem;
            }
        }
    </style>
</head>
<body>
    <!-- Header and Navigation - Updated to match dashboard -->
    <header class="header">
        <div class="header-container">
            <a href="#" class="logo">
                <i class="ri-computer-line"></i>
                <span>CCS Sit-In</span>
            </a>
            
            <nav class="nav-links">
                <a href="#features" class="nav-link">
                    <i class="ri-layout-grid-line"></i>
                    <span>Features</span>
                </a>
                <a href="#how-it-works" class="nav-link">
                    <i class="ri-question-line"></i>
                    <span>How It Works</span>
                </a>
                <a href="#about" class="nav-link">
                    <i class="ri-information-line"></i>
                    <span>About</span>
                </a>
            </nav>
            
            <div class="auth-buttons">
                <a href="auth/login.php" class="btn btn-outline">
                    <i class="ri-login-box-line"></i>
                    <span>Login</span>
                </a>
                <a href="auth/register.php" class="btn btn-primary">
                    <i class="ri-user-add-line"></i>
                    <span>Register</span>
                </a>
            </div>
        </div>
    </header>
    
    <!-- Hero Section - Updated with card style -->
    <section class="hero">
        <div class="hero-container">
            <h1 class="hero-title">Welcome to <span>CCS Sit-In</span> Monitoring System</h1>
            <p class="hero-subtitle">A seamless way to manage and track computer laboratory usage for students. Reserve your spot, track your time, and enhance your academic experience.</p>
            <div class="hero-buttons">
                <a href="auth/login.php" class="btn btn-primary">
                    <i class="ri-login-box-line"></i>
                    <span>Student Login</span>
                </a>
                <a href="auth/register.php" class="btn btn-outline">
                    <i class="ri-user-add-line"></i>
                    <span>Create Account</span>
                </a>
            </div>
        </div>
    </section>
    
    <!-- Features Section - Updated with consistent styling -->
    <section class="features" id="features">
        <h2 class="section-title">Why Choose <span>CCS Sit-In</span></h2>
        <div class="features-container">
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="ri-calendar-check-line"></i>
                </div>
                <h3 class="feature-title">Easy Reservations</h3>
                <p class="feature-description">Book your laboratory sessions in advance with our user-friendly reservation system. Secure your spot for study sessions, project work, or research.</p>
            </div>
            
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="ri-time-line"></i>
                </div>
                <h3 class="feature-title">Time Tracking</h3>
                <p class="feature-description">Monitor your laboratory usage time effectively. Our system automatically tracks check-in and check-out times for better time management.</p>
            </div>
            
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="ri-notification-4-line"></i>
                </div>
                <h3 class="feature-title">Instant Updates</h3>
                <p class="feature-description">Stay informed with real-time notifications about your reservations, administrative announcements, and laboratory availability.</p>
            </div>
        </div>
    </section>
    
    <!-- How It Works Section - Updated with dashboard-like styling -->
    <section class="how-it-works" id="how-it-works">
        <h2 class="section-title">How It <span>Works</span></h2>
        <div class="steps-container">
            <div class="step">
                <div class="step-number">1</div>
                <div class="step-content">
                    <h3 class="step-title">Create an Account</h3>
                    <p class="step-description">Register with your student information to access the CCS Sit-In system. Simply provide your basic details, and you're ready to go.</p>
                </div>
            </div>
            
            <div class="step">
                <div class="step-number">2</div>
                <div class="step-content">
                    <h3 class="step-title">Make a Reservation</h3>
                    <p class="step-description">Book your laboratory session in advance by selecting your preferred date and time, then state your purpose for laboratory use.</p>
                </div>
            </div>
            
            <div class="step">
                <div class="step-number">3</div>
                <div class="step-content">
                    <h3 class="step-title">Check-In at the Lab</h3>
                    <p class="step-description">When you arrive, simply check in through the system. The administrator will verify your reservation and record your entry.</p>
                </div>
            </div>
            
            <div class="step">
                <div class="step-number">4</div>
                <div class="step-content">
                    <h3 class="step-title">Check-Out When Done</h3>
                    <p class="step-description">Once you've completed your work, check out through the system to record your usage duration and free up space for other students.</p>
                </div>
            </div>
        </div>
    </section>
    
    <!-- About Section - Updated with dashboard card styling -->
    <section class="about" id="about">
        <div class="about-container">
            <div class="about-image">
                <img src="assets/images/lab-image.jpg" alt="Computer Laboratory" 
                     onerror="this.src='https://via.placeholder.com/450x300?text=CCS+Computer+Laboratory'">
            </div>
            <div class="about-content">
                <h2 class="about-title">About CCS Sit-In System</h2>
                <p class="about-description">
                    The College of Computer Studies (CCS) Sit-In Monitoring System streamlines the process of 
                    laboratory usage tracking for both students and administrators. Our goal is to maximize 
                    laboratory resources by ensuring proper scheduling, monitoring, and utilization.
                </p>
                <p class="about-description">
                    With features like real-time availability tracking, purpose documentation, and usage analytics, 
                    we aim to improve the overall laboratory experience for the entire CCS community.
                </p>
                <a href="auth/login.php" class="btn btn-primary">
                    <i class="ri-arrow-right-line"></i>
                    <span>Get Started</span>
                </a>
            </div>
        </div>
    </section>
    
    <!-- Footer - Updated with more consistent styling -->
    <footer class="footer">
        <div class="footer-container">
            <div class="footer-top">
                <div>
                    <div class="footer-logo">
                        <i class="ri-computer-line"></i>
                        <span>CCS Sit-In</span>
                    </div>
                    <p class="footer-tagline">Streamlining laboratory access and usage monitoring for a better academic experience.</p>
                </div>
                
                <div>
                    <h3 class="footer-links-title">Quick Links</h3>
                    <ul class="footer-links">
                        <li class="footer-link"><a href="#features"><i class="ri-arrow-right-s-line"></i> Features</a></li>
                        <li class="footer-link"><a href="#how-it-works"><i class="ri-arrow-right-s-line"></i> How It Works</a></li>
                        <li class="footer-link"><a href="#about"><i class="ri-arrow-right-s-line"></i> About</a></li>
                    </ul>
                </div>
                
                <div>
                    <h3 class="footer-links-title">Resources</h3>
                    <ul class="footer-links">
                        <li class="footer-link"><a href="#"><i class="ri-arrow-right-s-line"></i> Help Center</a></li>
                        <li class="footer-link"><a href="#"><i class="ri-arrow-right-s-line"></i> Lab Guidelines</a></li>
                        <li class="footer-link"><a href="#"><i class="ri-arrow-right-s-line"></i> Privacy Policy</a></li>
                    </ul>
                </div>
                
                <div>
                    <h3 class="footer-links-title">Get Started</h3>
                    <ul class="footer-links">
                        <li class="footer-link"><a href="auth/login.php"><i class="ri-login-box-line"></i> Login</a></li>
                        <li class="footer-link"><a href="auth/register.php"><i class="ri-user-add-line"></i> Register</a></li>
                        <li class="footer-link"><a href="auth/admin-login.php"><i class="ri-admin-line"></i> Admin Access</a></li>
                    </ul>
                </div>
            </div>
            
            <div class="footer-bottom">
                <p>&copy; <?php echo date('Y'); ?> CCS Sit-In Monitoring System. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <!-- Notification Container - Matching dashboard notifications -->
    <div id="notification-container"></div>

    <!-- Smooth scroll functionality -->
    <script>
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
