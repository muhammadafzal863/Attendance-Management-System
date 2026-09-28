<?php
// classes.php
require_once 'config.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    
    if ($action == 'add') {
        $class_name = sanitize($_POST['class_name']);
        $section = sanitize($_POST['section']);
        $teacher_id = (int)$_POST['teacher_id'];
        $room_number = sanitize($_POST['room_number']);
        $schedule = sanitize($_POST['schedule']);
        
        $conn->query("INSERT INTO classes (class_name, section, teacher_id, room_number, schedule) 
                     VALUES ('$class_name', '$section', $teacher_id, '$room_number', '$schedule')");
        
        $success = "Class added successfully!";
    } elseif ($action == 'delete' && isset($_POST['id'])) {
        $id = (int)$_POST['id'];
        $conn->query("DELETE FROM classes WHERE id=$id");
        $success = "Class deleted successfully!";
    } elseif ($action == 'update' && isset($_POST['id'])) {
        $id = (int)$_POST['id'];
        $class_name = sanitize($_POST['class_name']);
        $section = sanitize($_POST['section']);
        $teacher_id = (int)$_POST['teacher_id'];
        $room_number = sanitize($_POST['room_number']);
        $schedule = sanitize($_POST['schedule']);
        
        $conn->query("UPDATE classes SET class_name='$class_name', section='$section', teacher_id=$teacher_id, room_number='$room_number', schedule='$schedule' WHERE id=$id");
        $success = "Class updated successfully!";
    }
}

$classes = $conn->query("
    SELECT c.*, t.full_name as teacher_name 
    FROM classes c 
    LEFT JOIN teachers t ON c.teacher_id = t.id 
    ORDER BY c.class_name, c.section
");

$teachers = $conn->query("SELECT id, full_name FROM teachers WHERE status='active'");
?>
<!DOCTYPE html>
<html>
<head>
    <title>Class Management</title>
    <link rel="stylesheet" href="style.css">
</head>
<body style="background-color:gray">
    <?php include 'navbar.php'; ?>
    <div class="container">
        <h1>📚 Class Management</h1>
        
        <?php if(isset($success)): ?>
            <div class="alert success"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <button onclick="showAddForm()" class="btn btn-success" style="margin-bottom: 20px;">Add New Class</button>
        
        <!-- Add/Edit Form -->
        <div id="addForm" style="display:none; background: white; padding: 20px; border-radius: 10px; margin-bottom: 20px;">
            <h3 id="formTitle">Add New Class</h3>
            <form method="POST" id="classForm">
                <input type="hidden" name="action" value="add" id="formAction">
                <input type="hidden" name="id" id="classId" value="">
                
                <div class="form-group">
                    <label>Class Name</label>
                    <input type="text" name="class_name" id="class_name" required>
                </div>
                <div class="form-group">
                    <label>Section</label>
                    <input type="text" name="section" id="section">
                </div>
                <div class="form-group">
                    <label>Teacher</label>
                    <select name="teacher_id" id="teacher_id">
                        <option value="">Select Teacher</option>
                        <?php while($teacher = $teachers->fetch_assoc()): ?>
                            <option value="<?php echo $teacher['id']; ?>"><?php echo $teacher['full_name']; ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Room Number</label>
                    <input type="text" name="room_number" id="room_number">
                </div>
                <div class="form-group">
                    <label>Schedule</label>
                    <input type="text" name="schedule" id="schedule" placeholder="e.g., Mon-Fri 9:00-10:00">
                </div>
                <button type="submit" class="btn">Save Class</button>
                <button type="button" onclick="hideAddForm()" class="btn btn-danger">Cancel</button>
            </form>
        </div>
        
        <table class="table">
            <thead>
                <tr>
                    <th>Class Name</th>
                    <th>Section</th>
                    <th>Teacher</th>
                    <th>Room</th>
                    <th>Schedule</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while($class = $classes->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo $class['class_name']; ?></td>
                        <td><?php echo $class['section']; ?></td>
                        <td><?php echo $class['teacher_name'] ?? 'Not Assigned'; ?></td>
                        <td><?php echo $class['room_number']; ?></td>
                        <td><?php echo $class['schedule']; ?></td>
                        <td>
                            <button onclick="editClass(<?php echo htmlspecialchars(json_encode($class)); ?>)" class="btn">Edit</button>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?php echo $class['id']; ?>">
                                <button type="submit" class="btn btn-danger" onclick="return confirm('Are you sure?')">Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
    
    <script>
        function showAddForm() {
            document.getElementById('addForm').style.display = 'block';
            document.getElementById('formTitle').textContent = 'Add New Class';
            document.getElementById('formAction').value = 'add';
            document.getElementById('classId').value = '';
            document.getElementById('class_name').value = '';
            document.getElementById('section').value = '';
            document.getElementById('teacher_id').value = '';
            document.getElementById('room_number').value = '';
            document.getElementById('schedule').value = '';
        }
        
        function hideAddForm() {
            document.getElementById('addForm').style.display = 'none';
        }
        
        function editClass(classData) {
            showAddForm();
            document.getElementById('formTitle').textContent = 'Edit Class';
            document.getElementById('formAction').value = 'update';
            document.getElementById('classId').value = classData.id;
            document.getElementById('class_name').value = classData.class_name;
            document.getElementById('section').value = classData.section;
            document.getElementById('teacher_id').value = classData.teacher_id;
            document.getElementById('room_number').value = classData.room_number;
            document.getElementById('schedule').value = classData.schedule;
        }
    </script>
</body>
</html>