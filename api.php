<?php
header('Content-Type: application/json');
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/database.php';


if (!$auth->isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}


$conn = $database->getConnection();

$requestMethod = $_SERVER['REQUEST_METHOD'];
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$uriSegments = explode('/', $uri);

// Simple routing
if ($uriSegments[count($uriSegments) - 1] === 'api.php') {
    $endpoint = 'dashboard';
} else {
    $endpoint = $uriSegments[count($uriSegments) - 1];
}

switch ($endpoint) {
    case 'milk_production':
        if ($requestMethod === 'GET') {
            // Get milk production data for charts
            $query = "SELECT date, SUM(quantity) as total 
                      FROM milk_production 
                      WHERE date BETWEEN DATE_SUB(CURDATE(), INTERVAL 30 DAY) AND CURDATE() 
                      GROUP BY date 
                      ORDER BY date";
            $result = $conn->query($query);
            $data = [];
            while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
                $data[] = $row;
            }
            echo json_encode($data);
        }
        break;
        
    case 'expenses':
        if ($requestMethod === 'GET') {
            // Get expenses data for charts
            $query = "SELECT category, SUM(amount) as total 
                      FROM expenses 
                      WHERE MONTH(date) = MONTH(CURDATE()) 
                      GROUP BY category";
            $result = $conn->query($query);
            $data = [];
            while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
                $data[] = $row;
            }
            echo json_encode($data);
        }
        break;
        
    default:
        http_response_code(404);
        echo json_encode(['error' => 'Endpoint not found']);
        break;
}
?>