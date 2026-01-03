<?php
session_start();
require_once '../db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $trip_name = $_POST['trip_name'];
    $destination = $_POST['destination'];
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    $travelers = $_POST['travelers'];
    $budget = $_POST['budget'];
    
    $stmt = $pdo->prepare("INSERT INTO trips (user_id, trip_name, destination, start_date, end_date, travelers, total_budget) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$_SESSION['user_id'], $trip_name, $destination, $start_date, $end_date, $travelers, $budget]);
    
    $trip_id = $pdo->lastInsertId();
    header("Location: itinerary_builder.php?trip_id=$trip_id");
    exit();
}

// Fetch suggested places
$suggestions = $pdo->query("SELECT DISTINCT city, country FROM activities ORDER BY popularity DESC LIMIT 10")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Plan New Trip - Globetrotter</title>
    <link rel="stylesheet" href="../assets/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="container">
        <div class="form-header">
            <h1><i class="fas fa-map-marked-alt"></i> Plan a New Trip</h1>
            <a href="../dashboard/landing.php" class="btn-back">← Back to Home</a>
        </div>

        <form method="POST" class="trip-form">
            <div class="form-group">
                <label for="trip_name"><i class="fas fa-signature"></i> Trip Name</label>
                <input type="text" id="trip_name" name="trip_name" placeholder="e.g., Summer Europe Adventure 2024" required>
            </div>

            <div class="form-group">
                <label for="destination"><i class="fas fa-map-pin"></i> Destination</label>
                <input type="text" id="destination" name="destination" list="suggestions" placeholder="Enter city or country" required>
                <datalist id="suggestions">
                    <?php foreach($suggestions as $suggestion): ?>
                        <option value="<?= htmlspecialchars($suggestion['city'] . ', ' . $suggestion['country']) ?>">
                    <?php endforeach; ?>
                </datalist>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="start_date"><i class="fas fa-calendar-plus"></i> Start Date</label>
                    <input type="date" id="start_date" name="start_date" required>
                </div>

                <div class="form-group">
                    <label for="end_date"><i class="fas fa-calendar-minus"></i> End Date</label>
                    <input type="date" id="end_date" name="end_date" required>
                </div>

                <div class="form-group">
                    <label for="travelers"><i class="fas fa-users"></i> Number of People</label>
                    <select id="travelers" name="travelers" required>
                        <?php for($i = 1; $i <= 20; $i++): ?>
                            <option value="<?= $i ?>" <?= $i == 1 ? 'selected' : '' ?>><?= $i ?> <?= $i == 1 ? 'person' : 'people' ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label for="budget"><i class="fas fa-wallet"></i> Estimated Budget (optional)</label>
                <div class="input-with-icon">
                    <span class="currency">$</span>
                    <input type="number" id="budget" name="budget" placeholder="0.00" step="0.01">
                </div>
            </div>

            <!-- Suggested Places -->
            <div class="suggestions-box">
                <h3><i class="fas fa-lightbulb"></i> Popular Destinations</h3>
                <div class="suggestions-grid">
                    <?php foreach($suggestions as $suggestion): ?>
                        <div class="suggestion-card" onclick="document.getElementById('destination').value = '<?= htmlspecialchars($suggestion['city'] . ', ' . $suggestion['country']) ?>'">
                            <i class="fas fa-map-marker-alt"></i>
                            <span><?= htmlspecialchars($suggestion['city']) ?>, <?= htmlspecialchars($suggestion['country']) ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn-primary">
                    <i class="fas fa-arrow-right"></i> Continue to Itinerary Builder
                </button>
                <a href="../dashboard/trip_list.php" class="btn-secondary">Cancel</a>
            </div>
        </form>
    </div>

    <script>
        // Set minimum date to today
        const today = new Date().toISOString().split('T')[0];
        document.getElementById('start_date').min = today;
        document.getElementById('end_date').min = today;

        // Update end date min when start date changes
        document.getElementById('start_date').addEventListener('change', function() {
            document.getElementById('end_date').min = this.value;
        });
    </script>
</body>
</html>