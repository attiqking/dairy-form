<?php
require_once __DIR__ . '/../includes/bootstrap.php';



if (!$auth->isLoggedIn()) {
    header("Location: " . BASE_URL . "/auth/login.php");
    exit();
}


$conn = $database->getConnection();

// Handle record deletion
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM health_records WHERE id = ?");
    $stmt->execute([$id]);
    header("Location: " . BASE_URL . "/health/");
    exit();
}

// Fetch all health records with animal details
$records = [];
$query = "SELECT h.id, h.record_type, h.date, h.description, h.treatment, h.next_followup, a.tag_number, a.breed 
          FROM health_records h 
          JOIN animals a ON h.animal_id = a.id 
          ORDER BY h.date DESC";
$result = $conn->query($query);
while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
    $records[] = $row;
}
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Health Records</h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <a href="<?php echo BASE_URL; ?>/health/add.php" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-plus-circle"></i> Add Record
            </a>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-striped table-hover">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Animal</th>
                    <th>Breed</th>
                    <th>Type</th>
                    <th>Description</th>
                    <th>Next Follow-up</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($records as $record): ?>
                <tr>
                    <td><?php echo date('M j, Y', strtotime($record['date'])); ?></td>
                    <td><?php echo htmlspecialchars($record['tag_number']); ?></td>
                    <td><?php echo htmlspecialchars($record['breed']); ?></td>
                    <td><?php echo $record['record_type']; ?></td>
                    <td><?php echo htmlspecialchars($record['description']); ?></td>
                    <td><?php echo $record['next_followup'] ? date('M j, Y', strtotime($record['next_followup'])) : 'N/A'; ?></td>
                    <td>
                        <a href="<?php echo BASE_URL; ?>/health/add.php?id=<?php echo $record['id']; ?>" class="btn btn-sm btn-outline-secondary">
                            <i class="bi bi-pencil"></i> Edit
                        </a>
                        <a href="<?php echo BASE_URL; ?>/health/?delete=<?php echo $record['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Are you sure you want to delete this record?')">
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