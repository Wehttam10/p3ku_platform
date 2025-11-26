<?php
/**
 * Admin Reports Page
 */

// --- 1. CONFIGURATION ---
ini_set('display_errors', 1);
error_reporting(E_ALL);

if (!defined('ROOT_PATH')) define('ROOT_PATH', dirname(__DIR__) . '/');
if (!defined('BASE_URL')) define('BASE_URL', '/p3ku-main/'); 

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once(ROOT_PATH . 'config/auth.php'); 
require_once(ROOT_PATH . 'models/task.php');

// --- 2. SECURITY CHECK ---
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: " . BASE_URL . "index.php");
    exit();
}

// --- 3. HANDLE FILTERS ---
$status_filter = $_GET['status'] ?? 'all';
$skill_filter = $_GET['skill'] ?? 'all';

// --- 4. GET DATA ---
$task_model = new Task();
$all_assignments = $task_model->getAllAssignmentDetails([
    'status' => $status_filter,
    'required_skill' => $skill_filter
]);

// --- 5. CALCULATE STATS ---
$total_shown = count($all_assignments);
$completed_count = 0;
$happy_count = 0;

foreach ($all_assignments as $a) {
    if ($a['status'] === 'Completed') $completed_count++;
    if (!empty($a['emoji_sentiment']) && $a['emoji_sentiment'] === 'happy') $happy_count++;
}

function getEmoji($sentiment) {
    switch ($sentiment) {
        case 'happy': return '😊';
        case 'neutral': return '😐';
        case 'hard': return '😓';
        default: return '—';
    }
}

$skill_levels = [
    'Level 1: Basic Visual (Red)' => 'Level 1: Basic Visual',
    'Level 2: Simple Steps (Yellow)' => 'Level 2: Simple Steps',
    'Level 3: Guided Independence (Blue)' => 'Level 3: Guided',
    'Level 4: Full Independence (Green)' => 'Level 4: Independent'
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin | Reports</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/style.css"> 
    <style>
        a { text-decoration: none; color: #333; }

        .filter-bar { 
            background: white; padding: 20px; border-radius: 12px; 
            display: flex; gap: 15px; align-items: flex-end; box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .filter-group { display: flex; flex-direction: column; }
        .filter-group label { font-weight: bold; font-size: 0.9rem; margin-bottom: 5px; color: #555; }
        .filter-control { padding: 10px; border: 1px solid #ccc; border-radius: 6px; min-width: 200px; }
        .btn-filter { 
            background-color: #2F8F2F; color: white; padding: 10px 20px; 
            border: none; border-radius: 6px; cursor: pointer; font-weight: bold; height: 40px;
        }
        
        .summary-section { display: flex; gap: 20px; margin-bottom: 30px; }
        .summary-box { 
            flex: 1; background: white; padding: 20px; border-radius: 12px; 
            box-shadow: 0 2px 5px rgba(0,0,0,0.1); border-left: 5px solid #ccc;
        }
        .summary-box h4 { margin: 0; color: #666; font-size: 0.9rem; }
        .summary-box .val { font-size: 2rem; font-weight: bold; color: #333; margin-top: 5px; }
        
        .data-table { width: 100%; border-collapse: collapse; background: white; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .data-table th, .data-table td { padding: 12px 15px; text-align: left; border-bottom: 1px solid #eee; }
        .data-table th { background-color: #455A64; color: white; }
        
        .status-Completed { color: green; font-weight: bold; }
        .status-Pending { color: orange; font-weight: bold; }
        .status-InProgress { color: blue; font-weight: bold; }
    </style>
</head>
<body>
    <header>
        <h1>Admin Dashboard</h1>
        <nav>
            <a href="<?php echo BASE_URL; ?>admin/dashboard.php"><b>Dashboard</b></a> | 
            <a href="reports.php" style="font-weight:bold; color:#FFC107; font-size:24px">Reports</a> |
            <a href="<?php echo BASE_URL; ?>controllers/authController.php?logout=1" style="color: red; font-weight: bold;">Logout</a> 
        </nav>
    </header>

    <main>
        <h2>📈 Detailed Reports</h2>

        <div class="summary-section">
            <div class="summary-box" style="border-left-color: #F4C542;">
                <h4>Total Assignments Shown</h4>
                <div class="val"><?php echo $total_shown; ?></div>
            </div>
            <div class="summary-box" style="border-left-color: #2F8F2F;">
                <h4>Completed</h4>
                <div class="val"><?php echo $completed_count; ?></div>
            </div>
            <div class="summary-box" style="border-left-color: #2196F3;">
                <h4>Happy Feedback 😊</h4>
                <div class="val"><?php echo $happy_count; ?></div>
            </div>
        </div>

        <form action="" method="GET" class="filter-bar">
            <div class="filter-group">
                <label>Filter by Status:</label>
                <select name="status" class="filter-control">
                    <option value="all">-- All Statuses --</option>
                    <option value="Pending" <?php echo $status_filter === 'Pending' ? 'selected' : ''; ?>>Pending</option>
                    <option value="In Progress" <?php echo $status_filter === 'In Progress' ? 'selected' : ''; ?>>In Progress</option>
                    <option value="Completed" <?php echo $status_filter === 'Completed' ? 'selected' : ''; ?>>Completed</option>
                </select>
            </div>
            
            <div class="filter-group">
                <label>Filter by Skill Level:</label>
                <select name="skill" class="filter-control">
                    <option value="all">-- All Skills --</option>
                    <?php foreach ($skill_levels as $val => $label): ?>
                        <option value="<?php echo htmlspecialchars($val); ?>" <?php echo $skill_filter === $val ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($label); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <button type="submit" class="btn-filter">Apply Filters</button>
            <a href="reports.php" style="margin-left: 10px; color: #666; align-self: center;">Reset</a>
        </form>

        <table class="data-table">
            <thead>
                <tr>
                    <th>Participant</th>
                    <th>Task Name</th>
                    <th>Required Skill</th>
                    <th>Status</th>
                    <th>Feedback</th>
                    <th>Assigned Date</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($total_shown > 0): ?>
                    <?php foreach ($all_assignments as $row): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['participant_name']); ?></td>
                        <td><?php echo htmlspecialchars($row['task_name']); ?></td>
                        <td><small><?php echo htmlspecialchars($row['required_skill']); ?></small></td>
                        <td class="status-<?php echo str_replace(' ', '', $row['status']); ?>">
                            <?php echo htmlspecialchars($row['status']); ?>
                        </td>
                        <td style="font-size: 1.5rem; text-align: center;">
                            <?php echo getEmoji($row['emoji_sentiment'] ?? ''); ?>
                        </td>
                        <td><?php echo date("Y-m-d", strtotime($row['assigned_at'])); ?></td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" style="text-align:center; padding:30px;">No data found matching your filters.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </main>
</body>
</html>