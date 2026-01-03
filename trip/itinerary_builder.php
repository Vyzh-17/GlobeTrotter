<?php
session_start();
require_once '../db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit();
}

$trip_id = $_GET['trip_id'] ?? null;
if (!$trip_id) {
    header('Location: create_trip.php');
    exit();
}

// Fetch trip details
$stmt = $pdo->prepare("SELECT * FROM trips WHERE id = ? AND user_id = ?");
$stmt->execute([$trip_id, $_SESSION['user_id']]);
$trip = $stmt->fetch();

if (!$trip) {
    die('Trip not found or access denied');
}

// Fetch existing sections
$sections = $pdo->prepare("SELECT * FROM itinerary_sections WHERE trip_id = ? ORDER BY sequence");
$sections->execute([$trip_id]);
$sections = $sections->fetchAll();

// Add new section
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_section'])) {
    $section_type = $_POST['section_type'];
    $title = $_POST['title'];
    $description = $_POST['description'];
    $location = $_POST['location'];
    $start_datetime = $_POST['start_datetime'];
    $end_datetime = $_POST['end_datetime'];
    $budget = $_POST['budget'] ?: 0;
    
    // Get next sequence number
    $seq = $pdo->query("SELECT MAX(sequence) as max_seq FROM itinerary_sections WHERE trip_id = $trip_id")->fetch()['max_seq'] ?? 0;
    $seq++;
    
    $stmt = $pdo->prepare("INSERT INTO itinerary_sections (trip_id, section_type, title, description, location, start_datetime, end_datetime, budget, sequence) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$trip_id, $section_type, $title, $description, $location, $start_datetime, $end_datetime, $budget, $seq]);
    
    header("Location: ?trip_id=$trip_id");
    exit();
}

