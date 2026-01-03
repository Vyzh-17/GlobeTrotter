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
$filter_type = $_GET['filter_type'] ?? '';

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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Trips - Globetrotter</title>
    <link rel="stylesheet" href="../assets/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="container">
        <div class="page-header">
            <h1><i class="fas fa-suitcase-rolling"></i> My Trips</h1>
            <a href="landing.php" class="btn-back">← Back to Home</a>
        </div>

        <!-- Statistics -->
        <div class="stats-cards">
            <div class="stat-card">
                <div class="stat-icon planning">
                    <i class="fas fa-pen-fancy"></i>
                </div>
                <div class="stat-info">
                    <h3><?= $stats['planning'] ?? 0 ?></h3>
                    <p>Planning</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon ongoing">
                    <i class="fas fa-plane-departure"></i>
                </div>
                <div class="stat-info">
                    <h3><?= $stats['ongoing'] ?? 0 ?></h3>
                    <p>Ongoing</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon completed">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="stat-info">
                    <h3><?= $stats['completed'] ?? 0 ?></h3>
                    <p>Completed</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon total">
                    <i class="fas fa-globe-americas"></i>
                </div>
                <div class="stat-info">
                    <h3><?= $stats['total'] ?? 0 ?></h3>
                    <p>Total Trips</p>
                </div>
            </div>
        </div>

        <!-- Search and Filters -->
        <div class="filters-section">
            <form method="GET" class="filters-form">
                <div class="search-bar">
                    <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" 
                           placeholder="Search trips by name or destination...">
                    <button type="submit"><i class="fas fa-search"></i></button>
                </div>
                
                <div class="filter-controls">
                    <div class="filter-group">
                        <label><i class="fas fa-filter"></i> Status</label>
                        <select name="status" onchange="this.form.submit()">
                            <option value="all" <?= $status == 'all' ? 'selected' : '' ?>>All Trips</option>
                            <option value="planning" <?= $status == 'planning' ? 'selected' : '' ?>>Planning</option>
                            <option value="ongoing" <?= $status == 'ongoing' ? 'selected' : '' ?>>Ongoing</option>
                            <option value="completed" <?= $status == 'completed' ? 'selected' : '' ?>>Completed</option>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label><i class="fas fa-sort"></i> Sort By</label>
                        <select name="sort" onchange="this.form.submit()">
                            <option value="start_date_desc" <?= $sort == 'start_date_desc' ? 'selected' : '' ?>>Start Date (Newest)</option>
                            <option value="start_date_asc" <?= $sort == 'start_date_asc' ? 'selected' : '' ?>>Start Date (Oldest)</option>
                            <option value="end_date_desc" <?= $sort == 'end_date_desc' ? 'selected' : '' ?>>End Date (Newest)</option>
                            <option value="end_date_asc" <?= $sort == 'end_date_asc' ? 'selected' : '' ?>>End Date (Oldest)</option>
                            <option value="budget_desc" <?= $sort == 'budget_desc' ? 'selected' : '' ?>>Budget (High to Low)</option>
                            <option value="budget_asc" <?= $sort == 'budget_asc' ? 'selected' : '' ?>>Budget (Low to High)</option>
                            <option value="name_asc" <?= $sort == 'name_asc' ? 'selected' : '' ?>>Name (A-Z)</option>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label><i class="fas fa-tags"></i> Filter By</label>
                        <select name="filter_type" onchange="this.form.submit()">
                            <option value="">All Types</option>
                            <option value="solo" <?= $filter_type == 'solo' ? 'selected' : '' ?>>Solo Travel</option>
                            <option value="family" <?= $filter_type == 'family' ? 'selected' : '' ?>>Family</option>
                            <option value="business" <?= $filter_type == 'business' ? 'selected' : '' ?>>Business</option>
                            <option value="adventure" <?= $filter_type == 'adventure' ? 'selected' : '' ?>>Adventure</option>
                        </select>
                    </div>
                </div>
            </form>
            
            <div class="create-trip-btn">
                <a href="../trip/create_trip.php" class="btn-primary">
                    <i class="fas fa-plus"></i> Create New Trip
                </a>
            </div>
        </div>

        <!-- Trips Grid -->
        <?php if(empty($trips)): ?>
            <div class="empty-state">
                <i class="fas fa-suitcase"></i>
                <h3>No trips found</h3>
                <p><?= $search ? 'Try a different search term' : 'Start planning your first trip!' ?></p>
                <a href="../trip/create_trip.php" class="btn-primary">Create Your First Trip</a>
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
                    
                    // Status badge
                    $status_classes = [
                        'planning' => 'status-planning',
                        'ongoing' => 'status-ongoing',
                        'completed' => 'status-completed'
                    ];
                ?>
                <div class="trip-card-large">
                    <div class="trip-card-header">
                        <div class="trip-status">
                            <span class="status-badge <?= $status_classes[$trip['status']] ?>">
                                <?= ucfirst($trip['status']) ?>
                            </span>
                        </div>
                        <div class="trip-actions">
                            <a href="../trip/itinerary_view.php?trip_id=<?= $trip['id'] ?>" class="btn-icon" title="View">
                                <i class="fas fa-eye"></i>
                            </a>
                            <a href="../trip/itinerary_builder.php?trip_id=<?= $trip['id'] ?>" class="btn-icon" title="Edit">
                                <i class="fas fa-edit"></i>
                            </a>
                            <button class="btn-icon delete-trip" data-id="<?= $trip['id'] ?>" title="Delete">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
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
                                <span><?= $section_count ?> sections</span>
                            </div>
                        </div>
                        
                        <?php if($trip['total_budget']): ?>
                        <div class="trip-budget">
                            <i class="fas fa-wallet"></i>
                            <strong>Budget:</strong> $<?= number_format($trip['total_budget'], 2) ?>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="trip-card-footer">
                        <span class="created-date">
                            Created: <?= date('M d, Y', strtotime($trip['created_at'])) ?>
                        </span>
                        <a href="../trip/itinerary_view.php?trip_id=<?= $trip['id'] ?>" class="btn-small">
                            View Details →
                        </a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <script>
        // Delete trip confirmation
        document.querySelectorAll('.delete-trip').forEach(btn => {
            btn.addEventListener('click', function() {
                const tripId = this.dataset.id;
                if(confirm('Are you sure you want to delete this trip? This action cannot be undone.')) {
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
                            this.closest('.trip-card-large').remove();
                        } else {
                            alert('Error deleting trip: ' + data.message);
                        }
                    });
                }
            });
        });
        
        // Real-time search
        let searchTimeout;
        document.querySelector('[name="search"]').addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                this.form.submit();
            }, 500);
        });
    </script>
</body>
</html>