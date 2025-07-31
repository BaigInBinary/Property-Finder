<?php
session_start();
require_once '../backend/db.php'; // adjust path if needed

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php?message=login_required");
    exit;
}

$userId = $_SESSION['user_id'];

// Fetch user name from database
$sql = "SELECT * FROM users WHERE id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $userId);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$userName = 'User';
if ($row = mysqli_fetch_assoc($result)) {
    $userName = $row['name'];
    $userEmail = $row['email'];
    $userCNIC = $row['cnic'];
    $userRole = $row['role'];
    $userCreated = $row['created_at'];
    $picture = $row['picture'];
}

// Fetch revenue and expenditure for the user
$revenue = 0;
$expenditure = 0;
$stmt = $conn->prepare("SELECT SUM(amount) FROM transactions WHERE seller_id = ?");
$stmt->bind_param('i', $userId);
$stmt->execute();
$stmt->bind_result($revenue);
$stmt->fetch();
$stmt->close();
$stmt = $conn->prepare("SELECT SUM(amount) FROM transactions WHERE buyer_id = ?");
$stmt->bind_param('i', $userId);
$stmt->execute();
$stmt->bind_result($expenditure);
$stmt->fetch();
$stmt->close();
// Fetch transaction history for the user
$transactions = [];
$stmt = $conn->prepare("SELECT t.*, p.title AS property_title, bu.name AS buyer_name, se.name AS seller_name FROM transactions t
    LEFT JOIN properties p ON t.property_id = p.id
    LEFT JOIN users bu ON t.buyer_id = bu.id
    LEFT JOIN users se ON t.seller_id = se.id
    WHERE t.buyer_id = ? OR t.seller_id = ? ORDER BY t.created_at DESC");
$stmt->bind_param('ii', $userId, $userId);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $transactions[] = $row;
}
$stmt->close();
?>
<style>
    .navbar {
        z-index: 1030;
        padding: 1rem;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }

    .main-content {
        padding-top: 76px;
        /* Adjust based on your navbar height */
    }

    .sidebar {
        top: 76px;
        /* Should match the padding-top of main-content */
        height: calc(100vh - 76px);
    }

    @media (max-width: 991.98px) {
        .navbar-collapse {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: white;
            padding: 1rem;
            border-radius: 0 0 0.5rem 0.5rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
    }

    .notifications-dropdown .dropdown-menu {
        min-width: 300px;
    }

    .notification-item {
        display: flex;
        align-items: center;
        gap: 1rem;
        padding: 0.5rem;
    }
    
    /* Loading styles for buy requests */
    .loading-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.5);
        display: none;
        justify-content: center;
        align-items: center;
        z-index: 9999;
    }
    
    .loading-spinner {
        background: white;
        padding: 30px;
        border-radius: 10px;
        text-align: center;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
    }
    
    .spinner {
        border: 4px solid #f3f3f3;
        border-top: 4px solid #3498db;
        border-radius: 50%;
        width: 40px;
        height: 40px;
        animation: spin 1s linear infinite;
        margin: 0 auto 15px;
    }
    
    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }
    
    .btn:disabled {
        opacity: 0.6;
        cursor: not-allowed;
    }
    
    .row-loading {
        background-color: #f8f9fa;
        opacity: 0.7;
    }

    .notification-content {
        flex: 1;
    }

    .avatar-sm {
        width: 32px;
        height: 32px;
        border-radius: 50%;
        object-fit: cover;
    }

    .bg-gradient-primary {
        background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
        border: none;
    }

    .card-header.bg-gradient-primary {
        border-radius: 0.375rem 0.375rem 0 0;
        padding: 1.5rem;
    }

    .card-header.bg-gradient-primary .fas {
        color: rgba(255, 255, 255, 0.9);
    }

    .card-header.bg-gradient-primary h4 {
        color: white;
        text-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
    }

    .card-header.bg-gradient-primary small {
        color: rgba(255, 255, 255, 0.8);
    }
</style>

<?php
                require_once '../backend/db.php';

                $userId = $_SESSION['user_id'] ?? null;
                if (!$userId) {
                    die('User not logged in.');
                }

                // Posted Properties
                $postedCount = 0;
                $stmt = $conn->prepare("SELECT COUNT(*) FROM properties WHERE user_id = ?");
                $stmt->bind_param('i', $userId);
                $stmt->execute();
                $stmt->bind_result($postedCount);
                $stmt->fetch();
                $stmt->close();

                // Saved Properties
                $savedCount = 0;
                $stmt = $conn->prepare("SELECT COUNT(*) FROM saved_properties WHERE user_id = ?");
                $stmt->bind_param('i', $userId);
                $stmt->execute();
                $stmt->bind_result($savedCount);
                $stmt->fetch();
                $stmt->close();

                // Reward Points
                $rewardPoints = 0;
                $stmt = $conn->prepare("SELECT SUM(bonus_points_awarded) FROM referrals WHERE referrer_id = ?");
                $stmt->bind_param('i', $userId);
                $stmt->execute();
                $stmt->bind_result($rewardPoints);
                $stmt->fetch();
                $stmt->close();

                $stmt = $conn->prepare("SELECT id, title, location, price, images_json FROM properties WHERE user_id = ? ORDER BY id DESC LIMIT 5");

                if (!$stmt) {
                    die("Prepare failed: " . $conn->error);
                }

                $stmt->bind_param('i', $userId);
                $stmt->execute();
                $result = $stmt->get_result();

                $recentProperties = [];
                while ($row = $result->fetch_assoc()) {
                    $images = json_decode($row['images_json'], true);
                    $row['thumbnail'] = (!empty($images) && isset($images[0])) ? $images[0] : 'https://via.placeholder.com/100x100?text=No+Image';
                    $recentProperties[] = $row;
                }
                $stmt->close();


                ?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title></title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="../css/styles.css" rel="stylesheet">
    <link href="../css/dashboard.css" rel="stylesheet">
</head>

