<?php
header('Content-Type: application/json');
include 'db.php';

$property_id = $_GET['property_id'] ?? null;
if (!$property_id) {
    echo json_encode(['success' => false, 'message' => 'Property ID required.']);
    exit;
}

// Ensure the table property_reviews exists in your database with the correct schema for robust operation.
$stmt = $conn->prepare("SELECT r.rating, r.review, r.created_at, u.name AS user_name FROM property_reviews r JOIN users u ON r.user_id = u.id WHERE r.property_id = ? ORDER BY r.created_at DESC");
$stmt->bind_param('i', $property_id);
$stmt->execute();
$result = $stmt->get_result();
$reviews = [];
while ($row = $result->fetch_assoc()) {
    $reviews[] = $row;
}
$stmt->close();
echo json_encode(['success' => true, 'reviews' => $reviews]); 