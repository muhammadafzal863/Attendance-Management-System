<?php
// settings.php - System Settings
require_once 'config.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect('login.php');
}

$message = '';

// Create settings table if not exists
$conn->query("
    CREATE TABLE IF NOT EXISTS settings (
        id INT PRIMARY KEY AUTO_INCREMENT,
        setting_key VARCHAR(100) UNIQUE NOT NULL,
        setting_value TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )
");

// Default settings
$defaultSettings = [
    'school_name' => 'Attendance Management System',
    'school_address' => '123 School Street, City',
    'school_phone' => '+1234567890',
    'school_email' => 'info@school.com',
    'academic_year' => date('Y'),
    'semester' => '1',
    'attendance_start_time' => '09:00',
    'attendance_end_time' => '16:00',
    'late_threshold' => '15',
    'auto_logout' => '30',
    'timezone' => 'Asia/Kolkata',
    'date_format' => 'd-m-Y',
    'time_format' => 'H:i:s'
];

// Insert default settings if they don't exist
foreach ($defaultSettings as $key => $value) {
    $check = $conn->query("SELECT id FROM settings WHERE setting_key = '$key'");
    if ($check->num_rows == 0) {
        $conn->query("INSERT INTO settings (setting_key, setting_value) VALUES ('$key', '$value')");
    }
}

// Load settings
$settings = $defaultSettings;
$result = $conn->query("SELECT * FROM settings");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    foreach ($_POST['settings'] as $key => $value) {
        $key = sanitize($key);
        $value = sanitize($value);
        
        // Check if setting exists
        $check = $conn->query("SELECT id FROM settings WHERE setting_key='$key'");
        
        if ($check && $check->num_rows > 0) {
            $conn->query("UPDATE settings SET setting_value='$value' WHERE setting_key='$key'");
        } else {
            $conn->query("INSERT INTO settings (setting_key, setting_value) VALUES ('$key', '$value')");
        }
        $settings[$key] = $value;
    }
    
    // Update timezone
    if (isset($_POST['settings']['timezone'])) {
        date_default_timezone_set($_POST['settings']['timezone']);
    }
    
    $message = "<div class='alert success'>✅ Settings saved successfully!</div>";
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>System Settings</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .settings-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
        }
        .settings-card {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .settings-card h3 {
            margin-bottom: 20px;
            color: #2c3e50;
            border-bottom: 2px solid #3498db;
            padding-bottom: 10px;
        }
        .form-group {
            margin-bottom: 18px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
            color: #555;
        }
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
            transition: border-color 0.3s;
        }
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            border-color: #3498db;
            outline: none;
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
        }
        .form-group .help-text {
            font-size: 12px;
            color: #7f8c8d;
            margin-top: 4px;
        }
        .btn-save {
            padding: 12px 40px;
            font-size: 16px;
            margin-top: 10px;
        }
        .alert {
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .alert.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .alert.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        @media (max-width: 768px) {
            .settings-container {
                grid-template-columns: 1fr;
            }
        }
        body{

            background-color: gray;
        }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>
    <div class="container">
        <h1>⚙️ System Settings</h1>
        
        <?php echo $message; ?>
        
        <div class="settings-container">
            <form method="POST" style="grid-column: 1 / -1;">
                <div class="settings-card">
                    <h3>🏫 School Information</h3>
                    <div class="form-group">
                        <label>School Name</label>
                        <input type="text" name="settings[school_name]" value="<?php echo htmlspecialchars($settings['school_name']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>School Address</label>
                        <input type="text" name="settings[school_address]" value="<?php echo htmlspecialchars($settings['school_address']); ?>">
                    </div>
                    <div class="form-group">
                        <label>School Phone</label>
                        <input type="text" name="settings[school_phone]" value="<?php echo htmlspecialchars($settings['school_phone']); ?>">
                    </div>
                    <div class="form-group">
                        <label>School Email</label>
                        <input type="email" name="settings[school_email]" value="<?php echo htmlspecialchars($settings['school_email']); ?>">
                    </div>
                </div>
                
                <div class="settings-card" style="margin-top: 30px;">
                    <h3>📚 Academic Settings</h3>
                    <div class="form-group">
                        <label>Academic Year</label>
                        <input type="text" name="settings[academic_year]" value="<?php echo htmlspecialchars($settings['academic_year']); ?>" placeholder="e.g., 2026">
                        <div class="help-text">Current academic year</div>
                    </div>
                    <div class="form-group">
                        <label>Semester</label>
                        <select name="settings[semester]">
                            <option value="1" <?php echo $settings['semester'] == '1' ? 'selected' : ''; ?>>Semester 1</option>
                            <option value="2" <?php echo $settings['semester'] == '2' ? 'selected' : ''; ?>>Semester 2</option>
                            <option value="3" <?php echo $settings['semester'] == '3' ? 'selected' : ''; ?>>Semester 3</option>
                            <option value="4" <?php echo $settings['semester'] == '4' ? 'selected' : ''; ?>>Semester 4</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Date Format</label>
                        <select name="settings[date_format]">
                            <option value="d-m-Y" <?php echo $settings['date_format'] == 'd-m-Y' ? 'selected' : ''; ?>>DD-MM-YYYY</option>
                            <option value="m-d-Y" <?php echo $settings['date_format'] == 'm-d-Y' ? 'selected' : ''; ?>>MM-DD-YYYY</option>
                            <option value="Y-m-d" <?php echo $settings['date_format'] == 'Y-m-d' ? 'selected' : ''; ?>>YYYY-MM-DD</option>
                            <option value="d/m/Y" <?php echo $settings['date_format'] == 'd/m/Y' ? 'selected' : ''; ?>>DD/MM/YYYY</option>
                        </select>
                    </div>
                </div>
                
                <div class="settings-card" style="margin-top: 30px;">
                    <h3>⏰ Attendance Settings</h3>
                    <div class="form-group">
                        <label>Attendance Start Time</label>
                        <input type="time" name="settings[attendance_start_time]" value="<?php echo htmlspecialchars($settings['attendance_start_time']); ?>">
                        <div class="help-text">When attendance marking starts</div>
                    </div>
                    <div class="form-group">
                        <label>Attendance End Time</label>
                        <input type="time" name="settings[attendance_end_time]" value="<?php echo htmlspecialchars($settings['attendance_end_time']); ?>">
                        <div class="help-text">When attendance marking ends</div>
                    </div>
                    <div class="form-group">
                        <label>Late Threshold (minutes)</label>
                        <input type="number" name="settings[late_threshold]" value="<?php echo htmlspecialchars($settings['late_threshold']); ?>" min="0" max="60">
                        <div class="help-text">Minutes after start time considered as late</div>
                    </div>
                    <div class="form-group">
                        <label>Auto Logout (minutes)</label>
                        <input type="number" name="settings[auto_logout]" value="<?php echo htmlspecialchars($settings['auto_logout']); ?>" min="5" max="120">
                        <div class="help-text">Auto logout after inactivity (minutes)</div>
                    </div>
                </div>
                
                <div class="settings-card" style="margin-top: 30px;">
                    <h3>🌐 Regional Settings</h3>
                    <div class="form-group">
                        <label>Timezone</label>
                        <select name="settings[timezone]">
                            <?php
                            $timezones = [
                                'Asia/Kolkata' => 'Asia/Kolkata (UTC+5:30)',
                                'Asia/Dubai' => 'Asia/Dubai (UTC+4:00)',
                                'Asia/Singapore' => 'Asia/Singapore (UTC+8:00)',
                                'Asia/Tokyo' => 'Asia/Tokyo (UTC+9:00)',
                                'America/New_York' => 'America/New_York (UTC-5:00)',
                                'America/Los_Angeles' => 'America/Los_Angeles (UTC-8:00)',
                                'Europe/London' => 'Europe/London (UTC+0:00)',
                                'Europe/Paris' => 'Europe/Paris (UTC+1:00)',
                                'Australia/Sydney' => 'Australia/Sydney (UTC+11:00)',
                                'Pacific/Auckland' => 'Pacific/Auckland (UTC+13:00)'
                            ];
                            foreach ($timezones as $value => $label) {
                                $selected = ($settings['timezone'] == $value) ? 'selected' : '';
                                echo "<option value='$value' $selected>$label</option>";
                            }
                            ?>
                        </select>
                        <div class="help-text">Current time: <?php echo date('Y-m-d H:i:s'); ?></div>
                    </div>
                    <div class="form-group">
                        <label>Time Format</label>
                        <select name="settings[time_format]">
                            <option value="H:i:s" <?php echo $settings['time_format'] == 'H:i:s' ? 'selected' : ''; ?>>24-hour (HH:MM:SS)</option>
                            <option value="h:i:s A" <?php echo $settings['time_format'] == 'h:i:s A' ? 'selected' : ''; ?>>12-hour (HH:MM:SS AM/PM)</option>
                        </select>
                    </div>
                </div>
                
                <div style="grid-column: 1 / -1; margin-top: 30px; text-align: center;">
                    <button type="submit" class="btn btn-save">💾 Save All Settings</button>
                </div>
            </form>
        </div>
        
        <!-- Quick Actions -->
        <div style="background: white; padding: 20px; border-radius: 10px; margin-top: 30px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
            <h3>🔄 Quick Actions</h3>
            <div style="display: flex; gap: 15px; flex-wrap: wrap; margin-top: 15px;">
                <button onclick="resetSettings()" class="btn btn-danger">Reset to Defaults</button>
                <button onclick="exportSettings()" class="btn">📤 Export Settings</button>
                <button onclick="window.location.href='update_db.php'" class="btn" style="background: #9b59b6;">🔄 Update Database</button>
            </div>
        </div>
    </div>
    
    <script>
        function resetSettings() {
            if (confirm('Are you sure you want to reset all settings to default values?')) {
                window.location.href = 'reset_settings.php';
            }
        }
        
        function exportSettings() {
            // Create a JSON export
            const settings = <?php echo json_encode($settings); ?>;
            const dataStr = "data:text/json;charset=utf-8," + encodeURIComponent(JSON.stringify(settings, null, 2));
            const downloadAnchorNode = document.createElement('a');
            downloadAnchorNode.setAttribute("href", dataStr);
            downloadAnchorNode.setAttribute("download", "settings_backup.json");
            document.body.appendChild(downloadAnchorNode);
            downloadAnchorNode.click();
            downloadAnchorNode.remove();
        }
    </script>
</body>
</html>