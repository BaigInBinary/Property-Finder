<?php
session_start();
require_once 'backend/db.php';

echo "<h2>Profile Save Changes Debug</h2>";

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo "❌ User not logged in<br>";
    exit;
}

echo "✅ User logged in: " . $_SESSION['user_id'] . "<br>";

// Check database connection
if (!$conn) {
    echo "❌ Database connection failed<br>";
    exit;
}
echo "✅ Database connected<br>";

// Check if users table exists and has required columns
$result = $conn->query("SHOW TABLES LIKE 'users'");
if ($result->num_rows == 0) {
    echo "❌ Users table doesn't exist<br>";
    exit;
}
echo "✅ Users table exists<br>";

// Check table structure
$result = $conn->query("DESCRIBE users");
echo "<h3>Users Table Structure:</h3>";
echo "<table border='1'>";
echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
while ($row = $result->fetch_assoc()) {
    echo "<tr>";
    echo "<td>" . $row['Field'] . "</td>";
    echo "<td>" . $row['Type'] . "</td>";
    echo "<td>" . $row['Null'] . "</td>";
    echo "<td>" . $row['Key'] . "</td>";
    echo "<td>" . $row['Default'] . "</td>";
    echo "</tr>";
}
echo "</table>";

// Check if CNIC column exists
$result = $conn->query("SHOW COLUMNS FROM users LIKE 'cnic'");
if ($result->num_rows == 0) {
    echo "<br>❌ CNIC column doesn't exist - this is likely the issue!<br>";
    echo "Adding CNIC column...<br>";
    
    $sql = "ALTER TABLE users ADD COLUMN cnic VARCHAR(15) AFTER location";
    if ($conn->query($sql)) {
        echo "✅ CNIC column added successfully<br>";
    } else {
        echo "❌ Failed to add CNIC column: " . $conn->error . "<br>";
    }
} else {
    echo "<br>✅ CNIC column exists<br>";
}

// Test the update-user-details.php file
echo "<h3>Testing Backend File:</h3>";
if (file_exists('backend/update-user-details.php')) {
    echo "✅ Backend file exists<br>";
    
    // Test if file is accessible
    $content = file_get_contents('backend/update-user-details.php');
    if (strlen($content) > 100) {
        echo "✅ Backend file has content<br>";
    } else {
        echo "❌ Backend file seems empty or corrupted<br>";
    }
} else {
    echo "❌ Backend file missing<br>";
}

// Test uploads directory
echo "<h3>Testing Uploads Directory:</h3>";
if (is_dir('uploads/profile-pic')) {
    echo "✅ Uploads directory exists<br>";
    if (is_writable('uploads/profile-pic')) {
        echo "✅ Uploads directory is writable<br>";
    } else {
        echo "❌ Uploads directory is not writable<br>";
    }
} else {
    echo "❌ Uploads directory doesn't exist<br>";
}

echo "<br><strong>Debug completed. Check the results above to identify the issue.</strong>";
?> 