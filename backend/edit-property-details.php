<?php
session_start();
require_once 'db.php'; // assumes $conn is your mysqli connection

header('Content-Type: application/json');

$userId = $_SESSION['user_id'] ?? null;

if (!$userId) {
    echo json_encode(['success' => false, 'message' => 'User not authenticated']);
    exit;
}

// Retrieve form data
$propertyId = $_POST['property_id'] ?? '';
$title = $_POST['title'] ?? '';
$price = $_POST['price'] ?? '';
$city = $_POST['city'] ?? '';
$area = $_POST['area'] ?? '';
$unit = $_POST['unit'] ?? '';
$type = $_POST['type'] ?? '';
$link = $_POST['link'] ?? '';

// Validate required fields
if (!$propertyId || !$title || !$price || !$city || !$area || !$unit || !$type) {
    echo json_encode(['success' => false, 'message' => 'All fields are required.']);
    exit;
}

// Validate map link - must be a valid Google Maps embed iframe (if provided)
if (!empty($link)) {
    // Check if it's a valid iframe embed link
    $iframePattern = '/<iframe[^>]*src=["\'](https?:\/\/www\.google\.com\/maps\/embed[^"\']*)["\'][^>]*>/i';
    $googleMapsEmbedPattern = '/https?:\/\/www\.google\.com\/maps\/embed/i';
    
    // Must be a complete iframe HTML, not just a URL
    if (!preg_match($iframePattern, $link)) {
        echo json_encode(['success' => false, 'message' => 'Please provide the complete iframe HTML code from Google Maps embed, not just the URL.']);
        exit;
    }
    
    // Additional validation for iframe structure
    if (strpos($link, '<iframe') !== false && strpos($link, 'src=') === false) {
        echo json_encode(['success' => false, 'message' => 'Invalid iframe structure. Please include the src attribute']);
        exit;
    }
    
    // Ensure it has proper iframe closing tag
    if (strpos($link, '<iframe') !== false && strpos($link, '</iframe>') === false) {
        echo json_encode(['success' => false, 'message' => 'Invalid iframe structure. Please include the complete iframe HTML with closing tag']);
        exit;
    }
}

// Update query
$query = "UPDATE properties SET title=?, price=?, city=?, area=?, unit=?, type=?, link=? WHERE id=? AND user_id=?";
$stmt = $conn->prepare($query);

if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Prepare failed: ' . $conn->error]);
    exit;
}

$stmt->bind_param("sdsssssii", $title, $price, $city, $area, $unit, $type, $link, $propertyId, $userId);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Property updated successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Update failed: ' . $stmt->error]);
}

$stmt->close();
?>
