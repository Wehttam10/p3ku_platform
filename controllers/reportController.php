<?php
/**
 * Report Controller
 * Handles business logic for generating and serving filtered reports.
 */

require_once(ROOT_PATH . 'config/auth.php'); 
require_once(ROOT_PATH . 'models/Task.php'); 

class ReportController {

    /**
     * * @param array 
     * @return void
     */
    public static function getFilteredAssignments($query_params) {
        
        // --- 1. Security Check ---
        if (!is_admin()) {
            sendJsonResponse(false, 'Unauthorized access: Admin role required.', [], 403);
        }

        // --- 2. Input Processing ---
        $filters = [
            'status' => $query_params['status'] ?? 'all',
            'required_skill' => $query_params['skill'] ?? 'all',
        ];

        // --- 3. Model Interaction ---
        $task_model = new Task();
        
        $assignments = $task_model->getAllAssignmentDetails($filters);

        // --- 4. Output Response ---
        if (is_array($assignments)) {
            sendJsonResponse(true, "Successfully retrieved filtered assignments.", $assignments);
        } else {
            sendJsonResponse(false, "Failed to retrieve assignment data.", [], 500);
        }
    }
}