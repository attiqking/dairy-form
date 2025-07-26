<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE");
header("Access-Control-Allow-Headers: Content-Type");

$db_host = "localhost";
$db_name = "sohailpk_DairyFarm";
$db_user = "sohailpk_DairyFarm";
$db_pass = "G~vc2,s*S;HuqOn%";

try {
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

$request = $_SERVER['REQUEST_METHOD'];
$uri = $_SERVER['REQUEST_URI'];
$parts = explode('/', trim($uri, '/'));

// Handle different endpoints
switch ($parts[1] ?? '') {
    case 'animals':
        handleAnimals($request, $pdo);
        break;
    case 'milk':
        handleMilkProduction($request, $pdo);
        break;
    case 'health':
        handleHealthRecords($request, $pdo);
        break;
    case 'expenses':
        handleExpenses($request, $pdo);
        break;
    case 'payments':
        handlePayments($request, $pdo);
        break;
    default:
        http_response_code(404);
        echo json_encode(['error' => 'Endpoint not found']);
}

function handleAnimals($request, $pdo) {
    if ($request === 'GET') {
        $stmt = $pdo->query("SELECT * FROM animals");
        $animals = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode($animals);
    } elseif ($request === 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);
        $stmt = $pdo->prepare("INSERT INTO animals (tag_number, breed, date_of_birth, purchase_date, purchase_price, status) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $data['tag_number'],
            $data['breed'],
            $data['date_of_birth'],
            $data['purchase_date'],
            $data['purchase_price'],
            $data['status']
        ]);
        echo json_encode(['id' => $pdo->lastInsertId()]);
    }
    // Add PUT and DELETE handlers as needed
}

// Similar functions for milk production, health records, expenses, payments
// ...