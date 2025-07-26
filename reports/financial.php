<?php
// reports/financial.php
require_once __DIR__ . '/../includes/bootstrap.php';



if (!$auth->isLoggedIn()) {
    header("Location: " . BASE_URL . "/auth/login.php");
    exit();
}


$conn = $database->getConnection();

// Get filter parameters
$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-t');
$report_type = $_GET['report_type'] ?? 'profit_loss';

// Get financial data
$income = [];
$expenses = [];
$profit_loss = [];

if ($report_type === 'profit_loss') {
    // Daily profit/loss report
    $query = "SELECT DATE(date) as day, SUM(amount) as total 
              FROM payments 
              WHERE date BETWEEN ? AND ?
              GROUP BY day
              ORDER BY day";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ss", $start_date, $end_date);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $income[$row['day']] = $row['total'];
    }
    
    $query = "SELECT DATE(date) as day, SUM(amount) as total 
              FROM expenses 
              WHERE date BETWEEN ? AND ?
              GROUP BY day
              ORDER BY day";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ss", $start_date, $end_date);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $expenses[$row['day']] = $row['total'];
    }
    
    // Combine all dates
    $all_dates = array_unique(array_merge(array_keys($income), array_keys($expenses)));
    sort($all_dates);
    
    foreach ($all_dates as $date) {
        $income_val = $income[$date] ?? 0;
        $expense_val = $expenses[$date] ?? 0;
        $profit_loss[] = [
            'date' => $date,
            'income' => $income_val,
            'expenses' => $expense_val,
            'profit' => $income_val - $expense_val
        ];
    }
} elseif ($report_type === 'expense_category') {
    // Expense by category
    $query = "SELECT category, SUM(amount) as total 
              FROM expenses 
              WHERE date BETWEEN ? AND ?
              GROUP BY category
              ORDER BY total DESC";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ss", $start_date, $end_date);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $expenses[] = [
            'category' => $row['category'],
            'amount' => $row['total']
        ];
    }
} elseif ($report_type === 'income_type') {
    // Income by type
    $query = "SELECT payment_type, SUM(amount) as total 
              FROM payments 
              WHERE date BETWEEN ? AND ?
              GROUP BY payment_type
              ORDER BY total DESC";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ss", $start_date, $end_date);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $income[] = [
            'type' => $row['payment_type'],
            'amount' => $row['total']
        ];
    }
}

