<?php
include 'inc/header.php';
require_once 'backend/db.php';

// Initialize filters from GET
$minPrice = $_GET['minPrice'] ?? null;
$maxPrice = $_GET['maxPrice'] ?? null;
$propertyType = $_GET['propertyType'] ?? null;
$area = $_GET['area'] ?? null;
$areaUnit = $_GET['areaUnit'] ?? null;
$city = $_GET['city'] ?? null;

// Base query
$query = "SELECT * FROM properties WHERE 1=1";
$params = [];
$types = '';

// Add filters dynamically
if (!empty($minPrice)) {
    $query .= " AND price >= ?";
    $params[] = $minPrice;
    $types .= 'd';
}

if (!empty($maxPrice)) {
    $query .= " AND price <= ?";
    $params[] = $maxPrice;
    $types .= 'd';
}

if (!empty($propertyType)) {
    $query .= " AND type = ?";
    $params[] = $propertyType;
    $types .= 's';
}

if (!empty($area)) {
    $query .= " AND area = ?";
    $params[] = $area;
    $types .= 'd';
}

if (!empty($areaUnit)) {
    $query .= " AND unit = ?";
    $params[] = $areaUnit;
    $types .= 's';
}

if (!empty($city)) {
    $query .= " AND city = ?";
    $params[] = $city;
    $types .= 's';
}

$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();

$properties = [];
while ($row = $result->fetch_assoc()) {
    $properties[] = $row;
}

$stmt->close();

// Build search criteria display
$searchCriteria = [];
if (!empty($minPrice)) $searchCriteria[] = "Min Price: PKR " . number_format($minPrice);
if (!empty($maxPrice)) $searchCriteria[] = "Max Price: PKR " . number_format($maxPrice);
if (!empty($city)) $searchCriteria[] = "City: " . htmlspecialchars($city);
if (!empty($propertyType)) $searchCriteria[] = "Type: " . ucfirst(htmlspecialchars($propertyType));
if (!empty($area)) $searchCriteria[] = "Area: " . $area . " " . htmlspecialchars($areaUnit);
?>

