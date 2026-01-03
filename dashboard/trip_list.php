<?php
session_start();
require_once '../db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// Get filter parameters
$status = $_GET['status'] ?? 'all';
$sort = $_GET['sort'] ?? 'start_date_desc';
$search = $_GET['search'] ?? '';

// Build query
$query = "SELECT * FROM trips WHERE user_id = ?";
$params = [$user_id];

if ($search) {
    $query .= " AND (trip_name LIKE ? OR destination LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($status !== 'all') {
    $query .= " AND status = ?";
    $params[] = $status;
}

// Apply sorting
switch ($sort) {
    case 'start_date_asc':
        $query .= " ORDER BY start_date ASC";
        break;
    case 'end_date_desc':
        $query .= " ORDER BY end_date DESC";
        break;
    case 'end_date_asc':
        $query .= " ORDER BY end_date ASC";
        break;
    case 'budget_desc':
        $query .= " ORDER BY total_budget DESC";
        break;
    case 'budget_asc':
        $query .= " ORDER BY total_budget ASC";
        break;
    case 'name_asc':
        $query .= " ORDER BY trip_name ASC";
        break;
    default: // start_date_desc
        $query .= " ORDER BY start_date DESC";
        break;
}

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$trips = $stmt->fetchAll();

// Get trip statistics
$stats = $pdo->prepare("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = 'planning' THEN 1 ELSE 0 END) as planning,
        SUM(CASE WHEN status = 'ongoing' THEN 1 ELSE 0 END) as ongoing,
        SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed
    FROM trips 
    WHERE user_id = ?
");
$stats->execute([$user_id]);
$stats = $stats->fetch();

// Trip images mapping (based on destination keywords)
function getTripImage($destination) {
    $destination = strtolower($destination);
    $images = [
        'beach' => 'https://images.unsplash.com/photo-1507525428034-b723cf961d3e?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80',
        'mountain' => 'https://images.unsplash.com/photo-1464822759023-fed622ff2c3b?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80',
        'city' => 'https://images.unsplash.com/photo-1519501025264-65ba15a82390?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80',
        'island' => 'https://images.unsplash.com/photo-1537953773345-d172ccf13cf1?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80',
        'forest' => 'https://images.unsplash.com/photo-1448375240586-882707db888b?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80',
        'desert' => 'https://images.unsplash.com/photo-1505118380757-91f5f5632de0?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80',
        'lake' => 'https://images.unsplash.com/photo-1501785888041-af3ef285b470?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80',
        'default' => 'https://images.unsplash.com/photo-1469474968028-56623f02e42e?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80'
    ];
    
    // Check for keywords in destination
    foreach ($images as $keyword => $image) {
        if (strpos($destination, $keyword) !== false) {
            return $image;
        }
    }
    
    return $images['default'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Trips | Globetrotter ✈️</title>
    <link rel="stylesheet" href="../assets/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
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
            min-height: 100vh;
            padding: 20px;
        }

        /* Floating Elements */
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

        /* Main Container */
        .trips-container {
            max-width: 1400px;
            margin: 0 auto;
        }

        /* Header */
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 40px;
            animation: fadeInUp 0.8s ease;
        }

        .page-header h1 {
            font-size: 2.5rem;
            font-weight: 900;
            background: var(--blue-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .page-header h1 i {
            font-size: 2.5rem;
        }

        .btn-back {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px 24px;
            background: var(--blue-bg);
            color: var(--primary-blue);
            text-decoration: none;
            border-radius: 50px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-back:hover {
            background: var(--blue-border);
            transform: translateX(-5px);
        }

        /* Statistics Cards */
        .stats-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 24px;
            margin-bottom: 40px;
            animation: fadeInUp 0.8s ease 0.2s both;
        }

        .stat-card {
            background: var(--glass-bg);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 30px;
            border: 1px solid rgba(255, 255, 255, 0.3);
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            cursor: pointer;
            position: relative;
            overflow: hidden;
        }

        .stat-card:hover {
            transform: translateY(-10px);
            box-shadow: var(--card-shadow-hover);
            border-color: var(--blue-border);
        }

        .stat-card::before {
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

        .stat-card:hover::before {
            opacity: 1;
        }

        .stat-icon {
            width: 70px;
            height: 70px;
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 20px;
            font-size: 1.75rem;
            color: white;
            transition: transform 0.3s ease;
        }

        .stat-card:hover .stat-icon {
            transform: scale(1.1) rotate(5deg);
        }

        .stat-icon.planning { background: linear-gradient(135deg, #f59e0b, #d97706); }
        .stat-icon.ongoing { background: linear-gradient(135deg, #3b82f6, #1d4ed8); }
        .stat-icon.completed { background: linear-gradient(135deg, #10b981, #059669); }
        .stat-icon.total { background: var(--blue-gradient); }

        .stat-info h3 {
            font-size: 2.5rem;
            font-weight: 800;
            margin-bottom: 5px;
            background: var(--blue-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .stat-info p {
            color: var(--text-light);
            font-size: 0.9375rem;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        /* Filters Section */
        .filters-section {
            background: var(--glass-bg);
            backdrop-filter: blur(10px);
            border-radius: 24px;
            padding: 40px;
            margin-bottom: 40px;
            box-shadow: var(--card-shadow);
            border: 1px solid rgba(255, 255, 255, 0.3);
            animation: fadeInUp 0.8s ease 0.4s both;
            position: relative;
            overflow: hidden;
        }

        .filters-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 5px;
            background: var(--blue-gradient);
        }

        .search-bar {
            display: flex;
            margin-bottom: 30px;
            position: relative;
        }

        .search-bar input {
            flex: 1;
            padding: 18px 24px;
            border: 2px solid transparent;
            border-radius: 16px;
            font-size: 1.125rem;
            background: white;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
        }

        .search-bar input:focus {
            outline: none;
            border-color: var(--primary-blue);
            box-shadow: 0 4px 30px rgba(37, 99, 235, 0.2);
        }

        .search-bar button {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            background: var(--blue-gradient);
            color: white;
            border: none;
            width: 50px;
            height: 50px;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .search-bar button:hover {
            transform: translateY(-50%) scale(1.1);
        }

        .filter-controls {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 24px;
            margin-bottom: 30px;
        }

        .filter-group label {
            display: block;
            margin-bottom: 12px;
            font-weight: 600;
            color: var(--text-dark);
            font-size: 0.875rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            display: flex;
            align-items: center;
            gap: 8px;
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
        }

        .create-trip-btn {
            text-align: center;
        }

        .btn-primary {
            display: inline-flex;
            align-items: center;
            gap: 12px;
            background: var(--blue-gradient);
            color: white;
            border: none;
            padding: 18px 40px;
            border-radius: 50px;
            font-weight: 700;
            font-size: 1.125rem;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.3s ease;
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);
            position: relative;
            overflow: hidden;
        }

        .btn-primary:hover {
            transform: translateY(-5px) scale(1.05);
            box-shadow: 0 20px 40px rgba(102, 126, 234, 0.4);
        }

        /* Trips Grid */
        .trips-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(400px, 1fr));
            gap: 32px;
            animation: fadeInUp 0.8s ease 0.6s both;
        }

        .trip-card-large {
            background: white;
            border-radius: 24px;
            overflow: hidden;
            box-shadow: var(--card-shadow);
            border: 1px solid rgba(255, 255, 255, 0.5);
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            cursor: pointer;
            position: relative;
            backdrop-filter: blur(10px);
        }

        .trip-card-large:hover {
            transform: translateY(-10px);
            box-shadow: var(--card-shadow-hover);
        }

        .trip-card-large::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 5px;
            background: var(--blue-gradient);
            opacity: 0;
            transition: opacity 0.3s ease;
            z-index: 2;
        }

        .trip-card-large:hover::before {
            opacity: 1;
        }

        .trip-card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 24px 30px 0;
            margin-bottom: 20px;
        }

        .trip-status .status-badge {
            padding: 8px 20px;
            border-radius: 50px;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .status-planning {
            background: linear-gradient(135deg, #fef3c7, #f59e0b);
            color: #92400e;
        }

        .status-ongoing {
            background: linear-gradient(135deg, #dbeafe, #3b82f6);
            color: #1e40af;
        }

        .status-completed {
            background: linear-gradient(135deg, #d1fae5, #10b981);
            color: #065f46;
        }

        .trip-actions {
            display: flex;
            gap: 12px;
        }

        .btn-icon {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--blue-bg);
            color: var(--primary-blue);
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .btn-icon:hover {
            background: var(--blue-border);
            transform: translateY(-2px);
        }

        .btn-icon.delete-trip:hover {
            background: #fee2e2;
            color: #dc2626;
        }

        .trip-card-image {
            width: 100%;
            height: 200px;
            overflow: hidden;
        }

        .trip-card-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.6s ease;
        }

        .trip-card-large:hover .trip-card-image img {
            transform: scale(1.1);
        }

        .trip-card-body {
            padding: 0 30px;
        }

        .trip-card-body h3 {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 12px;
            color: var(--text-dark);
            line-height: 1.3;
        }

        .destination {
            display: flex;
            align-items: center;
            gap: 10px;
            color: var(--primary-blue);
            font-weight: 600;
            margin-bottom: 20px;
        }

        .trip-details {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 16px;
            margin-bottom: 20px;
        }

        .detail {
            display: flex;
            align-items: center;
            gap: 10px;
            color: var(--text-light);
            font-size: 0.9375rem;
        }

        .detail i {
            color: var(--primary-blue);
            width: 20px;
        }

        .trip-budget {
            background: var(--blue-bg);
            padding: 15px 20px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            gap: 12px;
            font-weight: 600;
            color: var(--primary-blue);
            margin-bottom: 20px;
        }

        .trip-budget i {
            font-size: 1.25rem;
        }

        .trip-card-footer {
            padding: 20px 30px;
            border-top: 1px solid var(--gray-border);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .created-date {
            color: var(--text-light);
            font-size: 0.875rem;
        }

        .btn-small {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            background: var(--blue-gradient);
            color: white;
            text-decoration: none;
            border-radius: 10px;
            font-weight: 600;
            font-size: 0.875rem;
            transition: all 0.3s ease;
        }

        .btn-small:hover {
            transform: translateX(5px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);
        }

        /* Empty State */
        .empty-state {
            background: var(--glass-bg);
            backdrop-filter: blur(10px);
            border-radius: 24px;
            padding: 80px 40px;
            text-align: center;
            border: 2px dashed var(--blue-border);
            animation: fadeInUp 0.8s ease;
        }

        .empty-state i {
            font-size: 4rem;
            color: var(--blue-light);
            margin-bottom: 24px;
            opacity: 0.5;
        }

        .empty-state h3 {
            font-size: 1.75rem;
            font-weight: 700;
            color: var(--text-dark);
            margin-bottom: 12px;
        }

        .empty-state p {
            color: var(--text-light);
            margin-bottom: 32px;
            font-size: 1.125rem;
        }

        /* Animations */
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

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        /* Responsive Design */
        @media (max-width: 1024px) {
            .trips-grid {
                grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            }
        }

        @media (max-width: 768px) {
            body {
                padding: 16px;
            }
            
            .page-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 20px;
            }
            
            .page-header h1 {
                font-size: 2rem;
            }
            
            .stats-cards {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .filters-section {
                padding: 30px 20px;
            }
            
            .filter-controls {
                grid-template-columns: 1fr;
            }
            
            .trips-grid {
                grid-template-columns: 1fr;
            }
            
            .trip-details {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 480px) {
            .stats-cards {
                grid-template-columns: 1fr;
            }
            
            .page-header h1 {
                font-size: 1.75rem;
            }
            
            .empty-state {
                padding: 60px 20px;
            }
        }

        /* Trip card hover parallax effect */
        .trip-card-large {
            perspective: 1000px;
        }

        .trip-card-large:hover {
            transform: translateY(-10px) rotateX(2deg) rotateY(2deg);
        }

        /* Loading animation */
        .loading {
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

    <div class="trips-container">
        <!-- Header -->
        <div class="page-header">
            <h1><i class="fas fa-suitcase-rolling"></i> My Travel Adventures</h1>
            <a href="landing.php" class="btn-back">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
        </div>

        <!-- Statistics -->
        <div class="stats-cards">
            <div class="stat-card" onclick="filterByStatus('planning')">
                <div class="stat-icon planning">
                    <i class="fas fa-pen-fancy"></i>
                </div>
                <div class="stat-info">
                    <h3><?= $stats['planning'] ?? 0 ?></h3>
                    <p>Planning</p>
                </div>
            </div>
            
            <div class="stat-card" onclick="filterByStatus('ongoing')">
                <div class="stat-icon ongoing">
                    <i class="fas fa-plane-departure"></i>
                </div>
                <div class="stat-info">
                    <h3><?= $stats['ongoing'] ?? 0 ?></h3>
                    <p>Ongoing</p>
                </div>
            </div>
            
            <div class="stat-card" onclick="filterByStatus('completed')">
                <div class="stat-icon completed">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="stat-info">
                    <h3><?= $stats['completed'] ?? 0 ?></h3>
                    <p>Completed</p>
                </div>
            </div>
            
            <div class="stat-card" onclick="filterByStatus('all')">
                <div class="stat-icon total">
                    <i class="fas fa-globe-americas"></i>
                </div>
                <div class="stat-info">
                    <h3><?= $stats['total'] ?? 0 ?></h3>
                    <p>Total Adventures</p>
                </div>
            </div>
        </div>

        <!-- Search and Filters -->
        <div class="filters-section">
            <form method="GET" class="filters-form" id="filtersForm">
                <div class="search-bar">
                    <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" 
                           placeholder="Search your adventures...">
                    <button type="submit"><i class="fas fa-search"></i></button>
                </div>
                
                <div class="filter-controls">
                    <div class="filter-group">
                        <label><i class="fas fa-filter"></i> Trip Status</label>
                        <select name="status" id="statusFilter">
                            <option value="all" <?= $status == 'all' ? 'selected' : '' ?>>All Adventures</option>
                            <option value="planning" <?= $status == 'planning' ? 'selected' : '' ?>>Planning</option>
                            <option value="ongoing" <?= $status == 'ongoing' ? 'selected' : '' ?>>Ongoing</option>
                            <option value="completed" <?= $status == 'completed' ? 'selected' : '' ?>>Completed</option>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label><i class="fas fa-sort"></i> Sort By</label>
                        <select name="sort" id="sortFilter">
                            <option value="start_date_desc" <?= $sort == 'start_date_desc' ? 'selected' : '' ?>>Start Date (Newest)</option>
                            <option value="start_date_asc" <?= $sort == 'start_date_asc' ? 'selected' : '' ?>>Start Date (Oldest)</option>
                            <option value="end_date_desc" <?= $sort == 'end_date_desc' ? 'selected' : '' ?>>End Date (Newest)</option>
                            <option value="end_date_asc" <?= $sort == 'end_date_asc' ? 'selected' : '' ?>>End Date (Oldest)</option>
                            <option value="budget_desc" <?= $sort == 'budget_desc' ? 'selected' : '' ?>>Budget (High to Low)</option>
                            <option value="budget_asc" <?= $sort == 'budget_asc' ? 'selected' : '' ?>>Budget (Low to High)</option>
                            <option value="name_asc" <?= $sort == 'name_asc' ? 'selected' : '' ?>>Name (A-Z)</option>
                        </select>
                    </div>
                </div>
            </form>
            
            <div class="create-trip-btn">
                <a href="../trip/create_trip.php" class="btn-primary">
                    <i class="fas fa-plus"></i> Plan New Adventure
                </a>
            </div>
        </div>

        <!-- Trips Grid -->
        <?php if(empty($trips)): ?>
            <div class="empty-state">
                <i class="fas fa-suitcase"></i>
                <h3>No adventures found</h3>
                <p><?= $search ? 'Try a different search term' : 'Start planning your first exciting journey!' ?></p>
                <a href="../trip/create_trip.php" class="btn-primary">
                    <i class="fas fa-compass"></i> Create Your First Adventure
                </a>
            </div>
        <?php else: ?>
            <div class="trips-grid">
                <?php foreach($trips as $trip): 
                    // Calculate trip duration
                    $start = new DateTime($trip['start_date']);
                    $end = new DateTime($trip['end_date']);
                    $duration = $start->diff($end)->days + 1;
                    
                    // Get itinerary count
                    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM itinerary_sections WHERE trip_id = ?");
                    $stmt->execute([$trip['id']]);
                    $section_count = $stmt->fetch()['count'];
                    
                    // Get image based on destination keywords
                    $image_url = getTripImage($trip['destination']);
                    
                    // Status badge
                    $status_classes = [
                        'planning' => 'status-planning',
                        'ongoing' => 'status-ongoing',
                        'completed' => 'status-completed'
                    ];
                ?>
                <div class="trip-card-large" onclick="window.location.href='../trip/itinerary_view.php?trip_id=<?= $trip['id'] ?>'">
                    <div class="trip-card-header">
                        <div class="trip-status">
                            <span class="status-badge <?= $status_classes[$trip['status']] ?>">
                                <?= ucfirst($trip['status']) ?>
                            </span>
                        </div>
                        <div class="trip-actions" onclick="event.stopPropagation();">
                            <a href="../trip/itinerary_view.php?trip_id=<?= $trip['id'] ?>" class="btn-icon" title="View Adventure">
                                <i class="fas fa-eye"></i>
                            </a>
                            <a href="../trip/itinerary_builder.php?trip_id=<?= $trip['id'] ?>" class="btn-icon" title="Edit Adventure">
                                <i class="fas fa-edit"></i>
                            </a>
                            <button class="btn-icon delete-trip" data-id="<?= $trip['id'] ?>" title="Delete Adventure">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                    
                    <div class="trip-card-image">
                        <img src="<?= $image_url ?>" alt="<?= htmlspecialchars($trip['trip_name']) ?>">
                    </div>
                    
                    <div class="trip-card-body">
                        <h3><?= htmlspecialchars($trip['trip_name']) ?></h3>
                        <p class="destination">
                            <i class="fas fa-map-marker-alt"></i>
                            <?= htmlspecialchars($trip['destination']) ?>
                        </p>
                        
                        <div class="trip-details">
                            <div class="detail">
                                <i class="fas fa-calendar"></i>
                                <span><?= date('M d, Y', strtotime($trip['start_date'])) ?> - <?= date('M d, Y', strtotime($trip['end_date'])) ?></span>
                            </div>
                            <div class="detail">
                                <i class="fas fa-clock"></i>
                                <span><?= $duration ?> day<?= $duration > 1 ? 's' : '' ?></span>
                            </div>
                            <div class="detail">
                                <i class="fas fa-users"></i>
                                <span><?= $trip['travelers'] ?> traveler<?= $trip['travelers'] > 1 ? 's' : '' ?></span>
                            </div>
                            <div class="detail">
                                <i class="fas fa-list"></i>
                                <span><?= $section_count ?> itinerary sections</span>
                            </div>
                        </div>
                        
                        <?php if($trip['total_budget']): ?>
                        <div class="trip-budget">
                            <i class="fas fa-wallet"></i>
                            <strong>Budget:</strong> ₹<?= number_format($trip['total_budget'], 2) ?> INR
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="trip-card-footer">
                        <span class="created-date">
                            <i class="far fa-calendar-plus"></i>
                            Created: <?= date('M d, Y', strtotime($trip['created_at'])) ?>
                        </span>
                        <a href="../trip/itinerary_view.php?trip_id=<?= $trip['id'] ?>" class="btn-small">
                            View Details <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <script>
        // Filter functions
        function filterByStatus(status) {
            document.getElementById('statusFilter').value = status;
            document.getElementById('filtersForm').submit();
        }

        // Auto-submit filters on change
        document.querySelectorAll('#statusFilter, #sortFilter').forEach(select => {
            select.addEventListener('change', function() {
                // Add animation to filter
                this.style.transform = 'scale(1.05)';
                setTimeout(() => {
                    this.style.transform = '';
                }, 300);
                
                document.getElementById('filtersForm').submit();
            });
        });

        // Delete trip with confirmation
        document.querySelectorAll('.delete-trip').forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.stopPropagation(); // Prevent card click
                const tripId = this.dataset.id;
                
                // Create custom modal
                const modal = document.createElement('div');
                modal.style.cssText = `
                    position: fixed;
                    top: 0;
                    left: 0;
                    right: 0;
                    bottom: 0;
                    background: rgba(0,0,0,0.5);
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    z-index: 1000;
                    backdrop-filter: blur(5px);
                `;
                
                modal.innerHTML = `
                    <div style="
                        background: white;
                        padding: 40px;
                        border-radius: 20px;
                        max-width: 400px;
                        width: 90%;
                        text-align: center;
                        box-shadow: 0 20px 60px rgba(0,0,0,0.3);
                    ">
                        <div style="font-size: 3rem; color: #ef4444; margin-bottom: 20px;">
                            <i class="fas fa-exclamation-triangle"></i>
                        </div>
                        <h3 style="font-size: 1.5rem; color: var(--text-dark); margin-bottom: 15px;">
                            Delete Adventure?
                        </h3>
                        <p style="color: var(--text-light); margin-bottom: 30px;">
                            This action cannot be undone. All itinerary data will be permanently deleted.
                        </p>
                        <div style="display: flex; gap: 15px; justify-content: center;">
                            <button id="cancelDelete" style="
                                padding: 12px 30px;
                                background: var(--gray-border);
                                color: var(--text-dark);
                                border: none;
                                border-radius: 10px;
                                font-weight: 600;
                                cursor: pointer;
                                transition: all 0.3s ease;
                            ">Cancel</button>
                            <button id="confirmDelete" style="
                                padding: 12px 30px;
                                background: linear-gradient(135deg, #ef4444, #dc2626);
                                color: white;
                                border: none;
                                border-radius: 10px;
                                font-weight: 600;
                                cursor: pointer;
                                transition: all 0.3s ease;
                            ">Delete Adventure</button>
                        </div>
                    </div>
                `;
                
                document.body.appendChild(modal);
                
                // Handle cancel
                document.getElementById('cancelDelete').addEventListener('click', () => {
                    document.body.removeChild(modal);
                });
                
                // Handle delete
                document.getElementById('confirmDelete').addEventListener('click', () => {
                    // Add loading state
                    document.getElementById('confirmDelete').innerHTML = '<i class="fas fa-spinner fa-spin"></i> Deleting...';
                    document.getElementById('confirmDelete').disabled = true;
                    
                    fetch('../trip/delete_trip.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: `trip_id=${tripId}`
                    })
                    .then(response => response.json())
                    .then(data => {
                        if(data.success) {
                            // Add fade out animation
                            const card = btn.closest('.trip-card-large');
                            card.style.opacity = '0';
                            card.style.transform = 'translateY(-20px) scale(0.95)';
                            
                            setTimeout(() => {
                                card.remove();
                                document.body.removeChild(modal);
                                
                                // Show success notification
                                showNotification('Adventure deleted successfully!', 'success');
                            }, 300);
                        } else {
                            document.body.removeChild(modal);
                            showNotification('Error: ' + data.message, 'error');
                        }
                    })
                    .catch(error => {
                        document.body.removeChild(modal);
                        showNotification('Network error occurred', 'error');
                    });
                });
            });
        });

        // Real-time search with debounce
        let searchTimeout;
        document.querySelector('[name="search"]').addEventListener('input', function() {
            clearTimeout(searchTimeout);
            
            // Add loading animation to search bar
            this.style.backgroundImage = 'linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%)';
            this.style.backgroundSize = '200% 100%';
            this.style.animation = 'shimmer 2s infinite linear';
            
            searchTimeout = setTimeout(() => {
                this.style.backgroundImage = '';
                this.style.animation = '';
                document.getElementById('filtersForm').submit();
            }, 500);
        });

        // Show notification
        function showNotification(message, type) {
            const notification = document.createElement('div');
            notification.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                padding: 15px 25px;
                background: ${type === 'success' ? '#10b981' : '#ef4444'};
                color: white;
                border-radius: 10px;
                box-shadow: 0 10px 30px rgba(0,0,0,0.2);
                z-index: 1000;
                animation: slideIn 0.3s ease;
                font-weight: 600;
            `;
            notification.textContent = message;
            
            document.body.appendChild(notification);
            
            setTimeout(() => {
                notification.style.animation = 'slideOut 0.3s ease forwards';
                setTimeout(() => {
                    document.body.removeChild(notification);
                }, 300);
            }, 3000);
        }

        // Add animations for notifications
        const notificationStyles = document.createElement('style');
        notificationStyles.textContent = `
            @keyframes slideIn {
                from {
                    transform: translateX(100%);
                    opacity: 0;
                }
                to {
                    transform: translateX(0);
                    opacity: 1;
                }
            }
            
            @keyframes slideOut {
                from {
                    transform: translateX(0);
                    opacity: 1;
                }
                to {
                    transform: translateX(100%);
                    opacity: 0;
                }
            }
        `;
        document.head.appendChild(notificationStyles);

        // Card hover effects
        document.querySelectorAll('.trip-card-large').forEach(card => {
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

        // Stats cards hover effect
        document.querySelectorAll('.stat-card').forEach(card => {
            card.addEventListener('mousemove', function(e) {
                const rect = this.getBoundingClientRect();
                const x = e.clientX - rect.left;
                const y = e.clientY - rect.top;
                
                const centerX = rect.width / 2;
                const centerY = rect.height / 2;
                
                const rotateY = (x - centerX) / 50;
                const rotateX = (centerY - y) / 50;
                
                this.style.transform = `perspective(1000px) rotateX(${rotateX}deg) rotateY(${rotateY}deg) translateY(-10px)`;
            });
            
            card.addEventListener('mouseleave', function() {
                this.style.transform = '';
            });
        });
    </script>
</body>
</html>