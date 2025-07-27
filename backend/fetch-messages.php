<?php
session_start();
header('Content-Type: application/json');
require_once 'db.php';

$currentUserId = $_SESSION['user_id'] ?? null;
$targetUserId = $_GET['userId'] ?? null;

if (!$currentUserId || !$targetUserId) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request']);
    exit;
}

// Fetch messages between current user and target user
$query = "SELECT * FROM messages 
          WHERE (sender_id = ? AND receiver_id = ?)
             OR (sender_id = ? AND receiver_id = ?)
          ORDER BY created_at ASC";

$stmt = $conn->prepare($query);
$stmt->bind_param('iiii', $currentUserId, $targetUserId, $targetUserId, $currentUserId);
$stmt->execute();
$result = $stmt->get_result();

$messages = [];
while ($row = $result->fetch_assoc()) {
    $messages[] = [
        'message' => $row['message'],
        'time' => date('h:i A', strtotime($row['created_at'])),
        'is_sent_by_current_user' => $row['sender_id'] == $currentUserId
    ];
}

// Fetch user details (with last_active if available)
$userQuery = $conn->prepare("SELECT id, name, picture, last_active FROM users WHERE id = ?");
$userQuery->bind_param('i', $targetUserId);
$userQuery->execute();
$userResult = $userQuery->get_result();
$userData = $userResult->fetch_assoc();

// Check online status if last_active exists
if (isset($userData['last_active'])) {
    $userData['is_online'] = (strtotime($userData['last_active']) >= strtotime('-5 minutes')) ? true : false;
} else {
    $userData['is_online'] = false;
}

echo json_encode(['status' => 'success', 'messages' => $messages, 'user' => $userData]);
