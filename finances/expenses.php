<?php
require_once __DIR__ . '/../includes/bootstrap.php';



if (!$auth->isLoggedIn()) {
    header("Location: " . BASE_URL . "/auth/login.php");
    exit();
}


$conn = $database->getConnection();

// Handle expense deletion
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM expenses WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    header("Location: " . BASE_URL . "/finances/expenses.php");
    exit();
}

// Fetch all expenses
$expenses = [];
$query = "SELECT * FROM expenses ORDER BY date DESC";
$result = $conn->query($query);
while ($row = $result->fetch_assoc()) {
    $expenses[] = $row;
}

// Calculate expense summary
$summary = [];
$query = "SELECT 
            SUM(CASE WHEN MONTH(date) = MONTH(CURDATE()) THEN amount ELSE 0 END) as this_month,
            SUM(CASE WHEN MONTH(date) = MONTH(DATE_SUB(CURDATE(), INTERVAL 1 MONTH)) THEN amount ELSE 0 END) as last_month,
            AVG(amount) as avg_monthly,
            category, SUM(amount) as total
          FROM expenses
          GROUP BY category";
$result = $conn->query($query);
while ($row = $result->fetch_assoc()) {
    $summary[] = $row;
}

// Calculate totals
$thisMonthTotal = array_sum(array_column($summary, 'this_month'));
$lastMonthTotal = array_sum(array_column($summary, 'last_month'));
$avgMonthly = array_sum(array_column($summary, 'avg_monthly'));
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Expense Management</h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <a href="<?php echo BASE_URL; ?>/finances/add_expense.php" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-plus-circle"></i> Add Expense
            </a>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card text-white bg-primary mb-3">
                <div class="card-body">
                    <h5 class="card-title">This Month</h5>
                    <p class="card-text h2">$<?php echo number_format($thisMonthTotal, 2); ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card text-white bg-success mb-3">
                <div class="card-body">
                    <h5 class="card-title">Last Month</h5>
                    <p class="card-text h2">$<?php echo number_format($lastMonthTotal, 2); ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card text-white bg-info mb-3">
                <div class="card-body">
                    <h5 class="card-title">Average Monthly</h5>
                    <p class="card-text h2">$<?php echo number_format($avgMonthly, 2); ?></p>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header">
                    <h5>Expenses by Category</h5>
                </div>
                <div class="card-body">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Category</th>
                                <th>Amount</th>
                                <th>Percentage</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($summary as $item): ?>
                            <tr>
                                <td><?php echo $item['category']; ?></td>
                                <td>$<?php echo number_format($item['total'], 2); ?></td>
                                <td><?php echo $thisMonthTotal > 0 ? round(($item['total'] / $thisMonthTotal) * 100) : 0; ?>%</td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header">
                    <h5>Recent Expenses</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Category</th>
                                    <th>Description</th>
                                    <th>Amount</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($expenses as $expense): ?>
                                <tr>
                                    <td><?php echo date('M j, Y', strtotime($expense['date'])); ?></td>
                                    <td><?php echo $expense['category']; ?></td>
                                    <td><?php echo htmlspecialchars($expense['description']); ?></td>
                                    <td>$<?php echo number_format($expense['amount'], 2); ?></td>
                                    <td>
                                        <a href="<?php echo BASE_URL; ?>/finances/add_expense.php?id=<?php echo $expense['id']; ?>" class="btn btn-sm btn-outline-secondary">
                                            <i class="bi bi-pencil"></i> Edit
                                        </a>
                                        <a href="<?php echo BASE_URL; ?>/finances/expenses.php?delete=<?php echo $expense['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Are you sure you want to delete this expense?')">
                                            <i class="bi bi-trash"></i> Delete
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>