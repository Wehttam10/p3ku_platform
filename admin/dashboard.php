<?php
/**
 * Admin Dashboard Page
 * Displays critical summaries and quick actions for management.
 */

// --- 1. CONFIGURATION (Essential to prevent blank pages) ---
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (!defined('ROOT_PATH')) define('ROOT_PATH', dirname(__DIR__) . '/');
if (!defined('BASE_URL')) define('BASE_URL', '/p3ku-main/'); 

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// --- 2. SECURITY CHECK ---
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: " . BASE_URL . "index.php");
    exit();
}

// --- 3. DATA RETRIEVAL ---
if (!file_exists(ROOT_PATH . 'models/dashboard.php')) {
    die("Error: Missing file models/dashboard.php");
}
require_once(ROOT_PATH . 'models/dashboard.php');

$dashboard_model = new DashboardModel();
$stats = $dashboard_model->getStats();

$total_participants = $stats['total_participants'] ?? 0;
$total_tasks = $stats['total_tasks'] ?? 0;
$assignments_active = $stats['assignments_in_progress'] ?? 0;

$admin_name = $_SESSION['user_name'] ?? 'Admin';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | P3KU</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/style.css"> 
    <style>
        main { padding: 20px; }
        h2 { color: #455A64; margin-bottom: 20px; }
        a { text-decoration: none; color: #333; }

        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }
        .action-btn {
            background-color: #F4C542;
            color: #333;
            padding: 20px;
            border-radius: 12px;
            font-size: 1.1rem;
            font-weight: bold;
            text-decoration: none;
            text-align: center;
            display: block;
            transition: background-color 0.2s;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .action-btn:hover { background-color: #e0b435; transform: translateY(-2px); }

        .summary-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }
        .summary-card {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            border-left: 5px solid;
        }
        .summary-card h3 { font-size: 1.1rem; color: #455A64; margin: 0 0 10px 0; }
        .summary-card .value { font-size: 2.5rem; font-weight: bold; }
        .summary-card .detail { color: #666; font-size: 0.9rem; margin-top: 10px; }

        .card-active { border-left-color: #2F8F2F; }
        .card-neutral { border-left-color: #455A64; }
    </style>
</head>
<body>
    <header>
        <h1>Admin Dashboard</h1>
        <nav>
            <a href="<?php echo BASE_URL; ?>admin/dashboard.php"  style="font-size:24px;"><b>Dashboard</b></a> | 
            <a href="reports.php" style="font-weight:bold; color:#FFC107;">Reports</a> |
            <a href="<?php echo BASE_URL; ?>controllers/authController.php?logout=1" style="color: red; font-weight: bold;">Logout</a> 
        </nav>
    </header>

    <main>
        <h2>📊 Welcome, <?php echo htmlspecialchars($admin_name); ?></h2>
        
        <p class="breadcrumbs">Admin > Dashboard</p>

        <h3>Quick Actions</h3>
        <div class="quick-actions">
            <a href="createTask.php" class="action-btn">
                ✍️ Create New Task
            </a>
            <a href="tasks.php" class="action-btn">
                🎯 Assign Task
            </a>
            <a href="participants.php" class="action-btn">
                👥 Manage Participants
            </a>
        </div>
        
        <hr>

        <h3>Key Performance Indicators</h3>
        <div class="summary-grid">
            
            <div class="summary-card card-active">
                <h3>Total Participants</h3>
                <div class="value"><?php echo $total_participants; ?></div>
                <div class="detail">Registered children in the system.</div>
            </div>

            <div class="summary-card card-neutral">
                <h3>Total Tasks Created</h3>
                <div class="value"><?php echo $total_tasks; ?></div>
                <div class="detail">Total available tasks in the library.</div>
            </div>

            <div class="summary-card card-neutral">
                <h3>Assignments In Progress</h3>
                <div class="value"><?php echo $assignments_active; ?></div>
                <div class="detail">Tasks currently being worked on.</div>
            </div>
            
        </div>
        
    </main>

    <footer>
    </footer>
</body>
</html>