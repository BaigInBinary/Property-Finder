<?php
session_start();
require_once 'backend/db.php';

echo "<h2>Fixing CNIC Column Issue</h2>";

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo "❌ User not logged in. Please log in first.<br>";
    exit;
}

echo "✅ User logged in: " . $_SESSION['user_id'] . "<br>";

// Check database connection
if (!$conn) {
    echo "❌ Database connection failed<br>";
    exit;
}
echo "✅ Database connected<br>";

// Check if CNIC column exists
$result = $conn->query("SHOW COLUMNS FROM users LIKE 'cnic'");
if ($result->num_rows == 0) {
    echo "❌ CNIC column doesn't exist - adding it now...<br>";
    
    $sql = "ALTER TABLE users ADD COLUMN cnic VARCHAR(15) AFTER location";
    if ($conn->query($sql)) {
        echo "✅ CNIC column added successfully<br>";
    } else {
        echo "❌ Failed to add CNIC column: " . $conn->error . "<br>";
        exit;
    }
} else {
    echo "✅ CNIC column already exists<br>";
}

// Test the profile update functionality
echo "<h3>Testing Profile Update:</h3>";

// Simulate a profile update request
$_POST['full_name'] = 'Test User';
$_POST['email'] = 'test@example.com';
$_POST['phone'] = '0300-1234567';
$_POST['location'] = 'Lahore, Pakistan';
$_POST['cnic'] = '12345-1234567-1';
$_POST['bio'] = 'Test bio';

// Include the update-user-details.php file
ob_start();
include 'backend/update-user-details.php';
$response = ob_get_clean();

echo "Backend response: " . $response . "<br>";

// Check if the response is valid JSON
$decoded = json_decode($response, true);
if ($decoded) {
    if ($decoded['success']) {
        echo "✅ Profile update test successful<br>";
    } else {
        echo "❌ Profile update test failed: " . $decoded['message'] . "<br>";
    }
} else {
    echo "❌ Invalid response from backend<br>";
}

echo "<br><strong>CNIC column fix completed. Try the profile update again.</strong>";
?> 