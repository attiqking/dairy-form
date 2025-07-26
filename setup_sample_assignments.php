<?php
/**
 * Script to create sample animal assignments for testing
 * This will assign all animals to the first user found in the database
 */

require_once 'config/database.php';

// Get database connection
$database = new Database();
$pdo = $database->getConnection();

try {
    // Check if we have users
    $stmt = $pdo->query("SELECT id, username FROM users LIMIT 1");
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        echo "No users found in database. Please create a user first.\n";
        exit(1);
    }
    
    echo "Found user: " . $user['username'] . " (ID: " . $user['id'] . ")\n";
    
    // Check if we have animals
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM animals");
    $animalCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    if ($animalCount == 0) {
        echo "No animals found in database. Please add some animals first.\n";
        exit(1);
    }
    
    echo "Found $animalCount animals in database.\n";
    
    // Check existing assignments
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM animal_assignments WHERE end_date IS NULL");
    $assignmentCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    if ($assignmentCount > 0) {
        echo "Found $assignmentCount existing active assignments.\n";
    } else {
        echo "No active assignments found. Creating sample assignments...\n";
        
        // Create assignments for all animals to the first user
        $stmt = $pdo->prepare("
            INSERT INTO animal_assignments (animal_id, user_id, start_date)
            SELECT a.id, ?, CURDATE() 
            FROM animals a 
            WHERE NOT EXISTS (
                SELECT 1 FROM animal_assignments aa 
                WHERE aa.animal_id = a.id AND aa.end_date IS NULL
            )
        ");
        
        $stmt->execute([$user['id']]);
        $newAssignments = $stmt->rowCount();
        
        echo "Created $newAssignments new animal assignments.\n";
    }
    
    echo "Setup completed successfully!\n";
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
?>
