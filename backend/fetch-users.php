<?php
session_start();
header('Content-Type: application/json');
require_once 'db.php'; // Ensure $conn is a valid mysqli connection

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'User not logged in']);
    exit;
}

$currentUserId = $_SESSION['user_id'];

// Optional: Update current user's last_active timestamp
$update = $conn->prepare("UPDATE users SET last_active = NOW() WHERE id = ?");
if ($update) {
    $update->bind_param('i', $currentUserId);
    $update->execute();
}

// Fetch other users excluding current user
$query = "SELECT id, name, picture, role, last_active
          FROM users
          WHERE id != ?
          ORDER BY last_active DESC";

$stmt = $conn->prepare($query);
if (!$stmt) {
    echo json_encode(['status' => 'error', 'message' => 'Failed to prepare query.']);
    exit;
}

$stmt->bind_param('i', $currentUserId);
$stmt->execute();
$result = $stmt->get_result();

$users = [];
while ($row = $result->fetch_assoc()) {
    $row['is_online'] = (strtotime($row['last_active']) >= strtotime('-5 minutes'));
    $users[] = $row;
}

echo json_encode(['status' => 'success', 'users' => $users]);
exit;
