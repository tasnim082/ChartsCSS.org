<?php
require_once __DIR__ . '/config.php';

if (isLoggedIn()) {
    header('Location: ' . BASE_URL . 'index.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRF($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid request. Please try again.';
    } else {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($username) || empty($password)) {
            $error = 'Please enter both username and password.';
        } else {
            $db = getDB();
            $stmt = $db->prepare("SELECT * FROM users WHERE username = ? AND is_active = 1 LIMIT 1");
            $stmt->execute([$username]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                session_regenerate_id(true);
                $_SESSION['user_id']   = $user['id'];
                $_SESSION['username']  = $user['username'];
                $_SESSION['user_name'] = $user['full_name'];
                $_SESSION['user_role'] = $user['role'];
                header('Location: ' . BASE_URL . 'index.php');
                exit;
            } else {
                $error = 'Invalid username or password.';
            }
        }
    }
}

$csrf = generateCSRF();
$theme = getTheme();
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="<?= $theme ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?= APP_NAME ?></title>
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/vendor/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/vendor/bootstrap/css/bootstrap-icons.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/app.css">
</head>
<body>
<div class="login-wrapper">
    <div class="login-card card shadow-lg">
        <div class="login-header">
            <i class="bi bi-pc-display-horizontal fs-1 mb-2 d-block"></i>
            <h5 class="fw-bold mb-0"><?= APP_NAME ?></h5>
            <small class="opacity-75">Hardware Management System</small>
        </div>
        <div class="card-body p-4">
            <?php if ($error): ?>
            <div class="alert alert-danger alert-sm py-2 px-3 mb-3">
                <i class="bi bi-exclamation-circle me-1"></i><?= sanitize($error) ?>
            </div>
            <?php endif; ?>
            <form method="POST" autocomplete="off">
                <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                <div class="mb-3">
                    <label class="form-label">
                        <i class="bi bi-person me-1"></i>Username
                    </label>
                    <input type="text" name="username" class="form-control"
                           value="<?= sanitize($_POST['username'] ?? '') ?>"
                           placeholder="Enter username" autofocus required>
                </div>
                <div class="mb-3">
                    <label class="form-label">
                        <i class="bi bi-lock me-1"></i>Password
                    </label>
                    <div class="input-group">
                        <input type="password" name="password" id="passwordField"
                               class="form-control" placeholder="Enter password" required>
                        <button class="btn btn-outline-secondary" type="button" id="togglePwd">
                            <i class="bi bi-eye"></i>
                        </button>
                    </div>
                </div>
                <div class="d-grid mt-4">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-box-arrow-in-right me-1"></i>Login
                    </button>
                </div>
            </form>
            <div class="text-center mt-3">
                <small class="text-muted">Default: admin / admin123</small>
            </div>
        </div>
        <div class="card-footer text-center text-muted py-2">
            <small><?= APP_NAME ?> v<?= APP_VERSION ?></small>
        </div>
    </div>
</div>
<script src="<?= BASE_URL ?>assets/vendor/jquery/jquery.min.js"></script>
<script src="<?= BASE_URL ?>assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
<script>
$('#togglePwd').on('click', function() {
    const f = $('#passwordField');
    const isPassword = f.attr('type') === 'password';
    f.attr('type', isPassword ? 'text' : 'password');
    $(this).find('i').toggleClass('bi-eye bi-eye-slash');
});
</script>
</body>
</html>
