<?php
require_once __DIR__ . '/config.php';
session_unset();
session_destroy();
// Regenerate to prevent session fixation
session_start();
session_regenerate_id(true);
session_destroy();
header('Location: ' . BASE_URL . 'login.php');
exit;
