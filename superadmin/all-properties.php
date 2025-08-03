<?php include 'inc/header.php'; ?>
<?php require_once '../backend/db.php'; ?>

<div class="main-content">
    <!-- Inner Navbar -->
    <div class="inner-navbar">
        <div class="row align-items-center">
            <div class="col-md-6">
                <h2 class="mb-0">Property Management</h2>
                <p class="text-muted mb-0">Manage and monitor all property listings</p>
            </div>
            <div class="col-md-6 text-md-end">
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addPropertyModal">
                    <i class="fas fa-plus me-2"></i>Add New Property
                </button>
            </div>
        </div>
    </div>

    <!-- Main Content Area -->
    <div class="container-fluid p-4">

        <!-- Properties Table -->
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-dark">
                            <tr>
                                <th>#</th>
                                <th>Property</th>
                                <th>Type</th>
                                <th>City</th>
                                <th>Price</th>
                                <th>Area</th>
                                <th>Posted By</th>
                                <th>Status</th>
                                <th>Listed Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $query = "SELECT p.*, u.name AS user_name, u.email AS user_email
                                      FROM properties p
                                      JOIN users u ON p.user_id = u.id
                                      ORDER BY p.created_at DESC";
                            $result = $conn->query($query);
                            $counter = 1;
                            while ($property = $result->fetch_assoc()): 
                                $images = json_decode($property['images_json'], true);
                                $firstImage = !empty($images) ? $images[0] : 'https://via.placeholder.com/50';
                                $statusIcon = '';
                                if ($property['listing'] == 'approved') {
                                    $statusIcon = '<i class="fas fa-check text-success" title="Approved"></i>';
                                } elseif ($property['listing'] == 'rejected') {
                                    $statusIcon = '<i class="fas fa-times text-danger" title="Rejected"></i>';
                                } elseif ($property['listing'] == 'pending') {
                                    $statusIcon = '<i class="fas fa-clock text-warning" title="Pending"></i>';
                                }
                                
                            ?>
                                <tr>
                                    <td><?php echo $counter++; ?></td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <img src="../<?php echo htmlspecialchars($firstImage); ?>" class="rounded me-2" width="50" height="50" alt="Property">
                                            <div>
                                                <div class="fw-bold"><?php echo htmlspecialchars($property['title']); ?></div>
                                                <small class="text-muted">#PROP<?php echo str_pad($property['id'], 3, '0', STR_PAD_LEFT); ?></small>
                                            </div>
                                        </div>
                                    </td>
                                    <td><?php echo htmlspecialchars($property['type']); ?></td>
                                    <td><?php echo htmlspecialchars($property['city']); ?></td>
                                    <td>PKR <?php echo number_format($property['price']); ?></td>
                                    <td><?php echo htmlspecialchars($property['area']) . ' ' . htmlspecialchars($property['unit']); ?></td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($property['user_name']); ?></strong><br>
                                        <small><?php echo htmlspecialchars($property['user_email']); ?></small>
                                    </td>
                                    <td><?php echo $statusIcon; ?></td>
                                    <td><?php echo date('Y-m-d', strtotime($property['created_at'])); ?></td>
                                    <td>
    <div class="btn-group">
        <!-- Approve Icon -->
        <a href="approve-property.php?id=<?php echo $property['id']; ?>" 
           class="btn btn-sm btn-outline-success" 
           title="Approve">
            <i class="fas fa-check"></i>
        </a>

        <!-- Reject Icon -->
        <a href="reject-property.php?id=<?php echo $property['id']; ?>" 
           class="btn btn-sm btn-outline-danger" 
           title="Reject">
            <i class="fas fa-times"></i>
        </a>

        <a href="view-property-detail.php?id=<?php echo $property['id']; ?>" 
   class="btn btn-sm btn-outline-info me-1" 
   title="View">
    <i class="fas fa-eye"></i>
</a>

        </a>
    </div>
</td>

                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>
</div>

<?php include 'inc/footer.php'; ?>
