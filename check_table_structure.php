<?php
/**
 * Script to check the current structure of animal_assignments table
 */

require_once 'config/database.php';

// Get database connection
$database = new Database();
$pdo = $database->getConnection();

try {
    // Check if table exists and show its structure
    $stmt = $pdo->query("DESCRIBE animal_assignments");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Current structure of animal_assignments table:\n";
    foreach ($columns as $column) {
        echo "- " . $column['Field'] . " (" . $column['Type'] . ")\n";
    }
    
} catch (PDOException $e) {
    if ($e->getCode() == '42S02') {
        echo "Table animal_assignments does not exist.\n";
    } else {
        echo "Error: " . $e->getMessage() . "\n";
    }
}
?>
