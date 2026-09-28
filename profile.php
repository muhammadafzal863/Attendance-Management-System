<?php
// profile.php - User Profile Management
require_once 'config.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

$user_id = $_SESSION['user_id'];
$message = '';

// Get user details
$user = $conn->query("SELECT * FROM users WHERE id=$user_id")->fetch_assoc();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action == 'update_profile') {
        $full_name = sanitize($_POST['full_name']);
        $email = sanitize($_POST['email']);
        
        $conn->query("UPDATE users SET full_name='$full_name', email='$email' WHERE id=$user_id");
        $_SESSION['full_name'] = $full_name;
        $message = "<div class='alert success'>Profile updated successfully!</div>";
        $user = $conn->query("SELECT * FROM users WHERE id=$user_id")->fetch_assoc();
        
    } elseif ($action == 'change_password') {
        $current_password = md5($_POST['current_password']);
        $new_password = md5($_POST['new_password']);
        $confirm_password = md5($_POST['confirm_password']);
        
        // Verify current password
        $check = $conn->query("SELECT id FROM users WHERE id=$user_id AND password='$current_password'");
        
        if ($check->num_rows > 0) {
            if ($new_password == $confirm_password) {
                $conn->query("UPDATE users SET password='$new_password' WHERE id=$user_id");
                $message = "<div class='alert success'>Password changed successfully!</div>";
            } else {
                $message = "<div class='alert error'>New passwords do not match!</div>";
            }
        } else {
            $message = "<div class='alert error'>Current password is incorrect!</div>";
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>My Profile</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .profile-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
        }
        .profile-card {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .profile-card h3 {
            margin-bottom: 20px;
            color: #2c3e50;
            border-bottom: 2px solid #3498db;
            padding-bottom: 10px;
        }
        .profile-info {
            display: grid;
            grid-template-columns: 120px 1fr;
            gap: 10px;
            margin-bottom: 10px;
        }
        .profile-info .label {
            font-weight: bold;
            color: #555;
        }
        @media (max-width: 768px) {
            .profile-container {
                grid-template-columns: 1fr;
            }
        }
        body{

            background-color:#CBC3E3 ;
        }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>
    <div class="container">
        <h1>👤 My Profile</h1>
        
        <?php echo $message; ?>
        
        <div class="profile-container">
            <!-- Profile Information -->
            <div class="profile-card">
                <h3>Profile Information</h3>
                <div class="profile-info">
                    <span class="label">Username:</span>
                    <span><?php echo $user['username']; ?></span>
                </div>
                <div class="profile-info">
                    <span class="label">Full Name:</span>
                    <span><?php echo $user['full_name']; ?></span>
                </div>
                <div class="profile-info">
                    <span class="label">Email:</span>
                    <span><?php echo $user['email'] ?? 'Not set'; ?></span>
                </div>
                <div class="profile-info">
                    <span class="label">Role:</span>
                    <span style="text-transform: capitalize;"><?php echo $user['role']; ?></span>
                </div>
                <div class="profile-info">
                    <span class="label">Member Since:</span>
                    <span><?php echo date('d-m-Y', strtotime($user['created_at'])); ?></span>
                </div>
                
                <hr style="margin: 20px 0;">
                
                <form method="POST">
                    <input type="hidden" name="action" value="update_profile">
                    <div class="form-group">
                        <label>Full Name</label>
                        <input type="text" name="full_name" value="<?php echo $user['full_name']; ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" name="email" value="<?php echo $user['email']; ?>">
                    </div>
                    <button type="submit" class="btn">Update Profile</button>
                </form>
            </div>
            
            <!-- Change Password -->
            <div class="profile-card">
                <h3>Change Password</h3>
                <form method="POST">
                    <input type="hidden" name="action" value="change_password">
                    <div class="form-group">
                        <label>Current Password</label>
                        <input type="password" name="current_password" required>
                    </div>
                    <div class="form-group">
                        <label>New Password</label>
                        <input type="password" name="new_password" required>
                    </div>
                    <div class="form-group">
                        <label>Confirm New Password</label>
                        <input type="password" name="confirm_password" required>
                    </div>
                    <button type="submit" class="btn">Change Password</button>
                </form>
            </div>
        </div>
    </div>
</body>
</html>