<body class="dashboard-body">
    <!-- Loading Overlay -->
    <div class="loading-overlay" id="loadingOverlay">
        <div class="loading-spinner">
            <div class="spinner"></div>
            <div id="loadingText">Processing request...</div>
        </div>
    </div>
    
    <div class="dashboard-wrapper">
        <!-- Sidebar -->
        <nav id="sidebar" class="sidebar">
            <!-- <div class="sidebar-header">
                <a href="index.php" class="sidebar-brand">
                    <i class="fas fa-home"></i>
                    <span>PropFind</span>
                </a>
                <button type="button" id="sidebarCollapse" class="btn btn-link d-lg-none">
                    <i class="fas fa-bars"></i>
                </button>
            </div> -->

            <div class="sidebar-user">

                <img src="<?php echo $picture ? '../' . $picture : '../images/user.png' ?>" alt="User Avatar" class="user-avatar">
                <div class="user-info">
                    <h6 class="user-name mb-0" id="profileName"><? $userName ?></h6>
                    <span class="user-role" id="profileRole">Agent</span>
                </div>
            </div>

            <ul class="sidebar-nav">
                <li class="nav-item active">
                    <a href="#overview" class="nav-link" data-section="overview">
                        <i class="fas fa-th-large"></i>
                        <span>Overview</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#profile" class="nav-link" data-section="profile">
                        <i class="fas fa-user"></i>
                        <span>Profile</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#account-settings" class="nav-link" data-section="account-settings">
                        <i class="fas fa-cog"></i>
                        <span>Account Settings</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#properties" class="nav-link" data-section="properties">
                        <i class="fas fa-building"></i>
                        <span>My Properties</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#saved" class="nav-link" data-section="saved">
                        <i class="fas fa-bookmark"></i>
                        <span>Saved Properties</span>
                    </a>
                </li>
                <!-- <li class="nav-item">
                    <a href="#notifications" class="nav-link" data-section="notifications">
                        <i class="fas fa-bell"></i>
                        <span>Notifications</span>
                        <span class="badge bg-danger">3</span>
                    </a>
                </li> -->
                <li class="nav-item">
                    <a href="#rewards" class="nav-link" data-section="rewards">
                        <i class="fas fa-gift"></i>
                        <span>Referral Rewards</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#transactions" class="nav-link" data-section="transactions">
                        <i class="fas fa-exchange-alt"></i>
                        <span>Transactions</span>
                    </a>
                </li>
                <?php if (isset($userRole) && strtolower($userRole) === 'admin'): ?>
                <li class="nav-item">
                    <a href="#buy-requests" class="nav-link" data-section="buy-requests">
                        <i class="fas fa-shopping-cart"></i>
                        <span>Manage Buy Requests</span>
                    </a>
                </li>
                <?php endif; ?>
            </ul>

            <div class="sidebar-footer">
                <a href="logout.php" class="nav-link text-danger">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a>
            </div>
        </nav>

        <!-- Main Content -->
        <div class="main-content">
            <!-- Top Navbar -->
            <nav class="navbar navbar-expand-lg navbar-light fixed-top">
                <div class="container-fluid">
                    <!-- Sidebar Toggle Button -->
                    <button type="button" id="mobileSidebarCollapse" class="btn btn-link d-lg-none">
                        <i class="fas fa-bars"></i>
                    </button>

                    <!-- Brand -->
                    <a class="navbar-brand ms-lg-3" href="../index.php">
                        <i class="fas fa-home"></i>
                        <span>PropFind</span>
                    </a>

                    <!-- Main Navigation -->
                    <div class="collapse navbar-collapse" id="navbarNav">
                        <ul class="navbar-nav mx-auto">
                            <li class="nav-item">
                                <a class="nav-link" href="../index.php">
                                    <i class="fas fa-home d-lg-none me-2"></i>Home
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="../listings.php">
                                    <i class="fas fa-list d-lg-none me-2"></i>All Listings
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="../upload-property.php">
                                    <i class="fas fa-upload d-lg-none me-2"></i>Upload Property
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link active" href="dashboard.php">
                                    <i class="fas fa-tachometer-alt d-lg-none me-2"></i>Dashboard
                                </a>
                            </li>

                            <!-- More Options Dropdown -->
                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle" href="#" id="moreDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="fas fa-ellipsis-h d-lg-none me-2"></i>More
                                </a>
                                <ul class="dropdown-menu" aria-labelledby="moreDropdown">
                                 
                                    <li>
                                        <a class="dropdown-item" href="../compare.php">
                                            <i class="fas fa-exchange-alt me-2"></i>Compare Properties
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="../about.php">
                                            <i class="fas fa-info-circle me-2"></i>About Us
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="../contact.php">
                                            <i class="fas fa-envelope me-2"></i>Contact Us
                                        </a>
                                    </li>
                                    <li>
                                        <hr class="dropdown-divider">
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="../chat.php">
                                            <i class="fas fa-comments me-2"></i>Chat
                                        </a>
                                    </li>
                                    <!-- <li>
                                        <hr class="dropdown-divider">
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="admin.php">
                                            <i class="fas fa-user-shield me-2"></i>Admin Panel
                                        </a>
                                    </li> -->
                                </ul>
                            </li>
                        </ul>

                        <!-- Right Side Items -->
                        <div class="ms-auto d-flex align-items-center">
                            <!-- Notifications Dropdown -->
                            <div class="dropdown notifications-dropdown me-3">
                                <!-- <button class="btn btn-link position-relative" type="button" data-bs-toggle="dropdown">
                                    <i class="fas fa-bell"></i>
                                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                        3
                                    </span>
                                </button> -->
                                <!-- <div class="dropdown-menu dropdown-menu-end">
                                    <h6 class="dropdown-header">Notifications</h6>
                                    <a class="dropdown-item" href="#">
                                        <div class="notification-item">
                                            <i class="fas fa-user-plus text-primary"></i>
                                            <div class="notification-content">
                                                <p class="mb-1">New user registered</p>
                                                <small class="text-muted">5 minutes ago</small>
                                            </div>
                                        </div>
                                    </a>
                                    <a class="dropdown-item" href="#">
                                        <div class="notification-item">
                                            <i class="fas fa-heart text-danger"></i>
                                            <div class="notification-content">
                                                <p class="mb-1">Your property was liked</p>
                                                <small class="text-muted">1 hour ago</small>
                                            </div>
                                        </div>
                                    </a>
                                    <div class="dropdown-divider"></div>
                                    <a class="dropdown-item text-center" href="#notifications">View all</a>
                                </div> -->
                            </div>

                            <!-- User Dropdown -->
                            <div class="dropdown">
                                <button class="btn btn-link dropdown-toggle d-flex align-items-center" type="button" data-bs-toggle="dropdown">
                                    <!-- <img src="images/default-avatar.png" alt="User Avatar" class="avatar-sm me-2"> -->
                                    <span class="d-none d-md-inline"><?php echo ucfirst($userName) ?></span>
                                </button>
                                <div class="dropdown-menu dropdown-menu-end">
                                    <!-- <a class="dropdown-item" href="#profile">
                                        <i class="fas fa-user me-2"></i>Profile
                                    </a> -->
                                    <!-- <div class="dropdown-divider"></div> -->
                                    <a class="dropdown-item text-danger" href="logout.php">
                                        <i class="fas fa-sign-out-alt me-2"></i>Logout
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </nav>

            <!-- Dashboard Sections -->
            <div class="dashboard-content">

                <!-- Overview Section -->
                <section id="overview" class="dashboard-section active">
                    <div class="container-fluid">
                        <h2 class="section-title">Dashboard Overview</h2>

                        <!-- Stats Cards -->
                        <div class="row g-4 mb-4">
                            <div class="col-md-6 col-xl-4">
                                <div class="stats-card">
                                    <div class="stats-icon bg-primary">
                                        <i class="fas fa-building"></i>
                                    </div>
                                    <div class="stats-info">
                                        <h3><?php echo $postedCount; ?></h3>
                                        <p>Posted Properties</p>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6 col-xl-4">
                                <div class="stats-card">
                                    <div class="stats-icon bg-warning">
                                        <i class="fas fa-bookmark"></i>
                                    </div>
                                    <div class="stats-info">
                                        <h3><?php echo $savedCount; ?></h3>
                                        <p>Saved Properties</p>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6 col-xl-4">
                                <div class="stats-card">
                                    <div class="stats-icon bg-info">
                                        <i class="fas fa-gift"></i>
                                    </div>
                                    <div class="stats-info">
                                        <h3><?php echo $rewardPoints ?: 0; ?></h3>
                                        <p>Reward Points</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6 col-xl-4">
                                <div class="stats-card">
                                    <div class="stats-icon bg-success">
                                        <i class="fas fa-wallet"></i>
                                    </div>
                                    <div class="stats-info">
                                        <h3>PKR <?php echo number_format($revenue ?: 0); ?></h3>
                                        <p>Total Revenue (as Seller)</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6 col-xl-4">
                                <div class="stats-card">
                                    <div class="stats-icon bg-danger">
                                        <i class="fas fa-credit-card"></i>
                                    </div>
                                    <div class="stats-info">
                                        <h3>PKR <?php echo number_format($expenditure ?: 0); ?></h3>
                                        <p>Total Expenditure (as Buyer)</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Recent Activity -->
                        <div class="row">
                            <div class="col-lg-8 mb-4">
                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="card-title mb-0">Recent Properties</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="table-responsive">
                                            <table class="table table-hover">
                                                <thead>
                                                    <tr>
                                                        <th>Property</th>
                                                        <th>Location</th>
                                                        <th>Price</th>
                                                        <th>Actions</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php if (!empty($recentProperties)): ?>
                                                        <?php foreach ($recentProperties as $property): ?>
                                                            <tr>
                                                                <td>
                                                                    <div class="d-flex align-items-center">
                                                                        <img src="../<?php echo htmlspecialchars($property['thumbnail']); ?>" alt="Property" class="property-thumb me-2" width="50" height="50">
                                                                        <span><?php echo htmlspecialchars($property['title']); ?></span>
                                                                    </div>
                                                                </td>
                                                                <td><?php echo htmlspecialchars($property['location']); ?></td>
                                                                <td>PKR <?php echo number_format($property['price']); ?></td>
                                                                <td>
                                                                <button 
        class="btn btn-sm btn-outline-danger delete-btn" 
        data-property-id="<?php echo $property['id']; ?>"
    >
        <i class="fas fa-trash"></i>
    </button>


                                                                </td>

                                                            </tr>
                                                        <?php endforeach; ?>
                                                    <?php else: ?>
                                                        <tr>
                                                            <td colspan="4" class="text-center">No recent properties found.</td>
                                                        </tr>
                                                    <?php endif; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Notifications Placeholder -->
                            <div class="col-lg-4">
                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="card-title mb-0">Recent Notifications</h5>
                                    </div>
                                    <div class="card-body p-0">
                                        <div class="notification-list">
                                            <a href="#" class="notification-item">
                                                <div class="notification-icon bg-primary">
                                                    <i class="fas fa-user-plus"></i>
                                                </div>
                                                <div class="notification-content">
                                                    <p class="mb-1">New user registered</p>
                                                    <small class="text-muted">5 minutes ago</small>
                                                </div>
                                            </a>
                                            <a href="#" class="notification-item">
                                                <div class="notification-icon bg-danger">
                                                    <i class="fas fa-heart"></i>
                                                </div>
                                                <div class="notification-content">
                                                    <p class="mb-1">Your property was liked</p>
                                                    <small class="text-muted">1 hour ago</small>
                                                </div>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- Transaction History Table -->
                        <div class="card mt-4">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Transaction History</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-bordered">
                                        <thead>
                                            <tr>
                                                <th>ID</th>
                                                <th>Property</th>
                                                <th>Buyer</th>
                                                <th>Seller</th>
                                                <th>Amount</th>
                                                <th>Date</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (!empty($transactions)): ?>
                                                <?php foreach ($transactions as $t): ?>
                                                    <tr>
                                                        <td><?php echo $t['id']; ?></td>
                                                        <td><?php echo htmlspecialchars($t['property_title']); ?></td>
                                                        <td><?php echo htmlspecialchars($t['buyer_name']); ?></td>
                                                        <td><?php echo htmlspecialchars($t['seller_name']); ?></td>
                                                        <td>PKR <?php echo number_format($t['amount']); ?></td>
                                                        <td><?php echo $t['created_at']; ?></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <tr><td colspan="6" class="text-center">No transactions found.</td></tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>


                <!-- Profile Section -->
                <section id="profile" class="dashboard-section">
                    <div class="container-fluid">
                        <h2 class="section-title">Profile Settings</h2>
                        <form id="profileForm">
                            <div class="row">
                                <div class="col-lg-4 mb-4">
                                    <div class="card">
                                        <div class="card-body text-center">

                                            <div class="profile-avatar-wrapper mb-3">
                                                <img src="<?php echo $picture ? '../' . $picture : '../images/user.png' ?>" alt="Profile Avatar" class="profile-avatar" title="Upload your profile picture">
                                                <input type="file" name="picture" accept="image/*" class="form-control mb-3" id="profilePictureInput" style="display: none;">
                                                <label for="profilePictureInput" class="btn btn-sm btn-primary avatar-upload" id="uploadPictureBtn" style="cursor: pointer;">
                                                    <i class="fas fa-camera"></i>
                                                </label>
                                            </div>
                                            <h5 class="mt-5" id="profileName">Name not set</h5>
                                            <p class="text-muted" id="profileRole">Role not set</p>

                                            <div class="profile-stats">
                                                <div class="row g-0">
                                                    <div class="col">
                                                        <h6>15</h6>
                                                        <p>Properties</p>
                                                    </div>
                                                    <div class="col">
                                                        <h6>2.5K</h6>
                                                        <p>Views</p>
                                                    </div>
                                                    <div class="col">
                                                        <h6>250</h6>
                                                        <p>Points</p>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-lg-8">
                                    <div class="card">
                                        <div class="card-body">

                                            <div class="row mb-3">
                                                <div class="col-md-6">
                                                    <label class="form-label">Full Name</label>
                                                    <input type="text" name="full_name" class="form-control" value="">
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label">Email</label>
                                                    <input type="email" name="email" class="form-control" value="" readonly>
                                                </div>
                                            </div>
                                            <div class="row mb-3">
                                                <div class="col-md-6">
                                                    <label class="form-label">Phone</label>
                                                    <input type="tel" name="phone" class="form-control" value="">
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label">Location</label>
                                                    <input type="text" name="location" class="form-control" value="">
                                                </div>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Bio</label>
                                                <textarea name="bio" class="form-control" rows="4"></textarea>
                                            </div>
                                            <button type="submit" class="btn btn-primary">Save Changes</button>
                        </form>
                    </div>
            </div>
        </div>
    </div>
    </div>
    </section>

    <!-- Account Settings Section -->
    <section id="account-settings" class="dashboard-section">
        <div class="container-fluid">
            <h2 class="section-title">Account Settings</h2>
            <div class="row justify-content-center">
                <div class="col-lg-6">
                    <div class="card">
                        <div class="card-header bg-gradient-primary text-white">
                            <div class="d-flex align-items-center">
                                <div class="me-3">
                                    <i class="fas fa-key fa-2x"></i>
                                </div>
                                <div>
                                    <h4 class="card-title mb-0 fw-bold">Change Password</h4>
                                    <small class="opacity-75">Update your account security</small>
                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                            <p class="text-muted mb-4">Update your account password to keep your account secure.</p>
                            <form id="changePasswordForm">
                                <div class="mb-3">
                                    <label for="currentPassword" class="form-label">Current Password</label>
                                    <div class="input-group">
                                        <input type="password" name="current_password" id="currentPassword" class="form-control" required>
                                        <button class="btn btn-outline-secondary" type="button" id="toggleCurrentPassword">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label for="newPassword" class="form-label">New Password</label>
                                    <div class="input-group">
                                        <input type="password" name="new_password" id="newPassword" class="form-control" required>
                                        <button class="btn btn-outline-secondary" type="button" id="toggleNewPassword">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label for="confirmPassword" class="form-label">Confirm New Password</label>
                                    <div class="input-group">
                                        <input type="password" name="confirm_password" id="confirmPassword" class="form-control" required>
                                        <button class="btn btn-outline-secondary" type="button" id="toggleConfirmPassword">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                </div>
                                <div class="text-center">
                                    <button type="submit" class="btn btn-primary btn-lg">
                                        <i class="fas fa-key me-2"></i>Change Password
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Properties Section -->
    <section id="properties" class="dashboard-section">
        <div class="container-fluid">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="section-title mb-0">My Properties</h2>
                <button class="btn btn-primary" onclick="window.location.href='../upload-property.php'">
                    <i class="fas fa-plus me-2"></i>Add New Property
                </button>
            </div>

            <div class="row g-4" id="propertyList"></div>

        </div>
    </section>

    <!-- Saved Properties Section -->
    <section id="saved" class="dashboard-section">
        <div class="container-fluid">
            <h2 class="section-title mb-4">Saved Properties</h2>

            <div class="row g-4">
                <!-- Saved properties will be injected here via JS -->
            </div>
        </div>
    </section>


    <!-- Notifications Section -->
    <section id="notifications" class="dashboard-section">
        <div class="container-fluid">
            <h2 class="section-title mb-4">Notifications</h2>

            <div class="card">
                <div class="card-body p-0">
                    <div class="notification-list">
                        <div class="notification-item">
                            <div class="notification-icon bg-primary">
                                <i class="fas fa-user-plus"></i>
                            </div>
                            <div class="notification-content">
                                <h6 class="mb-1">New User Registration</h6>
                                <p class="mb-1">A new user has registered through your referral link</p>
                                <small class="text-muted">5 minutes ago</small>
                            </div>
                            <div class="notification-action">
                                <button class="btn btn-sm btn-light">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        </div>
                        <!-- Add more notification items -->
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Rewards Section -->
    <section id="rewards" class="dashboard-section">
        <div class="container-fluid">
            <h2 class="section-title mb-4">Referral Rewards</h2>

            <div class="row g-4">
                <div class="col-md-6 col-xl-4">
                    <div class="card">
                        <div class="card-body">
                            <div class="text-center mb-4">
                                <div class="rewards-points">
                                    <h1>0</h1>
                                    <p>Total Points</p>
                                </div>
                            </div>
                            <div class="rewards-progress mb-4">
                                <h6>Next Reward: 500 points</h6>
                                <div class="progress">
                                    <div class="progress-bar" role="progressbar" style="width: 0%"></div>
                                </div>
                                <small class="text-muted">0 more points needed</small>
                            </div>
                            <button id="copyReferralBtn" class="btn btn-primary w-100">
                                <i class="fas fa-share-alt me-2"></i>Copy Referral Code
                            </button>
                        </div>
                    </div>
                </div>


                <div class="col-md-6 col-xl-8">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Rewards History</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>Activity</th>
                                            <th>Points</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <!-- Filled dynamically by JS -->
                                    </tbody>
                                </table>
                            </div>

                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <?php if (isset($userRole) && strtolower($userRole) === 'admin'): ?>
