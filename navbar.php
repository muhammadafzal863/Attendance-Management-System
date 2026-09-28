<?php
// navbar.php - Complete Navigation with Student & Teacher Attendance Links
if (!isLoggedIn()) {
    redirect('login.php');
}
?>
<nav class="navbar">
    <div class="brand">📋 Attendance System</div>
    <div class="nav-links">
        <!-- Dashboard -->
        <a href="dashboard.php">📊 Dashboard</a>
        
        <!-- Attendance Dropdown -->
        <div class="dropdown">
            <a href="#" class="dropdown-toggle">📝 Attendance ▾</a>
            <div class="dropdown-menu">
                <a href="student_attendance.php">👨‍🎓 Student Attendance</a>
                <a href="teacher_attendance.php">👨‍🏫 Teacher Attendance</a>
                <a href="mark_attendance.php">📋 Mark Attendance</a>
                <a href="bulk_attendance.php">📥 Bulk Import</a>
                <a href="calendar.php">📅 Calendar View</a>
            </div>
        </div>
        
        <!-- Reports Dropdown -->
        <div class="dropdown">
            <a href="#" class="dropdown-toggle">📊 Reports ▾</a>
            <div class="dropdown-menu">
                <a href="reports.php">📈 All Reports</a>
                <a href="student_reports.php">👤 Student Report</a>
                <a href="management.php?tab=summary">📊 Summary</a>
            </div>
        </div>
        
        <!-- Management Dropdown -->
        <div class="dropdown">
            <a href="#" class="dropdown-toggle">⚙️ Management ▾</a>
            <div class="dropdown-menu">
                <a href="students.php">👨‍🎓 Students</a>
                <a href="teachers.php">👨‍🏫 Teachers</a>
                <a href="classes.php">📚 Classes</a>
                <a href="management.php">📋 All Management</a>
            </div>
        </div>
        
        <!-- Admin Settings -->
        <?php if(isAdmin()): ?>
            <a href="settings.php">⚙️ Settings</a>
        <?php endif; ?>
        
        <!-- User Profile & Logout -->
        <a href="profile.php">👤 <?php echo $_SESSION['full_name']; ?></a>
        <a href="logout.php" class="logout">🚪 Logout</a>
    </div>
</nav>

<style>
/* Navigation Styles */
.navbar {
    background: #2c3e50;
    color: white;
    padding: 1rem 2rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    box-shadow: 0 2px 10px rgba(0,0,0,0.2);
    position: sticky;
    top: 0;
    z-index: 1000;
}

.navbar .brand {
    font-size: 1.5rem;
    font-weight: bold;
    color: white;
    text-decoration: none;
}

.navbar .nav-links {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 5px;
}

.navbar .nav-links a {
    color: white;
    text-decoration: none;
    padding: 8px 15px;
    border-radius: 5px;
    transition: all 0.3s;
    font-size: 14px;
}

.navbar .nav-links a:hover {
    background: #34495e;
    transform: translateY(-2px);
}

.navbar .nav-links a.logout {
    color: #ff6b6b;
}

.navbar .nav-links a.logout:hover {
    background: #ff6b6b;
    color: white;
}

/* Dropdown Styles */
.dropdown {
    position: relative;
    display: inline-block;
}

.dropdown-toggle {
    cursor: pointer;
}

.dropdown-menu {
    display: none;
    position: absolute;
    background: white;
    min-width: 220px;
    box-shadow: 0 8px 16px rgba(0,0,0,0.2);
    border-radius: 8px;
    z-index: 1000;
    padding: 8px 0;
    top: 100%;
    left: 0;
}

.dropdown-menu a {
    color: #333 !important;
    padding: 10px 20px !important;
    display: block !important;
    text-align: left !important;
    font-size: 14px !important;
    border-radius: 0 !important;
}

.dropdown-menu a:hover {
    background: #f0f0f0 !important;
    transform: none !important;
}

.dropdown:hover .dropdown-menu {
    display: block;
}

/* Mobile Responsive */
@media (max-width: 768px) {
    .navbar {
        flex-direction: column;
        padding: 1rem;
    }
    
    .navbar .brand {
        margin-bottom: 10px;
    }
    
    .navbar .nav-links {
        flex-direction: column;
        width: 100%;
        gap: 5px;
    }
    
    .navbar .nav-links a {
        width: 100%;
        text-align: center;
        padding: 10px;
    }
    
    .dropdown {
        width: 100%;
    }
    
    .dropdown-menu {
        position: static;
        background: #34495e;
        box-shadow: none;
        padding-left: 20px;
        width: 100%;
    }
    
    .dropdown-menu a {
        color: #ddd !important;
        padding: 8px 20px !important;
    }
    
    .dropdown-menu a:hover {
        background: #2c3e50 !important;
        color: white !important;
    }
    
    .dropdown:hover .dropdown-menu {
        display: block;
    }
}

/* Mobile Menu Toggle */
.mobile-menu-toggle {
    display: none;
    background: none;
    border: none;
    color: white;
    font-size: 1.5rem;
    cursor: pointer;
}

@media (max-width: 768px) {
    .mobile-menu-toggle {
        display: block;
    }
    
    .nav-links {
        display: none !important;
    }
    
    .nav-links.active {
        display: flex !important;
    }
}
</style>

<script>
// Mobile menu toggle
document.addEventListener('DOMContentLoaded', function() {
    // Add mobile menu toggle button
    const navbar = document.querySelector('.navbar');
    const brand = navbar.querySelector('.brand');
    const navLinks = navbar.querySelector('.nav-links');
    
    // Create toggle button
    const toggleBtn = document.createElement('button');
    toggleBtn.className = 'mobile-menu-toggle';
    toggleBtn.innerHTML = '☰';
    toggleBtn.setAttribute('aria-label', 'Toggle menu');
    
    // Insert toggle button after brand
    brand.parentNode.insertBefore(toggleBtn, brand.nextSibling);
    
    // Toggle menu on click
    toggleBtn.addEventListener('click', function() {
        navLinks.classList.toggle('active');
        this.innerHTML = navLinks.classList.contains('active') ? '✕' : '☰';
    });
    
    // Close menu when clicking outside
    document.addEventListener('click', function(event) {
        if (!navbar.contains(event.target)) {
            navLinks.classList.remove('active');
            toggleBtn.innerHTML = '☰';
        }
    });
    
    // Close menu when clicking a link (mobile)
    navLinks.querySelectorAll('a').forEach(link => {
        link.addEventListener('click', function() {
            if (window.innerWidth <= 768) {
                navLinks.classList.remove('active');
                toggleBtn.innerHTML = '☰';
            }
        });
    });
});
</script>