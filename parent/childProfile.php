<?php
/**
 * Parent Child Profile View
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
require_once(ROOT_PATH . 'models/participant.php'); 

// --- 3. SECURITY CHECK ---
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'parent') {
    header("Location: " . BASE_URL . "index.php");
    exit();
}

$parent_user_id = $_SESSION['user_id'] ?? 0;
$child_id = filter_input(INPUT_GET, 'child_id', FILTER_VALIDATE_INT);

if (!$child_id) {
    header('Location: dashboard.php');
    exit;
}

// --- 4. DATA RETRIEVAL ---
$participant_model = new Participant();
$child_data = $participant_model->getParticipantById($child_id);

if (!$child_data || $child_data['parent_user_id'] != $parent_user_id) {
    $_SESSION['error_message'] = "Access denied or child not found.";
    header('Location: dashboard.php');
    exit;
}

// --- 5. FIX: Handle Null Values Safeley ---
$name = htmlspecialchars($child_data['name'] ?? '');
$skill_level = htmlspecialchars($child_data['skill_level'] ?? 'Pending');
$sensory_details = htmlspecialchars($child_data['sensory_details'] ?? ''); 
$is_active = $child_data['is_active'] ?? 0;
$pin = htmlspecialchars($child_data['pin'] ?? '****');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Profile: <?php echo $name; ?></title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/style.css"> 
    <style>
        main { padding: 20px; }
        a { text-decoration: none; color: #333; }
        .card { background: white; padding: 25px; border-radius: 12px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); margin-bottom: 20px; }
        .status-tag { display: inline-block; padding: 5px 10px; border-radius: 5px; font-weight: bold; font-size: 0.9rem; }
        .status-Active { background-color: #e8f5e9; color: #2e7d32; }
        .status-Pending { background-color: #fff3e0; color: #ef6c00; }
        
        label { font-weight: bold; display: block; margin-top: 15px; color: #455A64; }
        textarea { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 8px; background-color: #f9f9f9; resize: none; }
        
        .btn-secondary { background-color: #607D8B; color: white; padding: 10px 15px; text-decoration: none; border-radius: 6px; }
        .btn-primary { background-color: #2F8F2F; color: white; padding: 12px 20px; text-decoration: none; border-radius: 8px; font-weight: bold; display: inline-block; }
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
        <h2>👤 Child Profile: <?php echo $name; ?></h2>
        <p class="breadcrumbs">Parent > My Child > Profile</p>

        <div class="card">
            <h3>Key Information</h3>
            
            <p><strong>Status:</strong> 
                <span class="status-tag status-<?php echo $is_active ? 'Active' : 'Pending'; ?>">
                    <?php echo $is_active ? 'Active' : 'Pending Review'; ?>
                </span>
            </p>
            
            <p><strong>Assigned Skill Level:</strong> 
                <strong style="color: #2F8F2F;"><?php echo $skill_level; ?></strong>
            </p>
            
            <p><strong>Login PIN:</strong> <?php echo $pin; ?></p>

            <hr>

            <h3>Initial Details Submitted</h3>
            <label for="sensory">Sensory & Skill Details:</label>
            <textarea id="sensory" rows="5" readonly><?php echo $sensory_details ?: 'No details provided.'; ?></textarea>
            <small style="color: #666;">This information is used by the Admin to assign tasks.</small>
        </div>

        <div style="margin-top: 30px;">
            <a href="childReport.php?child_id=<?php echo $child_id; ?>" class="btn-primary">
                📄 View Progress Report
            </a>
            <a href="dashboard.php" class="btn-secondary" style="margin-left: 10px;">
                Back to Dashboard
            </a>
        </div>
    </main>
</body>
</html>