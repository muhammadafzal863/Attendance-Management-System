<?php
// management.php - Teacher and Student Attendance Management
require_once 'config.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

$active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'student';
$message = '';
$error = '';

// ==================== CREATE TABLES IF NOT EXISTS ====================

// Create teacher_attendance table if not exists
$conn->query("
    CREATE TABLE IF NOT EXISTS teacher_attendance (
        id INT PRIMARY KEY AUTO_INCREMENT,
        teacher_id INT NOT NULL,
        date DATE NOT NULL,
        status ENUM('present', 'absent', 'late', 'leave') NOT NULL,
        check_in_time TIME,
        check_out_time TIME,
        remarks TEXT,
        marked_by INT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (teacher_id) REFERENCES teachers(id) ON DELETE CASCADE,
        FOREIGN KEY (marked_by) REFERENCES users(id) ON DELETE SET NULL,
        UNIQUE KEY unique_teacher_attendance (teacher_id, date)
    )
");

// Create attendance_summary table if not exists (for students)
$conn->query("
    CREATE TABLE IF NOT EXISTS attendance_summary (
        id INT PRIMARY KEY AUTO_INCREMENT,
        student_id INT,
        month_year DATE,
        total_present INT DEFAULT 0,
        total_absent INT DEFAULT 0,
        total_late INT DEFAULT 0,
        total_leave INT DEFAULT 0,
        total_days INT DEFAULT 0,
        percentage DECIMAL(5,2) DEFAULT 0,
        FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
        UNIQUE KEY unique_summary (student_id, month_year)
    )
");

// ==================== STUDENT ATTENDANCE FUNCTIONS ====================

// Mark Student Attendance
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'mark_student_attendance') {
    $attendance_date = $_POST['attendance_date'];
    
    foreach ($_POST['attendance'] as $student_id => $status) {
        $student_id = (int)$student_id;
        $status = sanitize($status);
        $check_in_time = date('H:i:s');
        $marked_by = $_SESSION['user_id'];
        
        // Check if attendance already exists
        $check = $conn->query("SELECT id FROM attendance WHERE student_id=$student_id AND date='$attendance_date'");
        
        if ($check && $check->num_rows > 0) {
            $conn->query("UPDATE attendance SET status='$status', check_in_time='$check_in_time', marked_by=$marked_by WHERE student_id=$student_id AND date='$attendance_date'");
        } else {
            $conn->query("INSERT INTO attendance (student_id, date, status, check_in_time, marked_by) VALUES ($student_id, '$attendance_date', '$status', '$check_in_time', $marked_by)");
        }
    }
    
    $message = "✅ Student attendance marked successfully!";
}

// ==================== TEACHER ATTENDANCE FUNCTIONS ====================

// Mark Teacher Attendance
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'mark_teacher_attendance') {
    $date = $_POST['attendance_date'];
    
    foreach ($_POST['attendance'] as $teacher_id => $status) {
        $teacher_id = (int)$teacher_id;
        $status = sanitize($status);
        $check_in_time = date('H:i:s');
        $marked_by = $_SESSION['user_id'];
        
        // Check if attendance already exists
        $check = $conn->query("SELECT id FROM teacher_attendance WHERE teacher_id=$teacher_id AND date='$date'");
        
        if ($check && $check->num_rows > 0) {
            $conn->query("UPDATE teacher_attendance SET status='$status', check_in_time='$check_in_time', marked_by=$marked_by WHERE teacher_id=$teacher_id AND date='$date'");
        } else {
            $conn->query("INSERT INTO teacher_attendance (teacher_id, date, status, check_in_time, marked_by) VALUES ($teacher_id, '$date', '$status', '$check_in_time', $marked_by)");
        }
    }
    
    $message = "✅ Teacher attendance marked successfully!";
}

// ==================== GET DATA ====================

// Get Students
$students = $conn->query("SELECT * FROM students WHERE status='active' ORDER BY full_name");

// Get Teachers
$teachers = $conn->query("SELECT * FROM teachers WHERE status='active' ORDER BY full_name");

// Get Classes
$classes = $conn->query("SELECT * FROM classes ORDER BY class_name, section");

// Get today's date
$today = date('Y-m-d');

// Get student attendance for today
$student_attendance_today = [];
$result = $conn->query("SELECT student_id, status FROM attendance WHERE date='$today'");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $student_attendance_today[$row['student_id']] = $row['status'];
    }
}

// Get teacher attendance for today
$teacher_attendance_today = [];
$result = $conn->query("SELECT teacher_id, status FROM teacher_attendance WHERE date='$today'");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $teacher_attendance_today[$row['teacher_id']] = $row['status'];
    }
}

