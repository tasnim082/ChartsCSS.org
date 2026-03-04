<!DOCTYPE html>
<html lang="en" data-bs-theme="<?= htmlspecialchars(getTheme()) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($pageTitle) ? sanitize($pageTitle) . ' - ' : '' ?><?= APP_NAME ?></title>
    <!-- Bootstrap CSS (Offline) -->
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/vendor/bootstrap/css/bootstrap.min.css">
    <!-- Bootstrap Icons (Offline) -->
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/vendor/bootstrap/css/bootstrap-icons.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/app.css">
</head>
<body class="<?= getTheme() === 'dark' ? 'theme-dark' : 'theme-light' ?>">
