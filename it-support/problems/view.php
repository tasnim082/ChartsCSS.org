<?php
require_once __DIR__ . '/../config.php';

$id = (int)($_GET['id'] ?? 0);
if (!$id) { header('Location: ' . BASE_URL . 'problems/list.php'); exit; }

$db = getDB();
$stmt = $db->prepare("
    SELECT i.*, b.branch_name, b.branch_code, b.branch_type,
           cu.full_name as created_by_name, uu.full_name as updated_by_name
    FROM issues i
    LEFT JOIN branches b ON i.branch_id = b.id
    LEFT JOIN users cu ON i.created_by = cu.id
    LEFT JOIN users uu ON i.last_updated_by = uu.id
    WHERE i.id = ?
");
$stmt->execute([$id]);
$issue = $stmt->fetch();
if (!$issue) { header('Location: ' . BASE_URL . 'problems/list.php?error=notfound'); exit; }

// Audit timeline
$auditStmt = $db->prepare("SELECT * FROM audit_logs WHERE issue_id = ? ORDER BY updated_at DESC");
$auditStmt->execute([$id]);
$auditLogs = $auditStmt->fetchAll();

$catColors = [
    'PC'=>'#4e73df','UPS'=>'#1cc88a','CCTV'=>'#36b9cc',
    'Printer'=>'#f6c23e','IP Phone'=>'#e74a3b','Hardware'=>'#6f42c1','Others'=>'#858796',
];

$pageTitle = 'Issue #' . $id;
include __DIR__ . '/../includes/header.php';
?>
<script>window.IT = window.IT || {}; window.IT.baseUrl = '<?= BASE_URL ?>';</script>
<?php include __DIR__ . '/../includes/navbar.php'; ?>

<div class="main-content">
    <!-- Header -->
    <div class="d-flex align-items-center justify-content-between mb-3">
        <div class="d-flex align-items-center">
            <a href="<?= BASE_URL ?>problems/list.php" class="btn btn-sm btn-outline-secondary me-2">
                <i class="bi bi-arrow-left"></i>
            </a>
            <h5 class="mb-0 fw-bold">
                <i class="bi bi-eye me-1 text-info"></i>Issue #<?= $id ?>
            </h5>
            <span class="ms-2 badge" style="background:<?= $catColors[$issue['category']] ?? '#858796' ?>">
                <?= sanitize($issue['category']) ?>
            </span>
            <span class="ms-1 badge <?= getStatusBadge($issue['status']) ?>">
                <?= sanitize($issue['status']) ?>
            </span>
        </div>
        <?php if (isLoggedIn() && (isAdmin() || canEditCategory($issue['category']))): ?>
        <a href="<?= BASE_URL ?>problems/edit.php?id=<?= $id ?>" class="btn btn-warning btn-sm">
            <i class="bi bi-pencil me-1"></i>Edit
        </a>
        <?php endif; ?>
    </div>

    <?php if (isset($_GET['created'])): ?>
    <div class="alert alert-success alert-auto-dismiss py-2">
        <i class="bi bi-check-circle me-1"></i>Issue created successfully.
    </div>
    <?php endif; ?>
    <?php if (isset($_GET['updated'])): ?>
    <div class="alert alert-info alert-auto-dismiss py-2">
        <i class="bi bi-info-circle me-1"></i>Issue updated successfully.
    </div>
    <?php endif; ?>

    <div class="row g-2">
        <!-- Main Details -->
        <div class="col-12 col-md-8">
            <div class="card mb-2">
                <div class="card-header py-2 px-3 fw-semibold fs-xs text-uppercase">
                    <i class="bi bi-info-circle me-1"></i>Issue Details
                </div>
                <div class="card-body p-0">
                    <table class="table table-sm table-borderless mb-0">
                        <tbody>
                            <tr>
                                <th width="35%" class="ps-3 text-muted fs-xs">Issue ID</th>
                                <td><?= $id ?></td>
                                <th class="text-muted fs-xs">Issue Date</th>
                                <td><?= date('d/m/Y', strtotime($issue['issue_date'])) ?></td>
                            </tr>
                            <tr>
                                <th class="ps-3 text-muted fs-xs">Category</th>
                                <td>
                                    <span class="badge" style="background:<?= $catColors[$issue['category']] ?? '#858796' ?>">
                                        <?= sanitize($issue['category']) ?>
                                    </span>
                                </td>
                                <th class="text-muted fs-xs">Status</th>
                                <td>
                                    <span class="badge <?= getStatusBadge($issue['status']) ?>">
                                        <?= sanitize($issue['status']) ?>
                                    </span>
                                </td>
                            </tr>
                            <tr>
                                <th class="ps-3 text-muted fs-xs">Vendor Name</th>
                                <td><?= sanitize($issue['vendor_name'] ?? '-') ?></td>
                                <th class="text-muted fs-xs">Solved</th>
                                <td>
                                    <?php if ($issue['solved']): ?>
                                    <span class="badge bg-success"><i class="bi bi-check me-1"></i>Yes</span>
                                    <?php else: ?>
                                    <span class="badge bg-danger"><i class="bi bi-x me-1"></i>No</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <tr>
                                <th class="ps-3 text-muted fs-xs">Branch</th>
                                <td><?= $issue['branch_code'] ? sanitize($issue['branch_code'] . ' - ' . $issue['branch_name']) : '-' ?></td>
                                <th class="text-muted fs-xs">Branch Type</th>
                                <td><?= sanitize($issue['branch_type'] ?? '-') ?></td>
                            </tr>
                            <tr>
                                <th class="ps-3 text-muted fs-xs">Branch Officer</th>
                                <td><?= sanitize($issue['branch_officer'] ?? '-') ?></td>
                                <th class="text-muted fs-xs">Contact/IP Phone</th>
                                <td><?= sanitize($issue['contact_ipphone'] ?? '-') ?></td>
                            </tr>
                            <tr>
                                <th class="ps-3 text-muted fs-xs">IT Officer</th>
                                <td><?= sanitize($issue['officer_it'] ?? '-') ?></td>
                                <th class="text-muted fs-xs">Manager</th>
                                <td><?= sanitize($issue['manager_name'] ?? '-') ?></td>
                            </tr>
                            <tr>
                                <th class="ps-3 text-muted fs-xs">Created By</th>
                                <td><?= sanitize($issue['created_by_name'] ?? '-') ?></td>
                                <th class="text-muted fs-xs">Created At</th>
                                <td><?= date('d/m/Y H:i', strtotime($issue['created_at'])) ?></td>
                            </tr>
                            <tr>
                                <th class="ps-3 text-muted fs-xs">Last Updated By</th>
                                <td><?= sanitize($issue['updated_by_name'] ?? '-') ?></td>
                                <th class="text-muted fs-xs">Last Updated At</th>
                                <td><?= $issue['last_updated_at'] ? date('d/m/Y H:i', strtotime($issue['last_updated_at'])) : '-' ?></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Problem Description -->
            <div class="card mb-2">
                <div class="card-header py-2 px-3 fw-semibold fs-xs text-uppercase">
                    <i class="bi bi-chat-text me-1 text-danger"></i>Problem Description
                </div>
                <div class="card-body">
                    <p class="mb-0"><?= nl2br(sanitize($issue['problem_description'])) ?></p>
                </div>
            </div>

            <!-- Remarks -->
            <?php if ($issue['remarks']): ?>
            <div class="card">
                <div class="card-header py-2 px-3 fw-semibold fs-xs text-uppercase">
                    <i class="bi bi-chat-dots me-1 text-secondary"></i>Remarks
                </div>
                <div class="card-body">
                    <p class="mb-0 text-muted"><?= nl2br(sanitize($issue['remarks'])) ?></p>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Audit Timeline -->
        <div class="col-12 col-md-4">
            <div class="card h-100">
                <div class="card-header py-2 px-3 fw-semibold fs-xs text-uppercase">
                    <i class="bi bi-clock-history me-1 text-warning"></i>Activity Timeline
                </div>
                <div class="card-body p-3" style="max-height:500px;overflow-y:auto">
                    <?php if (empty($auditLogs)): ?>
                    <p class="text-muted text-center fs-xs mt-2">No activity recorded.</p>
                    <?php else: ?>
                    <ul class="timeline">
                        <?php foreach ($auditLogs as $log):
                            $iconClass = 'bg-secondary';
                            $icon = 'bi-pencil';
                            if ($log['action'] === 'created') { $iconClass = 'bg-success'; $icon = 'bi-plus'; }
                            if ($log['action'] === 'status_changed') { $iconClass = 'bg-primary'; $icon = 'bi-arrow-left-right'; }
                            if ($log['action'] === 'solved') { $iconClass = 'bg-success'; $icon = 'bi-check'; }
                        ?>
                        <li class="timeline-item">
                            <div class="timeline-icon <?= $iconClass ?> text-white">
                                <i class="bi <?= $icon ?>"></i>
                            </div>
                            <div class="timeline-content">
                                <div class="d-flex justify-content-between">
                                    <strong class="fs-xs">
                                        <?= sanitize($log['updated_by_name']) ?>
                                    </strong>
                                    <span class="timeline-time">
                                        <?= date('d/m/Y H:i', strtotime($log['updated_at'])) ?>
                                    </span>
                                </div>
                                <div class="fs-xs mt-1">
                                    <?php if ($log['action'] === 'created'): ?>
                                        <span class="text-success">Issue created</span>
                                    <?php elseif ($log['field_changed']): ?>
                                        Updated <strong><?= sanitize(str_replace('_', ' ', $log['field_changed'])) ?></strong>
                                        <?php if ($log['old_value'] !== null): ?>
                                        from <em class="text-danger"><?= sanitize($log['old_value']) ?></em>
                                        to <em class="text-success"><?= sanitize($log['new_value']) ?></em>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <?= sanitize($log['new_value'] ?? $log['action']) ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
