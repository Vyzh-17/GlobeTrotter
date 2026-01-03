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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Globetrotter - Travel Planner</title>
    <link rel="stylesheet" href="../assets/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <header>
        <nav>
            <div class="logo">🌍 Globetrotter</div>
            <div class="nav-links">
                <a href="landing.php" class="active">Home</a>
                <a href="../trip/create_trip.php">Plan Trip</a>
                <a href="trip_list.php">My Trips</a>
                <a href="../explore/activities.php">Explore</a>
                <a href="../community/chat.php">Community</a>
                <a href="../profile/profile.php">Profile</a>
            </div>
        </nav>
    </header>

    <main class="landing-container">
        <!-- Banner Section -->
        <section class="banner">
            <div class="banner-content">
                <h1>Plan Your Perfect Adventure</h1>
                <p>Discover amazing destinations and create unforgettable memories</p>
            </div>
        </section>

        <!-- Search Bar -->
        <section class="search-section">
            <div class="search-box">
                <input type="text" id="searchInput" placeholder="Search destinations, activities, or trips...">
                <button id="searchBtn"><i class="fas fa-search"></i> Search</button>
            </div>
            
            <div class="filters">
                <!-- Region Selection -->
                <div class="filter-group">
                    <label>Top Regions:</label>
                    <select id="regionFilter">
                        <option value="">All Regions</option>
                        <?php foreach($regions as $region): ?>
                            <option value="<?= htmlspecialchars($region['region']) ?>">
                                <?= htmlspecialchars($region['region']) ?> (<?= $region['count'] ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <!-- Sort By -->
                <div class="filter-group">
                    <label>Sort By:</label>
                    <select id="sortFilter">
                        <option value="popular">Most Popular</option>
                        <option value="newest">Newest</option>
                        <option value="budget_low">Budget (Low to High)</option>
                        <option value="budget_high">Budget (High to Low)</option>
                    </select>
                </div>
                
                <!-- Filter By -->
                <div class="filter-group">
                    <label>Filter By:</label>
                    <select id="categoryFilter">
                        <option value="">All Categories</option>
                        <option value="adventure">Adventure</option>
                        <option value="beach">Beach</option>
                        <option value="cultural">Cultural</option>
                        <option value="family">Family</option>
                    </select>
                </div>
            </div>
        </section>

        <!-- Previous Trips Section -->
        <?php if(!empty($previous_trips)): ?>
        <section class="previous-trips">
            <h2><i class="fas fa-history"></i> Your Previous Trips</h2>
            <div class="trip-grid">
                <?php foreach($previous_trips as $trip): ?>
                <div class="trip-card">
                    <h3><?= htmlspecialchars($trip['trip_name']) ?></h3>
                    <p><?= htmlspecialchars($trip['destination']) ?></p>
                    <p><?= date('M Y', strtotime($trip['start_date'])) ?></p>
                </div>
                <?php endforeach; ?>
            </div>
        </section>
        <?php endif; ?>

        <!-- Plan a Trip CTA -->
        <section class="plan-cta">
            <div class="cta-content">
                <h2>Ready for your next adventure?</h2>
                <a href="../trip/create_trip.php" class="btn-primary">
                    <i class="fas fa-plus"></i> Plan a New Trip
                </a>
            </div>
        </section>

        <!-- Trending Activities -->
        <section class="trending-activities">
            <h2><i class="fas fa-fire"></i> Trending Activities</h2>
            <div class="activity-grid">
                <?php foreach($activities as $activity): ?>
                <div class="activity-card">
                    <div class="activity-icon">
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
                    <h4><?= htmlspecialchars($activity['name']) ?></h4>
                    <p><?= htmlspecialchars($activity['city']) ?>, <?= htmlspecialchars($activity['country']) ?></p>
                    <span class="activity-type"><?= ucfirst($activity['type']) ?></span>
                </div>
                <?php endforeach; ?>
            </div>
        </section>
    </main>

    <script>
        // Search functionality
        document.getElementById('searchBtn').addEventListener('click', function() {
            const query = document.getElementById('searchInput').value;
            if(query.trim()) {
                window.location.href = `../explore/activities.php?search=${encodeURIComponent(query)}`;
            }
        });

        // Filter handlers
        document.getElementById('regionFilter').addEventListener('change', filterResults);
        document.getElementById('sortFilter').addEventListener('change', filterResults);
        document.getElementById('categoryFilter').addEventListener('change', filterResults);

        function filterResults() {
            const filters = {
                region: document.getElementById('regionFilter').value,
                sort: document.getElementById('sortFilter').value,
                category: document.getElementById('categoryFilter').value
            };
            console.log('Filters applied:', filters);
            // In real implementation, you would make an AJAX call here
        }
    </script>
</body>
</html>