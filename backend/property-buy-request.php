<?php
header('Content-Type: application/json');
include 'db.php';

$data = json_decode(file_get_contents('php://input'), true);

$property_id = $data['property_id'] ?? null;
$email = $data['email'] ?? null;
$card = $data['card'] ?? null;
$expiry = $data['expiry'] ?? null;
$cvv = $data['cvv'] ?? null;

if (!$property_id || !$email || !$card || !$expiry || !$cvv) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields.']);
    exit;
}

// Optionally, get user_id from session if logged in
session_start();
$user_id = $_SESSION['user_id'] ?? null;

// Check if property is already sold
$stmt = $conn->prepare("SELECT status FROM properties WHERE id = ?");
$stmt->bind_param('i', $property_id);
$stmt->execute();
$stmt->bind_result($property_status);
$stmt->fetch();
$stmt->close();

if ($property_status === 'sold') {
    echo json_encode(['success' => false, 'message' => 'This property is already sold.']);
    exit;
}

// Save request to DB (status: pending)
$stmt = $conn->prepare("INSERT INTO property_buy_requests (property_id, user_id, email, card_number, expiry, cvv, status, created_at) VALUES (?, ?, ?, ?, ?, ?, 'pending', NOW())");
$stmt->bind_param('iissss', $property_id, $user_id, $email, $card, $expiry, $cvv);
if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Database error.']);
} 
