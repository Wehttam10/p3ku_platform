<?php
/**
 * Do Task Page - Production Version
 */
ini_set('display_errors', 1);
error_reporting(E_ALL);

define('ROOT_PATH', dirname(__DIR__) . '/');
define('BASE_URL', '/p3ku-main/'); 

session_set_cookie_params(0, '/');

if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'participant') {
    header("Location: pinLogin.php");
    exit();
}

require_once(ROOT_PATH . 'models/task.php');

$assignment_id = filter_input(INPUT_GET, 'assignment_id', FILTER_VALIDATE_INT);

if (!$assignment_id) {
    header("Location: index.php");
    exit();
}

$model = new Task();
$task_data = $model->getAssignmentDetails($assignment_id, $_SESSION['user_id']);

if (!$task_data) {
    die("Task not found or access denied.");
}

if ($task_data['status'] === 'Pending') {
    $model->markInProgress($assignment_id);
}

$steps = $task_data['steps'];
$steps_json = json_encode($steps);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Doing: <?php echo htmlspecialchars($task_data['name']); ?></title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/style.css">
    <style>
        body { background-color: #E0F7FA; font-family: 'Comic Sans MS', sans-serif; display: flex; flex-direction: column; height: 100vh; margin: 0; }
        header { background-color: #00BCD4; padding: 15px; text-align: center; color: white; position: relative;}
        .back-btn { position: absolute; left: 15px; top: 15px; color: black; text-decoration: none; font-weight: bold; font-size: 1.2rem; border: 2px solid black; padding: 5px 10px; border-radius: 10px; }
        .container { flex: 1; display: flex; flex-direction: column; justify-content: center; align-items: center; padding: 20px; }
        .step-card { background: white; border-radius: 25px; box-shadow: 0 10px 30px rgba(0,0,0,0.15); width: 100%; max-width: 600px; overflow: hidden; text-align: center; display: none; }
        .step-card.active { display: block; animation: popIn 0.3s ease-out; }
        @keyframes popIn { from { transform: scale(0.9); opacity: 0; } to { transform: scale(1); opacity: 1; } }
        .step-image { width: 100%; height: 300px; object-fit: cover; background-color: #eee; border-bottom: 5px solid #FFC107; }
        .step-content { padding: 30px; }
        .step-number { background: #FFC107; display: inline-block; padding: 5px 15px; border-radius: 20px; font-weight: bold; margin-bottom: 10px; }
        .instruction { font-size: 1.8rem; color: #333; margin: 10px 0 30px 0; }
        .controls { display: flex; justify-content: space-between; margin-top: 20px; gap: 20px; }
        .btn-nav { flex: 1; padding: 20px; border: none; border-radius: 15px; font-size: 1.5rem; font-weight: bold; cursor: pointer; }
        .btn-prev { background-color: #B0BEC5; color: #333; }
        .btn-next { background-color: #FF9800; color: white; box-shadow: 0 5px 0 #E65100; }
        .btn-finish { background-color: #4CAF50; color: white; width: 100%; padding: 20px; border: none; border-radius: 15px; font-size: 1.8rem; font-weight: bold; cursor: pointer; box-shadow: 0 5px 0 #2E7D32; display: none; }
    </style>
</head>
<body>
    <header>
        <a href="index.php" class="back-btn" style="margin-top:60px;">❌ Quit</a>
        <h2 style="margin:0;"><?php echo htmlspecialchars($task_data['name']); ?></h2>
    </header>

    <div class="container">
        <div id="steps-wrapper"></div>
        <div id="finish-screen" class="step-card">
            <div style="padding: 50px;">
                <div style="font-size: 5rem;">🎉</div>
                <h1>Good Job!</h1>
                <p>You finished all the steps.</p>
                <a href="feedback.php?assignment_id=<?php echo $assignment_id; ?>" class="btn-finish" style="display:inline-block; text-decoration:none;">I'm Done! ✅</a>
            </div>
        </div>
    </div>

    <script>
        const steps = <?php echo $steps_json; ?>;
        const wrapper = document.getElementById('steps-wrapper');
        const finishScreen = document.getElementById('finish-screen');
        let currentIndex = 0;

        function renderSteps() {
            let html = '';
            steps.forEach((step, index) => {
                let imgUrl = step.image_path ? '<?php echo BASE_URL; ?>' + step.image_path.replace(/^\//, '') : 'https://via.placeholder.com/600x400?text=No+Image';
                html += `
                    <div class="step-card ${index === 0 ? 'active' : ''}" id="step-${index}">
                        <img src="${imgUrl}" class="step-image">
                        <div class="step-content">
                            <div class="step-number">Step ${index + 1}</div>
                            <div class="instruction">${step.instruction_text}</div>
                            <div class="controls">
                                <button class="btn-nav btn-prev" onclick="prevStep()" ${index === 0 ? 'disabled style="opacity:0.5"' : ''}>⬅️ Back</button>
                                ${index === steps.length - 1 ? `<button class="btn-nav btn-next" style="background:#4CAF50" onclick="showFinish()">Finish 🏁</button>` : `<button class="btn-nav btn-next" onclick="nextStep()">Next ➡️</button>`}
                            </div>
                        </div>
                    </div>`;
            });
            wrapper.innerHTML = html;
        }

        function showStep(index) {
            document.querySelectorAll('.step-card').forEach(el => el.classList.remove('active'));
            document.getElementById(`step-${index}`).classList.add('active');
        }
        window.nextStep = () => { if(currentIndex < steps.length - 1) { currentIndex++; showStep(currentIndex); } };
        window.prevStep = () => { if(currentIndex > 0) { currentIndex--; showStep(currentIndex); } };
        window.showFinish = () => {
            document.querySelectorAll('.step-card').forEach(el => el.style.display = 'none');
            finishScreen.style.display = 'block';
            finishScreen.classList.add('active');
        }
        if(steps.length > 0) renderSteps(); else wrapper.innerHTML = '<div class="step-card active"><h3>No steps found!</h3></div>';
    </script>
</body>
</html>