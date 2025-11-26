<?php
/**
 * Parent Dashboard Page - Final Version with Profile & Report Links
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

// --- 4. GET DATA ---
$parent_name = $_SESSION['user_name'] ?? 'Parent'; 
$parent_id = $_SESSION['user_id'];

$participant_model = new Participant();
$children = $participant_model->getChildrenByParentId($parent_id);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Parent Dashboard | P3KU</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/style.css"> 
    <style>
        a { text-decoration: none; color: #333; }

        .quick-actions-grid {
            display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 30px;
        }
        .action-card {
            background-color: #F4C542; color: #333; padding: 25px; border-radius: 12px;
            text-decoration: none; text-align: center; font-weight: bold; display: block;
            transition: transform 0.2s;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .action-card:hover { transform: translateY(-5px); }
        
        .child-item {
            background: white; padding: 20px; margin-bottom: 15px; border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1); border-left: 5px solid #ccc;
            display: flex; justify-content: space-between; align-items: center;
        }
        .status-active { border-left-color: #2F8F2F; }
        .status-pending { border-left-color: #cc3333; }
        
        .pin-display { 
            background: #E0F7FA; padding: 5px 10px; border-radius: 4px; 
            font-family: monospace; font-size: 1.1rem; color: #006064; 
            border: 1px solid #B2EBF2;
        }

        .btn-action {
            padding: 8px 15px; text-decoration: none; border-radius: 6px; 
            font-size: 0.9rem; display:inline-block; margin-top: 10px; 
            transition: background 0.2s; margin-left: 5px;
        }
        .btn-report { background-color: #455A64; color: white; }
        .btn-report:hover { background-color: #37474F; }
        
        .btn-profile { background-color: #607D8B; color: white; }
        .btn-profile:hover { background-color: #546E7A; }
    </style>
</head>
<body>
    <header>
        <h1>Parent Dashboard</h1>
        <nav>
            <a href="dashboard.php" style="font-weight:bold; font-size:24px">Dashboard</a> |
            <a href="<?php echo BASE_URL; ?>controllers/authController.php?logout=1" style="color: red;">Logout</a>
        </nav>
    </header>

    <main>
        <h2>👋 Welcome, <?php echo htmlspecialchars($parent_name); ?>!</h2>

        <h3>Quick Actions</h3>
        <div class="quick-actions-grid">
            <a href="register_child.php" class="action-card">
                <span style="font-size: 2rem; display: block;">➕</span> 
                Register New Child
            </a>
        </div>
        
        <hr>

        <h3>Your Children (<?php echo count($children); ?> Registered)</h3>
        
        <?php if (empty($children)): ?>
            <div style="background: #fff3cd; padding: 20px; border-radius: 8px; border: 1px solid #ffeeba; color: #856404;">
                <p><strong>No children found.</strong></p>
                <p>Click "Register New Child" above to get started.</p>
            </div>
        <?php else: ?>
            <div class="child-list">
                <?php foreach ($children as $child): 
                    $isActive = $child['is_active'] == 1;
                    $class = $isActive ? 'status-active' : 'status-pending';
                ?>
                <div class="child-item <?php echo $class; ?>">
                    <div>
                        <h3 style="margin: 0 0 5px 0;"><?php echo htmlspecialchars($child['name']); ?></h3>
                        <p style="margin: 0; color: #666;">Skill Level: <strong><?php echo htmlspecialchars($child['skill_level']); ?></strong></p>
                        <p style="margin: 5px 0 0 0;">
                            Status: <?php echo $isActive ? '<span style="color:green; font-weight:bold;">Active</span>' : '<span style="color:red; font-weight:bold;">Pending Review</span>'; ?>
                        </p>
                    </div>
                    
                    <div style="text-align: right;">
                        <small style="display:block; margin-bottom:5px;">Child Login PIN:</small>
                        <span class="pin-display"><?php echo htmlspecialchars($child['pin'] ?? '****'); ?></span>
                        <br>
                        
                        <a href="childProfile.php?child_id=<?php echo $child['participant_id']; ?>" class="btn-action btn-profile">
                           👤 Profile
                        </a>

                        <a href="childReport.php?child_id=<?php echo $child['participant_id']; ?>" class="btn-action btn-report">
                           📄 Report
                        </a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
    </main>
</body>
</html>