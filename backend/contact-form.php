<?php
header('Content-Type: application/json');
require_once 'db.php';

// Get form data
$input = json_decode(file_get_contents('php://input'), true);

$name = trim($input['name'] ?? '');
$email = trim($input['email'] ?? '');
$message = trim($input['message'] ?? '');

// Validation
if (empty($name) || empty($email) || empty($message)) {
    echo json_encode(['status' => 'error', 'message' => 'All fields are required']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid email address']);
    exit;
}

// Sanitize inputs
$name = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
$email = filter_var($email, FILTER_SANITIZE_EMAIL);
$message = htmlspecialchars($message, ENT_QUOTES, 'UTF-8');

// Email configuration
$to = 'propfind3@gmail.com'; // Your email address
$subject = 'New Contact Form Message - PropFind';
$headers = "From: $email\r\n";
$headers .= "Reply-To: $email\r\n";
$headers .= "MIME-Version: 1.0\r\n";
$headers .= "Content-Type: text/html; charset=UTF-8\r\n";

// Email body
$emailBody = "
<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background-color: #007bff; color: white; padding: 20px; text-align: center; }
        .content { padding: 20px; background-color: #f8f9fa; }
        .field { margin-bottom: 15px; }
        .label { font-weight: bold; color: #007bff; }
        .value { margin-top: 5px; }
    </style>
</head>
<body>
    <div class='container'>
        <div class='header'>
            <h2>New Contact Form Message</h2>
            <p>PropFind Real Estate Platform</p>
        </div>
        <div class='content'>
            <div class='field'>
                <div class='label'>Name:</div>
                <div class='value'>$name</div>
            </div>
            <div class='field'>
                <div class='label'>Email:</div>
                <div class='value'>$email</div>
            </div>
            <div class='field'>
                <div class='label'>Message:</div>
                <div class='value'>$message</div>
            </div>
            <div class='field'>
                <div class='label'>Date:</div>
                <div class='value'>" . date('F j, Y \a\t g:i A') . "</div>
            </div>
        </div>
    </div>
</body>
</html>
";

// Send email
$mailSent = mail($to, $subject, $emailBody, $headers);

if ($mailSent) {
    // Try to save to database for record keeping (optional)
    try {
        // Check if connection is still alive
        if ($conn->ping()) {
            $stmt = $conn->prepare("INSERT INTO contact_messages (name, email, message, created_at) VALUES (?, ?, ?, NOW())");
            if ($stmt) {
                $stmt->bind_param('sss', $name, $email, $message);
                $stmt->execute();
                $stmt->close();
            }
        }
    } catch (Exception $e) {
        // Database error - but email was sent, so we still return success
        error_log("Database error in contact form: " . $e->getMessage());
    }
    
    echo json_encode(['status' => 'success', 'message' => 'Thank you! Your message has been sent successfully.']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Sorry, there was an error sending your message. Please try again later.']);
}

$conn->close();
?> 