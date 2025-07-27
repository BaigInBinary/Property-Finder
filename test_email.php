<?php
// Test email functionality
echo "<h2>PHP Email Configuration Test</h2>";

// Test basic mail function
$to = "test@example.com";
$subject = "Test Email from PropFind";
$message = "This is a test email to verify PHP mail functionality.\n\n";
$message .= "Time: " . date('Y-m-d H:i:s') . "\n";
$message .= "Server: " . $_SERVER['SERVER_NAME'] . "\n";
$message .= "PHP Version: " . phpversion() . "\n";

$headers = "From: property@finder.com\r\n";
$headers .= "Reply-To: property@finder.com\r\n";
$headers .= "X-Mailer: PHP/" . phpversion();

echo "<h3>Attempting to send test email...</h3>";

$result = mail($to, $subject, $message, $headers);

if ($result) {
    echo "<p style='color: green;'>✅ Basic mail function is working</p>";
} else {
    echo "<p style='color: red;'>❌ Basic mail function failed</p>";
}

// Display PHP mail configuration
echo "<h3>PHP Mail Configuration:</h3>";
echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
echo "<tr><th>Setting</th><th>Value</th></tr>";

$mail_settings = [
    'SMTP' => ini_get('SMTP'),
    'smtp_port' => ini_get('smtp_port'),
    'sendmail_path' => ini_get('sendmail_path'),
    'sendmail_from' => ini_get('sendmail_from'),
    'mail.add_x_header' => ini_get('mail.add_x_header'),
    'mail.log' => ini_get('mail.log')
];

foreach ($mail_settings as $setting => $value) {
    echo "<tr><td>$setting</td><td>" . ($value ?: 'Not set') . "</td></tr>";
}
echo "</table>";

// Check if error log exists and show recent mail-related errors
echo "<h3>Recent Mail Errors (if any):</h3>";
$error_log_path = "C:\\xampp\\php\\logs\\php_error_log";
if (file_exists($error_log_path)) {
    $log_content = file_get_contents($error_log_path);
    $lines = explode("\n", $log_content);
    $mail_errors = [];
    
    // Get last 50 lines and check for mail-related errors
    $recent_lines = array_slice($lines, -50);
    foreach ($recent_lines as $line) {
        if (stripos($line, 'mail') !== false || stripos($line, 'smtp') !== false || stripos($line, 'email') !== false) {
            $mail_errors[] = $line;
        }
    }
    
    if (!empty($mail_errors)) {
        echo "<div style='background: #f8f8f8; padding: 10px; border-radius: 5px;'>";
        echo "<h4>Recent mail-related errors:</h4>";
        foreach (array_slice($mail_errors, -10) as $error) {
            echo "<p style='color: red; font-family: monospace; font-size: 12px;'>" . htmlspecialchars($error) . "</p>";
        }
        echo "</div>";
    } else {
        echo "<p style='color: green;'>✅ No recent mail-related errors found</p>";
    }
} else {
    echo "<p style='color: orange;'>⚠️ Error log file not found at: $error_log_path</p>";
}

// Test with actual email (if you want to test with a real email)
echo "<h3>Test with Real Email:</h3>";
echo "<p>To test with a real email address, uncomment the code below and add your email:</p>";
echo "<pre style='background: #f8f8f8; padding: 10px; border-radius: 5px;'>";
echo "// Uncomment and modify these lines to test with real email:\n";
echo "// \$real_to = 'your-email@gmail.com';\n";
echo "// \$real_result = mail(\$real_to, \$subject, \$message, \$headers);\n";
echo "// echo \$real_result ? 'Real email sent successfully' : 'Real email failed';";
echo "</pre>";

// Recommendations
echo "<h3>Recommendations:</h3>";
echo "<ul>";
echo "<li>If mail() function is not working, consider using PHPMailer or SwiftMailer</li>";
echo "<li>For local development, you can use tools like Mailtrap or configure a local SMTP server</li>";
echo "<li>Check XAMPP's mail configuration in php.ini</li>";
echo "<li>Ensure your hosting provider allows mail() function</li>";
echo "</ul>";

echo "<h3>Alternative Email Libraries:</h3>";
echo "<ul>";
echo "<li><strong>PHPMailer:</strong> Most popular and feature-rich</li>";
echo "<li><strong>SwiftMailer:</strong> Modern and well-maintained</li>";
echo "<li><strong>SendGrid:</strong> Cloud-based email service</li>";
echo "<li><strong>Mailgun:</strong> Another popular email service</li>";
echo "</ul>";

echo "<p><strong>Note:</strong> This test script will be deleted after use for security reasons.</p>";
?> 