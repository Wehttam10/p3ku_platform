<?php
/**
 * Admin Edit Task Page
 */

// --- 1. CONFIGURATION ---
ini_set('display_errors', 1);
error_reporting(E_ALL);

define('ROOT_PATH', dirname(__DIR__) . '/');
define('BASE_URL', '/p3ku-main/'); 

if (session_status() === PHP_SESSION_NONE) session_start();

require_once(ROOT_PATH . 'models/task.php');
require_once(ROOT_PATH . 'controllers/taskController.php');

// --- 2. HANDLE SUBMISSION ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    TaskController::handleUpdateTask($_POST, $_FILES);
}

// --- 3. FETCH EXISTING DATA ---
$task_id = $_GET['id'] ?? null;

if (!$task_id) {
    header("Location: " . BASE_URL . "admin/tasks.php");
    exit();
}

$task_model = new Task();
$task = $task_model->getTaskWithSteps($task_id);

if (!$task) {
    $_SESSION['error_message'] = "Task not found.";
    header("Location: " . BASE_URL . "admin/tasks.php");
    exit();
}

$existing_steps = $task['steps'] ?? [];

$skill_levels = [
    'Level 1: Basic Visual (Red)' => 'Level 1: Basic Visual Tasks',
    'Level 2: Simple Steps (Yellow)' => 'Level 2: Simple Multi-Step Tasks',
    'Level 3: Guided Independence (Blue)' => 'Level 3: Guided Independent Tasks',
    'Level 4: Full Independence (Green)' => 'Level 4: Fully Independent Tasks'
];

$error_message = $_SESSION['error_message'] ?? null;
unset($_SESSION['error_message']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin | Edit Task</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/style.css"> 
    <style>
        a { text-decoration: none; color: #333; }
        .form-group { margin-bottom: 20px; }
        .form-control { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 5px; box-sizing: border-box; }
        .step-container { border: 1px solid #F4C542; padding: 15px; margin-bottom: 15px; background-color: #fffff0; border-radius: 8px; position: relative; }
        .remove-step { position: absolute; top: 10px; right: 10px; background: #cc3333; color: white; border: none; padding: 5px 10px; border-radius: 4px; cursor: pointer; }
        .btn-add { background: #455A64; color: white; padding: 10px 15px; border: none; border-radius: 5px; cursor: pointer; }
        .btn-save { background: #2F8F2F; color: white; padding: 15px 30px; border: none; border-radius: 8px; font-size: 1.1rem; cursor: pointer; }
        .current-img-preview { max-width: 100px; display: block; margin-top: 5px; border: 1px solid #ddd; padding: 3px; background: white; }
    </style>
</head>
<body>
    <header>
        <h1>Admin Dashboard</h1>
        <nav><a href="tasks.php">Back to Tasks</a></nav>
    </header>

    <main>
        <h2>✏️ Edit Task: <?php echo htmlspecialchars($task['name']); ?></h2>

        <?php if ($error_message): ?>
            <div class="alert-error"><?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>

        <form action="" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="task_id" value="<?php echo $task['task_id']; ?>">

            <h3>Main Information</h3>
            <div class="form-group">
                <label>Task Name</label>
                <input type="text" name="task_name" class="form-control" value="<?php echo htmlspecialchars($task['name']); ?>" required>
            </div>
            
            <div class="form-group">
                <label>Description</label>
                <textarea name="task_description" class="form-control" rows="3"><?php echo htmlspecialchars($task['description']); ?></textarea>
            </div>
            
            <div class="form-group">
                <label>Required Skill Level</label>
                <select name="required_skill" class="form-control" required>
                    <?php foreach ($skill_levels as $val => $label): ?>
                        <option value="<?php echo $val; ?>" <?php echo ($task['required_skill'] === $val) ? 'selected' : ''; ?>>
                            <?php echo $label; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <hr>
            <h3>Instructions</h3>
            <div id="stepsContainer">
                
                <?php 
                $counter = 0;
                foreach ($existing_steps as $step): 
                    $counter++;
                ?>
                <div class="step-container" data-step="<?php echo $counter; ?>">
                    <h4>Step <?php echo $counter; ?></h4>
                    <button type="button" class="remove-step" onclick="removeStep(this)">X Remove</button>
                    
                    <div class="form-group">
                        <label>Instruction Text</label>
                        <input type="text" 
                               name="steps[<?php echo $counter; ?>][instruction_text]" 
                               class="form-control" 
                               value="<?php echo htmlspecialchars($step['instruction_text']); ?>" 
                               required>
                    </div>

                    <div class="form-group">
                        <label>Visual Asset</label>
                        
                        <?php if (!empty($step['image_path'])): ?>
                            <div style="margin-bottom: 10px;">
                                <small>Current Image:</small><br>
                                <img src="<?php echo BASE_URL . ltrim($step['image_path'], '/'); ?>" class="current-img-preview" alt="Step Image">
                                <input type="hidden" name="steps[<?php echo $counter; ?>][existing_image_path]" value="<?php echo $step['image_path']; ?>">
                            </div>
                        <?php endif; ?>

                        <label><small>Upload New (Replaces current):</small></label>
                        <input type="file" 
                               name="step_image_files[]" 
                               class="form-control"
                               accept="image/*">
                    </div>
                </div>
                <?php endforeach; ?>
                
            </div>

            <button type="button" class="btn-add" id="addStepButton">➕ Add Step</button>
            <hr>
            <button type="submit" class="btn-save">💾 Update Task</button>
        </form>
    </main>

    <script>
        const stepsContainer = document.getElementById('stepsContainer');
        const addStepButton = document.getElementById('addStepButton');
        
        let stepCounter = <?php echo count($existing_steps); ?>;

        function updateStepNumbers() {
            const steps = stepsContainer.querySelectorAll('.step-container');
            let currentStepIndex = 1;
            
            steps.forEach((stepDiv) => {
                stepDiv.setAttribute('data-step', currentStepIndex);
                stepDiv.querySelector('h4').textContent = `Step ${currentStepIndex}`;

                stepDiv.querySelectorAll('input, textarea').forEach(input => {
                    if (input.name.includes('steps[')) {
                        const newName = input.name.replace(/steps\[\d+\]/, `steps[${currentStepIndex}]`);
                        input.name = newName;
                    }
                });
                currentStepIndex++;
            });
            stepCounter = currentStepIndex - 1;
        }

        window.removeStep = function(btn) {
            if(confirm('Delete this step?')) {
                btn.closest('.step-container').remove();
                updateStepNumbers();
            }
        };

        addStepButton.addEventListener('click', function() {
            stepCounter++;
            const html = `
                <div class="step-container" data-step="${stepCounter}">
                    <h4>Step ${stepCounter}</h4>
                    <button type="button" class="remove-step" onclick="removeStep(this)">X Remove</button>
                    
                    <div class="form-group">
                        <label>Instruction Text</label>
                        <input type="text" name="steps[${stepCounter}][instruction_text]" class="form-control" placeholder="New instruction..." required>
                    </div>
                    
                    <div class="form-group">
                        <label>Visual Asset</label>
                        <input type="file" name="step_image_files[]" class="form-control" accept="image/*">
                        <input type="hidden" name="steps[${stepCounter}][existing_image_path]" value="">
                    </div>
                </div>
            `;
            stepsContainer.insertAdjacentHTML('beforeend', html);
            updateStepNumbers();
        });
    </script>
</body>
</html>