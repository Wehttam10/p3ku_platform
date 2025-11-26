<?php
/**
 * Task Controller
 * Handles all business logic and request processing for Task creation and management.
 */

ini_set('display_errors', 1);
error_reporting(E_ALL);

if (!defined('ROOT_PATH')) define('ROOT_PATH', dirname(__DIR__) . '/');
if (!defined('BASE_URL')) define('BASE_URL', '/p3ku-main/'); 

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once(ROOT_PATH . 'models/task.php');
require_once(ROOT_PATH . 'config/auth.php');

class TaskController {

    /**
     * 1. Create Task
     */
    public static function handleCreateTask($post_data, $file_data) { 
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASE_URL . 'admin/createTask.php'); 
            exit();
        }

        $admin_id = $_SESSION['user_id'] ?? 1;
        $steps = $post_data['steps'] ?? []; 
        $files = $file_data['step_image_files'] ?? []; 
        
        $name = trim($post_data['task_name'] ?? '');
        $description = trim($post_data['task_description'] ?? '');
        $required_skill = trim($post_data['required_skill'] ?? '');

        if (empty($name) || empty($required_skill)) {
            $_SESSION['error_message'] = "Task name and required skill level are mandatory.";
            header('Location: ' . BASE_URL . 'admin/createTask.php'); 
            exit();
        }
        
        $cleaned_steps = [];
        $step_number = 1;
        $upload_error = "";
        
        foreach ($steps as $key => $step) {
            if (!empty(trim($step['instruction_text'] ?? ''))) {
                $image_path = '';
                $file_index = $key - 1; 

                if (isset($files['tmp_name'][$file_index]) && $files['error'][$file_index] === UPLOAD_ERR_OK) {
                    $single_file = [
                        'name' => $files['name'][$file_index], 
                        'type' => $files['type'][$file_index],
                        'tmp_name' => $files['tmp_name'][$file_index], 
                        'error' => $files['error'][$file_index],
                        'size' => $files['size'][$file_index],
                    ];
                    
                    $uploaded_path = self::handleStepImageUpload($single_file, $upload_error); 

                    if ($uploaded_path) {
                        $image_path = $uploaded_path;
                    } else {
                        $_SESSION['error_message'] = "Image upload failed for step {$step_number}: " . $upload_error;
                        header('Location: ' . BASE_URL . 'admin/createTask.php'); 
                        exit();
                    }
                }
                
                $cleaned_steps[] = [
                    'step_number' => $step_number++,
                    'image_path' => $image_path, 
                    'instruction_text' => $step['instruction_text'],
                ];
            }
        }
        
        if (empty($cleaned_steps)) {
             $_SESSION['error_message'] = "A task must have at least one instruction step.";
             header('Location: ' . BASE_URL . 'admin/createTask.php'); 
             exit();
        }

        $task_model = new Task();
        $conn = $task_model->__get('conn'); 

        try {
            $conn->beginTransaction(); 
            $task_id = $task_model->createTask($admin_id, $name, $description, $required_skill);
            
            if ($task_id) {
                $task_model->createTaskSteps($task_id, $cleaned_steps);
                $conn->commit();
                $_SESSION['success_message'] = "Task created successfully!";
                header('Location: ' . BASE_URL . 'admin/tasks.php'); 
                exit();
            } else {
                throw new Exception("Failed to create task record.");
            }
        } catch (Exception $e) {
            if ($conn->inTransaction()) $conn->rollBack();
            $_SESSION['error_message'] = "System Error: " . $e->getMessage();
            header('Location: ' . BASE_URL . 'admin/createTask.php'); 
            exit();
        }
    }

    /**
     * 2. Assign Task
     */
    public static function handleAssignment($post_data) {
        if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
            header('Location: ' . BASE_URL . 'index.php');
            exit();
        }

        $admin_id = $_SESSION['user_id'];
        $task_id = filter_var($post_data['task_id'] ?? null, FILTER_VALIDATE_INT);
        $participant_ids = $post_data['participant_ids'] ?? [];

        if (!$task_id) {
            $_SESSION['error_message'] = "System Error: Task ID missing.";
            header('Location: ' . BASE_URL . 'admin/tasks.php'); 
            exit();
        }

        if (empty($participant_ids)) {
            $_SESSION['error_message'] = "Please select at least one participant.";
            header('Location: ' . BASE_URL . 'admin/assignTask.php?task_id=' . $task_id); 
            exit();
        }
        
        $task_model = new Task();
        $result = $task_model->createAssignments($task_id, $admin_id, $participant_ids);

        if ($result['success']) {
            $_SESSION['success_message'] = "Success! Assigned to " . $result['assigned_count'] . " participant(s).";
            header('Location: ' . BASE_URL . 'admin/tasks.php'); 
        } else {
            $_SESSION['error_message'] = "Database Error: Could not assign task.";
            header('Location: ' . BASE_URL . 'admin/assignTask.php?task_id=' . $task_id);
        }
        exit();
    }

    /**
     * 3. Update Task
     */
    public static function handleUpdateTask($post_data, $file_data) {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
            header('Location: ' . BASE_URL . 'index.php');
            exit();
        }

        $task_id = $post_data['task_id'] ?? null;
        $name = trim($post_data['task_name'] ?? '');
        $description = trim($post_data['task_description'] ?? '');
        $required_skill = trim($post_data['required_skill'] ?? '');
        $steps = $post_data['steps'] ?? [];
        $files = $file_data['step_image_files'] ?? [];

        if (!$task_id || empty($name)) {
            $_SESSION['error_message'] = "Missing required fields.";
            header('Location: ' . BASE_URL . 'admin/editTask.php?id=' . $task_id);
            exit();
        }

        $cleaned_steps = [];
        $step_number = 1;
        $upload_error = "";

        foreach ($steps as $key => $step) {
            if (!empty(trim($step['instruction_text'] ?? ''))) {
                $image_path = $step['existing_image_path'] ?? ''; 
                $file_index = $step_number - 1; 

                if (isset($files['tmp_name'][$file_index]) && $files['error'][$file_index] === UPLOAD_ERR_OK) {
                    $single_file = [
                        'name' => $files['name'][$file_index], 
                        'type' => $files['type'][$file_index],
                        'tmp_name' => $files['tmp_name'][$file_index], 
                        'error' => $files['error'][$file_index],
                        'size' => $files['size'][$file_index],
                    ];
                    
                    $uploaded_path = self::handleStepImageUpload($single_file, $upload_error); 
                    if ($uploaded_path) {
                        $image_path = $uploaded_path;
                    }
                }

                $cleaned_steps[] = [
                    'step_number' => $step_number++,
                    'image_path' => $image_path, 
                    'instruction_text' => $step['instruction_text'],
                ];
            }
        }

        $task_model = new Task();
        $conn = $task_model->__get('conn');

        try {
            $conn->beginTransaction();
            $task_model->updateTask($task_id, $name, $description, $required_skill);
            $task_model->deleteAllSteps($task_id);
            $task_model->createTaskSteps($task_id, $cleaned_steps);
            $conn->commit();

            $_SESSION['success_message'] = "Task updated successfully!";
            header('Location: ' . BASE_URL . 'admin/tasks.php');

        } catch (Exception $e) {
            $conn->rollBack();
            $_SESSION['error_message'] = "Update failed: " . $e->getMessage();
            header('Location: ' . BASE_URL . 'admin/editTask.php?id=' . $task_id);
        }
        exit();
    }

    /**
     * 4. Submit Feedback
     */
    public static function handleSubmitEvaluation($post_data) {
        if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'participant') {
             header('Location: ' . BASE_URL . 'participant/pinLogin.php'); 
             exit();
        }

        $assignment_id = filter_var($post_data['assignment_id'] ?? null, FILTER_VALIDATE_INT);
        $emoji_key = trim($post_data['emoji_sentiment'] ?? '');
        $participant_id = $_SESSION['user_id'] ?? null;

        if (!$assignment_id || empty($emoji_key)) {
             header('Location: ' . BASE_URL . 'participant/index.php'); 
             exit();
        }
        
        $task_model = new Task();
        $success = $task_model->submitSelfEvaluation($assignment_id, $participant_id, $emoji_key);

        if ($success) {
            header('Location: ' . BASE_URL . 'participant/index.php'); 
        } else {
             // Generic error message if DB fails
             $_SESSION['error_message'] = "Error saving evaluation.";
             header('Location: ' . BASE_URL . 'participant/doTask.php?assignment_id=' . $assignment_id); 
        }
        exit();
    }

    /**
     * 5. Delete Task (From Library)
     */
    public static function handleDeleteTask($get_data) {
        if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
            header('Location: ' . BASE_URL . 'index.php');
            exit();
        }

        $task_id = filter_var($get_data['id'] ?? null, FILTER_VALIDATE_INT);

        if ($task_id) {
            $task_model = new Task();
            if ($task_model->deleteTask($task_id)) {
                $_SESSION['success_message'] = "Task deleted successfully.";
            } else {
                $_SESSION['error_message'] = "Failed to delete task.";
            }
        } else {
            $_SESSION['error_message'] = "Invalid Task ID.";
        }
        
        header('Location: ' . BASE_URL . 'admin/tasks.php');
        exit();
    }

    /**
     * 6. Delete Assignment (From Active List)
     */
    public static function handleDeleteAssignment($get_data) {
        if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
            header('Location: ' . BASE_URL . 'index.php');
            exit();
        }

        $assignment_id = filter_var($get_data['id'] ?? null, FILTER_VALIDATE_INT);

        if ($assignment_id) {
            $task_model = new Task();
            if ($task_model->deleteAssignment($assignment_id)) {
                $_SESSION['success_message'] = "Assignment removed successfully.";
            } else {
                $_SESSION['error_message'] = "Failed to remove assignment.";
            }
        } else {
            $_SESSION['error_message'] = "Invalid Assignment ID.";
        }

        // ✅ FIXED: Redirect to manageTask.php (not manage_assignments.php)
        header('Location: ' . BASE_URL . 'admin/manageTask.php');
        exit();
    }

    /**
     * ✅ HELPER: Image Upload Logic
     */
    private static function handleStepImageUpload($file_array, &$error_msg = "") {
        if ($file_array['error'] !== UPLOAD_ERR_OK) {
            $error_msg = "PHP Upload Error Code: " . $file_array['error'];
            return false;
        }

        $allowed_types = ['image/jpeg', 'image/png', 'image/webp'];
        $max_size = 5 * 1024 * 1024; 

        if ($file_array['size'] > $max_size) {
            $error_msg = "File is too big (Max 5MB).";
            return false;
        }

        if (!class_exists('finfo')) {
            $file_type = $file_array['type'];
        } else {
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $file_type = $finfo->file($file_array['tmp_name']);
        }

        if (!in_array($file_type, $allowed_types)) {
            $error_msg = "Invalid file type: " . $file_type;
            return false;
        }

        $target_dir = ROOT_PATH . 'uploads/tasks/';
        if (!is_dir($target_dir)) {
             if (!mkdir($target_dir, 0777, true)) {
                 $error_msg = "Failed to create folder.";
                 return false;
             }
        }
        
        $extension = pathinfo($file_array['name'], PATHINFO_EXTENSION);
        $safe_filename = uniqid('task_') . time() . '.' . $extension;
        $target_path = $target_dir . $safe_filename; 

        if (move_uploaded_file($file_array['tmp_name'], $target_path)) {
            return '/uploads/tasks/' . $safe_filename;
        } else {
            $error_msg = "Failed to move uploaded file.";
            return false;
        }
    }
}
?>