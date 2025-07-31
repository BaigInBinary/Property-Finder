<?php
session_start();
require_once 'db.php'; // $conn (mysqli)

header('Content-Type: application/json');

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

$userId = $_SESSION['user_id'] ?? null;
$propertyId = $_POST['property_id'] ?? null;

// Log the request
error_log("Delete property request - User ID: $userId, Property ID: $propertyId");

if (!$userId || !$propertyId) {
    error_log("Invalid request - User ID: $userId, Property ID: $propertyId");
    echo json_encode(['success' => false, 'message' => 'Invalid request.']);
    exit;
}

// First check if the property exists and belongs to the user
$checkStmt = $conn->prepare("SELECT id FROM properties WHERE id = ? AND user_id = ?");
$checkStmt->bind_param("ii", $propertyId, $userId);
$checkStmt->execute();
$result = $checkStmt->get_result();

if ($result->num_rows === 0) {
    error_log("Property not found or doesn't belong to user - Property ID: $propertyId, User ID: $userId");
    echo json_encode(['success' => false, 'message' => 'Property not found or you do not have permission to delete it.']);
    $checkStmt->close();
    exit;
}

$checkStmt->close();

// Start transaction to handle foreign key constraints
$conn->begin_transaction();

try {
    // Delete related records first (in order of dependencies)
    
    // 1. Delete property reviews
    $deleteReviewsStmt = $conn->prepare("DELETE FROM property_reviews WHERE property_id = ?");
    $deleteReviewsStmt->bind_param("i", $propertyId);
    $deleteReviewsStmt->execute();
    $reviewsDeleted = $deleteReviewsStmt->affected_rows;
    error_log("Deleted $reviewsDeleted reviews for property ID: $propertyId");
    $deleteReviewsStmt->close();
    
    // 2. Delete saved properties references
    $deleteSavedStmt = $conn->prepare("DELETE FROM saved_properties WHERE property_id = ?");
    $deleteSavedStmt->bind_param("i", $propertyId);
    $deleteSavedStmt->execute();
    $savedDeleted = $deleteSavedStmt->affected_rows;
    error_log("Deleted $savedDeleted saved property references for property ID: $propertyId");
    $deleteSavedStmt->close();
    
    // 3. Delete property buy requests
    $deleteRequestsStmt = $conn->prepare("DELETE FROM property_buy_requests WHERE property_id = ?");
    $deleteRequestsStmt->bind_param("i", $propertyId);
    $deleteRequestsStmt->execute();
    $requestsDeleted = $deleteRequestsStmt->affected_rows;
    error_log("Deleted $requestsDeleted buy requests for property ID: $propertyId");
    $deleteRequestsStmt->close();
    
    // 4. Delete transactions (if any)
    $deleteTransactionsStmt = $conn->prepare("DELETE FROM transactions WHERE property_id = ?");
    $deleteTransactionsStmt->bind_param("i", $propertyId);
    $deleteTransactionsStmt->execute();
    $transactionsDeleted = $deleteTransactionsStmt->affected_rows;
    error_log("Deleted $transactionsDeleted transactions for property ID: $propertyId");
    $deleteTransactionsStmt->close();
    
    // 5. Finally delete the property itself
    $deletePropertyStmt = $conn->prepare("DELETE FROM properties WHERE id = ? AND user_id = ?");
    $deletePropertyStmt->bind_param("ii", $propertyId, $userId);
    
    if ($deletePropertyStmt->execute()) {
        $affectedRows = $deletePropertyStmt->affected_rows;
        if ($affectedRows > 0) {
            // Commit the transaction
            $conn->commit();
            error_log("Property deleted successfully - Property ID: $propertyId, Affected rows: $affectedRows");
            echo json_encode(['success' => true, 'message' => 'Property deleted successfully.']);
        } else {
            // Rollback if no property was deleted
            $conn->rollback();
            error_log("No property was deleted - Property ID: $propertyId");
            echo json_encode(['success' => false, 'message' => 'Property not found or you do not have permission to delete it.']);
        }
    } else {
        // Rollback on error
        $conn->rollback();
        error_log("Failed to delete property - Property ID: $propertyId, Error: " . $deletePropertyStmt->error);
        echo json_encode(['success' => false, 'message' => 'Failed to delete property: ' . $deletePropertyStmt->error]);
    }
    
    $deletePropertyStmt->close();
    
} catch (Exception $e) {
    // Rollback on any exception
    $conn->rollback();
    error_log("Exception during property deletion - Property ID: $propertyId, Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Failed to delete property: ' . $e->getMessage()]);
}
?>
