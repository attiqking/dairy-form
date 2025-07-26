<?php
// index.php
require_once __DIR__ . '/includes/bootstrap.php';

if ($auth->isLoggedIn()) {
    redirect('dashboard.php');
} else {
    redirect('auth/login.php');
}
?>