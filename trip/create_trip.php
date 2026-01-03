<?php
session_start();
require_once '../db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit();
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $trip_name = trim($_POST['trip_name']);
    $destination = trim($_POST['destination']);
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    $travelers = $_POST['travelers'];
    
    // Fix budget handling - remove commas and format properly
    $budget = isset($_POST['budget']) ? $_POST['budget'] : 0;
    if ($budget !== '') {
        // Remove any commas and non-numeric characters except decimal point
        $budget = preg_replace('/[^\d.]/', '', $budget);
        $budget = floatval($budget);
    } else {
        $budget = 0;
    }
    
    // Validation
    if (empty($trip_name) || empty($destination) || empty($start_date) || empty($end_date)) {
        $error = "Please fill in all required fields";
    } elseif (strtotime($end_date) < strtotime($start_date)) {
        $error = "End date must be after start date";
    } elseif ($budget < 0) {
        $error = "Budget cannot be negative";
    } else {
        $stmt = $pdo->prepare("INSERT INTO trips (user_id, trip_name, destination, start_date, end_date, travelers, total_budget) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$_SESSION['user_id'], $trip_name, $destination, $start_date, $end_date, $travelers, $budget]);
        
        $trip_id = $pdo->lastInsertId();
        header("Location: itinerary_builder.php?trip_id=$trip_id");
        exit();
    }
}

// Fetch suggested places
$suggestions = $pdo->query("SELECT DISTINCT city, country FROM activities ORDER BY popularity DESC LIMIT 8")->fetchAll();

// Destination images mapping
$destination_images = [
    'Paris' => 'https://images.unsplash.com/photo-1502602898657-3e91760cbb34?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80',
    'Tokyo' => 'https://images.unsplash.com/photo-1540959733332-eab4deabeeaf?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80',
    'New York' => 'https://images.unsplash.com/photo-1496442226666-8d4d0e62e6e9?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80',
    'Bali' => 'https://images.unsplash.com/photo-1537953773345-d172ccf13cf1?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80',
    'Rome' => 'https://images.unsplash.com/photo-1552832230-c0197dd311b5?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80',
    'Dubai' => 'https://images.unsplash.com/photo-1518684079-3c830dcef090?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80',
    'Sydney' => 'https://images.unsplash.com/photo-1506973035872-a4ec16b8e8d9?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80',
    'Bangkok' => 'https://images.unsplash.com/photo-1528181304800-259b08848526?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80',
    'London' => 'https://images.unsplash.com/photo-1513635269975-59663e0ac1ad?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80',
    'Singapore' => 'https://images.unsplash.com/photo-1525625293386-3f8f99389edd?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80',
    'default' => 'https://images.unsplash.com/photo-1469474968028-56623f02e42e?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80'
];

