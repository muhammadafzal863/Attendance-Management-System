<?php
// security.php - Security functions
function generateCSRFToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

function sanitizeInput($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

function rateLimit($key, $limit = 5, $time = 60) {
    if (!isset($_SESSION['rate_limit'][$key])) {
        $_SESSION['rate_limit'][$key] = ['count' => 1, 'time' => time()];
        return true;
    }
    
    $data = $_SESSION['rate_limit'][$key];
    if (time() - $data['time'] > $time) {
        $_SESSION['rate_limit'][$key] = ['count' => 1, 'time' => time()];
        return true;
    }
    
    if ($data['count'] >= $limit) {
        return false;
    }
    
    $_SESSION['rate_limit'][$key]['count']++;
    return true;
}

function logActivity($userId, $action, $details = '') {
    global $conn;
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    $details = sanitizeInput($details);
    
    $conn->query("INSERT INTO activity_logs (user_id, action, details, ip_address) 
                  VALUES ($userId, '$action', '$details', '$ip')");
}

function requireAdmin() {
    if (!isAdmin()) {
        redirect('dashboard.php');
    }
}
?>