<?php
session_start();
require_once 'db.php'; // $conn (mysqli)

header('Content-Type: application/json');

$userId = $_SESSION['user_id'] ?? null;
$propertyId = $_POST['property_id'] ?? null;

if (!$userId || !$propertyId) {
    echo json_encode(['success' => false, 'message' => 'Invalid request.']);
    exit;
}

$stmt = $conn->prepare("DELETE FROM properties WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $propertyId, $userId);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to delete property.']);
}

$stmt->close();
?>
