<?php
/**
 * Task Model - ULTIMATE VERSION
 * Contains all methods for Admin, Parent, and Participant features.
 */

if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', dirname(__DIR__) . '/');
}

require_once(ROOT_PATH . 'config/db.php');

class Task {
    private $conn;
    
    private $task_table = "tasks";
    private $steps_table = "task_steps";
    private $assignment_table = "assignments";
    private $evaluation_table = "evaluations";
    private $participant_table = "participants";

    public function __construct() {
        $this->conn = get_db_connection();
    }
    
    public function __get($property) {
        if ($property === 'conn') {
            return $this->conn;
        }
        return null;
    }

    public function createTask($admin_id, $name, $description, $required_skill) {
        $query = "INSERT INTO " . $this->task_table . " 
                  SET admin_user_id = :admin_id, name = :name, description = :description, required_skill = :required_skill";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":admin_id", $admin_id);
        $stmt->bindParam(":name", $name);
        $stmt->bindParam(":description", $description);
        $stmt->bindParam(":required_skill", $required_skill);

        if ($stmt->execute()) {
            return $this->conn->lastInsertId();
        }
        return false;
    }
    
    public function createTaskSteps($task_id, $steps) {
        if (empty($steps)) return true;
        $query = "INSERT INTO " . $this->steps_table . " (task_id, step_number, instruction_text, image_path) VALUES (:task_id, :step_number, :instruction, :image_path)";
        $stmt = $this->conn->prepare($query);

        foreach ($steps as $step) {
            $instruction = trim($step['instruction_text'] ?? '');
            $image_path = trim($step['image_path'] ?? '');
            if (empty($instruction)) continue;

            $stmt->bindValue(':task_id', $task_id);
            $stmt->bindValue(':step_number', $step['step_number']);
            $stmt->bindValue(':instruction', $instruction);
            $stmt->bindValue(':image_path', $image_path);
            if (!$stmt->execute()) return false;
        }
        return true;
    }
    
    public function updateTask($task_id, $name, $description, $required_skill) {
        $query = "UPDATE " . $this->task_table . " SET name = :name, description = :description, required_skill = :skill WHERE task_id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(':name', $name);
        $stmt->bindValue(':description', $description);
        $stmt->bindValue(':skill', $required_skill);
        $stmt->bindValue(':id', $task_id);
        return $stmt->execute();
    }

    public function deleteAllSteps($task_id) {
        $query = "DELETE FROM " . $this->steps_table . " WHERE task_id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(':id', $task_id);
        return $stmt->execute();
    }

    public function deleteTask($task_id) {
        try {
            $this->conn->beginTransaction();
            // 1. Delete Steps
            $stmt = $this->conn->prepare("DELETE FROM " . $this->steps_table . " WHERE task_id = :id");
            $stmt->bindValue(':id', $task_id);
            $stmt->execute();
            // 2. Delete Assignments
            $stmt = $this->conn->prepare("DELETE FROM " . $this->assignment_table . " WHERE task_id = :id");
            $stmt->bindValue(':id', $task_id);
            $stmt->execute();
            // 3. Delete Task
            $stmt = $this->conn->prepare("DELETE FROM " . $this->task_table . " WHERE task_id = :id");
            $stmt->bindValue(':id', $task_id);
            $stmt->execute();
            $this->conn->commit();
            return true;
        } catch (PDOException $e) {
            if ($this->conn->inTransaction()) $this->conn->rollBack();
            error_log("Delete Task Error: " . $e->getMessage());
            return false;
        }
    }

    public function getAllTasks() {
        $stmt = $this->conn->query("SELECT task_id, name, required_skill, created_at FROM " . $this->task_table . " ORDER BY created_at DESC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC); 
    }

    public function getTaskWithSteps($task_id) {
        $stmt = $this->conn->prepare("SELECT * FROM " . $this->task_table . " WHERE task_id = :id LIMIT 1");
        $stmt->bindValue(":id", $task_id);
        $stmt->execute();
        $task = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$task) return false;

        $stmtSteps = $this->conn->prepare("SELECT * FROM " . $this->steps_table . " WHERE task_id = :id ORDER BY step_number ASC");
        $stmtSteps->bindValue(":id", $task_id);
        $stmtSteps->execute();
        $task['steps'] = $stmtSteps->fetchAll(PDO::FETCH_ASSOC);
        return $task;
    }

