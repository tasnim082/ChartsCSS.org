<?php
require_once __DIR__ . '/config.php';

$pageTitle = 'Dashboard';
$db = getDB();

// Stats per category
$catStats = [];
$categories = getCategories();
foreach ($categories as $cat) {
    $stmt = $db->prepare("SELECT COUNT(*) as total, SUM(solved) as solved FROM issues WHERE category = ?");
    $stmt->execute([$cat]);
    $row = $stmt->fetch();
    $catStats[$cat] = $row;
}

// Overall stats
$totalStmt = $db->query("SELECT COUNT(*) as total, SUM(solved) as solved FROM issues");
$totalRow = $totalStmt->fetch();

$openStmt  = $db->query("SELECT COUNT(*) as cnt FROM issues WHERE status = 'Open'");
$openCount = $openStmt->fetch()['cnt'];

$inProgressStmt = $db->query("SELECT COUNT(*) as cnt FROM issues WHERE status = 'In Progress'");
$inProgressCount = $inProgressStmt->fetch()['cnt'];

// Monthly trend (last 6 months)
$monthlyStmt = $db->query("
    SELECT DATE_FORMAT(issue_date, '%b %Y') as month_label,
           DATE_FORMAT(issue_date, '%Y-%m') as month_val,
           COUNT(*) as cnt
    FROM issues
    WHERE issue_date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
    GROUP BY month_val
    ORDER BY month_val ASC
");
$monthlyData = $monthlyStmt->fetchAll();

// Status distribution
$statusStmt = $db->query("SELECT status, COUNT(*) as cnt FROM issues GROUP BY status");
$statusData = $statusStmt->fetchAll();

// Recent issues
$recentStmt = $db->query("
    SELECT i.*, b.branch_name, b.branch_code
    FROM issues i
    LEFT JOIN branches b ON i.branch_id = b.id
    ORDER BY i.created_at DESC LIMIT 10
");
$recentIssues = $recentStmt->fetchAll();

// Category color map
$catColors = [
    'PC'       => '#4e73df',
    'UPS'      => '#1cc88a',
    'CCTV'     => '#36b9cc',
    'Printer'  => '#f6c23e',
    'IP Phone' => '#e74a3b',
    'Hardware' => '#6f42c1',
    'Others'   => '#858796',
];

$catCardClasses = [
    'PC'       => 'pc-card',
    'UPS'      => 'ups-card',
    'CCTV'     => 'cctv-card',
    'Printer'  => 'printer-card',
    'IP Phone' => 'ipphone-card',
    'Hardware' => 'hardware-card',
    'Others'   => 'others-card',
];

$catIcons = [
    'PC'       => 'bi-pc-display',
    'UPS'      => 'bi-battery-charging',
    'CCTV'     => 'bi-camera-video',
    'Printer'  => 'bi-printer',
    'IP Phone' => 'bi-telephone',
    'Hardware' => 'bi-cpu',
    'Others'   => 'bi-three-dots',
];

include __DIR__ . '/includes/header.php';
?>
<script>
    window.IT = window.IT || {};
    window.IT.baseUrl = '<?= BASE_URL ?>';
</script>
<?php include __DIR__ . '/includes/navbar.php'; ?>

<div class="main-content">
    <!-- Page Header -->
    <div class="d-flex align-items-center justify-content-between mb-3">
        <h5 class="mb-0 fw-bold">
            <i class="bi bi-speedometer2 me-1 text-primary"></i>Dashboard
        </h5>
        <span class="text-muted fs-xs">
            <i class="bi bi-calendar3 me-1"></i><?= date('d M Y, l') ?>
        </span>
    </div>

    <!-- Summary Row -->
    <div class="row g-2 mb-3">
        <div class="col-6 col-sm-3">
            <div class="card stat-card total-card h-100">
                <div class="card-body py-2 px-3 d-flex justify-content-between align-items-center"
                     onclick="window.location.href='<?= BASE_URL ?>problems/list.php'" style="cursor:pointer">
                    <div>
                        <div class="fs-xs text-uppercase text-muted fw-bold">Total Issues</div>
                        <div class="fs-4 fw-bold"><?= (int)$totalRow['total'] ?></div>
                    </div>
                    <i class="bi bi-clipboard-data stat-icon text-secondary"></i>
                </div>
            </div>
        </div>
        <div class="col-6 col-sm-3">
            <div class="card stat-card open-card h-100">
                <div class="card-body py-2 px-3 d-flex justify-content-between align-items-center"
                     onclick="window.location.href='<?= BASE_URL ?>problems/list.php?status=Open'" style="cursor:pointer">
                    <div>
                        <div class="fs-xs text-uppercase text-muted fw-bold">Open</div>
                        <div class="fs-4 fw-bold text-danger"><?= (int)$openCount ?></div>
                    </div>
                    <i class="bi bi-exclamation-circle stat-icon text-danger"></i>
                </div>
            </div>
        </div>
        <div class="col-6 col-sm-3">
            <div class="card stat-card h-100" style="border-left-color:#f6c23e">
                <div class="card-body py-2 px-3 d-flex justify-content-between align-items-center"
                     onclick="window.location.href='<?= BASE_URL ?>problems/list.php?status=In+Progress'" style="cursor:pointer">
                    <div>
                        <div class="fs-xs text-uppercase text-muted fw-bold">In Progress</div>
                        <div class="fs-4 fw-bold text-warning"><?= (int)$inProgressCount ?></div>
                    </div>
                    <i class="bi bi-hourglass-split stat-icon text-warning"></i>
                </div>
            </div>
        </div>
        <div class="col-6 col-sm-3">
            <div class="card stat-card solved-card h-100">
                <div class="card-body py-2 px-3 d-flex justify-content-between align-items-center"
                     onclick="window.location.href='<?= BASE_URL ?>problems/list.php?solved=1'" style="cursor:pointer">
                    <div>
                        <div class="fs-xs text-uppercase text-muted fw-bold">Solved</div>
                        <div class="fs-4 fw-bold text-success"><?= (int)$totalRow['solved'] ?></div>
                    </div>
                    <i class="bi bi-check-circle stat-icon text-success"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Category Cards -->
    <div class="row g-2 mb-3">
        <?php foreach ($categories as $cat):
            $stats = $catStats[$cat];
            $cardClass = $catCardClasses[$cat];
            $icon = $catIcons[$cat];
        ?>
        <div class="col-6 col-sm-4 col-lg-3">
            <div class="card stat-card <?= $cardClass ?> h-100"
                 onclick="window.location.href='<?= BASE_URL ?>problems/list.php?category=<?= urlencode($cat) ?>'"
                 style="cursor:pointer" data-bs-toggle="tooltip" title="Click to view <?= $cat ?> issues">
                <div class="card-body py-2 px-3">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="fs-xs text-uppercase fw-bold text-muted"><?= sanitize($cat) ?></div>
                            <div class="fs-3 fw-bold"><?= (int)$stats['total'] ?></div>
                            <div class="fs-xs text-muted">
                                <span class="text-success"><?= (int)$stats['solved'] ?> solved</span>
                                &nbsp;/&nbsp;
                                <span class="text-danger"><?= (int)$stats['total'] - (int)$stats['solved'] ?> open</span>
                            </div>
                        </div>
                        <i class="bi <?= $icon ?> stat-icon" style="color:<?= $catColors[$cat] ?>"></i>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Charts Row -->
    <div class="row g-2 mb-3">
        <!-- Category Doughnut -->
        <div class="col-12 col-md-4">
            <div class="card h-100">
                <div class="card-header py-2 px-3 fw-semibold fs-xs text-uppercase">
                    <i class="bi bi-pie-chart me-1 text-primary"></i>Issues by Category
                </div>
                <div class="card-body p-2">
                    <div class="chart-container">
                        <canvas id="categoryChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
        <!-- Status Pie -->
        <div class="col-12 col-md-4">
            <div class="card h-100">
                <div class="card-header py-2 px-3 fw-semibold fs-xs text-uppercase">
                    <i class="bi bi-pie-chart-fill me-1 text-success"></i>Issues by Status
                </div>
                <div class="card-body p-2">
                    <div class="chart-container">
                        <canvas id="statusChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
        <!-- Monthly Bar -->
        <div class="col-12 col-md-4">
            <div class="card h-100">
                <div class="card-header py-2 px-3 fw-semibold fs-xs text-uppercase">
                    <i class="bi bi-bar-chart me-1 text-info"></i>Monthly Trend
                </div>
                <div class="card-body p-2">
                    <div class="chart-container">
                        <canvas id="monthlyChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Issues Table -->
    <div class="card">
        <div class="card-header py-2 px-3 d-flex justify-content-between align-items-center">
            <span class="fw-semibold fs-xs text-uppercase">
                <i class="bi bi-clock-history me-1 text-warning"></i>Recent Issues
            </span>
            <a href="<?= BASE_URL ?>problems/list.php" class="btn btn-outline-primary btn-sm py-0 px-2 fs-xs">
                View All
            </a>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm table-hover table-striped mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th>#</th>
                            <th>Category</th>
                            <th>Date</th>
                            <th>Branch</th>
                            <th>Problem</th>
                            <th>Status</th>
                            <th>Solved</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($recentIssues)): ?>
                        <tr><td colspan="7" class="text-center text-muted py-3">No issues found.</td></tr>
                        <?php else: ?>
                        <?php foreach ($recentIssues as $issue): ?>
                        <tr class="issue-row" data-href="<?= BASE_URL ?>problems/view.php?id=<?= $issue['id'] ?>"
                            style="cursor:pointer">
                            <td><?= $issue['id'] ?></td>
                            <td>
                                <span class="badge" style="background-color:<?= $catColors[$issue['category']] ?? '#858796' ?>">
                                    <?= sanitize($issue['category']) ?>
                                </span>
                            </td>
                            <td><?= date('d/m/Y', strtotime($issue['issue_date'])) ?></td>
                            <td><?= sanitize($issue['branch_code'] ?? '-') ?></td>
                            <td class="text-truncate-2" style="max-width:200px">
                                <?= sanitize($issue['problem_description']) ?>
                            </td>
                            <td>
                                <span class="badge <?= getStatusBadge($issue['status']) ?>">
                                    <?= sanitize($issue['status']) ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($issue['solved']): ?>
                                    <i class="bi bi-check-circle-fill text-success"></i>
                                <?php else: ?>
                                    <i class="bi bi-x-circle-fill text-danger"></i>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
// Prepare chart data from PHP
const catLabels  = <?= json_encode(array_keys($catStats)) ?>;
const catCounts  = <?= json_encode(array_values(array_column(array_values($catStats), 'total'))) ?>;
const catColors  = <?= json_encode(array_values($catColors)) ?>;

const statusLabels = <?= json_encode(array_column($statusData, 'status')) ?>;
const statusCounts = <?= json_encode(array_map('intval', array_column($statusData, 'cnt'))) ?>;

const monthLabels  = <?= json_encode(array_column($monthlyData, 'month_label')) ?>;
const monthCounts  = <?= json_encode(array_map('intval', array_column($monthlyData, 'cnt'))) ?>;
const monthValues  = <?= json_encode(array_column($monthlyData, 'month_val')) ?>;

$(function() {
    IT.buildCategoryChart('categoryChart', {
        labels: catLabels,
        counts: catCounts.map(Number),
        colors: catColors
    }, IT.baseUrl);

    IT.buildStatusChart('statusChart', {
        labels: statusLabels,
        counts: statusCounts
    });

    IT.buildMonthlyChart('monthlyChart', {
        labels: monthLabels,
        counts: monthCounts,
        monthValues: monthValues
    });
});
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
