<?php
/**
 * Admin View/Edit Participant Profile Page
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
if (!is_admin()) {
    header("Location: " . BASE_URL . "index.php");
    exit();
}

// --- 4. HANDLE FORM SUBMISSION (UPDATE SKILL) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update_skill') {
    
    $p_id = $_POST['participant_id'];
    $new_skill = $_POST['skill_level'];
    $is_active = isset($_POST['is_active']) ? 1 : 0; 

    $model = new Participant();
    
    if ($model->updateSkillLevel($p_id, $new_skill, $is_active)) {
        $_SESSION['success_message'] = "Profile updated successfully!";
    } else {
        $_SESSION['error_message'] = "Failed to update profile.";
    }

    header("Location: view_profile.php?id=" . $p_id);
    exit();
}

// --- 5. DATA RETRIEVAL ---
$participant_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$participant_id) {
    header('Location: participants.php');
    exit;
}

$participant_model = new Participant();
$participant = $participant_model->getParticipantById($participant_id);

if (!$participant) {
    $_SESSION['error_message'] = "Participant not found.";
    header('Location: participants.php');
    exit;
}

$success_message = $_SESSION['success_message'] ?? null;
$error_message = $_SESSION['error_message'] ?? null;
unset($_SESSION['success_message'], $_SESSION['error_message']);

$skill_levels = [
    'Pending' => 'Pending Review',
    'Level 1: Basic Visual (Red)' => 'Level 1: Basic Visual Tasks',
    'Level 2: Simple Steps (Yellow)' => 'Level 2: Simple Multi-Step Tasks',
    'Level 3: Guided Independence (Blue)' => 'Level 3: Guided Independent Tasks',
    'Level 4: Full Independence (Green)' => 'Level 4: Fully Independent Tasks'
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin | Review Profile</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/style.css"> 
    <style>
        a { text-decoration: none; color: #333; }
        .profile-card { border: 1px solid #ccc; padding: 20px; border-radius: 12px; margin-bottom: 30px; background-color: #FAFAFA; }
        .details-section p { margin: 10px 0; }
        .details-section strong { display: inline-block; width: 150px; }
        .form-control, .btn-primary { padding: 12px; font-size: 1.1rem; border-radius: 8px; width: 100%; box-sizing: border-box;}
        .btn-update { background-color: #2F8F2F; color: white; border: none; cursor: pointer; font-weight: bold; }
        .btn-update:hover { background-color: #257a25; }
        textarea { width: 100%; min-height: 100px; padding: 10px; margin-top: 5px;}
        .checkbox-wrapper { display: flex; align-items: center; background: #e8f5e9; padding: 15px; border-radius: 8px; border: 1px solid #c8e6c9; }
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
        <h2>👤 Review Participant: <?php echo htmlspecialchars($participant['name']); ?></h2>
        
        <p class="breadcrumbs">Admin > Participants > Review</p>

        <?php if ($success_message): ?>
            <div style="background:#d4edda; color:#155724; padding:15px; border-radius:8px; margin-bottom:20px;">
                <?php echo htmlspecialchars($success_message); ?>
            </div>
        <?php endif; ?>

        <div class="profile-card">
            <h3>Initial Registration Details</h3>
            <div class="details-section">
                <p><strong>Registered Name:</strong> <?php echo htmlspecialchars($participant['name']); ?></p>
                <p><strong>Login PIN:</strong> <?php echo htmlspecialchars($participant['pin']); ?></p>
                <p><strong>Current Status:</strong> 
                    <span style="font-weight: bold; color: <?php echo $participant['skill_level'] === 'Pending' ? '#F4C542' : '#2F8F2F'; ?>;">
                    <?php echo htmlspecialchars($participant['skill_level']); ?>
                    </span>
                </p>
                
                <hr>
                <h4>Parent's Notes (Sensory/Skills)</h4>
                <textarea readonly class="form-control" style="background:#eee;"><?php echo htmlspecialchars($participant['sensory_details']); ?></textarea>
            </div>
        </div>

        <h3>Assign Skill Level & Activate Account</h3>
        
        <form action="" method="POST">
            <input type="hidden" name="action" value="update_skill">
            <input type="hidden" name="participant_id" value="<?php echo $participant['participant_id']; ?>">

            <div style="margin-bottom: 20px;">
                <label for="skill_level" style="font-weight:bold; display:block; margin-bottom:5px;">Assign New Skill Level:</label>
                <select name="skill_level" id="skill_level" class="form-control" required>
                    <?php foreach ($skill_levels as $value => $label): ?>
                        <option value="<?php echo htmlspecialchars($value); ?>" 
                                <?php echo ($participant['skill_level'] === $value) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($label); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="checkbox-wrapper">
                <input type="checkbox" name="is_active" value="1" 
                       id="activate_check"
                       style="width: 20px; height: 20px; margin-right: 15px; cursor: pointer;"
                       <?php echo ($participant['is_active'] == 1) ? 'checked' : ''; ?>>
                <label for="activate_check" style="cursor: pointer;">
                    <strong>Activate Participant Account</strong><br>
                    <small>Check this box to allow the child to log in using their PIN.</small>
                </label>
            </div>

            <button type="submit" class="btn-primary btn-update" style="margin-top: 30px;">
                ✅ Update Skill Level & Save Changes
            </button>
        </form>
    </main>
</body>
</html>