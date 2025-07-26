<?php
require_once __DIR__ . '/../includes/bootstrap.php';



if (!$auth->isLoggedIn()) {
    header("Location: " . BASE_URL . "/auth/login.php");
    exit();
}


$conn = $database->getConnection();

$expense = [
    'id' => '',
    'date' => date('Y-m-d'),
    'category' => 'Fodder',
    'description' => '',
    'amount' => '',
    'payment_method' => 'Cash',
    'receipt_reference' => ''
];

$isEdit = false;
$title = "Add Expense";

if (isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $stmt = $conn->prepare("SELECT * FROM expenses WHERE id = ?");
    $stmt->execute([$id]);
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $expense = $result->fetch(PDO::FETCH_ASSOC);
        $isEdit = true;
        $title = "Edit Expense";
    }
}

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $expense['date'] = $_POST['date'] ?? '';
    $expense['category'] = $_POST['category'] ?? '';
    $expense['description'] = $_POST['description'] ?? '';
    $expense['amount'] = $_POST['amount'] ?? '';
    $expense['payment_method'] = $_POST['payment_method'] ?? '';
    $expense['receipt_reference'] = $_POST['receipt_reference'] ?? '';
    
    // Validation
    if (empty($expense['date'])) {
        $errors['date'] = 'Date is required';
    }
    
    if (empty($expense['description'])) {
        $errors['description'] = 'Description is required';
    }
    
    if (empty($expense['amount']) || !is_numeric($expense['amount']) || $expense['amount'] <= 0) {
        $errors['amount'] = 'Amount must be a positive number';
    }
    
    if (empty($errors)) {
        if ($isEdit) {
            $stmt = $conn->prepare("UPDATE expenses SET date=?, category=?, description=?, amount=?, payment_method=?, receipt_reference=? WHERE id=?");
            $stmt->bind_param("sssdssi", 
                $expense['date'],
                $expense['category'],
                $expense['description'],
                $expense['amount'],
                $expense['payment_method'],
                $expense['receipt_reference'],
                $expense['id']
            );
        } else {
            $stmt = $conn->prepare("INSERT INTO expenses (date, category, description, amount, payment_method, receipt_reference) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sssdss", 
                $expense['date'],
                $expense['category'],
                $expense['description'],
                $expense['amount'],
                $expense['payment_method'],
                $expense['receipt_reference']
            );
        }
        
        if ($stmt->execute()) {
            header("Location: " . BASE_URL . "/finances/expenses.php");
            exit();
        } else {
            $errors['database'] = 'Error saving expense: ' . $conn->error;
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
                <label for="date" class="form-label">Date</label>
                <input type="date" class="form-control <?php echo isset($errors['date']) ? 'is-invalid' : ''; ?>" 
                       id="date" name="date" value="<?php echo htmlspecialchars($expense['date']); ?>" required>
                <?php if (isset($errors['date'])): ?>
                <div class="invalid-feedback"><?php echo $errors['date']; ?></div>
                <?php endif; ?>
            </div>
            <div class="col-md-6">
                <label for="category" class="form-label">Category</label>
                <select class="form-select" id="category" name="category">
                    <option value="Fodder" <?php echo $expense['category'] === 'Fodder' ? 'selected' : ''; ?>>Fodder</option>
                    <option value="Medicines" <?php echo $expense['category'] === 'Medicines' ? 'selected' : ''; ?>>Medicines</option>
                    <option value="Labor" <?php echo $expense['category'] === 'Labor' ? 'selected' : ''; ?>>Labor</option>
                    <option value="Equipment" <?php echo $expense['category'] === 'Equipment' ? 'selected' : ''; ?>>Equipment</option>
                    <option value="Utilities" <?php echo $expense['category'] === 'Utilities' ? 'selected' : ''; ?>>Utilities</option>
                    <option value="Maintenance" <?php echo $expense['category'] === 'Maintenance' ? 'selected' : ''; ?>>Maintenance</option>
                    <option value="Other" <?php echo $expense['category'] === 'Other' ? 'selected' : ''; ?>>Other</option>
                </select>
            </div>
        </div>

        <div class="row mb-3">
            <div class="col-12">
                <label for="description" class="form-label">Description</label>
                <input type="text" class="form-control <?php echo isset($errors['description']) ? 'is-invalid' : ''; ?>" 
                       id="description" name="description" value="<?php echo htmlspecialchars($expense['description']); ?>" required>
                <?php if (isset($errors['description'])): ?>
                <div class="invalid-feedback"><?php echo $errors['description']; ?></div>
                <?php endif; ?>
            </div>
        </div>

        <div class="row mb-3">
            <div class="col-md-6">
                <label for="amount" class="form-label">Amount</label>
                <input type="number" step="0.01" class="form-control <?php echo isset($errors['amount']) ? 'is-invalid' : ''; ?>" 
                       id="amount" name="amount" value="<?php echo htmlspecialchars($expense['amount']); ?>" required>
                <?php if (isset($errors['amount'])): ?>
                <div class="invalid-feedback"><?php echo $errors['amount']; ?></div>
                <?php endif; ?>
            </div>
            <div class="col-md-6">
                <label for="payment_method" class="form-label">Payment Method</label>
                <select class="form-select" id="payment_method" name="payment_method">
                    <option value="Cash" <?php echo $expense['payment_method'] === 'Cash' ? 'selected' : ''; ?>>Cash</option>
                    <option value="Bank Transfer" <?php echo $expense['payment_method'] === 'Bank Transfer' ? 'selected' : ''; ?>>Bank Transfer</option>
                    <option value="Check" <?php echo $expense['payment_method'] === 'Check' ? 'selected' : ''; ?>>Check</option>
                    <option value="Credit Card" <?php echo $expense['payment_method'] === 'Credit Card' ? 'selected' : ''; ?>>Credit Card</option>
                </select>
            </div>
        </div>

        <div class="row mb-3">
            <div class="col-12">
                <label for="receipt_reference" class="form-label">Receipt/Reference</label>
                <input type="text" class="form-control" id="receipt_reference" name="receipt_reference" 
                       value="<?php echo htmlspecialchars($expense['receipt_reference']); ?>">
            </div>
        </div>

        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
            <a href="<?php echo BASE_URL; ?>/finances/expenses.php" class="btn btn-secondary me-md-2">Cancel</a>
            <button type="submit" class="btn btn-primary">Save</button>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>