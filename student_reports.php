<?php
// student_reports.php - Individual Student Report
require_once 'config.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

$student_id = isset($_GET['student_id']) ? (int)$_GET['student_id'] : 0;
$month = isset($_GET['month']) ? $_GET['month'] : date('Y-m');

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['export'])) {
    exportStudentReport($student_id, $month);
}

function exportStudentReport($student_id, $month) {
    global $conn;
    
    // Get student details
    $student = $conn->query("SELECT * FROM students WHERE id=$student_id")->fetch_assoc();
    
    // Get attendance data
    $attendance = $conn->query("
        SELECT date, status, check_in_time, check_out_time, remarks 
        FROM attendance 
        WHERE student_id=$student_id 
        AND DATE_FORMAT(date, '%Y-%m') = '$month'
        ORDER BY date
    ");
    
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="attendance_report_'.$student['student_id'].'_'.$month.'.csv"');
    
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Date', 'Status', 'Check In Time', 'Check Out Time', 'Remarks']);
    
    while($row = $attendance->fetch_assoc()) {
        fputcsv($output, $row);
    }
    
    fclose($output);
    exit();
}

$students = $conn->query("SELECT * FROM students WHERE status='active' ORDER BY full_name");
$attendanceData = null;
$studentInfo = null;

if ($student_id > 0) {
    $studentInfo = $conn->query("SELECT * FROM students WHERE id=$student_id")->fetch_assoc();
    
    $attendanceData = $conn->query("
        SELECT date, status, check_in_time, check_out_time, remarks 
        FROM attendance 
        WHERE student_id=$student_id 
        AND DATE_FORMAT(date, '%Y-%m') = '$month'
        ORDER BY date
    ");
    
    // Statistics
    $stats = $conn->query("
        SELECT 
            COUNT(CASE WHEN status='present' THEN 1 END) as present,
            COUNT(CASE WHEN status='absent' THEN 1 END) as absent,
            COUNT(CASE WHEN status='late' THEN 1 END) as late,
            COUNT(CASE WHEN status='leave' THEN 1 END) as leave,
            COUNT(*) as total
        FROM attendance 
        WHERE student_id=$student_id 
        AND DATE_FORMAT(date, '%Y-%m') = '$month'
    ")->fetch_assoc();
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Student Reports</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .report-header {
            background: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .stats-box {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 15px;
            margin: 20px 0;
        }
        .stat-item {
            text-align: center;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
        }
        .stat-item .number {
            font-size: 2rem;
            font-weight: bold;
            color: #2c3e50;
        }
        .stat-item .label {
            color: #7f8c8d;
            margin-top: 5px;
        }
        .stat-item.present .number { color: #27ae60; }
        .stat-item.absent .number { color: #e74c3c; }
        .stat-item.late .number { color: #f39c12; }
        .stat-item.leave .number { color: #3498db; }
        
        .status-badge {
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: bold;
        }
        .status-badge.present { background: #d4edda; color: #155724; }
        .status-badge.absent { background: #f8d7da; color: #721c24; }
        .status-badge.late { background: #fff3cd; color: #856404; }
        .status-badge.leave { background: #d1ecf1; color: #0c5460; }
    </style>
</head>
<body style="background-color:#CBC3E3">
    <?php include 'navbar.php'; ?>
    <div class="container">
        <h1>📊 Student Attendance Report</h1>
        
        <form method="GET" style="background: white; padding: 20px; border-radius: 10px; margin-bottom: 20px;">
            <div style="display: grid; grid-template-columns: 1fr 1fr auto; gap: 20px; align-items: end;">
                <div class="form-group">
                    <label>Select Student</label>
                    <select name="student_id" required>
                        <option value="">Choose Student</option>
                        <?php while($student = $students->fetch_assoc()): ?>
                            <option value="<?php echo $student['id']; ?>" <?php echo ($student_id==$student['id'])?'selected':''; ?>>
                                <?php echo $student['full_name'] . ' (' . $student['student_id'] . ')'; ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Month</label>
                    <input type="month" name="month" value="<?php echo $month; ?>" required>
                </div>
                <button type="submit" class="btn">View Report</button>
            </div>
        </form>
        
        <?php if($student_id > 0 && $studentInfo && $attendanceData): ?>
            <div class="report-header">
                <h2><?php echo $studentInfo['full_name']; ?></h2>
                <p><strong>Student ID:</strong> <?php echo $studentInfo['student_id']; ?> | 
                   <strong>Class:</strong> <?php echo $studentInfo['class']; ?> | 
                   <strong>Section:</strong> <?php echo $studentInfo['section']; ?> | 
                   <strong>Roll No:</strong> <?php echo $studentInfo['roll_number']; ?></p>
                
                <div class="stats-box">
                    <div class="stat-item present">
                        <div class="number"><?php echo $stats['present'] ?? 0; ?></div>
                        <div class="label">✅ Present</div>
                    </div>
                    <div class="stat-item absent">
                        <div class="number"><?php echo $stats['absent'] ?? 0; ?></div>
                        <div class="label">❌ Absent</div>
                    </div>
                    <div class="stat-item late">
                        <div class="number"><?php echo $stats['late'] ?? 0; ?></div>
                        <div class="label">⏰ Late</div>
                    </div>
                    <div class="stat-item leave">
                        <div class="number"><?php echo $stats['leave'] ?? 0; ?></div>
                        <div class="label">📝 Leave</div>
                    </div>
                </div>
                
                <?php 
                $total = max($stats['total'] ?? 1, 1);
                $percentage = (($stats['present'] ?? 0) / $total) * 100;
                ?>
                <div style="text-align: center; margin-top: 10px;">
                    <strong>Attendance Percentage:</strong> 
                    <span style="font-size: 1.5rem; color: <?php echo $percentage >= 75 ? '#27ae60' : '#e74c3c'; ?>">
                        <?php echo number_format($percentage, 2); ?>%
                    </span>
                </div>
            </div>
            
            <div style="background: white; padding: 20px; border-radius: 10px; overflow-x: auto;">
                <div style="display: flex; justify-content: space-between; margin-bottom: 15px;">
                    <h3>Daily Attendance Details</h3>
                    <form method="POST">
                        <input type="hidden" name="export" value="1">
                        <input type="hidden" name="student_id" value="<?php echo $student_id; ?>">
                        <input type="hidden" name="month" value="<?php echo $month; ?>">
                        <button type="submit" class="btn btn-success">📥 Export CSV</button>
                    </form>
                </div>
                
                <table class="table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Status</th>
                            <th>Check In Time</th>
                            <th>Check Out Time</th>
                            <th>Remarks</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if($attendanceData->num_rows > 0): ?>
                            <?php while($row = $attendanceData->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo date('d-m-Y', strtotime($row['date'])); ?></td>
                                    <td>
                                        <span class="status-badge <?php echo $row['status']; ?>">
                                            <?php echo ucfirst($row['status']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo $row['check_in_time'] ?? '-'; ?></td>
                                    <td><?php echo $row['check_out_time'] ?? '-'; ?></td>
                                    <td><?php echo $row['remarks'] ?? '-'; ?></td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" style="text-align: center;">No attendance records found for this month</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        <?php elseif($student_id > 0): ?>
            <div class="alert error">Student not found!</div>
        <?php endif; ?>
    </div>
</body>
</html>