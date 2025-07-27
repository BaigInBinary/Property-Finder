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

// Check if property exists and fetch owner user_id
$stmt = $conn->prepare("SELECT user_id FROM properties WHERE id = ?");
$stmt->bind_param('i', $propertyId);
$stmt->execute();
$result = $stmt->get_result();
$property = $result->fetch_assoc();
$stmt->close();

if (!$property) {
    echo json_encode(['status' => 'error', 'message' => 'Property not found']);
    exit;
}

if ($property['user_id'] == $userId) {
    echo json_encode(['status' => 'error', 'message' => 'You cannot save your own property']);
    exit;
}

// Check if property is already saved
$stmt = $conn->prepare("SELECT id FROM saved_properties WHERE user_id = ? AND property_id = ?");
$stmt->bind_param('ii', $userId, $propertyId);
$stmt->execute();
$result = $stmt->get_result();
$existing = $result->fetch_assoc();
$stmt->close();

if ($existing) {
    // Property already saved => unsave it
    $deleteStmt = $conn->prepare("DELETE FROM saved_properties WHERE user_id = ? AND property_id = ?");
    $deleteStmt->bind_param('ii', $userId, $propertyId);
    if ($deleteStmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'Property unsaved', 'is_saved' => 0]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to unsave property', 'is_saved' => 1]);
    }
    $deleteStmt->close();
} else {
    // Not saved yet => save it
    $insertStmt = $conn->prepare("INSERT INTO saved_properties (user_id, property_id, saved_at) VALUES (?, ?, NOW())");
    $insertStmt->bind_param('ii', $userId, $propertyId);
    if ($insertStmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'Property saved', 'is_saved' => 1]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to save property', 'is_saved' => 0]);
    }
    $insertStmt->close();
}