<!-- Buy Requests Section (Admin Only) -->
<section id="buy-requests" class="dashboard-section">
    <div class="container-fluid">
        <h2 class="section-title mb-4">Manage Buy Requests</h2>
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered" id="buyRequestsTable">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Property</th>
                                <th>User</th>
                                <th>Email</th>
                                <th>Status</th>
                                <th>Requested At</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php
                        $query = "SELECT r.*, p.title AS property_title, u.name AS user_name FROM property_buy_requests r
                                  LEFT JOIN properties p ON r.property_id = p.id
                                  LEFT JOIN users u ON r.user_id = u.id
                                  ORDER BY r.created_at DESC";
                        $result = $conn->query($query);
                        while($row = $result->fetch_assoc()): ?>
                            <tr id="req-<?php echo $row['id']; ?>">
                                <td><?php echo $row['id']; ?></td>
                                <td><?php echo htmlspecialchars($row['property_title']); ?></td>
                                <td><?php echo htmlspecialchars($row['user_name'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($row['email']); ?></td>
                                <td class="status-<?php echo $row['status']; ?>"><?php echo ucfirst($row['status']); ?></td>
                                <td><?php echo $row['created_at']; ?></td>
                                <td>
                                    <?php if($row['status'] === 'pending'): ?>
                                        <button class="btn btn-success btn-sm me-1" onclick="handleBuyRequest(<?php echo $row['id']; ?>, 'approved')">Approve</button>
                                        <button class="btn btn-danger btn-sm" onclick="handleBuyRequest(<?php echo $row['id']; ?>, 'rejected')">Reject</button>
                                    <?php else: ?>
                                        <span>-</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</section>
<script>
function showLoading(message) {
    $('#loadingText').text(message || 'Processing request...');
    $('#loadingOverlay').css('display', 'flex');
}

function hideLoading() {
    $('#loadingOverlay').hide();
}

function handleBuyRequest(id, action) {
    if (!confirm('Are you sure you want to ' + action + ' this request?')) return;
    
    // Show loading and disable buttons
    showLoading(action.charAt(0).toUpperCase() + action.slice(1) + ' action request...');
    $('.btn').prop('disabled', true);
    $('#req-' + id).addClass('row-loading');
    
    $.ajax({
        url: '../backend/handle-buy-request.php',
        method: 'POST',
        data: { id: id, action: action },
        dataType: 'json',
        success: function(res) {
            hideLoading();
            $('.btn').prop('disabled', false);
            $('#req-' + id).removeClass('row-loading');
            
            if(res.success) {
                $('#req-' + id + ' td.status-pending').text(action.charAt(0).toUpperCase() + action.slice(1)).removeClass('status-pending').addClass('status-' + action);
                $('#req-' + id + ' td:last').html('<span>-</span>');
                alert(res.message || 'Request ' + action + ' successfully.');
                
                // If approved, refresh the page to show updated statuses of other requests
                if (action === 'approved') {
                    showLoading('Refreshing page to show updated statuses...');
                    setTimeout(function() {
                        location.reload();
                    }, 1500);
                }
            } else {
                alert(res.message || 'Failed to update request.');
            }
        },
        error: function() { 
            hideLoading();
            $('.btn').prop('disabled', false);
            $('#req-' + id).removeClass('row-loading');
            alert('Error processing request.'); 
        }
    });
}
</script>
<?php endif; ?>
    <!-- Transactions Section (All Users) -->
    <section id="transactions" class="dashboard-section">
        <div class="container-fluid">
            <h2 class="section-title mb-4">My Transactions & Stats</h2>
            <div class="row g-4 mb-4">
                <div class="col-md-6 col-xl-4">
                    <div class="stats-card">
                        <div class="stats-icon bg-success">
                            <i class="fas fa-wallet"></i>
                        </div>
                        <div class="stats-info">
                            <h3>PKR <?php echo number_format($revenue ?: 0); ?></h3>
                            <p>Total Revenue (as Seller)</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-xl-4">
                    <div class="stats-card">
                        <div class="stats-icon bg-danger">
                            <i class="fas fa-credit-card"></i>
                        </div>
                        <div class="stats-info">
                            <h3>PKR <?php echo number_format($expenditure ?: 0); ?></h3>
                            <p>Total Expenditure (as Buyer)</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card mt-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Transaction History</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Property</th>
                                    <th>Buyer</th>
                                    <th>Seller</th>
                                    <th>Amount</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($transactions)): ?>
                                    <?php foreach ($transactions as $t): ?>
                                        <tr>
                                            <td><?php echo $t['id']; ?></td>
                                            <td><?php echo htmlspecialchars($t['property_title']); ?></td>
                                            <td><?php echo htmlspecialchars($t['buyer_name']); ?></td>
                                            <td><?php echo htmlspecialchars($t['seller_name']); ?></td>
                                            <td>PKR <?php echo number_format($t['amount']); ?></td>
                                            <td><?php echo $t['created_at']; ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr><td colspan="6" class="text-center">No transactions found.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </section>
    </div>
    </div>
    </div>

    <!-- Bootstrap 5 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Custom JS -->
    <script src="../js/dashboard.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            fetch('../backend/fetch-referral-dashboard.php')
                .then(response => response.json())
                .then(data => {
                    console.log('Fetched data:', data);

                    if (data.status === 'success') {
                        document.querySelector('.rewards-points h1').textContent = data.total_points;
                        document.querySelector('.progress-bar').style.width = `${data.progress_percent}%`;
                        document.querySelector('.rewards-progress small').textContent = `${data.points_to_next} more points needed`;

                        const historyBody = document.querySelector('#rewards .table tbody');
                        historyBody.innerHTML = '';

                        if (data.rewards_history.length > 0) {
                            data.rewards_history.forEach(item => {
                                historyBody.innerHTML += `
                        <tr>
                            <td>${item.date}</td>
                            <td>${item.activity}</td>
                            <td>${item.points}</td>
                            <td><span class="badge bg-success">${item.status}</span></td>
                        </tr>
                    `;
                            });
                        } else {
                            historyBody.innerHTML = `
                    <tr>
                        <td colspan="4" class="text-center">No rewards history yet.</td>
                    </tr>
                `;
                        }

                        // Set referral code to button data attribute and text
                        const referralBtn = document.getElementById('copyReferralBtn');
                        if (referralBtn && data.referral_code) {
                            referralBtn.setAttribute('data-referral-code', data.referral_code);
                            referralBtn.innerHTML = `<i class="fas fa-share-alt me-2"></i>Copy Referral Code`;

                            referralBtn.addEventListener('click', () => {
                                navigator.clipboard.writeText(data.referral_code).then(() => {
                                    alert('Referral code copied: ' + data.referral_code);
                                });
                            });
                        }
                    } else {
                        console.warn('Status not success:', data);
                    }
                })
                .catch(error => console.error('Error fetching rewards data:', error));
        });
    </script>
    <!-- Add these styles to fix the layout -->

    <!-- Edit Property Modal -->
    <div class="modal fade" id="editPropertyModal" tabindex="-1" aria-labelledby="editPropertyModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <form id="editPropertyForm">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="editPropertyModalLabel">Edit Property</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="property_id" id="editPropertyId">

                        <div class="mb-3">
                            <label>Title</label>
                            <input type="text" name="title" id="editTitle" class="form-control" required>
                        </div>

                        <div class="mb-3">
                            <label>Price</label>
                            <input type="number" name="price" id="editPrice" class="form-control" required>
                        </div>

                        <div class="mb-3">
                            <label>Location</label>
                            <input type="text" name="location" id="editLocation" class="form-control" required>
                        </div>

                        <div class="mb-3">
                            <label>Area</label>
                            <input type="text" name="area" id="editArea" class="form-control" required>
                        </div>

                        <div class="mb-3">
                            <label>Unit</label>
                            <select name="unit" id="editUnit" class="form-select" required>
                                <option value="">Select Unit</option>
                                <option value="marla">Marla</option>
                                <option value="square feet">Square Feet</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label>Type</label>
                            <select name="type" id="editType" class="form-select" required>
                                <option value="">Select Property Type</option>
                                <option value="House">House</option>
                                <option value="Flat">Flat</option>
                                <option value="Plot">Plot</option>
                                <option value="Commercial">Commercial</option>
                                <option value="Farmhouse">Farmhouse</option>
                            </select>
                        </div>

                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </div>
                </div>
            </form>
        </div>
    </div>




    <script>
        $(document).on('click', '.edit-btn', function() {
            const property = $(this).data('property');

            console.log('Editing Property:', property);

            $('#editPropertyId').val(property.id);
            $('#editTitle').val(property.title);
            $('#editPrice').val(property.price);
            $('#editLocation').val(property.location);
            $('#editArea').val(property.area);
            $('#editUnit').val(property.unit);
            $('#editType').val(property.type);
            $('#editUnit').val(property.unit);
            $('#editType').val(property.type);


            $('#editPropertyModal').modal('show');
        });
    </script>

    <script>
        $(document).ready(function() {
            fetchSavedProperties();

            // ----------------- Fetch Profile -------------------
            $.ajax({
                url: '../backend/fetch-dashboard-details.php',
                type: 'GET',
                dataType: 'json',
                success: function(response) {
                    console.log('Fetched Dashboard Details:', response);

                    if (response.success) {
                        // Profile Picture
                        if (response.data.picture) {
                            $('.profile-avatar').attr('src', '../' + response.data.picture);
                        } else {
                            $('.profile-avatar').attr('src', '../images/user.png')
                                .attr('title', 'Upload your profile picture');
                        }

                        // Profile Info
                        $('.card-body h5').text(response.data.name || 'N/A');
                        $('.card-body p.text-muted').text(response.data.role || 'Role not set');

                        // Form Fields
                        $('#profileForm input[name="full_name"]').val(response.data.name || '');
                        $('#profileForm input[name="email"]').val(response.data.email || '');
                        $('#profileForm input[name="phone"]').val(response.data.phone || '')
                            .attr('placeholder', response.data.phone ? '' : 'Add your phone number to complete your profile.');
                        $('#profileForm input[name="location"]').val(response.data.location || '')
                            .attr('placeholder', response.data.location ? '' : 'Specify your city or address.');
                        $('#profileForm textarea[name="bio"]').val(response.data.bio || '')
                            .attr('placeholder', response.data.bio ? '' : 'Write a short bio about yourself or your profession.');

                        $('#profileName').text(response.data.name || 'N/A');
                        $('#profileRole').text(response.data.role || 'Role not set');
                    }
                },
                error: function() {
                    iziToast.error({
                        title: 'Error',
                        message: 'Failed to load profile data.',
                        position: 'topRight'
                    });
                }
            });

            // ----------------- Update Profile -------------------
            $('#profileForm').submit(function(e) {
                e.preventDefault();

                var formData = new FormData(this);

                $.ajax({
                    url: '../backend/update-user-details.php',
                    type: 'POST',
                    data: formData,
                    contentType: false,
                    processData: false,
                    dataType: 'json',
                    success: function(response) {
                        console.log('Profile Update Response:', response);

                        if (response.success) {
                            console.log('Profile update successful');
                            if (response.picture_path) {
                                console.log('New picture path:', response.picture_path);
                            }
                            iziToast.success({
                                title: 'Success',
                                message: 'Profile updated successfully!',
                                position: 'topRight'
                            });
                            
                            // Update profile picture if a new one was uploaded
                            if (response.picture_path) {
                                $('.profile-avatar').attr('src', '../' + response.picture_path);
                            }
                            
                            // Refresh profile data to show updated picture
                            $.ajax({
                                url: '../backend/fetch-dashboard-details.php',
                                type: 'GET',
                                dataType: 'json',
                                success: function(profileResponse) {
                                    if (profileResponse.success && profileResponse.data.picture) {
                                        $('.profile-avatar').attr('src', '../' + profileResponse.data.picture);
                                    }
                                }
                            });
                            
                            setTimeout(() => {
                                window.location.href = 'dashboard.php#profile';
                            }, 1000);
                        } else {
                            iziToast.error({
                                title: 'Error',
                                message: 'Failed to update profile.',
                                position: 'topRight'
                            });
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Profile update error:', xhr.responseText);
                        iziToast.error({
                            title: 'Error',
                            message: 'An error occurred while updating the profile: ' + error,
                            position: 'topRight'
                        });
                    }
                });
            });

            // ----------------- Profile Picture Handling -------------------
            // Handle file input change for profile picture preview
            let isProcessingFile = false;
            $('#profilePictureInput').off('change').on('change', function() {
                console.log('File input change event triggered');
                if (isProcessingFile) {
                    console.log('Already processing file, skipping');
                    return;
                }
                isProcessingFile = true;
                
                const file = this.files[0];
                if (file) {
                    // Validate file type
                    const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
                    if (!allowedTypes.includes(file.type)) {
                        iziToast.error({
                            title: 'Error',
                            message: 'Please select a valid image file (JPG, PNG, GIF)',
                            position: 'topRight'
                        });
                        this.value = '';
                        isProcessingFile = false;
                        return;
                    }

                    // Validate file size (max 5MB)
                    if (file.size > 5 * 1024 * 1024) {
                        iziToast.error({
                            title: 'Error',
                            message: 'File size must be less than 5MB',
                            position: 'topRight'
                        });
                        this.value = '';
                        isProcessingFile = false;
                        return;
                    }

                    // Preview the image
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        $('.profile-avatar').attr('src', e.target.result);
                        isProcessingFile = false;
                    };
                    reader.readAsDataURL(file);
                } else {
                    isProcessingFile = false;
                }
            });

            // Handle upload button click - using label approach (no JavaScript needed)
            // The label automatically triggers the file input when clicked

            // ----------------- Fetch User Properties -------------------
            fetchUserProperties();

            function fetchUserProperties() {
                $.ajax({
                    url: '../backend/fetch-user-properties.php',
                    type: 'GET',
                    dataType: 'json',
                    success: function(response) {
                        console.log('Fetched User Properties:', response);

                        if (response.success) {
                            renderProperties(response.data);
                        } else {
                            $('#propertyList').html('<p>No properties found.</p>');
                        }
                    },
                    error: function() {
                        $('#propertyList').html('<p>Failed to fetch properties.</p>');
                    }
                });
            }

            function getFirstImage(property) {
                try {
                    let images = property.images;

                    // If images is not an array but images_json exists, decode it
                    if (!Array.isArray(images) && property.images_json) {
                        images = JSON.parse(property.images_json);
                    }

                    return Array.isArray(images) && images.length ?
                        `../${images[0]}` :
                        'images/property-placeholder.jpg';
                } catch {
                    return 'images/property-placeholder.jpg';
                }
            }

            function renderProperties(properties) {

                let html = '';

                properties.forEach(property => {
                    html += `
            <div class="col-md-6 col-xl-4">
                <div class="property-card card h-100">
                    <div class="property-image-wrapper">
                        <img src="${getFirstImage(property)}" class="card-img-top" alt="Property">
                        <div class="property-badges">
                            <span class="badge bg-success">Active</span>
                            <span class="badge bg-primary">${property.type}</span>
                        </div>
                        <div class="property-actions">
                            <button class="btn btn-light btn-sm edit-btn" title="Edit" data-id="${property.id}" data-property='${JSON.stringify(property)}'>
    <i class="fas fa-edit"></i>
</button>

                          <button class="btn btn-light btn-sm delete-btn" title="Delete" data-property-id="${property.id}">
    <i class="fas fa-trash"></i>
</button>

                        </div>
                    </div>
                    <div class="card-body">
                        <h5 class="card-title">${property.title}</h5>
                        <p class="card-text text-primary fw-bold">PKR ${property.price ? Number(property.price).toLocaleString() : 'N/A'}</p>
                        <p class="card-text"><i class="fas fa-map-marker-alt"></i> ${property.location}</p>
                        <div class="property-features">
                            <span><i class="fas fa-ruler-combined"></i> ${property.area} ${property.unit}</span>
                            <span><i class="fas fa-home"></i> ${property.type}</span>
                        </div>
                    </div>
                    <div class="card-footer bg-white">
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="property-stats">
                                <span title="Views"><i class="fas fa-eye"></i> 0</span>
                                <span title="Likes"><i class="fas fa-heart"></i> 0</span>
                            </div>
                            <button class="btn btn-primary btn-sm" onclick="window.location.href='../view-property-detail.php?id=${property.id}'">
    View Details
</button>

                        </div>
                    </div>
                </div>
            </div>
        `;
                });

                $('#propertyList').html(html);
            }

            // edit property
            $('#editPropertyForm').submit(function(e) {
                e.preventDefault();

                $.ajax({
                    url: '../backend/edit-property-details.php',
                    type: 'POST',
                    data: $(this).serialize(),
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            iziToast.success({
                                title: 'Success',
                                message: 'Property updated successfully!',
                                position: 'topRight'
                            });

                            $('#editPropertyModal').modal('hide');
                            fetchUserProperties(); // Refresh the properties list
                        } else {
                            iziToast.error({
                                title: 'Error',
                                message: response.message || 'Failed to update property.',
                                position: 'topRight'
                            });
                        }
                    },
                    error: function() {
                        iziToast.error({
                            title: 'Error',
                            message: 'An error occurred while updating the property.',
                            position: 'topRight'
                        });
                    }
                });
            });


            // fetch saved properties
            async function fetchSavedProperties() {
                try {
                    const res = await fetch('../backend/fetch-save-properties.php');
                    console.log('Raw fetch response:', res);

                    if (!res.ok) {
                        throw new Error(`HTTP error! Status: ${res.status}`);
                    }

                    const data = await res.json();
                    console.log('fetch-save-properties response:', data);

                    const savedSection = document.querySelector('#saved .row');

                    if (data.status === 'success' && Array.isArray(data.properties) && data.properties.length > 0) {
                        const cards = data.properties.map(property => {
                            return `
                    <div class="col-md-6 col-xl-4">
                        <div class="property-card card h-100">
                            <div class="property-image-wrapper position-relative">
                                <img src="${getFirstImage(property)}" class="card-img-top" alt="${property.title}">
                                <button class="btn btn-danger btn-sm bookmark-btn position-absolute top-0 end-0 m-2" data-property-id="${property.id}">
                                    <i class="fas fa-bookmark"></i>
                                </button>
                            </div>
                            <div class="card-body">
                                <h5 class="card-title">${property.title}</h5>
                                <p class="card-text text-primary fw-bold">PKR ${Number(property.price).toLocaleString()}</p>
                                <p class="card-text"><i class="fas fa-map-marker-alt"></i> ${property.location}</p>
                                <div class="property-features">
                                    ${property.bedrooms ? `<span><i class="fas fa-bed"></i> ${property.bedrooms} Beds</span>` : ''}
                                    ${property.bathrooms ? `<span><i class="fas fa-bath"></i> ${property.bathrooms} Baths</span>` : ''}
                                    ${property.area ? `<span><i class="fas fa-ruler-combined"></i> ${property.area} sq ft</span>` : ''}
                                </div>
                            </div>
                            <div class="card-footer bg-white">
    <button class="btn btn-primary w-100" onclick="window.location.href='../view-property-detail.php?id=${property.id}'">
        View Property Details
    </button>
</div>

                        </div>
                    </div>
                `;
                        }).join('');

                        savedSection.innerHTML = cards;

                        // Attach click listener to new bookmark buttons
                        document.querySelectorAll('.bookmark-btn').forEach(btn => {
                            btn.addEventListener('click', async function() {
                                const propertyId = this.getAttribute('data-property-id');
                                await toggleSaveProperty(propertyId, this);
                            });
                        });

                    } else {
                        console.log('No saved properties returned:', data);
                        savedSection.innerHTML = `
                <div class="col-12">
                    <p class="text-muted text-center">You have no saved properties yet.</p>
                </div>
            `;
                    }

                } catch (err) {
                    console.error('fetchSavedProperties error:', err);
                    iziToast.error({
                        title: 'Error',
                        message: 'Failed to load saved properties.',
                        position: 'topRight'
                    });
                }
            }
            async function toggleSaveProperty(propertyId, button) {
                try {
                    const response = await fetch('../backend/save-property.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            property_id: propertyId,
                            action: 'remove'
                        })
                    });

                    const data = await response.json();

                    if (data.status === 'success') {
                        iziToast.info({
                            title: 'Removed',
                            message: 'Property removed from saved.',
                            position: 'topRight'
                        });

                        // Remove the card from UI
                        const card = button.closest('.col-md-6, .col-xl-4');
                        card.remove();

                        // Check if no saved properties remain
                        const savedSection = document.querySelector('#saved .row');
                        if (savedSection.querySelectorAll('.property-card').length === 0) {
                            savedSection.innerHTML = `
                    <div class="col-12">
                        <p class="text-muted text-center">You have no saved properties yet.</p>
                    </div>
                `;
                        }

                    } else {
                        iziToast.error({
                            title: 'Error',
                            message: data.message || 'Failed to update saved property.',
                            position: 'topRight'
                        });
                    }
                } catch (error) {
                    console.error('toggleSaveProperty error:', error);
                    iziToast.error({
                        title: 'Error',
                        message: 'An error occurred while saving/removing the property.',
                        position: 'topRight'
                    });
                }
            }





            // delete property
            $(document).on('click', '.delete-btn', function() {
                const propertyId = $(this).data('property-id');
                console.log('Delete button clicked for property ID:', propertyId);

                if (confirm('Are you sure you want to delete this property?')) {
                    console.log('User confirmed deletion, sending request...');
                    
                    $.ajax({
                        url: '../backend/delete-property.php',
                        type: 'POST',
                        data: {
                            property_id: propertyId
                        },
                        dataType: 'json',
                        beforeSend: function() {
                            console.log('Sending delete request for property ID:', propertyId);
                        },
                        success: function(response) {
                            console.log('Delete response:', response);
                            if (response.success) {
                                iziToast.success({
                                    title: 'Deleted',
                                    message: 'Property deleted successfully.',
                                    position: 'topRight'
                                });
                                console.log('Property deleted successfully, refreshing list...');
                                fetchUserProperties(); // Refresh properties
                            } else {
                                iziToast.error({
                                    title: 'Error',
                                    message: response.message || 'Failed to delete property.',
                                    position: 'topRight'
                                });
                                console.log('Delete failed:', response.message);
                            }
                        },
                        error: function(xhr, status, error) {
                            console.error('Delete request failed:', xhr.responseText, status, error);
                            iziToast.error({
                                title: 'Error',
                                message: 'An error occurred while deleting the property.',
                                position: 'topRight'
                            });
                        }
                    });
                } else {
                    console.log('User cancelled deletion');
                }
            });

        // Account Settings Functions
        // Change Password Form Handler
        $('#changePasswordForm').submit(function(e) {
            e.preventDefault();
            
            const currentPassword = $('#currentPassword').val();
            const newPassword = $('#newPassword').val();
            const confirmPassword = $('#confirmPassword').val();
            
            // Validation
            if (!currentPassword || !newPassword || !confirmPassword) {
                iziToast.error({
                    title: 'Error',
                    message: 'Please fill in all fields.',
                    position: 'topRight'
                });
                return;
            }
            
            if (newPassword !== confirmPassword) {
                iziToast.error({
                    title: 'Error',
                    message: 'New password and confirm password do not match.',
                    position: 'topRight'
                });
                return;
            }
            
            if (newPassword.length < 6) {
                iziToast.error({
                    title: 'Error',
                    message: 'New password must be at least 6 characters long.',
                    position: 'topRight'
                });
                return;
            }
            
            // Submit form
            $.ajax({
                url: '../backend/change-password.php',
                type: 'POST',
                data: {
                    current_password: currentPassword,
                    new_password: newPassword,
                    confirm_password: confirmPassword
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        iziToast.success({
                            title: 'Success',
                            message: 'Password changed successfully!',
                            position: 'topRight'
                        });
                        $('#changePasswordForm')[0].reset();
                    } else {
                        iziToast.error({
                            title: 'Error',
                            message: response.message || 'Failed to change password.',
                            position: 'topRight'
                        });
                    }
                },
                error: function() {
                    iziToast.error({
                        title: 'Error',
                        message: 'An error occurred while changing password.',
                        position: 'topRight'
                    });
                }
            });
        });

        // Password visibility toggle event listeners
        document.getElementById("toggleCurrentPassword")?.addEventListener("click", function () {
            const input = document.getElementById("currentPassword");
            input.type = input.type === "password" ? "text" : "password";
            this.querySelector("i").classList.toggle("fa-eye");
            this.querySelector("i").classList.toggle("fa-eye-slash");
        });

        document.getElementById("toggleNewPassword")?.addEventListener("click", function () {
            const input = document.getElementById("newPassword");
            input.type = input.type === "password" ? "text" : "password";
            this.querySelector("i").classList.toggle("fa-eye");
            this.querySelector("i").classList.toggle("fa-eye-slash");
        });

        document.getElementById("toggleConfirmPassword")?.addEventListener("click", function () {
            const input = document.getElementById("confirmPassword");
            input.type = input.type === "password" ? "text" : "password";
            this.querySelector("i").classList.toggle("fa-eye");
            this.querySelector("i").classList.toggle("fa-eye-slash");
        });

        });
    </script>




    <!-- iziToast CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/izitoast/dist/css/iziToast.min.css">

    <!-- iziToast JS -->
    <script src="https://cdn.jsdelivr.net/npm/izitoast/dist/js/iziToast.min.js"></script>

</body>

</html>