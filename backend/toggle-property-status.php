<?php
session_start();
require_once 'db.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Login required']);
    exit;
}

$userId = $_SESSION['user_id'];

// Fetch user details
$sql = "SELECT * FROM users WHERE id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $userId);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($result);

// Check if user is admin
if (!$user || strtolower($user['role']) !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit;
}

// Check if property ID and status are provided
if (!isset($_POST['id']) || !is_numeric($_POST['id'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid property ID']);
    exit;
}

if (!isset($_POST['status']) || !in_array($_POST['status'], ['active', 'inactive', 'pending'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid status']);
    exit;
}

$propertyId = (int)$_POST['id'];
$newStatus = $_POST['status'];

// Check if property exists
$checkQuery = "SELECT id FROM properties WHERE id = ?";
$stmt = mysqli_prepare($conn, $checkQuery);
mysqli_stmt_bind_param($stmt, "i", $propertyId);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) === 0) {
    echo json_encode(['success' => false, 'message' => 'Property not found']);
    exit;
}

// Update property status
$updateQuery = "UPDATE properties SET status = ? WHERE id = ?";
$stmt = mysqli_prepare($conn, $updateQuery);
mysqli_stmt_bind_param($stmt, "si", $newStatus, $propertyId);

if (mysqli_stmt_execute($stmt)) {
    $action = $newStatus === 'active' ? 'activated' : 'deactivated';
    echo json_encode(['success' => true, 'message' => "Property $action successfully"]);
} else {
    echo json_encode(['success' => false, 'message' => 'Error updating property status']);
}

mysqli_stmt_close($stmt);
mysqli_close($conn);
?> 