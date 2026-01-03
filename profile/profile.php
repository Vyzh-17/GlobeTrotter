<?php
session_start();
require_once '../db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch user data
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if (!$user) {
    session_destroy();
    header('Location: ../auth/login.php');
    exit();
}

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = $_POST['full_name'];
    $email = $_POST['email'];
    $bio = $_POST['bio'];
    $country = $_POST['country'];
    
    // Handle profile picture upload
    $profile_pic = $user['profile_pic'];
    if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = '../uploads/profiles/';
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        
        $fileName = uniqid() . '_' . basename($_FILES['profile_pic']['name']);
        $targetFile = $uploadDir . $fileName;
        
        if (move_uploaded_file($_FILES['profile_pic']['tmp_name'], $targetFile)) {
            // Delete old profile picture if exists
            if ($profile_pic && file_exists('../' . $profile_pic)) {
                unlink('../' . $profile_pic);
            }
            $profile_pic = 'uploads/profiles/' . $fileName;
        }
    }
    
    $stmt = $pdo->prepare("UPDATE users SET full_name = ?, email = ?, bio = ?, country = ?, profile_pic = ? WHERE id = ?");
    $stmt->execute([$full_name, $email, $bio, $country, $profile_pic, $user_id]);
    
    $_SESSION['success'] = 'Profile updated successfully!';
    header('Location: profile.php');
    exit();
}

