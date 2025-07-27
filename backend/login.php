<?php
session_start();
require_once 'db.php'; // make sure this contains a valid $conn (MySQLi connection)

$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

if (empty($email) || empty($password)) {
    echo json_encode(['status' => 'error', 'message' => 'Email and password are required.']);
    exit;
}

// Prepare and execute
$sql = "SELECT * FROM users WHERE email = ?";
$stmt = mysqli_prepare($conn, $sql);

if (!$stmt) {
    echo json_encode(['status' => 'error', 'message' => 'Database error.']);
    exit;
}

mysqli_stmt_bind_param($stmt, "s", $email);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if ($user = mysqli_fetch_assoc($result)) {
    if (password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];

        echo json_encode(['status' => 'success', 'message' => 'Login successful.']);
        exit;
    }
}

echo json_encode(['status' => 'error', 'message' => 'Invalid email or password.']);
exit;
