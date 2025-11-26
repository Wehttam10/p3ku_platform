<?php
/**
 * Register New Child Page
 */

// --- 1. CONFIGURATION ---
ini_set('display_errors', 1);
error_reporting(E_ALL);

define('ROOT_PATH', dirname(__DIR__) . '/');
define('BASE_URL', '/p3ku-main/'); 

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// --- 2. INCLUDES ---
require_once(ROOT_PATH . 'config/auth.php');
require_once(ROOT_PATH . 'models/participant.php');

// --- 3. SECURITY CHECK ---
if (!is_parent()) {
    header("Location: " . BASE_URL . "index.php");
    exit();
}

// --- 4. HANDLE FORM SUBMISSION ---
$success_message = "";
$error_message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['child_name'] ?? '');
    $pin = trim($_POST['child_pin'] ?? '');
    $sensory = trim($_POST['sensory_details'] ?? '');
    
    if (empty($name) || empty($pin) || empty($sensory)) {
        $error_message = "All fields are required.";
    } elseif (strlen($pin) !== 4 || !ctype_digit($pin)) {
        $error_message = "PIN must be exactly 4 digits.";
    } else {
        $model = new Participant();
        
        $result = $model->createParticipant($_SESSION['user_id'], $name, $pin, $sensory);
        
        if ($result === true) {
            $success_message = "Child registered successfully! Pending Admin activation.";
            $name = $pin = $sensory = '';
        } else {
            $error_message = $result;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>P3ku | Register New Child</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/style.css"> 
    <style>
        a { text-decoration: none; color: #333; }
        .form-group { margin-bottom: 24px; }
        .form-group label {
            font-size: 1.1rem; font-weight: bold; display: block; margin-bottom: 8px; color: #455A64;
        }
        .form-control, textarea {
            width: 100%; padding: 16px; font-size: 1rem; border: 2px solid #ccc;
            border-radius: 12px; box-sizing: border-box;
        }
        .form-control:focus { border-color: #2F8F2F; outline: none; }
        .btn-primary {
            background-color: #2F8F2F; color: white; padding: 18px 30px; font-size: 1.2rem;
            border: none; border-radius: 16px; cursor: pointer; width: 100%; font-weight: bold;
        }
        .btn-primary:hover { background-color: #257a25; }
        .alert-success { background-color: #d4edda; color: #155724; padding: 15px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #c3e6cb; }
        .alert-error { background-color: #f8d7da; color: #721c24; padding: 15px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #f5c6cb; }
        small { color: #666; display: block; margin-top: 5px; }
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
        <h2>Register Your Child</h2>

        <p class="breadcrumbs">Parent > Register Child</p>

        <?php if ($success_message): ?>
            <div class="alert-success" role="alert"><?php echo htmlspecialchars($success_message); ?></div>
        <?php endif; ?>

        <?php if ($error_message): ?>
            <div class="alert-error" role="alert"><?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>

        <p>Please enter the details below. This information helps the admin assign appropriate tasks.</p>

        <form action="" method="POST">
            
            <div class="form-group">
                <label for="child_name">Child's Full Name</label>
                <input type="text" id="child_name" name="child_name" class="form-control" required 
                       value="<?php echo htmlspecialchars($name ?? ''); ?>">
                <small>Enter the name exactly as you wish it to appear on reports.</small>
            </div>

            <div class="form-group">
                <label for="child_pin">4-Digit PIN for Child Login</label>
                <input type="text" id="child_pin" name="child_pin" class="form-control" 
                       maxlength="4" pattern="\d{4}" placeholder="e.g., 1234" required
                       value="<?php echo htmlspecialchars($pin ?? ''); ?>">
                <small>This code will be used by your child to log in.</small>
            </div>
            
            <div class="form-group">
                <label for="sensory_details">Initial Sensory and Skill Details</label>
                <textarea id="sensory_details" name="sensory_details" rows="6" class="form-control" required><?php echo htmlspecialchars($sensory ?? ''); ?></textarea>
                <small>Describe skill level, preferred learning styles, and any sensory sensitivities.</small>
            </div>

            <button type="submit" class="btn-primary">
                ⭐ Register Child
            </button>
        </form>
    </main>
</body>
</html>