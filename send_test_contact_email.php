<?php
// Test script to send a contact email
echo "Sending test contact email...\n";

// Test data
$testData = [
    'name' => 'Test User',
    'email' => 'test@example.com',
    'message' => 'This is a test message from the PropFind contact form system. Testing email functionality!'
];

// Convert to JSON
$jsonData = json_encode($testData);

// Set up cURL request
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'http://localhost/Ammar-fyp/backend/contact-form.php');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Content-Length: ' . strlen($jsonData)
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

// Execute the request
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Response Code: $httpCode\n";
echo "Response: $response\n";

// Parse JSON response
$result = json_decode($response, true);

if ($result) {
    if ($result['status'] === 'success') {
        echo "✅ Test email sent successfully!\n";
        echo "Message: " . $result['message'] . "\n";
    } else {
        echo "❌ Failed to send email: " . $result['message'] . "\n";
    }
} else {
    echo "❌ Invalid response format\n";
}

echo "\nCheck your email at: propfind3@gmail.com\n";
?> 