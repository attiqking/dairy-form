<?php
// finances/reports.php
require_once __DIR__ . '/../includes/bootstrap.php';



if (!$auth->isLoggedIn()) {
    header("Location: " . BASE_URL . "/auth/login.php");
    exit();
}


$conn = $database->getConnection();

// Get filter parameters
$year = $_GET['year'] ?? date('Y');
$report_type = $_GET['report_type'] ?? 'profit_loss';

// Get available years
$years = [];
$query = "SELECT DISTINCT YEAR(date) as year FROM (
            SELECT date FROM expenses 
            UNION ALL 
            SELECT date FROM payments
          ) as dates 
          ORDER BY year DESC";
$result = $conn->query($query);
while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
    $years[] = $row['year'];
}

// Initialize report data
$report_data = [];
$chart_labels = [];
$chart_expenses = [];
$chart_payments = [];
$chart_profit = [];

if ($report_type === 'profit_loss') {
    // Monthly profit/loss report
    for ($month = 1; $month <= 12; $month++) {
        $month_name = date('F', mktime(0, 0, 0, $month, 1));
        $chart_labels[] = $month_name;
        
        // Get expenses for the month
        $query = "SELECT SUM(amount) as total FROM expenses 
                  WHERE YEAR(date) = ? AND MONTH(date) = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ii", $year, $month);
        $stmt->execute();
        $expenses = $stmt->get_result()->fetch_assoc()['total'] ?? 0;
        $chart_expenses[] = $expenses;
        
        // Get payments (income) for the month
        $query = "SELECT SUM(amount) as total FROM payments 
                  WHERE YEAR(date) = ? AND MONTH(date) = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ii", $year, $month);
        $stmt->execute();
        $income = $stmt->get_result()->fetch_assoc()['total'] ?? 0;
        $chart_payments[] = $income;
        
        $profit = $income - $expenses;
        $chart_profit[] = $profit;
        
        $report_data[] = [
            'month' => $month_name,
            'income' => $income,
            'expenses' => $expenses,
            'profit' => $profit
        ];
    }
} elseif ($report_type === 'category') {
    // Expense by category report
    $query = "SELECT category, SUM(amount) as total 
              FROM expenses 
              WHERE YEAR(date) = ?
              GROUP BY category
              ORDER BY total DESC";
    $stmt = $conn->prepare($query);
    $stmt->execute([$year]);
    $result = $stmt->get_result();
    
    while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
        $report_data[] = [
            'category' => $row['category'],
            'amount' => $row['total']
        ];
        $chart_labels[] = $row['category'];
        $chart_expenses[] = $row['total'];
    }
}
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Financial Reports</h1>
    </div>

    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-4">
                    <label for="year" class="form-label">Year</label>
                    <select class="form-select" id="year" name="year">
                        <?php foreach ($years as $y): ?>
                        <option value="<?php echo $y; ?>" <?php echo $year == $y ? 'selected' : ''; ?>><?php echo $y; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="report_type" class="form-label">Report Type</label>
                    <select class="form-select" id="report_type" name="report_type">
                        <option value="profit_loss" <?php echo $report_type == 'profit_loss' ? 'selected' : ''; ?>>Profit & Loss</option>
                        <option value="category" <?php echo $report_type == 'category' ? 'selected' : ''; ?>>Expenses by Category</option>
                    </select>
                </div>
                <div class="col-md-4 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary">Generate Report</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header">
            <h5>
                <?php echo $report_type === 'profit_loss' ? 'Monthly Profit & Loss Report' : 'Expenses by Category'; ?>
                for <?php echo $year; ?>
            </h5>
        </div>
        <div class="card-body">
            <canvas id="financialChart" height="100"></canvas>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h5>Report Data</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <?php if ($report_type === 'profit_loss'): ?>
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Month</th>
                            <th>Income</th>
                            <th>Expenses</th>
                            <th>Profit/Loss</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($report_data as $row): ?>
                        <tr>
                            <td><?php echo $row['month']; ?></td>
                            <td><?php echo number_format($row['income'], 2); ?></td>
                            <td><?php echo number_format($row['expenses'], 2); ?></td>
                            <td class="<?php echo $row['profit'] >= 0 ? 'text-success' : 'text-danger'; ?>">
                                <?php echo number_format($row['profit'], 2); ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <tr class="table-active">
                            <td><strong>Total</strong></td>
                            <td><strong><?php echo number_format(array_sum(array_column($report_data, 'income')), 2); ?></strong></td>
                            <td><strong><?php echo number_format(array_sum(array_column($report_data, 'expenses')), 2); ?></strong></td>
                            <td class="<?php echo array_sum(array_column($report_data, 'profit')) >= 0 ? 'text-success' : 'text-danger'; ?>">
                                <strong><?php echo number_format(array_sum(array_column($report_data, 'profit')), 2); ?></strong>
                            </td>
                        </tr>
                    </tbody>
                </table>
                <?php else: ?>
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
                        $total = array_sum(array_column($report_data, 'amount'));
                        foreach ($report_data as $row): 
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
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('financialChart').getContext('2d');
    
    <?php if ($report_type === 'profit_loss'): ?>
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: <?php echo json_encode($chart_labels); ?>,
            datasets: [
                {
                    label: 'Income',
                    data: <?php echo json_encode($chart_payments); ?>,
                    backgroundColor: 'rgba(54, 162, 235, 0.7)',
                    borderColor: 'rgba(54, 162, 235, 1)',
                    borderWidth: 1
                },
                {
                    label: 'Expenses',
                    data: <?php echo json_encode($chart_expenses); ?>,
                    backgroundColor: 'rgba(255, 99, 132, 0.7)',
                    borderColor: 'rgba(255, 99, 132, 1)',
                    borderWidth: 1
                },
                {
                    label: 'Profit/Loss',
                    data: <?php echo json_encode($chart_profit); ?>,
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
                    beginAtZero: true
                }
            }
        }
    });
    <?php else: ?>
    new Chart(ctx, {
        type: 'pie',
        data: {
            labels: <?php echo json_encode($chart_labels); ?>,
            datasets: [{
                data: <?php echo json_encode($chart_expenses); ?>,
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
    <?php endif; ?>
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>