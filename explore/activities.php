<?php
session_start();
require_once '../db.php';

// Get search parameters
$search = $_GET['search'] ?? '';
$city = $_GET['city'] ?? '';
$country = $_GET['country'] ?? '';
$type = $_GET['type'] ?? '';
$min_cost = $_GET['min_cost'] ?? '';
$max_cost = $_GET['max_cost'] ?? '';
$duration = $_GET['duration'] ?? '';
$sort = $_GET['sort'] ?? 'popularity';

// Build query
$query = "SELECT * FROM activities WHERE 1=1";
$params = [];

if ($search) {
    $query .= " AND (name LIKE ? OR description LIKE ? OR city LIKE ? OR country LIKE ?)";
    $likeSearch = "%$search%";
    $params[] = $likeSearch;
    $params[] = $likeSearch;
    $params[] = $likeSearch;
    $params[] = $likeSearch;
}

if ($city) {
    $query .= " AND city LIKE ?";
    $params[] = "%$city%";
}

if ($country) {
    $query .= " AND country LIKE ?";
    $params[] = "%$country%";
}

if ($type) {
    $query .= " AND type = ?";
    $params[] = $type;
}

if ($min_cost !== '') {
    $query .= " AND avg_cost >= ?";
    $params[] = $min_cost;
}

if ($max_cost !== '') {
    $query .= " AND avg_cost <= ?";
    $params[] = $max_cost;
}

if ($duration) {
    switch ($duration) {
        case 'short':
            $query .= " AND duration_hours <= 2";
            break;
        case 'medium':
            $query .= " AND duration_hours BETWEEN 3 AND 6";
            break;
        case 'long':
            $query .= " AND duration_hours > 6";
            break;
    }
}

// Apply sorting
switch ($sort) {
    case 'name_asc':
        $query .= " ORDER BY name ASC";
        break;
    case 'name_desc':
        $query .= " ORDER BY name DESC";
        break;
    case 'cost_asc':
        $query .= " ORDER BY avg_cost ASC";
        break;
    case 'cost_desc':
        $query .= " ORDER BY avg_cost DESC";
        break;
    case 'duration_asc':
        $query .= " ORDER BY duration_hours ASC";
        break;
    case 'duration_desc':
        $query .= " ORDER BY duration_hours DESC";
        break;
    default: // popularity
        $query .= " ORDER BY popularity DESC, name ASC";
        break;
}

// Get unique cities and countries for filters
$cities = $pdo->query("SELECT DISTINCT city FROM activities ORDER BY city")->fetchAll();
$countries = $pdo->query("SELECT DISTINCT country FROM activities ORDER BY country")->fetchAll();
$types = $pdo->query("SELECT DISTINCT type FROM activities ORDER BY type")->fetchAll();

// Execute main query
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$activities = $stmt->fetchAll();

// If no search, show popular activities
if (empty($search) && empty($city) && empty($country) && empty($type)) {
    $popular = $pdo->query("SELECT * FROM activities ORDER BY popularity DESC LIMIT 12")->fetchAll();
} else {
    $popular = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Explore Activities - Globetrotter</title>
    <link rel="stylesheet" href="../assets/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .map-container {
            height: 300px;
            background: linear-gradient(135deg, #6a11cb 0%, #2575fc 100%);
            border-radius: 10px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            position: relative;
            overflow: hidden;
        }
        
        .map-container::before {
            content: '';
            position: absolute;
            width: 100%;
            height: 100%;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path fill="white" opacity="0.1" d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/></svg>');
            background-size: 100px;
            opacity: 0.1;
        }
        
        .city-search {
            background: white;
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        
        .city-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
            gap: 15px;
            margin-top: 20px;
        }
        
        .city-card {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
            border: 2px solid transparent;
        }
        
        .city-card:hover {
            border-color: #667eea;
            transform: translateY(-2px);
        }
        
        .city-card i {
            font-size: 1.5rem;
            color: #667eea;
            margin-bottom: 10px;
        }
        
        .activities-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 25px;
            margin-top: 20px;
        }
        
        .activity-detail-card {
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            transition: transform 0.3s;
        }
        
        .activity-detail-card:hover {
            transform: translateY(-5px);
        }
        
        .activity-image {
            height: 160px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 3rem;
        }
        
        .activity-content {
            padding: 20px;
        }
        
        .activity-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }
        
        .activity-type {
            background: #eef2ff;
            color: #667eea;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
        }
        
        .activity-cost {
            color: #38a169;
            font-weight: bold;
        }
        
        .activity-location {
            color: #666;
            margin-bottom: 10px;
            font-size: 0.9rem;
        }
        
        .activity-location i {
            margin-right: 5px;
        }
        
        .activity-description {
            color: #555;
            margin: 10px 0;
            font-size: 0.95rem;
            line-height: 1.5;
        }
        
        .activity-stats {
            display: flex;
            justify-content: space-between;
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid #eee;
            font-size: 0.85rem;
            color: #666;
        }
        
        .add-to-trip {
            background: #48bb78;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 6px;
            cursor: pointer;
            margin-top: 15px;
            width: 100%;
            transition: background 0.3s;
        }
        
        .add-to-trip:hover {
            background: #38a169;
        }
        
        .filters-sidebar {
            background: white;
            border-radius: 10px;
            padding: 25px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        
        .filter-section {
            margin-bottom: 25px;
        }
        
        .filter-section h3 {
            margin-bottom: 15px;
            color: #333;
            font-size: 1.1rem;
        }
        
        .filter-tags {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }
        
        .filter-tag {
            background: #f0f2f5;
            border: none;
            padding: