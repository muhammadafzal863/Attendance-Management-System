<?php
// calendar.php - Monthly Calendar View of Attendance
require_once 'config.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

$month = isset($_GET['month']) ? (int)$_GET['month'] : date('m');
$year = isset($_GET['year']) ? (int)$_GET['year'] : date('Y');
$student_id = isset($_GET['student_id']) ? (int)$_GET['student_id'] : 0;

$students = $conn->query("SELECT id, full_name, student_id FROM students WHERE status='active' ORDER BY full_name");

$attendanceData = [];
if ($student_id > 0) {
    $result = $conn->query("
        SELECT day(date) as day, status 
        FROM attendance 
        WHERE student_id=$student_id 
        AND MONTH(date)=$month 
        AND YEAR(date)=$year
    ");
    while ($row = $result->fetch_assoc()) {
        $attendanceData[$row['day']] = $row['status'];
    }
}

// Get student name
$studentName = '';
if ($student_id > 0) {
    $student = $conn->query("SELECT full_name FROM students WHERE id=$student_id")->fetch_assoc();
    $studentName = $student['full_name'] ?? '';
}

// Calendar generation
$firstDay = mktime(0, 0, 0, $month, 1, $year);
$daysInMonth = date('t', $firstDay);
$startDay = date('w', $firstDay);
$today = date('d');
$currentMonth = date('m');
$currentYear = date('Y');

$prevMonth = $month - 1;
$prevYear = $year;
if ($prevMonth < 1) {
    $prevMonth = 12;
    $prevYear--;
}
$nextMonth = $month + 1;
$nextYear = $year;
if ($nextMonth > 12) {
    $nextMonth = 1;
    $nextYear++;
}

$monthNames = [
    'January', 'February', 'March', 'April', 'May', 'June',
    'July', 'August', 'September', 'October', 'November', 'December'
];
?>
<!DOCTYPE html>
<html>
<head>
    <title>Attendance Calendar</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .calendar {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .calendar-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        .calendar-header h2 {
            margin: 0;
            color: #2c3e50;
        }
        .calendar-header .nav-btn {
            background: #3498db;
            color: white;
            border: none;
            padding: 8px 20px;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
        }
        .calendar-header .nav-btn:hover {
            background: #2980b9;
        }
        .calendar-grid {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 2px;
        }
        .calendar-grid .day-header {
            text-align: center;
            font-weight: bold;
            padding: 10px;
            background: #34495e;
            color: white;
            border-radius: 5px;
        }
        .calendar-grid .day {
            text-align: center;
            padding: 15px 5px;
            background: #f8f9fa;
            border-radius: 5px;
            min-height: 60px;
            position: relative;
            cursor: default;
        }
        .calendar-grid .day .date {
            font-size: 1.1rem;
            font-weight: bold;
        }
        .calendar-grid .day .status {
            font-size: 1.5rem;
            margin-top: 5px;
        }
        .calendar-grid .day.present { background: #d4edda; }
        .calendar-grid .day.absent { background: #f8d7da; }
        .calendar-grid .day.late { background: #fff3cd; }
        .calendar-grid .day.leave { background: #d1ecf1; }
        .calendar-grid .day.today { border: 2px solid #3498db; }
        .calendar-grid .day.empty { background: transparent; }
        
        .legend {
            display: flex;
            gap: 20px;
            margin: 20px 0;
            flex-wrap: wrap;
        }
        .legend-item {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .legend-item .color-box {
            width: 20px;
            height: 20px;
            border-radius: 4px;
        }
        .student-selector {
            background: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        @media (max-width: 768px) {
            .calendar-grid .day {
                padding: 8px 2px;
                min-height: 40px;
                font-size: 0.8rem;
            }
            .calendar-grid .day .status {
                font-size: 1rem;
            }
        }
    </style>
</head>
<body style="background-color:#CBE3C3">
    <?php include 'navbar.php'; ?>
    <div class="container">
        <h1>📅 Attendance Calendar</h1>
        
        <div class="student-selector">
            <form method="GET">
                <div style="display: grid; grid-template-columns: 1fr auto; gap: 20px; align-items: end;">
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
                    <div style="display: flex; gap: 10px;">
                        <input type="hidden" name="month" value="<?php echo $month; ?>">
                        <input type="hidden" name="year" value="<?php echo $year; ?>">
                        <button type="submit" class="btn">View Calendar</button>
                    </div>
                </div>
            </form>
        </div>
        
        <?php if($student_id > 0 && $studentName): ?>
            <div class="calendar">
                <div class="calendar-header">
                    <a href="?student_id=<?php echo $student_id; ?>&month=<?php echo $prevMonth; ?>&year=<?php echo $prevYear; ?>" class="nav-btn">‹ Prev</a>
                    <h2><?php echo $monthNames[$month-1] . ' ' . $year; ?></h2>
                    <a href="?student_id=<?php echo $student_id; ?>&month=<?php echo $nextMonth; ?>&year=<?php echo $nextYear; ?>" class="nav-btn">Next ›</a>
                </div>
                <div style="text-align: center; margin-bottom: 15px;">
                    <strong>Student:</strong> <?php echo $studentName; ?>
                </div>
                
                <div class="calendar-grid">
                    <div class="day-header">Sun</div>
                    <div class="day-header">Mon</div>
                    <div class="day-header">Tue</div>
                    <div class="day-header">Wed</div>
                    <div class="day-header">Thu</div>
                    <div class="day-header">Fri</div>
                    <div class="day-header">Sat</div>
                    
                    <?php for($i = 0; $i < $startDay; $i++): ?>
                        <div class="day empty"></div>
                    <?php endfor; ?>
                    
                    <?php for($day = 1; $day <= $daysInMonth; $day++): ?>
                        <?php 
                        $status = $attendanceData[$day] ?? '';
                        $classes = 'day';
                        $classes .= $status ? ' ' . $status : '';
                        $classes .= ($day == $today && $month == $currentMonth && $year == $currentYear) ? ' today' : '';
                        ?>
                        <div class="<?php echo $classes; ?>">
                            <div class="date"><?php echo $day; ?></div>
                            <div class="status">
                                <?php if($status == 'present'): ?>✅
                                <?php elseif($status == 'absent'): ?>❌
                                <?php elseif($status == 'late'): ?>⏰
                                <?php elseif($status == 'leave'): ?>📝
                                <?php else: ?>-
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endfor; ?>
                </div>
                
                <div class="legend">
                    <div class="legend-item">
                        <div class="color-box" style="background: #d4edda;"></div>
                        <span>✅ Present</span>
                    </div>
                    <div class="legend-item">
                        <div class="color-box" style="background: #f8d7da;"></div>
                        <span>❌ Absent</span>
                    </div>
                    <div class="legend-item">
                        <div class="color-box" style="background: #fff3cd;"></div>
                        <span>⏰ Late</span>
                    </div>
                    <div class="legend-item">
                        <div class="color-box" style="background: #d1ecf1;"></div>
                        <span>📝 Leave</span>
                    </div>
                    <div class="legend-item">
                        <div class="color-box" style="background: #f8f9fa; border: 2px solid #3498db;"></div>
                        <span>Today</span>
                    </div>
                    <div class="legend-item">
                        <div class="color-box" style="background: #f8f9fa;"></div>
                        <span>No Record</span>
                    </div>
                </div>
            </div>
            
            <?php 
            // Calculate statistics for the month
            $stats = [
                'present' => 0,
                'absent' => 0,
                'late' => 0,
                'leave' => 0
            ];
            foreach($attendanceData as $status) {
                if (isset($stats[$status])) {
                    $stats[$status]++;
                }
            }
            $total = array_sum($stats);
            ?>
            <div style="background: white; padding: 20px; border-radius: 10px; margin-top: 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
                <h3>📊 Monthly Summary</h3>
                <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 15px; margin-top: 10px;">
                    <div style="text-align: center;">
                        <span style="display: block; font-size: 1.8rem; color: #27ae60;"><?php echo $stats['present']; ?></span>
                        <span style="color: #7f8c8d;">Present</span>
                    </div>
                    <div style="text-align: center;">
                        <span style="display: block; font-size: 1.8rem; color: #e74c3c;"><?php echo $stats['absent']; ?></span>
                        <span style="color: #7f8c8d;">Absent</span>
                    </div>
                    <div style="text-align: center;">
                        <span style="display: block; font-size: 1.8rem; color: #f39c12;"><?php echo $stats['late']; ?></span>
                        <span style="color: #7f8c8d;">Late</span>
                    </div>
                    <div style="text-align: center;">
                        <span style="display: block; font-size: 1.8rem; color: #3498db;"><?php echo $stats['leave']; ?></span>
                        <span style="color: #7f8c8d;">Leave</span>
                    </div>
                </div>
                <?php if($total > 0): ?>
                    <div style="text-align: center; margin-top: 10px;">
                        <strong>Attendance Rate:</strong> 
                        <span style="font-size: 1.3rem; color: <?php echo ($stats['present'] / $total) * 100 >= 75 ? '#27ae60' : '#e74c3c'; ?>">
                            <?php echo number_format(($stats['present'] / $total) * 100, 2); ?>%
                        </span>
                    </div>
                <?php endif; ?>
            </div>
        <?php elseif($student_id > 0): ?>
            <div class="alert error">Student not found!</div>
        <?php else: ?>
            <div style="background: white; padding: 40px; border-radius: 10px; text-align: center; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
                <p style="font-size: 1.2rem; color: #7f8c8d;">👆 Select a student to view their attendance calendar</p>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
