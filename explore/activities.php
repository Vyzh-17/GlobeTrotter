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
            padding: 8px 15px;
            border-radius: 20px;
            cursor: pointer;
            font-size: 0.9rem;
            transition: all 0.3s;
        }
        
        .filter-tag:hover {
            background: #e2e6ea;
        }
        
        .filter-tag.active {
            background: #667eea;
            color: white;
        }
        
        .price-range {
            display: flex;
            gap: 10px;
        }
        
        .price-range input {
            flex: 1;
            padding: 8px;
            border: 2px solid #e0e0e0;
            border-radius: 6px;
        }
        
        .results-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .results-count {
            color: #666;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="page-header">
            <h1><i class="fas fa-search"></i> Explore Activities & Cities</h1>
            <a href="../dashboard/landing.php" class="btn-back">← Back to Home</a>
        </div>

        <!-- Interactive Map Placeholder -->
        <div class="map-container">
            <div class="map-content">
                <i class="fas fa-globe-americas fa-3x"></i>
                <h2 style="margin-top: 15px;">Explore Destinations Worldwide</h2>
                <p style="opacity: 0.9;">Click on any city to see activities</p>
            </div>
        </div>

        <!-- Main Search -->
        <div class="search-section">
            <form method="GET" class="search-form">
                <div class="search-bar large">
                    <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" 
                           placeholder="Search activities, cities, or countries...">
                    <button type="submit"><i class="fas fa-search"></i> Search</button>
                </div>
            </form>
        </div>

        <div class="explore-layout">
            <!-- Filters Sidebar -->
            <div class="filters-sidebar">
                <h2><i class="fas fa-filter"></i> Filters</h2>
                
                <div class="filter-section">
                    <h3>City</h3>
                    <div class="search-bar small">
                        <input type="text" id="citySearch" placeholder="Search city...">
                    </div>
                    <div class="filter-tags" id="cityTags">
                        <?php foreach($cities as $cityItem): ?>
                            <button type="button" class="filter-tag <?= $city == $cityItem['city'] ? 'active' : '' ?>" 
                                    data-filter="city" data-value="<?= htmlspecialchars($cityItem['city']) ?>">
                                <?= htmlspecialchars($cityItem['city']) ?>
                            </button>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <div class="filter-section">
                    <h3>Country</h3>
                    <div class="filter-tags">
                        <?php foreach($countries as $countryItem): ?>
                            <button type="button" class="filter-tag <?= $country == $countryItem['country'] ? 'active' : '' ?>" 
                                    data-filter="country" data-value="<?= htmlspecialchars($countryItem['country']) ?>">
                                <?= htmlspecialchars($countryItem['country']) ?>
                            </button>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <div class="filter-section">
                    <h3>Activity Type</h3>
                    <div class="filter-tags">
                        <button type="button" class="filter-tag <?= $type == '' ? 'active' : '' ?>" 
                                data-filter="type" data-value="">All</button>
                        <?php foreach($types as $typeItem): ?>
                            <button type="button" class="filter-tag <?= $type == $typeItem['type'] ? 'active' : '' ?>" 
                                    data-filter="type" data-value="<?= htmlspecialchars($typeItem['type']) ?>">
                                <?= ucfirst($typeItem['type']) ?>
                            </button>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <div class="filter-section">
                    <h3>Price Range</h3>
                    <div class="price-range">
                        <input type="number" name="min_cost" placeholder="Min $" value="<?= htmlspecialchars($min_cost) ?>">
                        <span>to</span>
                        <input type="number" name="max_cost" placeholder="Max $" value="<?= htmlspecialchars($max_cost) ?>">
                    </div>
                </div>
                
                <div class="filter-section">
                    <h3>Duration</h3>
                    <div class="filter-tags">
                        <button type="button" class="filter-tag <?= $duration == '' ? 'active' : '' ?>" 
                                data-filter="duration" data-value="">Any</button>
                        <button type="button" class="filter-tag <?= $duration == 'short' ? 'active' : '' ?>" 
                                data-filter="duration" data-value="short">Short (≤2h)</button>
                        <button type="button" class="filter-tag <?= $duration == 'medium' ? 'active' : '' ?>" 
                                data-filter="duration" data-value="medium">Medium (3-6h)</button>
                        <button type="button" class="filter-tag <?= $duration == 'long' ? 'active' : '' ?>" 
                                data-filter="duration" data-value="long">Long (>6h)</button>
                    </div>
                </div>
                
                <div class="filter-section">
                    <h3>Sort By</h3>
                    <select name="sort" onchange="applyFilters()" style="width: 100%;">
                        <option value="popularity" <?= $sort == 'popularity' ? 'selected' : '' ?>>Most Popular</option>
                        <option value="name_asc" <?= $sort == 'name_asc' ? 'selected' : '' ?>>Name (A-Z)</option>
                        <option value="name_desc" <?= $sort == 'name_desc' ? 'selected' : '' ?>>Name (Z-A)</option>
                        <option value="cost_asc" <?= $sort == 'cost_asc' ? 'selected' : '' ?>>Price (Low to High)</option>
                        <option value="cost_desc" <?= $sort == 'cost_desc' ? 'selected' : '' ?>>Price (High to Low)</option>
                        <option value="duration_asc" <?= $sort == 'duration_asc' ? 'selected' : '' ?>>Duration (Short to Long)</option>
                        <option value="duration_desc" <?= $sort == 'duration_desc' ? 'selected' : '' ?>>Duration (Long to Short)</option>
                    </select>
                </div>
                
                <div class="filter-actions">
                    <button type="button" onclick="applyFilters()" class="btn-primary" style="width: 100%;">
                        <i class="fas fa-filter"></i> Apply Filters
                    </button>
                    <button type="button" onclick="resetFilters()" class="btn-secondary" style="width: 100%; margin-top: 10px;">
                        <i class="fas fa-redo"></i> Reset All
                    </button>
                </div>
            </div>

            <!-- Results Section -->
            <div class="results-section">
                <div class="results-header">
                    <h2>
                        <?php if($search || $city || $country || $type): ?>
                            Search Results
                        <?php else: ?>
                            Popular Activities
                        <?php endif; ?>
                    </h2>
                    <div class="results-count">
                        <?= count($activities) ?> activity<?= count($activities) != 1 ? 'ies' : '' ?> found
                    </div>
                </div>
                
                <!-- City Search Section -->
                <div class="city-search">
                    <h3><i class="fas fa-city"></i> Browse by City</h3>
                    <div class="city-grid">
                        <?php 
                        $popularCities = array_slice($cities, 0, 8);
                        foreach($popularCities as $cityItem): 
                            $cityActivities = $pdo->prepare("SELECT COUNT(*) as count FROM activities WHERE city = ?");
                            $cityActivities->execute([$cityItem['city']]);
                            $count = $cityActivities->fetch()['count'];
                        ?>
                            <div class="city-card" onclick="selectCity('<?= htmlspecialchars($cityItem['city']) ?>')">
                                <i class="fas fa-city"></i>
                                <h4><?= htmlspecialchars($cityItem['city']) ?></h4>
                                <small><?= $count ?> activities</small>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Activities Grid -->
                <?php if(empty($activities) && empty($search) && empty($city) && empty($country) && empty($type)): ?>
                    <div class="activities-grid">
                        <?php foreach($popular as $activity): ?>
                            <?php include 'activity_card.php'; ?>
                        <?php endforeach; ?>
                    </div>
                <?php elseif(empty($activities)): ?>
                    <div class="empty-state">
                        <i class="fas fa-search"></i>
                        <h3>No activities found</h3>
                        <p>Try adjusting your search filters</p>
                        <button onclick="resetFilters()" class="btn-primary">Reset Filters</button>
                    </div>
                <?php else: ?>
                    <div class="activities-grid">
                        <?php foreach($activities as $activity): ?>
                            <div class="activity-detail-card">
                                <div class="activity-image">
                                    <?php 
                                    $icons = [
                                        'sightseeing' => 'fa-landmark',
                                        'adventure' => 'fa-mountain',
                                        'food' => 'fa-utensils',
                                        'culture' => 'fa-theater-masks',
                                        'shopping' => 'fa-shopping-bag',
                                        'relaxation' => 'fa-spa'
                                    ];
                                    ?>
                                    <i class="fas <?= $icons[$activity['type']] ?? 'fa-map-marker-alt' ?>"></i>
                                </div>
                                
                                <div class="activity-content">
                                    <div class="activity-meta">
                                        <span class="activity-type"><?= ucfirst($activity['type']) ?></span>
                                        <span class="activity-cost">$<?= number_format($activity['avg_cost'], 0) ?></span>
                                    </div>
                                    
                                    <h3><?= htmlspecialchars($activity['name']) ?></h3>
                                    
                                    <div class="activity-location">
                                        <i class="fas fa-map-marker-alt"></i>
                                        <?= htmlspecialchars($activity['city']) ?>, <?= htmlspecialchars($activity['country']) ?>
                                    </div>
                                    
                                    <?php if($activity['description']): ?>
                                        <p class="activity-description">
                                            <?= htmlspecialchars(substr($activity['description'], 0, 100)) ?>...
                                        </p>
                                    <?php endif; ?>
                                    
                                    <div class="activity-stats">
                                        <span><i class="fas fa-clock"></i> <?= $activity['duration_hours'] ?> hours</span>
                                        <span><i class="fas fa-fire"></i> <?= $activity['popularity'] ?> views</span>
                                    </div>
                                    
                                    <?php if(isset($_SESSION['user_id'])): ?>
                                        <button class="add-to-trip" data-activity-id="<?= $activity['id'] ?>">
                                            <i class="fas fa-plus"></i> Add to Trip
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        // Filter functionality
        const filterTags = document.querySelectorAll('.filter-tag[data-filter]');
        filterTags.forEach(tag => {
            tag.addEventListener('click', function() {
                // Update active state
                document.querySelectorAll(`.filter-tag[data-filter="${this.dataset.filter}"]`).forEach(t => {
                    t.classList.remove('active');
                });
                this.classList.add('active');
                
                // Don't apply immediately, wait for Apply button
            });
        });
        
        function applyFilters() {
            const form = document.createElement('form');
            form.method = 'GET';
            
            // Add search
            const searchInput = document.querySelector('[name="search"]');
            if(searchInput.value) {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'search';
                input.value = searchInput.value;
                form.appendChild(input);
            }
            
            // Add active filters
            document.querySelectorAll('.filter-tag.active').forEach(tag => {
                if(tag.dataset.value) {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = tag.dataset.filter;
                    input.value = tag.dataset.value;
                    form.appendChild(input);
                }
            });
            
            // Add price range
            const minCost = document.querySelector('[name="min_cost"]').value;
            const maxCost = document.querySelector('[name="max_cost"]').value;
            if(minCost) {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'min_cost';
                input.value = minCost;
                form.appendChild(input);
            }
            if(maxCost) {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'max_cost';
                input.value = maxCost;
                form.appendChild(input);
            }
            
            // Add sort
            const sortSelect = document.querySelector('[name="sort"]');
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'sort';
            input.value = sortSelect.value;
            form.appendChild(input);
            
            document.body.appendChild(form);
            form.submit();
        }
        
        function resetFilters() {
            window.location.href = 'activities.php';
        }
        
        function selectCity(cityName) {
            const form = document.createElement('form');
            form.method = 'GET';
            
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'city';
            input.value = cityName;
            form.appendChild(input);
            
            document.body.appendChild(form);
            form.submit();
        }
        
        // City search filter
        document.getElementById('citySearch').addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            const cityTags = document.querySelectorAll('#cityTags .filter-tag');
            
            cityTags.forEach(tag => {
                const cityName = tag.dataset.value.toLowerCase();
                if(cityName.includes(searchTerm)) {
                    tag.style.display = 'inline-block';
                } else {
                    tag.style.display = 'none';
                }
            });
        });
        
        // Add to trip functionality
        document.querySelectorAll('.add-to-trip').forEach(btn => {
            btn.addEventListener('click', function() {
                const activityId = this.dataset.activityId;
                if(confirm('Would you like to add this activity to one of your trips?')) {
                    // Show trip selection modal
                    fetch('../trip/get_user_trips.php')
                        .then(response => response.json())
                        .then(trips => {
                            if(trips.length === 0) {
                                alert('Please create a trip first!');
                                window.location.href = '../trip/create_trip.php';
                            } else {
                                const tripList = trips.map(trip => 
                                    `<option value="${trip.id}">${trip.trip_name} - ${trip.destination}</option>`
                                ).join('');
                                
                                const modal = document.createElement('div');
                                modal.innerHTML = `
                                    <div style="position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,0.5);display:flex;align-items:center;justify-content:center;z-index:1000;">
                                        <div style="background:white;padding:30px;border-radius:10px;max-width:500px;width:90%;">
                                            <h3>Select Trip</h3>
                                            <select id="tripSelect" style="width:100%;padding:10px;margin:15px 0;">
                                                ${tripList}
                                            </select>
                                            <div style="display:flex;gap:10px;margin-top:20px;">
                                                <button onclick="addActivityToTrip(${activityId})" style="padding:10px 20px;background:#48bb78;color:white;border:none;border-radius:5px;cursor:pointer;">
                                                    Add to Trip
                                                </button>
                                                <button onclick="this.closest('div[style^=\"position:fixed\"]').remove()" style="padding:10px 20px;background:#e53e3e;color:white;border:none;border-radius:5px;cursor:pointer;">
                                                    Cancel
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                `;
                                document.body.appendChild(modal.firstElementChild);
                            }
                        });
                }
            });
        });
        
        // Global function for modal
        window.addActivityToTrip = function(activityId) {
            const tripId = document.getElementById('tripSelect').value;
            
            fetch('../trip/add_activity.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    trip_id: tripId,
                    activity_id: activityId
                })
            })
            .then(response => response.json())
            .then(data => {
                if(data.success) {
                    alert('Activity added to trip successfully!');
                    document.querySelector('div[style^="position:fixed"]').remove();
                } else {
                    alert('Error: ' + data.message);
                }
            });
        };
    </script>
</body>
</html>