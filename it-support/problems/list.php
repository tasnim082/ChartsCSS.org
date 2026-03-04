<?php
require_once __DIR__ . '/../config.php';

$pageTitle = 'Issue List';
$db = getDB();

// Filters
$filterCat    = $_GET['category'] ?? '';
$filterStatus = $_GET['status'] ?? '';
$filterSolved = isset($_GET['solved']) ? (int)$_GET['solved'] : -1;
$filterMonth  = $_GET['month'] ?? '';
$filterBranch = $_GET['branch'] ?? '';
$search       = trim($_GET['q'] ?? '');
$page         = max(1, (int)($_GET['page'] ?? 1));
$perPage      = 20;

// Build WHERE
$where = ['1=1'];
$params = [];

// Role-based filter: non-admin users see only their category
if (!isAdmin() && isLoggedIn()) {
    $roleToCategory = [
        'pc'       => 'PC',
        'ups'      => 'UPS',
        'cctv'     => 'CCTV',
        'printer'  => 'Printer',
        'ipphone'  => 'IP Phone',
        'hardware' => 'Hardware',
    ];
    $userRole = $_SESSION['user_role'];
    if (isset($roleToCategory[$userRole])) {
        $where[] = 'i.category = ?';
        $params[] = $roleToCategory[$userRole];
    }
} elseif ($filterCat) {
    $where[] = 'i.category = ?';
    $params[] = $filterCat;
}

