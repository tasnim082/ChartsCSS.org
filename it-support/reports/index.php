<?php
require_once __DIR__ . '/../config.php';

$pageTitle = 'Reports';
$db = getDB();

// Report filters
$filterCat    = $_GET['category'] ?? '';
$filterStatus = $_GET['status'] ?? '';
$filterSolved = isset($_GET['solved']) ? (int)$_GET['solved'] : -1;
$filterDateFrom = $_GET['date_from'] ?? '';
$filterDateTo   = $_GET['date_to'] ?? '';
$filterBranch   = $_GET['branch'] ?? '';
$filterBranchType = $_GET['branch_type'] ?? '';
$filterOfficer  = trim($_GET['officer'] ?? '');
$search         = trim($_GET['q'] ?? '');

// Build WHERE
$where  = ['1=1'];
$params = [];

// Role filter
if (!isAdmin() && isLoggedIn()) {
    $roleToCategory = [
        'pc'=>'PC','ups'=>'UPS','cctv'=>'CCTV',
        'printer'=>'Printer','ipphone'=>'IP Phone','hardware'=>'Hardware',
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
if ($filterDateFrom) {
    $where[] = 'i.issue_date >= ?';
    $params[] = $filterDateFrom;
}
if ($filterDateTo) {
    $where[] = 'i.issue_date <= ?';
    $params[] = $filterDateTo;
}
if ($filterBranch) {
    $where[] = 'i.branch_id = ?';
    $params[] = $filterBranch;
}
if ($filterBranchType) {
    $where[] = 'b.branch_type = ?';
    $params[] = $filterBranchType;
}
if ($filterOfficer) {
    $where[] = "(i.branch_officer LIKE ? OR i.officer_it LIKE ?)";
    $params[] = "%$filterOfficer%";
    $params[] = "%$filterOfficer%";
}
if ($search) {
    $where[] = "(i.problem_description LIKE ? OR b.branch_name LIKE ? OR b.branch_code LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$whereSQL = implode(' AND ', $where);

$stmt = $db->prepare("
    SELECT i.*, b.branch_name, b.branch_code, b.branch_type
    FROM issues i
    LEFT JOIN branches b ON i.branch_id = b.id
    WHERE $whereSQL
    ORDER BY i.issue_date DESC, i.id DESC
");
$stmt->execute($params);
$issues = $stmt->fetchAll();

// Summary stats
$totalCount   = count($issues);
$solvedCount  = count(array_filter($issues, fn($r) => $r['solved']));
$openCount    = count(array_filter($issues, fn($r) => $r['status'] === 'Open'));
$resolvedCount = count(array_filter($issues, fn($r) => $r['status'] === 'Resolved'));

// Category breakdown
$catBreakdown = [];
foreach ($issues as $row) {
    $catBreakdown[$row['category']] = ($catBreakdown[$row['category']] ?? 0) + 1;
}

// Branches for filter
$branchesStmt = $db->query("SELECT id, branch_code, branch_name, branch_type FROM branches ORDER BY branch_name");
$branches = $branchesStmt->fetchAll();

$catColors = [
    'PC'=>'#4e73df','UPS'=>'#1cc88a','CCTV'=>'#36b9cc',
    'Printer'=>'#f6c23e','IP Phone'=>'#e74a3b','Hardware'=>'#6f42c1','Others'=>'#858796',
];

include __DIR__ . '/../includes/header.php';
?>
<script>window.IT = window.IT || {}; window.IT.baseUrl = '<?= BASE_URL ?>';</script>
<?php include __DIR__ . '/../includes/navbar.php'; ?>

<div class="main-content">
    <div class="d-flex align-items-center justify-content-between mb-2">
        <h5 class="mb-0 fw-bold">
            <i class="bi bi-file-earmark-bar-graph me-1 text-primary"></i>Reports
        </h5>
        <button onclick="window.print()" class="btn btn-outline-secondary btn-sm no-print">
            <i class="bi bi-printer me-1"></i>Print
        </button>
    </div>

    <!-- Filter Card -->
    <div class="card mb-2 no-print">
        <div class="card-header py-2 px-3 fw-semibold fs-xs text-uppercase">
            <i class="bi bi-funnel me-1"></i>Report Filters
        </div>
        <div class="card-body py-2">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-6 col-sm-4 col-md-2">
                    <label class="form-label">Category</label>
                    <select name="category" class="form-select form-select-sm">
                        <option value="">All</option>
                        <?php foreach (getCategories() as $cat): ?>
                        <option value="<?= $cat ?>" <?= $filterCat === $cat ? 'selected' : '' ?>><?= $cat ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-6 col-sm-4 col-md-2">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select form-select-sm">
                        <option value="">All</option>
                        <?php foreach (['Open','In Progress','Resolved','Closed'] as $s): ?>
                        <option value="<?= $s ?>" <?= $filterStatus === $s ? 'selected' : '' ?>><?= $s ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-6 col-sm-4 col-md-2">
                    <label class="form-label">Branch Type</label>
                    <select name="branch_type" class="form-select form-select-sm">
                        <option value="">All</option>
                        <?php foreach (['Branch','Sub-Branch','MBO','FT','Division'] as $bt): ?>
                        <option value="<?= $bt ?>" <?= $filterBranchType === $bt ? 'selected' : '' ?>><?= $bt ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-6 col-sm-4 col-md-2">
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
                <div class="col-6 col-sm-4 col-md-2">
                    <label class="form-label">Date From</label>
                    <input type="date" name="date_from" id="dateFrom" class="form-control form-control-sm"
                           value="<?= sanitize($filterDateFrom) ?>">
                </div>
                <div class="col-6 col-sm-4 col-md-2">
                    <label class="form-label">Date To</label>
                    <input type="date" name="date_to" id="dateTo" class="form-control form-control-sm"
                           value="<?= sanitize($filterDateTo) ?>">
                </div>
                <div class="col-6 col-sm-4 col-md-2">
                    <label class="form-label">Officer Name</label>
                    <input type="text" name="officer" class="form-control form-control-sm"
                           value="<?= sanitize($filterOfficer) ?>" placeholder="Branch/IT officer">
                </div>
                <div class="col-6 col-sm-4 col-md-2">
                    <label class="form-label">Search</label>
                    <input type="text" name="q" class="form-control form-control-sm"
                           value="<?= sanitize($search) ?>" placeholder="Problem / branch code...">
                </div>
                <div class="col-6 col-sm-4 col-md-2">
                    <label class="form-label">Solved</label>
                    <select name="solved" class="form-select form-select-sm">
                        <option value="-1">All</option>
                        <option value="1" <?= $filterSolved === 1 ? 'selected' : '' ?>>Yes</option>
                        <option value="0" <?= $filterSolved === 0 ? 'selected' : '' ?>>No</option>
                    </select>
                </div>
                <div class="col-12 d-flex gap-2 align-items-center">
                    <button type="submit" class="btn btn-primary btn-sm">
                        <i class="bi bi-search me-1"></i>Generate Report
                    </button>
                    <a href="<?= BASE_URL ?>reports/index.php" class="btn btn-outline-secondary btn-sm">
                        <i class="bi bi-x me-1"></i>Reset
                    </a>
                    <button type="button" id="quickToday" class="btn btn-outline-info btn-sm">Today</button>
                    <button type="button" id="quickThisMonth" class="btn btn-outline-info btn-sm">This Month</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="row g-2 mb-2">
        <div class="col-6 col-sm-3">
            <div class="card text-center py-2">
                <div class="fs-xs text-muted text-uppercase">Total</div>
                <div class="fs-3 fw-bold"><?= $totalCount ?></div>
            </div>
        </div>
        <div class="col-6 col-sm-3">
            <div class="card text-center py-2">
                <div class="fs-xs text-muted text-uppercase">Open</div>
                <div class="fs-3 fw-bold text-danger"><?= $openCount ?></div>
            </div>
        </div>
        <div class="col-6 col-sm-3">
            <div class="card text-center py-2">
                <div class="fs-xs text-muted text-uppercase">Resolved</div>
                <div class="fs-3 fw-bold text-success"><?= $resolvedCount ?></div>
            </div>
        </div>
        <div class="col-6 col-sm-3">
            <div class="card text-center py-2">
                <div class="fs-xs text-muted text-uppercase">Solved</div>
                <div class="fs-3 fw-bold text-primary"><?= $solvedCount ?></div>
            </div>
        </div>
    </div>

    <!-- Category Breakdown -->
    <?php if (!empty($catBreakdown)): ?>
    <div class="card mb-2">
        <div class="card-header py-2 px-3 fw-semibold fs-xs text-uppercase">
            <i class="bi bi-bar-chart me-1"></i>Category Breakdown
        </div>
        <div class="card-body py-2 px-3">
            <div class="row g-2">
                <?php foreach ($catBreakdown as $cat => $cnt): ?>
                <div class="col-auto">
                    <span class="badge fs-xs py-1 px-2" style="background:<?= $catColors[$cat] ?? '#858796' ?>">
                        <?= sanitize($cat) ?>: <?= $cnt ?>
                    </span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Report Table -->
    <div class="card">
        <div class="card-header py-2 px-3 d-flex justify-content-between align-items-center">
            <span class="fw-semibold fs-xs text-uppercase">
                <i class="bi bi-table me-1"></i>Issue Report
            </span>
            <small class="text-muted"><?= $totalCount ?> record(s)</small>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm table-hover table-striped mb-0" id="reportTable">
                    <thead class="table-dark">
                        <tr>
                            <th>#</th>
                            <th>ID</th>
                            <th>Category</th>
                            <th>Issue Date</th>
                            <th>Vendor</th>
                            <th>Branch Code</th>
                            <th>Branch Name</th>
                            <th>Type</th>
                            <th>Branch Officer</th>
                            <th>Contact</th>
                            <th>Problem</th>
                            <th>Remarks</th>
                            <th>IT Officer</th>
                            <th>Status</th>
                            <th>Solved</th>
                            <th>Manager</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($issues)): ?>
                        <tr><td colspan="16" class="text-center text-muted py-3">No records found.</td></tr>
                        <?php else: ?>
                        <?php $sn = 1; foreach ($issues as $issue): ?>
                        <tr class="issue-row" data-href="<?= BASE_URL ?>problems/view.php?id=<?= $issue['id'] ?>">
                            <td><?= $sn++ ?></td>
                            <td><?= $issue['id'] ?></td>
                            <td>
                                <span class="badge badge-category" style="background:<?= $catColors[$issue['category']] ?? '#858796' ?>">
                                    <?= sanitize($issue['category']) ?>
                                </span>
                            </td>
                            <td><?= date('d/m/Y', strtotime($issue['issue_date'])) ?></td>
                            <td><?= sanitize($issue['vendor_name'] ?? '-') ?></td>
                            <td><?= sanitize($issue['branch_code'] ?? '-') ?></td>
                            <td><?= sanitize($issue['branch_name'] ?? '-') ?></td>
                            <td><?= sanitize($issue['branch_type'] ?? '-') ?></td>
                            <td><?= sanitize($issue['branch_officer'] ?? '-') ?></td>
                            <td><?= sanitize($issue['contact_ipphone'] ?? '-') ?></td>
                            <td style="max-width:150px" class="text-truncate-2"><?= sanitize($issue['problem_description']) ?></td>
                            <td style="max-width:100px" class="text-truncate-2"><?= sanitize($issue['remarks'] ?? '-') ?></td>
                            <td><?= sanitize($issue['officer_it'] ?? '-') ?></td>
                            <td>
                                <span class="badge <?= getStatusBadge($issue['status']) ?>">
                                    <?= sanitize($issue['status']) ?>
                                </span>
                            </td>
                            <td class="text-center">
                                <?= $issue['solved'] ? '<i class="bi bi-check-circle-fill text-success"></i>' : '<i class="bi bi-x-circle-fill text-danger"></i>' ?>
                            </td>
                            <td><?= sanitize($issue['manager_name'] ?? '-') ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
