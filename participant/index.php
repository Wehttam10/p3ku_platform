<?php
/**
 * Participant Dashboard (My Tasks) - FIXED SESSION BUG
 */
ini_set('display_errors', 1);
error_reporting(E_ALL);

define('ROOT_PATH', dirname(__DIR__) . '/');
define('BASE_URL', '/p3ku-main/'); 

session_set_cookie_params(0, '/');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'participant') {
    header("Location: pinLogin.php");
    exit();
}

if (!file_exists(ROOT_PATH . 'models/task.php')) {
    die("Error: Could not find models/task.php");
}
require_once(ROOT_PATH . 'models/task.php');

try {
    $model = new Task();
    $my_tasks = $model->getParticipantTasks($_SESSION['user_id']);
} catch (Exception $e) {
    die("Error loading tasks: " . $e->getMessage());
}

$user_name = $_SESSION['user_name'] ?? 'Participant';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Tasks | P3KU</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/style.css">
    <style>
        body { font-family: 'Comic Sans MS', 'Chalkboard SE', sans-serif; background-color: #E0F7FA; margin: 0; }
        header { background-color: #00BCD4; padding: 15px 20px; color: white; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        h1 { margin: 0; font-size: 1.5rem; }
        .logout { background: white; color: #00BCD4; padding: 8px 15px; text-decoration: none; border-radius: 20px; font-weight: bold; border: 2px solid white; }
        .task-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 25px; padding: 25px; max-width: 1200px; margin: 0 auto; }
        .task-card { background: white; border-radius: 20px; overflow: hidden; box-shadow: 0 5px 15px rgba(0,0,0,0.1); border-bottom: 8px solid #ddd; display: flex; flex-direction: column; justify-content: space-between; }
        .card-body { padding: 20px; flex-grow: 1; }
        h3 { margin-top: 10px; color: #333; font-size: 1.3rem; }
        .status-badge { display: inline-block; padding: 5px 12px; border-radius: 15px; font-size: 0.8rem; font-weight: bold; color: white; }
        .status-Pending { background-color: #FFC107; color: #333; }
        .status-InProgress { background-color: #2196F3; }
        .status-Completed { background-color: #4CAF50; }
        .btn-start { display: block; width: 100%; padding: 15px; background: #FF9800; color: white; text-align: center; text-decoration: none; font-size: 1.2rem; font-weight: bold; border: none; cursor: pointer; }
        .btn-completed { background: #CFD8DC; color: #78909C; cursor: default; }
    </style>
</head>
<body>
    <header>
        <h1>🌟 My Tasks</h1>
        <div style="display: flex; align-items: center; gap: 15px;">
            <span>Hi, <?php echo htmlspecialchars($user_name); ?>!</span>
            <a href="pinLogin.php?logout=1" class="logout">Exit</a>
        </div>
    </header>

    <main>
        <?php if (empty($my_tasks)): ?>
            <div style="text-align: center; padding: 50px;">
                <h2>All caught up!</h2>
                <p>You have no tasks right now.</p>
            </div>
        <?php else: ?>
            <div class="task-grid">
                <?php foreach ($my_tasks as $task): 
                    $statusClass = str_replace(' ', '', $task['status']); 
                ?>
                <div class="task-card">
                    <div class="card-body">
                        <span class="status-badge status-<?php echo $statusClass; ?>">
                            <?php echo htmlspecialchars($task['status']); ?>
                        </span>
                        <h3><?php echo htmlspecialchars($task['name']); ?></h3>
                        <p><?php echo htmlspecialchars($task['description']); ?></p>
                    </div>
                    <?php if ($task['status'] === 'Completed'): ?>
                        <div class="btn-start btn-completed">Done ✅</div>
                    <?php else: ?>
                        <a href="doTask.php?assignment_id=<?php echo $task['assignment_id']; ?>" class="btn-start">
                            <?php echo ($task['status'] === 'In Progress') ? 'Continue ▶️' : 'Start ▶️'; ?>
                        </a>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </main>
</body>
</html>