<?php
// teacher_attendance.php - Separate Teacher Attendance Management
require_once 'config.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

$message = '';
$error = '';

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

$today = date('Y-m-d');
$selected_date = isset($_GET['date']) ? $_GET['date'] : $today;

// Get all active teachers
$teachers = $conn->query("SELECT * FROM teachers WHERE status='active' ORDER BY full_name");

// Get today's attendance
$attendance_today = [];
$result = $conn->query("SELECT teacher_id, status, check_in_time, check_out_time, remarks FROM teacher_attendance WHERE date='$selected_date'");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $attendance_today[$row['teacher_id']] = $row;
    }
}

// Get statistics
$total_teachers = 0;
$result = $conn->query("SELECT COUNT(*) as count FROM teachers WHERE status='active'");
if ($result) {
    $total_teachers = $result->fetch_assoc()['count'];
}

$present_today = 0;
$result = $conn->query("SELECT COUNT(*) as count FROM teacher_attendance WHERE date='$selected_date' AND status='present'");
if ($result) {
    $present_today = $result->fetch_assoc()['count'];
}

$absent_today = 0;
$result = $conn->query("SELECT COUNT(*) as count FROM teacher_attendance WHERE date='$selected_date' AND status='absent'");
if ($result) {
    $absent_today = $result->fetch_assoc()['count'];
}

$late_today = 0;
$result = $conn->query("SELECT COUNT(*) as count FROM teacher_attendance WHERE date='$selected_date' AND status='late'");
if ($result) {
    $late_today = $result->fetch_assoc()['count'];
}

$leave_today = 0;
$result = $conn->query("SELECT COUNT(*) as count FROM teacher_attendance WHERE date='$selected_date' AND status='leave'");
if ($result) {
    $leave_today = $result->fetch_assoc()['count'];
}

// Mark Attendance
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'mark_attendance') {
    $attendance_date = $_POST['attendance_date'];
    
    foreach ($_POST['attendance'] as $teacher_id => $data) {
        $teacher_id = (int)$teacher_id;
        $status = sanitize($data['status']);
        $check_in_time = !empty($data['check_in_time']) ? $data['check_in_time'] : date('H:i:s');
        $check_out_time = !empty($data['check_out_time']) ? $data['check_out_time'] : null;
        $remarks = sanitize($data['remarks'] ?? '');
        $marked_by = $_SESSION['user_id'];
        
        // Check if attendance already exists
        $check = $conn->query("SELECT id FROM teacher_attendance WHERE teacher_id=$teacher_id AND date='$attendance_date'");
        
        if ($check && $check->num_rows > 0) {
            $conn->query("UPDATE teacher_attendance SET 
                status='$status', 
                check_in_time='$check_in_time', 
                check_out_time='$check_out_time', 
                remarks='$remarks', 
                marked_by=$marked_by 
                WHERE teacher_id=$teacher_id AND date='$attendance_date'");
        } else {
            $conn->query("INSERT INTO teacher_attendance 
                (teacher_id, date, status, check_in_time, check_out_time, remarks, marked_by) 
                VALUES ($teacher_id, '$attendance_date', '$status', '$check_in_time', '$check_out_time', '$remarks', $marked_by)");
        }
    }
    
    $message = "✅ Teacher attendance marked successfully!";
    
    // Refresh data
    $attendance_today = [];
    $result = $conn->query("SELECT teacher_id, status, check_in_time, check_out_time, remarks FROM teacher_attendance WHERE date='$selected_date'");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $attendance_today[$row['teacher_id']] = $row;
        }
    }
}

