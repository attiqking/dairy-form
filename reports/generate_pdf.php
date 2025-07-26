php
<?php
// reports/generate_pdf.php
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../../config/database.php';


if (!$auth->isLoggedIn()) {
    header("Location: " . BASE_URL . "/auth/login.php");
    exit();
}

require_once __DIR__ . '/../../vendor/autoload.php'; // Require Composer autoload


$conn = $database->getConnection();

// Get report parameters
$type = $_GET['type'] ?? '';
$start_date = $_GET['start_date'] ?? '';
$end_date = $_GET['end_date'] ?? '';
$group_by = $_GET['group_by'] ?? '';
$report_type = $_GET['report_type'] ?? '';

// Validate parameters
if (empty($type) || empty($start_date) || empty($end_date)) {
    die("Invalid parameters");
}

// Create new PDF instance
$mpdf = new \Mpdf\Mpdf();

// Set PDF metadata
$mpdf->SetTitle('DairyFarm Pro Report');
$mpdf->SetAuthor('DairyFarm Pro System');
$mpdf->SetCreator('DairyFarm Pro');

// Add a page
$mpdf->AddPage();

// Report header
$mpdf->WriteHTML('<h1 style="text-align: center;">DairyFarm Pro</h1>');
$mpdf->WriteHTML('<h2 style="text-align: center;">' . ucfirst($type) . ' Report</h2>');
$mpdf->WriteHTML('<p style="text-align: center;">Period: ' . date('M j, Y', strtotime($start_date)) . ' to ' . date('M j, Y', strtotime($end_date)) . '</p>');
$mpdf->WriteHTML('<p style="text-align: center;">Generated on: ' . date('M j, Y H:i:s') . '</p>');
$mpdf->WriteHTML('<hr>');

