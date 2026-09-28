<?php
// update_db.php - Update database with new tables if needed
require_once 'config.php';

if (!isLoggedIn() || !isAdmin()) {
    die('Access denied');
}

$updates = [];

// Add settings table
$conn->query("
    CREATE TABLE IF NOT EXISTS settings (
        id INT PRIMARY KEY AUTO_INCREMENT,
        setting_key VARCHAR(100) UNIQUE NOT NULL,
        setting_value TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )
");
$updates[] = "Settings table created/updated";

// Add notifications table
$conn->query("
    CREATE TABLE IF NOT EXISTS notifications (
        id INT PRIMARY KEY AUTO_INCREMENT,
        user_id INT,
        title VARCHAR(255),
        message TEXT,
        type ENUM('info', 'success', 'warning', 'error') DEFAULT 'info',
        is_read BOOLEAN DEFAULT FALSE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )
");
$updates[] = "Notifications table created/updated";

// Add logs table
$conn->query("
    CREATE TABLE IF NOT EXISTS activity_logs (
        id INT PRIMARY KEY AUTO_INCREMENT,
        user_id INT,
        action VARCHAR(255),
        details TEXT,
        ip_address VARCHAR(45),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
    )
");
$updates[] = "Activity logs table created/updated";

// Add index for performance
$conn->query("CREATE INDEX idx_attendance_student_date ON attendance(student_id, date)");
$conn->query("CREATE INDEX idx_attendance_class_date ON attendance(class_id, date)");
$updates[] = "Indexes added for performance";

?>
<!DOCTYPE html>
<html>
<head>
    <title>Database Update</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <?php include 'navbar.php'; ?>
    <div class="container">
        <h1>🔄 Database Update</h1>
        <div style="background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
            <h3>Update Summary</h3>
            <ul>
                <?php foreach($updates as $update): ?>
                    <li style="padding: 8px 0; color: #27ae60;">✅ <?php echo $update; ?></li>
                <?php endforeach; ?>
            </ul>
            <p style="margin-top: 20px;">Database update completed successfully!</p>
            <a href="dashboard.php" class="btn">Go to Dashboard</a>
        </div>
    </div>
</body>
</html>