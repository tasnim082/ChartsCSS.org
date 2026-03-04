<?php
require_once __DIR__ . '/../config.php';
requireAdmin();

$pageTitle = 'Audit Logs';
$db = getDB();

// Filters
$filterIssue = (int)($_GET['issue_id'] ?? 0);
$filterUser  = (int)($_GET['user_id'] ?? 0);
$filterAction = $_GET['action'] ?? '';
$dateFrom = $_GET['date_from'] ?? '';
$dateTo   = $_GET['date_to'] ?? '';
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 30;

$where = ['1=1'];
$params = [];

if ($filterIssue) {
    $where[] = 'al.issue_id = ?';
    $params[] = $filterIssue;
}
if ($filterUser) {
    $where[] = 'al.updated_by = ?';
    $params[] = $filterUser;
}
if ($filterAction) {
    $where[] = 'al.action = ?';
    $params[] = $filterAction;
}
if ($dateFrom) {
    $where[] = 'DATE(al.updated_at) >= ?';
    $params[] = $dateFrom;
}
if ($dateTo) {
    $where[] = 'DATE(al.updated_at) <= ?';
    $params[] = $dateTo;
}

$whereSQL = implode(' AND ', $where);

$total = $db->prepare("SELECT COUNT(*) FROM audit_logs al WHERE $whereSQL");
$total->execute($params);
$total = $total->fetchColumn();
$totalPages = max(1, ceil($total / $perPage));
$offset = ($page - 1) * $perPage;

$stmt = $db->prepare("
    SELECT al.*, i.category
    FROM audit_logs al
    LEFT JOIN issues i ON al.issue_id = i.id
    WHERE $whereSQL
    ORDER BY al.updated_at DESC
    LIMIT $perPage OFFSET $offset
");
$stmt->execute($params);
$logs = $stmt->fetchAll();

$users = $db->query("SELECT id, full_name FROM users ORDER BY full_name")->fetchAll();

$csrf = generateCSRF();
include __DIR__ . '/../includes/header.php';
?>
<script>window.IT = window.IT || {}; window.IT.baseUrl = '<?= BASE_URL ?>';</script>
<?php include __DIR__ . '/../includes/navbar.php'; ?>

<div class="main-content">
    <div class="d-flex align-items-center mb-3">
        <h5 class="mb-0 fw-bold">
            <i class="bi bi-clock-history me-1 text-warning"></i>Audit Logs
        </h5>
    </div>

    <!-- Filters -->
    <div class="card mb-2">
        <div class="card-body py-2">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-6 col-md-2">
                    <label class="form-label">Issue ID</label>
                    <input type="number" name="issue_id" class="form-control form-control-sm"
                           value="<?= $filterIssue ?: '' ?>" placeholder="Issue #">
                </div>
                <div class="col-6 col-md-3">
                    <label class="form-label">User</label>
                    <select name="user_id" class="form-select form-select-sm">
                        <option value="">All</option>
                        <?php foreach ($users as $u): ?>
                        <option value="<?= $u['id'] ?>" <?= $filterUser == $u['id'] ? 'selected' : '' ?>>
                            <?= sanitize($u['full_name']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label">Action</label>
                    <select name="action" class="form-select form-select-sm">
                        <option value="">All</option>
                        <?php foreach (['created','updated','status_changed','solved'] as $a): ?>
                        <option value="<?= $a ?>" <?= $filterAction === $a ? 'selected' : '' ?>><?= ucfirst(str_replace('_',' ',$a)) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label">Date From</label>
                    <input type="date" name="date_from" class="form-control form-control-sm" value="<?= $dateFrom ?>">
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label">Date To</label>
                    <input type="date" name="date_to" class="form-control form-control-sm" value="<?= $dateTo ?>">
                </div>
                <div class="col-6 col-md-1 d-flex gap-1">
                    <button type="submit" class="btn btn-primary btn-sm flex-fill"><i class="bi bi-search"></i></button>
                    <a href="<?= BASE_URL ?>admin/audit_logs.php" class="btn btn-outline-secondary btn-sm flex-fill"><i class="bi bi-x"></i></a>
                </div>
            </form>
        </div>
    </div>

    <!-- Logs Table -->
    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm table-hover table-striped mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th>#</th>
                            <th>Issue</th>
                            <th>Category</th>
                            <th>Action</th>
                            <th>Field Changed</th>
                            <th>Old Value</th>
                            <th>New Value</th>
                            <th>Updated By</th>
                            <th>Date/Time</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($logs)): ?>
                        <tr><td colspan="9" class="text-center text-muted py-3">No audit logs found.</td></tr>
                        <?php else: ?>
                        <?php foreach ($logs as $log):
                            $actionColors = [
                                'created' => 'bg-success', 'updated' => 'bg-primary',
                                'status_changed' => 'bg-warning text-dark', 'solved' => 'bg-info text-dark',
                            ];
                            $badgeClass = $actionColors[$log['action']] ?? 'bg-secondary';
                        ?>
                        <tr>
                            <td><?= $log['id'] ?></td>
                            <td>
                                <a href="<?= BASE_URL ?>problems/view.php?id=<?= $log['issue_id'] ?>" class="text-decoration-none">
                                    #<?= $log['issue_id'] ?>
                                </a>
                            </td>
                            <td><?= sanitize($log['category'] ?? '-') ?></td>
                            <td>
                                <span class="badge <?= $badgeClass ?>">
                                    <?= sanitize(str_replace('_', ' ', $log['action'])) ?>
                                </span>
                            </td>
                            <td><?= sanitize($log['field_changed'] ? str_replace('_', ' ', $log['field_changed']) : '-') ?></td>
                            <td style="max-width:120px" class="text-truncate text-muted fs-xs">
                                <?= sanitize($log['old_value'] ?? '-') ?>
                            </td>
                            <td style="max-width:120px" class="text-truncate fs-xs">
                                <?= sanitize($log['new_value'] ?? '-') ?>
                            </td>
                            <td><?= sanitize($log['updated_by_name']) ?></td>
                            <td class="fs-xs"><?= date('d/m/Y H:i:s', strtotime($log['updated_at'])) ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php if ($totalPages > 1): ?>
        <div class="card-footer py-2">
            <nav>
                <ul class="pagination pagination-sm mb-0 justify-content-center">
                    <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                    <li class="page-item <?= $p === $page ? 'active' : '' ?>">
                        <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $p])) ?>"><?= $p ?></a>
                    </li>
                    <?php endfor; ?>
                </ul>
            </nav>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
