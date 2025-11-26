<?php
/**
 * P3KU Platform - Main Landing/Login Page
 */

// --- 1. CONFIGURATION ---
ini_set('display_errors', 1);
error_reporting(E_ALL);

define('ROOT_PATH', __DIR__ . '/');
define('URL_ROOT', '/p3ku-main/');

session_start();

if (file_exists(ROOT_PATH . 'controllers/authController.php')) {
    require_once(ROOT_PATH . 'controllers/authController.php');
}

// --- 2. CAPTURE MESSAGES ---
$error_message = $_SESSION['login_error'] ?? null;
unset($_SESSION['login_error']);

$success_message = $_SESSION['login_success'] ?? null;
unset($_SESSION['login_success']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>P3KU Platform - Login</title>
    <link rel="stylesheet" href="<?php echo URL_ROOT; ?>assets/css/style.css">
    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            background: linear-gradient(135deg, #A5D6A7 0%, #66BB6A 100%);
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }

        .login-container {
            background: #fff;
            border-radius: 15px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
            max-width: 900px;
            width: 100%;
            display: flex;
            overflow: hidden;
        }

        .welcome-section {
            background: linear-gradient(135deg, #81C784 0%, #4CAF50 100%);
            color: #fff;
            padding: 60px 40px;
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        
        .welcome-section h1 { text-shadow: 0 2px 4px rgba(0,0,0,0.1); }

        .login-section {
            padding: 60px 40px;
            flex: 1;
        }

        .login-section h2 {
            margin-bottom: 30px;
            color: #388E3C;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #555;
            font-weight: 500;
        }

        .form-group input {
            width: 100%;
            padding: 12px;
            border: 2px solid #E8F5E9;
            border-radius: 8px;
            font-size: 1em;
            box-sizing: border-box;
            background-color: #FAFAFA;
            transition: border-color 0.3s;
        }

        .form-group input:focus {
            border-color: #66BB6A;
            background-color: #fff;
            outline: none;
        }

        .btn {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #81C784 0%, #4CAF50 100%);
            color: #fff;
            border: none;
            border-radius: 8px;
            font-size: 1.1em;
            font-weight: bold;
            cursor: pointer;
            transition: transform 0.1s, opacity 0.3s;
            box-shadow: 0 4px 6px rgba(76, 175, 80, 0.2);
        }

        .btn:hover {
            opacity: 0.9;
            transform: translateY(-1px);
        }

        .child-login-link {
            text-align: center;
            margin-top: 25px;
        }

        .child-login-link a {
            color: #4CAF50;
            text-decoration: none;
            font-weight: bold;
            font-size: 1.1rem;
            padding: 12px 20px;
            border: 2px solid #4CAF50;
            border-radius: 10px;
            display: inline-block;
            transition: all 0.3s;
        }

        .child-login-link a:hover {
            background-color: #4CAF50;
            color: white;
            text-decoration: none;
        }
        
        .alert-error {
            background: #FFEBEE;
            color: #C62828;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 5px solid #C62828;
        }
        
        .alert-success {
            background: #E8F5E9;
            color: #2E7D32;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 5px solid #2E7D32;
        }

        @media (max-width: 768px) {
            .login-container { flex-direction: column; }
            .welcome-section, .login-section { padding: 40px 30px; }
        }
    </style>
</head>
<body>

<div class="login-container">
    <div class="welcome-section">
        <h1>Welcome to P3KU</h1>
        <p>Platform to support children with special needs through personalized tasks and skill tracking.</p>
    </div>

    <div class="login-section">
        <h2>Admin / Parent Login</h2>

        <?php if ($success_message): ?>
            <div class="alert-success">
                <?php echo htmlspecialchars($success_message); ?>
            </div>
        <?php endif; ?>

        <?php if ($error_message): ?>
            <div class="alert-error">
                <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="<?php echo URL_ROOT; ?>controllers/authController.php">
            <input type="hidden" name="action" value="login">

            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" placeholder="admin@example.com" required>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" placeholder="Enter your password" required>
            </div>

            <button type="submit" class="btn">Login</button>
        </form>

        <div class="child-login-link">
            <a href="<?php echo URL_ROOT; ?>participant/pinLogin.php">Child PIN Login →</a>
        </div>
        
        <div style="text-align: center; margin-top: 15px;">
            <span style="color:#888;">Don't have an account?</span> 
            <a href="register.php" style="color: #4CAF50; text-decoration: none; font-weight:bold;">Sign up here</a>
        </div>
    </div>
</div>

</body>
</html>