<?php
session_start();
require_once '../backend/db.php';

$propertyId = $_GET['id'] ?? null;

if ($propertyId) {
    $stmt = $conn->prepare("UPDATE properties SET listing = 'rejected' WHERE id = ?");
    $stmt->bind_param('i', $propertyId);

    if ($stmt->execute()) {
        $_SESSION['success'] = "Property rejected successfully.";
    } else {
        $_SESSION['error'] = "Failed to reject property.";
    }
    $stmt->close();
}

header("Location: all-properties.php");
exit;
?>
