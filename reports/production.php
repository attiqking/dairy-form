<?php
// reports/production.php
require_once __DIR__ . '/../includes/bootstrap.php';



if (!$auth->isLoggedIn()) {
    header("Location: " . BASE_URL . "/auth/login.php");
    exit();
}


$conn = $database->getConnection();

// Get filter parameters
$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-t');
$group_by = $_GET['group_by'] ?? 'daily';

// Get milk production data
$query = "SELECT ";
if ($group_by === 'daily') {
    $query .= "DATE(date) as period, SUM(quantity) as total";
} else {
    $query .= "CONCAT(YEAR(date), '-', MONTH(date)) as period, SUM(quantity) as total";
}

$query .= " FROM milk_production 
           WHERE date BETWEEN ? AND ?
           GROUP BY period
           ORDER BY period";

$stmt = $conn->prepare($query);
$stmt->bind_param("ss", $start_date, $end_date);
$stmt->execute();
$result = $stmt->get_result();
$production_data = [];
while ($row = $result->fetch_assoc()) {
    $production_data[] = $row;
}

// Get animals with production
$animals = [];
$query = "SELECT a.id, a.tag_number, SUM(m.quantity) as total 
          FROM milk_production m
          JOIN animals a ON m.animal_id = a.id
          WHERE m.date BETWEEN ? AND ?
          GROUP BY a.id
          ORDER BY total DESC";
$stmt = $conn->prepare($query);
$stmt->bind_param("ss", $start_date, $end_date);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $animals[] = $row;
}

// Calculate totals
$total_production = array_sum(array_column($production_data, 'total'));
$avg_production = $total_production / (count($production_data) ?: 1);
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Milk Production Report</h1>
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
                <div class="col-md-3">
                    <label for="group_by" class="form-label">Group By</label>
                    <select class="form-select" id="group_by" name="group_by">
                        <option value="daily" <?php echo $group_by === 'daily' ? 'selected' : ''; ?>>Daily</option>
                        <option value="monthly" <?php echo $group_by === 'monthly' ? 'selected' : ''; ?>>Monthly</option>
                    </select>
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary">Generate Report</button>
                </div>
            </form>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card text-white bg-primary mb-3">
                <div class="card-body">
                    <h5 class="card-title">Total Production</h5>
                    <p class="card-text h2"><?php echo number_format($total_production, 2); ?> L</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card text-white bg-success mb-3">
                <div class="card-body">
                    <h5 class="card-title">Average Per <?php echo $group_by === 'daily' ? 'Day' : 'Month'; ?></h5>
                    <p class="card-text h2"><?php echo number_format($avg_production, 2); ?> L</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card text-white bg-info mb-3">
                <div class="card-body">
                    <h5 class="card-title">Reporting Period</h5>
                    <p class="card-text h2"><?php echo date('M j, Y', strtotime($start_date)) . ' - ' . date('M j, Y', strtotime($end_date)); ?></p>
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header">
            <h5>Production Trend</h5>
        </div>
        <div class="card-body">
            <canvas id="productionChart" height="100"></canvas>
        </div>
    </div>

    <div class="row">
        <div class="col-md-8">
            <div class="card mb-4">
                <div class="card-header">
                    <h5>Production Data</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th><?php echo $group_by === 'daily' ? 'Date' : 'Month'; ?></th>
                                    <th>Total Production (L)</th>
                                    <th>% of Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($production_data as $row): ?>
                                <tr>
                                    <td>
                                        <?php 
                                        if ($group_by === 'daily') {
                                            echo date('M j, Y', strtotime($row['period']));
                                        } else {
                                            list($year, $month) = explode('-', $row['period']);
                                            echo date('F Y', mktime(0, 0, 0, $month, 1, $year));
                                        }
                                        ?>
                                    </td>
                                    <td><?php echo number_format($row['total'], 2); ?></td>
                                    <td>
                                        <div class="progress">
                                            <div class="progress-bar" role="progressbar" 
                                                 style="width: <?php echo ($row['total'] / $total_production) * 100; ?>%" 
                                                 aria-valuenow="<?php echo ($row['total'] / $total_production) * 100; ?>" 
                                                 aria-valuemin="0" aria-valuemax="100">
                                                <?php echo round(($row['total'] / $total_production) * 100, 1); ?>%
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card mb-4">
                <div class="card-header">
                    <h5>Top Producing Animals</h5>
                </div>
                <div class="card-body">
                    <?php if (!empty($animals)): ?>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Animal</th>
                                    <th>Total (L)</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($animals as $animal): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($animal['tag_number']); ?></td>
                                    <td><?php echo number_format($animal['total'], 2); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                    <p>No production data available for selected period.</p>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <h5>Export Report</h5>
                </div>
                <div class="card-body">
                    <a href="<?php echo BASE_URL; ?>/reports/generate_pdf.php?type=production&start_date=<?php echo urlencode($start_date); ?>&end_date=<?php echo urlencode($end_date); ?>&group_by=<?php echo urlencode($group_by); ?>" 
                       class="btn btn-outline-primary w-100 mb-2">
                        <i class="bi bi-file-earmark-pdf"></i> Export as PDF
                    </a>
                    <a href="<?php echo BASE_URL; ?>/reports/generate_csv.php?type=production&start_date=<?php echo urlencode($start_date); ?>&end_date=<?php echo urlencode($end_date); ?>&group_by=<?php echo urlencode($group_by); ?>" 
                       class="btn btn-outline-success w-100">
                        <i class="bi bi-file-earmark-excel"></i> Export as CSV
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('productionChart').getContext('2d');
    
    const labels = <?php echo json_encode(array_map(function($row) use ($group_by) {
        if ($group_by === 'daily') {
            return date('M j', strtotime($row['period']));
        } else {
            list($year, $month) = explode('-', $row['period']);
            return date('M Y', mktime(0, 0, 0, $month, 1, $year));
        }
    }, $production_data)); ?>;
    
    const data = <?php echo json_encode(array_column($production_data, 'total')); ?>;
    
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: 'Milk Production (L)',
                data: data,
                backgroundColor: 'rgba(54, 162, 235, 0.2)',
                borderColor: 'rgba(54, 162, 235, 1)',
                borderWidth: 2,
                tension: 0.1,
                fill: true
            }]
        },
        options: {
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>