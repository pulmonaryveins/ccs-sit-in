<?php
session_start();

// If user is already logged in, redirect to dashboard
if (isset($_SESSION['username'])) {
    header("Location: view/dashboard.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CCS Sit-In System | University of Cebu</title>
    <link rel="icon" href="assets/images/logo/ccs.png" type="image/png">
    <link rel="stylesheet" href="assets/css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/remixicon/4.6.0/remixicon.css">
    <style>
        :root {
            --primary-color: #7556cc;
            --secondary-color: #9556cc;
            --accent-color: #6200ea;
            --dark-color: #1e293b;
            --light-color: #f8fafc;
            --text-color: #334155;
            --light-text: #64748b;
            --bg-color: #f1f5f9;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Inter', 'Segoe UI', sans-serif;
        }

        body {
            background-color: var(--bg-color);
            color: var(--text-color);
            line-height: 1.6;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 1.5rem;
        }

        /* Hero Section */
        .hero {
            position: relative;
            min-height: 550px;
            background: linear-gradient(135deg, #7556cc 0%, #9556cc 100%);
            padding: 80px 0 60px;
            overflow: hidden;
            color: white;
        }

        .hero-pattern {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.1'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
            opacity: 0.5;
        }

        .hero-content {
            position: relative;
            z-index: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            max-width: 800px;
            margin: 0 auto;
        }

        .logos-container {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .hero-logo {
            width: 100px;
            height: 100px;
            background-color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 10px 25px rgba(0,0,0,0.2);
        }

        .hero-logo img {
            width: 65px;
            height: auto;
        }

        .hero h1 {
            font-size: 3rem;
            font-weight: 800;
            margin-bottom: 1rem;
            background: linear-gradient(to right, #ffffff, #f0f0f0);
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
            text-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .hero h2 {
            font-size: 1.25rem;
            font-weight: 400;
            margin-bottom: 2rem;
            max-width: 600px;
            opacity: 0.9;
        }

        .cta-buttons {
            display: flex;
            gap: 1rem;
            margin-top: 1rem;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s ease;
            text-decoration: none;
            font-size: 1rem;
            cursor: pointer;
        }

        .btn-primary {
            background-color: white;
            color: var(--primary-color);
            border: none;
            box-shadow: 0 4px 14px rgba(0,0,0,0.15);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0,0,0,0.2);
        }

        .btn-secondary {
            background-color: rgba(255,255,255,0.1);
            color: white;
            border: 1px solid rgba(255,255,255,0.3);
            backdrop-filter: blur(5px);
        }

        .btn-secondary:hover {
            background-color: rgba(255,255,255,0.2);
            transform: translateY(-2px);
        }

        .btn i {
            margin-right: 0.5rem;
            font-size: 1.1rem;
        }

        /* Features section */
        .features {
            padding: 5rem 0;
        }

        .section-header {
            text-align: center;
            margin-bottom: 3.5rem;
        }

        .section-header h2 {
            font-size: 2.25rem;
            color: var( --primary-color);
            margin-bottom: 1rem;
            font-weight: 700;
        }

        .section-header p {
            font-size: 1.1rem;
            color: var(--light-text);
            max-width: 700px;
            margin: 0 auto;
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 2.5rem;
        }

        .feature-card {
            display: flex;
            align-items: flex-start;
            background: white;
            border-radius: 12px;
            padding: 1.25rem;
            border: 1px solid #e2e8f0;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .feature-card::after {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 5px;
            height: 100%;
            background: linear-gradient(135deg, #7556cc 0%, #9556cc 100%);
            transform: translateX(5px);
            transition: transform 0.3s ease;
        }

        .feature-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.08);
        }

        .feature-card:hover::after {
            transform: translateX(0);
        }

        .feature-icon {
            width: 50px;
            height: 50px;
            min-width: 50px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(117, 86, 204, 0.1);
            border-radius: 12px;
            margin-right: 1rem;
            color: var(--primary-color);
            font-size: 1.5rem;
            transition: all 0.3s ease;
        }

        .feature-card:hover .feature-icon {
            background: #7556cc;
            color: white;
        }

        .feature-content {
            flex: 1;
        }

        .feature-content h3 {
            font-size: 1.1rem;
            color: var(--dark-color);
            margin: 0 0 8px 0;
            font-weight: 600;
            transition: color 0.3s ease;
        }

        .feature-content p {
            color: var(--light-text);
            font-size: 0.95rem;
            margin: 0;
            line-height: 1.5;
        }

        .feature-card:hover .feature-content h3 {
            color: #7556cc;
        }

        /* How it works section */
        .how-it-works {
            padding: 5rem 0;
            background-color: white;
        }

        .steps {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 2rem;
            margin-top: 3rem;
        }

        .step {
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            max-width: 300px;
            padding: 1.5rem;
        }

        .step-number {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            font-weight: 600;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1.25rem;
            font-size: 1.25rem;
            box-shadow: 0 5px 15px rgba(117, 86, 204, 0.3);
        }

        .step h3 {
            font-size: 1.25rem;
            color: var(--dark-color);
            margin-bottom: 0.75rem;
            font-weight: 600;
        }

        .step p {
            color: var(--light-text);
            line-height: 1.6;
        }

        /* About section */
        .about {
            padding: 5rem 0;
            background-color: #f8fafc;
            position: relative;
            overflow: hidden;
        }

        .about-content {
            position: relative;
            z-index: 1;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
            margin-top: 3rem;
            max-width: 1200px;
            margin-left: auto;
            margin-right: auto;
            justify-content: center; /* Center the cards horizontally */
        }

        @media (max-width: 768px) {
            .about-content {
                grid-template-columns: 1fr;
                justify-content: center; /* Ensure cards remain centered on smaller screens */
            }
        }

        .about-card {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0,0,0,0.05);
            transition: all 0.3s ease;
            border: 1px solid #e2e8f0;
            height: 100%;
            display: flex;
            flex-direction: column;
            position: relative;
        }

        .about-card::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 5px;
            background: linear-gradient(135deg, #7556cc 0%, #9556cc 100%);
        }

        .about-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 25px rgba(0,0,0,0.1);
        }

        .about-card-header {
            padding: 1.5rem;
            border-bottom: 1px solid #f1f5f9;
            display: flex;
            align-items: center;
        }

        .about-card-icon {
            width: 50px;
            height: 50px;
            border-radius: 10px;
            background: rgba(117, 86, 204, 0.1);
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 1rem;
            color: var(--primary-color);
            font-size: 1.5rem;
        }

        .about-card-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--primary-color);
            margin: 0;
        }

        .about-card-body {
            padding: 1.5rem;
            flex-grow: 1;
        }

        .about-card-body p {
            color: var(--text-color);
            font-size: 1rem;
            line-height: 1.7;
            margin-bottom: 1rem;
        }

        .about-card-body p:last-child {
            margin-bottom: 0;
        }

        .about-main {
            grid-column: 1 / -1;
            text-align: center;
            max-width: 800px;
            margin: 0 auto 2rem;
        }

        .about-main p {
            color: var(--text-color);
            font-size: 1.1rem;
            line-height: 1.7;
        }

        @media (max-width: 768px) {
            .about-content {
                grid-template-columns: 1fr;
            }
        }

        /* Call to action section */
        .cta {
            padding: 5rem 0;
            background: linear-gradient(135deg, #7556cc 0%, #9556cc 100%);
            color: white;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .cta-pattern {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.1'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
            opacity: 0.5;
        }

        .cta-content {
            position: relative;
            max-width: 700px;
            margin: 0 auto;
            z-index: 1;
        }

        .cta h2 {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 1.5rem;
        }

        .cta p {
            font-size: 1.2rem;
            margin-bottom: 2rem;
            opacity: 0.9;
        }

        /* Footer */
        .footer {
            background: linear-gradient(135deg, #7556cc 0%, #9556cc 100%);
            color: #cbd5e1;
            padding: 4rem 0 2rem;
            position: relative;
            overflow: hidden;
        }

        .footer-pattern {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.05'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
            opacity: 0.5;
        }

        .footer-content {
            position: relative;
            z-index: 1;
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            gap: 2rem;
            margin-bottom: 3rem;
        }

        .footer-info {
            flex: 1;
            min-width: 300px;
        }

        .footer-logo {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin-bottom: 1.25rem;
        }

        .footer-logo img {
            width: 45px;
            height: auto;
        }

        .footer-logo span {
            font-size: 1.5rem;
            font-weight: 700;
            color: white;
        }

        .footer-info p {
            color: #cbd5e1;
            line-height: 1.7;
            margin-bottom: 1.5rem;
        }

        .social-links {
            display: flex;
            gap: 1rem;
        }

        .social-links a {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: rgba(255,255,255,0.1);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
            text-decoration: none;
        }

        .social-links a:hover {
            background-color: var(--primary-color);
            transform: translateY(-3px);
        }

        .footer-links {
            display: flex;
            flex-wrap: wrap;
            gap: 2.5rem;
        }

        .footer-links-group h3 {
            color: white;
            font-size: 1.15rem;
            font-weight: 600;
            margin-bottom: 1.25rem;
        }

        .footer-links-group ul {
            list-style: none;
            padding: 0;
        }

        .footer-links-group ul li {
            margin-bottom: 0.75rem;
        }

        .footer-links-group ul li a {
            color: #cbd5e1;
            text-decoration: none;
            transition: color 0.3s ease;
        }

        .footer-links-group ul li a:hover {
            color: white;
        }

        .footer-bottom {
            border-top: 1px solid rgba(255, 255, 255, 0.23);
            padding-top: 2rem;
            text-align: center;
        }

        .footer-bottom p {
            color: #cbd5e1;
            font-size: 0.95rem;
        }

        /* Responsive */
        @media (max-width: 992px) {
            .hero h1 {
                font-size: 2.5rem;
            }
            
            .about-content {
                flex-direction: column;
            }
        }

        @media (max-width: 768px) {
            .hero {
                min-height: 500px;
                padding: 60px 0 40px;
            }
            
            .hero h1 {
                font-size: 2.25rem;
            }
            
            .hero h2 {
                font-size: 1.15rem;
            }
            
            .section-header h2 {
                font-size: 2rem;
            }
            
            .cta-buttons {
                flex-direction: column;
                width: 100%;
                max-width: 300px;
            }
            
            .btn {
                width: 100%;
            }
            
            .cta h2 {
                font-size: 2rem;
            }
            
            .step {
                max-width: 100%;
            }
        }

        @media (max-width: 576px) {
            .hero-logo {
                width: 100px;
                height: 100px;
            }
            
            .hero-logo img {
                width: 70px;
            }
            
            .hero h1 {
                font-size: 2rem;
            }
            
            .section-header h2 {
                font-size: 1.75rem;
            }
            
            .features {
                padding: 3rem 0;
            }
            
            .how-it-works, .about, .cta {
                padding: 3rem 0;
            }
        }

        /* Animations */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .animate-fade-up {
            opacity: 0;
            animation: fadeInUp 0.8s ease-out forwards;
        }

        .delay-1 {
            animation-delay: 0.1s;
        }

        .delay-2 {
            animation-delay: 0.3s;
        }

        .delay-3 {
            animation-delay: 0.5s;
        }

        .delay-4 {
            animation-delay: 0.7s;
        }
    </style>
</head>
<body>
    <!-- Hero Section -->
    <section class="hero">
        <div class="hero-pattern"></div>
        <div class="container">
            <div class="hero-content">
                <div class="logos-container animate-fade-up">
                    <div class="hero-logo">
                        <img src="assets/images/logo/uc.png" alt="UC Logo">
                    </div>
                    <div class="hero-logo">
                        <img src="assets/images/logo/ccs.png" alt="CCS Logo">
                    </div>
                </div>
                <h1 class="animate-fade-up delay-1">CCS SIT-IN MONITORING SYSTEM</h1>
                <h2 class="animate-fade-up delay-2">A modern platform for managing laboratory reservations at the College of Computer Studies</h2>
                <div class="cta-buttons animate-fade-up delay-3">
                    <a href="auth/login.php" class="btn btn-primary">
                        <i class="ri-login-circle-line"></i> Login
                    </a>
                    <a href="auth/register.php" class="btn btn-secondary">
                        <i class="ri-user-add-line"></i> Register
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="features">
        <div class="container">
            <div class="section-header">
                <h2>Key Features</h2>
                <p>Our system provides everything you need to manage your laboratory sessions efficiently</p>
            </div>
            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="ri-calendar-check-line"></i>
                    </div>
                    <div class="feature-content">
                        <h3>Easy Reservations</h3>
                        <p>Reserve laboratory sessions with just a few clicks. Select your preferred time slot and laboratory room.</p>
                    </div>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="ri-history-line"></i>
                    </div>
                    <div class="feature-content">
                        <h3>Session History</h3>
                        <p>Track your complete history of laboratory usage and reservations for better planning.</p>
                    </div>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="ri-computer-line"></i>
                    </div>
                    <div class="feature-content">
                        <h3>Lab Availability</h3>
                        <p>Check real-time availability of laboratories and computer systems before making reservations.</p>
                    </div>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="ri-profile-line"></i>
                    </div>
                    <div class="feature-content">
                        <h3>Student Dashboard</h3>
                        <p>Access a personalized dashboard showing your session statistics and upcoming reservations.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- How It Works Section -->
    <section class="how-it-works">
        <div class="container">
            <div class="section-header">
                <h2>How It Works</h2>
                <p>Getting started with the CCS Sit-In System is quick and easy</p>
            </div>
            <div class="steps">
                <div class="step">
                    <div class="step-number">1</div>
                    <h3>Create an Account</h3>
                    <p>Register with your student details and verification information to join the system.</p>
                </div>
                <div class="step">
                    <div class="step-number">2</div>
                    <h3>Choose a Laboratory</h3>
                    <p>Browse available laboratories and select one that meets your requirements.</p>
                </div>
                <div class="step">
                    <div class="step-number">3</div>
                    <h3>Reserve a Time Slot</h3>
                    <p>Pick an available time slot that fits your schedule for the laboratory session.</p>
                </div>
                <div class="step">
                    <div class="step-number">4</div>
                    <h3>Receive Confirmation</h3>
                    <p>Get instant confirmation and reminder notifications about your reservation.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- About Section -->
    <section class="about">
        <div class="about-pattern"></div>
        <div class="container">
            <div class="section-header">
                <h2>About CCS Sit-In System</h2>
                <p>Learn more about our system and its purpose</p>
            </div>
            <div class="about-content">
                <div class="about-main">
                    <p>The CCS Sit-In System was developed to streamline the process of laboratory reservations for the College of Computer Studies at University of Cebu. Our goal is to provide students with easy access to computing resources while maintaining efficient laboratory management.</p>
                </div>
                <div class="about-card">
                    <div class="about-card-header">
                        <div class="about-card-icon">
                            <i class="ri-database-2-line"></i>
                        </div>
                        <h3 class="about-card-title">Resource Management</h3>
                    </div>
                    <div class="about-card-body">
                        <p>Our system efficiently allocates laboratory resources, ensuring that all students have fair and equal access to computing facilities when they need them most.</p>
                        <p>By optimizing laboratory usage and scheduling, we maximize the availability of resources to support your educational needs.</p>
                    </div>
                </div>
                <div class="about-card">
                    <div class="about-card-header">
                        <div class="about-card-icon">
                            <i class="ri-user-settings-line"></i>
                        </div>
                        <h3 class="about-card-title">Student-Centered</h3>
                    </div>
                    <div class="about-card-body">
                        <p>Designed with students in mind, our platform focuses on providing a seamless experience that supports both educational requirements and practical skill development.</p>
                        <p>Every feature is built to enhance your learning journey and help you make the most of laboratory resources.</p>
                    </div>
                </div>
                <div class="about-card">
                    <div class="about-card-header">
                        <div class="about-card-icon">
                            <i class="ri-shield-check-line"></i>
                        </div>
                        <h3 class="about-card-title">Reliability & Security</h3>
                    </div>
                    <div class="about-card-body">
                        <p>Our secure platform ensures that your data and reservations are protected while providing consistent and reliable access to laboratory sessions.</p>
                        <p>Built with modern technologies, the system maintains high standards of performance and data integrity.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="footer-pattern"></div>
        <div class="container">
            <div class="footer-content">
                <div class="footer-info">
                    <div class="footer-logo">
                        <span>CCS SIT-IN MONITORING SYSTEM</span>
                    </div>
                    <p>A modern laboratory reservation system for the College of Computer Studies at University of Cebu.</p>
                    <div class="social-links">
                        <a href="#"><i class="ri-facebook-fill"></i></a>
                        <a href="#"><i class="ri-twitter-fill"></i></a>
                        <a href="#"><i class="ri-instagram-line"></i></a>
                        <a href="#"><i class="ri-linkedin-fill"></i></a>
                    </div>
                </div>
                <div class="footer-links">
                    <div class="footer-links-group">
                        <h3>Quick Links</h3>
                        <ul>
                            <li><a href="auth/login.php">Login</a></li>
                            <li><a href="auth/register.php">Register</a></li>
                            <li><a href="#">About Us</a></li>
                            <li><a href="#">Contact</a></li>
                        </ul>
                    </div>
                    <div class="footer-links-group">
                        <h3>Resources</h3>
                        <ul>
                            <li><a href="#">User Guide</a></li>
                            <li><a href="#">FAQ</a></li>
                            <li><a href="#">Lab Rules</a></li>
                            <li><a href="#">Privacy Policy</a></li>
                        </ul>
                    </div>
                    <div class="footer-links-group">
                        <h3>Contact</h3>
                        <ul>
                            <li><a href="#">Help Desk</a></li>
                            <li><a href="#">Report Issues</a></li>
                            <li><a href="#">Feedback</a></li>
                            <li><a href="#">Support</a></li>
                        </ul>
                    </div>
                </div>
            <div class="footer-bottom">
                <p>&copy; <?php echo date('Y'); ?> University of Cebu - College of Computer Studies. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Intersection Observer to trigger animations when elements come into view
            const observerOptions = {
                threshold: 0.1,
                rootMargin: '0px 0px -50px 0px'
            };
            
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('animate-fade-up');
                        observer.unobserve(entry.target);
                    }
                });
            }, observerOptions);
            
            // Observe feature cards, steps, and other elements
            document.querySelectorAll('.feature-card, .step, .about-card').forEach(el => {
                observer.observe(el);
            });
        });
    </script>
</body>
</html>
