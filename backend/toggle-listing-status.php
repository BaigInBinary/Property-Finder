<?php
session_start();
require_once 'db.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit;
}

// Check if user is admin
$userId = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT role FROM users WHERE id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

if (!$user || strtolower($user['role']) !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Access denied. Admin privileges required.']);
    exit;
}

// Check if required parameters are provided
if (!isset($_POST['id']) || !isset($_POST['action'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
    exit;
}

$propertyId = intval($_POST['id']);
$action = $_POST['action'];

// Determine new listing status based on action
$newListing = ($action === 'approve') ? 'approved' : 'rejected';

// Validate property ID
if ($propertyId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid property ID']);
    exit;
}

// Validate action
if (!in_array($action, ['approve', 'reject'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid action']);
    exit;
}

// Validate listing status
if (!in_array($newListing, ['approved', 'rejected'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid listing status']);
    exit;
}

// Get property details
$stmt = $conn->prepare("SELECT p.*, u.name as owner_name, u.email as owner_email 
                       FROM properties p 
                       LEFT JOIN users u ON p.user_id = u.id 
                       WHERE p.id = ?");
$stmt->bind_param("i", $propertyId);
$stmt->execute();
$result = $stmt->get_result();
$property = $result->fetch_assoc();
$stmt->close();

if (!$property) {
    echo json_encode(['success' => false, 'message' => 'Property not found']);
    exit;
}

// Update property listing status
$currentTime = date('Y-m-d H:i:s');

$stmt = $conn->prepare("UPDATE properties SET listing = ?, updated_at = ? WHERE id = ?");
$stmt->bind_param("ssi", $newListing, $currentTime, $propertyId);

if ($stmt->execute()) {
    echo json_encode([
        'success' => true, 
        'message' => 'Property ' . $action . 'd successfully',
        'property_id' => $propertyId,
        'new_listing_status' => $newListing
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to update property listing status']);
}

$stmt->close();
$conn->close();
?> 