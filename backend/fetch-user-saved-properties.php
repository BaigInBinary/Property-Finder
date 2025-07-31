<?php
session_start();
header('Content-Type: application/json');
require_once 'db.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'User not logged in']);
    exit;
}

$userId = $_SESSION['user_id'];

// Get all saved properties for this user
$query = "SELECT p.id, p.title, p.price, p.type, p.area, p.unit, p.location, p.images_json
          FROM saved_properties sp
          JOIN properties p ON p.id = sp.property_id
          WHERE sp.user_id = ?
          ORDER BY sp.saved_at DESC";

$stmt = $conn->prepare($query);
$stmt->bind_param('i', $userId);
$stmt->execute();
$result = $stmt->get_result();

$properties = [];
while ($row = $result->fetch_assoc()) {
    $row['images'] = json_decode($row['images_json'] ?? '[]', true);
    $properties[] = $row;
}

echo json_encode(['status' => 'success', 'properties' => $properties]);

exit;
?> 