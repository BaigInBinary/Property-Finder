<?php
session_start();
require_once 'db.php'; // assumes $conn is your mysqli connection

header('Content-Type: application/json');

$userId = $_SESSION['user_id'] ?? null;

if (!$userId) {
    echo json_encode(['success' => false, 'message' => 'User not authenticated']);
    exit;
}

// Retrieve form data
$propertyId = $_POST['property_id'] ?? '';
$title = $_POST['title'] ?? '';
$price = $_POST['price'] ?? '';
$city = $_POST['city'] ?? '';
$area = $_POST['area'] ?? '';
$unit = $_POST['unit'] ?? '';
$type = $_POST['type'] ?? '';

// Validate required fields
if (!$propertyId || !$title || !$price || !$city || !$area || !$unit || !$type) {
    echo json_encode(['success' => false, 'message' => 'All fields are required.']);
    exit;
}

// Update query
$query = "UPDATE properties SET title=?, price=?, city=?, area=?, unit=?, type=? WHERE id=? AND user_id=?";
$stmt = $conn->prepare($query);

if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Prepare failed: ' . $conn->error]);
    exit;
}

$stmt->bind_param("sdssssii", $title, $price, $city, $area, $unit, $type, $propertyId, $userId);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Property updated successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Update failed: ' . $stmt->error]);
}

$stmt->close();
?>
