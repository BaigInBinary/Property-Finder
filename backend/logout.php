<?php
require_once 'config.php';
require_once 'auth.php';

header('Content-Type: application/json');

// Attempt logout
$result = Auth::logout();

if (isset($result['success'])) {
    json_response('success', 'Logout successful');
} else {
    json_response('error', $result['error']);
}
?> 