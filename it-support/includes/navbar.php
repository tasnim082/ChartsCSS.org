<?php $currentUser = currentUser(); ?>
<nav class="navbar navbar-expand-lg navbar-dark bg-primary sticky-top py-1">
    <div class="container-fluid">
        <a class="navbar-brand fw-bold fs-6" href="<?= BASE_URL ?>index.php">
            <i class="bi bi-pc-display-horizontal me-1"></i>
            <span class="d-none d-sm-inline"><?= APP_NAME ?></span>
            <span class="d-inline d-sm-none">IT Support</span>
        </a>
        <button class="navbar-toggler navbar-toggler-sm" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="mainNav">
            <ul class="navbar-nav me-auto mb-0">
                <li class="nav-item">
                    <a class="nav-link py-1" href="<?= BASE_URL ?>index.php">
                        <i class="bi bi-speedometer2 me-1"></i>Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link py-1" href="<?= BASE_URL ?>problems/list.php">
                        <i class="bi bi-list-ul me-1"></i>Issues
                    </a>
                </li>
                <?php if (isLoggedIn()): ?>
                <li class="nav-item">
                    <a class="nav-link py-1" href="<?= BASE_URL ?>problems/add.php">
                        <i class="bi bi-plus-circle me-1"></i>New Issue
                    </a>
                </li>
                <?php endif; ?>
                <li class="nav-item">
                    <a class="nav-link py-1" href="<?= BASE_URL ?>reports/index.php">
                        <i class="bi bi-file-earmark-bar-graph me-1"></i>Reports
                    </a>
                </li>
                <?php if (isAdmin()): ?>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle py-1" href="#" data-bs-toggle="dropdown">
                        <i class="bi bi-shield-lock me-1"></i>Admin
                    </a>
                    <ul class="dropdown-menu dropdown-menu-dark">
                        <li><a class="dropdown-item" href="<?= BASE_URL ?>admin/users.php">
                            <i class="bi bi-people me-1"></i>Manage Users</a></li>
                        <li><a class="dropdown-item" href="<?= BASE_URL ?>admin/branches.php">
                            <i class="bi bi-building me-1"></i>Manage Branches</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="<?= BASE_URL ?>admin/audit_logs.php">
                            <i class="bi bi-clock-history me-1"></i>Audit Logs</a></li>
                    </ul>
                </li>
                <?php endif; ?>
            </ul>
            <ul class="navbar-nav ms-auto align-items-center mb-0">
                <!-- Theme toggle -->
                <li class="nav-item me-1">
                    <button class="btn btn-sm btn-outline-light py-0 px-2" id="themeToggle" title="Toggle theme">
                        <i class="bi bi-<?= getTheme() === 'dark' ? 'sun' : 'moon' ?>-fill"></i>
                    </button>
                </li>
                <?php if (isLoggedIn()): ?>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle py-1" href="#" data-bs-toggle="dropdown">
                        <i class="bi bi-person-circle me-1"></i>
                        <span class="d-none d-sm-inline"><?= sanitize($currentUser['name']) ?></span>
                        <small class="badge bg-warning text-dark ms-1"><?= strtoupper($currentUser['role']) ?></small>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end dropdown-menu-dark">
                        <li><a class="dropdown-item" href="<?= BASE_URL ?>profile.php">
                            <i class="bi bi-person me-1"></i>Profile</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-danger" href="<?= BASE_URL ?>logout.php">
                            <i class="bi bi-box-arrow-right me-1"></i>Logout</a></li>
                    </ul>
                </li>
                <?php else: ?>
                <li class="nav-item">
                    <a class="nav-link py-1" href="<?= BASE_URL ?>login.php">
                        <i class="bi bi-box-arrow-in-right me-1"></i>Login
                    </a>
                </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>
