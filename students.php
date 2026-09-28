<?php
// students.php
require_once 'config.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    
    if ($action == 'add') {
        $student_id = sanitize($_POST['student_id']);
        $full_name = sanitize($_POST['full_name']);
        $email = sanitize($_POST['email']);
        $phone = sanitize($_POST['phone']);
        $class = sanitize($_POST['class']);
        $section = sanitize($_POST['section']);
        $roll_number = (int)$_POST['roll_number'];
        $parent_contact = sanitize($_POST['parent_contact']);
        $address = sanitize($_POST['address']);
        
        $conn->query("INSERT INTO students (student_id, full_name, email, phone, class, section, roll_number, parent_contact, address) 
                     VALUES ('$student_id', '$full_name', '$email', '$phone', '$class', '$section', $roll_number, '$parent_contact', '$address')");
        
        $success = "Student added successfully!";
    } elseif ($action == 'delete' && isset($_POST['id'])) {
        $id = (int)$_POST['id'];
        $conn->query("UPDATE students SET status='inactive' WHERE id=$id");
        $success = "Student deleted successfully!";
    }
}

$students = $conn->query("SELECT * FROM students WHERE status='active' ORDER BY full_name");
?>
<!DOCTYPE html>
<html>
<head>
    <title>Student Management</title>
    <link rel="stylesheet" href="style.css">
</head>
<body style="background-color:gray">
    <?php include 'navbar.php'; ?>
    <div class="container">
        <h1>Student Management</h1>
        
        <?php if(isset($success)): ?>
            <div class="alert success"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <button onclick="showAddForm()" class="btn btn-success" style="margin-bottom: 20px;">Add New Student</button>
        
        <div id="addForm" style="display:none; background: white; padding: 20px; border-radius: 10px; margin-bottom: 20px;">
            <h3>Add New Student</h3>
            <form method="POST">
                <input type="hidden" name="action" value="add">
                <div class="form-group">
                    <label>Student ID</label>
                    <input type="text" name="student_id" required>
                </div>
                <div class="form-group">
                    <label>Full Name</label>
                    <input type="text" name="full_name" required>
                </div>
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email">
                </div>
                <div class="form-group">
                    <label>Phone</label>
                    <input type="text" name="phone">
                </div>
                <div class="form-group">
                    <label>Class</label>
                    <input type="text" name="class" required>
                </div>
                <div class="form-group">
                    <label>Section</label>
                    <input type="text" name="section">
                </div>
                <div class="form-group">
                    <label>Roll Number</label>
                    <input type="number" name="roll_number" required>
                </div>
                <div class="form-group">
                    <label>Parent Contact</label>
                    <input type="text" name="parent_contact">
                </div>
                <div class="form-group">
                    <label>Address</label>
                    <textarea name="address"></textarea>
                </div>
                <button type="submit" class="btn">Save Student</button>
                <button type="button" onclick="hideAddForm()" class="btn btn-danger">Cancel</button>
            </form>
        </div>
        
        <table class="table">
            <thead>
                <tr>
                    <th>Student ID</th>
                    <th>Name</th>
                    <th>Class</th>
                    <th>Section</th>
                    <th>Roll No</th>
                    <th>Phone</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody style="background-color:pink">
                <?php while($student = $students->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo $student['student_id']; ?></td>
                        <td><?php echo $student['full_name']; ?></td>
                        <td><?php echo $student['class']; ?></td>
                        <td><?php echo $student['section']; ?></td>
                        <td><?php echo $student['roll_number']; ?></td>
                        <td><?php echo $student['phone']; ?></td>
                        <td>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?php echo $student['id']; ?>">
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
        }
        
        function hideAddForm() {
            document.getElementById('addForm').style.display = 'none';
        }
    </script>
</body>
</html>