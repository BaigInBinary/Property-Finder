<?php
require_once 'backend/db.php';
session_start();

echo "<h1>PropFind Feature Test Results</h1>";

// Test 1: Database Connection
echo "<h2>1. Database Connection Test</h2>";
if ($conn) {
    echo "✅ Database connection successful<br>";
} else {
    echo "❌ Database connection failed<br>";
    die();
}

// Test 2: User Authentication
echo "<h2>2. User Authentication Test</h2>";
$test_email = 'user@test.com';
$test_password = 'password123';

$query = "SELECT * FROM users WHERE email = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "s", $test_email);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($result);

if ($user && password_verify($test_password, $user['password'])) {
    echo "✅ User authentication successful<br>";
} else {
    echo "❌ User authentication failed<br>";
}

// Test 3: Property Upload
echo "<h2>3. Property Upload Test</h2>";
$test_property = [
    'title' => 'Test Property',
    'description' => 'This is a test property',
    'price' => 100000,
    'location' => 'Test Location',
    'property_type' => 'house',
    'bedrooms' => 3,
    'bathrooms' => 2,
    'area' => 1500
];

$query = "INSERT INTO properties (user_id, title, description, price, location, property_type, bedrooms, bathrooms, area, status) 
          VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "issdssiid", $user['id'], 
    $test_property['title'], 
    $test_property['description'], 
    $test_property['price'], 
    $test_property['location'], 
    $test_property['property_type'], 
    $test_property['bedrooms'], 
    $test_property['bathrooms'], 
    $test_property['area']
);

if (mysqli_stmt_execute($stmt)) {
    echo "✅ Property upload successful<br>";
    $property_id = mysqli_insert_id($conn);
} else {
    echo "❌ Property upload failed<br>";
}

// Test 4: Property Approval (Admin)
echo "<h2>4. Property Approval Test</h2>";
if ($property_id) {
    $query = "UPDATE properties SET status = 'available' WHERE id = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $property_id);
    
    if (mysqli_stmt_execute($stmt)) {
        echo "✅ Property approval successful<br>";
    } else {
        echo "❌ Property approval failed<br>";
    }
}

// Test 5: Property Search
echo "<h2>5. Property Search Test</h2>";
$query = "SELECT * FROM properties WHERE status = 'available'";
$result = mysqli_query($conn, $query);

if ($result && mysqli_num_rows($result) > 0) {
    echo "✅ Property search successful<br>";
} else {
    echo "❌ Property search failed<br>";
}

// Test 6: Save Property
echo "<h2>6. Save Property Test</h2>";
if ($property_id) {
    $query = "INSERT INTO saved_properties (user_id, property_id) VALUES (?, ?)";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "ii", $user['id'], $property_id);
    
    if (mysqli_stmt_execute($stmt)) {
        echo "✅ Save property successful<br>";
    } else {
        echo "❌ Save property failed<br>";
    }
}

// Test 7: Reviews
echo "<h2>7. Review System Test</h2>";
if ($property_id) {
    $query = "INSERT INTO reviews (property_id, user_id, rating, comment) VALUES (?, ?, 5, 'Great property!')";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "ii", $property_id, $user['id']);
    
    if (mysqli_stmt_execute($stmt)) {
        echo "✅ Review submission successful<br>";
    } else {
        echo "❌ Review submission failed<br>";
    }
}

// Test 8: Notifications
echo "<h2>8. Notification System Test</h2>";
$query = "INSERT INTO notifications (user_id, message) VALUES (?, 'Test notification')";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $user['id']);

if (mysqli_stmt_execute($stmt)) {
    echo "✅ Notification creation successful<br>";
} else {
    echo "❌ Notification creation failed<br>";
}

// Test 9: Messages
echo "<h2>9. Messaging System Test</h2>";
$query = "INSERT INTO messages (sender_id, receiver_id, property_id, message) VALUES (?, ?, ?, 'Test message')";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "iii", $user['id'], $user['id'], $property_id);

if (mysqli_stmt_execute($stmt)) {
    echo "✅ Message creation successful<br>";
} else {
    echo "❌ Message creation failed<br>";
}

echo "<br><h3>Test Summary:</h3>";
echo "All core features have been tested. Please check the results above for any issues.<br>";
echo "To test the frontend features, please use the following credentials:<br>";
echo "Regular User: user@test.com / password123<br>";
echo "Admin User: admin@test.com / admin123<br>";
?> 