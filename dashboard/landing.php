<?php
session_start();
require_once '../db.php';

// Fetch popular regions
$regions = $pdo->query("SELECT region, COUNT(*) as count FROM user_regions GROUP BY region ORDER BY count DESC LIMIT 10")->fetchAll();

// Fetch user's previous trips if logged in
$previous_trips = [];
if (isset($_SESSION['user_id'])) {
    $stmt = $pdo->prepare("SELECT * FROM trips WHERE user_id = ? AND status = 'completed' ORDER BY end_date DESC LIMIT 5");
    $stmt->execute([$_SESSION['user_id']]);
    $previous_trips = $stmt->fetchAll();
}

// Fetch trending activities
$activities = $pdo->query("SELECT * FROM activities ORDER BY popularity DESC LIMIT 6")->fetchAll();

// Enhanced image arrays with more variety
$destination_images = [
    'sightseeing' => 'https://images.unsplash.com/photo-1523531294919-4bcd7c65e216?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80',
    'adventure' => 'https://images.unsplash.com/photo-1506905925346-21bda4d32df4?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80',
    'food' => 'https://images.unsplash.com/photo-1517248135467-4c7edcad34c4?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80',
    'culture' => 'https://images.unsplash.com/photo-1527838832700-5059252407fa?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80',
    'shopping' => 'https://images.unsplash.com/photo-1441986300917-64674bd600d8?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80',
    'relaxation' => 'https://images.unsplash.com/photo-1544551763-46a013bb70d5?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80',
    'beach' => 'https://images.unsplash.com/photo-1507525428034-b723cf961d3e?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80',
    'mountain' => 'https://images.unsplash.com/photo-1464822759023-fed622ff2c3b?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80',
    'city' => 'https://images.unsplash.com/photo-1519501025264-65ba15a82390?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80',
    'nature' => 'https://images.unsplash.com/photo-1501854140801-50d01698950b?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80'
];

$banner_images = [
    'https://images.unsplash.com/photo-1469474968028-56623f02e42e?ixlib=rb-4.0.3&auto=format&fit=crop&w=1600&q=80',
    'https://images.unsplash.com/photo-1506929562872-bb421503ef21?ixlib=rb-4.0.3&auto=format&fit=crop&w=1600&q=80',
    'https://images.unsplash.com/photo-1465146344425-f00d5f5c8f07?ixlib=rb-4.0.3&auto=format&fit=crop&w=1600&q=80',
    'https://images.unsplash.com/photo-1439066615861-d1af74d74000?ixlib=rb-4.0.3&auto=format&fit=crop&w=1600&q=80'
];

