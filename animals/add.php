<?php
require_once __DIR__ . '/../includes/bootstrap.php';



if (!$auth->isLoggedIn()) {
    header("Location: " . BASE_URL . "/auth/login.php");
    exit();
}


$conn = $database->getConnection();

$animal = [
    'id' => '',
    'tag_number' => '',
    'breed' => 'Holstein',
    'date_of_birth' => '',
    'purchase_date' => date('Y-m-d'),
    'purchase_price' => '',
    'status' => 'Active'
];

$isEdit = false;
$title = "Add New Animal";

if (isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $stmt = $conn->prepare("SELECT * FROM animals WHERE id = ?");
    $stmt->execute([$id]);
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $animal = $result->fetch(PDO::FETCH_ASSOC);
        $isEdit = true;
        $title = "Edit Animal";
    }
}

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $animal['tag_number'] = $_POST['tag_number'] ?? '';
    $animal['breed'] = $_POST['breed'] ?? '';
    $animal['date_of_birth'] = $_POST['date_of_birth'] ?? '';
    $animal['purchase_date'] = $_POST['purchase_date'] ?? '';
    $animal['purchase_price'] = $_POST['purchase_price'] ?? '';
    $animal['status'] = $_POST['status'] ?? 'Active';
    
    // Validation
    if (empty($animal['tag_number'])) {
        $errors['tag_number'] = 'Tag number is required';
    }
    
    if (empty($animal['breed'])) {
        $errors['breed'] = 'Breed is required';
    }
    
    if (empty($animal['purchase_date'])) {
        $errors['purchase_date'] = 'Purchase date is required';
    }
    
    if (empty($errors)) {
        if ($isEdit) {
            $stmt = $conn->prepare("UPDATE animals SET tag_number=?, breed=?, date_of_birth=?, purchase_date=?, purchase_price=?, status=?, updated_at=NOW() WHERE id=?");
            $stmt->bind_param("ssssdsi", 
                $animal['tag_number'],
                $animal['breed'],
                $animal['date_of_birth'],
                $animal['purchase_date'],
                $animal['purchase_price'],
                $animal['status'],
                $animal['id']
            );
        } else {
            $stmt = $conn->prepare("INSERT INTO animals (tag_number, breed, date_of_birth, purchase_date, purchase_price, status) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssds", 
                $animal['tag_number'],
                $animal['breed'],
                $animal['date_of_birth'],
                $animal['purchase_date'],
                $animal['purchase_price'],
                $animal['status']
            );
        }
        
        if ($stmt->execute()) {
            header("Location: " . BASE_URL . "/animals/");
            exit();
        } else {
            $errors['database'] = 'Error saving animal: ' . $conn->error;
        }
    }
}
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
                <label for="tag_number" class="form-label">Tag Number</label>
                <input type="text" class="form-control <?php echo isset($errors['tag_number']) ? 'is-invalid' : ''; ?>" 
                       id="tag_number" name="tag_number" value="<?php echo htmlspecialchars($animal['tag_number']); ?>" required>
                <?php if (isset($errors['tag_number'])): ?>
                <div class="invalid-feedback"><?php echo $errors['tag_number']; ?></div>
                <?php endif; ?>
            </div>
            <div class="col-md-6">
                <label for="breed" class="form-label">Breed</label>
                <select class="form-select <?php echo isset($errors['breed']) ? 'is-invalid' : ''; ?>" id="breed" name="breed" required>
                    <option value="Holstein" <?php echo $animal['breed'] === 'Holstein' ? 'selected' : ''; ?>>Holstein</option>
                    <option value="Jersey" <?php echo $animal['breed'] === 'Jersey' ? 'selected' : ''; ?>>Jersey</option>
                    <option value="Guernsey" <?php echo $animal['breed'] === 'Guernsey' ? 'selected' : ''; ?>>Guernsey</option>
                    <option value="Ayrshire" <?php echo $animal['breed'] === 'Ayrshire' ? 'selected' : ''; ?>>Ayrshire</option>
                    <option value="Brown Swiss" <?php echo $animal['breed'] === 'Brown Swiss' ? 'selected' : ''; ?>>Brown Swiss</option>
                </select>
                <?php if (isset($errors['breed'])): ?>
                <div class="invalid-feedback"><?php echo $errors['breed']; ?></div>
                <?php endif; ?>
            </div>
        </div>

        <div class="row mb-3">
            <div class="col-md-6">
                <label for="date_of_birth" class="form-label">Date of Birth</label>
                <input type="date" class="form-control" id="date_of_birth" name="date_of_birth" 
                       value="<?php echo htmlspecialchars($animal['date_of_birth']); ?>">
            </div>
            <div class="col-md-6">
                <label for="purchase_date" class="form-label">Purchase Date</label>
                <input type="date" class="form-control <?php echo isset($errors['purchase_date']) ? 'is-invalid' : ''; ?>" 
                       id="purchase_date" name="purchase_date" value="<?php echo htmlspecialchars($animal['purchase_date']); ?>" required>
                <?php if (isset($errors['purchase_date'])): ?>
                <div class="invalid-feedback"><?php echo $errors['purchase_date']; ?></div>
                <?php endif; ?>
            </div>
        </div>

        <div class="row mb-3">
            <div class="col-md-6">
                <label for="purchase_price" class="form-label">Purchase Price</label>
                <input type="number" step="0.01" class="form-control" id="purchase_price" name="purchase_price" 
                       value="<?php echo htmlspecialchars($animal['purchase_price']); ?>">
            </div>
            <div class="col-md-6">
                <label for="status" class="form-label">Status</label>
                <select class="form-select" id="status" name="status">
                    <option value="Active" <?php echo $animal['status'] === 'Active' ? 'selected' : ''; ?>>Active</option>
                    <option value="Inactive" <?php echo $animal['status'] === 'Inactive' ? 'selected' : ''; ?>>Inactive</option>
                    <option value="Recovering" <?php echo $animal['status'] === 'Recovering' ? 'selected' : ''; ?>>Recovering</option>
                    <option value="Pregnant" <?php echo $animal['status'] === 'Pregnant' ? 'selected' : ''; ?>>Pregnant</option>
                </select>
            </div>
        </div>

        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
            <a href="<?php echo BASE_URL; ?>/animals/" class="btn btn-secondary me-md-2">Cancel</a>
            <button type="submit" class="btn btn-primary">Save</button>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>