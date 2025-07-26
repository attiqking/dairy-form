<?php
require_o// Fetch all milk production records with animal details
$records = [];
$query = "SELECT m.id, m.date, m.session, m.quantity, m.notes, a.tag_number, a.breed 
          FROM milk_production m 
          JOIN animals a ON m.animal_id = a.id 
          ORDER BY m.date DESC, m.session DESC";
$result = $conn->query($query);
while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
    $records[] = $row;
}

$pageTitle = "Milk Production";
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Milk Production</h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <a href="<?php echo BASE_URL; ?>/milk/add.php" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-plus-circle"></i> Add Record
            </a>includes/bootstrap.php';
redirectIfNotLoggedIn();

$conn = $database->getConnection();

// Handle record deletion
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM milk_production WHERE id = ?");
    $stmt->execute([$id]);
    header("Location: " . BASE_URL . "/milk/");
    exit();
}

// Fetch all milk production records with animal details
$records = [];
$query = "SELECT m.id, m.date, m.session, m.quantity, m.notes, a.tag_number, a.breed 
          FROM milk_production m 
          JOIN animals a ON m.animal_id = a.id 
          ORDER BY m.date DESC, m.session DESC";
$result = $conn->query($query);
while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
    $records[] = $row;
}
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Milk Production</h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <a href="<?php echo BASE_URL; ?>/milk/add.php" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-plus-circle"></i> Add Record
            </a>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-striped table-hover">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Session</th>
                    <th>Animal</th>
                    <th>Breed</th>
                    <th>Quantity (liters)</th>
                    <th>Notes</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($records as $record): ?>
                <tr>
                    <td><?php echo date('M j, Y', strtotime($record['date'])); ?></td>
                    <td><?php echo $record['session']; ?></td>
                    <td><?php echo htmlspecialchars($record['tag_number']); ?></td>
                    <td><?php echo htmlspecialchars($record['breed']); ?></td>
                    <td><?php echo number_format($record['quantity'], 2); ?></td>
                    <td><?php echo htmlspecialchars($record['notes'] ?? 'N/A'); ?></td>
                    <td>
                        <a href="<?php echo BASE_URL; ?>/milk/add.php?id=<?php echo $record['id']; ?>" class="btn btn-sm btn-outline-secondary">
                            <i class="bi bi-pencil"></i> Edit
                        </a>
                        <a href="<?php echo BASE_URL; ?>/milk/?delete=<?php echo $record['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Are you sure you want to delete this record?')">
                            <i class="bi bi-trash"></i> Delete
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>