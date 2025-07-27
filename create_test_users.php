<?php
require_once 'backend/db.php';

// Function to create a test user
function createTestUser($email, $password, $name, $role = 'user') {
    global $conn;
    
    // Hash the password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    
    // Check if user already exists
    $check_query = "SELECT id FROM users WHERE email = ?";
    $stmt = mysqli_prepare($conn, $check_query);
    mysqli_stmt_bind_param($stmt, "s", $email);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if (mysqli_num_rows($result) > 0) {
        echo "User $email already exists.<br>";
        return;
    }
    
    // Create new user
    $insert_query = "INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)";
    $stmt = mysqli_prepare($conn, $insert_query);
    mysqli_stmt_bind_param($stmt, "ssss", $name, $email, $hashed_password, $role);
    
    if (mysqli_stmt_execute($stmt)) {
        echo "✅ Created $role user: $email<br>";
    } else {
        echo "❌ Failed to create user: $email<br>";
    }
}

// Create test users
createTestUser('user@test.com', 'password123', 'Test User', 'user');
createTestUser('admin@test.com', 'admin123', 'Admin User', 'admin');

echo "<br>Test users created successfully!<br>";
echo "Regular user: user@test.com / password123<br>";
echo "Admin user: admin@test.com / admin123<br>";
?> 