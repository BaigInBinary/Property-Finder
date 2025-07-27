<?php
session_start();
header('Content-Type: application/json');
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once 'db.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'You must be logged in to upload a property.']);
    exit;
}

$userId = $_SESSION['user_id'];

// Validate required fields
$fields = ['propertyTitle', 'price', 'propertyType', 'area', 'cnicNumber', 'unit', 'description', 'city'];
foreach ($fields as $field) {
    if (empty($_POST[$field])) {
        echo json_encode(['status' => 'error', 'message' => 'Missing field: ' . $field]);
        exit;
    }
}

$title       = trim($_POST['propertyTitle']);
$price       = (float)$_POST['price'];
$type        = trim($_POST['propertyType']);
$area        = (float)$_POST['area'];
$cnic        = trim($_POST['cnicNumber']);
$unit        = trim($_POST['unit']);
$description = trim($_POST['description']);
$link        = trim($_POST['link'] ?? '');
$city        = trim($_POST['city']);

// File upload paths
$propertyDir = 'uploads/property/';
$cnicDir     = 'uploads/cnic/';
$docsDir     = 'uploads/documents/';

foreach ([$propertyDir, $cnicDir, $docsDir] as $dir) {
    if (!is_dir($dir)) mkdir($dir, 0777, true);
}

// --- Property Images ---
$propertyImages = [];

if (isset($_FILES['propertyImages']['name'][0]) && !empty($_FILES['propertyImages']['name'][0])) {
    $count = count($_FILES['propertyImages']['name']);
    if ($count > 5) {
        echo json_encode(['status' => 'error', 'message' => 'You can upload a maximum of 5 property images.']);
        exit;
    }

    for ($i = 0; $i < $count; $i++) {
        $name     = $_FILES['propertyImages']['name'][$i];
        $tmp      = $_FILES['propertyImages']['tmp_name'][$i];
        $filename = uniqid('img_') . '_' . basename($name);
        $dest     = $propertyDir . $filename;

        if (move_uploaded_file($tmp, $dest)) {
            $propertyImages[] = 'uploads/property/' . $filename;
        }
    }
}

if (count($propertyImages) === 0) {
    echo json_encode(['status' => 'error', 'message' => 'At least one property image is required.']);
    exit;
}

// --- CNIC Image ---
$cnicImagePath = '';
if (isset($_FILES['cnicImage']) && !empty($_FILES['cnicImage']['tmp_name'])) {
    $filename = uniqid('cnic_') . '_' . basename($_FILES['cnicImage']['name']);
    $dest     = $cnicDir . $filename;

    if (move_uploaded_file($_FILES['cnicImage']['tmp_name'], $dest)) {
        $cnicImagePath = 'uploads/cnic/' . $filename;
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to upload CNIC image.']);
        exit;
    }
}

// --- Ownership Documents ---
$docsPath = '';
if (isset($_FILES['ownershipDocs']) && !empty($_FILES['ownershipDocs']['tmp_name'])) {
    $filename = uniqid('doc_') . '_' . basename($_FILES['ownershipDocs']['name']);
    $dest     = $docsDir . $filename;

    if (move_uploaded_file($_FILES['ownershipDocs']['tmp_name'], $dest)) {
        $docsPath = 'uploads/documents/' . $filename;
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to upload ownership document.']);
        exit;
    }
}

// Insert into DB
$imagesJson = json_encode($propertyImages);

$stmt = $conn->prepare("INSERT INTO properties 
    (user_id, title, price, type, area, unit, city, cnic_number, cnic_image, ownership_docs, images_json, description, link, created_at)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");

if (!$stmt) {
    echo json_encode(['status' => 'error', 'message' => 'Prepare failed: ' . $conn->error]);
    exit;
}

$stmt->bind_param(
    "isdsdssssssss",
    $userId,
    $title,
    $price,
    $type,
    $area,
    $unit,
    $city,
    $cnic,
    $cnicImagePath,
    $docsPath,
    $imagesJson,
    $description,
    $link
);

if ($stmt->execute()) {
    echo json_encode(['status' => 'success', 'message' => 'Property uploaded successfully!']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $stmt->error]);
}

$stmt->close();
