<?php
// dashboard.php
require_once __DIR__ . '/includes/bootstrap.php';
redirectIfNotLoggedIn();

$pageTitle = "Dashboard";
require_once __DIR__ . '/includes/header.php';

if (isAdmin()) {
    require_once __DIR__ . '/admin/dashboard.php';
} else {
    require_once __DIR__ . '/user/dashboard.php';
}

require_once __DIR__ . '/includes/footer.php';
?>