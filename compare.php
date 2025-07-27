<?php include'inc/header.php'; ?>
<?php
include 'backend/db.php';
$properties = [];
$result = $conn->query("SELECT id, title, price, area, unit, type, location, images_json FROM properties ORDER BY created_at DESC");
while ($row = $result->fetch_assoc()) {
    $row['images'] = json_decode($row['images_json'] ?? '[]', true);
    $properties[] = $row;
}
?>

<!-- Main Content -->
<main class="compare-container mt-5 pt-4">
    <div class="container">
        <!-- Page Header -->
        <div class="row mb-4">
            <div class="col-12">
                <h1 class="h2">Compare Properties</h1>
                <p class="text-muted">Compare up to 3 properties side by side</p>
            </div>
        </div>

        <!-- Property Selection -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between">
                            <h5 class="card-title mb-0">Select Properties to Compare</h5>
                            <button class="btn btn-outline-danger" id="clearAll">
                                <i class="fas fa-trash-alt"></i> Clear All
                            </button>
                        </div>
                        <hr>
                        <div class="property-selector">
                            <select class="form-select" id="propertySelect">
                                <option value="">Select a property to compare...</option>
                                <?php foreach ($properties as $p): ?>
                                    <option value="<?php echo $p['id']; ?>" data-prop='<?php echo htmlspecialchars(json_encode($p), ENT_QUOTES, "UTF-8"); ?>'>
                                        <?php echo htmlspecialchars($p['title']) . ' - ' . htmlspecialchars($p['location']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="selected-count mt-2">
                                <small class="text-muted">
                                    <span id="selectedCount">0</span>/3 properties selected
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Comparison Grid -->
        <div class="row comparison-grid" id="comparisonGrid">
            <!-- Empty State -->
            <div class="col-12 text-center empty-state" id="emptyState">
                <i class="fas fa-building fa-3x mb-3"></i>
                <h3>No Properties Selected</h3>
                <p>Select properties above to start comparing</p>
            </div>
        </div>

        <!-- Mobile View Warning -->
        <div class="d-block d-md-none alert alert-info mt-3">
            <i class="fas fa-info-circle"></i>
            Scroll horizontally to view full comparison
        </div>
    </div>
</main>

<!-- Property Card Template -->
<template id="propertyCardTemplate">
    <div class="col-md-4 property-card">
        <div class="card h-100">
            <button type="button" class="btn-close remove-property" aria-label="Remove property"></button>
            <img src="" class="card-img-top" alt="Property Image">
            <div class="card-body">
                <h5 class="card-title property-title"></h5>
                <p class="card-text property-location">
                    <i class="fas fa-map-marker-alt"></i>
                    <span></span>
                </p>

                <div class="property-specs">
                    <div class="spec-item">
                        <div class="spec-label">Price</div>
                        <div class="spec-value price"></div>
                    </div>
                    <div class="spec-item">
                        <div class="spec-label">Size</div>
                        <div class="spec-value size"></div>
                    </div>
                    <div class="spec-item">
                        <div class="spec-label">Type</div>
                        <div class="spec-value type"></div>
                    </div>
                </div>

                <div class="features-section mt-3">
                    <h6>Features</h6>
                    <ul class="features-list">
                        <!-- Features will be populated via JavaScript -->
                    </ul>
                </div>
            </div>
            <div class="card-footer">
                <a href="#" class="btn btn-primary w-100 view-details">View Details</a>
            </div>
        </div>
    </div>
</template>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    window.allProperties = <?php echo json_encode($properties); ?>;
</script>
<script src="js/compare.js"></script>
</body>
</html>
