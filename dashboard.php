<?php
// dashboard.php - Enhanced with more statistics and charts
require_once 'config.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

// Get statistics with date ranges
$today = date('Y-m-d');
$weekStart = date('Y-m-d', strtotime('monday this week'));
$monthStart = date('Y-m-01');

// Total counts
$totalStudents = $conn->query("SELECT COUNT(*) as count FROM students WHERE status='active'")->fetch_assoc()['count'];
$totalTeachers = $conn->query("SELECT COUNT(*) as count FROM teachers WHERE status='active'")->fetch_assoc()['count'];
$totalClasses = $conn->query("SELECT COUNT(*) as count FROM classes")->fetch_assoc()['count'];

// Today's attendance
$todayTotal = $conn->query("SELECT COUNT(DISTINCT student_id) as count FROM attendance WHERE date='$today'")->fetch_assoc()['count'];
$todayPresent = $conn->query("SELECT COUNT(*) as count FROM attendance WHERE date='$today' AND status='present'")->fetch_assoc()['count'];
$todayAbsent = $conn->query("SELECT COUNT(*) as count FROM attendance WHERE date='$today' AND status='absent'")->fetch_assoc()['count'];
$todayLate = $conn->query("SELECT COUNT(*) as count FROM attendance WHERE date='$today' AND status='late'")->fetch_assoc()['count'];
$todayLeave = $conn->query("SELECT COUNT(*) as count FROM attendance WHERE date='$today' AND status='leave'")->fetch_assoc()['count'];

// Weekly attendance
$weeklyPresent = $conn->query("SELECT COUNT(*) as count FROM attendance WHERE date BETWEEN '$weekStart' AND '$today' AND status='present'")->fetch_assoc()['count'];
$weeklyAbsent = $conn->query("SELECT COUNT(*) as count FROM attendance WHERE date BETWEEN '$weekStart' AND '$today' AND status='absent'")->fetch_assoc()['count'];

// Monthly attendance
$monthlyPresent = $conn->query("SELECT COUNT(*) as count FROM attendance WHERE date BETWEEN '$monthStart' AND '$today' AND status='present'")->fetch_assoc()['count'];
$monthlyAbsent = $conn->query("SELECT COUNT(*) as count FROM attendance WHERE date BETWEEN '$monthStart' AND '$today' AND status='absent'")->fetch_assoc()['count'];

