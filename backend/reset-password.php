<?php
header('Content-Type: application/json');
include 'db.php';

$token = $_POST['token'] ?? '';
$password = $_POST['password'] ?? '';
$confirmPassword = $_POST['confirmPassword'] ?? '';

if (!$token || !$password || !$confirmPassword) {
    echo json_encode(['success' => false, 'message' => 'All fields are required.']);
    exit;
}
if ($password !== $confirmPassword) {
    echo json_encode(['success' => false, 'message' => 'Passwords do not match.']);
    exit;
}
if (strlen($password) < 8) {
    echo json_encode(['success' => false, 'message' => 'Password must be at least 8 characters.']);
    exit;
}

// Find user by token and check expiry
$stmt = $conn->prepare("SELECT id, reset_token_expiry FROM users WHERE reset_token = ?");
$stmt->bind_param('s', $token);
$stmt->execute();
$stmt->bind_result($userId, $expiry);
$stmt->fetch();
$stmt->close();

if (!$userId) {
    echo json_encode(['success' => false, 'message' => 'Invalid or expired token.']);
    exit;
}
if (strtotime($expiry) < time()) {
    echo json_encode(['success' => false, 'message' => 'Token has expired.']);
    exit;
}

// Update password and clear token
$hashed = password_hash($password, PASSWORD_BCRYPT);
$stmt = $conn->prepare("UPDATE users SET password = ?, reset_token = NULL, reset_token_expiry = NULL WHERE id = ?");
$stmt->bind_param('si', $hashed, $userId);
if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Password reset successful. You can now log in.']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to reset password.']);
}
$stmt->close(); 