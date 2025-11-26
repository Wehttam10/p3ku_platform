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
     * @param array 
     * @param array
     */
    public static function handleCreateTask($post_data, $file_data) { 
        
        // --- 1. Security Check ---
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASE_URL . 'admin/createTask.php'); 
            exit();
        }

        $admin_id = $_SESSION['user_id'] ?? 1;
        $steps = $post_data['steps'] ?? []; 
        $files = $file_data['step_image_files'] ?? []; 
        
        // --- 2. Data Acquisition ---
        $name = trim($post_data['task_name'] ?? '');
        $description = trim($post_data['task_description'] ?? '');
        $required_skill = trim($post_data['required_skill'] ?? '');

        if (empty($name) || empty($required_skill)) {
            $_SESSION['error_message'] = "Task name and required skill level are mandatory.";
            header('Location: ' . BASE_URL . 'admin/createTask.php'); 
            exit();
        }
        
        // --- 3. Process Steps & Images ---
        $cleaned_steps = [];
        $step_number = 1;
        
        foreach ($steps as $key => $step) {
            if (!empty(trim($step['instruction_text'] ?? ''))) {
                
                $image_path = $step['image_path'] ?? ''; 
                
                $file_index = $key - 1; 

                if (isset($files['tmp_name'][$file_index]) && $files['error'][$file_index] === UPLOAD_ERR_OK) {
                    
                    $single_file_data = [
                        'name' => $files['name'][$file_index], 
                        'type' => $files['type'][$file_index],
                        'tmp_name' => $files['tmp_name'][$file_index], 
                        'error' => $files['error'][$file_index],
                        'size' => $files['size'][$file_index],
                    ];
                    
                    $uploaded_path = handleStepImageUpload($single_file_data); 

                    if ($uploaded_path) {
                        $image_path = $uploaded_path;
                    } else {
                        $_SESSION['error_message'] = "Image upload failed for step {$step_number}.";
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


        // --- 4. Save to Database ---
        $task_model = new Task();
        $conn = $task_model->__get('conn'); 

        try {
            $conn->beginTransaction(); 
            
            $task_id = $task_model->createTask($admin_id, $name, $description, $required_skill);
            
            if (!$task_id) {
                throw new Exception("Failed to create main task record.");
            }

            $steps_success = $task_model->createTaskSteps($task_id, $cleaned_steps);

            if (!$steps_success) {
                throw new Exception("Failed to save task steps.");
            }

            $conn->commit();

            $_SESSION['success_message'] = "Task created successfully!";
            
            header('Location: ' . BASE_URL . 'admin/createTask.php'); 
            exit();

        } catch (Exception $e) {
            if ($conn->inTransaction()) {
                $conn->rollBack();
            }
            error_log("Task creation failed: " . $e->getMessage());
            $_SESSION['error_message'] = "System Error: " . $e->getMessage();
            header('Location: ' . BASE_URL . 'admin/createTask.php'); 
            exit();
        }
    }

    public static function handleAssignment($post_data) {
        
        // 1. Security
        if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
            header('Location: ' . BASE_URL . 'index.php');
            exit();
        }

        // 2. Get Data
        $admin_id = $_SESSION['user_id'];
        $task_id = filter_var($post_data['task_id'] ?? null, FILTER_VALIDATE_INT);
        $participant_ids = $post_data['participant_ids'] ?? [];

        // 3. Validation
        if (!$task_id) {
            $_SESSION['error_message'] = "System Error: Task ID missing.";
            header('Location: ' . BASE_URL . 'admin/tasks.php'); 
            exit();
        }

        if (empty($participant_ids)) {
            $_SESSION['error_message'] = "Please select at least one participant.";
            // Redirect back to the assignment page (ensure file is named assignTask.php)
            header('Location: ' . BASE_URL . 'admin/assignTask.php?task_id=' . $task_id); 
            exit();
        }
        
        // 4. Model Interaction
        $task_model = new Task();
        $result = $task_model->createAssignments($task_id, $admin_id, $participant_ids);

        // 5. Handle Success/Failure
        if ($result['success']) {
            $count = $result['assigned_count'];
            $_SESSION['success_message'] = "Success! Assigned to $count participant(s).";

            header('Location: ' . BASE_URL . 'admin/tasks.php'); 
        } else {
            $_SESSION['error_message'] = "Database Error: Could not assign task.";
            header('Location: ' . BASE_URL . 'admin/assignTask.php?task_id=' . $task_id);
        }
        exit();
    }

    public static function checkAndSetProgress($assignment_id, $current_status) {
        if ($current_status === 'Pending') {
            $task_model = new Task();
            return $task_model->updateAssignmentStatus($assignment_id, 'In Progress');
        }
        return true;
    }

    public static function handleSubmitEvaluation($post_data) {
        
        // 1. Security Check: Ensure user is a participant
        if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'participant') {
             header('Location: ' . BASE_URL . 'participant/pinLogin.php'); 
             exit();
        }

        // 2. Get Data
        $assignment_id = filter_var($post_data['assignment_id'] ?? null, FILTER_VALIDATE_INT);
        $emoji_key = trim($post_data['emoji_sentiment'] ?? '');
        $participant_id = $_SESSION['user_id'] ?? null;

        // 3. Validation
        if (!$assignment_id || empty($emoji_key) || !$participant_id) {
             header('Location: ' . BASE_URL . 'participant/index.php'); 
             exit();
        }
        
        // 4. Model Interaction
        $task_model = new Task();
        $success = $task_model->submitSelfEvaluation($assignment_id, $participant_id, $emoji_key);

        // 5. Redirect
        if ($success) {
            header('Location: ' . BASE_URL . 'participant/index.php'); 
        } else {
             header('Location: ' . BASE_URL . 'participant/doTask.php?assignment_id=' . $assignment_id); 
        }
        exit();
    }

    function handleStepImageUpload($file_array) {
    // --- 1. Validation Checks ---
    if ($file_array['error'] !== UPLOAD_ERR_OK) {
        error_log("File upload failed with error code: " . $file_array['error']);
        return false;
    }

    $allowed_types = ['image/jpeg', 'image/png', 'image/webp'];
    $max_size = 5 * 1024 * 1024;

    if (!class_exists('finfo')) {
        $file_type = $file_array['type'];
    } else {
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $file_type = $finfo->file($file_array['tmp_name']);
    }

    if (!in_array($file_type, $allowed_types)) {
        error_log("File type not allowed: " . $file_type);
        return false;
    }

    if ($file_array['size'] > $max_size) {
        error_log("File size exceeds limit: " . $file_array['size']);
        return false;
    }

    // --- 2. Create Unique and Safe Filename ---
    $extension = '.jpg';
    if (function_exists('exif_imagetype')) {
        $extension = image_type_to_extension(exif_imagetype($file_array['tmp_name']));
    }
    
    $safe_filename = uniqid('task_') . time() . $extension;

    $target_dir_relative = 'uploads/tasks/';

    if (!is_dir(ROOT_PATH . $target_dir_relative)) {
         mkdir(ROOT_PATH . $target_dir_relative, 0755, true);
    }
    
    $target_path = ROOT_PATH . $target_dir_relative . $safe_filename; 

    // --- 3. Move File ---
    if (move_uploaded_file($file_array['tmp_name'], $target_path)) {
        return '/' . $target_dir_relative . $safe_filename;
    }

    return false;
    }

    public static function handleUpdateTask($post_data, $file_data) {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !is_admin()) {
            check_access(ROLE_ADMIN, ROOT_PATH);
            exit();
        }

        $task_id = $post_data['task_id'] ?? null;
        $name = trim($post_data['task_name'] ?? '');
        $description = trim($post_data['task_description'] ?? '');
        $required_skill = trim($post_data['required_skill'] ?? '');
        $steps = $post_data['steps'] ?? [];
        $files = $file_data['step_image_files'] ?? [];

        if (!$task_id || empty($name) || empty($required_skill)) {
            $_SESSION['error_message'] = "Missing required fields.";
            header('Location: ' . BASE_URL . 'admin/editTask.php?id=' . $task_id);
            exit();
        }

        $cleaned_steps = [];
        $step_number = 1;

        foreach ($steps as $key => $step) {
            if (!empty(trim($step['instruction_text'] ?? ''))) {

                $image_path = $step['existing_image_path'] ?? ''; 

                $file_index = $key - 1;

                if (isset($files['tmp_name'][$file_index]) && $files['error'][$file_index] === UPLOAD_ERR_OK) {
                    $single_file_data = [
                        'name' => $files['name'][$file_index], 'type' => $files['type'][$file_index],
                        'tmp_name' => $files['tmp_name'][$file_index], 'error' => $files['error'][$file_index],
                        'size' => $files['size'][$file_index],
                    ];
                    
                    $uploaded_path = handleStepImageUpload($single_file_data); 
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

            // 1. Update Main Info
            $task_model->updateTask($task_id, $name, $description, $required_skill);

            // 2. Delete OLD steps (The Clean Slate Strategy)
            $task_model->deleteAllSteps($task_id);

            // 3. Insert NEW/UPDATED steps
            if (!empty($cleaned_steps)) {
                $task_model->createTaskSteps($task_id, $cleaned_steps);
            }

            $conn->commit();
            $_SESSION['success_message'] = "Task updated successfully!";
            header('Location: ' . BASE_URL . 'admin/tasks.php');

        } catch (Exception $e) {
            $conn->rollBack();
            error_log("Update Error: " . $e->getMessage());
            $_SESSION['error_message'] = "Failed to update task.";
            header('Location: ' . BASE_URL . 'admin/editTask.php?id=' . $task_id);
        }
        exit();
    }

    public static function handleDeleteTask($get_data) {
        // 1. Security Check
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
}