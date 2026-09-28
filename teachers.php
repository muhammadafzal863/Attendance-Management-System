<?php
// teachers.php
require_once 'config.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    
    if ($action == 'add') {
        $teacher_id = sanitize($_POST['teacher_id']);
        $full_name = sanitize($_POST['full_name']);
        $email = sanitize($_POST['email']);
        $phone = sanitize($_POST['phone']);
        $specialization = sanitize($_POST['specialization']);
        
        $conn->query("INSERT INTO teachers (teacher_id, full_name, email, phone, specialization) 
                     VALUES ('$teacher_id', '$full_name', '$email', '$phone', '$specialization')");
        
        $success = "Teacher added successfully!";
    } elseif ($action == 'delete' && isset($_POST['id'])) {
        $id = (int)$_POST['id'];
        $conn->query("UPDATE teachers SET status='inactive' WHERE id=$id");
        $success = "Teacher deleted successfully!";
    } elseif ($action == 'update' && isset($_POST['id'])) {
        $id = (int)$_POST['id'];
        $full_name = sanitize($_POST['full_name']);
        $email = sanitize($_POST['email']);
        $phone = sanitize($_POST['phone']);
        $specialization = sanitize($_POST['specialization']);
        
        $conn->query("UPDATE teachers SET full_name='$full_name', email='$email', phone='$phone', specialization='$specialization' WHERE id=$id");
        $success = "Teacher updated successfully!";
    }
}

$teachers = $conn->query("SELECT * FROM teachers WHERE status='active' ORDER BY full_name");
?>
<!DOCTYPE html>
<html>
<head>
    <title>Teacher Management</title>
    <link rel="stylesheet" href="style.css">
</head>
<body style="background-color:gray">
    <?php include 'navbar.php'; ?>
    <div class="container">
        <h1>👨‍🏫 Teacher Management</h1>
        
        <?php if(isset($success)): ?>
            <div class="alert success"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <button onclick="showAddForm()" class="btn btn-success" style="margin-bottom: 20px;">Add New Teacher</button>
        
        <!-- Add/Edit Form -->
        <div id="addForm" style="display:none; background: white; padding: 20px; border-radius: 10px; margin-bottom: 20px;">
            <h3 id="formTitle">Add New Teacher</h3>
            <form method="POST" id="teacherForm">
                <input type="hidden" name="action" value="add" id="formAction">
                <input type="hidden" name="id" id="teacherId" value="">
                
                <div class="form-group">
                    <label>Teacher ID</label>
                    <input type="text" name="teacher_id" id="teacher_id" required>
                </div>
                <div class="form-group">
                    <label>Full Name</label>
                    <input type="text" name="full_name" id="full_name" required>
                </div>
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" id="email">
                </div>
                <div class="form-group">
                    <label>Phone</label>
                    <input type="text" name="phone" id="phone">
                </div>
                <div class="form-group">
                    <label>Specialization</label>
                    <input type="text" name="specialization" id="specialization">
                </div>
                <button type="submit" class="btn">Save Teacher</button>
                <button type="button" onclick="hideAddForm()" class="btn btn-danger">Cancel</button>
            </form>
        </div>
        
        <table class="table">
            <thead>
                <tr>
                    <th>Teacher ID</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Specialization</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while($teacher = $teachers->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo $teacher['teacher_id']; ?></td>
                        <td><?php echo $teacher['full_name']; ?></td>
                        <td><?php echo $teacher['email']; ?></td>
                        <td><?php echo $teacher['phone']; ?></td>
                        <td><?php echo $teacher['specialization']; ?></td>
                        <td>
                            <button onclick="editTeacher(<?php echo htmlspecialchars(json_encode($teacher)); ?>)" class="btn">Edit</button>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?php echo $teacher['id']; ?>">
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
            document.getElementById('formTitle').textContent = 'Add New Teacher';
            document.getElementById('formAction').value = 'add';
            document.getElementById('teacherId').value = '';
            document.getElementById('teacher_id').value = '';
            document.getElementById('full_name').value = '';
            document.getElementById('email').value = '';
            document.getElementById('phone').value = '';
            document.getElementById('specialization').value = '';
        }
        
        function hideAddForm() {
            document.getElementById('addForm').style.display = 'none';
        }
        
        function editTeacher(teacher) {
            showAddForm();
            document.getElementById('formTitle').textContent = 'Edit Teacher';
            document.getElementById('formAction').value = 'update';
            document.getElementById('teacherId').value = teacher.id;
            document.getElementById('teacher_id').value = teacher.teacher_id;
            document.getElementById('full_name').value = teacher.full_name;
            document.getElementById('email').value = teacher.email;
            document.getElementById('phone').value = teacher.phone;
            document.getElementById('specialization').value = teacher.specialization;
        }
    </script>
</body>
</html>