<?php
/**
 * Admin Participant Listing Page
 */

// --- 1. CONFIGURATION (Prevents Blank Page) ---
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (!defined('ROOT_PATH')) define('ROOT_PATH', dirname(__DIR__) . '/');
if (!defined('BASE_URL')) define('BASE_URL', '/p3ku-main/'); 

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// --- 2. SECURITY CHECK ---
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: " . BASE_URL . "index.php");
    exit();
}

// --- 3. INCLUDES ---
if (!file_exists(ROOT_PATH . 'models/participant.php')) {
    die("Error: Missing file models/participant.php");
}
require_once(ROOT_PATH . 'models/participant.php');

// --- 4. DATA RETRIEVAL ---
try {
    $participant_model = new Participant();
    $participants = $participant_model->getAllParticipants();
    $num_participants = count($participants);
} catch (Exception $e) {
    die("Database Error: " . $e->getMessage());
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin | Participant Management</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/style.css"> 
    <style>
        a { text-decoration: none; color: #333; }
        .table-container { overflow-x: auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .data-table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        .data-table th, .data-table td { 
            border: 1px solid #ddd; 
            padding: 12px; 
            text-align: left; 
        }
        .data-table th { background-color: #455A64; color: white; } 
        .status-pending { background-color: #F4C542; color: #333; font-weight: bold; padding: 4px 8px; border-radius: 4px; } 
        .btn-action { 
            background-color: #2F8F2F; 
            color: white; 
            padding: 8px 12px; 
            border: none; 
            border-radius: 8px; 
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            font-size: 0.9rem;
        }
        .btn-action:hover { background-color: #257a25; }
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
        <h2>🧑‍💻 Participant Management</h2>
        
        <p class="breadcrumbs">Admin > Participants</p>

        <p>Total Registered Participants: <strong><?php echo $num_participants; ?></strong></p>

        <div class="table-container">
            <?php if ($num_participants > 0): ?>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Parent Name</th>
                            <th>Current Skill Level</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($participants as $p): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($p['participant_id']); ?></td>
                            <td><?php echo htmlspecialchars($p['name']); ?></td>
                            <td><?php echo htmlspecialchars($p['parent_name'] ?? 'N/A'); ?></td>
                            <td>
                                <?php if ($p['skill_level'] === 'Pending'): ?>
                                    <span class="status-pending">Pending Review</span>
                                <?php else: ?>
                                    <?php echo htmlspecialchars($p['skill_level']); ?>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php echo ($p['is_active'] == 1) ? '<span style="color:green;font-weight:bold">Active</span>' : '<span style="color:red">Inactive</span>'; ?>
                            </td>
                            <td>
                                <a href="view_profile.php?id=<?php echo $p['participant_id']; ?>" 
                                   class="btn-action">
                                   Review Profile
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>No participants have been registered yet.</p>
            <?php endif; ?>
        </div>
    </main>

    <footer>
    </footer>
</body>
</html>