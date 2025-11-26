<?php
/**
 * P3KU - Registration Page
 */
ini_set('display_errors', 1);
error_reporting(E_ALL);

define('ROOT_PATH', __DIR__ . '/');
define('URL_ROOT', '/p3ku-main/');

session_start();

$error = $_SESSION['register_error'] ?? null;
unset($_SESSION['register_error']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Register | P3KU</title>
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
        .auth-card {
            background: white;
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
            width: 400px;
        }
        h2 {
            text-align: center;
            color: #388E3C;
            margin-bottom: 20px;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            color: #555;
            font-weight: 500;
        }
        .form-control {
            width: 100%;
            padding: 10px;
            border: 2px solid #E8F5E9;
            border-radius: 8px;
            box-sizing: border-box;
            background-color: #FAFAFA;
            transition: border-color 0.3s;
        }
        .form-control:focus {
            border-color: #66BB6A;
            outline: none;
            background-color: #fff;
        }
        .btn-primary {
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, #81C784 0%, #4CAF50 100%);
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 1rem;
            font-weight: bold;
            box-shadow: 0 4px 6px rgba(76, 175, 80, 0.2);
            transition: transform 0.1s, opacity 0.3s;
        }
        .btn-primary:hover {
            opacity: 0.9;
            transform: translateY(-1px);
        }
        .alert-error {
            color: #C62828;
            background: #FFEBEE;
            padding: 10px;
            border-radius: 8px;
            margin-bottom: 15px;
            text-align: center;
            border: 1px solid #C62828;
        }
        .link {
            display: block;
            text-align: center;
            margin-top: 15px;
            color: #4CAF50;
            text-decoration: none;
            font-weight: bold;
        }
        .link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>

    <div class="auth-card">
        <h2>Create Parent Account</h2>

        <?php if ($error): ?>
            <div class="alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form action="<?php echo URL_ROOT; ?>controllers/authController.php" method="POST">
            <input type="hidden" name="action" value="register">

            <div class="form-group">
                <label>Full Name</label>
                <input type="text" name="name" class="form-control" required>
            </div>

            <div class="form-group">
                <label>Email Address</label>
                <input type="email" name="email" class="form-control" required>
            </div>

            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" class="form-control" required>
            </div>

            <div class="form-group">
                <label>Confirm Password</label>
                <input type="password" name="confirm_password" class="form-control" required>
            </div>

            <button type="submit" class="btn-primary">Register</button>
        </form>

        <a href="index.php" class="link">Already have an account? Login</a>
    </div>

</body>
</html>