$profile_image = isset($_SESSION['user_id']) ? 
    'https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?ixlib=rb-4.0.3&auto=format&fit=crop&w=400&q=80' : 
    'https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?ixlib=rb-4.0.3&auto=format&fit=crop&w-400&q=80';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | Globetrotter ✈️</title>
    <link rel="stylesheet" href="../assets/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <style>
        :root {
            --primary-blue: #2563eb;
            --blue-dark: #1d4ed8;
            --blue-light: #3b82f6;
            --blue-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --blue-bg: rgba(37, 99, 235, 0.1);
            --blue-border: rgba(37, 99, 235, 0.2);
            --text-dark: #1e293b;
            --text-light: #64748b;
            --white: #ffffff;
            --gray-light: #f8fafc;
            --gray-border: #e2e8f0;
            --success: #10b981;
            --error: #ef4444;
            --warning: #f59e0b;
            --purple: #8b5cf6;
            --pink: #ec4899;
            --card-shadow: 0 10px 40px -20px rgba(0, 0, 0, 0.15);
            --card-shadow-hover: 0 30px 60px -30px rgba(0, 0, 0, 0.25);
            --glass-bg: rgba(255, 255, 255, 0.9);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
        }

        body {
            background: linear-gradient(135deg, #f0f7ff 0%, #f8fafc 100%);
            color: var(--text-dark);
            line-height: 1.5;
            min-height: 100vh;
            overflow-x: hidden;
        }

        /* Animated Background Elements */
        .floating-elements {
            position: fixed;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: -1;
        }

        .floating-circle {
            position: absolute;
            border-radius: 50%;
            background: var(--blue-gradient);
            opacity: 0.1;
            animation: float 20s infinite linear;
        }

        .floating-circle:nth-child(1) {
            width: 300px;
            height: 300px;
            top: -150px;
            right: -150px;
            animation-delay: 0s;
        }

        .floating-circle:nth-child(2) {
            width: 200px;
            height: 200px;
            bottom: -100px;
            left: -100px;
            animation-delay: 10s;
        }

        @keyframes float {
            0%, 100% { transform: translate(0, 0) rotate(0deg); }
            33% { transform: translate(30px, -30px) rotate(120deg); }
            66% { transform: translate(-20px, 20px) rotate(240deg); }
        }

        /* Header Styles */
        header {
            background: var(--glass-bg);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
            position: sticky;
            top: 0;
            z-index: 1000;
            box-shadow: 0 4px 30px rgba(0, 0, 0, 0.05);
        }

        nav {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 32px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            height: 80px;
        }

        .logo {
            font-size: 1.75rem;
            font-weight: 900;
            background: var(--blue-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            display: flex;
            align-items: center;
            gap: 12px;
            cursor: pointer;
            transition: transform 0.3s ease;
        }

        .logo:hover {
            transform: scale(1.05);
        }

        .logo-icon {
            font-size: 2rem;
            animation: bounce 2s infinite;
        }

        @keyframes bounce {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-5px); }
        }

        .nav-links {
            display: flex;
            gap: 40px;
            align-items: center;
        }

        .nav-links a {
            text-decoration: none;
            color: var(--text-dark);
            font-weight: 600;
            font-size: 0.9375rem;
            padding: 8px 0;
            position: relative;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .nav-links a:hover {
            color: var(--primary-blue);
            transform: translateY(-2px);
        }

        .nav-links a.active {
            color: var(--primary-blue);
        }

        .nav-links a.active::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 0;
            width: 100%;
            height: 3px;
            background: var(--blue-gradient);
            border-radius: 2px;
            animation: underline 0.3s ease;
        }

        @keyframes underline {
            from { width: 0; }
            to { width: 100%; }
        }

        .user-profile {
            display: flex;
            align-items: center;
            gap: 12px;
            cursor: pointer;
            padding: 8px 16px;
            border-radius: 50px;
            background: var(--blue-bg);
            transition: all 0.3s ease;
        }

        .user-profile:hover {
            background: var(--blue-border);
            transform: translateY(-2px);
        }

        .profile-img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid white;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .username {
            font-weight: 600;
            color: var(--text-dark);
        }

        /* Main Content */
        .landing-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 32px 80px;
        }

        /* Animated Hero Banner */
        .hero-banner {
            position: relative;
            height: 500px;
            border-radius: 24px;
            overflow: hidden;
            margin: 32px 0 60px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15);
            animation: fadeInUp 1s ease;
        }

        .banner-slider {
            width: 100%;
            height: 100%;
            position: relative;
        }

        .banner-slide {
            position: absolute;
            width: 100%;
            height: 100%;
            background-size: cover;
            background-position: center;
            opacity: 0;
            transition: opacity 1.5s ease;
        }

        .banner-slide.active {
            opacity: 1;
        }

        .hero-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(135deg, rgba(0, 0, 0, 0.6) 0%, rgba(0, 0, 0, 0.3) 100%);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .hero-content {
            text-align: center;
            color: white;
            padding: 0 32px;
            max-width: 800px;
            z-index: 2;
        }

        .hero-content h1 {
            font-size: 3.5rem;
            font-weight: 900;
            margin-bottom: 20px;
            line-height: 1.1;
            text-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
            animation: slideInUp 1s ease 0.2s both;
        }

        .hero-content p {
            font-size: 1.25rem;
            opacity: 0.9;
            margin-bottom: 40px;
            animation: slideInUp 1s ease 0.4s both;
        }

        /* Search Section */
        .search-section {
            background: var(--glass-bg);
            backdrop-filter: blur(10px);
            border-radius: 24px;
            padding: 40px;
            margin-bottom: 60px;
            box-shadow: var(--card-shadow);
            border: 1px solid rgba(255, 255, 255, 0.3);
            animation: fadeIn 1s ease 0.6s both;
            position: relative;
            overflow: hidden;
        }

        .search-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 5px;
            background: var(--blue-gradient);
        }

        .search-box {
            display: flex;
            gap: 16px;
            margin-bottom: 32px;
            position: relative;
        }

        .search-box input {
            flex: 1;
            padding: 20px 24px;
            border: 2px solid transparent;
            border-radius: 16px;
            font-size: 1.125rem;
            transition: all 0.3s ease;
            background: white;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        }

        .search-box input:focus {
            outline: none;
            border-color: var(--primary-blue);
            box-shadow: 0 4px 30px rgba(37, 99, 235, 0.2);
            transform: translateY(-2px);
        }

        .search-icon {
            position: absolute;
            right: 140px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-light);
            font-size: 1.25rem;
        }

        .search-box button {
            padding: 0 40px;
            background: var(--blue-gradient);
            color: white;
            border: none;
            border-radius: 16px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 1.125rem;
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);
            position: relative;
            overflow: hidden;
        }

        .search-box button:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 40px rgba(102, 126, 234, 0.4);
        }

        .search-box button::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 5px;
            height: 5px;
            background: rgba(255, 255, 255, 0.5);
            opacity: 0;
            border-radius: 100%;
            transform: scale(1, 1) translate(-50%);
            transform-origin: 50% 50%;
        }

        .search-box button:active::after {
            animation: ripple 1s ease-out;
        }

        .filters {
            display: flex;
            gap: 24px;
            flex-wrap: wrap;
        }

        .filter-group {
            flex: 1;
            min-width: 200px;
        }

        .filter-group label {
            display: block;
            margin-bottom: 12px;
            font-weight: 600;
            color: var(--text-dark);
            font-size: 0.875rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .filter-group select {
            width: 100%;
            padding: 16px 20px;
            border: 2px solid var(--gray-border);
            border-radius: 12px;
            font-size: 0.9375rem;
            background: white;
            cursor: pointer;
            transition: all 0.3s ease;
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='24' height='24' viewBox='0 0 24 24' fill='none' stroke='%2364748b' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 16px center;
            background-size: 16px;
        }

        .filter-group select:focus {
            outline: none;
            border-color: var(--primary-blue);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
            transform: translateY(-2px);
        }

        /* Section Styles */
        .section-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 32px;
            animation: fadeIn 0.8s ease;
        }

        .section-header h2 {
            font-size: 2rem;
            font-weight: 800;
            color: var(--text-dark);
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .section-header h2 i {
            width: 50px;
            height: 50px;
            background: var(--blue-gradient);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.25rem;
        }

        .view-all {
            color: var(--primary-blue);
            text-decoration: none;
            font-weight: 600;
            font-size: 0.9375rem;
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 12px 24px;
            border-radius: 50px;
            background: var(--blue-bg);
            transition: all 0.3s ease;
        }

        .view-all:hover {
            background: var(--blue-border);
            transform: translateX(5px);
        }

        /* Trip Grid */
        .trip-grid, .activity-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 32px;
        }

        .trip-card, .activity-card {
            background: white;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: var(--card-shadow);
            border: 1px solid rgba(255, 255, 255, 0.5);
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            cursor: pointer;
            animation: fadeInUp 0.6s ease;
            position: relative;
            backdrop-filter: blur(10px);
        }

        .trip-card:hover, .activity-card:hover {
            transform: translateY(-10px) scale(1.02);
            box-shadow: var(--card-shadow-hover);
        }

        .trip-card::before, .activity-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 5px;
            background: var(--blue-gradient);
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .trip-card:hover::before, .activity-card:hover::before {
            opacity: 1;
        }

        .trip-card img, .activity-card img {
            width: 100%;
            height: 220px;
            object-fit: cover;
            transition: transform 0.6s ease;
        }

        .trip-card:hover img, .activity-card:hover img {
            transform: scale(1.1);
        }

        .trip-card-content, .activity-card-content {
            padding: 24px;
            position: relative;
        }

        .trip-card h3, .activity-card h4 {
            font-size: 1.25rem;
            font-weight: 700;
            margin-bottom: 12px;
            color: var(--text-dark);
            line-height: 1.3;
        }

        .trip-card p, .activity-card p {
            font-size: 0.9375rem;
            color: var(--text-light);
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .trip-card .date {
            display: inline-block;
            background: var(--blue-gradient);
            color: white;
            padding: 6px 16px;
            border-radius: 50px;
            font-size: 0.875rem;
            font-weight: 600;
            margin-top: 12px;
            transition: all 0.3s ease;
        }

        .activity-card .activity-type {
            display: inline-block;
            background: var(--blue-bg);
            color: var(--primary-blue);
            padding: 6px 16px;
            border-radius: 50px;
            font-size: 0.875rem;
            font-weight: 600;
            margin-top: 12px;
        }

        /* Stats Cards */
        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 24px;
            margin: 40px 0;
        }

        .stat-card {
            background: var(--glass-bg);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 32px;
            text-align: center;
            border: 1px solid rgba(255, 255, 255, 0.3);
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--card-shadow);
            border-color: var(--blue-border);
        }

        .stat-icon {
            width: 70px;
            height: 70px;
            background: var(--blue-gradient);
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            color: white;
            font-size: 1.75rem;
            transition: transform 0.3s ease;
        }

        .stat-card:hover .stat-icon {
            transform: scale(1.1) rotate(5deg);
        }

        .stat-number {
            font-size: 2.5rem;
            font-weight: 800;
            background: var(--blue-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 8px;
        }

        .stat-label {
            color: var(--text-light);
            font-size: 0.9375rem;
            font-weight: 500;
        }

        /* Plan CTA Section */
        .plan-cta {
            background: var(--blue-gradient);
            border-radius: 24px;
            padding: 80px 60px;
            margin: 60px 0;
            color: white;
            text-align: center;
            position: relative;
            overflow: hidden;
            box-shadow: 0 20px 60px rgba(102, 126, 234, 0.3);
            animation: pulse 2s infinite;
        }

        .plan-cta::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('https://images.unsplash.com/photo-1488646953014-85cb44e25828?ixlib=rb-4.0.3&auto=format&fit=crop&w=1200&q=80') center/cover;
            opacity: 0.1;
            animation: zoomInOut 20s infinite alternate;
        }

        @keyframes zoomInOut {
            0% { transform: scale(1); }
            100% { transform: scale(1.1); }
        }

        .cta-content {
            position: relative;
            z-index: 2;
        }

        .cta-content h2 {
            font-size: 3rem;
            font-weight: 900;
            margin-bottom: 24px;
            line-height: 1.2;
            text-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
        }

        .cta-content p {
            font-size: 1.25rem;
            opacity: 0.9;
            margin-bottom: 40px;
            max-width: 600px;
            margin-left: auto;
            margin-right: auto;
        }

        .btn-primary {
            display: inline-flex;
            align-items: center;
            gap: 12px;
            background: white;
            color: var(--primary-blue);
            border: none;
            padding: 18px 40px;
            border-radius: 50px;
            font-weight: 700;
            font-size: 1.125rem;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.3s ease;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            position: relative;
            overflow: hidden;
        }

        .btn-primary:hover {
            transform: translateY(-5px) scale(1.05);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2);
        }

        /* Animations */
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes slideInUp {
            from {
                opacity: 0;
                transform: translateY(50px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.02); }
        }

        @keyframes ripple {
            0% {
                transform: scale(0, 0);
                opacity: 0.5;
            }
            100% {
                transform: scale(40, 40);
                opacity: 0;
            }
        }

        /* Responsive Design */
        @media (max-width: 1024px) {
            .hero-content h1 {
                font-size: 2.5rem;
            }
            
            .trip-grid, .activity-grid {
                grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            }
        }

        @media (max-width: 768px) {
            nav {
                padding: 0 20px;
                height: 70px;
            }
            
            .nav-links {
                display: none;
            }
            
            .hero-banner {
                height: 400px;
                margin: 20px 0 40px;
            }
            
            .hero-content h1 {
                font-size: 2rem;
            }
            
            .search-section {
                padding: 30px 20px;
            }
            
            .search-box {
                flex-direction: column;
            }
            
            .search-box button {
                width: 100%;
                justify-content: center;
            }
            
            .search-icon {
                right: 20px;
            }
            
            .plan-cta {
                padding: 60px 30px;
            }
            
            .cta-content h2 {
                font-size: 2rem;
            }
        }

        @media (max-width: 480px) {
            .landing-container {
                padding: 0 20px 40px;
            }
            
            .hero-banner {
                height: 300px;
            }
            
            .hero-content h1 {
                font-size: 1.75rem;
            }
            
            .stats-container {
                grid-template-columns: 1fr;
            }
            
            .section-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 16px;
            }
        }

        /* Loading animation for cards */
        .card-loading {
            animation: shimmer 2s infinite linear;
            background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
            background-size: 200% 100%;
        }

        @keyframes shimmer {
            0% { background-position: -200% 0; }
            100% { background-position: 200% 0; }
        }
    </style>
