<?php
session_start();
header('Content-Type: application/json');
require_once 'db.php';

$currentUserId = $_SESSION['user_id'] ?? null;

if (!$currentUserId) {
    echo json_encode(['status' => 'error', 'message' => 'User not logged in']);
    exit;
}

// Read the JSON body
$input = json_decode(file_get_contents('php://input'), true);
$receiverId = $input['receiver_id'] ?? null;
$message = trim($input['message'] ?? '');

if (!$receiverId || empty($message)) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid input']);
    exit;
}

$query = "INSERT INTO messages (sender_id, receiver_id, message) VALUES (?, ?, ?)";
$stmt = $conn->prepare($query);
$stmt->bind_param('iis', $currentUserId, $receiverId, $message);

if ($stmt->execute()) {
    echo json_encode(['status' => 'success', 'message' => 'Message sent']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Failed to send message']);
}