if ($type === 'production') {
    // Generate production report
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
    $stmt->execute([$start_date, $end_date]);
    $result = $stmt->get_result();
    $production_data = [];
    while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
        $production_data[] = $row;
    }

    // Get animals with production
    $animals = [];
    $query = "SELECT a.id, a.tag_number, SUM(m.quantity) as total 
              FROM milk_production m
              JOIN animals a ON m.animal_id = a.id
              WHERE m.date BETWEEN ? AND ?
              GROUP BY a.id
              ORDER BY total DESC
              LIMIT 10";
    $stmt = $conn->prepare($query);
    $stmt->execute([$start_date, $end_date]);
    $result = $stmt->get_result();
    while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
        $animals[] = $row;
    }

    // Calculate totals
    $total_production = array_sum(array_column($production_data, 'total'));
    $avg_production = $total_production / (count($production_data) ?: 1);

    // Production summary
    $mpdf->WriteHTML('<h3>Production Summary</h3>');
    $mpdf->WriteHTML('<table border="1" cellspacing="0" cellpadding="5" width="100%">');
    $mpdf->WriteHTML('<tr><th width="50%">Metric</th><th width="50%">Value</th></tr>');
    $mpdf->WriteHTML('<tr><td>Total Production</td><td>' . number_format($total_production, 2) . ' liters</td></tr>');
    $mpdf->WriteHTML('<tr><td>Average Per ' . ($group_by === 'daily' ? 'Day' : 'Month') . '</td><td>' . number_format($avg_production, 2) . ' liters</td></tr>');
    $mpdf->WriteHTML('<tr><td>Reporting Period</td><td>' . date('M j, Y', strtotime($start_date)) . ' to ' . date('M j, Y', strtotime($end_date)) . '</td></tr>');
    $mpdf->WriteHTML('</table>');

    // Production data
    $mpdf->WriteHTML('<h3>Production Data</h3>');
    $mpdf->WriteHTML('<table border="1" cellspacing="0" cellpadding="5" width="100%">');
    $mpdf->WriteHTML('<tr><th>' . ($group_by === 'daily' ? 'Date' : 'Month') . '</th><th>Total Production (L)</th><th>% of Total</th></tr>');
    
    foreach ($production_data as $row) {
        $period = $group_by === 'daily' ? date('M j, Y', strtotime($row['period'])) : 
                 date('F Y', mktime(0, 0, 0, explode('-', $row['period'])[1], 1, explode('-', $row['period'])[0]));
        $percentage = $total_production > 0 ? ($row['total'] / $total_production) * 100 : 0;
        
        $mpdf->WriteHTML('<tr>');
        $mpdf->WriteHTML('<td>' . $period . '</td>');
        $mpdf->WriteHTML('<td>' . number_format($row['total'], 2) . '</td>');
        $mpdf->WriteHTML('<td>' . round($percentage, 1) . '%</td>');
        $mpdf->WriteHTML('</tr>');
    }
    
    $mpdf->WriteHTML('</table>');

    // Top producing animals
    if (!empty($animals)) {
        $mpdf->WriteHTML('<h3>Top Producing Animals</h3>');
        $mpdf->WriteHTML('<table border="1" cellspacing="0" cellpadding="5" width="100%">');
        $mpdf->WriteHTML('<tr><th>Animal</th><th>Total Production (L)</th></tr>');
        
        foreach ($animals as $animal) {
            $mpdf->WriteHTML('<tr>');
            $mpdf->WriteHTML('<td>' . htmlspecialchars($animal['tag_number']) . '</td>');
            $mpdf->WriteHTML('<td>' . number_format($animal['total'], 2) . '</td>');
            $mpdf->WriteHTML('</tr>');
        }
        
        $mpdf->WriteHTML('</table>');
    }
} elseif ($type === 'financial') {
    // Generate financial report
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
        $stmt->execute([$start_date, $end_date]);
        $result = $stmt->get_result();
        while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
            $income[$row['day']] = $row['total'];
        }
        
        $query = "SELECT DATE(date) as day, SUM(amount) as total 
                  FROM expenses 
                  WHERE date BETWEEN ? AND ?
                  GROUP BY day
                  ORDER BY day";
        $stmt = $conn->prepare($query);
        $stmt->execute([$start_date, $end_date]);
        $result = $stmt->get_result();
        while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
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
        
        $total_income = array_sum(array_column($profit_loss, 'income'));
        $total_expenses = array_sum(array_column($profit_loss, 'expenses'));
        $total_profit = $total_income - $total_expenses;
        
        // Financial summary
        $mpdf->WriteHTML('<h3>Financial Summary</h3>');
        $mpdf->WriteHTML('<table border="1" cellspacing="0" cellpadding="5" width="100%">');
        $mpdf->WriteHTML('<tr><th width="50%">Metric</th><th width="50%">Value</th></tr>');
        $mpdf->WriteHTML('<tr><td>Total Income</td><td>' . number_format($total_income, 2) . '</td></tr>');
        $mpdf->WriteHTML('<tr><td>Total Expenses</td><td>' . number_format($total_expenses, 2) . '</td></tr>');
        $mpdf->WriteHTML('<tr><td>Net Profit</td><td style="color: ' . ($total_profit >= 0 ? 'green' : 'red') . '">' . number_format($total_profit, 2) . '</td></tr>');
        $mpdf->WriteHTML('<tr><td>Reporting Period</td><td>' . date('M j, Y', strtotime($start_date)) . ' to ' . date('M j, Y', strtotime($end_date)) . '</td></tr>');
        $mpdf->WriteHTML('</table>');
        
        // Financial data
        $mpdf->WriteHTML('<h3>Daily Financial Data</h3>');
        $mpdf->WriteHTML('<table border="1" cellspacing="0" cellpadding="5" width="100%">');
        $mpdf->WriteHTML('<tr><th>Date</th><th>Income</th><th>Expenses</th><th>Profit/Loss</th></tr>');
        
        foreach ($profit_loss as $row) {
            $mpdf->WriteHTML('<tr>');
            $mpdf->WriteHTML('<td>' . date('M j, Y', strtotime($row['date'])) . '</td>');
            $mpdf->WriteHTML('<td>' . number_format($row['income'], 2) . '</td>');
            $mpdf->WriteHTML('<td>' . number_format($row['expenses'], 2) . '</td>');
            $mpdf->WriteHTML('<td style="color: ' . ($row['profit'] >= 0 ? 'green' : 'red') . '">' . number_format($row['profit'], 2) . '</td>');
            $mpdf->WriteHTML('</tr>');
        }
        
        $mpdf->WriteHTML('<tr style="font-weight: bold;">');
        $mpdf->WriteHTML('<td>Total</td>');
        $mpdf->WriteHTML('<td>' . number_format($total_income, 2) . '</td>');
        $mpdf->WriteHTML('<td>' . number_format($total_expenses, 2) . '</td>');
        $mpdf->WriteHTML('<td style="color: ' . ($total_profit >= 0 ? 'green' : 'red') . '">' . number_format($total_profit, 2) . '</td>');
        $mpdf->WriteHTML('</tr>');
        
        $mpdf->WriteHTML('</table>');
    } elseif ($report_type === 'expense_category') {
        // Expense by category
        $query = "SELECT category, SUM(amount) as total 
                  FROM expenses 
                  WHERE date BETWEEN ? AND ?
                  GROUP BY category
                  ORDER BY total DESC";
        $stmt = $conn->prepare($query);
        $stmt->execute([$start_date, $end_date]);
        $result = $stmt->get_result();
        while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
            $expenses[] = [
                'category' => $row['category'],
                'amount' => $row['total']
            ];
        }
        
        $total = array_sum(array_column($expenses, 'amount'));
        
        // Expense summary
        $mpdf->WriteHTML('<h3>Expense Summary by Category</h3>');
        $mpdf->WriteHTML('<table border="1" cellspacing="0" cellpadding="5" width="100%">');
        $mpdf->WriteHTML('<tr><th width="50%">Metric</th><th width="50%">Value</th></tr>');
        $mpdf->WriteHTML('<tr><td>Total Expenses</td><td>' . number_format($total, 2) . '</td></tr>');
        $mpdf->WriteHTML('<tr><td>Categories</td><td>' . count($expenses) . '</td></tr>');
        $mpdf->WriteHTML('<tr><td>Reporting Period</td><td>' . date('M j, Y', strtotime($start_date)) . ' to ' . date('M j, Y', strtotime($end_date)) . '</td></tr>');
        $mpdf->WriteHTML('</table>');
        
        // Expense data
        $mpdf->WriteHTML('<h3>Expense by Category</h3>');
        $mpdf->WriteHTML('<table border="1" cellspacing="0" cellpadding="5" width="100%">');
        $mpdf->WriteHTML('<tr><th>Category</th><th>Amount</th><th>% of Total</th></tr>');
        
        foreach ($expenses as $expense) {
            $percentage = $total > 0 ? ($expense['amount'] / $total) * 100 : 0;
            
            $mpdf->WriteHTML('<tr>');
            $mpdf->WriteHTML('<td>' . htmlspecialchars($expense['category']) . '</td>');
            $mpdf->WriteHTML('<td>' . number_format($expense['amount'], 2) . '</td>');
            $mpdf->WriteHTML('<td>' . round($percentage, 1) . '%</td>');
            $mpdf->WriteHTML('</tr>');
        }
        
        $mpdf->WriteHTML('<tr style="font-weight: bold;">');
        $mpdf->WriteHTML('<td>Total</td>');
        $mpdf->WriteHTML('<td>' . number_format($total, 2) . '</td>');
        $mpdf->WriteHTML('<td>100%</td>');
        $mpdf->WriteHTML('</tr>');
        
        $mpdf->WriteHTML('</table>');
    }
} elseif ($type === 'inventory') {
    // Generate inventory report
    $query = "SELECT i.item_name, i.category, i.unit, i.current_quantity, i.reorder_level, 
                     i.supplier, i.last_restocked
              FROM inventory i
              ORDER BY i.category, i.item_name";
    
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $result = $stmt->get_result();
    $inventory_items = [];
    while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
        $inventory_items[] = $row;
    }
    
    // Inventory summary
    $mpdf->WriteHTML('<h3>Inventory Summary</h3>');
    $mpdf->WriteHTML('<table border="1" cellspacing="0" cellpadding="5" width="100%">');
    $mpdf->WriteHTML('<tr><th width="50%">Metric</th><th width="50%">Value</th></tr>');
    $mpdf->WriteHTML('<tr><td>Total Items</td><td>' . count($inventory_items) . '</td></tr>');
    $mpdf->WriteHTML('<tr><td>Items Below Reorder Level</td><td>' . 
        count(array_filter($inventory_items, function($item) {
            return $item['current_quantity'] < $item['reorder_level'];
        })) . '</td></tr>');
    $mpdf->WriteHTML('<tr><td>Report Date</td><td>' . date('M j, Y H:i:s') . '</td></tr>');
    $mpdf->WriteHTML('</table>');
    
    // Inventory data
    $mpdf->WriteHTML('<h3>Inventory Items</h3>');
    $mpdf->WriteHTML('<table border="1" cellspacing="0" cellpadding="5" width="100%">');
    $mpdf->WriteHTML('<tr><th>Item</th><th>Category</th><th>Quantity</th><th>Unit</th><th>Reorder Level</th><th>Status</th></tr>');
    
    foreach ($inventory_items as $item) {
        $status = $item['current_quantity'] <= 0 ? 'Out of Stock' : 
                 ($item['current_quantity'] < $item['reorder_level'] ? 'Low Stock' : 'In Stock');
        $status_color = $item['current_quantity'] <= 0 ? 'red' : 
                       ($item['current_quantity'] < $item['reorder_level'] ? 'orange' : 'green');
        
        $mpdf->WriteHTML('<tr>');
        $mpdf->WriteHTML('<td>' . htmlspecialchars($item['item_name']) . '</td>');
        $mpdf->WriteHTML('<td>' . htmlspecialchars($item['category']) . '</td>');
        $mpdf->WriteHTML('<td>' . number_format($item['current_quantity'], 2) . '</td>');
        $mpdf->WriteHTML('<td>' . htmlspecialchars($item['unit']) . '</td>');
        $mpdf->WriteHTML('<td>' . number_format($item['reorder_level'], 2) . '</td>');
        $mpdf->WriteHTML('<td style="color: ' . $status_color . '">' . $status . '</td>');
        $mpdf->WriteHTML('</tr>');
    }
    
    $mpdf->WriteHTML('</table>');
} else {
    $mpdf->WriteHTML('<p>Invalid report type selected.</p>');
}

// Output the PDF
$filename = 'DairyFarmPro_' . $type . '_Report_' . date('Ymd_His') . '.pdf';
$mpdf->Output($filename, 'D');

exit();