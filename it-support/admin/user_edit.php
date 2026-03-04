<?php
require_once __DIR__ . '/../config.php';
requireAdmin();

$id = (int)($_GET['id'] ?? 0);
if (!$id) { header('Location: ' . BASE_URL . 'admin/users.php'); exit; }

$db = getDB();
$stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$id]);
$user = $stmt->fetch();
if (!$user) { header('Location: ' . BASE_URL . 'admin/users.php'); exit; }

$pageTitle = 'Edit User: ' . $user['username'];
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRF($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid request.';
    } else {
        $fullName = trim($_POST['full_name'] ?? '');
        $email    = trim($_POST['email'] ?? '');
        $role     = $_POST['role'] ?? $user['role'];
        $password = $_POST['password'] ?? '';
        $password2 = $_POST['password2'] ?? '';
        $isActive = isset($_POST['is_active']) ? 1 : 0;

        if (empty($fullName)) {
            $error = 'Full name is required.';
        } elseif (!empty($password) && $password !== $password2) {
            $error = 'Passwords do not match.';
        } elseif (!empty($password) && strlen($password) < 6) {
            $error = 'Password must be at least 6 characters.';
        } else {
            if (!empty($password)) {
                $db->prepare("UPDATE users SET full_name=?, email=?, role=?, is_active=?, password=? WHERE id=?")
                   ->execute([$fullName, $email ?: null, $role, $isActive, password_hash($password, PASSWORD_DEFAULT), $id]);
            } else {
                $db->prepare("UPDATE users SET full_name=?, email=?, role=?, is_active=? WHERE id=?")
                   ->execute([$fullName, $email ?: null, $role, $isActive, $id]);
            }
            header('Location: ' . BASE_URL . 'admin/users.php?updated=1');
            exit;
        }
    }
}

$csrf = generateCSRF();
$roles = ['admin','pc','ups','cctv','printer','ipphone','hardware'];
include __DIR__ . '/../includes/header.php';
?>
<script>window.IT = window.IT || {}; window.IT.baseUrl = '<?= BASE_URL ?>';</script>
<?php include __DIR__ . '/../includes/navbar.php'; ?>

<div class="main-content">
    <div class="d-flex align-items-center mb-3">
        <a href="<?= BASE_URL ?>admin/users.php" class="btn btn-sm btn-outline-secondary me-2">
            <i class="bi bi-arrow-left"></i>
        </a>
        <h5 class="mb-0 fw-bold">
            <i class="bi bi-person-gear me-1 text-warning"></i>Edit User: <?= sanitize($user['username']) ?>
        </h5>
    </div>

    <?php if ($error): ?>
    <div class="alert alert-danger py-2"><?= sanitize($error) ?></div>
    <?php endif; ?>

    <div class="card" style="max-width:500px">
        <div class="card-header py-2 px-3 fw-semibold fs-xs text-uppercase">
            <i class="bi bi-pencil me-1"></i>Edit User Details
        </div>
        <div class="card-body">
            <form method="POST" autocomplete="off">
                <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                <div class="mb-2">
                    <label class="form-label">Username</label>
                    <input type="text" class="form-control form-control-sm" value="<?= sanitize($user['username']) ?>" disabled>
                    <small class="text-muted">Username cannot be changed.</small>
                </div>
                <div class="mb-2">
                    <label class="form-label">Full Name <span class="text-danger">*</span></label>
                    <input type="text" name="full_name" class="form-control form-control-sm"
                           value="<?= sanitize($_POST['full_name'] ?? $user['full_name']) ?>" required>
                </div>
                <div class="mb-2">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-control form-control-sm"
                           value="<?= sanitize($_POST['email'] ?? $user['email'] ?? '') ?>">
                </div>
                <div class="mb-2">
                    <label class="form-label">Role <span class="text-danger">*</span></label>
                    <select name="role" class="form-select form-select-sm" required
                            <?= ($id === $_SESSION['user_id']) ? 'disabled' : '' ?>>
                        <?php foreach ($roles as $r): ?>
                        <option value="<?= $r ?>" <?= (($_POST['role'] ?? $user['role']) === $r) ? 'selected' : '' ?>>
                            <?= strtoupper($r) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if ($id === $_SESSION['user_id']): ?>
                    <input type="hidden" name="role" value="<?= $user['role'] ?>">
                    <small class="text-muted">Cannot change your own role.</small>
                    <?php endif; ?>
                </div>
                <div class="mb-2">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="is_active" id="activeCheck"
                               <?= $user['is_active'] ? 'checked' : '' ?>
                               <?= ($id === $_SESSION['user_id']) ? 'disabled' : '' ?>>
                        <label class="form-check-label" for="activeCheck">Active</label>
                    </div>
                    <?php if ($id === $_SESSION['user_id']): ?>
                    <input type="hidden" name="is_active" value="1">
                    <?php endif; ?>
                </div>
                <hr class="my-2">
                <small class="text-muted d-block mb-2">Leave password blank to keep unchanged.</small>
                <div class="mb-2">
                    <label class="form-label">New Password</label>
                    <input type="password" name="password" class="form-control form-control-sm" minlength="6">
                </div>
                <div class="mb-3">
                    <label class="form-label">Confirm New Password</label>
                    <input type="password" name="password2" class="form-control form-control-sm">
                </div>
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-warning btn-sm">
                        <i class="bi bi-check-circle me-1"></i>Update User
                    </button>
                    <a href="<?= BASE_URL ?>admin/users.php" class="btn btn-outline-secondary btn-sm">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
