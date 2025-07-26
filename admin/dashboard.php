<?php
// admin/dashboard.php

require_once __DIR__ . '/../includes/bootstrap.php';


$auth->requirePermission(3); // Require admin privileges

$pageTitle = "Admin Dashboard";
require_once __DIR__ . '/../includes/header.php';


$pdo = $database->getConnection();

// Fetch user statistics
$users = [];
$query = "SELECT role, COUNT(*) as count FROM users GROUP BY role";
$stmt = $pdo->query($query);
$users = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

// Fetch animal statistics
$animals = [];
$query = "SELECT status, COUNT(*) as count FROM animals GROUP BY status";
$stmt = $pdo->query($query);
$animals = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

// Fetch recent activities
$activities = [];
$query = "SELECT a.*, u.username 
          FROM activities a 
          JOIN users u ON a.user_id = u.id 
          ORDER BY a.created_at DESC 
          LIMIT 5";
$stmt = $pdo->query($query);
$activities = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Admin Dashboard</h1>
        <span class="badge bg-primary"><?php echo date('M j, Y'); ?></span>
    </div>

    <!-- Summary Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card text-white bg-primary mb-3">
                <div class="card-body">
                    <h5 class="card-title">Total Users</h5>
                    <p class="card-text h2"><?php echo array_sum($users); ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-white bg-success mb-3">
                <div class="card-body">
                    <h5 class="card-title">Total Animals</h5>
                    <p class="card-text h2"><?php echo array_sum($animals); ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-white bg-info mb-3">
                <div class="card-body">
                    <h5 class="card-title">Active Animals</h5>
                    <p class="card-text h2"><?php echo $animals['Active'] ?? 0; ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-white bg-warning mb-3">
                <div class="card-body">
                    <h5 class="card-title">Recent Activities</h5>
                    <p class="card-text h2"><?php echo count($activities); ?></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Row -->
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Users by Role</h5>
                </div>
                <div class="card-body">
                    <canvas id="usersChart" height="250"></canvas>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">Animals by Status</h5>
                </div>
                <div class="card-body">
                    <canvas id="animalsChart" height="250"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Activities -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-dark text-white">
                    <h5 class="mb-0">Recent Activities</h5>
                </div>
                <div class="card-body">
                    <?php if (!empty($activities)): ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>User</th>
                                    <th>Action</th>
                                    <th>Details</th>
                                    <th>Time</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($activities as $activity): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($activity['username']); ?></td>
                                    <td><?php echo htmlspecialchars($activity['action']); ?></td>
                                    <td><?php echo htmlspecialchars($activity['details']); ?></td>
                                    <td><?php echo date('M j, H:i', strtotime($activity['created_at'])); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                    <div class="alert alert-info">No recent activities found.</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-secondary text-white">
                    <h5 class="mb-0">Quick Actions</h5>
                </div>
                <div class="card-body">
                    <div class="d-flex flex-wrap gap-2">
                        <a href="<?php echo BASE_URL; ?>/admin/users/" class="btn btn-primary">
                            <i class="bi bi-people-fill"></i> Manage Users
                        </a>
                        <a href="<?php echo BASE_URL; ?>/admin/animals/" class="btn btn-success">
                            <i class="bi bi-heart-fill"></i> Manage Animals
                        </a>
                        <a href="<?php echo BASE_URL; ?>/admin/reports/" class="btn btn-info">
                            <i class="bi bi-graph-up"></i> View Reports
                        </a>
                        <a href="<?php echo BASE_URL; ?>/admin/settings/" class="btn btn-warning">
                            <i class="bi bi-gear-fill"></i> System Settings
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Chart.js Script -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Users chart
    const usersCtx = document.getElementById('usersChart').getContext('2d');
    new Chart(usersCtx, {
        type: 'doughnut',
        data: {
            labels: ['Admin', 'Manager', 'Worker'],
            datasets: [{
                data: [
                    <?php echo $users['Admin'] ?? 0; ?>,
                    <?php echo $users['Manager'] ?? 0; ?>,
                    <?php echo $users['Worker'] ?? 0; ?>
                ],
                backgroundColor: [
                    'rgba(13, 110, 253, 0.7)',
                    'rgba(25, 135, 84, 0.7)',
                    'rgba(255, 193, 7, 0.7)'
                ],
                borderColor: [
                    'rgba(13, 110, 253, 1)',
                    'rgba(25, 135, 84, 1)',
                    'rgba(255, 193, 7, 1)'
                ],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });

    // Animals chart
    const animalsCtx = document.getElementById('animalsChart').getContext('2d');
    new Chart(animalsCtx, {
        type: 'pie',
        data: {
            labels: ['Active', 'Inactive', 'Recovering', 'Pregnant'],
            datasets: [{
                data: [
                    <?php echo $animals['Active'] ?? 0; ?>,
                    <?php echo $animals['Inactive'] ?? 0; ?>,
                    <?php echo $animals['Recovering'] ?? 0; ?>,
                    <?php echo $animals['Pregnant'] ?? 0; ?>
                ],
                backgroundColor: [
                    'rgba(25, 135, 84, 0.7)',
                    'rgba(108, 117, 125, 0.7)',
                    'rgba(255, 193, 7, 0.7)',
                    'rgba(13, 202, 240, 0.7)'
                ],
                borderColor: [
                    'rgba(25, 135, 84, 1)',
                    'rgba(108, 117, 125, 1)',
                    'rgba(255, 193, 7, 1)',
                    'rgba(13, 202, 240, 1)'
                ],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });
});
</script>

<?php 
require_once __DIR__ . '/../includes/footer.php';
?>