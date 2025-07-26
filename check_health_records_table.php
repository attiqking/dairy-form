<?php
/**
 * Script to check the structure of health_records table
 */

require_once 'config/database.php';

// Get database connection
$database = new Database();
$pdo = $database->getConnection();

try {
    // Check if table exists and show its structure
    $stmt = $pdo->query("DESCRIBE health_records");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Current structure of health_records table:\n";
    foreach ($columns as $column) {
        echo "- " . $column['Field'] . " (" . $column['Type'] . ")\n";
    }
    
} catch (PDOException $e) {
    if ($e->getCode() == '42S02') {
        echo "Table health_records does not exist.\n";
    } else {
        echo "Error: " . $e->getMessage() . "\n";
    }
}
?>
