<?php
session_start();
require_once 'db.php';

$response = ['success' => false];

if (isset($_SESSION['user_id'])) {
    $userId = $_SESSION['user_id'];
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($userData = $result->fetch_assoc()) {
        $response['success'] = true;
        $response['data'] = $userData;
    }
}

header('Content-Type: application/json');
echo json_encode($response);
