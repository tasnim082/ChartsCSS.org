<?php
require_once __DIR__ . '/../config.php';
requireAdmin();

$pageTitle = 'Manage Branches';
$db = getDB();
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRF($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid request.';
    } else {
        $action = $_POST['action'] ?? '';
        if ($action === 'add') {
            $code = strtoupper(trim($_POST['branch_code'] ?? ''));
            $name = trim($_POST['branch_name'] ?? '');
            $type = $_POST['branch_type'] ?? 'Branch';
            if (empty($code) || empty($name)) {
                $error = 'Branch code and name are required.';
            } else {
                try {
                    $db->prepare("INSERT INTO branches (branch_code, branch_name, branch_type) VALUES (?,?,?)")
                       ->execute([$code, $name, $type]);
                    $success = 'Branch added successfully.';
                } catch (PDOException $e) {
                    $error = 'Branch code already exists.';
                }
            }
        } elseif ($action === 'toggle' && isset($_POST['branch_id'])) {
            $bid = (int)$_POST['branch_id'];
            $db->prepare("UPDATE branches SET is_active = 1 - is_active WHERE id = ?")->execute([$bid]);
            $success = 'Branch status updated.';
        }
    }
}

$branches = $db->query("SELECT * FROM branches ORDER BY branch_type, branch_name")->fetchAll();
$csrf = generateCSRF();
include __DIR__ . '/../includes/header.php';
?>
<script>window.IT = window.IT || {}; window.IT.baseUrl = '<?= BASE_URL ?>';</script>
<?php include __DIR__ . '/../includes/navbar.php'; ?>

<div class="main-content">
    <div class="d-flex align-items-center justify-content-between mb-3">
        <h5 class="mb-0 fw-bold">
            <i class="bi bi-building me-1 text-primary"></i>Manage Branches
        </h5>
    </div>

    <?php if ($error): ?>
    <div class="alert alert-danger alert-auto-dismiss py-2"><?= sanitize($error) ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
    <div class="alert alert-success alert-auto-dismiss py-2"><?= sanitize($success) ?></div>
    <?php endif; ?>

    <!-- Add Branch Form -->
    <div class="card mb-3">
        <div class="card-header py-2 px-3 fw-semibold fs-xs text-uppercase">
            <i class="bi bi-plus-circle me-1"></i>Add New Branch
        </div>
        <div class="card-body">
            <form method="POST" class="row g-2 align-items-end">
                <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                <input type="hidden" name="action" value="add">
                <div class="col-6 col-md-2">
                    <label class="form-label">Branch Code</label>
                    <input type="text" name="branch_code" class="form-control form-control-sm"
                           placeholder="e.g. BR003" required>
                </div>
                <div class="col-6 col-md-4">
                    <label class="form-label">Branch Name</label>
                    <input type="text" name="branch_name" class="form-control form-control-sm"
                           placeholder="Full branch name" required>
                </div>
                <div class="col-6 col-md-3">
                    <label class="form-label">Branch Type</label>
                    <select name="branch_type" class="form-select form-select-sm">
                        <?php foreach (['Branch','Sub-Branch','MBO','FT','Division'] as $bt): ?>
                        <option value="<?= $bt ?>"><?= $bt ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <button type="submit" class="btn btn-success btn-sm w-100">
                        <i class="bi bi-plus me-1"></i>Add
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Branches Table -->
    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm table-hover table-striped mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th>#</th>
                            <th>Code</th>
                            <th>Name</th>
                            <th>Type</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($branches as $b): ?>
                        <tr>
                            <td><?= $b['id'] ?></td>
                            <td><strong><?= sanitize($b['branch_code']) ?></strong></td>
                            <td><?= sanitize($b['branch_name']) ?></td>
                            <td><span class="badge bg-secondary"><?= sanitize($b['branch_type']) ?></span></td>
                            <td>
                                <?= $b['is_active'] ?
                                    '<span class="badge bg-success">Active</span>' :
                                    '<span class="badge bg-secondary">Inactive</span>' ?>
                            </td>
                            <td>
                                <form method="POST" class="d-inline">
                                    <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                                    <input type="hidden" name="action" value="toggle">
                                    <input type="hidden" name="branch_id" value="<?= $b['id'] ?>">
                                    <button type="submit" class="btn btn-sm py-0 px-1 <?= $b['is_active'] ? 'btn-outline-danger' : 'btn-outline-success' ?>">
                                        <i class="bi bi-<?= $b['is_active'] ? 'toggle-on' : 'toggle-off' ?>"></i>
                                    </button>
                                </form>
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
