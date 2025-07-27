<?php
require_once '../backend/db.php';
include 'inc/header.php';

$propertyId = $_GET['id'] ?? null;

if (!$propertyId) {
    echo "<div class='alert alert-danger'>Property ID is missing.</div>";
    include 'inc/footer.php';
    exit;
}

// Fetch property with owner details
$stmt = $conn->prepare("SELECT p.*, u.name AS owner_name, u.picture AS owner_picture
                        FROM properties p
                        JOIN users u ON p.user_id = u.id
                        WHERE p.id = ?");
$stmt->bind_param('i', $propertyId);
$stmt->execute();
$result = $stmt->get_result();
$property = $result->fetch_assoc();
$stmt->close();

if (!$property) {
    echo "<div class='alert alert-danger'>Property not found.</div>";
    include 'inc/footer.php';
    exit;
}

$images = json_decode($property['images_json'], true) ?? [];
?>

<div class="main-content container-fluid p-4">
    <h2 class="mb-4">Property Details</h2>

    <!-- Image Slider -->
    <?php if (!empty($images)): ?>
        <div id="propertyImagesCarousel" class="carousel slide mb-4" data-bs-ride="carousel">
            <div class="carousel-inner">
                <?php foreach ($images as $index => $img): ?>
                    <div class="carousel-item <?php echo $index === 0 ? 'active' : ''; ?>">
                        <img src="../<?php echo htmlspecialchars($img); ?>" class="d-block w-100" alt="Property Image" style="height: 400px; object-fit: cover;">
                    </div>
                <?php endforeach; ?>
            </div>
            <button class="carousel-control-prev" type="button" data-bs-target="#propertyImagesCarousel" data-bs-slide="prev">
                <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                <span class="visually-hidden">Previous</span>
            </button>
            <button class="carousel-control-next" type="button" data-bs-target="#propertyImagesCarousel" data-bs-slide="next">
                <span class="carousel-control-next-icon" aria-hidden="true"></span>
                <span class="visually-hidden">Next</span>
            </button>
        </div>
    <?php else: ?>
        <p class="text-muted">No images available for this property.</p>
    <?php endif; ?>

    <!-- Property Info -->
    <div class="card mb-4">
        <div class="card-body">
            <h4><?php echo htmlspecialchars($property['title']); ?></h4>
            <p><strong>Type:</strong> <?php echo htmlspecialchars($property['type']); ?></p>
            <p><strong>City:</strong> <?php echo htmlspecialchars($property['city']); ?></p>
            <p><strong>Price:</strong> PKR <?php echo number_format($property['price']); ?></p>
            <p><strong>Area:</strong> <?php echo htmlspecialchars($property['area']) . ' ' . htmlspecialchars($property['unit']); ?></p>
            <p><strong>Description:</strong> <?php echo nl2br(($property['description'])); ?></p>
            <p><strong>Status:</strong> 
                <span class="badge 
                    <?php 
                        echo ($property['status'] === 'available') ? 'bg-success' : 
                             (($property['status'] === 'rejected') ? 'bg-danger' : 'bg-warning'); 
                    ?>">
                    <?php echo ucfirst($property['status']); ?>
                </span>
            </p>

            <hr>

            <!-- CNIC and Ownership Docs -->
            <div class="row text-center mb-3">
                <div class="col-md-6">
                    <h6>CNIC Image</h6>
                    <img src="../<?php echo htmlspecialchars($property['cnic_image'] ?: 'uploads/default-doc.png'); ?>" 
                         class="img-thumbnail" width="200" height="200" style="object-fit:cover;" alt="CNIC Image">
                </div>
                <div class="col-md-6">
                    <h6>Ownership Document</h6>
                    <img src="../<?php echo htmlspecialchars($property['ownership_docs'] ?: 'uploads/default-doc.png'); ?>" 
                         class="img-thumbnail" width="200" height="200" style="object-fit:cover;" alt="Ownership Document">
                </div>
            </div>

            <hr>

            <h5>Owner Information</h5>
            <div class="d-flex align-items-center">
                <img src="../<?php echo htmlspecialchars($property['owner_picture'] ?: 'uploads/default-user.png'); ?>" 
                     class="rounded-circle me-3" alt="Owner Picture" width="60" height="60">
                <strong><?php echo htmlspecialchars($property['owner_name']); ?></strong>
            </div>
        </div>
    </div>

    <a href="all-properties.php" class="btn btn-secondary">Back to All Properties</a>
</div>

<?php include 'inc/footer.php'; ?>
