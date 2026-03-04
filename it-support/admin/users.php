<?php
require_once __DIR__ . '/../config.php';
requireAdmin();

$pageTitle = 'Manage Users';
$db = getDB();

$msg = '';
$msgType = 'info';

// Handle delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!verifyCSRF($_POST['csrf_token'] ?? '')) {
        $msg = 'Invalid request.'; $msgType = 'danger';
    } elseif ($_POST['action'] === 'toggle_active' && isset($_POST['user_id'])) {
        $uid = (int)$_POST['user_id'];
        if ($uid === $_SESSION['user_id']) {
            $msg = 'Cannot deactivate your own account.'; $msgType = 'danger';
        } else {
            $db->prepare("UPDATE users SET is_active = 1 - is_active WHERE id = ?")->execute([$uid]);
            $msg = 'User status updated.'; $msgType = 'success';
        }
    }
}

$users = $db->query("SELECT * FROM users ORDER BY role, full_name")->fetchAll();
$csrf = generateCSRF();
include __DIR__ . '/../includes/header.php';
?>
<script>window.IT = window.IT || {}; window.IT.baseUrl = '<?= BASE_URL ?>';</script>
<?php include __DIR__ . '/../includes/navbar.php'; ?>

<div class="main-content">
    <div class="d-flex align-items-center justify-content-between mb-3">
        <h5 class="mb-0 fw-bold">
            <i class="bi bi-people me-1 text-primary"></i>Manage Users
        </h5>
        <a href="<?= BASE_URL ?>admin/user_add.php" class="btn btn-success btn-sm">
            <i class="bi bi-person-plus me-1"></i>Add User
        </a>
    </div>

    <?php if ($msg): ?>
    <div class="alert alert-<?= $msgType ?> alert-auto-dismiss py-2"><?= sanitize($msg) ?></div>
    <?php endif; ?>

    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm table-hover table-striped mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th>#</th>
                            <th>Username</th>
                            <th>Full Name</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>Created</th>
                            <th width="120">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                        <tr>
                            <td><?= $user['id'] ?></td>
                            <td><strong><?= sanitize($user['username']) ?></strong></td>
                            <td><?= sanitize($user['full_name']) ?></td>
                            <td><?= sanitize($user['email'] ?? '-') ?></td>
                            <td>
                                <?php
                                $roleColors = [
                                    'admin' => 'bg-danger', 'pc' => 'bg-primary',
                                    'ups' => 'bg-success', 'cctv' => 'bg-info text-dark',
                                    'printer' => 'bg-warning text-dark', 'ipphone' => 'bg-danger',
                                    'hardware' => 'bg-purple',
                                ];
                                $roleBadge = $roleColors[$user['role']] ?? 'bg-secondary';
                                ?>
                                <span class="badge <?= $roleBadge ?>">
                                    <?= strtoupper($user['role']) ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($user['is_active']): ?>
                                <span class="badge bg-success">Active</span>
                                <?php else: ?>
                                <span class="badge bg-secondary">Inactive</span>
                                <?php endif; ?>
                            </td>
                            <td><?= date('d/m/Y', strtotime($user['created_at'])) ?></td>
                            <td>
                                <a href="<?= BASE_URL ?>admin/user_edit.php?id=<?= $user['id'] ?>"
                                   class="btn btn-sm btn-outline-warning py-0 px-1 me-1"
                                   data-bs-toggle="tooltip" title="Edit">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <?php if ($user['id'] !== $_SESSION['user_id']): ?>
                                <form method="POST" class="d-inline">
                                    <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                                    <input type="hidden" name="action" value="toggle_active">
                                    <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                    <button type="submit" class="btn btn-sm py-0 px-1 <?= $user['is_active'] ? 'btn-outline-danger' : 'btn-outline-success' ?>"
                                            data-bs-toggle="tooltip" title="<?= $user['is_active'] ? 'Deactivate' : 'Activate' ?>">
                                        <i class="bi bi-<?= $user['is_active'] ? 'toggle-on' : 'toggle-off' ?>"></i>
                                    </button>
                                </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
