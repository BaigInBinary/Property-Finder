<?php
header('Content-Type: application/json');
include 'db.php';

$id = $_POST['id'] ?? null;
$action = $_POST['action'] ?? null;

if (!$id || !in_array($action, ['approved', 'rejected'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid request.']);
    exit;
}

// Get request info
$stmt = $conn->prepare("SELECT email, property_id FROM property_buy_requests WHERE id = ?");
$stmt->bind_param('i', $id);
$stmt->execute();
$stmt->bind_result($email, $property_id);
$stmt->fetch();
$stmt->close();

if (!$email) {
    echo json_encode(['success' => false, 'message' => 'Request not found.']);
    exit;
}

// Check if there's already an approved request for this property (for approval actions)
if ($action === 'approved') {
    $stmt = $conn->prepare("SELECT COUNT(*) FROM property_buy_requests WHERE property_id = ? AND status = 'approved'");
    $stmt->bind_param('i', $property_id);
    $stmt->execute();
    $stmt->bind_result($approved_count);
    $stmt->fetch();
    $stmt->close();
    
    if ($approved_count > 0) {
        echo json_encode(['success' => false, 'message' => 'This property already has an approved buy request.']);
        exit;
    }
}

// Get property title
$stmt = $conn->prepare("SELECT title FROM properties WHERE id = ?");
$stmt->bind_param('i', $property_id);
$stmt->execute();
$stmt->bind_result($property_title);
$stmt->fetch();
$stmt->close();

// Update status
$stmt = $conn->prepare("UPDATE property_buy_requests SET status = ?, updated_at = NOW() WHERE id = ?");
$stmt->bind_param('si', $action, $id);
if ($stmt->execute()) {
    // If approved, insert transaction
    if ($action === 'approved') {
        // Get buyer_id from buy request
        $stmt2 = $conn->prepare("SELECT user_id FROM property_buy_requests WHERE id = ?");
        $stmt2->bind_param('i', $id);
        $stmt2->execute();
        $stmt2->bind_result($buyer_id);
        $stmt2->fetch();
        $stmt2->close();
        // Get seller_id and price from property
        $stmt3 = $conn->prepare("SELECT user_id, price FROM properties WHERE id = ?");
        $stmt3->bind_param('i', $property_id);
        $stmt3->execute();
        $stmt3->bind_result($seller_id, $amount);
        $stmt3->fetch();
        $stmt3->close();
        // Insert transaction if both buyer and seller exist
        if ($buyer_id && $seller_id && $amount) {
            $stmt4 = $conn->prepare("INSERT INTO transactions (property_id, buyer_id, seller_id, amount) VALUES (?, ?, ?, ?)");
            $stmt4->bind_param('iiid', $property_id, $buyer_id, $seller_id, $amount);
            $stmt4->execute();
            $stmt4->close();
        }
    }
    // Send email
    $subject = "Your Property Purchase Request has been $action";
    $message = "Dear user,\n\nYour request to buy the property '$property_title' has been $action by the admin.\n\nThank you.";
    $headers = "From: property@finder.com";
    @mail($email, $subject, $message, $headers);
    
    // Return success message with auto-rejection info
    if ($action === 'approved' && isset($rejectedCount) && $rejectedCount > 0) {
        echo json_encode(['success' => true, 'message' => "Request approved successfully. $rejectedCount other pending requests were automatically rejected."]);
    } else {
        echo json_encode(['success' => true]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to update status.']);
} 