<?php
/**
 * Migration script to add the missing animal_assignments table
 * Run this file once to create the table in your database
 */

require_once 'config/database.php';

// Get database connection
$database = new Database();
$pdo = $database->getConnection();

try {
    // Read and execute the migration SQL
    $sql = file_get_contents('migration_add_animal_assignments.sql');
    
    // Remove the USE statement as it might cause issues
    $sql = preg_replace('/USE\s+[^;]+;/', '', $sql);
    
    // Split by semicolon and execute each statement
    $statements = array_filter(array_map('trim', explode(';', $sql)));
    
    foreach ($statements as $statement) {
        if (!empty($statement) && !preg_match('/^\s*--/', $statement)) {
            $pdo->exec($statement);
            echo "Executed: " . substr($statement, 0, 50) . "...\n";
        }
    }
    
    echo "Migration completed successfully!\n";
    echo "The animal_assignments table has been created.\n";
    
} catch (PDOException $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
?>