// Fetch user's trips
$trips = $pdo->prepare("
    SELECT * FROM trips 
    WHERE user_id = ? 
    ORDER BY start_date DESC
");
$trips->execute([$user_id]);
$trips = $trips->fetchAll();

// Calculate user statistics
$stats = $pdo->prepare("
    SELECT 
        COUNT(*) as total_trips,
        SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_trips,
        COUNT(DISTINCT destination) as unique_destinations,
        COALESCE(SUM(total_budget), 0) as total_spent
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
    <title>My Profile - Globetrotter</title>
    <link rel="stylesheet" href="../assets/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="container">
        <?php if(isset($_SESSION['success'])): ?>
            <div class="alert success">
                <?= $_SESSION['success'] ?>
                <?php unset($_SESSION['success']); ?>
            </div>
        <?php endif; ?>

        <div class="page-header">
            <h1><i class="fas fa-user-circle"></i> My Profile</h1>
            <a href="../dashboard/landing.php" class="btn-back">← Back to Home</a>
        </div>

        <div class="profile-container">
            <!-- Profile Edit Section -->
            <div class="profile-section">
                <h2><i class="fas fa-user-edit"></i> Profile Information</h2>
                <form method="POST" enctype="multipart/form-data" class="profile-form">
                    <div class="profile-header">
                        <div class="profile-picture">
                            <div class="picture-container">
                                <img src="<?= $user['profile_pic'] ? '../' . htmlspecialchars($user['profile_pic']) : 'https://ui-avatars.com/api/?name=' . urlencode($user['full_name'] ?? $user['username']) . '&background=667eea&color=fff' ?>" 
                                     alt="Profile Picture" id="profilePreview">
                                <label for="profileUpload" class="upload-overlay">
                                    <i class="fas fa-camera"></i>
                                </label>
                                <input type="file" id="profileUpload" name="profile_pic" accept="image/*" style="display: none;">
                            </div>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label><i class="fas fa-user"></i> Username</label>
                            <input type="text" value="<?= htmlspecialchars($user['username']) ?>" disabled>
                            <small class="helper-text">Username cannot be changed</small>
                        </div>
                        
                        <div class="form-group">
                            <label><i class="fas fa-id-card"></i> Full Name</label>
                            <input type="text" name="full_name" value="<?= htmlspecialchars($user['full_name'] ?? '') ?>" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label><i class="fas fa-envelope"></i> Email Address</label>
                        <input type="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required>
                    </div>

                    <div class="form-group">
                        <label><i class="fas fa-globe"></i> Country</label>
                        <select name="country">
                            <option value="">Select Country</option>
                            <?php
                            $countries = ['USA', 'Canada', 'UK', 'Australia', 'Germany', 'France', 'Japan', 'India', 'Brazil', 'Mexico'];
                            foreach($countries as $country): ?>
                                <option value="<?= $country ?>" <?= ($user['country'] ?? '') == $country ? 'selected' : '' ?>>
                                    <?= $country ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label><i class="fas fa-edit"></i> Bio</label>
                        <textarea name="bio" rows="4" placeholder="Tell us about yourself..."><?= htmlspecialchars($user['bio'] ?? '') ?></textarea>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn-primary">
                            <i class="fas fa-save"></i> Save Changes
                        </button>
                        <a href="../auth/logout.php" class="btn-secondary">
                            <i class="fas fa-sign-out-alt"></i> Logout
                        </a>
                    </div>
                </form>
            </div>

            <!-- User Statistics -->
            <div class="stats-section">
                <h2><i class="fas fa-chart-line"></i> My Travel Statistics</h2>
                <div class="user-stats">
                    <div class="user-stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-suitcase"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?= $stats['total_trips'] ?></h3>
                            <p>Total Trips</p>
                        </div>
                    </div>
                    
                    <div class="user-stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?= $stats['completed_trips'] ?></h3>
                            <p>Completed</p>
                        </div>
                    </div>
                    
                    <div class="user-stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-map-marked-alt"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?= $stats['unique_destinations'] ?></h3>
                            <p>Destinations</p>
                        </div>
                    </div>
                    
                    <div class="user-stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-wallet"></i>
                        </div>
                        <div class="stat-info">
                            <h3>$<?= number_format($stats['total_spent'], 0) ?></h3>
                            <p>Total Spent</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- User's Trips -->
            <div class="trips-section">
                <h2><i class="fas fa-suitcase-rolling"></i> My Trips</h2>
                <div class="tabs">
                    <button class="tab-btn active" data-tab="all">All Trips</button>
                    <button class="tab-btn" data-tab="planning">Planning</button>
                    <button class="tab-btn" data-tab="ongoing">Ongoing</button>
                    <button class="tab-btn" data-tab="completed">Completed</button>
                </div>
                
                <div class="trips-list">
                    <?php if(empty($trips)): ?>
                        <div class="empty-state">
                            <i class="fas fa-plane-slash"></i>
                            <p>You haven't planned any trips yet.</p>
                            <a href="../trip/create_trip.php" class="btn-primary">Plan Your First Trip</a>
                        </div>
                    <?php else: ?>
                        <?php foreach($trips as $trip): ?>
                            <div class="trip-item" data-status="<?= $trip['status'] ?>">
                                <div class="trip-item-header">
                                    <span class="trip-status status-badge <?= $trip['status'] == 'planning' ? 'status-planning' : ($trip['status'] == 'ongoing' ? 'status-ongoing' : 'status-completed') ?>">
                                        <?= ucfirst($trip['status']) ?>
                                    </span>
                                    <span class="trip-date">
                                        <?= date('M d, Y', strtotime($trip['start_date'])) ?> - <?= date('M d, Y', strtotime($trip['end_date'])) ?>
                                    </span>
                                </div>
                                
                                <div class="trip-item-body">
                                    <h4><?= htmlspecialchars($trip['trip_name']) ?></h4>
                                    <p class="destination">
                                        <i class="fas fa-map-marker-alt"></i>
                                        <?= htmlspecialchars($trip['destination']) ?>
                                    </p>
                                    <div class="trip-meta">
                                        <span><i class="fas fa-users"></i> <?= $trip['travelers'] ?> people</span>
                                        <?php if($trip['total_budget']): ?>
                                            <span><i class="fas fa-wallet"></i> $<?= number_format($trip['total_budget'], 2) ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <div class="trip-item-actions">
                                    <a href="../trip/itinerary_view.php?trip_id=<?= $trip['id'] ?>" class="btn-small">
                                        View Itinerary
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Profile picture preview
        document.getElementById('profileUpload').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('profilePreview').src = e.target.result;
                };
                reader.readAsDataURL(file);
            }
        });
        
        // Tab switching for trips
        document.querySelectorAll('.tab-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                // Update active tab
                document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
                this.classList.add('active');
                
                const status = this.dataset.tab;
                const tripItems = document.querySelectorAll('.trip-item');
                
                tripItems.forEach(item => {
                    if (status === 'all' || item.dataset.status === status) {
                        item.style.display = 'flex';
                    } else {
                        item.style.display = 'none';
                    }
                });
            });
        });
        
        // Initialize with all trips shown
        document.querySelectorAll('.trip-item').forEach(item => {
            item.style.display = 'flex';
        });
    </script>
</body>
</html>