<?php
// mark_attendance.php
require_once 'config.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

$class_id = isset($_GET['class_id']) ? (int)$_GET['class_id'] : 0;
$date = date('Y-m-d');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $class_id = (int)$_POST['class_id'];
    $attendance_date = $_POST['attendance_date'];
    
    foreach ($_POST['attendance'] as $student_id => $status) {
        $student_id = (int)$student_id;
        $status = sanitize($status);
        $check_in_time = date('H:i:s');
        
        // Check if attendance already exists
        $check = $conn->query("SELECT id FROM attendance WHERE student_id=$student_id AND class_id=$class_id AND date='$attendance_date'");
        
        if ($check->num_rows > 0) {
            // Update existing
            $conn->query("UPDATE attendance SET status='$status', check_in_time='$check_in_time', marked_by={$_SESSION['user_id']} WHERE student_id=$student_id AND class_id=$class_id AND date='$attendance_date'");
        } else {
            // Insert new
            $conn->query("INSERT INTO attendance (student_id, class_id, date, status, check_in_time, marked_by) VALUES ($student_id, $class_id, '$attendance_date', '$status', '$check_in_time', {$_SESSION['user_id']})");
        }
    }
    
    // Update summary
    updateAttendanceSummary($class_id, $attendance_date);
    
    $success = "Attendance marked successfully!";
}

// Get students for the class
$students = [];
if ($class_id > 0) {
    $result = $conn->query("SELECT * FROM students WHERE status='active'");
    while ($row = $result->fetch_assoc()) {
        $students[] = $row;
    }
}

// Get existing attendance for today
$existingAttendance = [];
if ($class_id > 0) {
    $result = $conn->query("SELECT student_id, status FROM attendance WHERE class_id=$class_id AND date='$date'");
    while ($row = $result->fetch_assoc()) {
        $existingAttendance[$row['student_id']] = $row['status'];
    }
}

// Get classes for dropdown
$classes = $conn->query("SELECT * FROM classes");

