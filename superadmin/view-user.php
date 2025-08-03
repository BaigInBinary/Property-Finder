<?php
require_once '../backend/db.php';
include 'inc/header.php';

$userId = $_GET['id'] ?? null;

if (!$userId) {
    echo "<div class='alert alert-danger'>User ID is missing.</div>";
    include 'inc/footer.php';
    exit;
}

// Fetch user details
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param('i', $userId);
$stmt->execute();
$userResult = $stmt->get_result();
$user = $userResult->fetch_assoc();
$stmt->close();

if (!$user) {
    echo "<div class='alert alert-danger'>User not found.</div>";
    include 'inc/footer.php';
    exit;
}

// Fetch user properties
$stmt = $conn->prepare("SELECT * FROM properties WHERE user_id = ?");
$stmt->bind_param('i', $userId);
$stmt->execute();
$propertiesResult = $stmt->get_result();
?>

<div class="main-content container-fluid p-4">

    <h2 class="mb-4">User Details</h2>

    <div class="card mb-4">
        <div class="card-body">
            <div class="row align-items-center">
                <div class="col-md-3 text-center">
                    <img src="../<?php echo htmlspecialchars($user['picture'] ?? 'uploads/default-user.png'); ?>" 
                         alt="User Picture" 
                         class="rounded img-thumbnail mb-2" 
                         width="150" height="150" 
                         style="object-fit: cover;">
                    <h5 class="mt-2"><?php echo htmlspecialchars($user['name']); ?></h5>
                </div>
                <div class="col-md-9">
                    <p><strong>CNIC:</strong> <?php echo htmlspecialchars($user['cnic']); ?></p>
                    <p><strong>Phone:</strong> <?php echo htmlspecialchars($user['phone']); ?></p>
                    <p><strong>Email:</strong> <?php echo htmlspecialchars($user['email']); ?></p>
                    <p><strong>Joined On:</strong> <?php echo date('Y-m-d', strtotime($user['created_at'])); ?></p>
                </div>
            </div>
        </div>
    </div>

    <h3>Properties Posted</h3>

    <?php if ($propertiesResult->num_rows > 0): ?>
        <div class="row g-3">
            <?php while ($property = $propertiesResult->fetch_assoc()): 
                $images = json_decode($property['images_json'], true);
                $firstImage = !empty($images) ? '../' . $images[0] : 'https://via.placeholder.com/150';

                $statusIcon = '';
                if ($property['listing'] == 'approved') {
                    $statusIcon = '<i class="fas fa-check text-success" title="Approved"></i>';
                } elseif ($property['listing'] == 'rejected') {
                    $statusIcon = '<i class="fas fa-times text-danger" title="Rejected"></i>';
                } elseif ($property['listing'] == 'pending') {
                    $statusIcon = '<i class="fas fa-clock text-warning" title="Pending"></i>';
                }
            ?>
                <div class="col-md-4">
                    <div class="card h-100">
                        <img src="<?php echo htmlspecialchars($firstImage); ?>" class="card-img-top" alt="Property Image" height="200" style="object-fit: cover;">
                        <div class="card-body">
                            <h5 class="card-title"><?php echo htmlspecialchars($property['title']); ?></h5>
                            <p class="card-text">
                                <strong>City:</strong> <?php echo htmlspecialchars($property['city']); ?><br>
                                <strong>Price:</strong> PKR <?php echo number_format($property['price']); ?><br>
                                <strong>Area:</strong> <?php echo htmlspecialchars($property['area']) . ' ' . htmlspecialchars($property['unit']); ?><br>
                                <strong>Status:</strong> <?php echo $statusIcon; ?>
                            </p>
                            <a href="view-property-detail.php?id=<?php echo $property['id']; ?>" class="btn btn-sm btn-outline-info">View Details</a>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    <?php else: ?>
        <p class="text-muted">No properties posted by this user.</p>
    <?php endif; ?>

</div>

<?php include 'inc/footer.php'; ?>