// Get statistics
$total_students = 0;
$result = $conn->query("SELECT COUNT(*) as count FROM students WHERE status='active'");
if ($result) {
    $total_students = $result->fetch_assoc()['count'];
}

$total_teachers = 0;
$result = $conn->query("SELECT COUNT(*) as count FROM teachers WHERE status='active'");
if ($result) {
    $total_teachers = $result->fetch_assoc()['count'];
}

$present_students_today = 0;
$result = $conn->query("SELECT COUNT(*) as count FROM attendance WHERE date='$today' AND status='present'");
if ($result) {
    $present_students_today = $result->fetch_assoc()['count'];
}

$present_teachers_today = 0;
$result = $conn->query("SELECT COUNT(*) as count FROM teacher_attendance WHERE date='$today' AND status='present'");
if ($result) {
    $present_teachers_today = $result->fetch_assoc()['count'];
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Attendance Management</title>
    <link rel="stylesheet" href="style.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f0f2f5;
        }
        
        .container {
            max-width: 1400px;
            margin: 30px auto;
            padding: 0 20px;
        }
        
        .page-header {
            background: white;
            padding: 25px 30px;
            border-radius: 12px;
            margin-bottom: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
        }
        
        .page-header h1 {
            color: #2c3e50;
            font-size: 28px;
        }
        
        .page-header .date-display {
            color: #7f8c8d;
            font-size: 16px;
        }
        
        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            transition: transform 0.3s, box-shadow 0.3s;
        }
        
        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 4px 20px rgba(0,0,0,0.12);
        }
        
        .stat-card .stat-icon {
            font-size: 2rem;
            margin-bottom: 8px;
        }
        
        .stat-card .stat-number {
            font-size: 2rem;
            font-weight: 700;
            color: #2c3e50;
        }
        
        .stat-card .stat-label {
            color: #7f8c8d;
            font-size: 0.9rem;
            margin-top: 4px;
        }
        
        .stat-card .stat-sub {
            font-size: 0.8rem;
            color: #95a5a6;
            margin-top: 5px;
        }
        
        /* Tabs */
        .tabs {
            display: flex;
            gap: 5px;
            background: white;
            padding: 8px;
            border-radius: 12px;
            margin-bottom: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
        }
        
        .tab-btn {
            flex: 1;
            padding: 12px 25px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            background: transparent;
            color: #7f8c8d;
        }
        
        .tab-btn:hover {
            background: #f0f2f5;
            color: #2c3e50;
        }
        
        .tab-btn.active {
            background: #3498db;
            color: white;
            box-shadow: 0 4px 15px rgba(52, 152, 219, 0.3);
        }
        
        .tab-content {
            display: none;
            animation: fadeIn 0.3s ease;
        }
        
        .tab-content.active {
            display: block;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        /* Attendance Cards */
        .attendance-section {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
        }
        
        .attendance-section .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #ecf0f1;
        }
        
        .attendance-section .section-header h2 {
            color: #2c3e50;
            font-size: 22px;
        }
        
        .attendance-section .section-header .subtitle {
            color: #7f8c8d;
            font-size: 14px;
        }
        
        .attendance-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 15px;
        }
        
        .attendance-item {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 10px;
            border-left: 4px solid #3498db;
            transition: all 0.3s;
        }
        
        .attendance-item:hover {
            transform: translateX(5px);
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
        }
        
        .attendance-item .item-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }
        
        .attendance-item .item-name {
            font-weight: 600;
            color: #2c3e50;
        }
        
        .attendance-item .item-id {
            font-size: 12px;
            color: #7f8c8d;
        }
        
        .attendance-item .item-details {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 10px;
        }
        
        .attendance-item .item-class {
            font-size: 13px;
            color: #7f8c8d;
        }
        
        .status-select {
            padding: 6px 12px;
            border-radius: 6px;
            border: 1px solid #ddd;
            font-size: 13px;
            cursor: pointer;
            background: white;
            transition: all 0.3s;
        }
        
        .status-select:hover {
            border-color: #3498db;
        }
        
        .status-select.present { background: #d4edda; border-color: #27ae60; color: #155724; }
        .status-select.absent { background: #f8d7da; border-color: #e74c3c; color: #721c24; }
        .status-select.late { background: #fff3cd; border-color: #f39c12; color: #856404; }
        .status-select.leave { background: #d1ecf1; border-color: #3498db; color: #0c5460; }
        
        .status-badge {
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
            display: inline-block;
        }
        
        .status-badge.present { background: #d4edda; color: #155724; }
        .status-badge.absent { background: #f8d7da; color: #721c24; }
        .status-badge.late { background: #fff3cd; color: #856404; }
        .status-badge.leave { background: #d1ecf1; color: #0c5460; }
        
        .form-actions {
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #ecf0f1;
            display: flex;
            gap: 15px;
            align-items: center;
            flex-wrap: wrap;
        }
        
        .btn {
            padding: 10px 25px;
            border: none;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn-primary { background: #3498db; color: white; }
        .btn-primary:hover { background: #2980b9; transform: translateY(-2px); box-shadow: 0 4px 15px rgba(52, 152, 219, 0.3); }
        
        .btn-success { background: #27ae60; color: white; }
        .btn-success:hover { background: #229954; transform: translateY(-2px); box-shadow: 0 4px 15px rgba(39, 174, 96, 0.3); }
        
        .btn-danger { background: #e74c3c; color: white; }
        .btn-danger:hover { background: #c0392b; transform: translateY(-2px); box-shadow: 0 4px 15px rgba(231, 76, 60, 0.3); }
        
        .btn-warning { background: #f39c12; color: white; }
        .btn-warning:hover { background: #e67e22; transform: translateY(-2px); box-shadow: 0 4px 15px rgba(243, 156, 18, 0.3); }
        
        .btn-outline { background: transparent; color: #3498db; border: 2px solid #3498db; }
        .btn-outline:hover { background: #3498db; color: white; }
        
        .btn-sm { padding: 6px 15px; font-size: 12px; }
        
        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border-left: 4px solid #27ae60;
        }
        
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border-left: 4px solid #e74c3c;
        }
        
        .alert-info {
            background: #d1ecf1;
            color: #0c5460;
            border-left: 4px solid #3498db;
        }
        
        .quick-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin: 15px 0;
        }
        
        .quick-actions .btn {
            font-size: 13px;
            padding: 8px 16px;
        }
        
        .filter-section {
            display: flex;
            gap: 15px;
            align-items: center;
            flex-wrap: wrap;
            margin-bottom: 20px;
        }
        
        .filter-section input[type="date"],
        .filter-section select {
            padding: 8px 15px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
        }
        
        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: #7f8c8d;
        }
        
        .empty-state .empty-icon {
            font-size: 4rem;
            margin-bottom: 15px;
        }
        
        .empty-state h3 {
            color: #2c3e50;
            margin-bottom: 10px;
        }
        
        @media (max-width: 768px) {
            .container {
                padding: 0 10px;
            }
            
            .page-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }
            
            .tabs {
                flex-direction: column;
            }
            
            .tab-btn {
                width: 100%;
                text-align: center;
            }
            
            .attendance-grid {
                grid-template-columns: 1fr;
            }
            
            .filter-section {
                flex-direction: column;
                align-items: stretch;
            }
            
            .stats-grid {
                grid-template-columns: 1fr 1fr;
            }
        }
    </style>
</head>
<body style="background-color:gray">
    <?php include 'navbar.php'; ?>
    
    <div class="container">
        <!-- Page Header -->
        <div class="page-header">
            <div>
                <h1>📋 Attendance Management</h1>
                <div style="color: #7f8c8d; margin-top: 5px;">
                    Manage student and teacher attendance
                </div>
            </div>
            <div class="date-display">
                📅 <?php echo date('l, d F Y'); ?>
                <span style="display: block; font-size: 14px; color: #95a5a6; margin-top: 3px;">
                    ⏰ <?php echo date('h:i A'); ?>
                </span>
            </div>
        </div>
        
        <!-- Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">👨‍🎓</div>
                <div class="stat-number"><?php echo $total_students; ?></div>
                <div class="stat-label">Total Students</div>
                <div class="stat-sub">✅ <?php echo $present_students_today; ?> present today</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">👨‍🏫</div>
                <div class="stat-number"><?php echo $total_teachers; ?></div>
                <div class="stat-label">Total Teachers</div>
                <div class="stat-sub">✅ <?php echo $present_teachers_today; ?> present today</div>
            </div>
            <div class="stat-card" style="border-left: 4px solid #27ae60;">
                <div class="stat-icon">✅</div>
                <div class="stat-number"><?php echo $present_students_today + $present_teachers_today; ?></div>
                <div class="stat-label">Total Present Today</div>
                <div class="stat-sub">Out of <?php echo $total_students + $total_teachers; ?> total</div>
            </div>
            <div class="stat-card" style="border-left: 4px solid #f39c12;">
                <div class="stat-icon">📊</div>
                <div class="stat-number">
                    <?php 
                    $total_all = max($total_students + $total_teachers, 1);
                    $present_all = $present_students_today + $present_teachers_today;
                    echo number_format(($present_all / $total_all) * 100, 1); ?>%
                </div>
                <div class="stat-label">Overall Attendance</div>
                <div class="stat-sub">Today's attendance rate</div>
            </div>
        </div>
        
        <!-- Messages -->
        <?php if($message): ?>
            <div class="alert alert-success"><?php echo $message; ?></div>
        <?php endif; ?>
        <?php if($error): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <!-- Tabs -->
        <div class="tabs">
            <button class="tab-btn <?php echo $active_tab == 'student' ? 'active' : ''; ?>" onclick="switchTab('student')">
                👨‍🎓 Student Attendance
            </button>
            <button class="tab-btn <?php echo $active_tab == 'teacher' ? 'active' : ''; ?>" onclick="switchTab('teacher')">
                👨‍🏫 Teacher Attendance
            </button>
            <button class="tab-btn <?php echo $active_tab == 'summary' ? 'active' : ''; ?>" onclick="switchTab('summary')">
                📊 Summary Report
            </button>
        </div>
        
        <!-- ==================== STUDENT ATTENDANCE TAB ==================== -->
        <div id="tab-student" class="tab-content <?php echo $active_tab == 'student' ? 'active' : ''; ?>">
            <div class="attendance-section">
                <div class="section-header">
                    <div>
                        <h2>👨‍🎓 Student Attendance</h2>
                        <div class="subtitle">Mark attendance for students</div>
                    </div>
                    <div style="display: flex; gap: 10px; align-items: center;">
                        <span style="color: #7f8c8d;">📅 <?php echo date('d-m-Y'); ?></span>
                        <span class="status-badge present"><?php echo $present_students_today; ?> Present</span>
                        <span class="status-badge absent"><?php echo $total_students - $present_students_today; ?> Absent</span>
                    </div>
                </div>
                
                <form method="POST">
                    <input type="hidden" name="action" value="mark_student_attendance">
                    <input type="hidden" name="attendance_date" value="<?php echo $today; ?>">
                    
                    <div class="filter-section">
                        <div>
                            <label style="font-weight: 600; margin-right: 10px;">Filter:</label>
                            <input type="text" id="studentSearch" placeholder="🔍 Search students..." onkeyup="filterStudents()" style="padding: 8px 15px; border: 1px solid #ddd; border-radius: 6px; width: 250px;">
                        </div>
                        <div>
                            <select id="studentClassFilter" onchange="filterStudents()" style="padding: 8px 15px; border: 1px solid #ddd; border-radius: 6px;">
                                <option value="">All Classes</option>
                                <?php 
                                $classes_list = $conn->query("SELECT DISTINCT class, section FROM students WHERE status='active' ORDER BY class, section");
                                if ($classes_list) {
                                    while($cls = $classes_list->fetch_assoc()): 
                                ?>
                                    <option value="<?php echo $cls['class'] . '|' . $cls['section']; ?>">
                                        <?php echo $cls['class'] . ' - ' . $cls['section']; ?>
                                    </option>
                                <?php 
                                    endwhile;
                                }
                                ?>
                            </select>
                        </div>
                        <div>
                            <select id="statusFilter" onchange="filterStudents()" style="padding: 8px 15px; border: 1px solid #ddd; border-radius: 6px;">
                                <option value="">All Status</option>
                                <option value="present">✅ Present</option>
                                <option value="absent">❌ Absent</option>
                                <option value="late">⏰ Late</option>
                                <option value="leave">📝 Leave</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="quick-actions">
                        <button type="button" class="btn btn-sm btn-success" onclick="selectAllStudents('present')">✅ All Present</button>
                        <button type="button" class="btn btn-sm btn-danger" onclick="selectAllStudents('absent')">❌ All Absent</button>
                        <button type="button" class="btn btn-sm btn-warning" onclick="selectAllStudents('late')">⏰ All Late</button>
                        <button type="button" class="btn btn-sm" style="background: #3498db; color: white;" onclick="selectAllStudents('leave')">📝 All Leave</button>
                    </div>
                    
                    <div class="attendance-grid" id="studentGrid">
                        <?php if($students && $students->num_rows > 0): ?>
                            <?php while($student = $students->fetch_assoc()): 
                                $status = isset($student_attendance_today[$student['id']]) ? $student_attendance_today[$student['id']] : '';
                            ?>
                                <div class="attendance-item" data-class="<?php echo $student['class'] . '|' . $student['section']; ?>" data-status="<?php echo $status; ?>">
                                    <div class="item-header">
                                        <div>
                                            <div class="item-name"><?php echo htmlspecialchars($student['full_name']); ?></div>
                                            <div class="item-id">ID: <?php echo htmlspecialchars($student['student_id']); ?></div>
                                        </div>
                                        <?php if($status): ?>
                                            <span class="status-badge <?php echo $status; ?>"><?php echo ucfirst($status); ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="item-details">
                                        <div class="item-class">
                                            <?php echo htmlspecialchars($student['class'] . ' - ' . $student['section']); ?> | 
                                            Roll: <?php echo $student['roll_number']; ?>
                                        </div>
                                        <select name="attendance[<?php echo $student['id']; ?>]" class="status-select <?php echo $status; ?>" onchange="updateStudentStatus(this)">
                                            <option value="present" <?php echo $status == 'present' ? 'selected' : ''; ?>>✅ Present</option>
                                            <option value="absent" <?php echo $status == 'absent' ? 'selected' : ''; ?>>❌ Absent</option>
                                            <option value="late" <?php echo $status == 'late' ? 'selected' : ''; ?>>⏰ Late</option>
                                            <option value="leave" <?php echo $status == 'leave' ? 'selected' : ''; ?>>📝 Leave</option>
                                        </select>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <div class="empty-state" style="grid-column: 1 / -1;">
                                <div class="empty-icon">📭</div>
                                <h3>No Students Found</h3>
                                <p>Please add students first.</p>
                                <a href="students.php" class="btn btn-primary" style="margin-top: 15px;">➕ Add Students</a>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">💾 Save Student Attendance</button>
                        <?php if(!empty($student_attendance_today)): ?>
                            <span style="color: #27ae60; font-size: 14px;">✅ Attendance already marked for today (will update)</span>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- ==================== TEACHER ATTENDANCE TAB ==================== -->
        <div id="tab-teacher" class="tab-content <?php echo $active_tab == 'teacher' ? 'active' : ''; ?>">
            <div class="attendance-section">
                <div class="section-header">
                    <div>
                        <h2>👨‍🏫 Teacher Attendance</h2>
                        <div class="subtitle">Mark attendance for teachers</div>
                    </div>
                    <div style="display: flex; gap: 10px; align-items: center;">
                        <span style="color: #7f8c8d;">📅 <?php echo date('d-m-Y'); ?></span>
                        <span class="status-badge present"><?php echo $present_teachers_today; ?> Present</span>
                        <span class="status-badge absent"><?php echo $total_teachers - $present_teachers_today; ?> Absent</span>
                    </div>
                </div>
                
                <form method="POST">
                    <input type="hidden" name="action" value="mark_teacher_attendance">
                    <input type="hidden" name="attendance_date" value="<?php echo $today; ?>">
                    
                    <div class="filter-section">
                        <div>
                            <label style="font-weight: 600; margin-right: 10px;">Filter:</label>
                            <input type="text" id="teacherSearch" placeholder="🔍 Search teachers..." onkeyup="filterTeachers()" style="padding: 8px 15px; border: 1px solid #ddd; border-radius: 6px; width: 250px;">
                        </div>
                        <div>
                            <select id="teacherStatusFilter" onchange="filterTeachers()" style="padding: 8px 15px; border: 1px solid #ddd; border-radius: 6px;">
                                <option value="">All Status</option>
                                <option value="present">✅ Present</option>
                                <option value="absent">❌ Absent</option>
                                <option value="late">⏰ Late</option>
                                <option value="leave">📝 Leave</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="quick-actions">
                        <button type="button" class="btn btn-sm btn-success" onclick="selectAllTeachers('present')">✅ All Present</button>
                        <button type="button" class="btn btn-sm btn-danger" onclick="selectAllTeachers('absent')">❌ All Absent</button>
                        <button type="button" class="btn btn-sm btn-warning" onclick="selectAllTeachers('late')">⏰ All Late</button>
                        <button type="button" class="btn btn-sm" style="background: #3498db; color: white;" onclick="selectAllTeachers('leave')">📝 All Leave</button>
                    </div>
                    
                    <div class="attendance-grid" id="teacherGrid">
                        <?php if($teachers && $teachers->num_rows > 0): ?>
                            <?php while($teacher = $teachers->fetch_assoc()): 
                                $status = isset($teacher_attendance_today[$teacher['id']]) ? $teacher_attendance_today[$teacher['id']] : '';
                            ?>
                                <div class="attendance-item" data-status="<?php echo $status; ?>">
                                    <div class="item-header">
                                        <div>
                                            <div class="item-name"><?php echo htmlspecialchars($teacher['full_name']); ?></div>
                                            <div class="item-id">ID: <?php echo htmlspecialchars($teacher['teacher_id']); ?></div>
                                        </div>
                                        <?php if($status): ?>
                                            <span class="status-badge <?php echo $status; ?>"><?php echo ucfirst($status); ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="item-details">
                                        <div class="item-class">
                                            <?php echo htmlspecialchars($teacher['specialization'] ?: 'General'); ?>
                                        </div>
                                        <select name="attendance[<?php echo $teacher['id']; ?>]" class="status-select <?php echo $status; ?>" onchange="updateTeacherStatus(this)">
                                            <option value="present" <?php echo $status == 'present' ? 'selected' : ''; ?>>✅ Present</option>
                                            <option value="absent" <?php echo $status == 'absent' ? 'selected' : ''; ?>>❌ Absent</option>
                                            <option value="late" <?php echo $status == 'late' ? 'selected' : ''; ?>>⏰ Late</option>
                                            <option value="leave" <?php echo $status == 'leave' ? 'selected' : ''; ?>>📝 Leave</option>
                                        </select>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <div class="empty-state" style="grid-column: 1 / -1;">
                                <div class="empty-icon">📭</div>
                                <h3>No Teachers Found</h3>
                                <p>Please add teachers first.</p>
                                <a href="teachers.php" class="btn btn-primary" style="margin-top: 15px;">➕ Add Teachers</a>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">💾 Save Teacher Attendance</button>
                        <?php if(!empty($teacher_attendance_today)): ?>
                            <span style="color: #27ae60; font-size: 14px;">✅ Attendance already marked for today (will update)</span>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- ==================== SUMMARY REPORT TAB ==================== -->
        <div id="tab-summary" class="tab-content <?php echo $active_tab == 'summary' ? 'active' : ''; ?>">
            <div class="attendance-section">
                <div class="section-header">
                    <div>
                        <h2>📊 Attendance Summary Report</h2>
                        <div class="subtitle">Today's attendance summary</div>
                    </div>
                </div>
                
                <!-- Student Summary -->
                <div style="margin-bottom: 30px;">
                    <h3 style="color: #2c3e50; margin-bottom: 15px;">👨‍🎓 Student Attendance Summary</h3>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 15px;">
                        <?php 
                        $student_stats = ['present' => 0, 'absent' => 0, 'late' => 0, 'leave' => 0, 'total' => 0];
                        $result = $conn->query("
                            SELECT 
                                COUNT(CASE WHEN status='present' THEN 1 END) as present,
                                COUNT(CASE WHEN status='absent' THEN 1 END) as absent,
                                COUNT(CASE WHEN status='late' THEN 1 END) as late,
                                COUNT(CASE WHEN status='leave' THEN 1 END) as `leave`,
                                COUNT(*) as total
                            FROM attendance WHERE date='$today'
                        ");
                        if ($result) {
                            $student_stats = $result->fetch_assoc();
                        }
                        ?>
                        <div style="background: #d4edda; padding: 15px; border-radius: 8px; text-align: center;">
                            <div style="font-size: 1.8rem; font-weight: bold; color: #155724;"><?php echo $student_stats['present'] ?? 0; ?></div>
                            <div style="color: #155724;">✅ Present</div>
                        </div>
                        <div style="background: #f8d7da; padding: 15px; border-radius: 8px; text-align: center;">
                            <div style="font-size: 1.8rem; font-weight: bold; color: #721c24;"><?php echo $student_stats['absent'] ?? 0; ?></div>
                            <div style="color: #721c24;">❌ Absent</div>
                        </div>
                        <div style="background: #fff3cd; padding: 15px; border-radius: 8px; text-align: center;">
                            <div style="font-size: 1.8rem; font-weight: bold; color: #856404;"><?php echo $student_stats['late'] ?? 0; ?></div>
                            <div style="color: #856404;">⏰ Late</div>
                        </div>
                        <div style="background: #d1ecf1; padding: 15px; border-radius: 8px; text-align: center;">
                            <div style="font-size: 1.8rem; font-weight: bold; color: #0c5460;"><?php echo $student_stats['leave'] ?? 0; ?></div>
                            <div style="color: #0c5460;">📝 Leave</div>
                        </div>
                        <div style="background: #e8f0fe; padding: 15px; border-radius: 8px; text-align: center;">
                            <div style="font-size: 1.8rem; font-weight: bold; color: #2c3e50;"><?php echo $student_stats['total'] ?? 0; ?></div>
                            <div style="color: #2c3e50;">📊 Total</div>
                        </div>
                    </div>
                </div>
                
                <!-- Teacher Summary -->
                <div>
                    <h3 style="color: #2c3e50; margin-bottom: 15px;">👨‍🏫 Teacher Attendance Summary</h3>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 15px;">
                        <?php 
                        $teacher_stats = ['present' => 0, 'absent' => 0, 'late' => 0, 'leave' => 0, 'total' => 0];
                        $result = $conn->query("
                            SELECT 
                                COUNT(CASE WHEN status='present' THEN 1 END) as present,
                                COUNT(CASE WHEN status='absent' THEN 1 END) as absent,
                                COUNT(CASE WHEN status='late' THEN 1 END) as late,
                                COUNT(CASE WHEN status='leave' THEN 1 END) as `leave`,
                                COUNT(*) as total
                            FROM teacher_attendance WHERE date='$today'
                        ");
                        if ($result) {
                            $teacher_stats = $result->fetch_assoc();
                        }
                        ?>
                        <div style="background: #d4edda; padding: 15px; border-radius: 8px; text-align: center;">
                            <div style="font-size: 1.8rem; font-weight: bold; color: #155724;"><?php echo $teacher_stats['present'] ?? 0; ?></div>
                            <div style="color: #155724;">✅ Present</div>
                        </div>
                        <div style="background: #f8d7da; padding: 15px; border-radius: 8px; text-align: center;">
                            <div style="font-size: 1.8rem; font-weight: bold; color: #721c24;"><?php echo $teacher_stats['absent'] ?? 0; ?></div>
                            <div style="color: #721c24;">❌ Absent</div>
                        </div>
                        <div style="background: #fff3cd; padding: 15px; border-radius: 8px; text-align: center;">
                            <div style="font-size: 1.8rem; font-weight: bold; color: #856404;"><?php echo $teacher_stats['late'] ?? 0; ?></div>
                            <div style="color: #856404;">⏰ Late</div>
                        </div>
                        <div style="background: #d1ecf1; padding: 15px; border-radius: 8px; text-align: center;">
                            <div style="font-size: 1.8rem; font-weight: bold; color: #0c5460;"><?php echo $teacher_stats['leave'] ?? 0; ?></div>
                            <div style="color: #0c5460;">📝 Leave</div>
                        </div>
                        <div style="background: #e8f0fe; padding: 15px; border-radius: 8px; text-align: center;">
                            <div style="font-size: 1.8rem; font-weight: bold; color: #2c3e50;"><?php echo $teacher_stats['total'] ?? 0; ?></div>
                            <div style="color: #2c3e50;">📊 Total</div>
                        </div>
                    </div>
                </div>
                
                <!-- Overall Stats -->
                <div style="margin-top: 30px; padding: 20px; background: #f8f9fa; border-radius: 10px;">
                    <h3 style="color: #2c3e50; margin-bottom: 15px;">📈 Overall Statistics</h3>
                    <?php 
                    $total_present = ($student_stats['present'] ?? 0) + ($teacher_stats['present'] ?? 0);
                    $total_absent = ($student_stats['absent'] ?? 0) + ($teacher_stats['absent'] ?? 0);
                    $total_late = ($student_stats['late'] ?? 0) + ($teacher_stats['late'] ?? 0);
                    $total_leave = ($student_stats['leave'] ?? 0) + ($teacher_stats['leave'] ?? 0);
                    $total_all = $total_present + $total_absent + $total_late + $total_leave;
                    $attendance_rate = $total_all > 0 ? ($total_present / $total_all) * 100 : 0;
                    ?>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(120px, 1fr)); gap: 15px;">
                        <div style="text-align: center;">
                            <div style="font-size: 1.5rem; font-weight: bold; color: #27ae60;"><?php echo $total_present; ?></div>
                            <div style="color: #7f8c8d; font-size: 0.9rem;">✅ Present</div>
                        </div>
                        <div style="text-align: center;">
                            <div style="font-size: 1.5rem; font-weight: bold; color: #e74c3c;"><?php echo $total_absent; ?></div>
                            <div style="color: #7f8c8d; font-size: 0.9rem;">❌ Absent</div>
                        </div>
                        <div style="text-align: center;">
                            <div style="font-size: 1.5rem; font-weight: bold; color: #f39c12;"><?php echo $total_late; ?></div>
                            <div style="color: #7f8c8d; font-size: 0.9rem;">⏰ Late</div>
                        </div>
                        <div style="text-align: center;">
                            <div style="font-size: 1.5rem; font-weight: bold; color: #3498db;"><?php echo $total_leave; ?></div>
                            <div style="color: #7f8c8d; font-size: 0.9rem;">📝 Leave</div>
                        </div>
                        <div style="text-align: center;">
                            <div style="font-size: 1.5rem; font-weight: bold; color: #2c3e50;"><?php echo $total_all; ?></div>
                            <div style="color: #7f8c8d; font-size: 0.9rem;">📊 Total</div>
                        </div>
                        <div style="text-align: center; grid-column: span 2;">
                            <div style="font-size: 2rem; font-weight: bold; color: <?php echo $attendance_rate >= 75 ? '#27ae60' : '#e74c3c'; ?>;">
                                <?php echo number_format($attendance_rate, 1); ?>%
                            </div>
                            <div style="color: #7f8c8d; font-size: 0.9rem;">📈 Attendance Rate</div>
                            <div style="width: 100%; height: 8px; background: #ecf0f1; border-radius: 4px; margin-top: 5px; overflow: hidden;">
                                <div style="width: <?php echo min($attendance_rate, 100); ?>%; height: 100%; background: <?php echo $attendance_rate >= 75 ? '#27ae60' : '#e74c3c'; ?>; border-radius: 4px;"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        // Tab switching
        function switchTab(tab) {
            // Update URL
            window.history.pushState({}, '', '?tab=' + tab);
            
            // Update tab buttons
            document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
            document.querySelectorAll('.tab-btn').forEach(btn => {
                if (btn.textContent.toLowerCase().includes(tab === 'student' ? 'student' : 
                    tab === 'teacher' ? 'teacher' : 'summary')) {
                    btn.classList.add('active');
                }
            });
            
            // Update tab content
            document.querySelectorAll('.tab-content').forEach(content => content.classList.remove('active'));
            document.getElementById('tab-' + tab).classList.add('active');
        }
        
        // Student search and filter
        function filterStudents() {
            const search = document.getElementById('studentSearch').value.toLowerCase();
            const classFilter = document.getElementById('studentClassFilter').value;
            const statusFilter = document.getElementById('statusFilter').value;
            
            document.querySelectorAll('#studentGrid .attendance-item').forEach(item => {
                const name = item.querySelector('.item-name').textContent.toLowerCase();
                const itemClass = item.dataset.class || '';
                const status = item.dataset.status || '';
                
                const matchesSearch = name.includes(search);
                const matchesClass = !classFilter || itemClass === classFilter;
                const matchesStatus = !statusFilter || status === statusFilter;
                
                item.style.display = (matchesSearch && matchesClass && matchesStatus) ? '' : 'none';
            });
        }
        
        // Teacher search and filter
        function filterTeachers() {
            const search = document.getElementById('teacherSearch').value.toLowerCase();
            const statusFilter = document.getElementById('teacherStatusFilter').value;
            
            document.querySelectorAll('#teacherGrid .attendance-item').forEach(item => {
                const name = item.querySelector('.item-name').textContent.toLowerCase();
                const status = item.dataset.status || '';
                
                const matchesSearch = name.includes(search);
                const matchesStatus = !statusFilter || status === statusFilter;
                
                item.style.display = (matchesSearch && matchesStatus) ? '' : 'none';
            });
        }
        
        // Select all students with a specific status
        function selectAllStudents(status) {
            document.querySelectorAll('#studentGrid select[name^="attendance["]').forEach(select => {
                select.value = status;
                select.className = 'status-select ' + status;
                // Update parent data-status
                const item = select.closest('.attendance-item');
                if (item) {
                    item.dataset.status = status;
                    // Update badge
                    const badge = item.querySelector('.status-badge');
                    if (badge) {
                        badge.textContent = status.charAt(0).toUpperCase() + status.slice(1);
                        badge.className = 'status-badge ' + status;
                    }
                }
            });
        }
        
        // Select all teachers with a specific status
        function selectAllTeachers(status) {
            document.querySelectorAll('#teacherGrid select[name^="attendance["]').forEach(select => {
                select.value = status;
                select.className = 'status-select ' + status;
                // Update parent data-status
                const item = select.closest('.attendance-item');
                if (item) {
                    item.dataset.status = status;
                    // Update badge
                    const badge = item.querySelector('.status-badge');
                    if (badge) {
                        badge.textContent = status.charAt(0).toUpperCase() + status.slice(1);
                        badge.className = 'status-badge ' + status;
                    }
                }
            });
        }
        
        // Update student status on change
        function updateStudentStatus(select) {
            const item = select.closest('.attendance-item');
            const status = select.value;
            
            // Update data-status
            if (item) {
                item.dataset.status = status;
                
                // Update badge
                const badge = item.querySelector('.status-badge');
                if (badge) {
                    badge.textContent = status.charAt(0).toUpperCase() + status.slice(1);
                    badge.className = 'status-badge ' + status;
                }
            }
            
            // Update select class
            select.className = 'status-select ' + status;
        }
        
        // Update teacher status on change
        function updateTeacherStatus(select) {
            const item = select.closest('.attendance-item');
            const status = select.value;
            
            if (item) {
                item.dataset.status = status;
                const badge = item.querySelector('.status-badge');
                if (badge) {
                    badge.textContent = status.charAt(0).toUpperCase() + status.slice(1);
                    badge.className = 'status-badge ' + status;
                }
            }
            
            select.className = 'status-select ' + status;
        }
    </script>
</body>
</html>