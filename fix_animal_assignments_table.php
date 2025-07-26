<?php
/**
 * Migration to fix the animal_assignments table structure
 * This will add the missing columns and rename existing ones
 */

require_once 'config/database.php';

// Get database connection
$database = new Database();
$pdo = $database->getConnection();

try {
    echo "Starting migration to fix animal_assignments table...\n";
    
    // Add start_date column (rename assignment_date)
    echo "Adding start_date column...\n";
    $pdo->exec("ALTER TABLE animal_assignments ADD COLUMN start_date DATE NOT NULL DEFAULT (CURDATE())");
    
    // Copy data from assignment_date to start_date
    echo "Copying data from assignment_date to start_date...\n";
    $pdo->exec("UPDATE animal_assignments SET start_date = assignment_date WHERE assignment_date IS NOT NULL");
    
    // Add end_date column
    echo "Adding end_date column...\n";
    $pdo->exec("ALTER TABLE animal_assignments ADD COLUMN end_date DATE NULL");
    
    // Add timestamps
    echo "Adding timestamp columns...\n";
    $pdo->exec("ALTER TABLE animal_assignments ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP");
    $pdo->exec("ALTER TABLE animal_assignments ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP");
    
    // Add indexes for better performance
    echo "Adding indexes...\n";
    $pdo->exec("ALTER TABLE animal_assignments ADD INDEX idx_animal_user (animal_id, user_id)");
    $pdo->exec("ALTER TABLE animal_assignments ADD INDEX idx_active_assignments (user_id, end_date)");
    
    // Drop the old assignment_date column
    echo "Dropping old assignment_date column...\n";
    $pdo->exec("ALTER TABLE animal_assignments DROP COLUMN assignment_date");
    
    echo "Migration completed successfully!\n";
    echo "The animal_assignments table has been updated with the correct structure.\n";
    
} catch (PDOException $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
    
    // Check if the error is because column already exists
    if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
        echo "It looks like some columns already exist. This might be okay.\n";
    }
    
    exit(1);
}
?>
