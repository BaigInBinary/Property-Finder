<?php
session_start();
require_once 'db.php'; // Assume this returns a mysqli connection in $conn

header('Content-Type: application/json');

// Assuming session has user_id
$userId = $_SESSION['user_id'] ?? null;

if (!$userId) {
    echo json_encode(['success' => false, 'message' => 'User not authenticated']);
    exit;
}

try {
    // Prepare statement to prevent SQL injection
    $stmt = $conn->prepare("SELECT id, title, price, city, area, unit, images_json, link, type FROM properties WHERE user_id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    
    $result = $stmt->get_result();
    $properties = [];
    
    while ($row = $result->fetch_assoc()) {
        $properties[] = $row;
    }

    echo json_encode(['success' => true, 'data' => $properties]);
    
    $stmt->close();
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
?>