// Calculate total budget
$total_budget = $pdo->query("SELECT SUM(budget) as total FROM itinerary_sections WHERE trip_id = $trip_id")->fetch()['total'] ?? 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Itinerary Builder - <?= htmlspecialchars($trip['trip_name']) ?></title>
    <link rel="stylesheet" href="../assets/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="container">
        <div class="itinerary-header">
            <h1><i class="fas fa-road"></i> Itinerary Builder</h1>
            <div class="trip-info">
                <h2><?= htmlspecialchars($trip['trip_name']) ?></h2>
                <p><?= htmlspecialchars($trip['destination']) ?> • <?= date('M d', strtotime($trip['start_date'])) ?> - <?= date('M d, Y', strtotime($trip['end_date'])) ?></p>
                <p class="budget-display">Total Budget: $<span id="totalBudget"><?= number_format($total_budget, 2) ?></span></p>
            </div>
            <div class="header-actions">
                <a href="itinerary_view.php?trip_id=<?= $trip_id ?>" class="btn-secondary"><i class="fas fa-eye"></i> View Itinerary</a>
                <a href="../dashboard/trip_list.php" class="btn-back">← Back to Trips</a>
            </div>
        </div>

        <div class="itinerary-builder">
            <!-- Add New Section Form -->
            <div class="add-section-form">
                <h3><i class="fas fa-plus-circle"></i> Add New Section</h3>
                <form method="POST">
                    <input type="hidden" name="add_section" value="1">
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label><i class="fas fa-tag"></i> Section Type</label>
                            <select name="section_type" required>
                                <option value="">Select Type</option>
                                <option value="travel">✈️ Travel</option>
                                <option value="accommodation">🏨 Accommodation</option>
                                <option value="activity">🎯 Activity</option>
                                <option value="food">🍽️ Food</option>
                                <option value="other">📝 Other</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label><i class="fas fa-heading"></i> Title</label>
                            <input type="text" name="title" placeholder="e.g., Flight to Paris" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label><i class="fas fa-align-left"></i> Description</label>
                        <textarea name="description" rows="2" placeholder="Add details..."></textarea>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label><i class="fas fa-map-marker-alt"></i> Location</label>
                            <input type="text" name="location" placeholder="Address or place name">
                        </div>
                        
                        <div class="form-group">
                            <label><i class="fas fa-calendar"></i> Start Date & Time</label>
                            <input type="datetime-local" name="start_datetime">
                        </div>
                        
                        <div class="form-group">
                            <label><i class="fas fa-calendar"></i> End Date & Time</label>
                            <input type="datetime-local" name="end_datetime">
                        </div>
                    </div>

                    <div class="form-group">
                        <label><i class="fas fa-wallet"></i> Budget for this section</label>
                        <div class="input-with-icon">
                            <span class="currency">$</span>
                            <input type="number" name="budget" step="0.01" placeholder="0.00">
                        </div>
                    </div>

                    <button type="submit" class="btn-primary">
                        <i class="fas fa-plus"></i> Add Section
                    </button>
                </form>
            </div>

            <!-- Existing Sections -->
            <div class="sections-list">
                <h3><i class="fas fa-list"></i> Itinerary Sections</h3>
                
                <?php if(empty($sections)): ?>
                    <div class="empty-state">
                        <i class="fas fa-calendar-plus"></i>
                        <p>No sections added yet. Start building your itinerary!</p>
                    </div>
                <?php else: ?>
                    <div class="sections-container">
                        <?php foreach($sections as $section): 
                            $icons = [
                                'travel' => 'fa-plane',
                                'accommodation' => 'fa-hotel',
                                'activity' => 'fa-hiking',
                                'food' => 'fa-utensils',
                                'other' => 'fa-sticky-note'
                            ];
                        ?>
                        <div class="section-card" data-type="<?= $section['section_type'] ?>">
                            <div class="section-header">
                                <div class="section-type">
                                    <i class="fas <?= $icons[$section['section_type']] ?>"></i>
                                    <span><?= ucfirst($section['section_type']) ?></span>
                                </div>
                                <div class="section-actions">
                                    <button class="btn-icon edit-section" data-id="<?= $section['id'] ?>">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn-icon delete-section" data-id="<?= $section['id'] ?>">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                            
                            <h4><?= htmlspecialchars($section['title']) ?></h4>
                            
                            <?php if($section['description']): ?>
                                <p class="section-desc"><?= htmlspecialchars($section['description']) ?></p>
                            <?php endif; ?>
                            
                            <div class="section-details">
                                <?php if($section['location']): ?>
                                    <div class="detail">
                                        <i class="fas fa-map-marker-alt"></i>
                                        <span><?= htmlspecialchars($section['location']) ?></span>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if($section['start_datetime']): ?>
                                    <div class="detail">
                                        <i class="fas fa-clock"></i>
                                        <span><?= date('M d, Y H:i', strtotime($section['start_datetime'])) ?></span>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if($section['budget']): ?>
                                    <div class="detail budget">
                                        <i class="fas fa-wallet"></i>
                                        <span>$<?= number_format($section['budget'], 2) ?></span>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <?php if($section['notes']): ?>
                                <div class="section-notes">
                                    <strong>Notes:</strong> <?= htmlspecialchars($section['notes']) ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        // Budget calculation
        function updateTotalBudget() {
            let total = 0;
            document.querySelectorAll('.detail.budget span').forEach(el => {
                const amount = parseFloat(el.textContent.replace('$', '')) || 0;
                total += amount;
            });
            document.getElementById('totalBudget').textContent = total.toFixed(2);
        }
        
        // Initialize
        updateTotalBudget();
        
        // Delete section
        document.querySelectorAll('.delete-section').forEach(btn => {
            btn.addEventListener('click', function() {
                const sectionId = this.dataset.id;
                if(confirm('Are you sure you want to delete this section?')) {
                    // AJAX request to delete section
                    fetch('delete_section.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            section_id: sectionId,
                            trip_id: <?= $trip_id ?>
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if(data.success) {
                            this.closest('.section-card').remove();
                            updateTotalBudget();
                        }
                    });
                }
            });
        });
        
        // Drag and drop for reordering (basic implementation)
        let draggedSection = null;
        
        document.querySelectorAll('.section-card').forEach(section => {
            section.setAttribute('draggable', true);
            
            section.addEventListener('dragstart', function(e) {
                draggedSection = this;
                this.style.opacity = '0.5';
            });
            
            section.addEventListener('dragend', function(e) {
                this.style.opacity = '1';
                draggedSection = null;
            });
        });
        
        document.querySelector('.sections-container').addEventListener('dragover', function(e) {
            e.preventDefault();
        });
        
        document.querySelector('.sections-container').addEventListener('drop', function(e) {
            e.preventDefault();
            if(draggedSection && draggedSection !== e.target.closest('.section-card')) {
                const targetSection = e.target.closest('.section-card');
                if(targetSection) {
                    this.insertBefore(draggedSection, targetSection);
                } else {
                    this.appendChild(draggedSection);
                }
                // In real implementation, save new order to database
            }
        });
    </script>
</body>
</html>