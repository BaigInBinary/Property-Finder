<?php
session_start();
require_once 'db.php';

// Test the delete property functionality
echo "<h2>Testing Property Deletion</h2>";

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo "<p style='color: red;'>User not logged in</p>";
    exit;
}

$userId = $_SESSION['user_id'];
echo "<p>User ID: $userId</p>";

// Get user's properties
$stmt = $conn->prepare("SELECT id, title FROM properties WHERE user_id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();

echo "<h3>User's Properties:</h3>";
if ($result->num_rows > 0) {
    echo "<ul>";
    while ($row = $result->fetch_assoc()) {
        echo "<li>ID: {$row['id']} - {$row['title']}</li>";
    }
    echo "</ul>";
} else {
    echo "<p>No properties found for this user.</p>";
}

$stmt->close();

// Test database connection
echo "<h3>Database Connection Test:</h3>";
if ($conn->ping()) {
    echo "<p style='color: green;'>Database connection successful</p>";
} else {
    echo "<p style='color: red;'>Database connection failed</p>";
}

// Check properties table structure
echo "<h3>Properties Table Structure:</h3>";
$result = $conn->query("DESCRIBE properties");
if ($result) {
    echo "<table border='1'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>{$row['Field']}</td>";
        echo "<td>{$row['Type']}</td>";
        echo "<td>{$row['Null']}</td>";
        echo "<td>{$row['Key']}</td>";
        echo "<td>{$row['Default']}</td>";
        echo "<td>{$row['Extra']}</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color: red;'>Failed to get table structure</p>";
}

// Test a simple delete operation
if (isset($_GET['test_delete']) && isset($_GET['property_id'])) {
    $propertyId = $_GET['property_id'];
    echo "<h3>Testing Delete Operation:</h3>";
    echo "<p>Attempting to delete property ID: $propertyId</p>";
    
    // Check if property exists
    $checkStmt = $conn->prepare("SELECT id, title FROM properties WHERE id = ? AND user_id = ?");
    $checkStmt->bind_param("ii", $propertyId, $userId);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();
    
    if ($checkResult->num_rows > 0) {
        $property = $checkResult->fetch_assoc();
        echo "<p>Property found: {$property['title']}</p>";
        
        // Attempt to delete
        $deleteStmt = $conn->prepare("DELETE FROM properties WHERE id = ? AND user_id = ?");
        $deleteStmt->bind_param("ii", $propertyId, $userId);
        
        if ($deleteStmt->execute()) {
            $affectedRows = $deleteStmt->affected_rows;
            echo "<p style='color: green;'>Delete successful! Affected rows: $affectedRows</p>";
        } else {
            echo "<p style='color: red;'>Delete failed: " . $deleteStmt->error . "</p>";
        }
        
        $deleteStmt->close();
    } else {
        echo "<p style='color: red;'>Property not found or doesn't belong to user</p>";
    }
    
    $checkStmt->close();
}

echo "<hr>";
echo "<p><a href='?test_delete=1&property_id=1'>Test Delete Property ID 1</a></p>";
echo "<p><a href='?test_delete=1&property_id=2'>Test Delete Property ID 2</a></p>";
?> 