// Update individual attendance via AJAX
if (isset($_POST['ajax']) && $_POST['ajax'] == '1') {
    $teacher_id = (int)$_POST['teacher_id'];
    $status = sanitize($_POST['status']);
    $date = $_POST['date'];
    $check_in_time = $_POST['check_in_time'] ?? date('H:i:s');
    $marked_by = $_SESSION['user_id'];
    
    $check = $conn->query("SELECT id FROM teacher_attendance WHERE teacher_id=$teacher_id AND date='$date'");
    
    if ($check && $check->num_rows > 0) {
        $conn->query("UPDATE teacher_attendance SET status='$status', check_in_time='$check_in_time', marked_by=$marked_by WHERE teacher_id=$teacher_id AND date='$date'");
    } else {
        $conn->query("INSERT INTO teacher_attendance (teacher_id, date, status, check_in_time, marked_by) VALUES ($teacher_id, '$date', '$status', '$check_in_time', $marked_by)");
    }
    
    echo json_encode(['success' => true]);
    exit();
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Teacher Attendance</title>
    <link rel="stylesheet" href="style.css">
    <style>
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
        
        .page-header .subtitle {
            color: #7f8c8d;
            font-size: 14px;
            margin-top: 5px;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            text-align: center;
            transition: transform 0.3s;
        }
        
        .stat-card:hover {
            transform: translateY(-3px);
        }
        
        .stat-card .number {
            font-size: 2rem;
            font-weight: 700;
        }
        
        .stat-card .label {
            color: #7f8c8d;
            font-size: 0.9rem;
            margin-top: 5px;
        }
        
        .stat-card.total .number { color: #2c3e50; }
        .stat-card.present .number { color: #27ae60; }
        .stat-card.absent .number { color: #e74c3c; }
        .stat-card.late .number { color: #f39c12; }
        .stat-card.leave .number { color: #3498db; }
        
        .attendance-section {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
        }
        
        .filter-bar {
            display: flex;
            gap: 20px;
            align-items: end;
            flex-wrap: wrap;
            margin-bottom: 20px;
            padding-bottom: 20px;
            border-bottom: 2px solid #ecf0f1;
        }
        
        .filter-bar .form-group {
            margin-bottom: 0;
        }
        
        .filter-bar label {
            font-weight: 600;
            display: block;
            margin-bottom: 5px;
            color: #555;
        }
        
        .filter-bar input[type="date"],
        .filter-bar select {
            padding: 10px 15px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
            min-width: 200px;
        }
        
        .filter-bar input[type="text"] {
            padding: 10px 15px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
            min-width: 250px;
        }
        
        .quick-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin: 15px 0;
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
        .btn-primary:hover { background: #2980b9; transform: translateY(-2px); }
        
        .btn-success { background: #27ae60; color: white; }
        .btn-success:hover { background: #229954; transform: translateY(-2px); }
        
        .btn-danger { background: #e74c3c; color: white; }
        .btn-danger:hover { background: #c0392b; transform: translateY(-2px); }
        
        .btn-warning { background: #f39c12; color: white; }
        .btn-warning:hover { background: #e67e22; transform: translateY(-2px); }
        
        .btn-info { background: #3498db; color: white; }
        .btn-info:hover { background: #2980b9; transform: translateY(-2px); }
        
        .btn-sm { padding: 6px 15px; font-size: 12px; }
        
        .attendance-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 15px;
        }
        
        .attendance-item {
            background: #f8f9fa;
            padding: 18px;
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
            align-items: start;
            margin-bottom: 10px;
        }
        
        .attendance-item .item-name {
            font-weight: 600;
            color: #2c3e50;
            font-size: 16px;
        }
        
        .attendance-item .item-id {
            font-size: 12px;
            color: #7f8c8d;
        }
        
        .attendance-item .item-specialization {
            font-size: 13px;
            color: #7f8c8d;
            margin-top: 2px;
        }
        
        .attendance-item .item-details {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 10px;
        }
        
        .attendance-item .time-inputs {
            display: flex;
            gap: 10px;
            align-items: center;
        }
        
        .attendance-item .time-inputs input[type="time"] {
            padding: 5px 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 12px;
            width: 100px;
        }
        
        .attendance-item .remarks-input {
            margin-top: 10px;
        }
        
        .attendance-item .remarks-input input {
            width: 100%;
            padding: 6px 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 12px;
        }
        
        .status-select {
            padding: 6px 12px;
            border-radius: 6px;
            border: 1px solid #ddd;
            font-size: 13px;
            cursor: pointer;
            background: white;
            transition: all 0.3s;
            min-width: 120px;
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
            margin-top: 25px;
            padding-top: 20px;
            border-top: 2px solid #ecf0f1;
            display: flex;
            gap: 15px;
            align-items: center;
            flex-wrap: wrap;
        }
        
        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
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
        
        .empty-state {
            text-align: center;
            padding: 60px 20px;
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
            .filter-bar {
                flex-direction: column;
                align-items: stretch;
            }
            
            .filter-bar input,
            .filter-bar select {
                min-width: 100%;
            }
            
            .attendance-grid {
                grid-template-columns: 1fr;
            }
            
            .stats-grid {
                grid-template-columns: 1fr 1fr;
            }
            
            .attendance-item .item-details {
                flex-direction: column;
                align-items: stretch;
            }
            
            .attendance-item .time-inputs {
                flex-wrap: wrap;
            }
        }
        
        /* Print styles */
        @media print {
            .filter-bar,
            .quick-actions,
            .form-actions,
            .no-print {
                display: none !important;
            }
            
            .attendance-item {
                break-inside: avoid;
                border: 1px solid #ddd;
            }
        }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>
    
    <div class="container">
        <!-- Page Header -->
        <div class="page-header">
            <div>
                <h1>👨‍🏫 Teacher Attendance</h1>
                <div class="subtitle">Manage attendance for all teachers</div>
            </div>
            <div>
                <span style="color: #7f8c8d;">📅 <?php echo date('l, d F Y', strtotime($selected_date)); ?></span>
                <span style="display: block; font-size: 14px; color: #95a5a6; margin-top: 3px;">
                    ⏰ <?php echo date('h:i A'); ?>
                </span>
            </div>
        </div>
        
        <!-- Statistics -->
        <div class="stats-grid">
            <div class="stat-card total">
                <div class="number"><?php echo $total_teachers; ?></div>
                <div class="label">👨‍🏫 Total Teachers</div>
            </div>
            <div class="stat-card present">
                <div class="number"><?php echo $present_today; ?></div>
                <div class="label">✅ Present</div>
            </div>
            <div class="stat-card absent">
                <div class="number"><?php echo $absent_today; ?></div>
                <div class="label">❌ Absent</div>
            </div>
            <div class="stat-card late">
                <div class="number"><?php echo $late_today; ?></div>
                <div class="label">⏰ Late</div>
            </div>
            <div class="stat-card leave">
                <div class="number"><?php echo $leave_today; ?></div>
                <div class="label">📝 Leave</div>
            </div>
        </div>
        
        <!-- Messages -->
        <?php if($message): ?>
            <div class="alert alert-success"><?php echo $message; ?></div>
        <?php endif; ?>
        <?php if($error): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <!-- Attendance Section -->
        <div class="attendance-section">
            <form method="POST" id="attendanceForm">
                <input type="hidden" name="action" value="mark_attendance">
                <input type="hidden" name="attendance_date" value="<?php echo $selected_date; ?>">
                
                <!-- Filter Bar -->
                <div class="filter-bar">
                    <div class="form-group">
                        <label>📅 Select Date</label>
                        <input type="date" name="date" value="<?php echo $selected_date; ?>" onchange="this.form.action=''; this.form.method='GET'; this.form.submit();">
                    </div>
                    <div class="form-group">
                        <label>🔍 Search Teacher</label>
                        <input type="text" id="searchTeacher" placeholder="Search by name or ID..." onkeyup="filterTeachers()">
                    </div>
                    <div class="form-group">
                        <label>📊 Filter by Status</label>
                        <select id="filterStatus" onchange="filterTeachers()">
                            <option value="">All Status</option>
                            <option value="present">✅ Present</option>
                            <option value="absent">❌ Absent</option>
                            <option value="late">⏰ Late</option>
                            <option value="leave">📝 Leave</option>
                        </select>
                    </div>
                    <div class="form-group no-print">
                        <label>&nbsp;</label>
                        <button type="button" onclick="window.print()" class="btn btn-info">🖨️ Print</button>
                    </div>
                </div>
                
                <!-- Quick Actions -->
                <div class="quick-actions no-print">
                    <button type="button" class="btn btn-sm btn-success" onclick="selectAll('present')">✅ All Present</button>
                    <button type="button" class="btn btn-sm btn-danger" onclick="selectAll('absent')">❌ All Absent</button>
                    <button type="button" class="btn btn-sm btn-warning" onclick="selectAll('late')">⏰ All Late</button>
                    <button type="button" class="btn btn-sm btn-info" onclick="selectAll('leave')">📝 All Leave</button>
                    <button type="button" class="btn btn-sm" style="background: #9b59b6; color: white;" onclick="resetAll()">🔄 Reset</button>
                </div>
                
                <!-- Teacher Grid -->
                <div class="attendance-grid" id="teacherGrid">
                    <?php if($teachers && $teachers->num_rows > 0): ?>
                        <?php while($teacher = $teachers->fetch_assoc()): 
                            $attendance = isset($attendance_today[$teacher['id']]) ? $attendance_today[$teacher['id']] : null;
                            $status = $attendance ? $attendance['status'] : '';
                            $check_in = $attendance ? $attendance['check_in_time'] : '';
                            $check_out = $attendance ? $attendance['check_out_time'] : '';
                            $remarks = $attendance ? $attendance['remarks'] : '';
                        ?>
                            <div class="attendance-item" data-teacher-id="<?php echo $teacher['id']; ?>" data-status="<?php echo $status; ?>">
                                <div class="item-header">
                                    <div>
                                        <div class="item-name"><?php echo htmlspecialchars($teacher['full_name']); ?></div>
                                        <div class="item-id">ID: <?php echo htmlspecialchars($teacher['teacher_id']); ?></div>
                                        <div class="item-specialization">
                                            <?php echo htmlspecialchars($teacher['specialization'] ?: 'General'); ?>
                                        </div>
                                    </div>
                                    <?php if($status): ?>
                                        <span class="status-badge <?php echo $status; ?>"><?php echo ucfirst($status); ?></span>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="item-details">
                                    <select name="attendance[<?php echo $teacher['id']; ?>][status]" class="status-select <?php echo $status; ?>" onchange="updateStatus(this)">
                                        <option value="present" <?php echo $status == 'present' ? 'selected' : ''; ?>>✅ Present</option>
                                        <option value="absent" <?php echo $status == 'absent' ? 'selected' : ''; ?>>❌ Absent</option>
                                        <option value="late" <?php echo $status == 'late' ? 'selected' : ''; ?>>⏰ Late</option>
                                        <option value="leave" <?php echo $status == 'leave' ? 'selected' : ''; ?>>📝 Leave</option>
                                    </select>
                                    
                                    <div class="time-inputs">
                                        <div>
                                            <label style="font-size: 11px; color: #7f8c8d;">In</label>
                                            <input type="time" name="attendance[<?php echo $teacher['id']; ?>][check_in_time]" value="<?php echo $check_in ?: date('H:i'); ?>" class="no-print">
                                        </div>
                                        <div>
                                            <label style="font-size: 11px; color: #7f8c8d;">Out</label>
                                            <input type="time" name="attendance[<?php echo $teacher['id']; ?>][check_out_time]" value="<?php echo $check_out; ?>" class="no-print">
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="remarks-input no-print">
                                    <input type="text" name="attendance[<?php echo $teacher['id']; ?>][remarks]" placeholder="Add remarks..." value="<?php echo htmlspecialchars($remarks); ?>">
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
                
                <!-- Form Actions -->
                <div class="form-actions no-print">
                    <button type="submit" class="btn btn-primary">💾 Save All Attendance</button>
                    <?php if(!empty($attendance_today)): ?>
                        <span style="color: #27ae60; font-size: 14px;">✅ Attendance already marked for this date (will update)</span>
                    <?php endif; ?>
                    <span style="color: #7f8c8d; font-size: 14px; margin-left: auto;">
                        <?php echo $teachers ? $teachers->num_rows : 0; ?> teachers • 
                        <?php echo $present_today; ?> present • 
                        <?php echo $total_teachers - $present_today; ?> absent
                    </span>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        // Filter teachers by search and status
        function filterTeachers() {
            const search = document.getElementById('searchTeacher').value.toLowerCase();
            const statusFilter = document.getElementById('filterStatus').value;
            
            document.querySelectorAll('#teacherGrid .attendance-item').forEach(item => {
                const name = item.querySelector('.item-name').textContent.toLowerCase();
                const id = item.querySelector('.item-id').textContent.toLowerCase();
                const status = item.dataset.status || '';
                
                const matchesSearch = name.includes(search) || id.includes(search);
                const matchesStatus = !statusFilter || status === statusFilter;
                
                item.style.display = (matchesSearch && matchesStatus) ? '' : 'none';
            });
        }
        
        // Select all teachers with a specific status
        function selectAll(status) {
            document.querySelectorAll('#teacherGrid select.status-select').forEach(select => {
                select.value = status;
                select.className = 'status-select ' + status;
                
                // Update parent data-status
                const item = select.closest('.attendance-item');
                if (item) {
                    item.dataset.status = status;
                    
                    // Update badge
                    let badge = item.querySelector('.status-badge');
                    if (badge) {
                        badge.textContent = status.charAt(0).toUpperCase() + status.slice(1);
                        badge.className = 'status-badge ' + status;
                    } else {
                        // Create badge if doesn't exist
                        const header = item.querySelector('.item-header');
                        const newBadge = document.createElement('span');
                        newBadge.className = 'status-badge ' + status;
                        newBadge.textContent = status.charAt(0).toUpperCase() + status.slice(1);
                        header.appendChild(newBadge);
                    }
                }
            });
        }
        
        // Reset all selections
        function resetAll() {
            document.querySelectorAll('#teacherGrid select.status-select').forEach(select => {
                select.value = '';
                select.className = 'status-select';
                
                const item = select.closest('.attendance-item');
                if (item) {
                    item.dataset.status = '';
                    const badge = item.querySelector('.status-badge');
                    if (badge) {
                        badge.remove();
                    }
                }
            });
        }
        
        // Update status on change
        function updateStatus(select) {
            const item = select.closest('.attendance-item');
            const status = select.value;
            
            if (item) {
                item.dataset.status = status;
                
                let badge = item.querySelector('.status-badge');
                if (status) {
                    if (badge) {
                        badge.textContent = status.charAt(0).toUpperCase() + status.slice(1);
                        badge.className = 'status-badge ' + status;
                    } else {
                        const header = item.querySelector('.item-header');
                        const newBadge = document.createElement('span');
                        newBadge.className = 'status-badge ' + status;
                        newBadge.textContent = status.charAt(0).toUpperCase() + status.slice(1);
                        header.appendChild(newBadge);
                    }
                } else if (badge) {
                    badge.remove();
                }
            }
            
            select.className = 'status-select ' + status;
        }
        
        // Auto submit on date change
        document.querySelector('input[name="date"]')?.addEventListener('change', function() {
            this.closest('form').submit();
        });
    </script>
</body>
</html>