if ($filterStatus) {
    $where[] = 'i.status = ?';
    $params[] = $filterStatus;
}
if ($filterSolved >= 0) {
    $where[] = 'i.solved = ?';
    $params[] = $filterSolved;
}
if ($filterMonth) {
    $where[] = "DATE_FORMAT(i.issue_date, '%Y-%m') = ?";
    $params[] = $filterMonth;
}
if ($filterBranch) {
    $where[] = 'i.branch_id = ?';
    $params[] = $filterBranch;
}
if ($search) {
    $where[] = "(i.problem_description LIKE ? OR i.branch_officer LIKE ? OR i.vendor_name LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$whereSQL = implode(' AND ', $where);

// Count
$countStmt = $db->prepare("SELECT COUNT(*) FROM issues i LEFT JOIN branches b ON i.branch_id = b.id WHERE $whereSQL");
$countStmt->execute($params);
$total = $countStmt->fetchColumn();
$totalPages = max(1, ceil($total / $perPage));
$offset = ($page - 1) * $perPage;

// Fetch
$stmt = $db->prepare("
    SELECT i.*, b.branch_name, b.branch_code, b.branch_type
    FROM issues i
    LEFT JOIN branches b ON i.branch_id = b.id
    WHERE $whereSQL
    ORDER BY i.created_at DESC
    LIMIT $perPage OFFSET $offset
");
$stmt->execute($params);
$issues = $stmt->fetchAll();

// Branches for filter dropdown
$branchesStmt = $db->query("SELECT id, branch_code, branch_name FROM branches ORDER BY branch_name");
$branches = $branchesStmt->fetchAll();

$catColors = [
    'PC' => '#4e73df','UPS' => '#1cc88a','CCTV' => '#36b9cc',
    'Printer' => '#f6c23e','IP Phone' => '#e74a3b','Hardware' => '#6f42c1','Others' => '#858796',
];

include __DIR__ . '/../includes/header.php';
?>
<script>window.IT = window.IT || {}; window.IT.baseUrl = '<?= BASE_URL ?>';</script>
<?php include __DIR__ . '/../includes/navbar.php'; ?>

<div class="main-content">
    <!-- Header -->
    <div class="d-flex align-items-center justify-content-between mb-2">
        <h5 class="mb-0 fw-bold">
            <i class="bi bi-list-ul me-1 text-primary"></i>Issue List
        </h5>
        <?php if (isLoggedIn()): ?>
        <a href="<?= BASE_URL ?>problems/add.php" class="btn btn-primary btn-sm">
            <i class="bi bi-plus-circle me-1"></i>New Issue
        </a>
        <?php endif; ?>
    </div>

    <!-- Filters Card -->
    <div class="card mb-2">
        <div class="card-body py-2 px-3">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-6 col-sm-3 col-md-2">
                    <label class="form-label">Category</label>
                    <select name="category" class="form-select form-select-sm">
                        <option value="">All</option>
                        <?php foreach (getCategories() as $cat): ?>
                        <option value="<?= $cat ?>" <?= $filterCat === $cat ? 'selected' : '' ?>><?= $cat ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-6 col-sm-3 col-md-2">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select form-select-sm">
                        <option value="">All</option>
                        <?php foreach (['Open','In Progress','Resolved','Closed'] as $s): ?>
                        <option value="<?= $s ?>" <?= $filterStatus === $s ? 'selected' : '' ?>><?= $s ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-6 col-sm-3 col-md-2">
                    <label class="form-label">Solved</label>
                    <select name="solved" class="form-select form-select-sm">
                        <option value="-1">All</option>
                        <option value="1" <?= $filterSolved === 1 ? 'selected' : '' ?>>Yes</option>
                        <option value="0" <?= $filterSolved === 0 ? 'selected' : '' ?>>No</option>
                    </select>
                </div>
                <div class="col-6 col-sm-3 col-md-2">
                    <label class="form-label">Branch</label>
                    <select name="branch" class="form-select form-select-sm">
                        <option value="">All</option>
                        <?php foreach ($branches as $b): ?>
                        <option value="<?= $b['id'] ?>" <?= $filterBranch == $b['id'] ? 'selected' : '' ?>>
                            <?= sanitize($b['branch_code']) ?> - <?= sanitize($b['branch_name']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-8 col-sm-6 col-md-3">
                    <label class="form-label">Search</label>
                    <input type="text" name="q" class="form-control form-control-sm"
                           placeholder="Problem, officer, vendor..." value="<?= sanitize($search) ?>">
                </div>
                <div class="col-4 col-sm-2 col-md-1 d-flex gap-1">
                    <button type="submit" class="btn btn-primary btn-sm flex-fill">
                        <i class="bi bi-search"></i>
                    </button>
                    <a href="<?= BASE_URL ?>problems/list.php" class="btn btn-outline-secondary btn-sm flex-fill">
                        <i class="bi bi-x"></i>
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Results info -->
    <div class="d-flex justify-content-between align-items-center mb-1">
        <small class="text-muted">
            Showing <?= count($issues) ?> of <?= $total ?> records
            <?php if ($filterCat): ?> | Category: <strong><?= sanitize($filterCat) ?></strong><?php endif; ?>
            <?php if ($filterStatus): ?> | Status: <strong><?= sanitize($filterStatus) ?></strong><?php endif; ?>
        </small>
        <a href="<?= BASE_URL ?>reports/index.php" class="btn btn-outline-info btn-sm py-0 px-2 fs-xs">
            <i class="bi bi-file-earmark-bar-graph me-1"></i>Full Report
        </a>
    </div>

    <!-- Issues Table -->
    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm table-hover table-striped mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th width="40">#</th>
                            <th>Category</th>
                            <th>Issue Date</th>
                            <th>Branch</th>
                            <th>Officer</th>
                            <th>Problem</th>
                            <th>IT Officer</th>
                            <th>Status</th>
                            <th>Solved</th>
                            <th width="80">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($issues)): ?>
                        <tr><td colspan="10" class="text-center text-muted py-3">No issues found.</td></tr>
                        <?php else: ?>
                        <?php foreach ($issues as $issue): ?>
                        <tr class="issue-row" data-href="<?= BASE_URL ?>problems/view.php?id=<?= $issue['id'] ?>">
                            <td><?= $issue['id'] ?></td>
                            <td>
                                <span class="badge badge-category" style="background:<?= $catColors[$issue['category']] ?? '#858796' ?>">
                                    <?= sanitize($issue['category']) ?>
                                </span>
                            </td>
                            <td><?= date('d/m/Y', strtotime($issue['issue_date'])) ?></td>
                            <td>
                                <?php if ($issue['branch_code']): ?>
                                <small><strong><?= sanitize($issue['branch_code']) ?></strong><br>
                                <?= sanitize($issue['branch_name']) ?></small>
                                <?php else: ?>-<?php endif; ?>
                            </td>
                            <td><?= sanitize($issue['branch_officer'] ?? '-') ?></td>
                            <td class="text-truncate-2" style="max-width:180px">
                                <?= sanitize($issue['problem_description']) ?>
                            </td>
                            <td><?= sanitize($issue['officer_it'] ?? '-') ?></td>
                            <td>
                                <span class="badge <?= getStatusBadge($issue['status']) ?>">
                                    <?= sanitize($issue['status']) ?>
                                </span>
                            </td>
                            <td class="text-center">
                                <?php if ($issue['solved']): ?>
                                    <i class="bi bi-check-circle-fill text-success"></i>
                                <?php else: ?>
                                    <i class="bi bi-x-circle-fill text-danger"></i>
                                <?php endif; ?>
                            </td>
                            <td onclick="event.stopPropagation()">
                                <a href="<?= BASE_URL ?>problems/view.php?id=<?= $issue['id'] ?>"
                                   class="btn btn-sm btn-outline-info py-0 px-1 me-1"
                                   data-bs-toggle="tooltip" title="View">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <?php if (isLoggedIn() && (isAdmin() || canEditCategory($issue['category']))): ?>
                                <a href="<?= BASE_URL ?>problems/edit.php?id=<?= $issue['id'] ?>"
                                   class="btn btn-sm btn-outline-warning py-0 px-1"
                                   data-bs-toggle="tooltip" title="Edit">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <?php if ($totalPages > 1): ?>
        <div class="card-footer py-2">
            <nav aria-label="Pagination">
                <ul class="pagination pagination-sm mb-0 justify-content-center">
                    <?php
                    $queryParams = array_filter([
                        'category' => $filterCat, 'status' => $filterStatus,
                        'solved' => $filterSolved >= 0 ? $filterSolved : null,
                        'branch' => $filterBranch, 'q' => $search
                    ]);
                    for ($p = 1; $p <= $totalPages; $p++):
                        $queryParams['page'] = $p;
                        $href = '?' . http_build_query($queryParams);
                    ?>
                    <li class="page-item <?= $p === $page ? 'active' : '' ?>">
                        <a class="page-link" href="<?= $href ?>"><?= $p ?></a>
                    </li>
                    <?php endfor; ?>
                </ul>
            </nav>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