function updateAttendanceSummary($class_id, $date) {
    global $conn;
    $month = date('Y-m-01', strtotime($date));
    
    $students = $conn->query("SELECT id FROM students WHERE status='active'");
    while ($student = $students->fetch_assoc()) {
        $student_id = $student['id'];
        
        // Fixed the SQL query - escaped 'leave' with backticks
        $attendance = $conn->query("
            SELECT 
                COUNT(CASE WHEN status='present' THEN 1 END) as present,
                COUNT(CASE WHEN status='absent' THEN 1 END) as absent,
                COUNT(CASE WHEN status='late' THEN 1 END) as late,
                COUNT(CASE WHEN status='leave' THEN 1 END) as `leave`,
                COUNT(*) as total
            FROM attendance 
            WHERE student_id=$student_id 
            AND class_id=$class_id 
            AND MONTH(date)=MONTH('$date') 
            AND YEAR(date)=YEAR('$date')
        ");
        
        // Check if query was successful
        if (!$attendance) {
            continue; // Skip if query fails
        }
        
        $attendanceData = $attendance->fetch_assoc();
        
        $total_days = $attendanceData['total'] > 0 ? $attendanceData['total'] : 1;
        $percentage = ($attendanceData['present'] / $total_days) * 100;
        
        // Check if summary exists
        $checkSummary = $conn->query("SELECT id FROM attendance_summary WHERE student_id=$student_id AND month_year='$month'");
        
        if ($checkSummary->num_rows > 0) {
            // Update existing
            $conn->query("UPDATE attendance_summary SET 
                total_present={$attendanceData['present']}, 
                total_absent={$attendanceData['absent']}, 
                total_late={$attendanceData['late']}, 
                total_leave={$attendanceData['leave']}, 
                total_days={$attendanceData['total']}, 
                percentage=$percentage 
                WHERE student_id=$student_id AND month_year='$month'
            ");
        } else {
            // Insert new
            $conn->query("INSERT INTO attendance_summary 
                (student_id, month_year, total_present, total_absent, total_late, total_leave, total_days, percentage)
                VALUES ($student_id, '$month', {$attendanceData['present']}, {$attendanceData['absent']}, {$attendanceData['late']}, {$attendanceData['leave']}, {$attendanceData['total']}, $percentage)
            ");
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Mark Attendance</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .attendance-form {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .status-select {
            padding: 5px 10px;
            border-radius: 5px;
            border: 1px solid #ddd;
            font-size: 14px;
        }
        .status-select.present { background: #d4edda; }
        .status-select.absent { background: #f8d7da; }
        .status-select.late { background: #fff3cd; }
        .status-select.leave { background: #d1ecf1; }
        .btn-mark {
            margin-top: 20px;
            padding: 12px 30px;
            font-size: 16px;
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
        .class-selector {
            background: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .class-selector select {
            padding: 10px 15px;
            border-radius: 5px;
            border: 1px solid #ddd;
            font-size: 14px;
            min-width: 250px;
        }
        .class-selector button {
            padding: 10px 20px;
            margin-left: 10px;
        }
        .table th {
            background: #34495e;
            color: white;
            padding: 12px 15px;
        }
        .table td {
            padding: 12px 15px;
        }
        .table tr:nth-child(even) {
            background: #f8f9fa;
        }
        .table tr:hover {
            background: #e8f0fe;
        }
    </style>
</head>
<body style="background-color:purple">
    <?php include 'navbar.php'; ?>
    <div class="container">
        <h1>📝 Mark Attendance</h1>
        
        <?php if(isset($success)): ?>
            <div class="alert success">✅ <?php echo $success; ?></div>
        <?php endif; ?>
        
        <?php if(isset($error)): ?>
            <div class="alert error">❌ <?php echo $error; ?></div>
        <?php endif; ?>
        
        <div class="class-selector">
            <form method="GET">
                <label for="class_id"><strong>Select Class:</strong></label>
                <select name="class_id" id="class_id" onchange="this.form.submit()">
                    <option value="">-- Select Class --</option>
                    <?php 
                    $classes = $conn->query("SELECT c.*, t.full_name as teacher_name FROM classes c LEFT JOIN teachers t ON c.teacher_id = t.id");
                    while($class = $classes->fetch_assoc()): 
                    ?>
                        <option value="<?php echo $class['id']; ?>" <?php echo $class_id==$class['id']?'selected':''; ?>>
                            <?php echo $class['class_name'] . ' - ' . $class['section'] . ' (' . ($class['teacher_name'] ?? 'No Teacher') . ')'; ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </form>
        </div>
        
        <?php if($class_id > 0): ?>
            <?php if(!empty($students)): ?>
                <div class="attendance-form">
                    <h3>Mark Attendance for <?php echo date('d-m-Y'); ?></h3>
                    <p style="color: #666; margin-bottom: 20px;">Total Students: <?php echo count($students); ?></p>
                    
                    <form method="POST">
                        <input type="hidden" name="class_id" value="<?php echo $class_id; ?>">
                        <input type="hidden" name="attendance_date" value="<?php echo $date; ?>">
                        
                        <table class="table">
                            <thead>
                                <tr>
                                    <th width="50">#</th>
                                    <th>Roll No</th>
                                    <th>Student Name</th>
                                    <th>Class</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $counter = 1; foreach($students as $student): ?>
                                    <tr>
                                        <td><?php echo $counter++; ?></td>
                                        <td><?php echo $student['roll_number']; ?></td>
                                        <td>
                                            <strong><?php echo $student['full_name']; ?></strong>
                                            <br>
                                            <small style="color: #666;"><?php echo $student['student_id']; ?></small>
                                        </td>
                                        <td><?php echo $student['class'] . ' - ' . $student['section']; ?></td>
                                        <td>
                                            <select name="attendance[<?php echo $student['id']; ?>]" class="status-select">
                                                <option value="present" <?php echo (isset($existingAttendance[$student['id']]) && $existingAttendance[$student['id']]=='present')?'selected':''; ?>>✅ Present</option>
                                                <option value="absent" <?php echo (isset($existingAttendance[$student['id']]) && $existingAttendance[$student['id']]=='absent')?'selected':''; ?>>❌ Absent</option>
                                                <option value="late" <?php echo (isset($existingAttendance[$student['id']]) && $existingAttendance[$student['id']]=='late')?'selected':''; ?>>⏰ Late</option>
                                                <option value="leave" <?php echo (isset($existingAttendance[$student['id']]) && $existingAttendance[$student['id']]=='leave')?'selected':''; ?>>📝 Leave</option>
                                            </select>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        
                        <div style="margin-top: 20px; display: flex; gap: 15px; align-items: center;">
                            <button type="submit" class="btn btn-mark">💾 Save Attendance</button>
                            
                            <?php if(!empty($existingAttendance)): ?>
                                <span style="color: #27ae60;">✅ Attendance already marked for today (will update existing records)</span>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
                
                <!-- Quick Actions -->
                <div style="margin-top: 20px; display: flex; gap: 15px; flex-wrap: wrap;">
                    <button onclick="selectAll('present')" class="btn">✅ All Present</button>
                    <button onclick="selectAll('absent')" class="btn btn-danger">❌ All Absent</button>
                    <button onclick="selectAll('late')" class="btn" style="background: #f39c12;">⏰ All Late</button>
                    <button onclick="selectAll('leave')" class="btn" style="background: #3498db;">📝 All Leave</button>
                </div>
                
            <?php else: ?>
                <div class="alert error">❌ No students found in this class. Please add students first.</div>
            <?php endif; ?>
        <?php else: ?>
            <div style="background: white; padding: 40px; border-radius: 10px; text-align: center; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
                <p style="font-size: 1.2rem; color: #7f8c8d;">👆 Select a class to mark attendance</p>
            </div>
        <?php endif; ?>
    </div>
    
    <script>
        function selectAll(status) {
            var selects = document.querySelectorAll('select[name^="attendance["]');
            selects.forEach(function(select) {
                select.value = status;
                // Update background color based on status
                select.className = 'status-select ' + status;
            });
        }
        
        // Add change event to update background color
        document.addEventListener('DOMContentLoaded', function() {
            var selects = document.querySelectorAll('select[name^="attendance["]');
            selects.forEach(function(select) {
                select.addEventListener('change', function() {
                    this.className = 'status-select ' + this.value;
                });
                // Set initial class
                select.className = 'status-select ' + select.value;
            });
        });
    </script>
</body>
</html>