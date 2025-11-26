<?php
/**
 * Participant Feedback / Self-Evaluation Page
 */

// --- 1. CONFIGURATION ---
ini_set('display_errors', 1);
error_reporting(E_ALL);

define('ROOT_PATH', dirname(__DIR__) . '/');
define('BASE_URL', '/p3ku-main/'); 

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// --- 2. SECURITY CHECK ---
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'participant') {
    header("Location: pinLogin.php");
    exit();
}

// --- 3. INCLUDE CONTROLLER ---
require_once(ROOT_PATH . 'controllers/taskController.php'); 

// --- 4. HANDLE POST REQUEST (Submission) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    TaskController::handleSubmitEvaluation($_POST);
}

// --- 5. GET ASSIGNMENT ID ---
$assignment_id = filter_input(INPUT_GET, 'assignment_id', FILTER_VALIDATE_INT);

if (!$assignment_id) {
    header("Location: index.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Great Job! | Feedback</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/style.css">
    <style>
        body { 
            background-color: #E0F7FA; 
            font-family: 'Comic Sans MS', 'Chalkboard SE', sans-serif; 
            display: flex; 
            justify-content: center; 
            align-items: center; 
            height: 100vh; 
            margin: 0; 
            text-align: center;
        }
        
        .feedback-card { 
            background: white; 
            padding: 40px; 
            border-radius: 30px; 
            box-shadow: 0 10px 30px rgba(0,0,0,0.15); 
            max-width: 500px; 
            width: 90%;
            border: 5px solid #00BCD4;
        }
        
        h1 { color: #0097A7; margin-bottom: 10px; font-size: 2.5rem; }
        p { font-size: 1.3rem; color: #555; margin-bottom: 30px; }
        
        .emoji-grid { 
            display: flex; 
            justify-content: center; 
            gap: 20px; 
            margin-bottom: 30px;
        }
        
        input[type="radio"] { display: none; }
        
        .emoji-label { 
            font-size: 3rem; 
            cursor: pointer; 
            padding: 10px; 
            border-radius: 50%; 
            transition: transform 0.2s, background 0.2s;
            border: 3px solid transparent;
        }
        
        .emoji-label:hover { transform: scale(1.2); background-color: #f0f0f0; }
        
        input[type="radio"]:checked + .emoji-label { 
            background-color: #FFEB3B; 
            border-color: #FBC02D; 
            transform: scale(1.3); 
        }
        
        .btn-submit { 
            background-color: #4CAF50; 
            color: white; 
            border: none; 
            padding: 15px 40px; 
            font-size: 1.5rem; 
            font-weight: bold; 
            border-radius: 50px; 
            cursor: pointer; 
            width: 100%; 
            box-shadow: 0 5px 0 #2E7D32;
            margin-top: 10px;
        }
        .btn-submit:active { transform: translateY(4px); box-shadow: none; }
    </style>
</head>
<body>

    <div class="feedback-card">
        <h1>You Did It! 🎉</h1>
        <p>How did you feel about this task?</p>
        
        <form action="" method="POST">
            <input type="hidden" name="assignment_id" value="<?php echo $assignment_id; ?>">
            
            <div class="emoji-grid">
                <label>
                    <input type="radio" name="emoji_sentiment" value="happy" required>
                    <span class="emoji-label" role="img" aria-label="Happy">😊</span>
                </label>
                
                <label>
                    <input type="radio" name="emoji_sentiment" value="neutral">
                    <span class="emoji-label" role="img" aria-label="Neutral">😐</span>
                </label>
                
                <label>
                    <input type="radio" name="emoji_sentiment" value="hard">
                    <span class="emoji-label" role="img" aria-label="Hard">😓</span>
                </label>
            </div>
            
            <button type="submit" class="btn-submit">Finish Task</button>
        </form>
    </div>

</body>
</html>