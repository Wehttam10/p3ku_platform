<?php
/**
 * Report Model
 * Handles data aggregation for Admin and Parent reporting.
 */

// --- 1. SAFETY: Define ROOT_PATH if missing ---
if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', dirname(__DIR__) . '/');
}

// --- 2. INCLUDES ---
require_once(ROOT_PATH . 'config/db.php');

if (file_exists(ROOT_PATH . 'models/participant.php')) {
    require_once(ROOT_PATH . 'models/participant.php');
} else {
    require_once(ROOT_PATH . 'models/participant.php');
}

class Report {
    private $conn;
    
    private $p_table = "participants";
    private $a_table = "assignments";
    private $t_table = "tasks";
    private $e_table = "evaluations";

    public function __construct() {
        $this->conn = get_db_connection();
    }

    public function getAdminSummary() {
        $summary = [];

        try {
            // 1. Participants
            $query_p = "SELECT 
                          COUNT(participant_id) AS total_participants,
                          SUM(CASE WHEN skill_level = 'Pending' THEN 1 ELSE 0 END) AS pending_skill_reviews,
                          SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) AS active_participants
                        FROM " . $this->p_table;
            $stmt_p = $this->conn->query($query_p);
            $summary['participants'] = $stmt_p->fetch(PDO::FETCH_ASSOC);

            // 2. Tasks
            $query_t = "SELECT COUNT(task_id) AS total_tasks FROM " . $this->t_table;
            $stmt_t = $this->conn->query($query_t);
            $summary['tasks'] = $stmt_t->fetch(PDO::FETCH_ASSOC);

            // 3. Assignments
            $query_a = "SELECT 
                          COUNT(assignment_id) AS total_assignments,
                          SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END) AS pending_start,
                          SUM(CASE WHEN status = 'In Progress' THEN 1 ELSE 0 END) AS in_progress
                        FROM " . $this->a_table;
            $stmt_a = $this->conn->query($query_a);
            $summary['assignments'] = $stmt_a->fetch(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            error_log("Admin summary failed: " . $e->getMessage());
            return [];
        }

        return $summary;
    }

    public function getParentReportData($participant_id) {
        $report = ['summary' => [], 'history' => []];

        if (!filter_var($participant_id, FILTER_VALIDATE_INT)) {
            return $report;
        }

        try {
            // 1. Summary Stats
            $query_summary = "SELECT 
                                COUNT(DISTINCT a.assignment_id) AS total_assignments,
                                SUM(CASE WHEN a.status = 'Completed' THEN 1 ELSE 0 END) AS tasks_completed
                              FROM " . $this->a_table . " a
                              WHERE a.participant_id = :pid";

            $stmt_summary = $this->conn->prepare($query_summary);
            $stmt_summary->bindParam(":pid", $participant_id);
            $stmt_summary->execute();
            $report['summary'] = $stmt_summary->fetch(PDO::FETCH_ASSOC);

            // 2. History (Completed tasks with Sentiment)
            $query_history = "SELECT 
                                t.name AS task_name, 
                                e.emoji_sentiment, 
                                e.evaluated_at
                              FROM " . $this->e_table . " e
                              JOIN " . $this->a_table . " a ON e.assignment_id = a.assignment_id
                              JOIN " . $this->t_table . " t ON a.task_id = t.task_id
                              WHERE e.participant_id = :pid
                              ORDER BY e.evaluated_at DESC
                              LIMIT 10";

            $stmt_history = $this->conn->prepare($query_history);
            $stmt_history->bindParam(":pid", $participant_id);
            $stmt_history->execute();
            $report['history'] = $stmt_history->fetchAll(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            error_log("Parent report error: " . $e->getMessage());
        }

        return $report;
    }

    public function getConsolidatedParentData($parent_id) {
        $data = ['children' => []];
        
        // Ensure Participant class is loaded
        if (!class_exists('Participant')) {
            return $data;
        }

        $p_model = new Participant(); 
        $children = $p_model->getChildrenByParentId($parent_id);
        
        if ($children) {
            foreach ($children as $child) {
                $child_id = $child['participant_id'];
                
                $report_data = $this->getParentReportData($child_id);
                
                $data['children'][] = [
                    'id' => $child_id,
                    'name' => $child['name'],
                    'skill_level' => $child['skill_level'],
                    'is_active' => $child['is_active'],
                    'pin' => $child['pin'],
                    'summary' => $report_data['summary']
                ];
            }
        }
        
        return $data;
    }
}
?>