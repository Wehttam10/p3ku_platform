<?php
/**
 * Parent Child Progress Report Page
 */

// --- 1. CONFIGURATION ---
ini_set('display_errors', 1);
error_reporting(E_ALL);

if (!defined('ROOT_PATH')) define('ROOT_PATH', dirname(__DIR__) . '/');
if (!defined('BASE_URL')) define('BASE_URL', '/p3ku-main/'); 

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// --- 2. INCLUDES ---
require_once(ROOT_PATH . 'config/auth.php'); 
require_once(ROOT_PATH . 'models/report.php'); 
require_once(ROOT_PATH . 'models/participant.php');

// --- 3. SECURITY CHECK ---
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'parent') {
    header("Location: " . BASE_URL . "index.php");
    exit();
}

// --- 4. GET DATA ---
$child_id = filter_input(INPUT_GET, 'child_id', FILTER_VALIDATE_INT);
$parent_user_id = $_SESSION['user_id'] ?? 0;

if (!$child_id) {
    header('Location: dashboard.php');
    exit;
}

$report_model = new Report();
$participant_model = new Participant();

$child_data = $participant_model->getParticipantById($child_id);

if (!$child_data || $child_data['parent_user_id'] != $parent_user_id) {
    $_SESSION['error_message'] = "Report not accessible or child not found.";
    header('Location: dashboard.php');
    exit;
}

$report_data = $report_model->getParentReportData($child_id);
$summary = $report_data['summary'] ?? [];
$history = $report_data['history'] ?? [];

$child_name = htmlspecialchars($child_data['name']);
$current_skill = htmlspecialchars($child_data['skill_level']);
$tasks_completed = $summary['tasks_completed'] ?? 0;

$emoji_map = [
    'happy' => '😊', 'calm' => '😌', 'neutral' => '😐',
    'frustrated' => '😤', 'sad' => '😔', 'hard' => '😓'
];

$dominant_mood = '—';
$dominant_icon = '❓';

if (!empty($history)) {
    $emojis = array_column($history, 'emoji_sentiment');
    if (!empty($emojis)) {
        $counts = array_count_values($emojis);
        arsort($counts);
        $top_sentiment = array_key_first($counts);
        $dominant_mood = ucfirst($top_sentiment);
        $dominant_icon = $emoji_map[$top_sentiment] ?? '❓';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Report: <?php echo $child_name; ?></title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/style.css"> 
    <style>
        main { padding: 20px; }
        a { text-decoration: none; color: #333; }
        .report-summary-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 40px; }
        .summary-card { background: white; padding: 20px; border-radius: 12px; box-shadow: 0 3px 8px rgba(0,0,0,0.1); border-bottom: 5px solid #F4C542; text-align: center; }
        .summary-card .value { font-size: 3rem; font-weight: bold; color: #455A64; }
        .summary-card h4 { font-size: 1rem; color: #666; margin-top: 5px; margin-bottom: 0; }

        .history-list { list-style: none; padding: 0; }
        .history-item {
            background: white;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
            border-left: 4px solid #ccc;
        }
        .sentiment-icon { font-size: 2.5rem; }
        .task-details { text-align: left; flex-grow: 1; margin-left: 15px; }
        .task-details strong { display: block; font-size: 1.1rem; color: #455A64; }
        .task-details span { font-size: 0.9rem; color: #666; }
        
        .btn-download {
            background-color: #455A64; color: white; padding: 10px 15px; 
            border-radius: 8px; text-decoration: none; display: inline-block; margin-top: 20px;
        }
        .btn-download:hover { background-color: #37474F; }
    </style>
</head>
<body>
    <header>
        <h1>Parent Dashboard</h1>
        <nav>
            <a href="dashboard.php" style="font-weight:bold;">Dashboard</a> |
            <a href="<?php echo BASE_URL; ?>controllers/authController.php?logout=1" style="color: red;">Logout</a>
        </nav>
    </header>

    <main>
        <h2>📊 Report: <?php echo $child_name; ?></h2>
        
        <p class="breadcrumbs">Parent > My Child > Report</p>

        <div class="report-summary-grid">
            <div class="summary-card">
                <div class="value">📚</div>
                <h4>Skill Level</h4>
                <div style="font-size: 1.1rem; font-weight: bold; color: #2F8F2F; margin-top: 10px;">
                    <?php echo $current_skill; ?>
                </div>
            </div>
            
            <div class="summary-card">
                <div class="value"><?php echo $tasks_completed; ?></div>
                <h4>Tasks Completed</h4>
            </div>

            <div class="summary-card">
                <div class="value"><?php echo $dominant_icon; ?></div>
                <h4>Most Frequent Feeling</h4>
                <div style="color: #666;"><?php echo $dominant_mood; ?></div>
            </div>
        </div>

        <h3>Recent Task History</h3>
        
        <ul class="history-list">
            <?php if (!empty($history)): ?>
                <?php foreach ($history as $h): ?>
                    <li class="history-item">
                        <div style="display:flex; align-items:center;">
                            <span class="sentiment-icon" role="img" aria-label="Mood">
                                <?php echo $emoji_map[$h['emoji_sentiment']] ?? '❓'; ?>
                            </span>
                            <div class="task-details">
                                <strong><?php echo htmlspecialchars($h['task_name']); ?></strong><br>
                                <span>Completed: <?php echo date("M j, Y, g:i a", strtotime($h['evaluated_at'])); ?></span>
                            </div>
                        </div>
                    </li>
                <?php endforeach; ?>
            <?php else: ?>
                <div style="text-align:center; padding:20px; background:#fff;">
                    <p>No completed tasks found for <?php echo $child_name; ?> yet.</p>
                </div>
            <?php endif; ?>
        </ul>
        
        <a href="#" class="btn-download" onclick="alert('PDF Generation feature coming soon!'); return false;">
            ⬇️ Download Full Report (PDF)
        </a>

    </main>
</body>
</html>