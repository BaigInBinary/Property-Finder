<?php
// Test script for contact email functionality
echo "<h2>Contact Email Functionality Test</h2>";

// Test 1: Check if mail function is available
echo "<h3>Test 1: Mail Function Availability</h3>";
if (function_exists('mail')) {
    echo "✅ PHP mail() function is available<br>";
} else {
    echo "❌ PHP mail() function is not available<br>";
}

// Test 2: Check if we can connect to database
echo "<h3>Test 2: Database Connection</h3>";
try {
    include 'backend/db.php';
    if ($conn->ping()) {
        echo "✅ Database connection successful<br>";
    } else {
        echo "❌ Database connection failed<br>";
    }
} catch (Exception $e) {
    echo "❌ Database error: " . $e->getMessage() . "<br>";
}

// Test 3: Check if contact_messages table exists
echo "<h3>Test 3: Contact Messages Table</h3>";
try {
    $result = $conn->query("SHOW TABLES LIKE 'contact_messages'");
    if ($result->num_rows > 0) {
        echo "✅ contact_messages table exists<br>";
    } else {
        echo "⚠️ contact_messages table does not exist. You may need to run the SQL script.<br>";
        echo "SQL to create table:<br>";
        echo "<pre>";
        echo "CREATE TABLE IF NOT EXISTS `contact_messages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
        echo "</pre>";
    }
} catch (Exception $e) {
    echo "❌ Error checking table: " . $e->getMessage() . "<br>";
}

// Test 4: Test email sending (optional - uncomment to test)
echo "<h3>Test 4: Email Sending Test</h3>";
echo "⚠️ Uncomment the code below to test actual email sending<br>";
echo "Note: This will send a test email to propfind3@gmail.com<br>";

/*
$to = 'propfind3@gmail.com';
$subject = 'Test Email - PropFind Contact Form';
$message = 'This is a test email from the PropFind contact form system.';
$headers = 'From: test@propfind.com' . "\r\n";

if (mail($to, $subject, $message, $headers)) {
    echo "✅ Test email sent successfully<br>";
} else {
    echo "❌ Failed to send test email<br>";
}
*/

echo "<h3>Setup Instructions:</h3>";
echo "<ol>";
echo "<li>Make sure your server has email functionality configured</li>";
echo "<li>Run the SQL script to create the contact_messages table</li>";
echo "<li>Test the contact form on the contact.php page</li>";
echo "<li>Check your email (propfind3@gmail.com) for incoming messages</li>";
echo "</ol>";

echo "<h3>Files Created/Modified:</h3>";
echo "<ul>";
echo "<li>✅ backend/contact-form.php - Backend handler for contact form</li>";
echo "<li>✅ js/contact.js - Updated JavaScript for form handling</li>";
echo "<li>✅ contact.php - Added contact.js script inclusion</li>";
echo "<li>✅ css/contact.css - Added validation and alert styles</li>";
echo "<li>✅ backend/create_contact_table.sql - SQL script for database table</li>";
echo "</ul>";
?> 