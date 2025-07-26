<?php
require_once __DIR__ . '/../includes/bootstrap.php';



if (!$auth->isLoggedIn()) {
    header("Location: " . BASE_URL . "/auth/login.php");
    exit();
}


$conn = $database->getConnection();

$payment = [
    'id' => '',
    'date' => date('Y-m-d'),
    'payment_type' => 'Salary',
    'recipient' => '',
    'amount' => '',
    'payment_method' => 'Cash',
    'reference' => ''
];

$isEdit = false;
$title = "Add Payment";

if (isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $stmt = $conn->prepare("SELECT * FROM payments WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $payment = $result->fetch_assoc();
        $isEdit = true;
        $title = "Edit Payment";
    }
}

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $payment['date'] = $_POST['date'] ?? '';
    $payment['payment_type'] = $_POST['payment_type'] ?? '';
    $payment['recipient'] = $_POST['recipient'] ?? '';
    $payment['amount'] = $_POST['amount'] ?? '';
    $payment['payment_method'] = $_POST['payment_method'] ?? '';
    $payment['reference'] = $_POST['reference'] ?? '';
    
    // Validation
    if (empty($payment['date'])) {
        $errors['date'] = 'Date is required';
    }
    
    if (empty($payment['recipient'])) {
        $errors['recipient'] = 'Recipient is required';
    }
    
    if (empty($payment['amount']) || !is_numeric($payment['amount']) || $payment['amount'] <= 0) {
        $errors['amount'] = 'Amount must be a positive number';
    }
    
    if (empty($errors)) {
        if ($isEdit) {
            $stmt = $conn->prepare("UPDATE payments SET date=?, payment_type=?, recipient=?, amount=?, payment_method=?, reference=? WHERE id=?");
            $stmt->bind_param("sssdssi", 
                $payment['date'],
                $payment['payment_type'],
                $payment['recipient'],
                $payment['amount'],
                $payment['payment_method'],
                $payment['reference'],
                $payment['id']
            );
        } else {
            $stmt = $conn->prepare("INSERT INTO payments (date, payment_type, recipient, amount, payment_method, reference) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sssdss", 
                $payment['date'],
                $payment['payment_type'],
                $payment['recipient'],
                $payment['amount'],
                $payment['payment_method'],
                $payment['reference']
            );
        }
        
        if ($stmt->execute()) {
            header("Location: " . BASE_URL . "/finances/payments.php");
            exit();
        } else {
            $errors['database'] = 'Error saving payment: ' . $conn->error;
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
                       id="date" name="date" value="<?php echo htmlspecialchars($payment['date']); ?>" required>
                <?php if (isset($errors['date'])): ?>
                <div class="invalid-feedback"><?php echo $errors['date']; ?></div>
                <?php endif; ?>
            </div>
            <div class="col-md-6">
                <label for="payment_type" class="form-label">Payment Type</label>
                <select class="form-select" id="payment_type" name="payment_type">
                    <option value="Salary" <?php echo $payment['payment_type'] === 'Salary' ? 'selected' : ''; ?>>Salary</option>
                    <option value="Utility Bill" <?php echo $payment['payment_type'] === 'Utility Bill' ? 'selected' : ''; ?>>Utility Bill</option>
                    <option value="Supplier Payment" <?php echo $payment['payment_type'] === 'Supplier Payment' ? 'selected' : ''; ?>>Supplier Payment</option>
                    <option value="Loan Payment" <?php echo $payment['payment_type'] === 'Loan Payment' ? 'selected' : ''; ?>>Loan Payment</option>
                    <option value="Other" <?php echo $payment['payment_type'] === 'Other' ? 'selected' : ''; ?>>Other</option>
                </select>
            </div>
        </div>

        <div class="row mb-3">
            <div class="col-12">
                <label for="recipient" class="form-label">Recipient</label>
                <input type="text" class="form-control <?php echo isset($errors['recipient']) ? 'is-invalid' : ''; ?>" 
                       id="recipient" name="recipient" value="<?php echo htmlspecialchars($payment['recipient']); ?>" required>
                <?php if (isset($errors['recipient'])): ?>
                <div class="invalid-feedback"><?php echo $errors['recipient']; ?></div>
                <?php endif; ?>
            </div>
        </div>

        <div class="row mb-3">
            <div class="col-md-6">
                <label for="amount" class="form-label">Amount</label>
                <input type="number" step="0.01" class="form-control <?php echo isset($errors['amount']) ? 'is-invalid' : ''; ?>" 
                       id="amount" name="amount" value="<?php echo htmlspecialchars($payment['amount']); ?>" required>
                <?php if (isset($errors['amount'])): ?>
                <div class="invalid-feedback"><?php echo $errors['amount']; ?></div>
                <?php endif; ?>
            </div>
            <div class="col-md-6">
                <label for="payment_method" class="form-label">Payment Method</label>
                <select class="form-select" id="payment_method" name="payment_method">
                    <option value="Cash" <?php echo $payment['payment_method'] === 'Cash' ? 'selected' : ''; ?>>Cash</option>
                    <option value="Bank Transfer" <?php echo $payment['payment_method'] === 'Bank Transfer' ? 'selected' : ''; ?>>Bank Transfer</option>
                    <option value="Check" <?php echo $payment['payment_method'] === 'Check' ? 'selected' : ''; ?>>Check</option>
                </select>
            </div>
        </div>

        <div class="row mb-3">
            <div class="col-12">
                <label for="reference" class="form-label">Reference</label>
                <textarea class="form-control" id="reference" name="reference" rows="2"><?php echo htmlspecialchars($payment['reference']); ?></textarea>
            </div>
        </div>

        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
            <a href="<?php echo BASE_URL; ?>/finances/payments.php" class="btn btn-secondary me-md-2">Cancel</a>
            <button type="submit" class="btn btn-primary">Save</button>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>