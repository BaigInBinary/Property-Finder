<?php
session_start();
require_once 'db.php';

$response = ['success' => false];

if (isset($_SESSION['user_id'])) {
    $userId = $_SESSION['user_id'];

    $name = $_POST['full_name'] ?? '';
    $email = $_POST['email'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $location = $_POST['location'] ?? '';
    $cnic = $_POST['cnic'] ?? '';
    $bio = $_POST['bio'] ?? '';

    // Validation
    $errors = [];
    
    // Validate name (only letters and spaces)
    if (!preg_match('/^[A-Za-z\s]+$/', $name)) {
        $errors[] = 'Name should only contain letters and spaces.';
    }
    
    // Validate phone format (03XX-XXXXXXX)
    if (!empty($phone) && !preg_match('/^[0-9]{4}-[0-9]{7}$/', $phone)) {
        $errors[] = 'Phone number should be in format: 03XX-XXXXXXX';
    }
    
    // Validate location (letters, spaces, commas, dots, hyphens)
    if (!empty($location) && !preg_match('/^[A-Za-z\s,.\-]+$/', $location)) {
        $errors[] = 'Location should only contain letters, spaces, commas, dots, and hyphens.';
    }
    
    // Validate CNIC format (XXXXX-XXXXXXX-X)
    if (!empty($cnic) && !preg_match('/^[0-9]{5}-[0-9]{7}-[0-9]{1}$/', $cnic)) {
        $errors[] = 'CNIC should be in format: XXXXX-XXXXXXX-X';
    }
    
    // Validate bio length
    if (strlen($bio) > 500) {
        $errors[] = 'Bio should not exceed 500 characters.';
    }
    
    if (!empty($errors)) {
        $response['message'] = implode(' ', $errors);
        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    }

    $picturePath = null;

    // Handle file upload if exists
    if (isset($_FILES['picture']) && $_FILES['picture']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = '../uploads/profile-pic/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $fileTmp = $_FILES['picture']['tmp_name'];
        $fileName = basename($_FILES['picture']['name']);
        $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        $allowedExts = ['jpg', 'jpeg', 'png', 'gif'];

        if (in_array($fileExt, $allowedExts)) {
            $newFileName = 'profile_' . $userId . '_' . time() . '.' . $fileExt;
            $destination = $uploadDir . $newFileName;

            if (move_uploaded_file($fileTmp, $destination)) {
                $picturePath = 'uploads/profile-pic/' . $newFileName;
            } else {
                error_log("Failed to move uploaded file from $fileTmp to $destination");
            }
        } else {
            error_log("Invalid file extension: $fileExt");
        }
    }

    // Prepare SQL with picture conditional update
    if ($picturePath) {
        $stmt = $conn->prepare("UPDATE users SET name = ?, email = ?, phone = ?, location = ?, cnic = ?, bio = ?, picture = ? WHERE id = ?");
        $stmt->bind_param('sssssssi', $name, $email, $phone, $location, $cnic, $bio, $picturePath, $userId);
    } else {
        $stmt = $conn->prepare("UPDATE users SET name = ?, email = ?, phone = ?, location = ?, cnic = ?, bio = ? WHERE id = ?");
        $stmt->bind_param('ssssssi', $name, $email, $phone, $location, $cnic, $bio, $userId);
    }

    if ($stmt->execute()) {
        $response['success'] = true;
        $response['message'] = 'Profile updated successfully';
        if ($picturePath) {
            $response['picture_path'] = $picturePath;
        }
    } else {
        $response['message'] = 'Database update failed: ' . $stmt->error;
        error_log("Profile update failed: " . $stmt->error);
    }
}

header('Content-Type: application/json');
echo json_encode($response);
