<?php
header('Content-Type: application/json');
include 'db.php';

$email = trim($_POST['email'] ?? '');
if (!$email) {
    echo json_encode(['success' => false, 'message' => 'Email is required.']);
    exit;
}

// Check if user exists
$stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
$stmt->bind_param('s', $email);
$stmt->execute();
$stmt->bind_result($userId);
$stmt->fetch();
$stmt->close();

if (!$userId) {
    echo json_encode(['success' => false, 'message' => 'No account found with that email.']);
    exit;
}

// Generate token
$token = bin2hex(random_bytes(32));
$expiry = date('Y-m-d H:i:s', strtotime('+1 hour'));

// Save token and expiry in users table
$stmt = $conn->prepare("UPDATE users SET reset_token = ?, reset_token_expiry = ? WHERE id = ?");
$stmt->bind_param('ssi', $token, $expiry, $userId);
$stmt->execute();
$stmt->close();

// Send email
$resetLink = (isset($_SERVER['HTTPS']) ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . dirname(dirname($_SERVER['REQUEST_URI'])) . "/reset-password.php?token=$token";
$subject = "Password Reset Request - PropFind";
$message = "Hello,\n\nWe received a request to reset your password. Click the link below to reset it:\n$resetLink\n\nIf you did not request this, please ignore this email.\n\nThis link will expire in 1 hour.";
$headers = "From: no-reply@yourdomain.com";
@mail($email, $subject, $message, $headers);

echo json_encode(['success' => true, 'message' => 'If your email exists in our system, a reset link has been sent.']); 