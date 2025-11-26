-- --- 1. Standard Users (Admin/Parent Login) ---
CREATE TABLE IF NOT EXISTS `users` (
  `user_id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(100) NOT NULL UNIQUE,
  `password_hash` VARCHAR(255) NOT NULL,
  `role` ENUM('admin', 'parent') NOT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- --- 2. Participants (Children) ---
CREATE TABLE IF NOT EXISTS `participants` (
  `participant_id` INT AUTO_INCREMENT PRIMARY KEY,
  `parent_user_id` INT NOT NULL,
  `name` VARCHAR(100) NOT NULL,
  `pin` VARCHAR(255) NOT NULL,
  `skill_level` VARCHAR(50) DEFAULT 'Pending',
  `sensory_details` TEXT,
  `is_active` BOOLEAN DEFAULT FALSE, 
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`parent_user_id`) REFERENCES `users`(`user_id`),
  INDEX `idx_parent_user_id` (`parent_user_id`),
  INDEX `idx_is_active` (`is_active`),
  INDEX `idx_pin` (`pin`)
);

-- --- 3. Task Creation ---
CREATE TABLE IF NOT EXISTS `tasks` (
  `task_id` INT AUTO_INCREMENT PRIMARY KEY,
  `admin_user_id` INT NOT NULL,
  `name` VARCHAR(100) NOT NULL,
  `description` TEXT,
  `required_skill` VARCHAR(50) NOT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_required_skill` (`required_skill`)
);

-- --- 4. Task Steps (Pictorial Guidance) ---
CREATE TABLE IF NOT EXISTS `task_steps` (
  `step_id` INT AUTO_INCREMENT PRIMARY KEY,
  `task_id` INT NOT NULL,
  `step_number` INT NOT NULL,
  `instruction_text` VARCHAR(255) NOT NULL,
  `image_path` VARCHAR(255),
  FOREIGN KEY (`task_id`) REFERENCES `tasks`(`task_id`),
  UNIQUE KEY `idx_task_step` (`task_id`, `step_number`)
);

-- --- 5. Task Assignments ---
CREATE TABLE IF NOT EXISTS `assignments` (
  `assignment_id` INT AUTO_INCREMENT PRIMARY KEY,
  `task_id` INT NOT NULL,
  `participant_id` INT NOT NULL,
  `admin_id` INT NOT NULL,
  `status` ENUM('Pending', 'In Progress', 'Completed', 'Canceled') DEFAULT 'Pending',
  `assigned_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`task_id`) REFERENCES `tasks`(`task_id`),
  FOREIGN KEY (`participant_id`) REFERENCES `participants`(`participant_id`),
  INDEX `idx_task_id` (`task_id`),
  INDEX `idx_participant_id` (`participant_id`),
  UNIQUE KEY `idx_unique_assignment` (`task_id`, `participant_id`, `status`)
);

-- --- 6. Participant Self-Evaluation ---
CREATE TABLE IF NOT EXISTS `evaluations` (
  `evaluation_id` INT AUTO_INCREMENT PRIMARY KEY,
  `assignment_id` INT NOT NULL UNIQUE,
  `participant_id` INT NOT NULL,
  `emoji_sentiment` VARCHAR(20) NOT NULL, 
  `evaluated_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`assignment_id`) REFERENCES `assignments`(`assignment_id`),
  FOREIGN KEY (`participant_id`) REFERENCES `participants`(`participant_id`)
);

-- --- 7. Security: Login Attempt Tracking (Rate Limiting) ---
CREATE TABLE IF NOT EXISTS `login_attempts` (
  `attempt_id` INT AUTO_INCREMENT PRIMARY KEY,
  `participant_id` INT NULL,
  `ip_address` VARCHAR(45) NOT NULL,
  `attempt_time` DATETIME DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_ip_time` (`ip_address`, `attempt_time`)
);