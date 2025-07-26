<?php
require_once __DIR__ . '/../includes/bootstrap.php';



if (!$auth->isLoggedIn()) {
    header("Location: " . BASE_URL . "/auth/login.php");
    exit();
}


$conn = $database->getConnection();

$record = [
    'id' => '',
    'animal_id' => $_GET['animal_id'] ?? '',
    'record_type' => 'Vaccination',
    'date' => date('Y-m-d'),
    'description' => '',
    'treatment' => '',
    'veterinarian' => '',
    'cost' => '',
    'next_followup' => ''
];

$isEdit = false;
$title = "Add Health Record";

if (isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $stmt = $conn->prepare("SELECT * FROM health_records WHERE id = ?");
    $stmt->execute([$id]);
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $record = $result->fetch(PDO::FETCH_ASSOC);
        $isEdit = true;
        $title = "Edit Health Record";
    }
}

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $record['animal_id'] = $_POST['animal_id'] ?? '';
    $record['record_type'] = $_POST['record_type'] ?? '';
    $record['date'] = $_POST['date'] ?? '';
    $record['description'] = $_POST['description'] ?? '';
    $record['treatment'] = $_POST['treatment'] ?? '';
    $record['veterinarian'] = $_POST['veterinarian'] ?? '';
    $record['cost'] = $_POST['cost'] ?? '';
    $record['next_followup'] = $_POST['next_followup'] ?? '';
    
    // Validation
    if (empty($record['animal_id'])) {
        $errors['animal_id'] = 'Animal is required';
    }
    
    if (empty($record['date'])) {
        $errors['date'] = 'Date is required';
    }
    
    if (empty($record['description'])) {
        $errors['description'] = 'Description is required';
    }
    
    if (empty($errors)) {
        if ($isEdit) {
            $stmt = $conn->prepare("UPDATE health_records SET animal_id=?, record_type=?, date=?, description=?, treatment=?, veterinarian=?, cost=?, next_followup=? WHERE id=?");
            $stmt->bind_param("isssssdsi", 
                $record['animal_id'],
                $record['record_type'],
                $record['date'],
                $record['description'],
                $record['treatment'],
                $record['veterinarian'],
                $record['cost'],
                $record['next_followup'],
                $record['id']
            );
        } else {
            $stmt = $conn->prepare("INSERT INTO health_records (animal_id, record_type, date, description, treatment, veterinarian, cost, next_followup) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("isssssds", 
                $record['animal_id'],
                $record['record_type'],
                $record['date'],
                $record['description'],
                $record['treatment'],
                $record['veterinarian'],
                $record['cost'],
                $record['next_followup']
            );
        }
        
        if ($stmt->execute()) {
            if (isset($_GET['animal_id'])) {
                header("Location: " . BASE_URL . "/animals/view.php?id=" . $record['animal_id']);
            } else {
                header("Location: " . BASE_URL . "/health/");
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
                <label for="record_type" class="form-label">Record Type</label>
                <select class="form-select" id="record_type" name="record_type">
                    <option value="Vaccination" <?php echo $record['record_type'] === 'Vaccination' ? 'selected' : ''; ?>>Vaccination</option>
                    <option value="Treatment" <?php echo $record['record_type'] === 'Treatment' ? 'selected' : ''; ?>>Treatment</option>
                    <option value="Checkup" <?php echo $record['record_type'] === 'Checkup' ? 'selected' : ''; ?>>Checkup</option>
                    <option value="Breeding" <?php echo $record['record_type'] === 'Breeding' ? 'selected' : ''; ?>>Breeding</option>
                    <option value="Other" <?php echo $record['record_type'] === 'Other' ? 'selected' : ''; ?>>Other</option>
                </select>
            </div>
        </div>

        <div class="row mb-3">
            <div class="col-md-6">
                <label for="date" class="form-label">Date</label>
                <input type="date" class="form-control <?php echo isset($errors['date']) ? 'is-invalid' : ''; ?>" 
                       id="date" name="date" value="<?php echo htmlspecialchars($record['date']); ?>" required>
                <?php if (isset($errors['date'])): ?>
                <div class="invalid-feedback"><?php echo $errors['date']; ?></div>
                <?php endif; ?>
            </div>
            <div class="col-md-6">
                <label for="veterinarian" class="form-label">Veterinarian</label>
                <input type="text" class="form-control" id="veterinarian" name="veterinarian" 
                       value="<?php echo htmlspecialchars($record['veterinarian']); ?>">
            </div>
        </div>

        <div class="row mb-3">
            <div class="col-12">
                <label for="description" class="form-label">Description</label>
                <textarea class="form-control <?php echo isset($errors['description']) ? 'is-invalid' : ''; ?>" 
                          id="description" name="description" rows="3" required><?php echo htmlspecialchars($record['description']); ?></textarea>
                <?php if (isset($errors['description'])): ?>
                <div class="invalid-feedback"><?php echo $errors['description']; ?></div>
                <?php endif; ?>
            </div>
        </div>

        <div class="row mb-3">
            <div class="col-12">
                <label for="treatment" class="form-label">Treatment</label>
                <textarea class="form-control" id="treatment" name="treatment" rows="3"><?php echo htmlspecialchars($record['treatment']); ?></textarea>
            </div>
        </div>

        <div class="row mb-3">
            <div class="col-md-6">
                <label for="cost" class="form-label">Cost</label>
                <input type="number" step="0.01" class="form-control" id="cost" name="cost" 
                       value="<?php echo htmlspecialchars($record['cost']); ?>">
            </div>
            <div class="col-md-6">
                <label for="next_followup" class="form-label">Next Follow-up</label>
                <input type="date" class="form-control" id="next_followup" name="next_followup" 
                       value="<?php echo htmlspecialchars($record['next_followup']); ?>">
            </div>
        </div>

        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
            <?php if (isset($_GET['animal_id'])): ?>
            <a href="<?php echo BASE_URL; ?>/animals/view.php?id=<?php echo $_GET['animal_id']; ?>" class="btn btn-secondary me-md-2">Cancel</a>
            <?php else: ?>
            <a href="<?php echo BASE_URL; ?>/health/" class="btn btn-secondary me-md-2">Cancel</a>
            <?php endif; ?>
            <button type="submit" class="btn btn-primary">Save</button>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>