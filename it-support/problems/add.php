<?php
require_once __DIR__ . '/../config.php';
requireLogin();

$pageTitle = 'New Issue';
$db = getDB();
$error = '';
$success = '';

// Branches
$branchesStmt = $db->query("SELECT * FROM branches WHERE is_active=1 ORDER BY branch_name");
$branches = $branchesStmt->fetchAll();

// Determine allowed categories
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
        $category    = $_POST['category'] ?? '';
        $issueDate   = $_POST['issue_date'] ?? date('Y-m-d');
        $vendorName  = trim($_POST['vendor_name'] ?? '');
        $branchId    = !empty($_POST['branch_id']) ? (int)$_POST['branch_id'] : null;
        $branchOfficer = trim($_POST['branch_officer'] ?? '');
        $contactIpPhone = trim($_POST['contact_ipphone'] ?? '');
        $problemDesc = trim($_POST['problem_description'] ?? '');
        $remarks     = trim($_POST['remarks'] ?? '');
        $officerIt   = trim($_POST['officer_it'] ?? '');
        $status      = $_POST['status'] ?? 'Open';
        $solved      = isset($_POST['solved']) ? 1 : 0;
        $managerName = trim($_POST['manager_name'] ?? '');

        // Validate
        if (empty($category) || !in_array($category, $allCats)) {
            $error = 'Please select a valid category.';
        } elseif (empty($problemDesc)) {
            $error = 'Problem description is required.';
        } elseif (!isAdmin() && !canEditCategory($category)) {
            $error = 'You are not authorized to create issues in this category.';
        } else {
            $user = currentUser();
            $stmt = $db->prepare("
                INSERT INTO issues (category, issue_date, vendor_name, branch_id, branch_officer, contact_ipphone,
                    problem_description, remarks, officer_it, status, solved, manager_name, created_by, last_updated_by, last_updated_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            $stmt->execute([
                $category, $issueDate, $vendorName ?: null, $branchId, $branchOfficer ?: null,
                $contactIpPhone ?: null, $problemDesc, $remarks ?: null, $officerIt ?: null,
                $status, $solved, $managerName ?: null, $user['id'], $user['id']
            ]);
            $newId = $db->lastInsertId();

            // Audit log
            writeAuditLog($newId, 'created', null, null, "Issue created by {$user['name']}", $user['id'], $user['name']);

            header("Location: " . BASE_URL . "problems/view.php?id=$newId&created=1");
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
        <a href="<?= BASE_URL ?>problems/list.php" class="btn btn-sm btn-outline-secondary me-2">
            <i class="bi bi-arrow-left"></i>
        </a>
        <h5 class="mb-0 fw-bold">
            <i class="bi bi-plus-circle me-1 text-success"></i>New Issue
        </h5>
    </div>

    <?php if ($error): ?>
    <div class="alert alert-danger alert-auto-dismiss py-2"><?= sanitize($error) ?></div>
    <?php endif; ?>

    <div class="card">
        <div class="card-header py-2 px-3 fw-semibold fs-xs text-uppercase">
            <i class="bi bi-clipboard-plus me-1"></i>Issue Entry Form
        </div>
        <div class="card-body">
            <form method="POST" autocomplete="off">
                <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                <div class="row g-2">
                    <!-- Category -->
                    <div class="col-6 col-md-3">
                        <label class="form-label">Category <span class="text-danger">*</span></label>
                        <select name="category" class="form-select form-select-sm" required>
                            <option value="">-- Select --</option>
                            <?php foreach ($allowedCats as $cat): ?>
                            <option value="<?= $cat ?>" <?= ($_POST['category'] ?? '') === $cat ? 'selected' : '' ?>>
                                <?= $cat ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <!-- Issue Date -->
                    <div class="col-6 col-md-3">
                        <label class="form-label">Issue Date <span class="text-danger">*</span></label>
                        <input type="date" name="issue_date" class="form-control form-control-sm"
                               value="<?= sanitize($_POST['issue_date'] ?? date('Y-m-d')) ?>" required>
                    </div>
                    <!-- Vendor -->
                    <div class="col-6 col-md-3">
                        <label class="form-label">Vendor Name</label>
                        <input type="text" name="vendor_name" class="form-control form-control-sm"
                               value="<?= sanitize($_POST['vendor_name'] ?? '') ?>" placeholder="Vendor name">
                    </div>
                    <!-- Branch -->
                    <div class="col-6 col-md-3">
                        <label class="form-label">Branch / Division</label>
                        <select name="branch_id" class="form-select form-select-sm">
                            <option value="">-- Select --</option>
                            <?php foreach ($branches as $b): ?>
                            <option value="<?= $b['id'] ?>"
                                <?= (isset($_POST['branch_id']) && $_POST['branch_id'] == $b['id']) ? 'selected' : '' ?>>
                                <?= sanitize($b['branch_code']) ?> - <?= sanitize($b['branch_name']) ?> (<?= $b['branch_type'] ?>)
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <!-- Branch Officer -->
                    <div class="col-6 col-md-3">
                        <label class="form-label">Branch Officer</label>
                        <input type="text" name="branch_officer" class="form-control form-control-sm"
                               value="<?= sanitize($_POST['branch_officer'] ?? '') ?>" placeholder="Officer name">
                    </div>
                    <!-- Contact/IP Phone -->
                    <div class="col-6 col-md-3">
                        <label class="form-label">Contact / IP Phone</label>
                        <input type="text" name="contact_ipphone" class="form-control form-control-sm"
                               value="<?= sanitize($_POST['contact_ipphone'] ?? '') ?>" placeholder="Phone / IP ext.">
                    </div>
                    <!-- IT Officer -->
                    <div class="col-6 col-md-3">
                        <label class="form-label">Officer IT</label>
                        <input type="text" name="officer_it" class="form-control form-control-sm"
                               value="<?= sanitize($_POST['officer_it'] ?? '') ?>" placeholder="IT officer name">
                    </div>
                    <!-- Manager -->
                    <div class="col-6 col-md-3">
                        <label class="form-label">Manager Name</label>
                        <input type="text" name="manager_name" class="form-control form-control-sm"
                               value="<?= sanitize($_POST['manager_name'] ?? '') ?>" placeholder="Manager name">
                    </div>
                    <!-- Problem Description -->
                    <div class="col-12">
                        <label class="form-label">Problem Description <span class="text-danger">*</span></label>
                        <textarea name="problem_description" class="form-control form-control-sm" rows="3"
                                  placeholder="Describe the problem in detail..." required><?= sanitize($_POST['problem_description'] ?? '') ?></textarea>
                    </div>
                    <!-- Remarks -->
                    <div class="col-12 col-md-8">
                        <label class="form-label">Remarks (If Any)</label>
                        <textarea name="remarks" class="form-control form-control-sm" rows="2"
                                  placeholder="Additional remarks..."><?= sanitize($_POST['remarks'] ?? '') ?></textarea>
                    </div>
                    <!-- Status + Solved -->
                    <div class="col-6 col-md-2">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select form-select-sm">
                            <?php foreach (['Open','In Progress','Resolved','Closed'] as $s): ?>
                            <option value="<?= $s ?>" <?= ($_POST['status'] ?? 'Open') === $s ? 'selected' : '' ?>><?= $s ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-6 col-md-2 d-flex align-items-end">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="solved" id="solvedCheck"
                                   <?= isset($_POST['solved']) ? 'checked' : '' ?>>
                            <label class="form-check-label" for="solvedCheck">Solved</label>
                        </div>
                    </div>
                </div>
                <div class="mt-3 d-flex gap-2">
                    <button type="submit" class="btn btn-success btn-sm">
                        <i class="bi bi-check-circle me-1"></i>Save Issue
                    </button>
                    <a href="<?= BASE_URL ?>problems/list.php" class="btn btn-outline-secondary btn-sm">
                        <i class="bi bi-x me-1"></i>Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
