<?php
require_once __DIR__ . '/../includes/bootstrap.php';



if (!$auth->isLoggedIn()) {
    header("Location: " . BASE_URL . "/auth/login.php");
    exit();
}


$conn = $database->getConnection();

// Handle animal deletion
if (isset($_GET['delete']) {
    $id = (int)$_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM animals WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    header("Location: " . BASE_URL . "/animals/");
    exit();
}

// Fetch all animals
$animals = [];
$query = "SELECT * FROM animals ORDER BY created_at DESC";
$result = $conn->query($query);
while ($row = $result->fetch_assoc()) {
    $animals[] = $row;
}
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Animals</h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <a href="<?php echo BASE_URL; ?>/animals/add.php" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-plus-circle"></i> Add Animal
            </a>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-striped table-hover">
            <thead>
                <tr>
                    <th>Tag Number</th>
                    <th>Breed</th>
                    <th>Date of Birth</th>
                    <th>Purchase Date</th>
                    <th>Purchase Price</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($animals as $animal): ?>
                <tr>
                    <td><?php echo htmlspecialchars($animal['tag_number']); ?></td>
                    <td><?php echo htmlspecialchars($animal['breed']); ?></td>
                    <td><?php echo $animal['date_of_birth'] ? date('M j, Y', strtotime($animal['date_of_birth'])) : 'N/A'; ?></td>
                    <td><?php echo date('M j, Y', strtotime($animal['purchase_date'])); ?></td>
                    <td>$<?php echo number_format($animal['purchase_price'], 2); ?></td>
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
                    <td>
                        <a href="<?php echo BASE_URL; ?>/animals/view.php?id=<?php echo $animal['id']; ?>" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-eye"></i> View
                        </a>
                        <a href="<?php echo BASE_URL; ?>/animals/add.php?id=<?php echo $animal['id']; ?>" class="btn btn-sm btn-outline-secondary">
                            <i class="bi bi-pencil"></i> Edit
                        </a>
                        <a href="<?php echo BASE_URL; ?>/animals/?delete=<?php echo $animal['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Are you sure you want to delete this animal?')">
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