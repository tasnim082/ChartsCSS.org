<?php
require_once __DIR__ . '/../config.php';
requireLogin();

$id = (int)($_GET['id'] ?? 0);
if (!$id) { header('Location: ' . BASE_URL . 'problems/list.php'); exit; }

$db = getDB();
$stmt = $db->prepare("SELECT * FROM issues WHERE id = ?");
$stmt->execute([$id]);
$issue = $stmt->fetch();
if (!$issue) { header('Location: ' . BASE_URL . 'problems/list.php?error=notfound'); exit; }

// Permission check
if (!isAdmin() && !canEditCategory($issue['category'])) {
    header('Location: ' . BASE_URL . 'problems/view.php?id=' . $id . '&error=unauthorized');
    exit;
}

$pageTitle = 'Edit Issue #' . $id;
$error = '';
$success = '';

// Branches
$branchesStmt = $db->query("SELECT * FROM branches WHERE is_active=1 ORDER BY branch_name");
$branches = $branchesStmt->fetchAll();

$allCats = getCategories();
$userRole = $_SESSION['user_role'];
$roleToCategory = [
    'pc' => 'PC','ups' => 'UPS','cctv' => 'CCTV',
    'printer' => 'Printer','ipphone' => 'IP Phone','hardware' => 'Hardware',
];
$allowedCats = isAdmin() ? $allCats : (isset($roleToCategory[$userRole]) ? [$roleToCategory[$userRole]] : $allCats);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRF($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid request. Please try again.';
    } else {
        $newData = [
            'category'           => $_POST['category'] ?? $issue['category'],
            'issue_date'         => $_POST['issue_date'] ?? $issue['issue_date'],
            'vendor_name'        => trim($_POST['vendor_name'] ?? ''),
            'branch_id'          => !empty($_POST['branch_id']) ? (int)$_POST['branch_id'] : null,
            'branch_officer'     => trim($_POST['branch_officer'] ?? ''),
            'contact_ipphone'    => trim($_POST['contact_ipphone'] ?? ''),
            'problem_description'=> trim($_POST['problem_description'] ?? ''),
            'remarks'            => trim($_POST['remarks'] ?? ''),
            'officer_it'         => trim($_POST['officer_it'] ?? ''),
            'status'             => $_POST['status'] ?? $issue['status'],
            'solved'             => isset($_POST['solved']) ? 1 : 0,
            'manager_name'       => trim($_POST['manager_name'] ?? ''),
        ];

        if (empty($newData['problem_description'])) {
            $error = 'Problem description is required.';
        } else {
            $user = currentUser();

            // Track changes for audit log
            $auditFields = [
                'category','issue_date','vendor_name','branch_id','branch_officer',
                'contact_ipphone','problem_description','remarks','officer_it',
                'status','solved','manager_name'
            ];
            foreach ($auditFields as $field) {
                $oldVal = (string)($issue[$field] ?? '');
                $newVal = (string)($newData[$field] ?? '');
                if ($oldVal !== $newVal) {
                    $action = ($field === 'status') ? 'status_changed' : (($field === 'solved') ? 'solved' : 'updated');
                    writeAuditLog($id, $action, $field, $oldVal, $newVal, $user['id'], $user['name']);
                }
            }

            $stmt = $db->prepare("
                UPDATE issues SET
                    category=?, issue_date=?, vendor_name=?, branch_id=?, branch_officer=?,
                    contact_ipphone=?, problem_description=?, remarks=?, officer_it=?,
                    status=?, solved=?, manager_name=?, last_updated_by=?, last_updated_at=NOW()
                WHERE id=?
            ");
            $stmt->execute([
                $newData['category'], $newData['issue_date'], $newData['vendor_name'] ?: null,
                $newData['branch_id'], $newData['branch_officer'] ?: null,
                $newData['contact_ipphone'] ?: null, $newData['problem_description'],
                $newData['remarks'] ?: null, $newData['officer_it'] ?: null,
                $newData['status'], $newData['solved'], $newData['manager_name'] ?: null,
                $user['id'], $id
            ]);

            header("Location: " . BASE_URL . "problems/view.php?id=$id&updated=1");
            exit;
        }
    }
}

$csrf = generateCSRF();
include __DIR__ . '/../includes/header.php';
?>
<script>window.IT = window.IT || {}; window.IT.baseUrl = '<?= BASE_URL ?>';</script>
<?php include __DIR__ . '/../includes/navbar.php'; ?>

