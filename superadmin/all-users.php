<?php include 'inc/header.php'; ?>
<?php require_once '../backend/db.php'; ?>

<div class="main-content">
    <!-- Inner Navbar -->
    <div class="inner-navbar">
        <div class="row align-items-center">
            <div class="col-md-6">
                <h2 class="mb-0">User Management</h2>
                <p class="text-muted mb-0">Manage and monitor all registered users</p>
            </div>
        </div>
    </div>

    <!-- Main Content Area -->
    <div class="container-fluid p-4">

        <!-- Users Table -->
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-dark">
                            <tr>
                                <th>#</th>
                                <th>User</th>
                                <th>CNIC</th>
                                <th>Phone</th>
                                <th>Registered At</th>
                                <th>Properties Posted</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $query = "
                                SELECT u.id, u.name, u.cnic, u.phone, u.picture, u.created_at, COUNT(p.id) as property_count
                                FROM users u
                                LEFT JOIN properties p ON u.id = p.user_id
                                GROUP BY u.id
                                ORDER BY u.created_at DESC
                            ";
                            $result = $conn->query($query);
                            $counter = 1;
                            while ($user = $result->fetch_assoc()):
                                $userImage = !empty($user['picture']) ? '../' . htmlspecialchars($user['picture']) : 'https://via.placeholder.com/50';
                            ?>
                                <tr>
                                    <td><?php echo $counter++; ?></td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <img src="<?php echo $userImage; ?>" class="rounded-circle me-2" width="50" height="50" alt="User Picture">
                                            <div>
                                                <div class="fw-bold"><?php echo htmlspecialchars($user['name']); ?></div>
                                                <small class="text-muted">#USER<?php echo str_pad($user['id'], 3, '0', STR_PAD_LEFT); ?></small>
                                            </div>
                                        </div>
                                    </td>
                                    <td><?php echo htmlspecialchars($user['cnic']); ?></td>
                                    <td><?php echo htmlspecialchars($user['phone']); ?></td>
                                    <td><?php echo date('Y-m-d', strtotime($user['created_at'])); ?></td>
                                    <td><span class="badge bg-primary"><?php echo $user['property_count']; ?></span></td>
                                    <td>
                                        <div class="btn-group">
                                            <a href="delete-user.php?id=<?php echo $user['id']; ?>" class="btn btn-sm btn-outline-danger" title="Delete" onclick="return confirm('Are you sure you want to delete this user?')">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                            <a href="view-user.php?id=<?php echo $user['id']; ?>" class="btn btn-sm btn-outline-info" title="View Details">
                                                <i class="fas fa-eye"></i>
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
