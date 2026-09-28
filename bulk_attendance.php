<?php
// bulk_attendance.php - Import attendance from CSV
require_once 'config.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect('login.php');
}

$message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['csv_file'])) {
    $file = $_FILES['csv_file'];
    
    if ($file['error'] == 0 && pathinfo($file['name'], PATHINFO_EXTENSION) == 'csv') {
        $handle = fopen($file['tmp_name'], 'r');
        $header = fgetcsv($handle); // Skip header
        
        $successCount = 0;
        $errorCount = 0;
        
        while (($row = fgetcsv($handle)) !== FALSE) {
            // Format: student_id, date, status, check_in_time, remarks
            $student_id = $row[0] ?? '';
            $date = $row[1] ?? '';
            $status = $row[2] ?? '';
            $check_in_time = $row[3] ?? date('H:i:s');
            $remarks = $row[4] ?? '';
            
            // Validate data
            $student = $conn->query("SELECT id FROM students WHERE student_id='$student_id' AND status='active'");
            if ($student->num_rows > 0 && in_array($status, ['present', 'absent', 'late', 'leave'])) {
                $student_id = $student->fetch_assoc()['id'];
                
                // Check if attendance exists
                $check = $conn->query("SELECT id FROM attendance WHERE student_id=$student_id AND date='$date'");
                
                if ($check->num_rows > 0) {
                    $conn->query("UPDATE attendance SET status='$status', check_in_time='$check_in_time', remarks='$remarks', marked_by={$_SESSION['user_id']} WHERE student_id=$student_id AND date='$date'");
                } else {
                    $conn->query("INSERT INTO attendance (student_id, date, status, check_in_time, remarks, marked_by) VALUES ($student_id, '$date', '$status', '$check_in_time', '$remarks', {$_SESSION['user_id']})");
                }
                $successCount++;
            } else {
                $errorCount++;
            }
        }
        
        fclose($handle);
        $message = "<div class='alert success'>Imported $successCount records. Failed: $errorCount</div>";
    } else {
        $message = "<div class='alert error'>Please upload a valid CSV file</div>";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Bulk Attendance Import</title>
    <link rel="stylesheet" href="style.css">
</head>
<body style="background-color:gray">
    <?php include 'navbar.php'; ?>
    <div class="container">
        <h1>📥 Bulk Attendance Import</h1>
        
        <?php echo $message; ?>
        
        <div style="background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
            <h3>Upload CSV File</h3>
            <p style="color: #666; margin-bottom: 20px;">
                CSV Format: <strong>student_id, date(Y-m-d), status(present/absent/late/leave), check_in_time(H:i:s), remarks</strong>
            </p>
            
            <form method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label>Select CSV File</label>
                    <input type="file" name="csv_file" accept=".csv" required>
                </div>
                <button type="submit" class="btn">Upload & Import</button>
            </form>
            
            <div style="margin-top: 20px; padding: 15px; background: #f8f9fa; border-radius: 5px;">
                <h4>📋 Sample CSV Format</h4>
                <pre style="background: #fff; padding: 10px; border: 1px solid #ddd; border-radius: 3px;">
student_id,date,status,check_in_time,remarks
STU001,2026-01-15,present,09:00:00,On time
STU002,2026-01-15,absent,,Sick
STU003,2026-01-15,late,09:30:00,Traffic jam
                </pre>
            </div>
        </div>
    </div>
</body>
</html>