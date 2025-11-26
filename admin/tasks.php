<?php
/**
 * Admin Task Listing Page
 * Displays a list of all created tasks and allows deletion.
 */

// --- 1. CONFIGURATION ---
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

// --- 3. INCLUDES ---
// Ensure the model file exists
if (!file_exists(ROOT_PATH . 'models/task.php')) {
    die("Error: Missing file models/task.php");
}
require_once(ROOT_PATH . 'models/task.php');

// --- 4. HANDLE DELETE REQUEST ---
// This block runs if you click the "Delete" button
if (isset($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']);
    $task_model = new Task();
    
    // Call the delete function
    if ($task_model->deleteTask($delete_id)) {
        $_SESSION['success_message'] = "Task deleted successfully!";
    } else {
        $_SESSION['error_message'] = "Failed to delete task. Check database logs.";
    }
    
    // Refresh page to clear the URL parameters
    header("Location: tasks.php");
    exit();
}

// --- 5. GET DATA ---
try {
    $task_model = new Task();
    $tasks = $task_model->getAllTasks();
    $num_tasks = count($tasks);
} catch (Exception $e) {
    die("Database Error: " . $e->getMessage());
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin | Task List</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/style.css"> 
    <style>
        .header-actions { margin-bottom: 20px; text-align: right; }
        .btn-create { 
            background-color: #F4C542; color: #333; padding: 12px 20px; 
            border-radius: 8px; text-decoration: none; font-weight: bold; display: inline-block;
        }
        .data-table { width: 100%; border-collapse: collapse; margin-top: 20px; background: white;}
        .data-table th, .data-table td { border: 1px solid #ddd; padding: 12px; text-align: left; }
        .data-table th { background-color: #455A64; color: white; }
        
        /* Button Styles */
        .btn-action { 
            padding: 6px 10px; border-radius: 6px; text-decoration: none; 
            color: white; margin-right: 5px; font-size: 0.8rem; display: inline-block;
        }
        .btn-assign { background-color: #007bff; }
        .btn-edit { background-color: #28a745; }
        
        /* RED DELETE BUTTON */
        .btn-delete { background-color: #dc3545; }
        .btn-delete:hover { background-color: #c82333; }
        
        .skill-cell { font-style: italic; font-size: 0.95rem; }
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
        <h2>📋 Task Library</h2>
        
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

        <div class="header-actions">
            <a href="createTask.php" class="btn-create">✨ Create New Task</a>
        </div>

        <p>Total Tasks Created: <strong><?php echo $num_tasks; ?></strong></p>

        <div class="table-container">
            <?php if ($num_tasks > 0): ?>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Task Name</th>
                            <th>Required Skill Level</th>
                            <th>Date Created</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($tasks as $t): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($t['task_id']); ?></td>
                            <td><?php echo htmlspecialchars($t['name']); ?></td>
                            <td class="skill-cell"><?php echo htmlspecialchars($t['required_skill']); ?></td>
                            <td><?php echo date("Y-m-d", strtotime($t['created_at'])); ?></td>
                            <td>
                                <a href="assignTask.php?task_id=<?php echo $t['task_id']; ?>" class="btn-action btn-assign">Assign</a>
                                <a href="editTask.php?id=<?php echo $t['task_id']; ?>" class="btn-action btn-edit">Edit</a>
                                
                                <a href="tasks.php?delete_id=<?php echo $t['task_id']; ?>" 
                                   class="btn-action btn-delete"
                                   onclick="return confirm('Are you sure? This will delete the task and remove it from all participants.');">
                                   Delete
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>No tasks found.</p>
            <?php endif; ?>
        </div>
    </main>
</body>
</html>