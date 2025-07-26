<?php
// milk/production.php
require_once __DIR__ . '/../includes/bootstrap.php';



if (!$auth->isLoggedIn()) {
    header("Location: " . BASE_URL . "/auth/login.php");
    exit();
}


$conn = $database->getConnection();

// Get filter parameters
$start_date = $_GET['start_date'] ?? date('Y-m-d', strtotime('-7 days'));
$end_date = $_GET['end_date'] ?? date('Y-m-d');
$animal_id = $_GET['animal_id'] ?? '';

// Get milk production data
$query = "SELECT m.date, m.session, a.tag_number, a.breed, m.quantity, m.notes 
          FROM milk_production m
          JOIN animals a ON m.animal_id = a.id
          WHERE m.date BETWEEN ? AND ?";
$params = [$start_date, $end_date];
$types = "ss";

if (!empty($animal_id)) {
    $query .= " AND m.animal_id = ?";
    $params[] = $animal_id;
    $types .= "i";
}

$query .= " ORDER BY m.date DESC, m.session";

$stmt = $conn->prepare($query);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();
$production_data = [];
while ($row = $result->fetch_assoc()) {
    $production_data[] = $row;
}

// Get animals for filter dropdown
$animals = [];
$query = "SELECT id, tag_number, breed FROM animals ORDER BY tag_number";
$result = $conn->query($query);
while ($row = $result->fetch_assoc()) {
    $animals[] = $row;
}

// Calculate totals
$total_production = array_sum(array_column($production_data, 'quantity'));
$avg_daily = $total_production / (count(array_unique(array_column($production_data, 'date'))) ?: 1);
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
                <div class="col-md-4">
                    <label for="animal_id" class="form-label">Animal (Optional)</label>
                    <select class="form-select" id="animal_id" name="animal_id">
                        <option value="">All Animals</option>
                        <?php foreach ($animals as $animal): ?>
                        <option value="<?php echo $animal['id']; ?>" <?php echo $animal_id == $animal['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($animal['tag_number'] . ' (' . $animal['breed'] . ')'); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary">Filter</button>
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
                    <h5 class="card-title">Average Daily</h5>
                    <p class="card-text h2"><?php echo number_format($avg_daily, 2); ?> L</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card text-white bg-info mb-3">
                <div class="card-body">
                    <h5 class="card-title">Records Count</h5>
                    <p class="card-text h2"><?php echo count($production_data); ?></p>
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header">
            <h5>Production Details</h5>
        </div>
        <div class="card-body">
            <?php if (!empty($production_data)): ?>
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Session</th>
                            <th>Animal</th>
                            <th>Breed</th>
                            <th>Quantity (L)</th>
                            <th>Notes</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($production_data as $record): ?>
                        <tr>
                            <td><?php echo date('M j, Y', strtotime($record['date'])); ?></td>
                            <td><?php echo $record['session']; ?></td>
                            <td><?php echo htmlspecialchars($record['tag_number']); ?></td>
                            <td><?php echo htmlspecialchars($record['breed']); ?></td>
                            <td><?php echo number_format($record['quantity'], 2); ?></td>
                            <td><?php echo htmlspecialchars($record['notes'] ?? ''); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <p>No production records found for the selected period.</p>
            <?php endif; ?>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h5>Production Chart</h5>
        </div>
        <div class="card-body">
            <canvas id="productionChart" height="100"></canvas>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('productionChart').getContext('2d');
    
    // Group data by date
    const groupedData = {};
    <?php foreach ($production_data as $record): ?>
        const date = '<?php echo $record["date"]; ?>';
        if (!groupedData[date]) {
            groupedData[date] = 0;
        }
        groupedData[date] += parseFloat(<?php echo $record['quantity']; ?>);
    <?php endforeach; ?>
    
    const dates = Object.keys(groupedData).sort();
    const quantities = dates.map(date => groupedData[date]);
    
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: dates,
            datasets: [{
                label: 'Daily Milk Production (L)',
                data: quantities,
                backgroundColor: 'rgba(54, 162, 235, 0.2)',
                borderColor: 'rgba(54, 162, 235, 1)',
                borderWidth: 1,
                tension: 0.1
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