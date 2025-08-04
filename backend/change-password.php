<?php
session_start();
require_once 'db.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Log the request
error_log("Change password request received: " . json_encode($_POST));

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    error_log("User not logged in for change password");
    echo json_encode([
        'success' => false,
        'message' => 'User not logged in'
    ]);
    exit;
}

// Check if request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method'
    ]);
    exit;
}

// Get form data
$currentPassword = $_POST['current_password'] ?? '';
$newPassword = $_POST['new_password'] ?? '';
$confirmPassword = $_POST['confirm_password'] ?? '';

error_log("Form data received - Current: " . (empty($currentPassword) ? 'empty' : 'filled') . 
          ", New: " . (empty($newPassword) ? 'empty' : 'filled') . 
          ", Confirm: " . (empty($confirmPassword) ? 'empty' : 'filled'));

// Validate input
if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
    error_log("Validation failed: missing fields");
    echo json_encode([
        'success' => false,
        'message' => 'All fields are required'
    ]);
    exit;
}

// Check if new password matches confirm password
if ($newPassword !== $confirmPassword) {
    echo json_encode([
        'success' => false,
        'message' => 'New password and confirm password do not match'
    ]);
    exit;
}

// Check password length
if (strlen($newPassword) < 6) {
    echo json_encode([
        'success' => false,
        'message' => 'New password must be at least 6 characters long'
    ]);
    exit;
}

$userId = $_SESSION['user_id'];

try {
    // Get current user's password from database
    $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode([
            'success' => false,
            'message' => 'User not found'
        ]);
        exit;
    }
    
    $user = $result->fetch_assoc();
    $currentHashedPassword = $user['password'];
    
    // Verify current password
    if (!password_verify($currentPassword, $currentHashedPassword)) {
        error_log("Password verification failed for user ID: " . $userId);
        echo json_encode([
            'success' => false,
            'message' => 'Current password is incorrect'
        ]);
        exit;
    }
    
    error_log("Password verification successful for user ID: " . $userId);
    
    // Hash the new password
    $newHashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
    
    // Update password in database
    $updateStmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
    $updateStmt->bind_param('si', $newHashedPassword, $userId);
    
    if ($updateStmt->execute()) {
        error_log("Password updated successfully for user ID: " . $userId);
        echo json_encode([
            'success' => true,
            'message' => 'Password changed successfully'
        ]);
    } else {
        error_log("Failed to update password for user ID: " . $userId . " - Error: " . $updateStmt->error);
        echo json_encode([
            'success' => false,
            'message' => 'Failed to update password'
        ]);
    }
    
    $updateStmt->close();
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred while changing password'
    ]);
}

$stmt->close();
$conn->close();
?> 