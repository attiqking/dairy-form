<?php
// user/dashboard.php

// Include necessary files
require_once __DIR__ . '/../includes/bootstrap.php'; // Loads all dependencies

// Check authentication
redirectIfNotLoggedIn();

// Set page title
$pageTitle = "User Dashboard";

// Include header
require_once __DIR__ . '/../includes/header.php';

// Get database connection
$pdo = $database->getConnection();

// Get user-specific data
$user_id = $_SESSION['user_id'];
$today = date('Y-m-d');

// Get today's milk production records for this user
$milk_production = [];
$query = "SELECT a.tag_number, m.session, m.quantity 
          FROM milk_production m
          JOIN animals a ON m.animal_id = a.id
          WHERE m.recorded_by = ? AND m.date = ?
          ORDER BY m.session";
$stmt = $pdo->prepare($query);
$stmt->execute([$user_id, $today]);
$milk_production = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get assigned animals
$animals = [];
$query = "SELECT a.id, a.tag_number, a.breed, a.status 
          FROM animals a
          JOIN animal_assignments aa ON a.id = aa.animal_id
          WHERE aa.user_id = ? AND aa.end_date IS NULL";
$stmt = $pdo->prepare($query);
$stmt->execute([$user_id]);
$animals = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get health alerts for assigned animals
$health_alerts = [];
$query = "SELECT h.record_date as date, h.description, a.tag_number 
          FROM health_records h
          JOIN animals a ON h.animal_id = a.id
          JOIN animal_assignments aa ON a.id = aa.animal_id
          WHERE aa.user_id = ? AND h.next_action_date <= DATE_ADD(CURDATE(), INTERVAL 3 DAY)
          ORDER BY h.next_action_date";
$stmt = $pdo->prepare($query);
$stmt->execute([$user_id]);
$health_alerts = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container">
    <h1>Welcome, <?php echo htmlspecialchars($_SESSION['full_name'] ?? 'User'); ?></h1>
    
    <div class="dashboard-content">
        <!-- Summary Cards -->
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="card text-white bg-primary mb-3">
                    <div class="card-body">
                        <h5 class="card-title">Assigned Animals</h5>
                        <p class="card-text h2"><?php echo count($animals); ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card text-white bg-success mb-3">
                    <div class="card-body">
                        <h5 class="card-title">Today's Milk Production</h5>
                        <p class="card-text h2">
                            <?php 
                            $total = array_sum(array_column($milk_production, 'quantity'));
                            echo number_format($total, 2) . ' L';
                            ?>
                        </p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card text-white bg-info mb-3">
                    <div class="card-body">
                        <h5 class="card-title">Health Alerts</h5>
                        <p class="card-text h2"><?php echo count($health_alerts); ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Quick Actions</h5>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2 d-md-flex">
                    <a href="<?php echo BASE_URL; ?>/animals/" class="btn btn-primary me-md-2">
                        <i class="bi bi-list-ul"></i> View Animals
                    </a>
                    <a href="<?php echo BASE_URL; ?>/milk/add.php" class="btn btn-success me-md-2">
                        <i class="bi bi-plus-circle"></i> Add Milk Record
                    </a>
                    <a href="<?php echo BASE_URL; ?>/health/" class="btn btn-info">
                        <i class="bi bi-heart-pulse"></i> Health Records
                    </a>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="row">
            <div class="col-md-6">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5>Today's Milk Production</h5>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($milk_production)): ?>
                        <div class="table-responsive">
                            <table class="table table-sm table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>Animal</th>
                                        <th>Session</th>
                                        <th>Quantity (L)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($milk_production as $record): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($record['tag_number']); ?></td>
                                        <td><?php echo ucfirst($record['session']); ?></td>
                                        <td><?php echo number_format($record['quantity'], 2); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                    <tr class="table-secondary fw-bold">
                                        <td colspan="2">Total</td>
                                        <td><?php echo number_format(array_sum(array_column($milk_production, 'quantity')), 2); ?> L</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <?php else: ?>
                        <div class="alert alert-info">
                            <p class="mb-0">No milk production recorded today.</p>
                        </div>
                        <a href="<?php echo BASE_URL; ?>/milk/add.php" class="btn btn-sm btn-primary">
                            <i class="bi bi-plus-circle"></i> Add Milk Record
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5>Assigned Animals</h5>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($animals)): ?>
                        <div class="table-responsive">
                            <table class="table table-sm table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>Tag Number</th>
                                        <th>Breed</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($animals as $animal): ?>
                                    <tr>
                                        <td>
                                            <a href="<?php echo BASE_URL; ?>/animals/view.php?id=<?php echo $animal['id']; ?>" class="text-decoration-none">
                                                <?php echo htmlspecialchars($animal['tag_number']); ?>
                                            </a>
                                        </td>
                                        <td><?php echo htmlspecialchars($animal['breed']); ?></td>
                                        <td>
                                            <span class="badge rounded-pill bg-<?php 
                                                switch($animal['status']) {
                                                    case 'Active': echo 'success'; break;
                                                    case 'Inactive': echo 'secondary'; break;
                                                    case 'Recovering': echo 'warning'; break;
                                                    case 'Pregnant': echo 'info'; break;
                                                    case 'Sick': echo 'danger'; break;
                                                    default: echo 'light text-dark';
                                                }
                                            ?>">
                                                <?php echo $animal['status']; ?>
                                            </span>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php else: ?>
                        <div class="alert alert-warning">
                            <p class="mb-0">No animals assigned to you.</p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <?php if (!empty($health_alerts)): ?>
        <div class="row">
            <div class="col-12">
                <div class="card mb-4 border-warning">
                    <div class="card-header bg-warning text-dark">
                        <h5 class="mb-0">Health Alerts <span class="badge bg-danger"><?php echo count($health_alerts); ?></span></h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm table-hover">
                                <thead class="table-warning">
                                    <tr>
                                        <th>Date</th>
                                        <th>Animal</th>
                                        <th>Description</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($health_alerts as $alert): ?>
                                    <tr>
                                        <td><?php echo date('M j, Y', strtotime($alert['date'])); ?></td>
                                        <td>
                                            <a href="<?php echo BASE_URL; ?>/animals/view.php?tag=<?php echo urlencode($alert['tag_number']); ?>" class="text-decoration-none">
                                                <?php echo htmlspecialchars($alert['tag_number']); ?>
                                            </a>
                                        </td>
                                        <td><?php echo htmlspecialchars($alert['description']); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php 
// Include footer
require_once __DIR__ . '/../includes/footer.php'; 
?>