</head>
<body>
    <div class="floating-elements">
        <div class="floating-circle"></div>
        <div class="floating-circle"></div>
    </div>

    <header>
        <nav>
            <div class="logo">
                <span class="logo-icon">✈️</span>
                <span>Globetrotter</span>
            </div>
            <div class="nav-links">
                <a href="landing.php" class="active"><i class="fas fa-home"></i> Home</a>
                <a href="../trip/create_trip.php"><i class="fas fa-map-marked-alt"></i> Plan Trip</a>
                <a href="trip_list.php"><i class="fas fa-suitcase"></i> My Trips</a>
                <a href="../explore/activities.php"><i class="fas fa-compass"></i> Explore</a>
                <a href="../community/chat.php"><i class="fas fa-users"></i> Community</a>
                <a href="../profile/profile.php"><i class="fas fa-user-circle"></i> Profile</a>
                <div class="user-profile" onclick="window.location.href='../profile/profile.php'">
                    <img src="<?= $profile_image ?>" alt="Profile" class="profile-img">
                    <span class="username"><?= isset($_SESSION['username']) ? htmlspecialchars($_SESSION['username']) : 'Guest' ?></span>
                </div>
            </div>
        </nav>
    </header>

    <main class="landing-container">
        <!-- Animated Hero Banner -->
        <section class="hero-banner animate__animated animate__fadeIn">
            <div class="banner-slider">
                <?php foreach($banner_images as $index => $image): ?>
                <div class="banner-slide <?= $index === 0 ? 'active' : '' ?>" 
                     style="background-image: url('<?= $image ?>');"></div>
                <?php endforeach; ?>
            </div>
            <div class="hero-overlay">
                <div class="hero-content">
                    <h1>Where Will Your Next Adventure Take You?</h1>
                    <p>Discover breathtaking destinations and create unforgettable memories with our intelligent trip planning</p>
                    <a href="../trip/create_trip.php" class="btn-primary">
                        <i class="fas fa-rocket"></i> Start Your Journey
                    </a>
                </div>
            </div>
        </section>

        <!-- Quick Stats -->
        <div class="stats-container">
            <div class="stat-card" onclick="window.location.href='trip_list.php'">
                <div class="stat-icon">
                    <i class="fas fa-suitcase-rolling"></i>
                </div>
                <div class="stat-number"><?= count($previous_trips) ?></div>
                <div class="stat-label">Trips Completed</div>
            </div>
            <div class="stat-card" onclick="window.location.href='../explore/activities.php'">
                <div class="stat-icon">
                    <i class="fas fa-mountain"></i>
                </div>
                <div class="stat-number"><?= count($activities) ?></div>
                <div class="stat-label">Activities Available</div>
            </div>
            <div class="stat-card" onclick="window.location.href='../community/chat.php'">
                <div class="stat-icon">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-number"><?= count($regions) ?></div>
                <div class="stat-label">Active Regions</div>
            </div>
        </div>

        <!-- Search Section -->
        <section class="search-section">
            <div class="search-box">
                <input type="text" id="searchInput" placeholder="Where would you like to go next?">
                <i class="fas fa-search search-icon"></i>
                <button id="searchBtn">
                    <i class="fas fa-search"></i> Explore Destinations
                </button>
            </div>
            
            <div class="filters">
                <div class="filter-group">
                    <label>Popular Destinations</label>
                    <select id="regionFilter">
                        <option value="">All Destinations</option>
                        <?php foreach($regions as $region): ?>
                        <option value="<?= htmlspecialchars($region['region']) ?>">
                            <?= htmlspecialchars($region['region']) ?> (<?= $region['count'] ?> travelers)
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label>Travel Style</label>
                    <select id="categoryFilter">
                        <option value="">All Styles</option>
                        <option value="adventure">Adventure & Sports</option>
                        <option value="beach">Beach & Relaxation</option>
                        <option value="cultural">Cultural & Historical</option>
                        <option value="family">Family Friendly</option>
                        <option value="luxury">Luxury Travel</option>
                        <option value="budget">Budget Travel</option>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label>Sort By</label>
                    <select id="sortFilter">
                        <option value="popular">Most Popular</option>
                        <option value="newest">Newest Destinations</option>
                        <option value="trending">Trending Now</option>
                        <option value="budget">Budget Friendly</option>
                    </select>
                </div>
            </div>
        </section>

        <!-- Previous Trips Section -->
        <section class="previous-trips">
            <div class="section-header">
                <h2><i class="fas fa-history"></i> Your Travel Memories</h2>
                <?php if(!empty($previous_trips)): ?>
                <a href="trip_list.php" class="view-all">View All Adventures <i class="fas fa-arrow-right"></i></a>
                <?php endif; ?>
            </div>
            
            <?php if(!empty($previous_trips)): ?>
                <div class="trip-grid">
                    <?php foreach($previous_trips as $trip): 
                        $trip_type = strtolower($trip['trip_type'] ?? 'city');
                        $image_url = $destination_images[$trip_type] ?? $destination_images['city'];
                    ?>
                    <div class="trip-card" onclick="window.location.href='../trip/trip_details.php?id=<?= $trip['id'] ?>'">
                        <img src="<?= $image_url ?>" alt="<?= htmlspecialchars($trip['trip_name']) ?>">
                        <div class="trip-card-content">
                            <h3><?= htmlspecialchars($trip['trip_name']) ?></h3>
                            <p><i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($trip['destination']) ?></p>
                            <p><i class="far fa-calendar-alt"></i> <?= date('F Y', strtotime($trip['start_date'])) ?></p>
                            <span class="date">
                                <i class="fas fa-check-circle"></i> Completed
                            </span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="empty-state" style="text-align: center; padding: 60px 20px;">
                    <div style="font-size: 4rem; color: #e2e8f0; margin-bottom: 20px;">
                        <i class="fas fa-suitcase"></i>
                    </div>
                    <h3 style="font-size: 1.5rem; color: var(--text-dark); margin-bottom: 10px;">
                        No adventures yet!
                    </h3>
                    <p style="color: var(--text-light); margin-bottom: 30px;">
                        Start your journey by planning your first trip
                    </p>
                    <a href="../trip/create_trip.php" class="btn-primary">
                        <i class="fas fa-plus"></i> Plan Your First Adventure
                    </a>
                </div>
            <?php endif; ?>
        </section>

        <!-- Plan a Trip CTA -->
        <section class="plan-cta">
            <div class="cta-content">
                <h2>Adventure Awaits Around Every Corner</h2>
                <p>Join thousands of travelers who have found their perfect getaway through our platform</p>
                <a href="../trip/create_trip.php" class="btn-primary">
                    <i class="fas fa-compass"></i> Create Your Dream Trip
                </a>
            </div>
        </section>

        <!-- Trending Activities -->
        <section class="trending-activities">
            <div class="section-header">
                <h2><i class="fas fa-fire"></i> Trending Experiences</h2>
                <a href="../explore/activities.php" class="view-all">Explore More <i class="fas fa-arrow-right"></i></a>
            </div>
            
            <div class="activity-grid">
                <?php foreach($activities as $activity): 
                    $image_url = $destination_images[$activity['type']] ?? $destination_images['sightseeing'];
                ?>
                <div class="activity-card" onclick="window.location.href='../explore/activity_details.php?id=<?= $activity['id'] ?>'">
                    <img src="<?= $image_url ?>" alt="<?= htmlspecialchars($activity['name']) ?>">
                    <div class="activity-card-content">
                        <h4><?= htmlspecialchars($activity['name']) ?></h4>
                        <p><i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($activity['city']) ?>, <?= htmlspecialchars($activity['country']) ?></p>
                        <span class="activity-type">
                            <i class="fas fa-tag"></i> <?= ucfirst($activity['type']) ?>
                        </span>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </section>
    </main>

    <script>
        // Animated banner slider
        let currentSlide = 0;
        const slides = document.querySelectorAll('.banner-slide');
        const totalSlides = slides.length;

        function rotateBanner() {
            slides[currentSlide].classList.remove('active');
            currentSlide = (currentSlide + 1) % totalSlides;
            slides[currentSlide].classList.add('active');
        }

        // Start banner rotation every 5 seconds
        setInterval(rotateBanner, 5000);

        // Search functionality
        const searchBtn = document.getElementById('searchBtn');
        const searchInput = document.getElementById('searchInput');

        searchBtn.addEventListener('click', function() {
            const query = searchInput.value.trim();
            if(query) {
                // Add click animation
                this.style.transform = 'scale(0.95)';
                setTimeout(() => {
                    this.style.transform = '';
                }, 200);
                
                window.location.href = `../explore/activities.php?search=${encodeURIComponent(query)}`;
            } else {
                searchInput.focus();
                searchInput.style.animation = 'shake 0.5s';
                setTimeout(() => {
                    searchInput.style.animation = '';
                }, 500);
            }
        });

        // Add shake animation
        const style = document.createElement('style');
        style.textContent = `
            @keyframes shake {
                0%, 100% { transform: translateX(0); }
                25% { transform: translateX(-5px); }
                75% { transform: translateX(5px); }
            }
        `;
        document.head.appendChild(style);

        // Enter key support for search
        searchInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                searchBtn.click();
            }
        });

        // Filter animations
        const filters = ['regionFilter', 'categoryFilter', 'sortFilter'];
        filters.forEach(filterId => {
            const filter = document.getElementById(filterId);
            filter.addEventListener('change', function() {
                this.style.transform = 'scale(1.05)';
                setTimeout(() => {
                    this.style.transform = '';
                }, 300);
                
                const filterData = {
                    region: document.getElementById('regionFilter').value,
                    category: document.getElementById('categoryFilter').value,
                    sort: document.getElementById('sortFilter').value
                };
                
                console.log('Filters updated:', filterData);
                // In production, make AJAX call here
            });
        });

        // Card hover effects with parallax
        document.querySelectorAll('.trip-card, .activity-card').forEach(card => {
            card.addEventListener('mousemove', function(e) {
                const rect = this.getBoundingClientRect();
                const x = e.clientX - rect.left;
                const y = e.clientY - rect.top;
                
                const centerX = rect.width / 2;
                const centerY = rect.height / 2;
                
                const rotateY = (x - centerX) / 25;
                const rotateX = (centerY - y) / 25;
                
                this.style.transform = `perspective(1000px) rotateX(${rotateX}deg) rotateY(${rotateY}deg) translateY(-10px) scale(1.02)`;
            });
            
            card.addEventListener('mouseleave', function() {
                this.style.transform = '';
            });
        });

        // Stats cards animation on scroll
        const observerOptions = {
            threshold: 0.5,
            rootMargin: '0px 0px -100px 0px'
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.animation = 'fadeInUp 0.6s ease forwards';
                }
            });
        }, observerOptions);

        // Observe all cards for animation
        document.querySelectorAll('.trip-card, .activity-card, .stat-card').forEach(card => {
            card.style.opacity = '0';
            observer.observe(card);
        });

        // Dynamic greeting based on time
        const hour = new Date().getHours();
        let greeting = 'Welcome';
        if (hour < 12) greeting = 'Good Morning';
        else if (hour < 18) greeting = 'Good Afternoon';
        else greeting = 'Good Evening';

        // Update hero text if you want dynamic greeting
        // document.querySelector('.hero-content h1').textContent = `${greeting}, Traveler! Where Will Your Next Adventure Take You?`;

        // Smooth scrolling for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });
    </script>
</body>
</html>