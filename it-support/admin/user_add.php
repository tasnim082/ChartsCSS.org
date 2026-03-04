<?php
require_once __DIR__ . '/../config.php';
requireAdmin();

$pageTitle = 'Add User';
$db = getDB();
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRF($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid request. Please try again.';
    } else {
        $username  = trim($_POST['username'] ?? '');
        $fullName  = trim($_POST['full_name'] ?? '');
        $email     = trim($_POST['email'] ?? '');
        $role      = $_POST['role'] ?? 'hardware';
        $password  = $_POST['password'] ?? '';
        $password2 = $_POST['password2'] ?? '';

        if (empty($username) || empty($fullName) || empty($password)) {
            $error = 'Username, full name and password are required.';
        } elseif (!preg_match('/^[a-z0-9_]{3,30}$/', $username)) {
            $error = 'Username must be 3-30 chars: lowercase letters, numbers, underscore only.';
        } elseif ($password !== $password2) {
            $error = 'Passwords do not match.';
        } elseif (strlen($password) < 6) {
            $error = 'Password must be at least 6 characters.';
        } else {
            // Check duplicate
            $check = $db->prepare("SELECT id FROM users WHERE username = ?");
            $check->execute([$username]);
            if ($check->fetch()) {
                $error = 'Username already exists.';
            } else {
                $hashed = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $db->prepare("INSERT INTO users (username, password, full_name, email, role) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$username, $hashed, $fullName, $email ?: null, $role]);
                header('Location: ' . BASE_URL . 'admin/users.php?created=1');
                exit;
            }
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
            <i class="bi bi-person-plus me-1 text-success"></i>Add New User
        </h5>
    </div>

    <?php if ($error): ?>
    <div class="alert alert-danger py-2"><?= sanitize($error) ?></div>
    <?php endif; ?>

    <div class="card" style="max-width:500px">
        <div class="card-header py-2 px-3 fw-semibold fs-xs text-uppercase">
            <i class="bi bi-person-gear me-1"></i>User Details
        </div>
        <div class="card-body">
            <form method="POST" autocomplete="off">
                <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                <div class="mb-2">
                    <label class="form-label">Username <span class="text-danger">*</span></label>
                    <input type="text" name="username" class="form-control form-control-sm"
                           value="<?= sanitize($_POST['username'] ?? '') ?>"
                           placeholder="lowercase, no spaces" required>
                    <small class="text-muted">3-30 chars: a-z, 0-9, underscore</small>
                </div>
                <div class="mb-2">
                    <label class="form-label">Full Name <span class="text-danger">*</span></label>
                    <input type="text" name="full_name" class="form-control form-control-sm"
                           value="<?= sanitize($_POST['full_name'] ?? '') ?>" required>
                </div>
                <div class="mb-2">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-control form-control-sm"
                           value="<?= sanitize($_POST['email'] ?? '') ?>">
                </div>
                <div class="mb-2">
                    <label class="form-label">Role <span class="text-danger">*</span></label>
                    <select name="role" class="form-select form-select-sm" required>
                        <?php foreach ($roles as $r): ?>
                        <option value="<?= $r ?>" <?= ($_POST['role'] ?? '') === $r ? 'selected' : '' ?>>
                            <?= strtoupper($r) ?>
                            <?php
                            $roleDesc = [
                                'admin' => '- Full access', 'pc' => '- PC issues only',
                                'ups' => '- UPS issues only', 'cctv' => '- CCTV issues only',
                                'printer' => '- Printer issues only', 'ipphone' => '- IP Phone issues only',
                                'hardware' => '- Hardware issues only',
                            ];
                            echo $roleDesc[$r] ?? '';
                            ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-2">
                    <label class="form-label">Password <span class="text-danger">*</span></label>
                    <input type="password" name="password" class="form-control form-control-sm" required minlength="6">
                    <small class="text-muted">Minimum 6 characters</small>
                </div>
                <div class="mb-3">
                    <label class="form-label">Confirm Password <span class="text-danger">*</span></label>
                    <input type="password" name="password2" class="form-control form-control-sm" required>
                </div>
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-success btn-sm">
                        <i class="bi bi-check-circle me-1"></i>Create User
                    </button>
                    <a href="<?= BASE_URL ?>admin/users.php" class="btn btn-outline-secondary btn-sm">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
