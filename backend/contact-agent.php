<?php
session_start();
header('Content-Type: application/json');
require_once 'db.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if request is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
    exit;
}

// Get form data
$data = json_decode(file_get_contents('php://input'), true);

// Validate required fields
$required_fields = ['name', 'email', 'phone', 'message', 'property_id'];
foreach ($required_fields as $field) {
    if (!isset($data[$field]) || empty($data[$field])) {
        echo json_encode(['status' => 'error', 'message' => "Missing required field: $field"]);
        exit;
    }
}

$name = trim($data['name']);
$email = trim($data['email']);
$phone = trim($data['phone']);
$message = trim($data['message']);
$propertyId = intval($data['property_id']);

// Validate email
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid email address']);
    exit;
}

// Get property and creator information
$stmt = $conn->prepare("SELECT p.title, p.price, p.location, u.email as creator_email, u.name as creator_name 
                        FROM properties p 
                        JOIN users u ON p.user_id = u.id 
                        WHERE p.id = ?");
$stmt->bind_param('i', $propertyId);
$stmt->execute();
$result = $stmt->get_result();
$property = $result->fetch_assoc();
$stmt->close();

if (!$property) {
    echo json_encode(['status' => 'error', 'message' => 'Property not found']);
    exit;
}

// Store the contact message in database
$stmt = $conn->prepare("INSERT INTO contact_messages (property_id, sender_name, sender_email, sender_phone, message, created_at) 
                        VALUES (?, ?, ?, ?, ?, NOW())");
$stmt->bind_param('issss', $propertyId, $name, $email, $phone, $message);

if (!$stmt->execute()) {
    echo json_encode(['status' => 'error', 'message' => 'Failed to save message']);
    $stmt->close();
    exit;
}

$messageId = $stmt->insert_id;
$stmt->close();

// Send email to property creator
$to = $property['creator_email'];
$subject = "New Property Inquiry - " . $property['title'];

// Create HTML email
$htmlMessage = "
<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #007bff; color: white; padding: 20px; text-align: center; }
        .content { padding: 20px; background: #f8f9fa; }
        .property-info { background: white; padding: 15px; margin: 15px 0; border-left: 4px solid #007bff; }
        .sender-info { background: white; padding: 15px; margin: 15px 0; border-left: 4px solid #28a745; }
        .message-box { background: white; padding: 15px; margin: 15px 0; border: 1px solid #dee2e6; }
        .footer { text-align: center; padding: 20px; color: #6c757d; font-size: 12px; }
    </style>
</head>
<body>
    <div class='container'>
        <div class='header'>
            <h2>New Property Inquiry</h2>
            <p>Someone is interested in your property!</p>
        </div>
        
        <div class='content'>
            <div class='property-info'>
                <h3>Property Details</h3>
                <p><strong>Title:</strong> {$property['title']}</p>
                <p><strong>Price:</strong> PKR " . number_format($property['price']) . "</p>
                <p><strong>Location:</strong> {$property['location']}</p>
            </div>
            
            <div class='sender-info'>
                <h3>Inquirer Details</h3>
                <p><strong>Name:</strong> $name</p>
                <p><strong>Email:</strong> $email</p>
                <p><strong>Phone:</strong> $phone</p>
            </div>
            
            <div class='message-box'>
                <h3>Message</h3>
                <p>" . nl2br(htmlspecialchars($message)) . "</p>
            </div>
            
            <p style='margin-top: 20px;'>
                <strong>Reply to this email to contact the inquirer directly.</strong>
            </p>
        </div>
        
        <div class='footer'>
            <p>This message was sent from PropFind - Your trusted property platform</p>
            <p>Message ID: $messageId</p>
        </div>
    </div>
</body>
</html>
";

// Email headers
$headers = array(
    'MIME-Version: 1.0',
    'Content-type: text/html; charset=UTF-8',
    'From: PropFind <noreply@propfind.com>',
    'Reply-To: ' . $email,
    'X-Mailer: PHP/' . phpversion()
);

// Send email
$mailSent = mail($to, $subject, $htmlMessage, implode("\r\n", $headers));

if ($mailSent) {
    echo json_encode([
        'status' => 'success', 
        'message' => 'Your message has been sent successfully! The property owner will contact you soon.'
    ]);
} else {
    // Email failed but message was saved
    echo json_encode([
        'status' => 'success', 
        'message' => 'Your message has been saved. The property owner will be notified.'
    ]);
}
?> 