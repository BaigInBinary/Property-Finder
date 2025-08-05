<?php
// Simple test to verify profile functionality
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo "User not logged in. Please log in first.";
    exit;
}

echo "<h2>Profile Functionality Test</h2>";

// Test 1: Check if backend file exists
if (file_exists('backend/update-user-details.php')) {
    echo "✅ Backend file exists<br>";
} else {
    echo "❌ Backend file missing<br>";
}

// Test 2: Check if uploads directory exists and is writable
if (is_dir('uploads/profile-pic') && is_writable('uploads/profile-pic')) {
    echo "✅ Uploads directory exists and is writable<br>";
} else {
    echo "❌ Uploads directory issue<br>";
}

// Test 3: Check if dashboard file exists
if (file_exists('dashboard/dashboard.php')) {
    echo "✅ Dashboard file exists<br>";
} else {
    echo "❌ Dashboard file missing<br>";
}

// Test 4: Check database connection
require_once 'backend/db.php';
if ($conn) {
    echo "✅ Database connection successful<br>";
} else {
    echo "❌ Database connection failed<br>";
}

// Test 5: Check if user exists in database
$userId = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT id, name, email FROM users WHERE id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();
    echo "✅ User found in database: " . htmlspecialchars($user['name']) . "<br>";
} else {
    echo "❌ User not found in database<br>";
}

echo "<br><strong>Test completed. If all checks show ✅, the profile functionality should work correctly.</strong>";
?> 