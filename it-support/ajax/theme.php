<?php
require_once __DIR__ . '/../config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'toggle') {
    $current = $_COOKIE['theme'] ?? 'light';
    $new = $current === 'dark' ? 'light' : 'dark';
    setcookie('theme', $new, time() + (86400 * 365), '/');
    echo json_encode(['theme' => $new]);
} else {
    echo json_encode(['theme' => getTheme()]);
}
