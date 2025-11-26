<?php
/**
 * Participant PIN Login Page (Production Ready)
 */

// --- 1. CONFIGURATION ---
ini_set('display_errors', 1);
error_reporting(E_ALL);

define('ROOT_PATH', dirname(__DIR__) . '/');
define('BASE_URL', '/p3ku-main/'); 

session_set_cookie_params(0, '/');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once(ROOT_PATH . 'models/participant.php');

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pin = $_POST['pin'] ?? '';
    
    if (empty($pin)) {
        $error = "Please enter a PIN.";
    } else {
        try {
            $model = new Participant();
            $user = $model->loginByPin($pin);

            if ($user) {
                $_SESSION['user_id'] = $user['participant_id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['role'] = 'participant';
                
                session_write_close(); 
                
                header("Location: index.php"); 
                exit();
            } else {
                $error = "Incorrect PIN code.";
            }
        } catch (Exception $e) {
            $error = "System Error: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Child Login | P3KU</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/style.css">
    <style>
        body { 
            background-color: #E0F7FA; 
            display: flex; justify-content: center; align-items: center; 
            height: 100vh; font-family: 'Comic Sans MS', sans-serif; margin: 0;
        }
        .login-card { 
            background: white; padding: 40px; border-radius: 25px; 
            text-align: center; width: 320px; border: 5px solid #00BCD4;
            box-shadow: 0 10px 25px rgba(0,0,0,0.1); 
        }
        .pin-input { 
            font-size: 2.5rem; padding: 15px; width: 140px; 
            text-align: center; letter-spacing: 8px; border-radius: 15px; 
            border: 3px solid #ddd; margin: 20px 0; outline: none;
        }
        .pin-input:focus { border-color: #00BCD4; }
        .btn-go { 
            background: #FF9800; color: white; border: none; 
            padding: 15px 40px; font-size: 1.5rem; font-weight: bold;
            border-radius: 50px; cursor: pointer; width: 100%; 
            transition: transform 0.1s;
        }
        .btn-go:active { transform: translateY(3px); }
        .error { color: #D32F2F; background: #FFEBEE; padding: 10px; border-radius: 8px; margin-bottom: 10px; }
        .back-link { display: block; margin-top: 20px; color: #666; text-decoration: none; }
    </style>
</head>
<body>
    <div class="login-card">
        <h1>👋 Hello!</h1>
        <p>Enter your secret PIN.</p>
        
        <?php if ($error): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form method="POST">
            <input type="password" name="pin" class="pin-input" maxlength="4" placeholder="••••" required autofocus>
            <br>
            <button type="submit" class="btn-go">GO!</button>
        </form>

        <a href="<?php echo BASE_URL; ?>" class="back-link">← Back to Home</a>
    </div>
</body>
</html>