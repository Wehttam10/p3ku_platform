<?php
/**
 * Admin Task Creation Page
 */

// --- 1. ENABLE ERROR REPORTING ---
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// --- 2. DEFINE PATHS ---
define('ROOT_PATH', dirname(__DIR__) . '/');

define('BASE_URL', '/p3ku-main/'); 

// --- 3. START SESSION ---
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// --- 4. INCLUDE CONFIGURATIONS ---
require_once(ROOT_PATH . 'config/db.php'); 

// --- 5. INCLUDE CONTROLLER ---
require_once(ROOT_PATH . 'controllers/taskController.php');

// --- 6. HANDLE FORM SUBMISSION ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    TaskController::handleCreateTask($_POST, $_FILES);
}

// --- 7. SETUP MESSAGES ---
$success_message = $_SESSION['success_message'] ?? null;
$error_message = $_SESSION['error_message'] ?? null;
unset($_SESSION['success_message'], $_SESSION['error_message']);

// --- 8. SKILL LEVELS ---
$skill_levels = [
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
    <title>Admin | Create New Task</title>
    <link rel="stylesheet" href="../assets/css/style.css"> 
    <style>
        .form-group { margin-bottom: 24px; }
        label { font-weight: bold; display: block; margin-bottom: 8px; }
        a { text-decoration: none; color: #333; }
        .form-control, textarea, select { 
            width: 100%; 
            padding: 12px; 
            border: 1px solid #ccc; 
            border-radius: 8px; 
            font-size: 1rem; 
            box-sizing: border-box;
        }
        .step-container { 
            border: 1px solid #F4C542;
            padding: 15px; 
            margin-bottom: 15px; 
            border-radius: 8px; 
            background-color: #fffff0;
        }
        .step-container h4 { margin-top: 0; display: inline-block; }
        .remove-step { float: right; background-color: #cc3333; color: white; border: none; padding: 5px 10px; border-radius: 4px; cursor: pointer; }
        .btn-add-step { 
            background-color: #455A64;
            color: white; 
            padding: 10px 15px; 
            border: none; 
            border-radius: 8px; 
            cursor: pointer; 
            margin-bottom: 20px;
        }
        .btn-primary { 
            background-color: #2F8F2F;
            color: white; 
            padding: 15px 25px; 
            border: none; 
            border-radius: 12px; 
            cursor: pointer; 
            font-size: 1.2rem;
        }
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
        <h2>➕ Create New Task</h2>
        
        <p class="breadcrumbs">Admin > Tasks > Create Task</p>

        <?php if ($success_message): ?>
            <div class="alert-success" role="alert"><?php echo htmlspecialchars($success_message); ?></div>
        <?php endif; ?>
        <?php if ($error_message): ?>
            <div class="alert-error" role="alert"><?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>

        <form action="./createTask.php" method="POST" id="createTaskForm" enctype="multipart/form-data">
            
            <h3>Main Task Information</h3>
            <div class="form-group">
                <label for="task_name">Task Name (e.g., Planting Basil Seedlings)</label>
                <input type="text" id="task_name" name="task_name" class="form-control" required>
            </div>
            
            <div class="form-group">
                <label for="task_description">Task Summary/Goal</label>
                <textarea id="task_description" name="task_description" rows="3" class="form-control" placeholder="Briefly explain the goal and expected outcome."></textarea>
            </div>
            
            <div class="form-group">
                <label for="required_skill">Required Minimum Skill Level</label>
                <select name="required_skill" id="required_skill" class="form-control" required>
                    <option value="">-- Select Skill Level --</option>
                    <?php foreach ($skill_levels as $value => $label): ?>
                        <option value="<?php echo htmlspecialchars($value); ?>">
                            <?php echo htmlspecialchars($label); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <small>Only participants at or above this skill level will be assigned this task.</small>
            </div>
            
            <hr>

            <h3>Step-by-Step Instructions (Visual Guidance)</h3>
            <p>Define each short step. For accessibility, each step should have minimal text and clear imagery (image input placeholder).</p>
            
            <button type="button" class="btn-add-step" id="addStepButton">
                ➕ Add New Instruction Step
            </button>
            
            <div id="stepsContainer">
                </div>

            <hr>

            <button type="submit" class="btn-primary">
                💾 Save Task and Instructions
            </button>
        </form>
    </main>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const stepsContainer = document.getElementById('stepsContainer');
            const addStepButton = document.getElementById('addStepButton');
            let stepCounter = 0; // Initialize to 0

            /**
             * @param {number} count 
             * @returns {string}
             */
            function createStepMarkup(count) {
                return `
                    <div class="step-container" data-step="${count}">
                        <h4>Step ${count}</h4>
                        <button type="button" class="remove-step" onclick="this.closest('.step-container').remove(); window.updateStepNumbers();">X Remove</button>
                        
                        <div class="form-group">
                            <label for="step_text_${count}">Instruction Text (e.g., Fill the cup with soil)</label>
                            <input type="text" 
                                   id="step_text_${count}" 
                                   name="steps[${count}][instruction_text]" 
                                   class="form-control" 
                                   placeholder="Short instruction for this step"
                                   required>
                        </div>
                        
                        <div class="form-group">
                            <label for="image_file_${count}">Visual Asset (Image Upload)</label>
                            <input type="file" 
                                id="image_file_${count}" 
                                name="step_image_files[]" 
                                class="form-control"
                                accept="image/png, image/jpeg, image/webp">
                            <small>Accepts JPG, PNG, and WebP formats. This input is mandatory for visual tasks.</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="image_path_${count}">Visual Asset/Image Path (Reference for existing image, optional)</label>
                            <input type="text" 
                                   id="image_path_${count}" 
                                   name="steps[${count}][image_path]" 
                                   class="form-control" 
                                   placeholder="/uploads/task_images/step_image.webp (Will be ignored if file is uploaded)">
                        </div>
                    </div>
                `;
            }

            window.updateStepNumbers = function() {
                const steps = stepsContainer.querySelectorAll('.step-container');
                let currentStepIndex = 1;
                
                steps.forEach((stepDiv) => {
                    stepDiv.setAttribute('data-step', currentStepIndex);
                    
                    stepDiv.querySelector('h4').textContent = `Step ${currentStepIndex}`;

                    stepDiv.querySelectorAll('input, textarea, select').forEach(input => {
                        if (input.name.startsWith('steps[')) {
                             const name = input.name.replace(/steps\[\d+\]/, `steps[${currentStepIndex}]`);
                             input.name = name;
                        }
                    });

                    currentStepIndex++;
                });
                
                stepCounter = currentStepIndex; 
            };

            function addStep() {
                let nextStepIndex = stepsContainer.querySelectorAll('.step-container').length + 1;
                stepsContainer.insertAdjacentHTML('beforeend', createStepMarkup(nextStepIndex));
                updateStepNumbers();
            }

            addStepButton.addEventListener('click', addStep);

            addStep();
        });
    </script>
</body>
</html>