function getDestinationImage($city) {
    global $destination_images;
    return $destination_images[$city] ?? $destination_images['default'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create New Trip | Globetrotter ✈️</title>
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
        .create-container {
            max-width: 1200px;
            margin: 0 auto;
            animation: fadeIn 0.8s ease;
        }

        /* Header */
        .form-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 40px;
            animation: fadeInUp 0.8s ease;
        }

        .form-header h1 {
            font-size: 2.5rem;
            font-weight: 900;
            background: var(--blue-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .form-header h1 i {
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

        /* Form Wrapper */
        .form-wrapper {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 40px;
            animation: fadeInUp 0.8s ease 0.2s both;
        }

        @media (max-width: 968px) {
            .form-wrapper {
                grid-template-columns: 1fr;
            }
        }

        /* Main Form */
        .trip-form {
            background: var(--glass-bg);
            backdrop-filter: blur(10px);
            border-radius: 24px;
            padding: 40px;
            box-shadow: var(--card-shadow);
            border: 1px solid rgba(255, 255, 255, 0.3);
            position: relative;
            overflow: hidden;
        }

        .trip-form::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 5px;
            background: var(--blue-gradient);
        }

        /* Error Message */
        .error-message {
            background: #fef2f2;
            border: 1px solid #fee2e2;
            border-left: 4px solid var(--error);
            color: #991b1b;
            padding: 14px 16px;
            border-radius: 8px;
            margin-bottom: 24px;
            display: flex;
            align-items: flex-start;
            gap: 12px;
            font-size: 0.875rem;
            animation: slideDown 0.3s ease;
        }

        .error-message i {
            color: var(--error);
            font-size: 1.125rem;
            margin-top: 1px;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Form Elements */
        .form-group {
            margin-bottom: 30px;
            animation: fadeInUp 0.6s ease;
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
        .form-group select {
            width: 100%;
            padding: 18px 20px;
            border: 2px solid var(--gray-border);
            border-radius: 12px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: white;
            color: var(--text-dark);
        }

        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: var(--primary-blue);
            box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.1);
            transform: translateY(-2px);
        }

        .form-group input:hover:not(:focus),
        .form-group select:hover:not(:focus) {
            border-color: #94a3b8;
        }

        .form-group.error input,
        .form-group.error select {
            border-color: var(--error);
            background: #fef2f2;
        }

        /* Form Row */
        .form-row {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }

        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
            }
        }

        /* Currency Input */
        .input-with-icon {
            position: relative;
        }

        .currency {
            position: absolute;
            left: 20px;
            top: 50%;
            transform: translateY(-50%);
            font-weight: 600;
            color: var(--text-dark);
            font-size: 1rem;
        }

        .input-with-icon input {
            padding-left: 50px !important;
        }

        /* Quick Budget Buttons */
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

        /* Suggestions Sidebar */
        .suggestions-sidebar {
            background: var(--glass-bg);
            backdrop-filter: blur(10px);
            border-radius: 24px;
            padding: 40px;
            box-shadow: var(--card-shadow);
            border: 1px solid rgba(255, 255, 255, 0.3);
            position: relative;
            overflow: hidden;
        }

        .suggestions-sidebar::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 5px;
            background: linear-gradient(90deg, #3b82f6, #8b5cf6);
        }

        .suggestions-header {
            margin-bottom: 30px;
        }

        .suggestions-header h3 {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--text-dark);
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 10px;
        }

        .suggestions-header h3 i {
            color: var(--primary-blue);
        }

        .suggestions-header p {
            color: var(--text-light);
            font-size: 0.9375rem;
        }

        .suggestions-grid {
            display: grid;
            gap: 20px;
        }

        .suggestion-card {
            background: white;
            border-radius: 16px;
            overflow: hidden;
            border: 1px solid var(--gray-border);
            cursor: pointer;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
        }

        .suggestion-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--card-shadow-hover);
            border-color: var(--primary-blue);
        }

        .suggestion-card img {
            width: 100%;
            height: 120px;
            object-fit: cover;
        }

        .suggestion-content {
            padding: 20px;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .suggestion-content i {
            color: var(--primary-blue);
            font-size: 1.25rem;
        }

        .suggestion-text {
            flex: 1;
        }

        .suggestion-text h4 {
            font-size: 1rem;
            font-weight: 600;
            color: var(--text-dark);
            margin-bottom: 4px;
        }

        .suggestion-text p {
            font-size: 0.875rem;
            color: var(--text-light);
        }

        /* Form Actions */
        .form-actions {
            display: flex;
            gap: 20px;
            margin-top: 40px;
            animation: fadeInUp 0.6s ease 0.4s both;
        }

        .btn-primary {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            background: var(--blue-gradient);
            color: white;
            border: none;
            padding: 20px;
            border-radius: 12px;
            font-weight: 700;
            font-size: 1.125rem;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 30px rgba(102, 126, 234, 0.3);
        }

        .btn-primary:active {
            transform: translateY(-1px);
        }

        .btn-secondary {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            padding: 20px 30px;
            background: var(--gray-border);
            color: var(--text-dark);
            text-decoration: none;
            border-radius: 12px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-secondary:hover {
            background: #cbd5e1;
            transform: translateY(-2px);
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

        /* Responsive Design */
        @media (max-width: 768px) {
            body {
                padding: 16px;
            }
            
            .form-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 20px;
            }
            
            .form-header h1 {
                font-size: 2rem;
            }
            
            .trip-form,
            .suggestions-sidebar {
                padding: 30px 20px;
            }
            
            .form-actions {
                flex-direction: column;
            }
            
            .budget-quick-options {
                justify-content: center;
            }
        }

        /* Loading Animation */
        .loading {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            border-top-color: white;
            animation: spin 1s ease-in-out infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        /* Info Box */
        .info-box {
            margin-top: 30px;
            padding: 20px;
            background: var(--blue-bg);
            border-radius: 12px;
            border-left: 4px solid var(--primary-blue);
        }

        .info-box h4 {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 10px;
            color: var(--primary-blue);
        }

        .info-box p {
            color: var(--text-light);
            font-size: 0.875rem;
            line-height: 1.5;
        }
    </style>
</head>
<body>
    <div class="floating-elements">
        <div class="floating-circle"></div>
        <div class="floating-circle"></div>
    </div>

    <div class="create-container">
        <!-- Header -->
        <div class="form-header">
            <h1><i class="fas fa-compass"></i> Plan Your Adventure</h1>
            <a href="../dashboard/landing.php" class="btn-back">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
        </div>

        <!-- Main Content -->
        <div class="form-wrapper">
            <!-- Trip Form -->
            <form method="POST" class="trip-form" id="tripForm">
                <?php if(!empty($error)): ?>
                    <div class="error-message">
                        <i class="fas fa-exclamation-circle"></i>
                        <span><?= htmlspecialchars($error) ?></span>
                    </div>
                <?php endif; ?>

                <div class="form-group">
                    <label for="trip_name"><i class="fas fa-signature"></i> Trip Name</label>
                    <input type="text" id="trip_name" name="trip_name" 
                           placeholder="e.g., Summer Europe Adventure 2024" 
                           required
                           autocomplete="off"
                           maxlength="100">
                </div>

                <div class="form-group">
                    <label for="destination"><i class="fas fa-map-pin"></i> Destination</label>
                    <input type="text" id="destination" name="destination" 
                           list="suggestions" 
                           placeholder="Where are you going? (City, Country)"
                           required
                           autocomplete="off"
                           maxlength="100">
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
                        <label for="travelers"><i class="fas fa-users"></i> Travelers</label>
                        <select id="travelers" name="travelers" required>
                            <option value="" disabled selected>Select number</option>
                            <?php for($i = 1; $i <= 20; $i++): ?>
                                <option value="<?= $i ?>"><?= $i ?> <?= $i == 1 ? 'person' : 'people' ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label for="budget"><i class="fas fa-wallet"></i> Estimated Budget (Optional)</label>
                    <div class="input-with-icon">
                        <span class="currency">₹</span>
                        <input type="text" id="budget" name="budget" 
                               placeholder="0"
                               oninput="formatBudget(this)">
                        <!-- Hidden field for raw numeric value -->
                        <input type="hidden" id="budget_raw" name="budget_raw">
                    </div>
                    <div class="budget-quick-options">
                        <button type="button" class="budget-quick-btn" onclick="setBudget(1000)">₹1,000</button>
                        <button type="button" class="budget-quick-btn" onclick="setBudget(5000)">₹5,000</button>
                        <button type="button" class="budget-quick-btn" onclick="setBudget(10000)">₹10,000</button>
                        <button type="button" class="budget-quick-btn" onclick="setBudget(25000)">₹25,000</button>
                        <button type="button" class="budget-quick-btn" onclick="setBudget(50000)">₹50,000</button>
                        <button type="button" class="budget-quick-btn" onclick="setBudget(100000)">₹1,00,000</button>
                    </div>
                    <small style="display: block; margin-top: 8px; color: var(--text-light);">
                        Enter in Indian Rupees (INR). Use quick buttons or type any amount.
                    </small>
                </div>

                <div class="info-box">
                    <h4><i class="fas fa-info-circle"></i> Quick Tips</h4>
                    <p>• Give your trip a descriptive name that you'll remember</p>
                    <p>• You can add more details in the itinerary builder</p>
                    <p>• Budget is optional - you can add it later</p>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn-primary" id="submitBtn">
                        <i class="fas fa-arrow-right"></i> Continue to Itinerary
                    </button>
                    <a href="../dashboard/trip_list.php" class="btn-secondary">
                        <i class="fas fa-times"></i> Cancel
                    </a>
                </div>
            </form>

            <!-- Suggestions Sidebar -->
            <div class="suggestions-sidebar">
                <div class="suggestions-header">
                    <h3><i class="fas fa-lightbulb"></i> Popular Destinations</h3>
                    <p>Click any destination to autofill the form</p>
                </div>
                
                <div class="suggestions-grid">
                    <?php foreach($suggestions as $suggestion): 
                        $city = $suggestion['city'];
                        $country = $suggestion['country'];
                    ?>
                    <div class="suggestion-card" 
                         onclick="selectDestination('<?= htmlspecialchars($city) ?>', '<?= htmlspecialchars($country) ?>')">
                        <img src="<?= getDestinationImage($city) ?>" alt="<?= htmlspecialchars($city) ?>">
                        <div class="suggestion-content">
                            <i class="fas fa-map-marker-alt"></i>
                            <div class="suggestion-text">
                                <h4><?= htmlspecialchars($city) ?></h4>
                                <p><?= htmlspecialchars($country) ?></p>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <div class="info-box">
                    <h4><i class="fas fa-star"></i> Why Plan with Us?</h4>
                    <p>• Easy itinerary creation with drag & drop</p>
                    <p>• Budget tracking and expense management</p>
                    <p>• Activity suggestions based on your destination</p>
                    <p>• Share trips with travel companions</p>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Set minimum date to today
        const today = new Date().toISOString().split('T')[0];
        const startDateInput = document.getElementById('start_date');
        const endDateInput = document.getElementById('end_date');
        const budgetInput = document.getElementById('budget');
        const budgetRawInput = document.getElementById('budget_raw');
        
        // Set default dates (tomorrow and 7 days later)
        const tomorrow = new Date();
        tomorrow.setDate(tomorrow.getDate() + 1);
        const nextWeek = new Date();
        nextWeek.setDate(nextWeek.getDate() + 8);
        
        startDateInput.min = today;
        endDateInput.min = today;
        startDateInput.value = tomorrow.toISOString().split('T')[0];
        endDateInput.value = nextWeek.toISOString().split('T')[0];

        // Update end date min when start date changes
        startDateInput.addEventListener('change', function() {
            endDateInput.min = this.value;
            
            // If end date is before start date, reset it
            if (endDateInput.value && endDateInput.value < this.value) {
                endDateInput.value = this.value;
            }
            
            updateDuration();
        });

        endDateInput.addEventListener('change', updateDuration);

        // Calculate and display trip duration
        function updateDuration() {
            const startDate = new Date(startDateInput.value);
            const endDate = new Date(endDateInput.value);
            
            if (startDate && endDate && startDate <= endDate) {
                const diffTime = Math.abs(endDate - startDate);
                const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24)) + 1;
                
                // Show duration hint
                let durationHint = document.getElementById('durationHint');
                if (!durationHint) {
                    durationHint = document.createElement('div');
                    durationHint.id = 'durationHint';
                    durationHint.style.cssText = `
                        margin-top: 8px;
                        color: var(--primary-blue);
                        font-size: 0.875rem;
                        font-weight: 500;
                        display: flex;
                        align-items: center;
                        gap: 6px;
                    `;
                    endDateInput.parentNode.appendChild(durationHint);
                }
                
                durationHint.innerHTML = `<i class="fas fa-clock"></i> ${diffDays} day${diffDays > 1 ? 's' : ''} trip`;
            }
        }

        // Format budget input with commas and store raw value
        function formatBudget(input) {
            // Get raw value without commas
            let rawValue = input.value.replace(/[^\d]/g, '');
            
            // Store raw value in hidden field
            budgetRawInput.value = rawValue;
            
            // Format with commas for display
            if (rawValue) {
                let formattedValue = parseInt(rawValue).toLocaleString('en-IN');
                input.value = formattedValue;
            } else {
                input.value = '';
            }
        }

        // Set budget using quick buttons
        function setBudget(amount) {
            budgetInput.value = amount.toLocaleString('en-IN');
            budgetRawInput.value = amount;
            
            // Add visual feedback
            budgetInput.style.borderColor = 'var(--primary-blue)';
            budgetInput.style.boxShadow = '0 0 0 4px rgba(37, 99, 235, 0.1)';
            setTimeout(() => {
                budgetInput.style.borderColor = '';
                budgetInput.style.boxShadow = '';
            }, 1000);
        }

        // Select destination from suggestions
        function selectDestination(city, country) {
            const destinationInput = document.getElementById('destination');
            destinationInput.value = `${city}, ${country}`;
            
            // Add focus effect
            destinationInput.style.borderColor = 'var(--primary-blue)';
            destinationInput.style.boxShadow = '0 0 0 4px rgba(37, 99, 235, 0.1)';
            destinationInput.style.transform = 'translateY(-2px)';
            
            // Remove focus effect after 1 second
            setTimeout(() => {
                destinationInput.style.borderColor = '';
                destinationInput.style.boxShadow = '';
                destinationInput.style.transform = '';
            }, 1000);
            
            // Update trip name suggestion
            const tripNameInput = document.getElementById('trip_name');
            if (!tripNameInput.value) {
                const monthNames = ["January", "February", "March", "April", "May", "June",
                    "July", "August", "September", "October", "November", "December"];
                const now = new Date();
                const month = monthNames[now.getMonth()];
                const year = now.getFullYear() + 1;
                tripNameInput.value = `${city} ${month} ${year} Adventure`;
            }
        }

        // Form validation
        const form = document.getElementById('tripForm');
        const submitBtn = document.getElementById('submitBtn');
        
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Clear previous errors
            document.querySelectorAll('.form-group').forEach(group => {
                group.classList.remove('error');
            });
            
            // Basic validation
            const tripName = document.getElementById('trip_name').value.trim();
            const destination = document.getElementById('destination').value.trim();
            const startDate = document.getElementById('start_date').value;
            const endDate = document.getElementById('end_date').value;
            const travelers = document.getElementById('travelers').value;
            const budget = budgetRawInput.value;
            
            let isValid = true;
            let errorMessage = '';
            
            if (!tripName) {
                document.getElementById('trip_name').parentElement.classList.add('error');
                isValid = false;
                errorMessage = 'Trip name is required';
            }
            
            if (!destination) {
                document.getElementById('destination').parentElement.classList.add('error');
                isValid = false;
                errorMessage = 'Destination is required';
            }
            
            if (!startDate) {
                document.getElementById('start_date').parentElement.classList.add('error');
                isValid = false;
                errorMessage = 'Start date is required';
            }
            
            if (!endDate) {
                document.getElementById('end_date').parentElement.classList.add('error');
                isValid = false;
                errorMessage = 'End date is required';
            }
            
            if (startDate && endDate && new Date(endDate) < new Date(startDate)) {
                document.getElementById('end_date').parentElement.classList.add('error');
                isValid = false;
                errorMessage = 'End date must be after start date';
            }
            
            if (!travelers) {
                document.getElementById('travelers').parentElement.classList.add('error');
                isValid = false;
                errorMessage = 'Please select number of travelers';
            }
            
            if (budget && budget < 0) {
                document.getElementById('budget').parentElement.classList.add('error');
                isValid = false;
                errorMessage = 'Budget cannot be negative';
            }
            
            if (!isValid) {
                showError(errorMessage);
                return;
            }
            
            // Ensure budget field has proper value before submission
            if (!budgetInput.value && budgetRawInput.value) {
                budgetInput.value = budgetRawInput.value;
            }
            
            // Show loading state
            submitBtn.innerHTML = '<span class="loading"></span> Creating Your Trip...';
            submitBtn.disabled = true;
            
            // Submit form
            this.submit();
        });

        function showError(message) {
            // Create error notification
            const errorDiv = document.createElement('div');
            errorDiv.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                background: var(--error);
                color: white;
                padding: 15px 25px;
                border-radius: 10px;
                box-shadow: 0 10px 30px rgba(0,0,0,0.2);
                z-index: 1000;
                animation: slideIn 0.3s ease;
                font-weight: 600;
                display: flex;
                align-items: center;
                gap: 10px;
            `;
            errorDiv.innerHTML = `<i class="fas fa-exclamation-circle"></i> ${message}`;
            
            document.body.appendChild(errorDiv);
            
            // Remove after 3 seconds
            setTimeout(() => {
                errorDiv.style.animation = 'slideOut 0.3s ease forwards';
                setTimeout(() => {
                    document.body.removeChild(errorDiv);
                }, 300);
            }, 3000);
            
            // Add animation styles
            const style = document.createElement('style');
            style.textContent = `
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
            document.head.appendChild(style);
            
            // Scroll to first error
            const firstError = document.querySelector('.form-group.error');
            if (firstError) {
                firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        }

        // Initialize duration calculation
        updateDuration();
        
        // Focus on trip name
        document.getElementById('trip_name').focus();
    </script>
</body>
</html>