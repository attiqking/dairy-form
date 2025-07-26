<?php
require 'config/database.php';

header("Content-Type: application/json");

// Get all animals
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $stmt = $pdo->query("SELECT * FROM animals");
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
}

// Add new animal
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    $stmt = $pdo->prepare("INSERT INTO animals 
        (tag_number, breed, date_of_birth, purchase_date, purchase_price, status) 
        VALUES (?, ?, ?, ?, ?, ?)");
    
    $stmt->execute([
        $data['tag_number'],
        $data['breed'],
        $data['date_of_birth'],
        $data['purchase_date'],
        $data['purchase_price'] ?? null,
        $data['status'] ?? 'Active'
    ]);
    
    echo json_encode(['id' => $pdo->lastInsertId()]);
}