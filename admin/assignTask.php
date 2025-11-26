<?php
/**
 * Admin Task Assignment Page
 */

// --- 1. CONFIGURATION ---
ini_set('display_errors', 1);
error_reporting(E_ALL);

if (!defined('ROOT_PATH')) define('ROOT_PATH', dirname(__DIR__) . '/');
if (!defined('BASE_URL')) define('BASE_URL', '/p3ku-main/'); 

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once(ROOT_PATH . 'models/task.php');
require_once(ROOT_PATH . 'controllers/taskController.php'); 

// --- 2. HANDLE FORM SUBMISSION ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'assign') {
    TaskController::handleAssignment($_POST);
}

// --- 3. GET DATA ---
$task_id = filter_input(INPUT_GET, 'task_id', FILTER_VALIDATE_INT);

if (!$task_id && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . 'admin/tasks.php');
    exit;
}

if (!$task_id) {
    $task_id = $_POST['task_id'] ?? null;
}

$task_model = new Task();
$task = $task_model->getTaskWithSteps($task_id); 

if (!$task) {
    $_SESSION['error_message'] = "Task not found.";
    header('Location: ' . BASE_URL . 'admin/tasks.php');
    exit;
}

$active_participants = $task_model->getAllParticipants(); 

$success_message = $_SESSION['success_message'] ?? null;
$error_message = $_SESSION['error_message'] ?? null;
unset($_SESSION['success_message'], $_SESSION['error_message']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin | Assign Task</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/style.css"> 
    <style>
        a { text-decoration: none; color: #333; }
        .task-info-box { border-left: 5px solid #2F8F2F; padding: 15px; background-color: #f0fff0; margin-bottom: 30px; border-radius: 4px;}
        .assignment-list { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 15px; }
        .participant-item { border: 1px solid #ddd; padding: 15px; border-radius: 8px; cursor: pointer; display: flex; align-items: center; background: #fff; transition: 0.2s;}
        .participant-item:hover { background-color: #f8f9fa; border-color: #2F8F2F; }
        .participant-item input { margin-right: 15px; transform: scale(1.5); }
        .btn-assign { background-color: #F4C542; color: #333; padding: 15px 25px; border: none; border-radius: 12px; cursor: pointer; font-size: 1.2rem; margin-top: 30px; font-weight: bold; width: 100%; }
        .btn-assign:hover { background-color: #e0b43b; }
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
        <h2>🎯 Assign Task: <?php echo htmlspecialchars($task['name']); ?></h2>
        
        <?php if ($success_message): ?>
            <div style="color: green; margin-bottom: 15px;"><?php echo $success_message; ?></div>
        <?php endif; ?>
        <?php if ($error_message): ?>
            <div style="color: red; margin-bottom: 15px;"><?php echo $error_message; ?></div>
        <?php endif; ?>

        <div class="task-info-box">
            <p><strong>Required Skill:</strong> <?php echo htmlspecialchars($task['required_skill']); ?></p>
            <p><?php echo nl2br(htmlspecialchars($task['description'])); ?></p>
        </div>

        <h3>Select Participants</h3>

        <form action="" method="POST" id="assignForm">
            <input type="hidden" name="action" value="assign">
            <input type="hidden" name="task_id" value="<?php echo $task_id; ?>">

            <?php if (count($active_participants) > 0): ?>
                <div class="assignment-list">
                    <?php foreach ($active_participants as $p): ?>
                        <label class="participant-item">
                            <input type="checkbox" class="p-checkbox" name="participant_ids[]" value="<?php echo $p['participant_id']; ?>">
                            <div>
                                <strong><?php echo htmlspecialchars($p['name']); ?></strong><br>
                                <small>Skill: <?php echo htmlspecialchars($p['skill_level']); ?></small>
                            </div>
                        </label>
                    <?php endforeach; ?>
                </div>

                <button type="submit" class="btn-assign" id="submitBtn">
                    🚀 Assign Task to Selected Participants
                </button>
            <?php else: ?>
                <p>No active participants found.</p>
            <?php endif; ?>
        </form>
    </main>

    <script>
        const checkboxes = document.querySelectorAll('.p-checkbox');
        document.getElementById('assignForm').addEventListener('submit', function(e) {
            let checkedOne = Array.prototype.slice.call(checkboxes).some(x => x.checked);
            if (!checkedOne) {
                e.preventDefault();
                alert("Please select at least one participant.");
            }
        });
    </script>
</body>
</html>