<div class="main-content">
    <div class="d-flex align-items-center mb-3">
        <a href="<?= BASE_URL ?>problems/view.php?id=<?= $id ?>" class="btn btn-sm btn-outline-secondary me-2">
            <i class="bi bi-arrow-left"></i>
        </a>
        <h5 class="mb-0 fw-bold">
            <i class="bi bi-pencil-square me-1 text-warning"></i>Edit Issue #<?= $id ?>
        </h5>
        <span class="ms-2 badge" style="background:<?php
            $catColors = ['PC'=>'#4e73df','UPS'=>'#1cc88a','CCTV'=>'#36b9cc','Printer'=>'#f6c23e','IP Phone'=>'#e74a3b','Hardware'=>'#6f42c1','Others'=>'#858796'];
            echo $catColors[$issue['category']] ?? '#858796';
        ?>"><?= sanitize($issue['category']) ?></span>
    </div>

    <?php if ($error): ?>
    <div class="alert alert-danger alert-auto-dismiss py-2"><?= sanitize($error) ?></div>
    <?php endif; ?>

    <div class="card">
        <div class="card-header py-2 px-3 fw-semibold fs-xs text-uppercase">
            <i class="bi bi-pencil me-1"></i>Edit Issue Details
        </div>
        <div class="card-body">
            <form method="POST" autocomplete="off">
                <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                <div class="row g-2">
                    <div class="col-6 col-md-3">
                        <label class="form-label">Category <span class="text-danger">*</span></label>
                        <select name="category" class="form-select form-select-sm" required <?= !isAdmin() ? 'disabled' : '' ?>>
                            <?php foreach ($allowedCats as $cat): ?>
                            <option value="<?= $cat ?>" <?= $issue['category'] === $cat ? 'selected' : '' ?>><?= $cat ?></option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (!isAdmin()): ?>
                        <input type="hidden" name="category" value="<?= sanitize($issue['category']) ?>">
                        <?php endif; ?>
                    </div>
                    <div class="col-6 col-md-3">
                        <label class="form-label">Issue Date <span class="text-danger">*</span></label>
                        <input type="date" name="issue_date" class="form-control form-control-sm"
                               value="<?= sanitize($issue['issue_date']) ?>" required>
                    </div>
                    <div class="col-6 col-md-3">
                        <label class="form-label">Vendor Name</label>
                        <input type="text" name="vendor_name" class="form-control form-control-sm"
                               value="<?= sanitize($issue['vendor_name'] ?? '') ?>">
                    </div>
                    <div class="col-6 col-md-3">
                        <label class="form-label">Branch / Division</label>
                        <select name="branch_id" class="form-select form-select-sm">
                            <option value="">-- Select --</option>
                            <?php foreach ($branches as $b): ?>
                            <option value="<?= $b['id'] ?>" <?= $issue['branch_id'] == $b['id'] ? 'selected' : '' ?>>
                                <?= sanitize($b['branch_code']) ?> - <?= sanitize($b['branch_name']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-6 col-md-3">
                        <label class="form-label">Branch Officer</label>
                        <input type="text" name="branch_officer" class="form-control form-control-sm"
                               value="<?= sanitize($issue['branch_officer'] ?? '') ?>">
                    </div>
                    <div class="col-6 col-md-3">
                        <label class="form-label">Contact / IP Phone</label>
                        <input type="text" name="contact_ipphone" class="form-control form-control-sm"
                               value="<?= sanitize($issue['contact_ipphone'] ?? '') ?>">
                    </div>
                    <div class="col-6 col-md-3">
                        <label class="form-label">Officer IT</label>
                        <input type="text" name="officer_it" class="form-control form-control-sm"
                               value="<?= sanitize($issue['officer_it'] ?? '') ?>">
                    </div>
                    <div class="col-6 col-md-3">
                        <label class="form-label">Manager Name</label>
                        <input type="text" name="manager_name" class="form-control form-control-sm"
                               value="<?= sanitize($issue['manager_name'] ?? '') ?>">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Problem Description <span class="text-danger">*</span></label>
                        <textarea name="problem_description" class="form-control form-control-sm" rows="3" required><?= sanitize($issue['problem_description']) ?></textarea>
                    </div>
                    <div class="col-12 col-md-8">
                        <label class="form-label">Remarks (If Any)</label>
                        <textarea name="remarks" class="form-control form-control-sm" rows="2"><?= sanitize($issue['remarks'] ?? '') ?></textarea>
                    </div>
                    <div class="col-6 col-md-2">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select form-select-sm">
                            <?php foreach (['Open','In Progress','Resolved','Closed'] as $s): ?>
                            <option value="<?= $s ?>" <?= $issue['status'] === $s ? 'selected' : '' ?>><?= $s ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-6 col-md-2 d-flex align-items-end pb-1">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="solved" id="solvedCheck"
                                   <?= $issue['solved'] ? 'checked' : '' ?>>
                            <label class="form-check-label" for="solvedCheck">Solved</label>
                        </div>
                    </div>
                </div>
                <div class="mt-3 d-flex gap-2">
                    <button type="submit" class="btn btn-warning btn-sm">
                        <i class="bi bi-check-circle me-1"></i>Update Issue
                    </button>
                    <a href="<?= BASE_URL ?>problems/view.php?id=<?= $id ?>" class="btn btn-outline-secondary btn-sm">
                        <i class="bi bi-x me-1"></i>Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
