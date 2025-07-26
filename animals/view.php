<?php
require_once __DIR__ . '/../includes/bootstrap.php';



if (!$auth->isLoggedIn()) {
    header("Location: " . BASE_URL . "/auth/login.php");
    exit();
}


$conn = $database->getConnection();

if (!isset($_GET['id'])) {
    header("Location: " . BASE_URL . "/animals/");
    exit();
}

$id = (int)$_GET['id'];
$stmt = $conn->prepare("SELECT * FROM animals WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows !== 1) {
    header("Location: " . BASE_URL . "/animals/");
    exit();
}

$animal = $result->fetch_assoc();

// Get health records
$healthRecords = [];
$stmt = $conn->prepare("SELECT * FROM health_records WHERE animal_id = ? ORDER BY date DESC LIMIT 5");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $healthRecords[] = $row;
}

// Get milk production
$milkProduction = [];
$stmt = $conn->prepare("SELECT date, session, quantity FROM milk_production WHERE animal_id = ? ORDER BY date DESC, session DESC LIMIT 10");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $milkProduction[] = $row;
}
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Animal Details: <?php echo htmlspecialchars($animal['tag_number']); ?></h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <a href="<?php echo BASE_URL; ?>/animals/add.php?id=<?php echo $animal['id']; ?>" class="btn btn-sm btn-outline-secondary me-2">
                <i class="bi bi-pencil"></i> Edit
            </a>
            <a href="<?php echo BASE_URL; ?>/animals/" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Back to Animals
            </a>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header">
                    <h5>Basic Information</h5>
                </div>
                <div class="card-body">
                    <table class="table table-sm">
                        <tr>
                            <th>Tag Number</th>
                            <td><?php echo htmlspecialchars($animal['tag_number']); ?></td>
                        </tr>
                        <tr>
                            <th>Breed</th>
                            <td><?php echo htmlspecialchars($animal['breed']); ?></td>
                        </tr>
                        <tr>
                            <th>Date of Birth</th>
                            <td><?php echo $animal['date_of_birth'] ? date('M j, Y', strtotime($animal['date_of_birth'])) : 'N/A'; ?></td>
                        </tr>
                        <tr>
                            <th>Purchase Date</th>
                            <td><?php echo date('M j, Y', strtotime($animal['purchase_date'])); ?></td>
                        </tr>
                        <tr>
                            <th>Purchase Price</th>
                            <td>$<?php echo number_format($animal['purchase_price'], 2); ?></td>
                        </tr>
                        <tr>
                            <th>Status</th>
                            <td>
                                <span class="badge bg-<?php 
                                    switch($animal['status']) {
                                        case 'Active': echo 'success'; break;
                                        case 'Inactive': echo 'secondary'; break;
                                        case 'Recovering': echo 'warning'; break;
                                        case 'Pregnant': echo 'info'; break;
                                        default: echo 'light';
                                    }
                                ?>">
                                    <?php echo $animal['status']; ?>
                                </span>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5>Recent Milk Production</h5>
                        <a href="<?php echo BASE_URL; ?>/milk/add.php?animal_id=<?php echo $animal['id']; ?>" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-plus"></i> Add Record
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <?php if (empty($milkProduction)): ?>
                    <p class="text-muted">No milk production records found.</p>
                    <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Session</th>
                                    <th>Quantity (liters)</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($milkProduction as $record): ?>
                                <tr>
                                    <td><?php echo date('M j, Y', strtotime($record['date'])); ?></td>
                                    <td><?php echo $record['session']; ?></td>
                                    <td><?php echo number_format($record['quantity'], 2); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card mb-4">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5>Recent Health Records</h5>
                        <a href="<?php echo BASE_URL; ?>/health/add.php?animal_id=<?php echo $animal['id']; ?>" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-plus"></i> Add Record
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <?php if (empty($healthRecords)): ?>
                    <p class="text-muted">No health records found.</p>
                    <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Type</th>
                                    <th>Description</th>
                                    <th>Treatment</th>
                                    <th>Next Follow-up</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($healthRecords as $record): ?>
                                <tr>
                                    <td><?php echo date('M j, Y', strtotime($record['date'])); ?></td>
                                    <td><?php echo $record['record_type']; ?></td>
                                    <td><?php echo htmlspecialchars($record['description']); ?></td>
                                    <td><?php echo htmlspecialchars($record['treatment'] ?? 'N/A'); ?></td>
                                    <td><?php echo $record['next_followup'] ? date('M j, Y', strtotime($record['next_followup'])) : 'N/A'; ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>