    public function getAllParticipants() {
        $stmt = $this->conn->query("SELECT participant_id, name, skill_level FROM " . $this->participant_table . " ORDER BY name ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function createAssignments($task_id, $admin_id, array $participant_ids) {
        if (empty($participant_ids)) return ['success' => true, 'assigned_count' => 0, 'skipped_count' => 0];

        $pids = array_filter($participant_ids, 'is_numeric');
        if (empty($pids)) return ['success' => false, 'assigned_count' => 0, 'skipped_count' => 0];

        $placeholders = implode(',', array_fill(0, count($pids), '?'));
        $sql = "SELECT participant_id FROM " . $this->assignment_table . " WHERE task_id = ? AND participant_id IN ($placeholders) AND status IN ('Pending', 'In Progress')";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute(array_merge([$task_id], $pids));
        $existing = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        $to_insert = array_diff($pids, $existing);
        $skipped = count($pids) - count($to_insert);

        if (empty($to_insert)) return ['success' => true, 'assigned_count' => 0, 'skipped_count' => $skipped];
        
        $sqlInsert = "INSERT INTO " . $this->assignment_table . " (task_id, participant_id, admin_id, status, assigned_at) VALUES (:tid, :pid, :aid, 'Pending', NOW())";
        $stmtInsert = $this->conn->prepare($sqlInsert);
        $count = 0;

        foreach ($to_insert as $pid) {
            $stmtInsert->bindValue(':tid', $task_id);
            $stmtInsert->bindValue(':pid', $pid);
            $stmtInsert->bindValue(':aid', $admin_id);
            if ($stmtInsert->execute()) $count++;
        }

        return ['success' => true, 'assigned_count' => $count, 'skipped_count' => $skipped];
    }

    public function getParticipantTasks($participant_id) {
        $sql = "SELECT a.assignment_id, a.status, t.task_id, t.name, t.description, t.required_skill, t.created_at
                FROM assignments a JOIN tasks t ON a.task_id = t.task_id
                WHERE a.participant_id = :pid
                ORDER BY CASE a.status WHEN 'In Progress' THEN 1 WHEN 'Pending' THEN 2 ELSE 3 END";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':pid', $participant_id);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getAssignmentDetails($assignment_id, $participant_id) {
        $sql = "SELECT a.assignment_id, a.status, t.task_id, t.name, t.description 
                FROM assignments a JOIN tasks t ON a.task_id = t.task_id
                WHERE a.assignment_id = :aid AND a.participant_id = :pid";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':aid', $assignment_id);
        $stmt->bindValue(':pid', $participant_id);
        $stmt->execute();
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$data) return false;

        $sqlSteps = "SELECT step_number, instruction_text, image_path FROM task_steps WHERE task_id = :tid ORDER BY step_number ASC";
        $stmtSteps = $this->conn->prepare($sqlSteps);
        $stmtSteps->bindValue(':tid', $data['task_id']);
        $stmtSteps->execute();
        $data['steps'] = $stmtSteps->fetchAll(PDO::FETCH_ASSOC);
        
        return $data;
    }

    public function markInProgress($assignment_id) {
        $sql = "UPDATE assignments SET status = 'In Progress' WHERE assignment_id = :aid AND status = 'Pending'";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':aid', $assignment_id);
        $stmt->execute();
    }

    public function submitSelfEvaluation($assignment_id, $participant_id, $emoji_sentiment) {
        try {
            $this->conn->beginTransaction();
            
            $sqlEval = "INSERT INTO " . $this->evaluation_table . " (assignment_id, participant_id, emoji_sentiment, evaluated_at) VALUES (:aid, :pid, :sent, NOW())";
            $stmt = $this->conn->prepare($sqlEval);
            $stmt->bindValue(':aid', $assignment_id);
            $stmt->bindValue(':pid', $participant_id);
            $stmt->bindValue(':sent', $emoji_sentiment);
            $stmt->execute();

            $sqlUpdate = "UPDATE " . $this->assignment_table . " SET status = 'Completed' WHERE assignment_id = :aid";
            $stmt = $this->conn->prepare($sqlUpdate);
            $stmt->bindValue(':aid', $assignment_id);
            $stmt->execute();

            $this->conn->commit();
            return true;
        } catch (PDOException $e) {
            if ($this->conn->inTransaction()) $this->conn->rollBack();
            return false;
        }
    }

    public function getCompletedTasks() {
        $sql = "SELECT a.assignment_id, p.name AS participant_name, t.name AS task_name, e.emoji_sentiment, e.evaluated_at AS completed_at
                FROM assignments a
                INNER JOIN participants p ON a.participant_id = p.participant_id
                INNER JOIN tasks t ON a.task_id = t.task_id
                LEFT JOIN evaluations e ON a.assignment_id = e.assignment_id
                WHERE a.status = 'Completed'
                ORDER BY e.evaluated_at DESC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getAllAssignmentDetails($filters = []) {
        $where = " WHERE 1=1 ";
        $params = [];
        
        if (!empty($filters['status']) && $filters['status'] !== 'all') {
            $where .= " AND a.status = :status ";
            $params[':status'] = $filters['status'];
        }

        if (!empty($filters['required_skill']) && $filters['required_skill'] !== 'all') {
            $where .= " AND t.required_skill = :required_skill ";
            $params[':required_skill'] = $filters['required_skill'];
        }

        $sql = "SELECT 
                    a.assignment_id, a.status, a.assigned_at,
                    t.name AS task_name, t.required_skill,
                    p.name AS participant_name, p.skill_level AS participant_skill,
                    e.emoji_sentiment
                  FROM " . $this->assignment_table . " a
                  INNER JOIN " . $this->task_table . " t ON a.task_id = t.task_id
                  INNER JOIN " . $this->participant_table . " p ON a.participant_id = p.participant_id
                  LEFT JOIN " . $this->evaluation_table . " e ON a.assignment_id = e.assignment_id"
                  . $where .
                  " ORDER BY a.assigned_at DESC";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params); 
        return $stmt->fetchAll(PDO::FETCH_ASSOC); 
    }
}
?>