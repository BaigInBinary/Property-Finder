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

// Check if user ID is provided
if (!isset($_POST['id']) || !is_numeric($_POST['id'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid user ID']);
    exit;
}

$targetUserId = (int)$_POST['id'];

// Prevent admin from deleting themselves
if ($targetUserId === $userId) {
    echo json_encode(['success' => false, 'message' => 'You cannot delete your own account']);
    exit;
}

// Check if target user exists
$checkQuery = "SELECT id, role FROM users WHERE id = ?";
$stmt = mysqli_prepare($conn, $checkQuery);
mysqli_stmt_bind_param($stmt, "i", $targetUserId);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) === 0) {
    echo json_encode(['success' => false, 'message' => 'User not found']);
    exit;
}

$targetUser = mysqli_fetch_assoc($result);

// Prevent deleting other admins
if ($targetUser['role'] === 'admin') {
    echo json_encode(['success' => false, 'message' => 'Cannot delete admin users']);
    exit;
}

// Start transaction
$conn->begin_transaction();

try {
    // Get user's properties for file deletion
    $propertiesQuery = "SELECT images_json, cnic_image, ownership_docs FROM properties WHERE user_id = ?";
    $stmt = mysqli_prepare($conn, $propertiesQuery);
    mysqli_stmt_bind_param($stmt, "i", $targetUserId);
    mysqli_stmt_execute($stmt);
    $propertiesResult = mysqli_stmt_get_result($stmt);
    
    // Delete property files
    while ($property = mysqli_fetch_assoc($propertiesResult)) {
        // Delete property images
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
    }
    
    // Delete related records first (in order of dependencies)
    
    // 1. Delete property reviews (both as property owner and as reviewer)
    $deleteReviewsStmt = $conn->prepare("DELETE FROM property_reviews WHERE property_id IN (SELECT id FROM properties WHERE user_id = ?) OR user_id = ?");
    $deleteReviewsStmt->bind_param("ii", $targetUserId, $targetUserId);
    $deleteReviewsStmt->execute();
    $deleteReviewsStmt->close();
    
    // 2. Delete saved properties references
    $deleteSavedStmt = $conn->prepare("DELETE FROM saved_properties WHERE property_id IN (SELECT id FROM properties WHERE user_id = ?)");
    $deleteSavedStmt->bind_param("i", $targetUserId);
    $deleteSavedStmt->execute();
    $deleteSavedStmt->close();
    
    // 3. Delete property buy requests (both property-based and user-based)
    $deleteRequestsStmt = $conn->prepare("DELETE FROM property_buy_requests WHERE property_id IN (SELECT id FROM properties WHERE user_id = ?) OR user_id = ?");
    $deleteRequestsStmt->bind_param("ii", $targetUserId, $targetUserId);
    $deleteRequestsStmt->execute();
    $deleteRequestsStmt->close();
    
    // 4. Delete transactions (both property-based and buyer-based)
    $deleteTransactionsStmt = $conn->prepare("DELETE FROM transactions WHERE property_id IN (SELECT id FROM properties WHERE user_id = ?) OR buyer_id = ?");
    $deleteTransactionsStmt->bind_param("ii", $targetUserId, $targetUserId);
    $deleteTransactionsStmt->execute();
    $deleteTransactionsStmt->close();
    
    // 5. Delete properties
    $deletePropertiesStmt = $conn->prepare("DELETE FROM properties WHERE user_id = ?");
    $deletePropertiesStmt->bind_param("i", $targetUserId);
    $deletePropertiesStmt->execute();
    $deletePropertiesStmt->close();
    
    // 6. Finally delete the user
    $deleteUserStmt = $conn->prepare("DELETE FROM users WHERE id = ?");
    $deleteUserStmt->bind_param("i", $targetUserId);
    
    if ($deleteUserStmt->execute()) {
        // Commit the transaction
        $conn->commit();
        echo json_encode(['success' => true, 'message' => 'User deleted successfully']);
    } else {
        // Rollback on error
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => 'Error deleting user']);
    }
    
    $deleteUserStmt->close();
    
} catch (Exception $e) {
    // Rollback on any exception
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => 'Error deleting user: ' . $e->getMessage()]);
}

mysqli_close($conn);
?> 