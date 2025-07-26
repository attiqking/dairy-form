<?php
// includes/navigation.php
$current_page = basename($_SERVER['PHP_SELF']);
?>
<nav class="navbar navbar-dark bg-dark flex-md-nowrap p-0 shadow">
    <a class="navbar-brand col-md-3 col-lg-2 me-0 px-3" href="<?php echo BASE_URL; ?>/dashboard.php">DairyFarm Pro</a>
    <button class="navbar-toggler position-absolute d-md-none collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#sidebarMenu">
        <span class="navbar-toggler-icon"></span>
    </button>
    <ul class="navbar-nav px-3">
        <li class="nav-item text-nowrap">
            <a class="nav-link" href="<?php echo BASE_URL; ?>/auth/logout.php">Sign out</a>
        </li>
    </ul>
</nav>

<div class="container-fluid">
    <div class="row">
        <nav id="sidebarMenu" class="col-md-3 col-lg-2 d-md-block bg-dark sidebar collapse">
            <div class="position-sticky pt-3">
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a class="nav-link <?php echo $current_page === 'dashboard.php' ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>/dashboard.php">
                            <i class="bi bi-speedometer2 me-2"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo in_array($current_page, ['index.php', 'add.php', 'view.php']) ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>/animals/">
                            <i class="bi bi-egg-fried me-2"></i> Animals
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo in_array($current_page, ['index.php', 'add.php', 'production.php']) ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>/milk/">
                            <i class="bi bi-droplet me-2"></i> Milk Production
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo in_array($current_page, ['index.php', 'add.php', 'vaccinations.php']) ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>/health/">
                            <i class="bi bi-heart-pulse me-2"></i> Health Records
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo in_array($current_page, ['expenses.php', 'payments.php', 'reports.php']) ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>/finances/">
                            <i class="bi bi-cash-stack me-2"></i> Finances
                        </a>
                    </li>
                    <?php if ($_SESSION['role'] === 'Admin'): ?>
                    <li class="nav-item">
                        <a class="nav-link <?php echo strpos($_SERVER['REQUEST_URI'], '/admin/') !== false ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>/admin/">
                            <i class="bi bi-shield-lock me-2"></i> Admin
                        </a>
                    </li>
                    <?php endif; ?>
                </ul>
            </div>
        </nav>
    </div>
</div>