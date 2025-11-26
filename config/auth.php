<?php
/**
 * Authentication and Authorization Utility Functions
 */

define('ROLE_ADMIN', 'admin');
define('ROLE_PARENT', 'parent');
define('ROLE_PARTICIPANT', 'participant');
define('ROLE_GUEST', 'guest');


function is_logged_in() {
    return isset($_SESSION['user_id']);
}

function get_user_role() {
    if (is_logged_in() && isset($_SESSION['user_role'])) {
        $role = strtolower($_SESSION['user_role']);
        if (in_array($role, [ROLE_ADMIN, ROLE_PARENT, ROLE_PARTICIPANT])) {
            return $role;
        }
    }
    return ROLE_GUEST;
}

function check_access($required_roles, $redirect_path = URL_ROOT) {
    if ($redirect_path === '/p3ku-main/' || $redirect_path === '/p3ku-main/home') {
        $redirect_path = URL_ROOT;
    }

    if (!is_array($required_roles)) {
        $required_roles = [$required_roles];
    }

    $current_role = get_user_role();

    if (!in_array($current_role, $required_roles)) {
        header('Location: ' . $redirect_path);
        exit();
    }
}

function is_admin() {
    return get_user_role() === ROLE_ADMIN;
}

function is_parent() {
    return get_user_role() === ROLE_PARENT;
}

function is_participant() {
    return get_user_role() === ROLE_PARTICIPANT;
}

function get_csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32)); 
    }
    return $_SESSION['csrf_token'];
}

function validate_csrf_token($submitted_token) {
    return !empty($submitted_token) && 
           !empty($_SESSION['csrf_token']) && 
           hash_equals($_SESSION['csrf_token'], $submitted_token);
}