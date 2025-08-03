<?php
session_start();
require_once 'db.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Login required']);
    exit;
}

$userId = $_SESSION['user_id'];

// Fetch user details
$sql = "SELECT * FROM users WHERE id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $userId);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($result);

// Check if user is admin
if (!$user || strtolower($user['role']) !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit;
}

// Check if property ID is provided
if (!isset($_POST['id']) || !is_numeric($_POST['id'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid property ID']);
    exit;
}

$propertyId = (int)$_POST['id'];

// Check if property exists
$checkQuery = "SELECT id FROM properties WHERE id = ?";
$stmt = mysqli_prepare($conn, $checkQuery);
mysqli_stmt_bind_param($stmt, "i", $propertyId);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) === 0) {
    echo json_encode(['success' => false, 'message' => 'Property not found']);
    exit;
}

// Get property details first (for file deletion)
$propertyQuery = "SELECT image, images_json, cnic_image, ownership_docs FROM properties WHERE id = ?";
$stmt = mysqli_prepare($conn, $propertyQuery);
mysqli_stmt_bind_param($stmt, "i", $propertyId);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$property = mysqli_fetch_assoc($result);

// Start transaction
$conn->begin_transaction();

try {
    // Delete related records first (in order of dependencies)
    
    // 1. Delete property reviews
    $deleteReviewsStmt = $conn->prepare("DELETE FROM property_reviews WHERE property_id = ?");
    $deleteReviewsStmt->bind_param("i", $propertyId);
    $deleteReviewsStmt->execute();
    $deleteReviewsStmt->close();
    
    // 2. Delete saved properties references
    $deleteSavedStmt = $conn->prepare("DELETE FROM saved_properties WHERE property_id = ?");
    $deleteSavedStmt->bind_param("i", $propertyId);
    $deleteSavedStmt->execute();
    $deleteSavedStmt->close();
    
    // 3. Delete property buy requests
    $deleteRequestsStmt = $conn->prepare("DELETE FROM property_buy_requests WHERE property_id = ?");
    $deleteRequestsStmt->bind_param("i", $propertyId);
    $deleteRequestsStmt->execute();
    $deleteRequestsStmt->close();
    
    // 4. Delete transactions
    $deleteTransactionsStmt = $conn->prepare("DELETE FROM transactions WHERE property_id = ?");
    $deleteTransactionsStmt->bind_param("i", $propertyId);
    $deleteTransactionsStmt->execute();
    $deleteTransactionsStmt->close();
    
    // 5. Finally delete the property
    $deletePropertyStmt = $conn->prepare("DELETE FROM properties WHERE id = ?");
    $deletePropertyStmt->bind_param("i", $propertyId);
    
    if ($deletePropertyStmt->execute()) {
        // Delete associated files
        if ($property['image'] && file_exists($property['image'])) {
            unlink($property['image']);
        }
        
        if ($property['images_json']) {
            $images = json_decode($property['images_json'], true);
            if (is_array($images)) {
                foreach ($images as $image) {
                    if (file_exists($image)) {
                        unlink($image);
                    }
                }
            }
        }
        
        // Delete CNIC image
        if ($property['cnic_image'] && file_exists($property['cnic_image'])) {
            unlink($property['cnic_image']);
        }
        
        // Delete ownership documents
        if ($property['ownership_docs'] && file_exists($property['ownership_docs'])) {
            unlink($property['ownership_docs']);
        }
        
        // Commit the transaction
        $conn->commit();
        echo json_encode(['success' => true, 'message' => 'Property deleted successfully']);
    } else {
        // Rollback on error
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => 'Error deleting property']);
    }
    
    $deletePropertyStmt->close();
    
} catch (Exception $e) {
    // Rollback on any exception
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => 'Error deleting property: ' . $e->getMessage()]);
}

mysqli_close($conn);
?>
