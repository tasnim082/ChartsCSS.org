<?php
require_once __DIR__ . '/config.php';
requireLogin();

$pageTitle = 'My Profile';
$db = getDB();
$user = currentUser();
$error = '';
$success = '';

// Get full user data
$stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user['id']]);
$userData = $stmt->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRF($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid request.';
    } else {
        $fullName = trim($_POST['full_name'] ?? '');
        $email    = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $password2 = $_POST['password2'] ?? '';

        if (empty($fullName)) {
            $error = 'Full name is required.';
        } elseif (!empty($password) && $password !== $password2) {
            $error = 'Passwords do not match.';
        } elseif (!empty($password) && strlen($password) < 6) {
            $error = 'Password must be at least 6 characters.';
        } else {
            if (!empty($password)) {
                $db->prepare("UPDATE users SET full_name=?, email=?, password=? WHERE id=?")
                   ->execute([$fullName, $email ?: null, password_hash($password, PASSWORD_DEFAULT), $user['id']]);
            } else {
                $db->prepare("UPDATE users SET full_name=?, email=? WHERE id=?")
                   ->execute([$fullName, $email ?: null, $user['id']]);
            }
            // Update session name
            $_SESSION['user_name'] = $fullName;
            $success = 'Profile updated successfully.';
            // Refresh user data
            $stmt->execute([$user['id']]);
            $userData = $stmt->fetch();
        }
    }
}

$csrf = generateCSRF();
include __DIR__ . '/includes/header.php';
?>
<script>window.IT = window.IT || {}; window.IT.baseUrl = '<?= BASE_URL ?>';</script>
<?php include __DIR__ . '/includes/navbar.php'; ?>

<div class="main-content">
    <h5 class="mb-3 fw-bold">
        <i class="bi bi-person-circle me-1 text-primary"></i>My Profile
    </h5>

    <?php if ($error): ?>
    <div class="alert alert-danger py-2"><?= sanitize($error) ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
    <div class="alert alert-success alert-auto-dismiss py-2"><?= sanitize($success) ?></div>
    <?php endif; ?>

    <div class="card" style="max-width:450px">
        <div class="card-header py-2 px-3 fw-semibold fs-xs text-uppercase">
            <i class="bi bi-person me-1"></i>Account Information
        </div>
        <div class="card-body">
            <div class="mb-2">
                <small class="text-muted">Username</small>
                <div class="fw-bold"><?= sanitize($userData['username']) ?></div>
            </div>
            <div class="mb-3">
                <small class="text-muted">Role</small>
                <div>
                    <span class="badge bg-primary"><?= strtoupper($userData['role']) ?></span>
                </div>
            </div>
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                <div class="mb-2">
                    <label class="form-label">Full Name <span class="text-danger">*</span></label>
                    <input type="text" name="full_name" class="form-control form-control-sm"
                           value="<?= sanitize($_POST['full_name'] ?? $userData['full_name']) ?>" required>
                </div>
                <div class="mb-2">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-control form-control-sm"
                           value="<?= sanitize($_POST['email'] ?? $userData['email'] ?? '') ?>">
                </div>
                <hr class="my-2">
                <small class="text-muted d-block mb-2">Leave password blank to keep unchanged.</small>
                <div class="mb-2">
                    <label class="form-label">New Password</label>
                    <input type="password" name="password" class="form-control form-control-sm" minlength="6">
                </div>
                <div class="mb-3">
                    <label class="form-label">Confirm Password</label>
                    <input type="password" name="password2" class="form-control form-control-sm">
                </div>
                <button type="submit" class="btn btn-primary btn-sm">
                    <i class="bi bi-check-circle me-1"></i>Update Profile
                </button>
            </form>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