// Recent attendance
$recentAttendance = $conn->query("
    SELECT a.*, s.full_name, s.student_id 
    FROM attendance a 
    JOIN students s ON a.student_id = s.id 
    ORDER BY a.date DESC, a.created_at DESC 
    LIMIT 10
");

// Class-wise attendance today
$classAttendance = $conn->query("
    SELECT c.class_name, c.section, 
           COUNT(CASE WHEN a.status='present' THEN 1 END) as present,
           COUNT(CASE WHEN a.status='absent' THEN 1 END) as absent,
           COUNT(*) as total
    FROM classes c
    LEFT JOIN students s ON s.class = c.class_name AND s.section = c.section
    LEFT JOIN attendance a ON s.id = a.student_id AND a.date='$today'
    WHERE s.status='active'
    GROUP BY c.id
    LIMIT 5
");

// Top performing students (highest attendance)
$topStudents = $conn->query("
    SELECT s.full_name, s.student_id, 
           COUNT(CASE WHEN a.status='present' THEN 1 END) as present,
           COUNT(*) as total,
           ROUND((COUNT(CASE WHEN a.status='present' THEN 1 END) / COUNT(*)) * 100, 2) as percentage
    FROM students s
    JOIN attendance a ON s.id = a.student_id
    WHERE a.date BETWEEN DATE_SUB(NOW(), INTERVAL 30 DAY) AND NOW()
    GROUP BY s.id
    HAVING total > 0
    ORDER BY percentage DESC
    LIMIT 5
");
?>
<!DOCTYPE html>
<html>
<head>
    <title>Dashboard - Attendance System</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
            transition: transform 0.3s;
        }
        .stat-card:hover {
            transform: translateY(-5px);
        }
        .stat-card .icon {
            font-size: 2.5rem;
            margin-bottom: 10px;
        }
        .stat-card .number {
            font-size: 2.5rem;
            font-weight: bold;
            color: #2c3e50;
        }
        .stat-card .label {
            color: #7f8c8d;
            margin-top: 5px;
        }
        .stat-card .sub-info {
            font-size: 0.9rem;
            color: #95a5a6;
            margin-top: 10px;
        }
        .stat-card.primary { border-left: 4px solid #3498db; }
        .stat-card.success { border-left: 4px solid #2ecc71; }
        .stat-card.warning { border-left: 4px solid #f39c12; }
        .stat-card.danger { border-left: 4px solid #e74c3c; }
        .stat-card.info { border-left: 4px solid #1abc9c; }
        
        .section-title {
            margin: 30px 0 20px;
            color: #2c3e50;
            border-bottom: 2px solid #ecf0f1;
            padding-bottom: 10px;
        }
        .attendance-summary {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 10px;
            margin: 15px 0;
        }
        .attendance-summary .item {
            text-align: center;
            padding: 15px;
            border-radius: 8px;
            background: #f8f9fa;
        }
        .attendance-summary .item .value {
            font-size: 1.8rem;
            font-weight: bold;
        }
        .attendance-summary .item .label {
            font-size: 0.85rem;
            color: #7f8c8d;
        }
        .attendance-summary .item.present .value { color: #27ae60; }
        .attendance-summary .item.absent .value { color: #e74c3c; }
        .attendance-summary .item.late .value { color: #f39c12; }
        .attendance-summary .item.leave .value { color: #3498db; }
        
        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin-top: 20px;
        }
        .quick-actions .action-btn {
            background: white;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
            text-decoration: none;
            color: #2c3e50;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            transition: all 0.3s;
        }
        .quick-actions .action-btn:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.15);
        }
        .quick-actions .action-btn .emoji {
            font-size: 2.5rem;
            display: block;
            margin-bottom: 8px;
        }
        
        @media (max-width: 768px) {
            .attendance-summary {
                grid-template-columns: 1fr 1fr;
            }
            .dashboard-grid {
                grid-template-columns: 1fr 1fr;
            }
        }
        body{

            background-color: powderblue;
        }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>
    <div class="container">
        <h1>📊 Dashboard</h1>
        
        <!-- Stats Cards -->
        <div class="dashboard-grid">
            <div class="stat-card primary">
                <div class="icon">👨‍🎓</div>
                <div class="number"><?php echo $totalStudents; ?></div>
                <div class="label">Total Students</div>
                <div class="sub-info">Active Students</div>
            </div>
            <div class="stat-card success">
                <div class="icon">👨‍🏫</div>
                <div class="number"><?php echo $totalTeachers; ?></div>
                <div class="label">Total Teachers</div>
                <div class="sub-info">Active Teachers</div>
            </div>
            <div class="stat-card info">
                <div class="icon">📚</div>
                <div class="number"><?php echo $totalClasses; ?></div>
                <div class="label">Total Classes</div>
                <div class="sub-info">Active Classes</div>
            </div>
            <div class="stat-card warning">
                <div class="icon">📅</div>
                <div class="number"><?php echo $todayTotal; ?></div>
                <div class="label">Today's Attendance</div>
                <div class="sub-info"><?php echo date('d-m-Y'); ?></div>
            </div>
        </div>
        
        <!-- Today's Attendance Summary -->
        <div style="background: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
            <h3>Today's Attendance Summary</h3>
            <div class="attendance-summary">
                <div class="item present">
                    <div class="value"><?php echo $todayPresent; ?></div>
                    <div class="label">✅ Present</div>
                </div>
                <div class="item absent">
                    <div class="value"><?php echo $todayAbsent; ?></div>
                    <div class="label">❌ Absent</div>
                </div>
                <div class="item late">
                    <div class="value"><?php echo $todayLate; ?></div>
                    <div class="label">⏰ Late</div>
                </div>
                <div class="item leave">
                    <div class="value"><?php echo $todayLeave; ?></div>
                    <div class="label">📝 Leave</div>
                </div>
            </div>
            
            <?php if($todayTotal > 0): ?>
                <?php $percentage = round(($todayPresent / max($todayTotal, 1)) * 100, 2); ?>
                <div style="margin-top: 10px; text-align: center;">
                    <strong>Overall Attendance Rate:</strong> 
                    <span style="font-size: 1.3rem; color: <?php echo $percentage >= 75 ? '#27ae60' : '#e74c3c'; ?>">
                        <?php echo $percentage; ?>%
                    </span>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Class-wise Attendance -->
        <h3 class="section-title">📊 Class-wise Today's Attendance</h3>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 15px; margin-bottom: 20px;">
            <?php while($class = $classAttendance->fetch_assoc()): ?>
                <?php $total = max($class['total'], 1); ?>
                <div style="background: white; padding: 15px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
                    <h4><?php echo $class['class_name'] . ' - ' . $class['section']; ?></h4>
                    <div style="margin-top: 10px;">
                        <span style="color: #27ae60;">Present: <?php echo $class['present']; ?></span> |
                        <span style="color: #e74c3c;">Absent: <?php echo $class['absent']; ?></span> |
                        <span style="color: #7f8c8d;">Total: <?php echo $class['total']; ?></span>
                    </div>
                    <div style="width: 100%; background: #ecf0f1; border-radius: 5px; height: 20px; margin-top: 10px; overflow: hidden;">
                        <div style="width: <?php echo ($class['present'] / $total) * 100; ?>%; background: #27ae60; height: 100%;"></div>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
        
        <!-- Top Students -->
        <h3 class="section-title">🏆 Top Performing Students (Last 30 Days)</h3>
        <div style="background: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin-bottom: 20px;">
            <table class="table">
                <thead>
                    <tr>
                        <th>Rank</th>
                        <th>Student ID</th>
                        <th>Name</th>
                        <th>Present</th>
                        <th>Total</th>
                        <th>Percentage</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $rank = 1; while($student = $topStudents->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $rank++; ?></td>
                            <td><?php echo $student['student_id']; ?></td>
                            <td><?php echo $student['full_name']; ?></td>
                            <td><?php echo $student['present']; ?></td>
                            <td><?php echo $student['total']; ?></td>
                            <td>
                                <span style="color: <?php echo $student['percentage'] >= 90 ? '#27ae60' : ($student['percentage'] >= 75 ? '#f39c12' : '#e74c3c'); ?>">
                                    <?php echo $student['percentage']; ?>%
                                </span>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Recent Attendance -->
        <h3 class="section-title">🕐 Recent Attendance Activity</h3>
        <div style="background: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin-bottom: 20px;">
            <table class="table">
                <thead>
                    <tr>
                        <th>Student</th>
                        <th>Date</th>
                        <th>Status</th>
                        <th>Time</th>
                        <th>Remarks</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($att = $recentAttendance->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $att['full_name']; ?></td>
                            <td><?php echo date('d-m-Y', strtotime($att['date'])); ?></td>
                            <td>
                                <span class="status-badge <?php echo $att['status']; ?>" style="background: <?php echo $att['status']=='present'?'#d4edda':($att['status']=='absent'?'#f8d7da':($att['status']=='late'?'#fff3cd':'#d1ecf1')); ?>; padding: 4px 10px; border-radius: 12px; font-size: 12px;">
                                    <?php echo ucfirst($att['status']); ?>
                                </span>
                            </td>
                            <td><?php echo $att['check_in_time'] ?? '-'; ?></td>
                            <td><?php echo $att['remarks'] ?? '-'; ?></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Quick Actions -->
        <div class="quick-actions">
            <a href="mark_attendance.php" class="action-btn">
                <span class="emoji">✅</span>
                Mark Attendance
            </a>
            <a href="reports.php" class="action-btn">
                <span class="emoji">📊</span>
                View Reports
            </a>
            <a href="students.php" class="action-btn">
                <span class="emoji">👨‍🎓</span>
                Manage Students
            </a>
            <a href="bulk_attendance.php" class="action-btn">
                <span class="emoji">📥</span>
                Bulk Import
            </a>
        </div>
    </div>
</body>
</html>
