<?php
session_start();
require_once '../backend/db.php';

$userId = $_GET['id'] ?? null;

if ($userId) {
    // First, delete all properties of the user
    $stmtProperties = $conn->prepare("DELETE FROM properties WHERE user_id = ?");
    $stmtProperties->bind_param('i', $userId);
    $stmtProperties->execute();
    $stmtProperties->close();

    // Then delete the user
    $stmtUser = $conn->prepare("DELETE FROM users WHERE id = ?");
    $stmtUser->bind_param('i', $userId);

    if ($stmtUser->execute()) {
        $_SESSION['delete_success'] = "User and all their properties deleted successfully.";
    } else {
        $_SESSION['delete_error'] = "Failed to delete user.";
    }
    $stmtUser->close();
}

header("Location: all-users.php");
exit;
?>
