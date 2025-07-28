<?php
require_once 'backend/db.php';

// SQL to create messages table
$sql = "
CREATE TABLE IF NOT EXISTS `messages` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `sender_id` int(11) NOT NULL,
    `receiver_id` int(11) NOT NULL,
    `message` text NOT NULL,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `sender_id` (`sender_id`),
    KEY `receiver_id` (`receiver_id`),
    KEY `created_at` (`created_at`),
    CONSTRAINT `messages_ibfk_1` FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
    CONSTRAINT `messages_ibfk_2` FOREIGN KEY (`receiver_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
";

if ($conn->query($sql) === TRUE) {
    echo "Messages table created successfully!<br>";
} else {
    echo "Error creating messages table: " . $conn->error . "<br>";
}

// Add last_active column to users table if it doesn't exist
$checkColumn = $conn->query("SHOW COLUMNS FROM users LIKE 'last_active'");
if ($checkColumn->num_rows == 0) {
    $addColumn = "ALTER TABLE users ADD COLUMN last_active TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP";
    if ($conn->query($addColumn) === TRUE) {
        echo "last_active column added to users table successfully!<br>";
    } else {
        echo "Error adding last_active column: " . $conn->error . "<br>";
    }
} else {
    echo "last_active column already exists in users table.<br>";
}

// Check if messages table exists
$result = $conn->query("SHOW TABLES LIKE 'messages'");
if ($result->num_rows > 0) {
    echo "Messages table exists and is ready for chat functionality!<br>";
} else {
    echo "Messages table does not exist. Please check the database connection.<br>";
}

// Check if last_active column exists
$checkColumn = $conn->query("SHOW COLUMNS FROM users LIKE 'last_active'");
if ($checkColumn->num_rows > 0) {
    echo "last_active column exists in users table!<br>";
} else {
    echo "last_active column does not exist in users table.<br>";
}

$conn->close();
?> 