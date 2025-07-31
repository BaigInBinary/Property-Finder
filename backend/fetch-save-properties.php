<?php
session_start();
header('Content-Type: application/json');
require_once 'db.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'User not logged in']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$propertyId = intval($data['property_id'] ?? 0);
$userId = $_SESSION['user_id'];

if ($propertyId <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid property ID']);
    exit;
}

// Check if property is saved by this user
$stmt = $conn->prepare("SELECT id FROM saved_properties WHERE user_id = ? AND property_id = ?");
$stmt->bind_param('ii', $userId, $propertyId);
$stmt->execute();
$result = $stmt->get_result();
$saved = $result->fetch_assoc();
$stmt->close();

if ($saved) {
    echo json_encode(['status' => 'success', 'is_saved' => 1, 'message' => 'Property is saved']);
} else {
    echo json_encode(['status' => 'success', 'is_saved' => 0, 'message' => 'Property is not saved']);
}
?>
