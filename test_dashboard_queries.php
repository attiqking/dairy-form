<?php
/**
 * Test script to verify the dashboard queries work correctly
 */

require_once 'config/database.php';

// Get database connection
$database = new Database();
$pdo = $database->getConnection();

try {
    echo "Testing dashboard queries...\n\n";
    
    // Test 1: Check if we can query animal_assignments with end_date
    echo "Test 1: Querying animal assignments...\n";
    $query = "SELECT a.id, a.tag_number, a.breed, a.status 
              FROM animals a
              JOIN animal_assignments aa ON a.id = aa.animal_id
              WHERE aa.user_id = ? AND aa.end_date IS NULL";
    $stmt = $pdo->prepare($query);
    $stmt->execute([1]); // Test with user ID 1
    $animals = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "Found " . count($animals) . " assigned animals for user 1\n";
    
    // Test 2: Check health alerts query
    echo "\nTest 2: Querying health alerts...\n";
    $query = "SELECT h.record_date as date, h.description, a.tag_number 
              FROM health_records h
              JOIN animals a ON h.animal_id = a.id
              JOIN animal_assignments aa ON a.id = aa.animal_id
              WHERE aa.user_id = ? AND h.next_action_date <= DATE_ADD(CURDATE(), INTERVAL 3 DAY)
              ORDER BY h.next_action_date";
    $stmt = $pdo->prepare($query);
    $stmt->execute([1]); // Test with user ID 1
    $health_alerts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "Found " . count($health_alerts) . " health alerts for user 1\n";
    
    echo "\nAll tests passed! The dashboard should work correctly now.\n";
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
?>
