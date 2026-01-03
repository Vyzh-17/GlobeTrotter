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
    
    // Fix budget handling
    $budget = isset($_POST['budget']) ? $_POST['budget'] : 0;
    if ($budget !== '' && $budget !== null) {
        // Remove any commas and non-numeric characters
        $budget = preg_replace('/[^\d.]/', '', $budget);
        $budget = floatval($budget);
    } else {
        $budget = 0;
    }
    
    // Get next sequence number
    $seq_result = $pdo->query("SELECT MAX(sequence) as max_seq FROM itinerary_sections WHERE trip_id = $trip_id")->fetch();
    $seq = $seq_result['max_seq'] ?? 0;
    $seq++;
    
    $stmt = $pdo->prepare("INSERT INTO itinerary_sections (trip_id, section_type, title, description, location, start_datetime, end_datetime, budget, sequence) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$trip_id, $section_type, $title, $description, $location, $start_datetime, $end_datetime, $budget, $seq]);
    
    header("Location: ?trip_id=$trip_id");
    exit();
}

// Delete section
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_section'])) {
    $section_id = $_POST['section_id'];
    $stmt = $pdo->prepare("DELETE FROM itinerary_sections WHERE id = ? AND trip_id = ?");
    $stmt->execute([$section_id, $trip_id]);
    
    // Reorder sequence numbers
    $remaining = $pdo->prepare("SELECT * FROM itinerary_sections WHERE trip_id = ? ORDER BY sequence");
    $remaining->execute([$trip_id]);
    $remaining_sections = $remaining->fetchAll();
    
    foreach ($remaining_sections as $index => $section) {
        $pdo->prepare("UPDATE itinerary_sections SET sequence = ? WHERE id = ?")->execute([$index + 1, $section['id']]);
    }
    
    header("Location: ?trip_id=$trip_id");
    exit();
}

// Calculate total budget
$budget_result = $pdo->query("SELECT SUM(budget) as total FROM itinerary_sections WHERE trip_id = $trip_id")->fetch();
$total_budget = $budget_result['total'] ?? 0;

