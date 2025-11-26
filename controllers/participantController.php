<?php
/**
 * Participant Controller
 * Handles all business logic and request processing for Participant (child) actions.
 */

require_once(ROOT_PATH . 'models/Participant.php');
require_once(ROOT_PATH . 'config/auth.php'); 

class ParticipantController {

    public static function handleRegistration($post_data) {
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . ROOT_PATH . 'parent/register_child');
            exit();
        }
        
        $parent_id = $_SESSION['user_id'] ?? null;
        
        if (!is_parent() || !$parent_id) {
            check_access(ROLE_PARENT, ROOT_PATH); 
            exit(); 
        }

        $name = trim($post_data['child_name'] ?? '');
        $pin = trim($post_data['child_pin'] ?? '');
        $sensory_details = trim($post_data['sensory_details'] ?? '');

        if (empty($name) || empty($pin) || empty($sensory_details)) {
            $_SESSION['error_message'] = "All fields are required to register your child.";
            header('Location: ' . ROOT_PATH . 'parent/register_child');
            exit();
        }

        if (!preg_match('/^\d{4}$/', $pin)) {
            $_SESSION['error_message'] = "The child PIN must be exactly 4 digits.";
            header('Location: ' . ROOT_PATH . 'parent/register_child');
            exit();
        }

        $participant_model = new Participant();
        
        $success = $participant_model->createParticipant(
            $parent_id, 
            $name, 
            $pin, 
            $sensory_details
        );

        if ($success) {
            $_SESSION['success_message'] = $name . " has been successfully registered. The admin will now review the profile to set a skill level.";
            header('Location: ' . ROOT_PATH . 'parent/dashboard');
            exit();
        } else {
            $_SESSION['error_message'] = "Registration failed due to a system error. Please try again.";
            header('Location: ' . ROOT_PATH . 'parent/register_child');
            exit();
        }
    }

    public static function handleSkillUpdate($post_data) {
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !is_admin()) {
            check_access(ROLE_ADMIN, ROOT_PATH);
            exit();
        }

        $participant_id = filter_var($post_data['participant_id'] ?? null, FILTER_VALIDATE_INT);
        $skill_level = trim($post_data['skill_level'] ?? '');
        $is_active = isset($post_data['is_active']) ? 1 : 0; 

        if (!$participant_id || empty($skill_level)) {
            $_SESSION['error_message'] = "Missing participant ID or skill level.";
            header('Location: ' . ROOT_PATH . 'admin/participants'); 
            exit();
        }

        $participant_model = new Participant();
        
        $success = $participant_model->updateSkillLevel(
            $participant_id, 
            $skill_level, 
            $is_active

        if ($success) {
            $_SESSION['success_message'] = "Participant skill level and status updated successfully!";
            header('Location: ' . ROOT_PATH . 'admin/participants');
        } else {
            $_SESSION['error_message'] = "Update failed due to a database error.";
            header('Location: ' . ROOT_PATH . 'admin/view_profile?id=' . $participant_id); 
        }
        exit();
    }

    public static function handlePinLogin($post_data) {
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . ROOT_PATH . 'participant/pin_login');
            exit();
        }

        $pin = trim($post_data['pin'] ?? '');
        $ip_address = $_SERVER['REMOTE_ADDR']; 

        if (!preg_match('/^\d{4}$/', $pin)) {
            $_SESSION['participant_error'] = "Please enter a valid 4-digit PIN.";
            header('Location: ' . ROOT_PATH . 'participant/pin_login');
            exit();
        }

        $participant_model = new Participant();
        
        $temp_lookup = $participant_model->lookupParticipantByPin($pin); 
        $target_pid = $temp_lookup['participant_id'] ?? null;

        if ($participant_model->isRateLimited($target_pid, $ip_address)) {
            $_SESSION['participant_error'] = "Too many login attempts. Please wait 5 minutes before trying again.";
            header('Location: ' . ROOT_PATH . 'participant/pin_login');
            exit();

        $participant_data = $participant_model->verifyPinLogin($pin);

        if ($participant_data) {
            session_regenerate_id(true); 
            $_SESSION['user_id'] = $participant_data['participant_id'];
            $_SESSION['user_role'] = ROLE_PARTICIPANT;
            $_SESSION['participant_name'] = $participant_data['name'];
            
            header('Location: ' . ROOT_PATH . 'participant/my_tasks');
            exit();
        } else {
            $participant_model->logFailedAttempt($target_pid, $ip_address);
            
            $_SESSION['participant_error'] = "Login failed. Check your PIN or ensure your account is active.";
            header('Location: ' . ROOT_PATH . 'participant/pin_login');
            exit();
        }
    }
}