// Calculate totals
$total_income = array_sum(array_column($profit_loss, 'income'));
$total_expenses = array_sum(array_column($profit_loss, 'expenses'));
$total_profit = $total_income - $total_expenses;
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Financial Report</h1>
    </div>

    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-3">
                    <label for="start_date" class="form-label">Start Date</label>
                    <input type="date" class="form-control" id="start_date" name="start_date" value="<?php echo htmlspecialchars($start_date); ?>">
                </div>
                <div class="col-md-3">
                    <label for="end_date" class="form-label">End Date</label>
                    <input type="date" class="form-control" id="end_date" name="end_date" value="<?php echo htmlspecialchars($end_date); ?>">
                </div>
                <div class="col-md-4">
                    <label for="report_type" class="form-label">Report Type</label>
                    <select class="form-select" id="report_type" name="report_type">
                        <option value="profit_loss" <?php echo $report_type === 'profit_loss' ? 'selected' : ''; ?>>Profit & Loss</option>
                        <option value="expense_category" <?php echo $report_type === 'expense_category' ? 'selected' : ''; ?>>Expenses by Category</option>
                        <option value="income_type" <?php echo $report_type === 'income_type' ? 'selected' : ''; ?>>Income by Type</option>
                    </select>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary">Generate Report</button>
                </div>
            </form>
        </div>
    </div>

    <div class="row mb-4">
        <?php if ($report_type === 'profit_loss'): ?>
        <div class="col-md-4">
            <div class="card text-white bg-primary mb-3">
                <div class="card-body">
                    <h5 class="card-title">Total Income</h5>
                    <p class="card-text h2"><?php echo number_format($total_income, 2); ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card text-white bg-danger mb-3">
                <div class="card-body">
                    <h5 class="card-title">Total Expenses</h5>
                    <p class="card-text h2"><?php echo number_format($total_expenses, 2); ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card text-white bg-<?php echo $total_profit >= 0 ? 'success' : 'warning'; ?> mb-3">
                <div class="card-body">
                    <h5 class="card-title">Net Profit</h5>
                    <p class="card-text h2"><?php echo number_format($total_profit, 2); ?></p>
                </div>
            </div>
        </div>
        <?php elseif ($report_type === 'expense_category'): ?>
        <div class="col-md-6">
            <div class="card text-white bg-danger mb-3">
                <div class="card-body">
                    <h5 class="card-title">Total Expenses</h5>
                    <p class="card-text h2"><?php echo number_format(array_sum(array_column($expenses, 'amount')), 2); ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card text-white bg-info mb-3">
                <div class="card-body">
                    <h5 class="card-title">Categories</h5>
                    <p class="card-text h2"><?php echo count($expenses); ?></p>
                </div>
            </div>
        </div>
        <?php else: ?>
        <div class="col-md-6">
            <div class="card text-white bg-primary mb-3">
                <div class="card-body">
                    <h5 class="card-title">Total Income</h5>
                    <p class="card-text h2"><?php echo number_format(array_sum(array_column($income, 'amount')), 2); ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card text-white bg-info mb-3">
                <div class="card-body">
                    <h5 class="card-title">Income Types</h5>
                    <p class="card-text h2"><?php echo count($income); ?></p>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <div class="card mb-4">
        <div class="card-header">
            <h5>
                <?php 
                echo ucfirst(str_replace('_', ' ', $report_type)) . ' Report: ' . 
                     date('M j, Y', strtotime($start_date)) . ' - ' . date('M j, Y', strtotime($end_date));
                ?>
            </h5>
        </div>
        <div class="card-body">
            <canvas id="financialChart" height="100"></canvas>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h5>Detailed Data</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <?php if ($report_type === 'profit_loss'): ?>
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Income</th>
                            <th>Expenses</th>
                            <th>Profit/Loss</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($profit_loss as $row): ?>
                        <tr>
                            <td><?php echo date('M j, Y', strtotime($row['date'])); ?></td>
                            <td><?php echo number_format($row['income'], 2); ?></td>
                            <td><?php echo number_format($row['expenses'], 2); ?></td>
                            <td class="<?php echo $row['profit'] >= 0 ? 'text-success' : 'text-danger'; ?>">
                                <?php echo number_format($row['profit'], 2); ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <tr class="table-active">
                            <td><strong>Total</strong></td>
                            <td><strong><?php echo number_format($total_income, 2); ?></strong></td>
                            <td><strong><?php echo number_format($total_expenses, 2); ?></strong></td>
                            <td class="<?php echo $total_profit >= 0 ? 'text-success' : 'text-danger'; ?>">
                                <strong><?php echo number_format($total_profit, 2); ?></strong>
                            </td>
                        </tr>
                    </tbody>
                </table>
                <?php elseif ($report_type === 'expense_category'): ?>
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Category</th>
                            <th>Amount</th>
                            <th>Percentage</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $total = array_sum(array_column($expenses, 'amount'));
                        foreach ($expenses as $row): 
                            $percentage = $total > 0 ? ($row['amount'] / $total) * 100 : 0;
                        ?>
                        <tr>
                            <td><?php echo $row['category']; ?></td>
                            <td><?php echo number_format($row['amount'], 2); ?></td>
                            <td>
                                <div class="progress">
                                    <div class="progress-bar" role="progressbar" style="width: <?php echo $percentage; ?>%" 
                                         aria-valuenow="<?php echo $percentage; ?>" aria-valuemin="0" aria-valuemax="100">
                                        <?php echo round($percentage, 1); ?>%
                                    </div>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <tr class="table-active">
                            <td><strong>Total</strong></td>
                            <td><strong><?php echo number_format($total, 2); ?></strong></td>
                            <td>100%</td>
                        </tr>
                    </tbody>
                </table>
                <?php else: ?>
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Income Type</th>
                            <th>Amount</th>
                            <th>Percentage</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $total = array_sum(array_column($income, 'amount'));
                        foreach ($income as $row): 
                            $percentage = $total > 0 ? ($row['amount'] / $total) * 100 : 0;
                        ?>
                        <tr>
                            <td><?php echo $row['type']; ?></td>
                            <td><?php echo number_format($row['amount'], 2); ?></td>
                            <td>
                                <div class="progress">
                                    <div class="progress-bar" role="progressbar" style="width: <?php echo $percentage; ?>%" 
                                         aria-valuenow="<?php echo $percentage; ?>" aria-valuemin="0" aria-valuemax="100">
                                        <?php echo round($percentage, 1); ?>%
                                    </div>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <tr class="table-active">
                            <td><strong>Total</strong></td>
                            <td><strong><?php echo number_format($total, 2); ?></strong></td>
                            <td>100%</td>
                        </tr>
                    </tbody>
                </table>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="card mt-4">
        <div class="card-header">
            <h5>Export Report</h5>
        </div>
        <div class="card-body">
            <a href="<?php echo BASE_URL; ?>/reports/generate_pdf.php?type=financial&start_date=<?php echo urlencode($start_date); ?>&end_date=<?php echo urlencode($end_date); ?>&report_type=<?php echo urlencode($report_type); ?>" 
               class="btn btn-outline-primary w-100 mb-2">
                <i class="bi bi-file-earmark-pdf"></i> Export as PDF
            </a>
            <a href="<?php echo BASE_URL; ?>/reports/generate_csv.php?type=financial&start_date=<?php echo urlencode($start_date); ?>&end_date=<?php echo urlencode($end_date); ?>&report_type=<?php echo urlencode($report_type); ?>" 
               class="btn btn-outline-success w-100">
                <i class="bi bi-file-earmark-excel"></i> Export as CSV
            </a>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('financialChart').getContext('2d');
    
    <?php if ($report_type === 'profit_loss'): ?>
    const labels = <?php echo json_encode(array_map(function($row) {
        return date('M j', strtotime($row['date']));
    }, $profit_loss)); ?>;
    
    const incomeData = <?php echo json_encode(array_column($profit_loss, 'income')); ?>;
    const expenseData = <?php echo json_encode(array_column($profit_loss, 'expenses')); ?>;
    const profitData = <?php echo json_encode(array_column($profit_loss, 'profit')); ?>;
    
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [
                {
                    label: 'Income',
                    data: incomeData,
                    backgroundColor: 'rgba(54, 162, 235, 0.7)',
                    borderColor: 'rgba(54, 162, 235, 1)',
                    borderWidth: 1
                },
                {
                    label: 'Expenses',
                    data: expenseData,
                    backgroundColor: 'rgba(255, 99, 132, 0.7)',
                    borderColor: 'rgba(255, 99, 132, 1)',
                    borderWidth: 1
                },
                {
                    label: 'Profit/Loss',
                    data: profitData,
                    type: 'line',
                    borderColor: 'rgba(75, 192, 192, 1)',
                    borderWidth: 2,
                    fill: false
                }
            ]
        },
        options: {
            scales: {
                y: {
                    beginAtZero: false
                }
            }
        }
    });
    <?php elseif ($report_type === 'expense_category'): ?>
    const labels = <?php echo json_encode(array_column($expenses, 'category')); ?>;
    const data = <?php echo json_encode(array_column($expenses, 'amount')); ?>;
    
    new Chart(ctx, {
        type: 'pie',
        data: {
            labels: labels,
            datasets: [{
                data: data,
                backgroundColor: [
                    'rgba(255, 99, 132, 0.7)',
                    'rgba(54, 162, 235, 0.7)',
                    'rgba(255, 206, 86, 0.7)',
                    'rgba(75, 192, 192, 0.7)',
                    'rgba(153, 102, 255, 0.7)',
                    'rgba(255, 159, 64, 0.7)'
                ],
                borderColor: [
                    'rgba(255, 99, 132, 1)',
                    'rgba(54, 162, 235, 1)',
                    'rgba(255, 206, 86, 1)',
                    'rgba(75, 192, 192, 1)',
                    'rgba(153, 102, 255, 1)',
                    'rgba(255, 159, 64, 1)'
                ],
                borderWidth: 1
            }]
        }
    });
    <?php else: ?>
    const labels = <?php echo json_encode(array_column($income, 'type')); ?>;
    const data = <?php echo json_encode(array_column($income, 'amount')); ?>;
    
    new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: labels,
            datasets: [{
                data: data,
                backgroundColor: [
                    'rgba(54, 162, 235, 0.7)',
                    'rgba(255, 206, 86, 0.7)',
                    'rgba(75, 192, 192, 0.7)',
                    'rgba(153, 102, 255, 0.7)',
                    'rgba(255, 159, 64, 0.7)'
                ],
                borderColor: [
                    'rgba(54, 162, 235, 1)',
                    'rgba(255, 206, 86, 1)',
                    'rgba(75, 192, 192, 1)',
                    'rgba(153, 102, 255, 1)',
                    'rgba(255, 159, 64, 1)'
                ],
                borderWidth: 1
            }]
        }
    });
    <?php endif; ?>
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>