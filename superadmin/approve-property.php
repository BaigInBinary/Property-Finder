<?php
session_start();
require_once '../backend/db.php';

$propertyId = $_GET['id'] ?? null;

if ($propertyId) {
    $stmt = $conn->prepare("UPDATE properties SET listing = 'approved' WHERE id = ?");
    $stmt->bind_param('i', $propertyId);

    if ($stmt->execute()) {
        $_SESSION['success'] = "Property approved successfully.";
    } else {
        $_SESSION['error'] = "Failed to approve property.";
    }
    $stmt->close();
}

header("Location: all-properties.php");
exit;
?>
