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
    $bio = $_POST['bio'] ?? '';

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
        $stmt = $conn->prepare("UPDATE users SET name = ?, email = ?, phone = ?, location = ?, bio = ?, picture = ? WHERE id = ?");
        $stmt->bind_param('ssssssi', $name, $email, $phone, $location, $bio, $picturePath, $userId);
    } else {
        $stmt = $conn->prepare("UPDATE users SET name = ?, email = ?, phone = ?, location = ?, bio = ? WHERE id = ?");
        $stmt->bind_param('sssssi', $name, $email, $phone, $location, $bio, $userId);
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