// Get section statistics
$section_count = count($sections);
$activities_count = 0;
$travel_count = 0;
foreach ($sections as $section) {
    if ($section['section_type'] == 'activity') $activities_count++;
    if ($section['section_type'] == 'travel') $travel_count++;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Itinerary Builder | <?= htmlspecialchars($trip['trip_name']) ?> ✈️</title>
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
        .itinerary-container {
            max-width: 1400px;
            margin: 0 auto;
        }

        /* Header */
        .itinerary-header {
            background: var(--glass-bg);
            backdrop-filter: blur(10px);
            border-radius: 24px;
            padding: 40px;
            margin-bottom: 40px;
            box-shadow: var(--card-shadow);
            border: 1px solid rgba(255, 255, 255, 0.3);
            position: relative;
            overflow: hidden;
        }

        .itinerary-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 5px;
            background: var(--blue-gradient);
        }

        .itinerary-header h1 {
            font-size: 2.5rem;
            font-weight: 900;
            background: var(--blue-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            display: flex;
            align-items: center;
            gap: 20px;
            margin-bottom: 20px;
        }

        .trip-info {
            margin-bottom: 30px;
        }

        .trip-info h2 {
            font-size: 1.75rem;
            font-weight: 700;
            color: var(--text-dark);
            margin-bottom: 10px;
        }

        .trip-info p {
            color: var(--text-light);
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .header-stats {
            display: flex;
            gap: 30px;
            margin-top: 20px;
            flex-wrap: wrap;
        }

        .stat-badge {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 24px;
            background: var(--blue-bg);
            border-radius: 12px;
            border: 1px solid var(--blue-border);
        }

        .stat-badge i {
            font-size: 1.25rem;
            color: var(--primary-blue);
        }

        .stat-badge .stat-number {
            font-weight: 700;
            color: var(--primary-blue);
            font-size: 1.25rem;
        }

        .stat-badge .stat-label {
            color: var(--text-light);
            font-size: 0.875rem;
        }

        .header-actions {
            display: flex;
            gap: 20px;
            margin-top: 30px;
            flex-wrap: wrap;
        }

        .btn-primary, .btn-secondary, .btn-back {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px 24px;
            border-radius: 12px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .btn-primary {
            background: var(--blue-gradient);
            color: white;
            border: none;
            cursor: pointer;
        }

        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);
        }

        .btn-secondary {
            background: var(--blue-bg);
            color: var(--primary-blue);
            border: 1px solid var(--blue-border);
        }

        .btn-secondary:hover {
            background: var(--blue-border);
            transform: translateY(-2px);
        }

        .btn-back {
            background: var(--gray-border);
            color: var(--text-dark);
        }

        .btn-back:hover {
            background: #cbd5e1;
            transform: translateX(-5px);
        }

        /* Main Builder Layout */
        .builder-layout {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 40px;
        }

        @media (max-width: 1024px) {
            .builder-layout {
                grid-template-columns: 1fr;
            }
        }

        /* Left Panel - Form */
        .add-section-form {
            background: var(--glass-bg);
            backdrop-filter: blur(10px);
            border-radius: 24px;
            padding: 40px;
            box-shadow: var(--card-shadow);
            border: 1px solid rgba(255, 255, 255, 0.3);
            position: relative;
            overflow: hidden;
        }

        .add-section-form::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 5px;
            background: var(--blue-gradient);
        }

        .add-section-form h3 {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--text-dark);
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 30px;
        }

        /* Form Elements */
        .form-group {
            margin-bottom: 24px;
        }

        .form-group label {
            display: block;
            margin-bottom: 12px;
            font-weight: 600;
            color: var(--text-dark);
            font-size: 0.9375rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .form-group label i {
            color: var(--primary-blue);
            font-size: 1.125rem;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 16px 20px;
            border: 2px solid var(--gray-border);
            border-radius: 12px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: white;
            color: var(--text-dark);
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: var(--primary-blue);
            box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.1);
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
            }
        }

        /* Right Panel - Sections */
        .sections-panel {
            background: var(--glass-bg);
            backdrop-filter: blur(10px);
            border-radius: 24px;
            padding: 40px;
            box-shadow: var(--card-shadow);
            border: 1px solid rgba(255, 255, 255, 0.3);
        }

        .sections-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }

        .sections-header h3 {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--text-dark);
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .sections-header h3 i {
            color: var(--primary-blue);
        }

        /* Sections List */
        .sections-list {
            min-height: 400px;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: var(--text-light);
        }

        .empty-state i {
            font-size: 3rem;
            color: var(--gray-border);
            margin-bottom: 20px;
        }

        /* Section Card */
        .section-card {
            background: white;
            border-radius: 16px;
            padding: 24px;
            margin-bottom: 20px;
            border: 1px solid var(--gray-border);
            transition: all 0.3s ease;
        }

        .section-card:hover {
            transform: translateY(-3px);
            box-shadow: var(--card-shadow);
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 16px;
        }

        .section-type {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .type-icon {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
            color: white;
        }

        .type-travel { background: #3b82f6; }
        .type-accommodation { background: #10b981; }
        .type-activity { background: #f59e0b; }
        .type-food { background: #8b5cf6; }
        .type-other { background: #6b7280; }

        .section-type span {
            font-weight: 600;
            color: var(--text-dark);
            text-transform: capitalize;
        }

        .section-actions {
            display: flex;
            gap: 10px;
        }

        .btn-icon {
            width: 36px;
            height: 36px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--blue-bg);
            color: var(--primary-blue);
            border: none;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-icon:hover {
            background: var(--blue-border);
            transform: translateY(-2px);
        }

        .btn-icon.delete:hover {
            background: #fee2e2;
            color: #dc2626;
        }

        .section-card h4 {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--text-dark);
            margin-bottom: 12px;
        }

        .section-desc {
            color: var(--text-light);
            margin-bottom: 16px;
            line-height: 1.5;
        }

        .section-details {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            margin-bottom: 16px;
        }

        .detail {
            display: flex;
            align-items: center;
            gap: 8px;
            color: var(--text-light);
            font-size: 0.875rem;
        }

        .detail i {
            color: var(--primary-blue);
        }

        /* Budget Input */
        .input-with-icon {
            position: relative;
        }

        .currency {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            font-weight: 600;
            color: var(--text-dark);
        }

        .input-with-icon input {
            padding-left: 50px !important;
        }

        .budget-quick-options {
            display: flex;
            gap: 10px;
            margin-top: 10px;
            flex-wrap: wrap;
        }

        .budget-quick-btn {
            padding: 8px 16px;
            background: var(--blue-bg);
            color: var(--primary-blue);
            border: 1px solid var(--blue-border);
            border-radius: 8px;
            font-size: 0.875rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .budget-quick-btn:hover {
            background: var(--blue-border);
            transform: translateY(-2px);
        }

        /* Responsive */
        @media (max-width: 768px) {
            body {
                padding: 16px;
            }
            
            .itinerary-header,
            .add-section-form,
            .sections-panel {
                padding: 30px 20px;
            }
            
            .itinerary-header h1 {
                font-size: 2rem;
            }
            
            .header-actions {
                flex-direction: column;
            }
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

        .fade-in {
            animation: fadeIn 0.5s ease;
        }

        .fade-in-up {
            animation: fadeInUp 0.5s ease;
        }
    </style>
</head>
<body>
    <div class="floating-elements">
        <div class="floating-circle"></div>
        <div class="floating-circle"></div>
    </div>

    <div class="itinerary-container">
        <!-- Header -->
        <div class="itinerary-header fade-in">
            <h1><i class="fas fa-route"></i> Itinerary Builder</h1>
            
            <div class="trip-info">
                <h2><?= htmlspecialchars($trip['trip_name']) ?></h2>
                <p><i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($trip['destination']) ?></p>
                <p><i class="fas fa-calendar"></i> <?= date('M d', strtotime($trip['start_date'])) ?> - <?= date('M d, Y', strtotime($trip['end_date'])) ?></p>
            </div>
            
            <div class="header-stats">
                <div class="stat-badge">
                    <i class="fas fa-layer-group"></i>
                    <div>
                        <div class="stat-number"><?= $section_count ?></div>
                        <div class="stat-label">Sections</div>
                    </div>
                </div>
                <div class="stat-badge">
                    <i class="fas fa-hiking"></i>
                    <div>
                        <div class="stat-number"><?= $activities_count ?></div>
                        <div class="stat-label">Activities</div>
                    </div>
                </div>
                <div class="stat-badge">
                    <i class="fas fa-wallet"></i>
                    <div>
                        <div class="stat-number">₹<?= number_format($total_budget, 0) ?></div>
                        <div class="stat-label">Total Budget</div>
                    </div>
                </div>
                <div class="stat-badge">
                    <i class="fas fa-plane"></i>
                    <div>
                        <div class="stat-number"><?= $travel_count ?></div>
                        <div class="stat-label">Travel Items</div>
                    </div>
                </div>
            </div>
            
            <div class="header-actions">
                <a href="itinerary_view.php?trip_id=<?= $trip_id ?>" class="btn-secondary">
                    <i class="fas fa-eye"></i> View Itinerary
                </a>
                <a href="../dashboard/trip_list.php" class="btn-back">
                    <i class="fas fa-arrow-left"></i> Back to Trips
                </a>
            </div>
        </div>

        <!-- Main Builder -->
        <div class="builder-layout">
            <!-- Left Panel - Add Section Form -->
            <div class="add-section-form fade-in-up">
                <h3><i class="fas fa-plus-circle"></i> Add New Section</h3>
                <form method="POST" id="sectionForm">
                    <input type="hidden" name="add_section" value="1">
                    
                    <div class="form-group">
                        <label><i class="fas fa-tag"></i> Section Type</label>
                        <select name="section_type" id="sectionType" required>
                            <option value="">Select Type</option>
                            <option value="travel">Travel</option>
                            <option value="accommodation">Accommodation</option>
                            <option value="activity">Activity</option>
                            <option value="food">Food & Dining</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label><i class="fas fa-heading"></i> Title</label>
                        <input type="text" name="title" id="sectionTitle" placeholder="e.g., Flight to Paris" required>
                    </div>

                    <div class="form-group">
                        <label><i class="fas fa-align-left"></i> Description</label>
                        <textarea name="description" id="sectionDesc" rows="2" placeholder="Add details about this section..."></textarea>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label><i class="fas fa-map-marker-alt"></i> Location</label>
                            <input type="text" name="location" id="sectionLocation" placeholder="Address or place name">
                        </div>
                        
                        <div class="form-group">
                            <label><i class="fas fa-calendar"></i> Start Date & Time</label>
                            <input type="datetime-local" name="start_datetime" id="startDateTime">
                        </div>
                        
                        <div class="form-group">
                            <label><i class="fas fa-calendar"></i> End Date & Time</label>
                            <input type="datetime-local" name="end_datetime" id="endDateTime">
                        </div>
                    </div>

                    <div class="form-group">
                        <label><i class="fas fa-wallet"></i> Budget for this section</label>
                        <div class="input-with-icon">
                            <span class="currency">₹</span>
                            <input type="text" id="budget" name="budget" placeholder="0" oninput="formatBudget(this)">
                            <input type="hidden" id="budget_raw" name="budget_raw">
                        </div>
                        <div class="budget-quick-options">
                            <button type="button" class="budget-quick-btn" onclick="setBudget(1000)">₹1,000</button>
                            <button type="button" class="budget-quick-btn" onclick="setBudget(5000)">₹5,000</button>
                            <button type="button" class="budget-quick-btn" onclick="setBudget(10000)">₹10,000</button>
                            <button type="button" class="budget-quick-btn" onclick="setBudget(25000)">₹25,000</button>
                        </div>
                    </div>

                    <button type="submit" class="btn-primary" style="width: 100%;">
                        <i class="fas fa-plus"></i> Add to Itinerary
                    </button>
                </form>
            </div>

            <!-- Right Panel - Sections List -->
            <div class="sections-panel fade-in-up">
                <div class="sections-header">
                    <h3><i class="fas fa-list"></i> Your Itinerary</h3>
                    <div class="total-budget-display">
                        <span style="color: var(--text-light);">Total: </span>
                        <span style="font-weight: 700; color: var(--primary-blue);">₹<span id="totalBudgetDisplay"><?= number_format($total_budget, 0) ?></span></span>
                    </div>
                </div>
                
                <div class="sections-list" id="sectionsList">
                    <?php if(empty($sections)): ?>
                        <div class="empty-state">
                            <i class="fas fa-calendar-plus"></i>
                            <p>Start building your itinerary! Add your first section.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach($sections as $section): ?>
                        <div class="section-card" data-id="<?= $section['id'] ?>">
                            <div class="section-header">
                                <div class="section-type">
                                    <div class="type-icon type-<?= $section['section_type'] ?>">
                                        <i class="fas <?= 
                                            $section['section_type'] == 'travel' ? 'fa-plane' :
                                            ($section['section_type'] == 'accommodation' ? 'fa-hotel' :
                                            ($section['section_type'] == 'activity' ? 'fa-hiking' :
                                            ($section['section_type'] == 'food' ? 'fa-utensils' : 'fa-sticky-note')))
                                        ?>"></i>
                                    </div>
                                    <span><?= ucfirst($section['section_type']) ?></span>
                                </div>
                                <div class="section-actions">
                                    <form method="POST" style="display: inline;" onsubmit="return confirm('Delete this section?')">
                                        <input type="hidden" name="delete_section" value="1">
                                        <input type="hidden" name="section_id" value="<?= $section['id'] ?>">
                                        <button type="submit" class="btn-icon delete">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
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
                                        <span>₹<?= number_format($section['budget'], 0) ?></span>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Budget functions
        function formatBudget(input) {
            let rawValue = input.value.replace(/[^\d]/g, '');
            document.getElementById('budget_raw').value = rawValue;
            
            if (rawValue) {
                input.value = parseInt(rawValue).toLocaleString('en-IN');
            }
        }

        function setBudget(amount) {
            document.getElementById('budget').value = amount.toLocaleString('en-IN');
            document.getElementById('budget_raw').value = amount;
            
            // Visual feedback
            const budgetInput = document.getElementById('budget');
            budgetInput.style.borderColor = 'var(--primary-blue)';
            budgetInput.style.boxShadow = '0 0 0 4px rgba(37, 99, 235, 0.1)';
            setTimeout(() => {
                budgetInput.style.borderColor = '';
                budgetInput.style.boxShadow = '';
            }, 1000);
        }

        // Update total budget display
        function updateTotalBudget() {
            let total = 0;
            document.querySelectorAll('.detail.budget span').forEach(el => {
                const amount = parseFloat(el.textContent.replace('₹', '').replace(/,/g, '')) || 0;
                total += amount;
            });
            document.getElementById('totalBudgetDisplay').textContent = total.toLocaleString('en-IN', {
                minimumFractionDigits: 0,
                maximumFractionDigits: 0
            });
        }

        // Form validation
        document.getElementById('sectionForm').addEventListener('submit', function(e) {
            const title = document.getElementById('sectionTitle').value.trim();
            const type = document.getElementById('sectionType').value;
            
            if (!title || !type) {
                e.preventDefault();
                alert('Please fill in all required fields');
                return false;
            }
            
            // Set default times if not set
            const startDateInput = document.getElementById('startDateTime');
            const endDateInput = document.getElementById('endDateTime');
            
            if (!startDateInput.value) {
                const tomorrow = new Date();
                tomorrow.setDate(tomorrow.getDate() + 1);
                startDateInput.value = tomorrow.toISOString().slice(0, 16);
                
                const endTime = new Date(tomorrow.getTime() + 2 * 60 * 60 * 1000);
                endDateInput.value = endTime.toISOString().slice(0, 16);
            }
            
            return true;
        });

        // Initialize
        updateTotalBudget();
        
        // Auto-set date times if not set
        const startDateInput = document.getElementById('startDateTime');
        const endDateInput = document.getElementById('endDateTime');
        
        if (!startDateInput.value) {
            const tomorrow = new Date();
            tomorrow.setDate(tomorrow.getDate() + 1);
            startDateInput.value = tomorrow.toISOString().slice(0, 16);
            
            const endTime = new Date(tomorrow.getTime() + 2 * 60 * 60 * 1000);
            endDateInput.value = endTime.toISOString().slice(0, 16);
        }
    </script>
</body>
</html>