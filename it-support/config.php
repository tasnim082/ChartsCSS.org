<?php
/**
 * IT Support Management System
 * Database Configuration
 */

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'it_support');
define('DB_CHARSET', 'utf8mb4');

define('APP_NAME', 'IT Support Management System');
define('APP_VERSION', '1.0.0');
define('BASE_URL', '/it-support/');

// Session configuration
ini_set('session.cookie_httponly', 1);
ini_set('session.use_strict_mode', 1);
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Timezone
date_default_timezone_set('Asia/Dhaka');

// Error reporting (disable in production)
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/logs/error.log');

// Database connection
function getDB() {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            die(json_encode(['error' => 'Database connection failed: ' . $e->getMessage()]));
        }
    }
    return $pdo;
}

// Auth helpers
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: ' . BASE_URL . 'login.php');
        exit;
    }
}

function requireAdmin() {
    requireLogin();
    if ($_SESSION['user_role'] !== 'admin') {
        header('Location: ' . BASE_URL . 'index.php?error=unauthorized');
        exit;
    }
}

function currentUser() {
    return [
        'id'       => $_SESSION['user_id'] ?? null,
        'username' => $_SESSION['username'] ?? null,
        'name'     => $_SESSION['user_name'] ?? null,
        'role'     => $_SESSION['user_role'] ?? null,
    ];
}

function isAdmin() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

function canEditCategory($category) {
    if (isAdmin()) return true;
    $roleMap = [
        'PC'       => 'pc',
        'UPS'      => 'ups',
        'CCTV'     => 'cctv',
        'Printer'  => 'printer',
        'IP Phone' => 'ipphone',
        'Hardware' => 'hardware',
        'Others'   => null,
    ];
    return isset($roleMap[$category]) && $_SESSION['user_role'] === $roleMap[$category];
}

// CSRF helpers
function generateCSRF() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCSRF($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// Sanitize input
function sanitize($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

// Theme helper
function getTheme() {
    return $_COOKIE['theme'] ?? 'light';
}

// Audit log writer
function writeAuditLog($issueId, $action, $fieldChanged, $oldValue, $newValue, $userId, $userName) {
    $db = getDB();
    $stmt = $db->prepare("INSERT INTO audit_logs (issue_id, action, field_changed, old_value, new_value, updated_by, updated_by_name) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$issueId, $action, $fieldChanged, $oldValue, $newValue, $userId, $userName]);
}

// Categories list
function getCategories() {
    return ['PC', 'UPS', 'CCTV', 'Printer', 'IP Phone', 'Hardware', 'Others'];
}

// Category color map
function getCategoryColor($category) {
    $colors = [
        'PC'       => '#4e73df',
        'UPS'      => '#1cc88a',
        'CCTV'     => '#36b9cc',
        'Printer'  => '#f6c23e',
        'IP Phone' => '#e74a3b',
        'Hardware' => '#6f42c1',
        'Others'   => '#858796',
    ];
    return $colors[$category] ?? '#858796';
}

// Status badge class
function getStatusBadge($status) {
    $classes = [
        'Open'        => 'bg-danger',
        'In Progress' => 'bg-warning text-dark',
        'Resolved'    => 'bg-success',
        'Closed'      => 'bg-secondary',
    ];
    return $classes[$status] ?? 'bg-secondary';
}
