<?php
// reports.php
require_once 'config.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

$date_from = isset($_GET['from']) ? $_GET['from'] : date('Y-m-01');
$date_to = isset($_GET['to']) ? $_GET['to'] : date('Y-m-d');

// Fixed SQL - escaped 'leave' with backticks
$attendanceData = $conn->query("
    SELECT s.student_id, s.full_name, s.class, s.section, s.roll_number,
           COUNT(CASE WHEN a.status='present' THEN 1 END) as present,
           COUNT(CASE WHEN a.status='absent' THEN 1 END) as absent,
           COUNT(CASE WHEN a.status='late' THEN 1 END) as late,
           COUNT(CASE WHEN a.status='leave' THEN 1 END) as `leave`,
           COUNT(*) as total
    FROM students s
    LEFT JOIN attendance a ON s.id = a.student_id AND a.date BETWEEN '$date_from' AND '$date_to'
    WHERE s.status='active'
    GROUP BY s.id
    ORDER BY s.full_name
");

// Check if query failed
if (!$attendanceData) {
    $error = "Error loading reports: " . $conn->error;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Attendance Reports</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .report-container {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow-x: auto;
        }
        .filter-section {
            background: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            display: flex;
            gap: 20px;
            align-items: end;
            flex-wrap: wrap;
        }
        .filter-section .form-group {
            margin-bottom: 0;
        }
        .filter-section label {
            font-weight: 600;
            display: block;
            margin-bottom: 5px;
            color: #555;
        }
        .filter-section input[type="date"] {
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
            min-width: 180px;
        }
        .filter-section input[type="date"]:focus {
            border-color: #3498db;
            outline: none;
        }
        .filter-section .btn {
            padding: 10px 25px;
            height: 42px;
        }
        .table th {
            background: #34495e;
            color: white;
            padding: 12px 15px;
            white-space: nowrap;
        }
        .table td {
            padding: 12px 15px;
            vertical-align: middle;
        }
        .table tr:nth-child(even) {
            background: #f8f9fa;
        }
        .table tr:hover {
            background: #e8f0fe;
        }
        .percentage-bar {
            width: 100%;
            height: 20px;
            background: #ecf0f1;
            border-radius: 10px;
            overflow: hidden;
            min-width: 100px;
        }
        .percentage-bar .fill {
            height: 100%;
            border-radius: 10px;
            transition: width 0.5s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 11px;
            color: white;
            font-weight: bold;
        }
        .percentage-bar .fill.good { background: #27ae60; }
        .percentage-bar .fill.average { background: #f39c12; }
        .percentage-bar .fill.poor { background: #e74c3c; }
        
        .status-badge {
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: bold;
            display: inline-block;
        }
        .status-badge.present { background: #d4edda; color: #155724; }
        .status-badge.absent { background: #f8d7da; color: #721c24; }
        .status-badge.late { background: #fff3cd; color: #856404; }
        .status-badge.leave { background: #d1ecf1; color: #0c5460; }
        
        .summary-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin-bottom: 25px;
        }
        .summary-stat {
            background: white;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .summary-stat .number {
            font-size: 2rem;
            font-weight: bold;
            margin-bottom: 5px;
        }
        .summary-stat .label {
            color: #7f8c8d;
            font-size: 0.9rem;
        }
        .summary-stat.total .number { color: #2c3e50; }
        .summary-stat.present .number { color: #27ae60; }
        .summary-stat.absent .number { color: #e74c3c; }
        .summary-stat.late .number { color: #f39c12; }
        .summary-stat.leave .number { color: #3498db; }
        
        .export-buttons {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-bottom: 20px;
        }
        .btn-export {
            padding: 8px 20px;
            font-size: 14px;
        }
        .btn-export.excel { background: #27ae60; }
        .btn-export.pdf { background: #e74c3c; }
        .btn-export.csv { background: #3498db; }
        
        @media (max-width: 768px) {
            .filter-section {
                flex-direction: column;
                align-items: stretch;
            }
            .filter-section input[type="date"] {
                min-width: 100%;
            }
            .summary-stats {
                grid-template-columns: 1fr 1fr;
            }
        }
    </style>
</head>
<body style="background-color:#CBC3E3">
    <?php include 'navbar.php'; ?>
    <div class="container">
        <h1>📊 Attendance Reports</h1>
        
        <?php if(isset($error)): ?>
            <div class="alert error">❌ <?php echo $error; ?></div>
        <?php endif; ?>
        
        <!-- Filter Section -->
        <div class="filter-section">
            <form method="GET" style="display: flex; gap: 20px; align-items: end; flex-wrap: wrap; width: 100%;">
                <div class="form-group">
                    <label>📅 From Date</label>
                    <input type="date" name="from" value="<?php echo $date_from; ?>" required>
                </div>
                <div class="form-group">
                    <label>📅 To Date</label>
                    <input type="date" name="to" value="<?php echo $date_to; ?>" required>
                </div>
                <button type="submit" class="btn">🔍 Generate Report</button>
                <button type="button" onclick="window.location.href='?from=<?php echo date('Y-m-01'); ?>&to=<?php echo date('Y-m-d'); ?>'" class="btn" style="background: #95a5a6;">🔄 Reset</button>
            </form>
        </div>
        
        <?php if($attendanceData && $attendanceData->num_rows > 0): 
            // Calculate summary statistics
            $totals = ['present' => 0, 'absent' => 0, 'late' => 0, 'leave' => 0, 'total' => 0];
            $attendanceData->data_seek(0);
            while($row = $attendanceData->fetch_assoc()) {
                $totals['present'] += $row['present'];
                $totals['absent'] += $row['absent'];
                $totals['late'] += $row['late'];
                $totals['leave'] += $row['leave'];
                $totals['total'] += $row['total'];
            }
            $attendanceData->data_seek(0);
        ?>
            <!-- Summary Statistics -->
            <div class="summary-stats">
                <div class="summary-stat total">
                    <div class="number"><?php echo $totals['total']; ?></div>
                    <div class="label">📊 Total Records</div>
                </div>
                <div class="summary-stat present">
                    <div class="number"><?php echo $totals['present']; ?></div>
                    <div class="label">✅ Present</div>
                </div>
                <div class="summary-stat absent">
                    <div class="number"><?php echo $totals['absent']; ?></div>
                    <div class="label">❌ Absent</div>
                </div>
                <div class="summary-stat late">
                    <div class="number"><?php echo $totals['late']; ?></div>
                    <div class="label">⏰ Late</div>
                </div>
                <div class="summary-stat leave">
                    <div class="number"><?php echo $totals['leave']; ?></div>
                    <div class="label">📝 Leave</div>
                </div>
            </div>
            
            <!-- Export Buttons -->
            <div class="export-buttons">
                <button onclick="exportReport('csv')" class="btn btn-export csv">📥 Export CSV</button>
                <button onclick="window.print()" class="btn btn-export pdf">🖨️ Print Report</button>
            </div>
            
            <!-- Report Table -->
            <div class="report-container">
                <table class="table" id="reportTable">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Student ID</th>
                            <th>Student Name</th>
                            <th>Class</th>
                            <th>Roll No</th>
                            <th>✅ Present</th>
                            <th>❌ Absent</th>
                            <th>⏰ Late</th>
                            <th>📝 Leave</th>
                            <th>📊 Total</th>
                            <th>📈 Percentage</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $counter = 1; while($row = $attendanceData->fetch_assoc()): 
                            $total = max($row['total'], 1);
                            $percentage = ($row['present'] / $total) * 100;
                            $barClass = $percentage >= 75 ? 'good' : ($percentage >= 50 ? 'average' : 'poor');
                        ?>
                            <tr>
                                <td><?php echo $counter++; ?></td>
                                <td><strong><?php echo $row['student_id']; ?></strong></td>
                                <td><?php echo $row['full_name']; ?></td>
                                <td><?php echo $row['class'] . ' - ' . $row['section']; ?></td>
                                <td><?php echo $row['roll_number']; ?></td>
                                <td><span class="status-badge present"><?php echo $row['present']; ?></span></td>
                                <td><span class="status-badge absent"><?php echo $row['absent']; ?></span></td>
                                <td><span class="status-badge late"><?php echo $row['late']; ?></span></td>
                                <td><span class="status-badge leave"><?php echo $row['leave']; ?></span></td>
                                <td><?php echo $row['total']; ?></td>
                                <td>
                                    <div style="display: flex; align-items: center; gap: 10px;">
                                        <div class="percentage-bar">
                                            <div class="fill <?php echo $barClass; ?>" style="width: <?php echo min($percentage, 100); ?>%">
                                                <?php echo number_format($percentage, 1); ?>%
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            
        <?php elseif($attendanceData && $attendanceData->num_rows == 0): ?>
            <div class="alert error">❌ No attendance records found for the selected date range.</div>
        <?php elseif(!$attendanceData): ?>
            <div class="alert error">❌ Error loading report data.</div>
        <?php endif; ?>
    </div>
    
    <script>
        function exportReport(format) {
            if (format === 'csv') {
                // Get table data
                const table = document.getElementById('reportTable');
                let csv = [];
                
                // Get headers
                const headers = [];
                const ths = table.querySelectorAll('thead th');
                ths.forEach(th => {
                    // Clean the header text (remove emojis)
                    let text = th.textContent.trim();
                    text = text.replace(/[^\w\s]/g, '').trim();
                    headers.push(text);
                });
                csv.push(headers.join(','));
                
                // Get rows
                const rows = table.querySelectorAll('tbody tr');
                rows.forEach(row => {
                    const rowData = [];
                    const cells = row.querySelectorAll('td');
                    cells.forEach((cell, index) => {
                        let text = cell.textContent.trim();
                        // For percentage column, get the number
                        if (index === 10) {
                            const match = text.match(/(\d+\.?\d*)%/);
                            text = match ? match[1] : '0';
                        }
                        rowData.push('"' + text + '"');
                    });
                    csv.push(rowData.join(','));
                });
                
                // Download CSV
                const csvContent = csv.join('\n');
                const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
                const link = document.createElement('a');
                const url = URL.createObjectURL(blob);
                link.setAttribute('href', url);
                link.setAttribute('download', 'attendance_report_<?php echo date('Y-m-d'); ?>.csv');
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
            }
        }
    </script>
</body>
</html>