<!-- Search Results Header -->
<div class="search-results-header py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8 text-center">
                <h1 class="search-results-title mb-3">
                    <i class="fas fa-search me-3"></i>
                    Search Results
                </h1>
                <div class="search-results-count mb-4">
                    <span class="badge bg-primary fs-6 px-4 py-2">
                        <?php echo count($properties); ?> Properties Found
                    </span>
                </div>
                
                <!-- Search Criteria Display -->
                <?php if (!empty($searchCriteria)): ?>
                <div class="search-criteria mb-4">
                    <h6 class="text-muted mb-2">Search Criteria:</h6>
                    <div class="d-flex flex-wrap justify-content-center gap-2">
                        <?php foreach ($searchCriteria as $criteria): ?>
                            <span class="badge bg-light text-dark border px-3 py-2">
                                <i class="fas fa-filter me-1"></i>
                                <?php echo $criteria; ?>
                            </span>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Back to Search Button -->
                <div class="mb-4">
                    <a href="index.php" class="btn btn-outline-primary">
                        <i class="fas fa-arrow-left me-2"></i>
                        Back to Search
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Search Results Content -->
<div class="container-fluid py-4">
    <div class="row g-4">
        <?php if (empty($properties)): ?>
            <div class="col-12">
                <div class="no-results-container text-center py-5">
                    <div class="no-results-icon mb-4">
                        <i class="fas fa-search fa-4x text-muted"></i>
                    </div>
                    <h3 class="text-muted mb-3">No Properties Found</h3>
                    <p class="text-muted mb-4">We couldn't find any properties matching your search criteria. Try adjusting your filters or browse all properties.</p>
                    <div class="d-flex justify-content-center gap-3">
                        <a href="index.php" class="btn btn-primary">
                            <i class="fas fa-search me-2"></i>
                            New Search
                        </a>
                        <a href="listings.php" class="btn btn-outline-secondary">
                            <i class="fas fa-list me-2"></i>
                            Browse All Properties
                        </a>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <?php foreach ($properties as $property): ?>
                <?php
                    $images = json_decode($property['images_json'], true);
                    $firstImage = (!empty($images) && isset($images[0])) ? htmlspecialchars($images[0]) : 'https://via.placeholder.com/300x200?text=No+Image';
                    $bookmarkIcon = !empty($property['is_saved']) ? 'fas fa-bookmark text-primary' : 'far fa-bookmark';
                ?>
                <div class="col-lg-3  col-md-4">
                    <div class="property-card card h-100 shadow-sm border-0 rounded-4 property-hover">
                        <div class="property-image-wrapper position-relative">
                            <img src="<?php echo $firstImage; ?>" alt="Property Image" class="card-img-top rounded-top-4" style="height: 220px; object-fit: cover;">
                            
                            <!-- Verified Badge -->
                            <span class="verified-badge badge bg-success position-absolute top-0 start-0 m-3">
                                <i class="fas fa-check-circle me-1"></i> Verified
                            </span>


                            <!-- Favorite Button -->
                            <button class="favorite-btn position-absolute bottom-0 end-0 m-3 btn btn-light rounded-circle shadow-sm"
                                data-property-id="<?php echo $property['id']; ?>"
                                data-owner-id="<?php echo $property['user_id']; ?>"
                                title="Save Property">
                                <i class="<?php echo $bookmarkIcon; ?>"></i>
                            </button>
                        </div>

                        <a href="view-property-detail.php?id=<?php echo $property['id']; ?>" class="text-decoration-none text-dark">
                            <div class="card-body">
                                <h5 class="card-title fw-bold mb-2"><?php echo htmlspecialchars($property['title']); ?></h5>
                                <p class="card-text text-primary fw-bold fs-5 mb-2">
                                    PKR <?php echo number_format($property['price']); ?>
                                </p>
                                <p class="card-text text-muted mb-3">
                                    <i class="fas fa-map-marker-alt text-primary me-2"></i> 
                                    <?php echo htmlspecialchars($property['city']); ?>
                                </p>
                                <div class="property-features d-flex flex-wrap gap-3 mt-3">
                                    <?php if (!empty($property['area'])): ?>
                                        <span class="feature-item">
                                            <i class="fas fa-ruler-combined text-primary"></i> 
                                            <?php echo $property['area'] . ' ' . htmlspecialchars($property['unit']); ?>
                                        </span>
                                    <?php endif; ?>
                                    <?php if (!empty($property['type'])): ?>
                                        <span class="feature-item">
                                            <i class="fas fa-home text-primary"></i> 
                                            <?php echo ucfirst(htmlspecialchars($property['type'])); ?>
                                        </span>
                                    <?php endif; ?>
                                    <?php if (!empty($property['status'])): ?>
                                        <span class="feature-item">
                                            <i class="fas fa-circle text-success"></i> 
                                            <?php echo ucfirst(htmlspecialchars($property['status'])); ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<script>
    document.querySelectorAll('.favorite-btn').forEach(btn => {
    btn.addEventListener('click', function () {
        const propertyId = this.getAttribute('data-property-id');
        const ownerId = this.getAttribute('data-owner-id');
        const icon = this.querySelector('i');

        fetch('backend/check-session.php')
            .then(res => res.json())
            .then(sessionData => {
                if (!sessionData.logged_in) {
                    iziToast.warning({
                        title: 'Login Required',
                        message: 'You need to be logged in to save this property.',
                        position: 'topRight'
                    });
                    return;
                }

                if (sessionData.user_id == ownerId) {
                    iziToast.error({
                        title: 'Not Allowed',
                        message: 'You cannot save your own property.',
                        position: 'topRight'
                    });
                    return;
                }

                fetch('backend/save-property.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ property_id: propertyId })
                })
                .then(res => res.json())
                .then(saveData => {
                    if (saveData.status === 'success') {
                        if (saveData.is_saved === 1) {
                            icon.classList.remove('far');
                            icon.classList.add('fas', 'text-primary');
                            iziToast.success({
                                title: 'Saved',
                                message: 'Property added to favorites.',
                                position: 'topRight'
                            });
                        } else {
                            icon.classList.remove('fas', 'text-primary');
                            icon.classList.add('far');
                            iziToast.info({
                                title: 'Removed',
                                message: 'Property removed from favorites.',
                                position: 'topRight'
                            });
                        }
                    } else {
                        iziToast.error({
                            title: 'Error',
                            message: saveData.message || 'Failed to save property.',
                            position: 'topRight'
                        });
                    }
                });
            })
            .catch(err => {
                console.error('Session check failed:', err);
                iziToast.error({
                    title: 'Error',
                    message: 'Failed to check session.',
                    position: 'topRight'
                });
            });
    });
});

</script>

<?php include'inc/footer.php'?>