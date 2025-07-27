<?php
header('Content-Type: application/json');
include 'db.php';
session_start();

$user_id = $_SESSION['user_id'] ?? null;
$property_id = $_POST['property_id'] ?? null;
$rating = $_POST['rating'] ?? null;
$review = trim($_POST['review'] ?? '');

if (!$user_id) {
    echo json_encode(['success' => false, 'message' => 'You must be logged in to review.']);
    exit;
}
if (!$property_id || !$rating || $rating < 1 || $rating > 5) {
    echo json_encode(['success' => false, 'message' => 'Invalid input.']);
    exit;
}
// Ensure the table property_reviews exists in your database with the correct schema for robust operation.
// Prevent multiple reviews by the same user for the same property
$check = $conn->prepare("SELECT id FROM property_reviews WHERE property_id = ? AND user_id = ?");
$check->bind_param('ii', $property_id, $user_id);
$check->execute();
$check->store_result();
if ($check->num_rows > 0) {
    echo json_encode(['success' => false, 'message' => 'You have already reviewed this property.']);
    exit;
}
$check->close();

$stmt = $conn->prepare("INSERT INTO property_reviews (property_id, user_id, rating, review) VALUES (?, ?, ?, ?)");
$stmt->bind_param('iiis', $property_id, $user_id, $rating, $review);
if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to add review.']);
}
$stmt->close(); 