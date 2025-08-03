<?php
require_once 'db.php';

$params = [];
$types = "";
// Get filters from POST or set defaults
$minPrice = $_POST['minPrice'] ?? '';
$maxPrice = $_POST['maxPrice'] ?? '';
$city = $_POST['city'] ?? '';
$minSize = $_POST['minSize'] ?? '';
$maxSize = $_POST['maxSize'] ?? '';
$type = $_POST['propertyType'] ?? '';
$bedrooms = $_POST['bedrooms'] ?? '';

// Check if any filters are set
$hasFilters = !empty($city) || !empty($type) || !empty($_POST['minPrice']) || !empty($_POST['maxPrice']) || !empty($_POST['minSize']) || !empty($_POST['maxSize']);

if ($hasFilters) {
    $query = "SELECT 
        p.id, p.title, p.price, p.type, p.area, p.location, p.city, 
        p.images_json, u.name AS user_name 
    FROM properties p 
    JOIN users u ON p.user_id = u.id 
    WHERE p.listing = 'approved' ";
    if (!empty($city)) {
        $query .= " AND p.city = ?";
        $params[] = $city;
        $types .= "s";
    }
    if (!empty($type)) {
        $query .= " AND p.type = ?";
        $params[] = $type;
        $types .= "s";
    }
    if (!empty($_POST['minPrice'])) {
        $query .= " AND p.price >= ?";
        $params[] = (float)$_POST['minPrice'];
        $types .= 'd';
    }
    if (!empty($_POST['maxPrice'])) {
        $query .= " AND p.price <= ?";
        $params[] = (float)$_POST['maxPrice'];
        $types .= 'd';
    }
    if (!empty($_POST['minSize'])) {
        $query .= " AND p.area >= ?";
        $params[] = (float)$_POST['minSize'];
        $types .= 'd';
    }
    if (!empty($_POST['maxSize'])) {
        $query .= " AND p.area <= ?";
        $params[] = (float)$_POST['maxSize'];
        $types .= 'd';
    }
    $stmt = $conn->prepare($query);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
} else {
    $query = "SELECT 
        p.id, p.title, p.price, p.type, p.area, p.location, p.city, 
        p.images_json, u.name AS user_name 
    FROM properties p 
    JOIN users u ON p.user_id = u.id 
    WHERE p.listing = 'approved'";
    $stmt = $conn->prepare($query);
}

$stmt->execute();
$result = $stmt->get_result();

$properties = [];
while ($row = $result->fetch_assoc()) {
    $properties[] = [
        'id' => $row['id'],
        'title'      => $row['title'],
        'price'      => (float)$row['price'],
        'type'       => $row['type'],
        'area'       => (float)$row['area'],
        'location'   => $row['location'],
        'city'       => $row['city'],
        'user_name'  => $row['user_name'],
        'images'     => json_decode($row['images_json'] ?? '[]')
    ];
}

// Debug: Output query and params for troubleshooting
file_put_contents('debug_fetch_properties.log', print_r([
    'query' => $query,
    'params' => $params,
    'types' => $types
], true));

// Output JSON
echo json_encode([
    'status' => 'success',
    'properties' => $properties
]);
?>
