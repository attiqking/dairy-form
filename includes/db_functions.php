<?php
function getAnimalById($id) {
    global $conn;
    $stmt = $conn->prepare("SELECT * FROM animals WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->get_result()->fetch_assoc();
}

// Other DB helper functions...
?>