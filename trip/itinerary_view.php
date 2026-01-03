<?php
session_start();
require_once '../db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit();
}

$trip_id = $_GET['trip_id'] ?? null;
if (!$trip_id) {
    header('Location: ../dashboard/trip_list.php');
    exit();
}

// Fetch trip details
$stmt = $pdo->prepare("SELECT * FROM trips WHERE id = ? AND user_id = ?");
$stmt->execute([$trip_id, $_SESSION['user_id']]);
$trip = $stmt->fetch();

if (!$trip) {
    die('Trip not found or access denied');
}

// Fetch itinerary sections
$sections = $pdo->prepare("
    SELECT * FROM itinerary_sections 
    WHERE trip_id = ? 
    ORDER BY start_datetime, sequence
");
$sections->execute([$trip_id]);
$sections = $sections->fetchAll();

// Group sections by date
$sectionsByDate = [];
foreach ($sections as $section) {
    $date = $section['start_datetime'] ? date('Y-m-d', strtotime($section['start_datetime'])) : 'unscheduled';
    if (!isset($sectionsByDate[$date])) {
        $sectionsByDate[$date] = [];
    }
    $sectionsByDate[$date][] = $section;
}

// Calculate budget summary
$budgetSummary = $pdo->prepare("
    SELECT 
        section_type,
        COUNT(*) as count,
        SUM(budget) as total,
        AVG(budget) as average
    FROM itinerary_sections 
    WHERE trip_id = ? 
    GROUP BY section_type
    ORDER BY total DESC
");
$budgetSummary->execute([$trip_id]);
$budgetSummary = $budgetSummary->fetchAll();

$totalBudget = array_sum(array_column($budgetSummary, 'total'));

// Get search/filter/sort parameters
$search = $_GET['search'] ?? '';
$section_type = $_GET['section_type'] ?? '';
$min_budget = $_GET['min_budget'] ?? '';
$max_budget = $_GET['max_budget'] ?? '';
$sort_by = $_GET['sort_by'] ?? 'time';

// Apply filters to sections
$filteredSections = $sections;
if ($search) {
    $searchLower = strtolower($search);
    $filteredSections = array_filter($filteredSections, function($section) use ($searchLower) {
        return strpos(strtolower($section['title']), $searchLower) !== false ||
               strpos(strtolower($section['description']), $searchLower) !== false ||
               strpos(strtolower($section['location']), $searchLower) !== false;
    });
}

if ($section_type) {
    $filteredSections = array_filter($filteredSections, function($section) use ($section_type) {
        return $section['section_type'] === $section_type;
    });
}

if ($min_budget !== '') {
    $filteredSections = array_filter($filteredSections, function($section) use ($min_budget) {
        return $section['budget'] >= $min_budget;
    });
}

if ($max_budget !== '') {
    $filteredSections = array_filter($filteredSections, function($section) use ($max_budget) {
        return $section['budget'] <= $max_budget;
    });
}

// Apply sorting
switch ($sort_by) {
    case 'budget_asc':
        usort($filteredSections, function($a, $b) {
            return $a['budget'] <=> $b['budget'];
        });
        break;
    case 'budget_desc':
        usort($filteredSections, function($a, $b) {
            return $b['budget'] <=> $a['budget'];
        });
        break;
    case 'title':
        usort($filteredSections, function($a, $b) {
            return strcmp($a['title'], $b['title']);
        });
        break;
    case 'type':
        usort($filteredSections, function($a, $b) {
            return strcmp($a['section_type'], $b['section_type']);
        });
        break;
    default: // time
        usort($filteredSections, function($a, $b) {
            if (!$a['start_datetime'] && !$b['start_datetime']) return 0;
            if (!$a['start_datetime']) return 1;
            if (!$b['start_datetime']) return -1;
            return strtotime($a['start_datetime']) <=> strtotime($b['start_datetime']);
        });
        break;
}

// Get unique section types for filter
$sectionTypes = array_unique(array_column($sections, 'section_type'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Itinerary - <?= htmlspecialchars($trip['trip_name']) ?></title>
    <link rel="stylesheet" href="../assets/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .itinerary-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 40px;
            border-radius: 10px;
            margin-bottom: 30px;
            position: relative;
            overflow: hidden;
        }
        
        .itinerary-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path fill="white" opacity="0.1" d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/></svg>');
            background-size: 200px;
            opacity: 0.2;
        }
        
        .header-content {
            position: relative;
            z-index: 1;
        }
        
        .trip-meta {
            display: flex;
            gap: 30px;
            margin-top: 20px;
            flex-wrap: wrap;
        }
        
        .meta-item {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .meta-item i {
            font-size: 1.2rem;
        }
        
        .budget-overview {
            background: white;
            border-radius: 10px;
            padding: 25px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        
        .budget-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        
        .budget-stat {
            text-align: center;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 8px;
        }
        
        .budget-stat h4 {
            color: #667eea;
            margin-bottom: 5px;
        }
        
        .budget-stat .amount {
            font-size: 1.5rem;
            font-weight: bold;
            color: #333;
        }
        
        .itinerary-controls {
            background: white;
            border-radius: 10px;
            padding: 25px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        
        .controls-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 15px;
        }
        
        .day-navigation {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        
        .day-btn {
            padding: 8px 15px;
            background: #f0f2f5;
            border: none;
            border-radius: 20px;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .day-btn:hover {
            background: #e2e6ea;
        }
        
        .day-btn.active {
            background: #667eea;
            color: white;
        }
        
        .timeline {
            position: relative;
            max-width: 800px;
            margin: 30px auto;
        }
        
        .timeline::before {
            content: '';
            position: absolute;
            left: 20px;
            top: 0;
            bottom: 0;
            width: 4px;
            background: #667eea;
            border-radius: 2px;
        }
        
        .timeline-item {
            position: relative;
            margin-bottom: 30px;
            padding-left: 50px;
        }
        
        .timeline-dot {
            position: absolute;
            left: 14px;
            top: 5px;
            width: 16px;
            height: 16px;
            background: white;
            border: 3px solid #667eea;
            border-radius: 50%;
            z-index: 1;
        }
        
        .timeline-content {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
        }
        
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 10px;
        }
        
        .section-type {
            background: #eef2ff;
            color: #667eea;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
        }
        
        .section-time {
            color: #666;
            font-size: 0.9rem;
        }
        
        .section-budget {
            color: #38a169;
            font-weight: bold;
            margin-top: 10px;
        }
        
        .day-section {
            margin-bottom: 40px;
        }
        
        .day-header {
            background: #f8f9fa;
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .day-total {
            color: #667eea;
            font-weight: bold;
        }
        
        .print-btn {
            background: #4299e1;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 6px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .export-btn {
            background: #48bb78;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 6px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        @media print {
            .no-print {
                display: none;
            }
            
            .timeline-content {
                box-shadow: none;
                border: 1px solid #ddd;
            }
            
            .itinerary-header {
                background: #667eea !important;
                -webkit-print-color-adjust: exact;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Trip Header -->
        <div class="itinerary-header">
            <div class="header-content">
                <div class="header-actions no-print" style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 20px;">
                    <a href="../dashboard/trip_list.php" class="btn-back" style="color: white; border: 1px solid rgba(255,255,255,0.3); padding: 8px 15px; border-radius: 6px;">
                        ← Back to Trips
                    </a>
                    <div style="display: flex; gap: 10px;">
                        <button onclick="window.print()" class="print-btn">
                            <i class="fas fa-print"></i> Print
                        </button>
                        <button onclick="exportItinerary()" class="export-btn">
                            <i class="fas fa-download"></i> Export PDF
                        </button>
                    </div>
                </div>
                
                <h1><?= htmlspecialchars($trip['trip_name']) ?></h1>
                <p style="opacity: 0.9; margin-top: 5px;"><?= htmlspecialchars($trip['destination']) ?></p>
                
                <div class="trip-meta">
                    <div class="meta-item">
                        <i class="fas fa-calendar-alt"></i>
                        <span><?= date('M d, Y', strtotime($trip['start_date'])) ?> - <?= date('M d, Y', strtotime($trip['end_date'])) ?></span>
                    </div>
                    <div class="meta-item">
                        <i class="fas fa-users"></i>
                        <span><?= $trip['travelers'] ?> traveler<?= $trip['travelers'] > 1 ? 's' : '' ?></span>
                    </div>
                    <div class="meta-item">
                        <i class="fas fa-wallet"></i>
                        <span>Total Budget: $<?= number_format($totalBudget, 2) ?></span>
                    </div>
                    <div class="meta-item">
                        <span class="status-badge <?= $trip['status'] == 'planning' ? 'status-planning' : ($trip['status'] == 'ongoing' ? 'status-ongoing' : 'status-completed') ?>">
                            <?= ucfirst($trip['status']) ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Budget Overview -->
        <div class="budget-overview no-print">
            <h2><i class="fas fa-chart-pie"></i> Budget Overview</h2>
            <div class="budget-stats">
                <?php foreach($budgetSummary as $category): ?>
                    <div class="budget-stat">
                        <h4><?= ucfirst($category['section_type']) ?></h4>
                        <div class="amount">$<?= number_format($category['total'], 2) ?></div>
                        <small><?= $category['count'] ?> item<?= $category['count'] > 1 ? 's' : '' ?></small>
                        <div style="margin-top: 10px; font-size: 0.9rem; color: #666;">
                            Avg: $<?= number_format($category['average'], 2) ?>
                        </div>
                    </div>
                <?php endforeach; ?>
                <div class="budget-stat" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
                    <h4 style="color: white;">Total Budget</h4>
                    <div class="amount" style="color: white; font-size: 1.8rem;">$<?= number_format($totalBudget, 2) ?></div>
                    <small><?= count($sections) ?> sections</small>
                </div>
            </div>
        </div>

        <!-- Controls -->
        <div class="itinerary-controls no-print">
            <h2><i class="fas fa-sliders-h"></i> Itinerary Controls</h2>
            
            <form method="GET" class="controls-grid">
                <input type="hidden" name="trip_id" value="<?= $trip_id ?>">
                
                <div class="form-group">
                    <label><i class="fas fa-search"></i> Search Sections</label>
                    <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" 
                           placeholder="Search by title, description, or location...">
                </div>
                
                <div class="form-group">
                    <label><i class="fas fa-filter"></i> Filter by Type</label>
                    <select name="section_type">
                        <option value="">All Types</option>
                        <?php foreach($sectionTypes as $type): ?>
                            <option value="<?= $type ?>" <?= $section_type == $type ? 'selected' : '' ?>>
                                <?= ucfirst($type) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label><i class="fas fa-wallet"></i> Budget Range</label>
                    <div style="display: flex; gap: 10px;">
                        <input type="number" name="min_budget" placeholder="Min $" value="<?= htmlspecialchars($min_budget) ?>" style="flex: 1;">
                        <input type="number" name="max_budget" placeholder="Max $" value="<?= htmlspecialchars($max_budget) ?>" style="flex: 1;">
                    </div>
                </div>
                
                <div class="form-group">
                    <label><i class="fas fa-sort"></i> Sort By</label>
                    <select name="sort_by">
                        <option value="time" <?= $sort_by == 'time' ? 'selected' : '' ?>>Time</option>
                        <option value="title" <?= $sort_by == 'title' ? 'selected' : '' ?>>Title</option>
                        <option value="type" <?= $sort_by == 'type' ? 'selected' : '' ?>>Type</option>
                        <option value="budget_asc" <?= $sort_by == 'budget_asc' ? 'selected' : '' ?>>Budget (Low to High)</option>
                        <option value="budget_desc" <?= $sort_by == 'budget_desc' ? 'selected' : '' ?>>Budget (High to Low)</option>
                    </select>
                </div>
                
                <div style="grid-column: 1 / -1; display: flex; gap: 10px; margin-top: 10px;">
                    <button type="submit" class="btn-primary">
                        <i class="fas fa-filter"></i> Apply Filters
                    </button>
                    <a href="itinerary_view.php?trip_id=<?= $trip_id ?>" class="btn-secondary">
                        <i class="fas fa-redo"></i> Reset
                    </a>
                </div>
            </form>
        </div>

        <!-- Day Navigation -->
        <?php if(count($sectionsByDate) > 1): ?>
        <div class="day-navigation no-print">
            <button class="day-btn active" data-day="all">All Days</button>
            <?php 
            $dayNumber = 1;
            foreach($sectionsByDate as $date => $daySections): 
                if($date !== 'unscheduled'):
            ?>
                <button class="day-btn" data-day="<?= $date ?>">Day <?= $dayNumber ?></button>
                <?php $dayNumber++; ?>
            <?php endif; endforeach; ?>
            <?php if(isset($sectionsByDate['unscheduled'])): ?>
                <button class="day-btn" data-day="unscheduled">Unscheduled</button>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <!-- Itinerary Timeline -->
        <div class="itinerary-timeline">
            <?php if(empty($filteredSections)): ?>
                <div class="empty-state">
                    <i class="fas fa-calendar-times"></i>
                    <h3>No sections found</h3>
                    <p><?= $search || $section_type || $min_budget || $max_budget ? 'Try adjusting your filters' : 'Start adding sections to your itinerary' ?></p>
                    <?php if(!$search && !$section_type && !$min_budget && !$max_budget): ?>
                        <a href="itinerary_builder.php?trip_id=<?= $trip_id ?>" class="btn-primary">
                            <i class="fas fa-plus"></i> Add Sections
                        </a>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <?php 
                // Group filtered sections by date
                $filteredByDate = [];
                foreach ($filteredSections as $section) {
                    $date = $section['start_datetime'] ? date('Y-m-d', strtotime($section['start_datetime'])) : 'unscheduled';
                    if (!isset($filteredByDate[$date])) {
                        $filteredByDate[$date] = [];
                    }
                    $filteredByDate[$date][] = $section;
                }
                
                $dayNumber = 1;
                foreach($filteredByDate as $date => $daySections): 
                    $dayTotal = array_sum(array_column($daySections, 'budget'));
                ?>
                    <div class="day-section" data-day="<?= $date ?>">
                        <div class="day-header">
                            <div>
                                <h3>
                                    <?php if($date === 'unscheduled'): ?>
                                        <i class="fas fa-clock"></i> Unscheduled Activities
                                    <?php else: ?>
                                        <i class="fas fa-calendar-day"></i> 
                                        <?= date('l, F j, Y', strtotime($date)) ?>
                                        <?php if(count($filteredByDate) > 1): ?>
                                            <small style="color: #666; margin-left: 10px;">Day <?= $dayNumber ?></small>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </h3>
                            </div>
                            <?php if($dayTotal > 0): ?>
                                <div class="day-total">
                                    <i class="fas fa-wallet"></i> 
                                    $<?= number_format($dayTotal, 2) ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="timeline">
                            <?php foreach($daySections as $section): 
                                $icons = [
                                    'travel' => 'fa-plane',
                                    'accommodation' => 'fa-hotel',
                                    'activity' => 'fa-hiking',
                                    'food' => 'fa-utensils',
                                    'other' => 'fa-sticky-note'
                                ];
                            ?>
                            <div class="timeline-item">
                                <div class="timeline-dot"></div>
                                <div class="timeline-content">
                                    <div class="section-header">
                                        <span class="section-type">
                                            <i class="fas <?= $icons[$section['section_type']] ?? 'fa-map-marker-alt' ?>"></i>
                                            <?= ucfirst($section['section_type']) ?>
                                        </span>
                                        <?php if($section['start_datetime']): ?>
                                            <span class="section-time">
                                                <i class="fas fa-clock"></i>
                                                <?= date('g:i A', strtotime($section['start_datetime'])) ?>
                                                <?php if($section['end_datetime']): ?>
                                                    - <?= date('g:i A', strtotime($section['end_datetime'])) ?>
                                                <?php endif; ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <h4 style="margin: 10px 0;"><?= htmlspecialchars($section['title']) ?></h4>
                                    
                                    <?php if($section['description']): ?>
                                        <p style="color: #555; margin: 10px 0;"><?= htmlspecialchars($section['description']) ?></p>
                                    <?php endif; ?>
                                    
                                    <?php if($section['location']): ?>
                                        <div style="margin: 10px 0;">
                                            <i class="fas fa-map-marker-alt" style="color: #666; margin-right: 5px;"></i>
                                            <span style="color: #666;"><?= htmlspecialchars($section['location']) ?></span>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if($section['budget']): ?>
                                        <div class="section-budget">
                                            <i class="fas fa-wallet"></i> 
                                            $<?= number_format($section['budget'], 2) ?>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if($section['notes']): ?>
                                        <div style="margin-top: 10px; padding: 10px; background: #f8f9fa; border-radius: 6px;">
                                            <strong>Notes:</strong> <?= htmlspecialchars($section['notes']) ?>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <div class="no-print" style="margin-top: 15px; display: flex; gap: 10px;">
                                        <a href="itinerary_builder.php?trip_id=<?= $trip_id ?>&edit=<?= $section['id'] ?>" 
                                           class="btn-small" style="padding: 6px 12px;">
                                            <i class="fas fa-edit"></i> Edit
                                        </a>
                                        <button onclick="deleteSection(<?= $section['id'] ?>)" 
                                                class="btn-small" style="padding: 6px 12px; background: #e53e3e; color: white; border: none; border-radius: 4px; cursor: pointer;">
                                            <i class="fas fa-trash"></i> Delete
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php $dayNumber++; ?>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Day navigation
        document.querySelectorAll('.day-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                // Update active button
                document.querySelectorAll('.day-btn').forEach(b => b.classList.remove('active'));
                this.classList.add('active');
                
                const day = this.dataset.day;
                const daySections = document.querySelectorAll('.day-section');
                
                daySections.forEach(section => {
                    if (day === 'all' || section.dataset.day === day) {
                        section.style.display = 'block';
                    } else {
                        section.style.display = 'none';
                    }
                });
            });
        });
        
        // Delete section
        function deleteSection(sectionId) {
            if(confirm('Are you sure you want to delete this section?')) {
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
                        location.reload();
                    } else {
                        alert('Error deleting section: ' + data.message);
                    }
                });
            }
        }
        
        // Export to PDF (mock function)
        function exportItinerary() {
            // In a real implementation, this would generate a PDF
            alert('PDF export would be generated here. In production, use a library like jsPDF or make a server request.');
            
            // Example server request:
            // fetch('export_pdf.php?trip_id=<?= $trip_id ?>')
            // .then(response => response.blob())
            // .then(blob => {
            //     const url = window.URL.createObjectURL(blob);
            //     const a = document.createElement('a');
            //     a.href = url;
            //     a.download = '<?= htmlspecialchars($trip["trip_name"]) ?>_itinerary.pdf';
            //     document.body.appendChild(a);
            //     a.click();
            //     a.remove();
            // });
        }
        
        // Print styles
        const printStyle = document.createElement('style');
        printStyle.innerHTML = `
            @media print {
                body { font-size: 12pt; }
                .container { max-width: 100%; padding: 0; }
                .itinerary-header { padding: 20px; margin-bottom: 20px; }
                .timeline-content { break-inside: avoid; }
                .day-section { break-inside: avoid; }
            }
        `;
        document.head.appendChild(printStyle);
    </script>
</body>
</html>