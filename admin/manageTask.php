<?php
/**
 * Admin Manage Participant Tasks
 * Filename: manageTask.php
 */

// --- 1. CONFIGURATION ---
ini_set('display_errors', 1);
error_reporting(E_ALL);

if (!defined('ROOT_PATH')) define('ROOT_PATH', dirname(__DIR__) . '/');
if (!defined('BASE_URL')) define('BASE_URL', '/p3ku-main/'); 

if (session_status() === PHP_SESSION_NONE) session_start();

// --- 2. SECURITY & INCLUDES ---
require_once(ROOT_PATH . 'config/auth.php'); 
require_once(ROOT_PATH . 'models/task.php'); 
require_once(ROOT_PATH . 'controllers/taskController.php');

if (!is_admin()) {
    header("Location: " . BASE_URL . "index.php");
    exit();
}

// --- 3. HANDLE DELETE REQUEST ---
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    TaskController::handleDeleteAssignment($_GET);
}

// --- 4. GET DATA ---
$model = new Task();

// FIX: Use the existing general function, then filter for active ones
// This ensures compatibility with the "Ultimate" Task Model
$all_data = $model->getAllAssignmentDetails(); 

$assignments = [];
if (!empty($all_data)) {
    foreach ($all_data as $row) {
        // Only show Pending or In Progress tasks (Active)
        if ($row['status'] === 'Pending' || $row['status'] === 'In Progress') {
            $assignments[] = $row;
        }
    }
}

// Get messages
$success_message = $_SESSION['success_message'] ?? null;
$error_message = $_SESSION['error_message'] ?? null;
unset($_SESSION['success_message'], $_SESSION['error_message']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin | Manage Tasks</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/style.css">
    <style>
        .table-container { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); margin-top: 20px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 15px; border-bottom: 1px solid #eee; text-align: left; }
        th { background-color: #455A64; color: white; }
        
        .status-Pending { color: orange; font-weight: bold; }
        .status-InProgress { color: blue; font-weight: bold; }
        
        .btn-delete { 
            background-color: #D32F2F; color: white; padding: 8px 12px; 
            text-decoration: none; border-radius: 6px; font-size: 0.9rem; font-weight: bold;
        }
        .btn-delete:hover { background-color: #B71C1C; }
    </style>
</head>
<body>
    <header>
        <h1>Admin Dashboard</h1>
        <nav>
            <a href="<?php echo BASE_URL; ?>admin/dashboard.php"><b>Dashboard</b></a> | 
            <a href="reports.php" style="font-weight:bold; color:#FFC107;">Reports</a> |
            <a href="<?php echo BASE_URL; ?>controllers/authController.php?logout=1" style="color: red; font-weight: bold;">Logout</a> 
        </nav>
    </header>

    <main>
        <h2>📋 Manage Active Assignments</h2>
        <p>Cancel tasks that were assigned by mistake.</p>

        <?php if ($success_message): ?>
            <div style="color: green; background: #e8f5e9; padding: 10px; border-radius: 5px; margin-bottom: 15px;">
                <?php echo htmlspecialchars($success_message); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($error_message): ?>
            <div style="color: red; background: #ffebee; padding: 10px; border-radius: 5px; margin-bottom: 15px;">
                <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>

        <div class="table-container">
            <?php if (empty($assignments)): ?>
                <p>No active assignments found.</p>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Participant</th>
                            <th>Task Name</th>
                            <th>Status</th>
                            <th>Assigned Date</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($assignments as $a): 
                            $statusClass = str_replace(' ', '', $a['status']);
                        ?>
                        <tr>
                            <td><?php echo htmlspecialchars($a['participant_name']); ?></td>
                            <td><?php echo htmlspecialchars($a['task_name']); ?></td>
                            <td><span class="status-<?php echo $statusClass; ?>"><?php echo htmlspecialchars($a['status']); ?></span></td>
                            <td><?php echo date("M j, Y", strtotime($a['assigned_at'])); ?></td>
                            <td>
                                <a href="manageTask.php?action=delete&id=<?php echo $a['assignment_id']; ?>"
                                   class="btn-delete"
                                   onclick="return confirm('Are you sure you want to cancel this task?');">
                                   🗑️ Cancel
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </main>
</body>
</html>