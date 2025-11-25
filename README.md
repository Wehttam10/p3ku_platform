# 🌱 P3KU EmpowerAbility Platform (Your Project Title)

This is a digital garden and skill-tracking platform built with PHP and MySQL. It's designed to help users track their learning, manage skills, and share progress in a supportive community.

This project was built as part of [Your Course/Personal Goal].

## ✨ Features

* Secure user login and registration system.
* **Dashboard:** An "at-a-glance" view of all stats.
* **Digital Journal:** Users can write, save, and delete private journal entries.
* **Skill Tracker:** Users can add skills and update their status (Learning, Improving, Mastered).
* **Badges:** Users earn achievements for milestones (e.g., "5 Journal Entries," "First Skill Mastered").
* **Community Wall:** A place for users to share public posts.
* **Post Management:** Users can edit and delete their own community posts.

## 🚀 How to Use / Setup

To run this project locally, you will need a web server with PHP and a MySQL database (like XAMPP or MAMP).

1.  **Clone the repository:**
    ```bash
    git clone [https://github.com/YourUsername/your-repository-name.git](https://github.com/YourUsername/your-repository-name.git)
    ```
2.  **Move to the project directory:**
    ```bash
    cd your-repository-name
    ```
3.  **Database Setup:**
    * Import the `database.sql` file (if you have one) into phpMyAdmin.
    * If not, create the tables manually (you can add the SQL `CREATE TABLE...` commands here).
4.  **Configure the Application:**
    * Find the `config.php.example` file.
    * Make a copy of it and rename the copy to `config.php`.
    * Open `config.php` and fill in your database details:
    ```php
    define('DB_USER', 'root');
    define('DB_PASS', 'YOUR_DATABASE_PASSWORD');
    define('DB_NAME', 'p3ku_platform');
    ```
5.  **Run the project:** Open the project in your browser (e.g., `http://localhost/your-repository-name`).
