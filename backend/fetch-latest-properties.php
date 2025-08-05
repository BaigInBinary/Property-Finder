<?php
session_start();
require_once 'db.php';

$userId = $_SESSION['user_id'] ?? 0;

// Step 1: Fetch all saved property IDs for current user
$savedPropertyIds = [];
if ($userId) {
    $savedResult = $conn->prepare("SELECT property_id FROM saved_properties WHERE user_id = ?");
    $savedResult->bind_param('i', $userId);
    $savedResult->execute();
    $savedResult->bind_result($savedPropertyId);
    while ($savedResult->fetch()) {
        $savedPropertyIds[] = $savedPropertyId;
    }
    $savedResult->close();
}

// Step 2: Fetch latest properties
$query = "SELECT p.*, u.name AS user_name 
FROM properties p 
JOIN users u ON u.id = p.user_id 
WHERE p.listing = 'approved'
ORDER BY p.created_at DESC 
LIMIT 4
";

$result = $conn->query($query);

if (!$result) {
    echo json_encode(['status' => 'error', 'message' => 'Query failed: ' . $conn->error]);
    exit;
}

$properties = [];
while ($row = $result->fetch_assoc()) {
    $row['images'] = json_decode($row['images_json'] ?? '[]', true);
    // Check if current property is saved
    $row['is_saved'] = in_array($row['id'], $savedPropertyIds) ? 1 : 0;
    $properties[] = $row;
}

echo json_encode(['status' => 'success', 'properties' => $properties]);
