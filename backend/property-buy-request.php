<?php
header('Content-Type: application/json');
include 'db.php';

$data = json_decode(file_get_contents('php://input'), true);

$property_id = $data['property_id'] ?? null;
$email = $data['email'] ?? null;
$cardholder_name = $data['cardholder_name'] ?? null;
$card = $data['card'] ?? null;
$expiry = $data['expiry'] ?? null;
$cvv = $data['cvv'] ?? null;

if (!$property_id || !$email || !$cardholder_name || !$card || !$expiry || !$cvv) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields.']);
    exit;
}

// Validate cardholder name format
if (!preg_match('/^[a-zA-Z\s]+$/', $cardholder_name)) {
    echo json_encode(['success' => false, 'message' => 'Cardholder name should only contain letters and spaces.']);
    exit;
}

// Validate cardholder name length
if (strlen(trim($cardholder_name)) < 2) {
    echo json_encode(['success' => false, 'message' => 'Please enter a valid cardholder name (at least 2 characters).']);
    exit;
}

// Validate expiry date on server side
function validateExpiryDate($expiry) {
    if (!preg_match('/^\d{2}\/\d{2}$/', $expiry)) {
        return false;
    }
    
    list($month, $year) = explode('/', $expiry);
    $currentDate = new DateTime();
    $currentYear = (int)$currentDate->format('y'); // Get last 2 digits
    $currentMonth = (int)$currentDate->format('m');
    
    $expMonth = (int)$month;
    $expYear = (int)$year;
    
    // Debug logging
    error_log("Expiry validation: input=$expiry, month=$expMonth, year=$expYear, currentYear=$currentYear, currentMonth=$currentMonth");
    
    // Check if month is valid (1-12)
    if ($expMonth < 1 || $expMonth > 12) {
        error_log("Invalid month: $expMonth");
        return false;
    }
    
    // Check if expiry date is in the future
    if ($expYear < $currentYear) {
        error_log("Year in past: $expYear < $currentYear");
        return false;
    }
    if ($expYear === $currentYear && $expMonth < $currentMonth) {
        error_log("Month in past for current year: $expMonth < $currentMonth");
        return false;
    }
    
    return true;
}

if (!validateExpiryDate($expiry)) {
    echo json_encode(['success' => false, 'message' => 'Invalid expiry date. Card must not be expired.']);
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
// Note: If cardholder_name column doesn't exist in database, you may need to add it
$stmt = $conn->prepare("INSERT INTO property_buy_requests (property_id, user_id, email, cardholder_name, card_number, expiry, cvv, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, 'pending', NOW())");
$stmt->bind_param('iisssss', $property_id, $user_id, $email, $cardholder_name, $card, $expiry, $cvv);
if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Database error.']);
} 
