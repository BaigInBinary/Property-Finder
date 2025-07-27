<?php
$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    case 'signup_user':
        include 'backend/signup.php';
        break;

    case 'login_user':
        include 'backend/login.php';
        break;

    case 'upload_property':
        include 'backend/property-upload.php';  
        break;

    // more cases...
    
    default:
        echo json_encode(['status' => 'error', 'message' => 'Invalid action']);
        break;
}
?>
