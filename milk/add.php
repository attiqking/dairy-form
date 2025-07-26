<?php
require_once __DIR__ . '/../includes/bootstrap.php';

redirectIfNotLoggedIn();

$conn = $database->getConnection();

$record = [
    'id' => '',
    'animal_id' => $_GET['animal_id'] ?? '',
    'date' => date('Y-m-d'),
    'session' => 'Morning',
    'quantity' => '',
    'notes' => '',
    'recorded_by' => $_SESSION['user_id']
];

$isEdit = false;
$title = "Add Milk Production Record";

if (isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $stmt = $conn->prepare("SELECT * FROM milk_production WHERE id = ?");
    $stmt->execute([$id]);
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $record = $result->fetch(PDO::FETCH_ASSOC);
        $isEdit = true;
        $title = "Edit Milk Production Record";
    }
}

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $record['animal_id'] = $_POST['animal_id'] ?? '';
    $record['date'] = $_POST['date'] ?? '';
    $record['session'] = $_POST['session'] ?? '';
    $record['quantity'] = $_POST['quantity'] ?? '';
    $record['notes'] = $_POST['notes'] ?? '';
    
    // Validation
    if (empty($record['animal_id'])) {
        $errors['animal_id'] = 'Animal is required';
    }
    
    if (empty($record['date'])) {
        $errors['date'] = 'Date is required';
    }
    
    if (empty($record['quantity'])) {
        $errors['quantity'] = 'Quantity is required';
    } elseif (!is_numeric($record['quantity']) || $record['quantity'] <= 0) {
        $errors['quantity'] = 'Quantity must be a positive number';
    }
    
    if (empty($errors)) {
        if ($isEdit) {
            $stmt = $conn->prepare("UPDATE milk_production SET animal_id=?, date=?, session=?, quantity=?, notes=?, recorded_by=? WHERE id=?");
            $stmt->bind_param("issdsii", 
                $record['animal_id'],
                $record['date'],
                $record['session'],
                $record['quantity'],
                $record['notes'],
                $record['recorded_by'],
                $record['id']
            );
        } else {
            $stmt = $conn->prepare("INSERT INTO milk_production (animal_id, date, session, quantity, notes, recorded_by) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("issdsi", 
                $record['animal_id'],
                $record['date'],
                $record['session'],
                $record['quantity'],
                $record['notes'],
                $record['recorded_by']
            );
        }
        
        if ($stmt->execute()) {
            if (isset($_GET['animal_id'])) {
                header("Location: " . BASE_URL . "/animals/view.php?id=" . $record['animal_id']);
            } else {
                header("Location: " . BASE_URL . "/milk/");
            }
            exit();
        } else {
            $errors['database'] = 'Error saving record: ' . $conn->error;
        }
    }
}

// Fetch animals for dropdown
$animals = [];
$query = "SELECT id, tag_number, breed FROM animals ORDER BY tag_number";
$result = $conn->query($query);
while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
    $animals[] = $row;
}

$pageTitle = $title;
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2"><?php echo $title; ?></h1>
    </div>

    <?php if (!empty($errors['database'])): ?>
    <div class="alert alert-danger"><?php echo $errors['database']; ?></div>
    <?php endif; ?>

    <form method="POST">
        <div class="row mb-3">
            <div class="col-md-6">
                <label for="animal_id" class="form-label">Animal</label>
                <select class="form-select <?php echo isset($errors['animal_id']) ? 'is-invalid' : ''; ?>" id="animal_id" name="animal_id" required>
                    <option value="">Select Animal</option>
                    <?php foreach ($animals as $animal): ?>
                    <option value="<?php echo $animal['id']; ?>" <?php echo $record['animal_id'] == $animal['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($animal['tag_number'] . ' (' . $animal['breed'] . ')'); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
                <?php if (isset($errors['animal_id'])): ?>
                <div class="invalid-feedback"><?php echo $errors['animal_id']; ?></div>
                <?php endif; ?>
            </div>
            <div class="col-md-6">
                <label for="date" class="form-label">Date</label>
                <input type="date" class="form-control <?php echo isset($errors['date']) ? 'is-invalid' : ''; ?>" 
                       id="date" name="date" value="<?php echo htmlspecialchars($record['date']); ?>" required>
                <?php if (isset($errors['date'])): ?>
                <div class="invalid-feedback"><?php echo $errors['date']; ?></div>
                <?php endif; ?>
            </div>
        </div>

        <div class="row mb-3">
            <div class="col-md-6">
                <label for="session" class="form-label">Session</label>
                <select class="form-select" id="session" name="session">
                    <option value="Morning" <?php echo $record['session'] === 'Morning' ? 'selected' : ''; ?>>Morning</option>
                    <option value="Evening" <?php echo $record['session'] === 'Evening' ? 'selected' : ''; ?>>Evening</option>
                </select>
            </div>
            <div class="col-md-6">
                <label for="quantity" class="form-label">Quantity (liters)</label>
                <input type="number" step="0.01" class="form-control <?php echo isset($errors['quantity']) ? 'is-invalid' : ''; ?>" 
                       id="quantity" name="quantity" value="<?php echo htmlspecialchars($record['quantity']); ?>" required>
                <?php if (isset($errors['quantity'])): ?>
                <div class="invalid-feedback"><?php echo $errors['quantity']; ?></div>
                <?php endif; ?>
            </div>
        </div>

        <div class="row mb-3">
            <div class="col-12">
                <label for="notes" class="form-label">Notes</label>
                <textarea class="form-control" id="notes" name="notes" rows="3"><?php echo htmlspecialchars($record['notes']); ?></textarea>
            </div>
        </div>

        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
            <?php if (isset($_GET['animal_id'])): ?>
            <a href="<?php echo BASE_URL; ?>/animals/view.php?id=<?php echo $_GET['animal_id']; ?>" class="btn btn-secondary me-md-2">Cancel</a>
            <?php else: ?>
            <a href="<?php echo BASE_URL; ?>/milk/" class="btn btn-secondary me-md-2">Cancel</a>
            <?php endif; ?>
            <button type="submit" class="btn btn-primary">Save</button>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>