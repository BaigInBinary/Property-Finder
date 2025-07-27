<?php
include 'db.php'; 
header('Content-Type: application/json');

// Sanitize input
function clean_input($data) {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

function generateReferralCode($name, $length = 6) {
    $namePart = strtoupper(substr(preg_replace('/[^a-zA-Z]/', '', $name), 0, 3));
    $randomPart = strtoupper(bin2hex(random_bytes($length)));
    $timestampPart = substr(time(), -4);
    return $namePart . $randomPart . $timestampPart;
}

function getUniqueReferralCode($conn, $name) {
    do {
        $referralCode = generateReferralCode($name);
        $stmt = $conn->prepare("SELECT id FROM users WHERE referral_code = ?");
        $stmt->bind_param("s", $referralCode);
        $stmt->execute();
        $stmt->store_result();
    } while ($stmt->num_rows > 0);
    $stmt->close();
    return $referralCode;
}

// Sanitize inputs
$name = clean_input($_POST['name'] ?? '');
$email = clean_input($_POST['email'] ?? '');
$phone = clean_input($_POST['phone'] ?? '');
$location = clean_input($_POST['location'] ?? '');
$cnic = clean_input($_POST['cnic'] ?? '');
$role = clean_input($_POST['role'] ?? '');
$password = $_POST['password'] ?? '';
$confirmPassword = $_POST['confirmPassword'] ?? '';
$referralCodeInput = clean_input($_POST['referralCode'] ?? '');

// Validation
if (empty($name) || empty($email) || empty($phone) || empty($location) || empty($cnic) || empty($role) || empty($password) || empty($confirmPassword)) {
    echo json_encode(['status' => 'error', 'message' => 'All fields are required']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid email address']);
    exit;
}

if (!preg_match('/^[0-9]{5}-[0-9]{7}-[0-9]{1}$/', $cnic)) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid CNIC format']);
    exit;
}

if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{8,}$/', $password)) {
    echo json_encode(['status' => 'error', 'message' => 'Password must be at least 8 characters long, with one uppercase letter and a number.']);
    exit;
}

if ($password !== $confirmPassword) {
    echo json_encode(['status' => 'error', 'message' => 'Passwords do not match']);
    exit;
}

// Check if email exists
$stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows > 0) {
    echo json_encode(['status' => 'error', 'message' => 'Email already registered']);
    $stmt->close();
    exit;
}
$stmt->close();

// Check if CNIC exists
$stmt = $conn->prepare("SELECT id FROM users WHERE cnic = ?");
$stmt->bind_param("s", $cnic);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows > 0) {
    echo json_encode(['status' => 'error', 'message' => 'CNIC already registered']);
    $stmt->close();
    exit;
}
$stmt->close();

// Check if name already exists
$stmt = $conn->prepare("SELECT id FROM users WHERE name = ?");
$stmt->bind_param("s", $name);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows > 0) {
    echo json_encode(['status' => 'error', 'message' => 'Name is already taken']);
    $stmt->close();
    exit;
}
$stmt->close();

// Hash password
$hashedPassword = password_hash($password, PASSWORD_BCRYPT);

// Profile Picture Upload
$profilePicturePath = null;
if (isset($_FILES['picture']) && $_FILES['picture']['error'] === UPLOAD_ERR_OK) {
    $targetDir = 'uploads/profile-pic/';
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0777, true);
    }

    $ext = pathinfo($_FILES['picture']['name'], PATHINFO_EXTENSION);
    $filename = 'profile_' . time() . '_' . uniqid() . '.' . $ext;
    $targetFile = $targetDir . $filename;

    if (move_uploaded_file($_FILES['picture']['tmp_name'], $targetFile)) {
        $profilePicturePath = $targetFile;
    }
}

// Generate unique referral code for the new user
$generatedReferralCode = getUniqueReferralCode($conn, $name);

// Insert new user
$stmt = $conn->prepare("INSERT INTO users (name, email, phone, location, cnic, role, password, referral_code, picture) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
$stmt->bind_param("sssssssss", $name, $email, $phone, $location, $cnic, $role, $hashedPassword, $generatedReferralCode, $profilePicturePath);

if ($stmt->execute()) {
    $newUserId = $stmt->insert_id;
    $stmt->close();

    // Process referral bonus
    if ($referralCodeInput) {
        $stmt = $conn->prepare("SELECT id FROM users WHERE referral_code = ?");
        $stmt->bind_param("s", $referralCodeInput);
        $stmt->execute();
        $stmt->bind_result($referrerId);
        if ($stmt->fetch()) {
            $stmt->close();

            // Add record to referrals table
            $stmt = $conn->prepare("INSERT INTO referrals (referrer_id, referred_id, bonus_points_awarded, referred_at) VALUES (?, ?, 1000, NOW())");
            $stmt->bind_param("ii", $referrerId, $newUserId);
            $stmt->execute();
            $stmt->close();
        } else {
            $stmt->close();
        }
    }

    echo json_encode([
        'status' => 'success',
        'message' => 'Account created successfully',
        'referral_code' => $generatedReferralCode
    ]);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Error creating account']);
    $stmt->close();
}

$conn->close();
?>
