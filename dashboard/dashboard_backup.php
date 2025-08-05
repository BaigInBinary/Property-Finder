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
    $userPhone = $row['phone'] ?? '';
    $userLocation = $row['location'] ?? '';
    $userBio = $row['bio'] ?? '';
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

    /* Validation styles */
    .form-control.is-invalid {
        border-color: #dc3545;
        box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25);
    }

    .form-control.is-valid {
        border-color: #198754;
        box-shadow: 0 0 0 0.2rem rgba(25, 135, 84, 0.25);
    }

    .invalid-feedback {
        display: block;
        width: 100%;
        margin-top: 0.25rem;
        font-size: 0.875em;
        color: #dc3545;
    }

    .valid-feedback {
        display: block;
        width: 100%;
        margin-top: 0.25rem;
        font-size: 0.875em;
        color: #198754;
    }

    .char-counter {
        font-size: 0.875em;
        margin-top: 0.25rem;
    }

    .char-counter.text-warning {
        color: #ffc107 !important;
    }

    .char-counter.text-danger {
        color: #dc3545 !important;
    }

    .form-text {
        font-size: 0.875em;
        color: #6c757d;
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
    <!-- iziToast CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/izitoast/dist/css/iziToast.min.css">
    <!-- Custom CSS -->
    <link href="../css/styles.css" rel="stylesheet">
    <link href="../css/dashboard.css" rel="stylesheet">
    <!-- iziToast JS -->
    <script src="https://cdn.jsdelivr.net/npm/izitoast/dist/js/iziToast.min.js"></script>
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
                    <h6 class="user-name mb-0" id="profileName"><?php echo htmlspecialchars($userName); ?></h6>
                    <span class="user-role" id="profileRole"><?php echo ucfirst(htmlspecialchars($userRole)); ?></span>
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
                <?php if (!isset($userRole) || strtolower($userRole) !== 'admin'): ?>
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
                <?php endif; ?>
                <?php if (!isset($userRole) || strtolower($userRole) !== 'admin'): ?>
                <li class="nav-item">
                    <a href="#transactions" class="nav-link" data-section="transactions">
                        <i class="fas fa-exchange-alt"></i>
                        <span>Transactions</span>
                    </a>
                </li>
                <?php endif; ?>
                <?php if (isset($userRole) && strtolower($userRole) === 'admin'): ?>
                <li class="nav-item">
                    <a href="#all-properties" class="nav-link" data-section="all-properties">
                        <i class="fas fa-building"></i>
                        <span>All Properties</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#all-users" class="nav-link" data-section="all-users">
                        <i class="fas fa-users"></i>
                        <span>All Users</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#all-transactions" class="nav-link" data-section="all-transactions">
                        <i class="fas fa-credit-card"></i>
                        <span>All Transactions</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#buy-requests" class="nav-link" data-section="buy-requests">
                        <i class="fas fa-shopping-cart"></i>
                        <span>Manage Buy Requests</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#property-approvals" class="nav-link" data-section="property-approvals">
                        <i class="fas fa-check-circle"></i>
                        <span>Property Approvals</span>
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
                            <?php if (isset($userRole) && strtolower($userRole) === 'admin'): ?>
                                <!-- Admin Stats -->
                                <?php
                                // Fetch admin stats
                                $totalProperties = 0;
                                $totalTransactions = 0;
                                $totalUsers = 0;
                                $totalRevenue = 0;
                                
                                // Total Properties
                                $stmt = $conn->prepare("SELECT COUNT(*) FROM properties");
                                $stmt->execute();
                                $stmt->bind_result($totalProperties);
                                $stmt->fetch();
                                $stmt->close();
                                
                                // Total Transactions
                                $stmt = $conn->prepare("SELECT COUNT(*) FROM transactions");
                                $stmt->execute();
                                $stmt->bind_result($totalTransactions);
                                $stmt->fetch();
                                $stmt->close();
                                
                                // Total Users
                                $stmt = $conn->prepare("SELECT COUNT(*) FROM users WHERE role != 'admin'");
                                $stmt->execute();
                                $stmt->bind_result($totalUsers);
                                $stmt->fetch();
                                $stmt->close();
                                
                                // Total Revenue
                                $stmt = $conn->prepare("SELECT SUM(amount) FROM transactions");
                                $stmt->execute();
                                $stmt->bind_result($totalRevenue);
                                $stmt->fetch();
                                $stmt->close();
                                ?>
                                
                                <div class="col-md-6 col-xl-3">
                                    <div class="stats-card">
                                        <div class="stats-icon bg-primary">
                                            <i class="fas fa-building"></i>
                                        </div>
                                        <div class="stats-info">
                                            <h3><?php echo $totalProperties; ?></h3>
                                            <p>Total Properties</p>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-6 col-xl-3">
                                    <div class="stats-card">
                                        <div class="stats-icon bg-success">
                                            <i class="fas fa-exchange-alt"></i>
                                        </div>
                                        <div class="stats-info">
                                            <h3><?php echo $totalTransactions; ?></h3>
                                            <p>Total Transactions</p>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-6 col-xl-3">
                                    <div class="stats-card">
                                        <div class="stats-icon bg-info">
                                            <i class="fas fa-users"></i>
                                        </div>
                                        <div class="stats-info">
                                            <h3><?php echo $totalUsers; ?></h3>
                                            <p>Total Users</p>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-6 col-xl-3">
                                    <div class="stats-card">
                                        <div class="stats-icon bg-warning">
                                            <i class="fas fa-wallet"></i>
                                        </div>
                                        <div class="stats-info">
                                            <h3>PKR <?php echo number_format($totalRevenue ?: 0); ?></h3>
                                            <p>Total Revenue</p>
                                        </div>
                                    </div>
                                </div>
                            <?php else: ?>
                                <!-- Regular User Stats -->
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
                            <?php endif; ?>
                        </div>

                        <!-- Recent Properties Table -->
                        <div class="row">
                            <div class="col-12">
                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="card-title mb-0">
                                            <?php if (isset($userRole) && strtolower($userRole) === 'admin'): ?>
                                                Recent Properties
                                            <?php else: ?>
                                                Recent Properties 
                                            <?php endif; ?>
                                        </h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="table-responsive">
                                            <table class="table table-hover">
                                                <thead>
                                                    <tr>
                                                        <th>ID</th>
                                                        <th>Prop. Name</th>
                                                        <th>Owner</th>
                                                        <th>City</th>
                                                        <th>Type</th>
                                                        <th>Price</th>
                                                        <th>Status</th>
                                                        <th>Created</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php if (isset($userRole) && strtolower($userRole) === 'admin'): ?>
                                                        <!-- Admin Recent Properties -->
                                                        <?php
                                                        $adminRecentQuery = "SELECT p.*, u.name as owner_name, u.email as owner_email 
                                                                           FROM properties p 
                                                                           LEFT JOIN users u ON p.user_id = u.id 
                                                                           ORDER BY p.created_at DESC 
                                                                           LIMIT 5";
                                                        $adminRecentResult = $conn->query($adminRecentQuery);
                                                        while ($property = $adminRecentResult->fetch_assoc()):
                                                        ?>
                                                            <tr>
                                                                <td><?php echo htmlspecialchars($property['id']); ?></td>
                                                                <td><?php echo htmlspecialchars($property['title']); ?></td>
                                                                <td>
                                                                    <div>
                                                                        <strong><?php echo htmlspecialchars($property['owner_name']); ?></strong>
                                                                        <br><small class="text-muted"><?php echo htmlspecialchars($property['owner_email']); ?></small>
                                                                    </div>
                                                                </td>
                                                                <td><?php echo htmlspecialchars($property['city']); ?></td>
                                                                <td><?php echo htmlspecialchars($property['type']); ?></td>
                                                                <td><strong>PKR <?php echo number_format($property['price']); ?></strong></td>
                                                                <td>
                                                                    <span class="badge bg-<?php echo $property['status'] === 'active' ? 'success' : ($property['status'] === 'pending' ? 'warning' : 'secondary'); ?>">
                                                                        <?php echo ucfirst($property['status']); ?>
                                                                    </span>
                                                                </td>
                                                                <td><?php echo date('M d, Y', strtotime($property['created_at'])); ?></td>
                                                            </tr>
                                                        <?php endwhile; ?>
                                                    <?php else: ?>
                                                        <!-- Regular User Recent Properties -->
                                                        <?php
                                                        $userRecentQuery = "SELECT p.*, u.name as owner_name, u.email as owner_email 
                                                                          FROM properties p 
                                                                          LEFT JOIN users u ON p.user_id = u.id 
                                                                          WHERE p.user_id = ? 
                                                                          ORDER BY p.created_at DESC 
                                                                          LIMIT 5";
                                                        $stmt = $conn->prepare($userRecentQuery);
                                                        $stmt->bind_param("i", $userId);
                                                        $stmt->execute();
                                                        $userRecentResult = $stmt->get_result();
                                                        while ($property = $userRecentResult->fetch_assoc()):
                                                        ?>
                                                            <tr>
                                                                <td><?php echo htmlspecialchars($property['id']); ?></td>
                                                                <td><?php echo htmlspecialchars($property['title']); ?></td>
                                                                <td>
                                                                    <div>
                                                                        <strong><?php echo htmlspecialchars($property['owner_name']); ?></strong>
                                                                        <br><small class="text-muted"><?php echo htmlspecialchars($property['owner_email']); ?></small>
                                                                    </div>
                                                                </td>
                                                                <td><?php echo htmlspecialchars($property['city']); ?></td>
                                                                <td><?php echo htmlspecialchars($property['type']); ?></td>
                                                                <td><strong>PKR <?php echo number_format($property['price']); ?></strong></td>
                                                                <td>
                                                                    <span class="badge bg-<?php echo $property['status'] === 'active' ? 'success' : ($property['status'] === 'pending' ? 'warning' : 'secondary'); ?>">
                                                                        <?php echo ucfirst($property['status']); ?>
                                                                    </span>
                                                                </td>
                                                                <td><?php echo date('M d, Y', strtotime($property['created_at'])); ?></td>
                                                                <td>
                                                                    <div class="btn-group" role="group">
                                                                        <button class="btn btn-sm btn-outline-primary" 
                                                                                onclick="viewProperty(<?php echo $property['id']; ?>)"
                                                                                title="View Property">
                                                                            <i class="fas fa-eye"></i>
                                                                        </button>
                                                                        <button class="btn btn-sm btn-outline-warning" 
                                                                                onclick="togglePropertyStatus(<?php echo $property['id']; ?>, '<?php echo $property['status']; ?>')"
                                                                                title="<?php echo $property['status'] === 'active' ? 'Deactivate' : 'Activate'; ?>">
                                                                            <i class="fas fa-<?php echo $property['status'] === 'active' ? 'pause' : 'play'; ?>"></i>
                                                                        </button>
                                                                        <button class="btn btn-sm btn-outline-danger" 
                                                                                onclick="deleteProperty(<?php echo $property['id']; ?>)"
                                                                                title="Delete Property">
                                                                            <i class="fas fa-trash"></i>
                                                                        </button>
                                                                    </div>
                                                                </td>
                                                            </tr>
                                                        <?php endwhile; ?>
                                                        <?php if ($userRecentResult->num_rows === 0): ?>
                                                            <tr>
                                                                <td colspan="9" class="text-center">No recent properties found.</td>
                                                            </tr>
                                                        <?php endif; ?>
                                                    <?php endif; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <?php if (isset($userRole) && strtolower($userRole) === 'admin'): ?>
                        <!-- Recent Transactions for Admin -->
                        <div class="row">
                            <div class="col-lg-6 mb-4">
                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="card-title mb-0">Recent Transactions</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="table-responsive">
                                            <table class="table table-sm">
                                                <thead>
                                                    <tr>
                                                        <th>Property</th>
                                                        <th>Buyer</th>
                                                        <th>Seller</th>
                                                        <th>Amount</th>
                                                        <th>Date</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php
                                                    $adminTransactionsQuery = "SELECT t.*, p.title as property_title, 
                                                                             u1.name as buyer_name, u1.email as buyer_email,
                                                                             u2.name as seller_name, u2.email as seller_email
                                                                             FROM transactions t 
                                                                             LEFT JOIN properties p ON t.property_id = p.id 
                                                                             LEFT JOIN users u1 ON t.buyer_id = u1.id
                                                                             LEFT JOIN users u2 ON p.user_id = u2.id
                                                                             ORDER BY t.created_at DESC 
                                                                             LIMIT 5";
                                                    $adminTransactionsResult = $conn->query($adminTransactionsQuery);
                                                    while ($transaction = $adminTransactionsResult->fetch_assoc()):
                                                    ?>
                                                        <tr>
                                                            <td>
                                                                <small><?php echo htmlspecialchars($transaction['property_title'] ?: 'N/A'); ?></small>
                                                            </td>
                                                            <td>
                                                                <small>
                                                                    <strong><?php echo htmlspecialchars($transaction['buyer_name'] ?: 'N/A'); ?></strong>
                                                                    <br><?php echo htmlspecialchars($transaction['buyer_email'] ?: 'N/A'); ?>
                                                                </small>
                                                            </td>
                                                            <td>
                                                                <small>
                                                                    <strong><?php echo htmlspecialchars($transaction['seller_name'] ?: 'N/A'); ?></strong>
                                                                    <br><?php echo htmlspecialchars($transaction['seller_email'] ?: 'N/A'); ?>
                                                                </small>
                                                            </td>
                                                            <td>
                                                                <strong class="text-success">PKR <?php echo number_format($transaction['amount']); ?></strong>
                                                            </td>
                                                            <td>
                                                                <small><?php echo date('M d', strtotime($transaction['created_at'])); ?></small>
                                                            </td>
                                                        </tr>
                                                    <?php endwhile; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-lg-6 mb-4">
                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="card-title mb-0">Recent Users</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="table-responsive">
                                            <table class="table table-sm">
                                                <thead>
                                                    <tr>
                                                        <th>Name</th>
                                                        <th>Email</th>
                                                        <th>Joined</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php
                                                    $recentUsersQuery = "SELECT name, email, created_at FROM users WHERE role != 'admin' ORDER BY created_at DESC LIMIT 5";
                                                    $recentUsersResult = $conn->query($recentUsersQuery);
                                                    while ($user = $recentUsersResult->fetch_assoc()):
                                                    ?>
                                                        <tr>
                                                            <td>
                                                                <small><?php echo htmlspecialchars($user['name']); ?></small>
                                                            </td>
                                                            <td>
                                                                <small><?php echo htmlspecialchars($user['email']); ?></small>
                                                            </td>
                                                            <td>
                                                                <small><?php echo date('M d', strtotime($user['created_at'])); ?></small>
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
                        <?php else: ?>
                        <!-- Transaction History Table for Regular Users -->
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
                        <?php endif; ?>
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
                                            <h5 class="mt-5" id="profileName"><?php echo htmlspecialchars($userName); ?></h5>
                                            <p class="text-muted" id="profileRole"><?php echo ucfirst(htmlspecialchars($userRole)); ?></p>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-lg-8">
                                    <div class="card">
                                        <div class="card-body">

                                            <div class="row mb-3">
                                                <div class="col-md-6">
                                                    <label class="form-label">Full Name <span class="text-danger">*</span></label>
                                                    <input type="text" name="full_name" class="form-control" value="<?php echo htmlspecialchars($userName); ?>" 
                                                           pattern="^[A-Za-z\s]+$" title="Name should only contain letters and spaces." required>
                                                    <div class="invalid-feedback">Please enter a valid name with only letters and spaces.</div>
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label">Email <span class="text-danger">*</span></label>
                                                    <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($userEmail); ?>" readonly>
                                                    <div class="form-text">Email cannot be changed for security reasons.</div>
                                                </div>
                                            </div>
                                            <div class="row mb-3">
                                                <div class="col-md-6">
                                                    <label class="form-label">Phone <span class="text-danger">*</span></label>
                                                    <input type="text" name="phone" class="form-control" value="<?php echo htmlspecialchars($userPhone); ?>" 
                                                           placeholder="03XX-XXXXXXX" pattern="^[0-9]{4}-[0-9]{7}$" title="Please enter phone number in format: 03XX-XXXXXXX" required>
                                                    <div class="invalid-feedback">Please enter a valid phone number in format: 03XX-XXXXXXX</div>
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label">Location <span class="text-danger">*</span></label>
                                                    <input type="text" name="location" class="form-control" value="<?php echo htmlspecialchars($userLocation); ?>" 
                                                           placeholder="City, Country" pattern="^[A-Za-z\s,.\-]+$" title="Location should only contain letters, spaces, commas, dots, and hyphens." required>
                                                    <div class="invalid-feedback">Please enter a valid location with only letters, spaces, commas, dots, and hyphens.</div>
                                                </div>
                                            </div>
                                            <div class="row mb-3">
                                                <div class="col-md-6">
                                                    <label class="form-label">CNIC <span class="text-danger">*</span></label>
                                                    <input type="text" name="cnic" class="form-control" value="<?php echo htmlspecialchars($userCNIC ?? ''); ?>" 
                                                           placeholder="12345-1234567-1" pattern="^[0-9]{5}-[0-9]{7}-[0-9]{1}$" title="Please enter CNIC in format: XXXXX-XXXXXXX-X" required>
                                                    <div class="invalid-feedback">Please enter a valid CNIC in format: XXXXX-XXXXXXX-X</div>
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label">Account Type</label>
                                                    <input type="text" class="form-control" value="<?php echo ucfirst(htmlspecialchars($userRole ?? 'user')); ?>" readonly>
                                                    <div class="form-text">Account type cannot be changed.</div>
                                                </div>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Bio</label>
                                                <textarea name="bio" class="form-control" rows="4" maxlength="500" placeholder="Write a short bio about yourself (max 500 characters)"><?php echo htmlspecialchars($userBio); ?></textarea>
                                                <div class="form-text">Maximum 500 characters allowed.</div>
                                            </div>
                                            
                                            <div class="text-end">
                                                <button type="submit" class="btn btn-primary">
                                                    <i class="fas fa-save me-2"></i>Save Changes
                                                </button>
                                            </div>
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
                                    <button type="button" id="changePasswordBtn" class="btn btn-primary btn-lg">
                                        <i class="fas fa-key me-2"></i>Change Password
                                    </button>
                                </div>
                                
                                <!-- Inline test for password toggles and change password button -->
                                <script>
                                    console.log('Inline script running');
                                    console.log('Toggle buttons found:', {
                                        current: document.getElementById('toggleCurrentPassword'),
                                        new: document.getElementById('toggleNewPassword'),
                                        confirm: document.getElementById('toggleConfirmPassword')
                                    });
                                    console.log('Change password button found:', document.getElementById('changePasswordBtn'));
                                    
                                    // Simple inline toggle test
                                    document.addEventListener('DOMContentLoaded', function() {
                                        const toggles = ['toggleCurrentPassword', 'toggleNewPassword', 'toggleConfirmPassword'];
                                        const inputs = ['currentPassword', 'newPassword', 'confirmPassword'];
                                        
                                        toggles.forEach((toggleId, index) => {
                                            const toggle = document.getElementById(toggleId);
                                            const input = document.getElementById(inputs[index]);
                                            
                                            if (toggle && input) {
                                                console.log('Setting up inline toggle for:', toggleId);
                                                toggle.addEventListener('click', function(e) {
                                                    e.preventDefault();
                                                    console.log('Inline toggle clicked:', toggleId);
                                                    
                                                    if (input.type === 'password') {
                                                        input.type = 'text';
                                                        this.querySelector('i').className = 'fas fa-eye-slash';
                                                    } else {
                                                        input.type = 'password';
                                                        this.querySelector('i').className = 'fas fa-eye';
                                                    }
                                                });
                                            }
                                        });
                                        
                                        // Test change password button
                                        const changePasswordBtn = document.getElementById('changePasswordBtn');
                                        if (changePasswordBtn) {
                                            console.log('Setting up inline change password button handler');
                                            changePasswordBtn.addEventListener('click', function(e) {
                                                e.preventDefault();
                                                console.log('Inline change password button clicked');
                                                
                                                // Get form values
                                                const currentPassword = document.getElementById('currentPassword').value;
                                                const newPassword = document.getElementById('newPassword').value;
                                                const confirmPassword = document.getElementById('confirmPassword').value;
                                                
                                                console.log('Inline form values:', {
                                                    currentPassword: currentPassword ? 'filled' : 'empty',
                                                    newPassword: newPassword ? 'filled' : 'empty',
                                                    confirmPassword: confirmPassword ? 'filled' : 'empty'
                                                });
                                                
                                                // Simple validation
                                                if (!currentPassword || !newPassword || !confirmPassword) {
                                                    alert('Please fill in all fields.');
                                                    return;
                                                }
                                                
                                                if (newPassword !== confirmPassword) {
                                                    alert('New password and confirm password do not match.');
                                                    return;
                                                }
                                                
                                                if (newPassword.length < 6) {
                                                    alert('New password must be at least 6 characters long.');
                                                    return;
                                                }
                                                
                                                console.log('Inline: Sending AJAX request');
                                                
                                                // Use fetch for AJAX request
                                                fetch('../backend/change-password.php', {
                                                    method: 'POST',
                                                    headers: {
                                                        'Content-Type': 'application/x-www-form-urlencoded',
                                                    },
                                                    body: new URLSearchParams({
                                                        current_password: currentPassword,
                                                        new_password: newPassword,
                                                        confirm_password: confirmPassword
                                                    })
                                                })
                                                .then(response => response.json())
                                                .then(data => {
                                                    console.log('Inline response:', data);
                                                    if (data.success) {
                                                        alert('Password changed successfully!');
                                                        document.getElementById('changePasswordForm').reset();
                                                    } else {
                                                        alert(data.message || 'Failed to change password.');
                                                    }
                                                })
                                                .catch(error => {
                                                    console.error('Inline error:', error);
                                                    alert('An error occurred while changing password.');
                                                });
                                            });
                                        }
                                    });
                                </script>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Properties Section -->
    <?php if (!isset($userRole) || strtolower($userRole) !== 'admin'): ?>
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
    <?php endif; ?>

    <!-- Saved Properties Section -->
    <?php if (!isset($userRole) || strtolower($userRole) !== 'admin'): ?>
    <section id="saved" class="dashboard-section">
        <div class="container-fluid">
            <h2 class="section-title mb-4">Saved Properties</h2>

            <div class="row g-4">
                <!-- Saved properties will be injected here via JS -->
            </div>
        </div>
    </section>
    <?php endif; ?>


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
    <?php if (!isset($userRole) || strtolower($userRole) !== 'admin'): ?>
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
    <?php endif; ?>
    <?php if (isset($userRole) && strtolower($userRole) === 'admin'): ?>
<!-- All Properties Section (Admin Only) -->
<section id="all-properties" class="dashboard-section">
    <div class="container-fluid">
        <h2 class="section-title mb-4">All Properties Management</h2>
        
        <!-- Stats Cards -->
        <div class="row mb-4">
            <?php
            // Fetch admin stats
            $totalProperties = 0;
            $activeProperties = 0;
            $pendingProperties = 0;
            $totalOwners = 0;
            
            // Total Properties
            $stmt = $conn->prepare("SELECT COUNT(*) FROM properties");
            $stmt->execute();
            $stmt->bind_result($totalProperties);
            $stmt->fetch();
            $stmt->close();
            
            // Active Properties
            $stmt = $conn->prepare("SELECT COUNT(*) FROM properties WHERE status = 'active'");
            $stmt->execute();
            $stmt->bind_result($activeProperties);
            $stmt->fetch();
            $stmt->close();
            
            // Pending Properties
            $stmt = $conn->prepare("SELECT COUNT(*) FROM properties WHERE listing = 'pending'");
            $stmt->execute();
            $stmt->bind_result($pendingProperties);
            $stmt->fetch();
            $stmt->close();
            
            // Total Property Owners
            $stmt = $conn->prepare("SELECT COUNT(DISTINCT user_id) FROM properties");
            $stmt->execute();
            $stmt->bind_result($totalOwners);
            $stmt->fetch();
            $stmt->close();
            ?>
            
            <div class="col-md-3">
                <div class="stats-card text-center">
                    <i class="fas fa-building fa-2x mb-2"></i>
                    <h3><?php echo $totalProperties; ?></h3>
                    <p class="mb-0">Total Properties</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card text-center">
                    <i class="fas fa-check-circle fa-2x mb-2"></i>
                    <h3><?php echo $activeProperties; ?></h3>
                    <p class="mb-0">Active Properties</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card text-center">
                    <i class="fas fa-clock fa-2x mb-2"></i>
                    <h3><?php echo $pendingProperties; ?></h3>
                    <p class="mb-0">Pending Properties</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card text-center">
                    <i class="fas fa-users fa-2x mb-2"></i>
                    <h3><?php echo $totalOwners; ?></h3>
                    <p class="mb-0">Property Owners</p>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title mb-3">
                            <i class="fas fa-filter me-2"></i>Filters
                        </h5>
                        <div class="row">
                            <div class="col-md-3">
                                <label class="form-label">Status</label>
                                <select class="form-select" id="statusFilter">
                                    <option value="">All Status</option>
                                    <option value="available">Available</option>
                                    <option value="sold">Sold</option>
                                    <option value="pending">Pending</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">City</label>
                                <select class="form-select" id="cityFilter">
                                    <option value="">All Cities</option>
                                    <option value="Karachi">Karachi</option>
                                    <option value="Lahore">Lahore</option>
                                    <option value="Islamabad">Islamabad</option>
                                    <option value="Rawalpindi">Rawalpindi</option>
                                    <option value="Faisalabad">Faisalabad</option>
                                    <option value="Multan">Multan</option>
                                    <option value="Peshawar">Peshawar</option>
                                    <option value="Quetta">Quetta</option>
                                    <option value="Sialkot">Sialkot</option>
                                    <option value="Gujranwala">Gujranwala</option>
                                    <option value="Hyderabad">Hyderabad</option>
                                    <option value="Bahawalpur">Bahawalpur</option>
                                    <option value="Sargodha">Sargodha</option>
                                    <option value="Sukkur">Sukkur</option>
                                    <option value="Abbottabad">Abbottabad</option>
                                    <option value="Mardan">Mardan</option>
                                    <option value="Rahim Yar Khan">Rahim Yar Khan</option>
                                    <option value="Okara">Okara</option>
                                    <option value="Dera Ghazi Khan">Dera Ghazi Khan</option>
                                    <option value="Chiniot">Chiniot</option>
                                    <option value="Jhelum">Jhelum</option>
                                    <option value="Gujrat">Gujrat</option>
                                    <option value="Larkana">Larkana</option>
                                    <option value="Sheikhupura">Sheikhupura</option>
                                    <option value="Mirpur Khas">Mirpur Khas</option>
                                    <option value="Muzaffargarh">Muzaffargarh</option>
                                    <option value="Kohat">Kohat</option>
                                    <option value="Swat">Swat</option>
                                    <option value="Gwadar">Gwadar</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Property Type</label>
                                <select class="form-select" id="typeFilter">
                                    <option value="">All Types</option>
                                    <option value="House">House</option>
                                    <option value="Flat">Flat</option>
                                    <option value="Plot">Plot</option>
                                    <option value="Commercial">Commercial</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Actions</label>
                                <div class="d-grid gap-2">
                                    <button type="button" class="btn btn-primary" onclick="applyFilters()">
                                        <i class="fas fa-search me-2"></i>Apply Filters
                                    </button>
                                    <button type="button" class="btn btn-outline-secondary" onclick="clearFilters()">
                                        <i class="fas fa-times me-2"></i>Clear
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Properties Table -->
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover" id="allPropertiesTable">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Prop. Name</th>
                                <th>Name</th>
                                <th>Owner</th>
                                <th>City</th>
                                <th>Type</th>
                                <th>Price</th>
                                <th>Status</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            // Fetch all properties with user details
                            $propertiesQuery = "SELECT p.*, u.name as owner_name, u.email as owner_email 
                                             FROM properties p 
                                             LEFT JOIN users u ON p.user_id = u.id 
                                             ORDER BY p.created_at DESC";
                            $propertiesResult = $conn->query($propertiesQuery);
                            while ($property = $propertiesResult->fetch_assoc()):
                            ?>
                            <tr class="property-row" 
                                data-id="<?php echo $property['id']; ?>"
                                data-status="<?php echo htmlspecialchars($property['status']); ?>"
                                data-city="<?php echo htmlspecialchars($property['city']); ?>"
                                data-type="<?php echo htmlspecialchars($property['type']); ?>"
                                data-price="<?php echo $property['price']; ?>">
                                <td><?php echo htmlspecialchars($property['id']); ?></td>
                                <td><?php echo htmlspecialchars($property['title']); ?></td>
                                <td>
                                    <div>
                                        <strong><?php echo htmlspecialchars($property['owner_name']); ?></strong>
                                        <br><small class="text-muted"><?php echo htmlspecialchars($property['owner_email']); ?></small>
                                    </div>
                                </td>
                                <td><?php echo htmlspecialchars($property['city']); ?></td>
                                <td><?php echo htmlspecialchars($property['city']); ?></td>
                                <td><?php echo htmlspecialchars($property['type']); ?></td>
                                <td><strong>PKR <?php echo number_format($property['price']); ?></strong></td>
                                <td>
                                    <span class="badge bg-<?php echo $property['status'] === 'active' ? 'success' : ($property['status'] === 'pending' ? 'warning' : 'secondary'); ?>">
                                        <?php echo ucfirst($property['status']); ?>
                                    </span>
                                </td>
                                <td><?php echo date('M d, Y', strtotime($property['created_at'])); ?></td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <button class="btn btn-sm btn-outline-primary" 
                                                onclick="viewProperty(<?php echo $property['id']; ?>)"
                                                title="View Property">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <!-- <button class="btn btn-sm btn-outline-warning" 
                                                onclick="togglePropertyStatus(<?php echo $property['id']; ?>, '<?php echo $property['status']; ?>')"
                                                title="<?php echo $property['status'] === 'active' ? 'Deactivate' : 'Activate'; ?>">
                                            <i class="fas fa-<?php echo $property['status'] === 'active' ? 'pause' : 'play'; ?>"></i>
                                        </button> -->
                                        <button class="btn btn-sm btn-outline-danger" 
                                                onclick="deleteProperty(<?php echo $property['id']; ?>)"
                                                title="Delete Property">
                                            <i class="fas fa-trash"></i>
                                        </button>
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
</section>

<!-- All Users Section (Admin Only) -->
<section id="all-users" class="dashboard-section">
    <div class="container-fluid">
        <h2 class="section-title mb-4">All Users Management</h2>
        
        <!-- Stats Cards -->
        <div class="row mb-4">
            <?php
            // Fetch user stats
            $totalUsers = 0;
            $activeUsers = 0;
            $adminUsers = 0;
            $regularUsers = 0;
            
            // Total Users
            $stmt = $conn->prepare("SELECT COUNT(*) FROM users");
            $stmt->execute();
            $stmt->bind_result($totalUsers);
            $stmt->fetch();
            $stmt->close();
            
            // Admin Users
            $stmt = $conn->prepare("SELECT COUNT(*) FROM users WHERE LOWER(role) = 'admin'");
            $stmt->execute();
            $stmt->bind_result($adminUsers);
            $stmt->fetch();
            $stmt->close();
            
            // Regular Users
            $stmt = $conn->prepare("SELECT COUNT(*) FROM users WHERE LOWER(role) != 'admin'");
            $stmt->execute();
            $stmt->bind_result($regularUsers);
            $stmt->fetch();
            $stmt->close();
            
            // Active Users (users with properties)
            $stmt = $conn->prepare("SELECT COUNT(DISTINCT user_id) FROM properties");
            $stmt->execute();
            $stmt->bind_result($activeUsers);
            $stmt->fetch();
            $stmt->close();
            ?>
            
            <div class="col-md-3">
                <div class="stats-card text-center">
                    <i class="fas fa-users fa-2x mb-2"></i>
                    <h3><?php echo $totalUsers; ?></h3>
                    <p class="mb-0">Total Users</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card text-center">
                    <i class="fas fa-user-shield fa-2x mb-2"></i>
                    <h3><?php echo $adminUsers; ?></h3>
                    <p class="mb-0">Admin Users</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card text-center">
                    <i class="fas fa-user fa-2x mb-2"></i>
                    <h3><?php echo $regularUsers; ?></h3>
                    <p class="mb-0">Regular Users</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card text-center">
                    <i class="fas fa-building fa-2x mb-2"></i>
                    <h3><?php echo $activeUsers; ?></h3>
                    <p class="mb-0">Active Users</p>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title mb-3">
                            <i class="fas fa-filter me-2"></i>Filters
                        </h5>
                        <div class="row">
                            <div class="col-md-3">
                                <label class="form-label">Role</label>
                                <select class="form-select" id="roleFilter">
                                    <option value="">All Roles</option>
                                    <option value="Admin">Admin</option>
                                    <option value="User">Regular User</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Status</label>
                                <select class="form-select" id="userStatusFilter">
                                    <option value="">All Users</option>
                                    <option value="active">Active (Has Properties)</option>
                                    <option value="inactive">Inactive (No Properties)</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Actions</label>
                                <div class="d-grid gap-2">
                                    <button type="button" class="btn btn-primary" onclick="applyUserFilters()">
                                        <i class="fas fa-search me-2"></i>Apply Filters
                                    </button>
                                    <button type="button" class="btn btn-outline-secondary" onclick="clearUserFilters()">
                                        <i class="fas fa-times me-2"></i>Clear
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Users Table -->
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover" id="allUsersTable">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Role</th>
                                <th>Properties</th>
                                <th>Joined</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            // Fetch all users with property count
                            $usersQuery = "SELECT u.*, 
                                          COUNT(p.id) as property_count,
                                          MAX(p.created_at) as last_property_date
                                          FROM users u 
                                          LEFT JOIN properties p ON u.id = p.user_id 
                                          GROUP BY u.id 
                                          ORDER BY u.created_at DESC";
                            $usersResult = $conn->query($usersQuery);
                            while ($user = $usersResult->fetch_assoc()):
                                $isActive = $user['property_count'] > 0;
                            ?>
                            <tr class="user-row" 
                                data-id="<?php echo htmlspecialchars($user['id']); ?>"
                                data-role="<?php echo htmlspecialchars($user['role']); ?>"
                                data-status="<?php echo $isActive ? 'active' : 'inactive'; ?>">
                                <td><?php echo htmlspecialchars($user['id']); ?></td>
                                <td>
                                    <div>
                                        <strong><?php echo htmlspecialchars($user['name']); ?></strong>
                                        <?php if ($user['cnic']): ?>
                                            <br><small class="text-muted">CNIC: <?php echo htmlspecialchars($user['cnic']); ?></small>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td><?php echo htmlspecialchars($user['phone'] ?: 'N/A'); ?></td>
                                <td>
                                    <span class="badge bg-<?php echo strtolower($user['role']) === 'admin' ? 'danger' : 'primary'; ?>">
                                        <?php echo ucfirst($user['role']); ?>
                                    </span>
                                </td>
                                <td>
                                    <strong><?php echo $user['property_count']; ?></strong>
                                    <?php if ($user['last_property_date']): ?>
                                        <br><small class="text-muted">Last: <?php echo date('M d, Y', strtotime($user['last_property_date'])); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                                <td>
                                    <span class="badge bg-<?php echo $isActive ? 'success' : 'secondary'; ?>">
                                        <?php echo $isActive ? 'Active' : 'Inactive'; ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <!-- <button class="btn btn-sm btn-outline-primary" 
                                                onclick="viewUserDetails(<?php echo $user['id']; ?>)"
                                                title="View User Details">
                                            <i class="fas fa-eye"></i>
                                        </button> -->
                                        <?php if ($user['role'] !== 'admin'): ?>
                                        <button class="btn btn-sm btn-outline-danger" 
                                                onclick="deleteUser(<?php echo $user['id']; ?>)"
                                                title="Delete User">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                        <?php else: ?>
                                        <span class="text-muted">-</span>
                                        <?php endif; ?>
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
</section>

<!-- All Transactions Section (Admin Only) -->
<section id="all-transactions" class="dashboard-section">
    <div class="container-fluid">
        <h2 class="section-title mb-4">All Transactions Management</h2>
        
        <!-- Stats Cards -->
        <div class="d-flex flex-wrap gap-4 mb-4">
            <?php
            // Fetch transaction stats
            $totalTransactions = 0;
            $totalRevenue = 0;
            $avgTransactionAmount = 0;
            $recentTransactions = 0;
            
            // Total Transactions
            $stmt = $conn->prepare("SELECT COUNT(*) FROM transactions");
            $stmt->execute();
            $stmt->bind_result($totalTransactions);
            $stmt->fetch();
            $stmt->close();
            
            // Total Revenue
            $stmt = $conn->prepare("SELECT SUM(amount) FROM transactions");
            $stmt->execute();
            $stmt->bind_result($totalRevenue);
            $stmt->fetch();
            $stmt->close();
            
            // Average Transaction Amount
            $stmt = $conn->prepare("SELECT AVG(amount) FROM transactions");
            $stmt->execute();
            $stmt->bind_result($avgTransactionAmount);
            $stmt->fetch();
            $stmt->close();
            
            // Recent Transactions (last 30 days)
            $stmt = $conn->prepare("SELECT COUNT(*) FROM transactions WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)");
            $stmt->execute();
            $stmt->bind_result($recentTransactions);
            $stmt->fetch();
            $stmt->close();
            ?>
            
            <div >
                <div class="stats-card text-center">
                    <i class="fas fa-credit-card fa-2x mb-2"></i>
                    <h5><?php echo $totalTransactions; ?></h5>
                    <p class="mb-0">Total Transactions</p>
                </div>
            </div>
            <div >
                <div class="stats-card text-center">
                    <i class="fas fa-money-bill-wave fa-2x mb-2"></i>
                    <h5>PKR <?php echo number_format($totalRevenue); ?></h5>
                    <p class="mb-0">Total Revenue</p>
                </div>
            </div>
            <div >
                <div class="stats-card text-center">
                    <i class="fas fa-chart-line fa-2x mb-2"></i>
                    <h5 class="text-wrap">PKR <?php echo number_format($avgTransactionAmount); ?></h5>
                    <p class="mb-0">Avg. Transaction</p>
                </div>
            </div>
            <div >
                <div class="stats-card text-center">
                    <i class="fas fa-calendar-day fa-2x mb-2"></i>
                    <h5><?php echo $recentTransactions; ?></h5>
                    <p class="mb-0">Last 30 Days</p>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title mb-3">
                            <i class="fas fa-filter me-2"></i>Filters
                        </h5>
                        <div class="row">
                            <div class="col-md-3">
                                <label class="form-label">Date Range</label>
                                <select class="form-select" id="dateFilter">
                                    <option value="">All Time</option>
                                    <option value="today">Today</option>
                                    <option value="week">This Week</option>
                                    <option value="month">This Month</option>
                                    <option value="year">This Year</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Amount Range</label>
                                <select class="form-select" id="amountFilter">
                                    <option value="">All Amounts</option>
                                    <option value="0-100000">Under 100K</option>
                                    <option value="100000-500000">100K - 500K</option>
                                    <option value="500000-1000000">500K - 1M</option>
                                    <option value="1000000+">Over 1M</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Actions</label>
                                <div class="d-grid gap-2">
                                    <button type="button" class="btn btn-primary" onclick="applyTransactionFilters()">
                                        <i class="fas fa-search me-2"></i>Apply Filters
                                    </button>
                                    <button type="button" class="btn btn-outline-secondary" onclick="clearTransactionFilters()">
                                        <i class="fas fa-times me-2"></i>Clear
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Transactions Table -->
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover" id="allTransactionsTable">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Property</th>
                                <th>Buyer</th>
                                <th>Seller</th>
                                <th>Amount</th>
                                <th>Date</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            // Fetch all transactions with details
                            $transactionsQuery = "SELECT t.*, p.title as property_title, 
                                                u1.name as buyer_name, u1.email as buyer_email,
                                                u2.name as seller_name, u2.email as seller_email
                                                FROM transactions t 
                                                LEFT JOIN properties p ON t.property_id = p.id 
                                                LEFT JOIN users u1 ON t.buyer_id = u1.id
                                                LEFT JOIN users u2 ON p.user_id = u2.id
                                                ORDER BY t.created_at DESC";
                            $transactionsResult = $conn->query($transactionsQuery);
                            while ($transaction = $transactionsResult->fetch_assoc()):
                            ?>
                            <tr class="transaction-row" 
                                data-amount="<?php echo $transaction['amount']; ?>"
                                data-date="<?php echo $transaction['created_at']; ?>">
                                <td><?php echo htmlspecialchars($transaction['id']); ?></td>
                                <td>
                                    <div>
                                        <strong><?php echo htmlspecialchars($transaction['property_title'] ?: 'N/A'); ?></strong>
                                        <?php if ($transaction['property_id']): ?>
                                            <br><small class="text-muted">ID: <?php echo htmlspecialchars($transaction['property_id']); ?></small>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <div>
                                        <strong><?php echo htmlspecialchars($transaction['buyer_name'] ?: 'N/A'); ?></strong>
                                        <br><small class="text-muted"><?php echo htmlspecialchars($transaction['buyer_email'] ?: 'N/A'); ?></small>
                                    </div>
                                </td>
                                <td>
                                    <div>
                                        <strong><?php echo htmlspecialchars($transaction['seller_name'] ?: 'N/A'); ?></strong>
                                        <br><small class="text-muted"><?php echo htmlspecialchars($transaction['seller_email'] ?: 'N/A'); ?></small>
                                    </div>
                                </td>
                                <td><strong class="text-success">PKR <?php echo number_format($transaction['amount']); ?></strong></td>
                                <td><?php echo date('M d, Y H:i', strtotime($transaction['created_at'])); ?></td>
                                <td>
                                    <span class="badge bg-success">Completed</span>
                                </td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <!-- <button class="btn btn-sm btn-outline-primary" 
                                                onclick="viewTransactionDetails(<?php echo $transaction['id']; ?>)"
                                                title="View Details">
                                            <i class="fas fa-eye"></i>
                                        </button> -->
                                        <button class="btn btn-sm btn-outline-info" 
                                                onclick="viewPropertyDetails(<?php echo $transaction['property_id']; ?>)"
                                                title="View Property">
                                            <i class="fas fa-building"></i>
                                        </button>
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
</section>

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

<!-- Property Approvals Section (Admin Only) -->
<section id="property-approvals" class="dashboard-section">
<div class="container-fluid">
        <h2 class="section-title mb-4">Property Approval Management</h2>
        
        <!-- Stats Cards -->
        <div class="row mb-4">
        <?php
            // Fetch approval stats
            $pendingApprovals = 0;
            $approvedToday = 0;
            $rejectedToday = 0;
            $totalApproved = 0;
            
            // Pending Approvals
            $stmt = $conn->prepare("SELECT COUNT(*) FROM properties WHERE listing = 'pending'");
            $stmt->execute();
            $stmt->bind_result($pendingApprovals);
            $stmt->fetch();
            $stmt->close();
            
            // Approved Today
            $stmt = $conn->prepare("SELECT COUNT(*) FROM properties WHERE listing = 'approved' AND DATE(updated_at) = CURDATE()");
            $stmt->execute();
            $stmt->bind_result($approvedToday);
            $stmt->fetch();
            $stmt->close();
            
            // Rejected Today
            $stmt = $conn->prepare("SELECT COUNT(*) FROM properties WHERE listing = 'rejected' AND DATE(updated_at) = CURDATE()");
            $stmt->execute();
            $stmt->bind_result($rejectedToday);
            $stmt->fetch();
            $stmt->close();
            
            // Total Approved
            $stmt = $conn->prepare("SELECT COUNT(*) FROM properties WHERE listing = 'approved'");
            $stmt->execute();
            $stmt->bind_result($totalApproved);
            $stmt->fetch();
            $stmt->close();
            ?>
            
            <div class="col-md-3">
                <div class="stats-card text-center">
                    <i class="fas fa-clock fa-2x mb-2 text-warning"></i>
                    <h3><?php echo $pendingApprovals; ?></h3>
                    <p class="mb-0">Pending Approvals</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card text-center">
                    <i class="fas fa-check-circle fa-2x mb-2 text-success"></i>
                    <h3><?php echo $approvedToday; ?></h3>
                    <p class="mb-0">Approved Today</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card text-center">
                    <i class="fas fa-times-circle fa-2x mb-2 text-danger"></i>
                    <h3><?php echo $rejectedToday; ?></h3>
                    <p class="mb-0">Rejected Today</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card text-center">
                    <i class="fas fa-thumbs-up fa-2x mb-2 text-primary"></i>
                    <h3><?php echo $totalApproved; ?></h3>
                    <p class="mb-0">Total Approved</p>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title mb-3">
                            <i class="fas fa-filter me-2"></i>Filters
                        </h5>
                        <div class="row">
                            <div class="col-md-3">
                                <label class="form-label">Status</label>
                                <select class="form-select" id="approvalStatusFilter">
                                    <option value="">All Status</option>
                                    <option value="pending">Pending</option>
                                    <option value="approved">Approved</option>
                                    <option value="rejected">Rejected</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Property Type</label>
                                <select class="form-select" id="approvalTypeFilter">
                                    <option value="">All Types</option>
                                    <option value="House">House</option>
                                    <option value="Flat">Flat</option>
                                    <option value="Plot">Plot</option>
                                    <option value="Commercial">Commercial</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Price Range</label>
                                <select class="form-select" id="approvalPriceFilter">
                                    <option value="">All Prices</option>
                                    <option value="0-1000000">Under 1M</option>
                                    <option value="1000000-5000000">1M - 5M</option>
                                    <option value="5000000-10000000">5M - 10M</option>
                                    <option value="10000000+">Over 10M</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Actions</label>
                                <div class="d-grid gap-2">
                                    <button type="button" class="btn btn-primary" onclick="applyApprovalFilters()">
                                        <i class="fas fa-search me-2"></i>Apply Filters
                                    </button>
                                    <button type="button" class="btn btn-outline-secondary" onclick="clearApprovalFilters()">
                                        <i class="fas fa-times me-2"></i>Clear
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>


        <!-- Properties Table -->
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-list me-2"></i>Property Approval Queue
                </h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover" id="approvalTable">
                        <thead>
                            <tr>
                                <th>
                                    <input type="checkbox" id="selectAllCheckbox">
                                </th>
                                <th>ID</th>
                                <th>Property</th>
                                <th>Owner</th>
                                <th>Type</th>
                                <th>Location</th>
                                <th>Price</th>
                                <th>Status</th>
                                <th>Submitted</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            // Fetch properties for approval with user details
                            $approvalQuery = "SELECT p.*, u.name as owner_name, u.email as owner_email, u.phone as owner_phone
                                           FROM properties p 
                                           LEFT JOIN users u ON p.user_id = u.id 
                                           ORDER BY 
                                               CASE WHEN p.listing = 'pending' THEN 1 ELSE 2 END,
                                               p.created_at DESC";
                            $approvalResult = $conn->query($approvalQuery);
                            while ($property = $approvalResult->fetch_assoc()):
                                $statusClass = $property['listing'] === 'approved' ? 'success' : 
                                            ($property['listing'] === 'pending' ? 'warning' : 'danger');
                            ?>
                            <tr class="approval-row" 
                                data-id="<?php echo $property['id']; ?>"
                                data-listing="<?php echo htmlspecialchars($property['listing']); ?>"
                                data-type="<?php echo htmlspecialchars($property['type']); ?>"
                                data-city="<?php echo htmlspecialchars($property['city']); ?>"
                                data-price="<?php echo $property['price']; ?>">
                                <td>
                                    <?php if ($property['listing'] === 'pending'): ?>
                                        <input type="checkbox" class="approval-checkbox" value="<?php echo $property['id']; ?>">
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($property['id']); ?></td>
                                <td>
                                    <div>
                                        <strong><?php echo htmlspecialchars($property['title']); ?></strong>
                                        <?php if ($property['description']): ?>
                                            <br><small class="text-muted"><?php echo htmlspecialchars(substr($property['description'], 0, 50)) . '...'; ?></small>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <div>
                                        <strong><?php echo htmlspecialchars($property['owner_name']); ?></strong>
                                        <br><small class="text-muted"><?php echo htmlspecialchars($property['owner_email']); ?></small>
                                        <?php if ($property['owner_phone']): ?>
                                            <br><small class="text-muted"><?php echo htmlspecialchars($property['owner_phone']); ?></small>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge bg-info"><?php echo htmlspecialchars($property['type']); ?></span>
                                </td>
                                <td>
                                    <div>
                                        <strong><?php echo htmlspecialchars($property['city']); ?></strong>
                                        <?php if ($property['area']): ?>
                                            <br><small class="text-muted"><?php echo htmlspecialchars($property['area']); ?></small>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <strong class="text-success">PKR <?php echo number_format($property['price']); ?></strong>
                                </td>
                                <td>
                                    <span class="badge bg-<?php echo $statusClass; ?>">
                                        <?php echo ucfirst($property['listing']); ?>
                                    </span>
                                </td>
                                <td><?php echo date('M d, Y H:i', strtotime($property['created_at'])); ?></td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <button class="btn btn-sm btn-outline-primary" 
                                                onclick="viewPropertyForApproval(<?php echo $property['id']; ?>)"
                                                title="View Details">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <?php if ($property['listing'] === 'pending'): ?>
                                            <button class="btn btn-sm btn-outline-success" 
                                                    onclick="approveProperty(<?php echo $property['id']; ?>)"
                                                    title="Approve Property">
                                                <i class="fas fa-check"></i>
                                            </button>
                                            <button class="btn btn-sm btn-outline-danger" 
                                                    onclick="rejectProperty(<?php echo $property['id']; ?>)"
                                                    title="Reject Property">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        <?php elseif ($property['listing'] === 'approved'): ?>
                                            <button class="btn btn-sm btn-outline-danger" 
                                                    onclick="toggleListingStatus(<?php echo $property['id']; ?>, '<?php echo $property['listing']; ?>')"
                                                    title="Hide Property">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        <?php elseif ($property['listing'] === 'rejected'): ?>
                                            <button class="btn btn-sm btn-outline-success" 
                                                    onclick="toggleListingStatus(<?php echo $property['id']; ?>, '<?php echo $property['listing']; ?>')"
                                                    title="Show Property">
                                                <i class="fas fa-check"></i>
                                            </button>
                                        <?php endif; ?>
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



function viewPropertyForApproval(id) {
    // Open property details in a new window or modal
    window.open('../view-property-detail.php?id=' + id, '_blank');
}

function updatePropertyRow(propertyId, newListingStatus) {
    // Find the table row for this property
    const row = $(`tr[data-id="${propertyId}"]`);
    if (row.length === 0) return;
    
    // Update the listing status in the data attribute
    row.attr('data-status', newListingStatus);
    
    // Update the status badge
    const statusCell = row.find('td:eq(7)'); // Status column
    let statusClass = '';
    let statusText = '';
    
    switch(newListingStatus) {
        case 'approved':
            statusClass = 'success';
            statusText = 'Approved';
            break;
        case 'rejected':
            statusClass = 'danger';
            statusText = 'Rejected';
            break;
        case 'pending':
            statusClass = 'warning';
            statusText = 'Pending';
            break;
    }
    
    statusCell.html(`<span class="badge bg-${statusClass}">${statusText}</span>`);
    
    // Update the action buttons
    const actionCell = row.find('td:last-child .btn-group');
    let newButtons = '';
    
    if (newListingStatus === 'pending') {
        newButtons = `
            <button class="btn btn-sm btn-outline-success" 
                    onclick="approveProperty(${propertyId})"
                    title="Approve Property">
                <i class="fas fa-check"></i>
            </button>
            <button class="btn btn-sm btn-outline-danger" 
                    onclick="rejectProperty(${propertyId})"
                    title="Reject Property">
                <i class="fas fa-times"></i>
            </button>
        `;
    } else if (newListingStatus === 'approved') {
        newButtons = `
            <button class="btn btn-sm btn-outline-primary" 
                    onclick="viewPropertyForApproval(${propertyId})"
                    title="View Details">
                <i class="fas fa-eye"></i>
            </button>
            <button class="btn btn-sm btn-outline-danger" 
                    onclick="toggleListingStatus(${propertyId}, 'approved')"
                    title="Hide Property">
                <i class="fas fa-times"></i>
            </button>
        `;
    } else if (newListingStatus === 'rejected') {
        newButtons = `
            <button class="btn btn-sm btn-outline-primary" 
                    onclick="viewPropertyForApproval(${propertyId})"
                    title="View Details">
                <i class="fas fa-eye"></i>
            </button>
            <button class="btn btn-sm btn-outline-success" 
                    onclick="toggleListingStatus(${propertyId}, 'rejected')"
                    title="Show Property">
                <i class="fas fa-check"></i>
            </button>
        `;
    }
    
    actionCell.html(newButtons);
    
    // Update the checkbox if it exists
    const checkboxCell = row.find('td:first-child');
    if (newListingStatus === 'pending') {
        checkboxCell.html('<input type="checkbox" class="approval-checkbox" value="' + propertyId + '">');
    } else {
        checkboxCell.html('<span class="text-muted">-</span>');
    }
}

function toggleListingStatus(id, currentListing) {
    const newListing = currentListing === 'approved' ? 'rejected' : 'approved';
    const action = currentListing === 'approved' ? 'reject' : 'approve';
    
    if (!confirm('Are you sure you want to ' + action + ' this property?')) return;
    
    $.ajax({
        url: '../backend/toggle-listing-status.php',
        method: 'POST',
        data: { id: id, action: action },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                iziToast.success({
                    title: 'Success',
                    message: response.message,
                    position: 'topRight'
                });
                updatePropertyRow(id, newListing);
            } else {
                iziToast.error({
                    title: 'Error',
                    message: response.message || 'Failed to update listing status.',
                    position: 'topRight'
                });
            }
        },
        error: function() {
            iziToast.error({
                title: 'Error',
                message: 'Error updating listing status. Please try again.',
                position: 'topRight'
            });
        }
        
        
        function updateApprovalRow(propertyId, newListingStatus) {
            const row = $(`.approval-row[data-id="${propertyId}"]`);
            if (row.length) {
                // Update the status badge
                const statusBadge = row.find("td:nth-child(8) .badge");
                if (statusBadge.length) {
                    statusBadge.removeClass("bg-success bg-danger bg-warning")
                        .addClass(newListingStatus === "approved" ? "bg-success" : 
                                 ((newListingStatus === "rejected" ? "bg-danger" : "bg-warning")))
                        .text(newListingStatus.charAt(0).toUpperCase() + newListingStatus.slice(1));
                }
                
                // Update the action buttons
                const actionButtons = row.find("td:nth-child(10) .btn-group");
                if (actionButtons.length) {
                    if (newListingStatus === "approved") {
                        actionButtons.html(`
                            <button class="btn btn-sm btn-outline-primary" 
                                    onclick="viewPropertyForApproval(${propertyId})"
                                    title="View Details">
                                <i class="fas fa-eye"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-danger" 
                                    onclick="toggleListingStatus(${propertyId}, 'approved')"
                                    title="Hide Property">
                                <i class="fas fa-times"></i>
                            </button>
                        `);
                    } else if (newListingStatus === "rejected") {
                        actionButtons.html(`
                            <button class="btn btn-sm btn-outline-primary" 
                                    onclick="viewPropertyForApproval(${propertyId})"
                                    title="View Details">
                                <i class="fas fa-eye"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-success" 
                                    onclick="toggleListingStatus(${propertyId}, 'rejected')"
                                    title="Show Property">
                                <i class="fas fa-check"></i>
                            </button>
                        `);
                    } else {
                        actionButtons.html(`
                            <button class="btn btn-sm btn-outline-primary" 
                                    onclick="viewPropertyForApproval(${propertyId})"
                                    title="View Details">
                                <i class="fas fa-eye"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-success" 
                                    onclick="approveProperty(${propertyId})"
                                    title="Approve Property">
                                <i class="fas fa-check"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-danger" 
                                    onclick="rejectProperty(${propertyId})"
                                    title="Reject Property">
                                <i class="fas fa-times"></i>
                            </button>
                        `);
                    }
                }
                
                // Update the data attribute
                row.attr("data-listing", newListingStatus);
            }
        }
    });
}

// Bulk Actions
function bulkApprove() {
    const selectedIds = getSelectedApprovalIds();
    if (selectedIds.length === 0) {
        alert('Please select properties to approve.');
        return;
    }
    
    if (!confirm('Are you sure you want to approve ' + selectedIds.length + ' properties?')) return;
    
    showLoading('Approving selected properties...');
    $('.btn').prop('disabled', true);
    
    $.ajax({
        url: '../backend/bulk-approve-properties.php',
        method: 'POST',
        data: { property_ids: selectedIds, action: 'approve' },
        dataType: 'json',
        success: function(res) {
            hideLoading();
            $('.btn').prop('disabled', false);
            
            if(res.success) {
                alert(res.message || 'Properties approved successfully.');
                location.reload();
            } else {
                alert(res.message || 'Failed to approve properties.');
            }
        },
        error: function() { 
            hideLoading();
            $('.btn').prop('disabled', false);
            alert('Error processing bulk approval.'); 
        }
    });
}

function bulkReject() {
    const selectedIds = getSelectedApprovalIds();
    if (selectedIds.length === 0) {
        alert('Please select properties to reject.');
        return;
    }
    
    const reason = prompt('Please provide a reason for rejection (optional):');
    if (reason === null) return; // User cancelled
    
    if (!confirm('Are you sure you want to reject ' + selectedIds.length + ' properties?')) return;
    
    showLoading('Rejecting selected properties...');
    $('.btn').prop('disabled', true);
    
    $.ajax({
        url: '../backend/bulk-approve-properties.php',
        method: 'POST',
        data: { 
            property_ids: selectedIds, 
            action: 'reject',
            reason: reason || ''
        },
        dataType: 'json',
        success: function(res) {
            hideLoading();
            $('.btn').prop('disabled', false);
            
            if(res.success) {
                alert(res.message || 'Properties rejected successfully.');
                location.reload();
            } else {
                alert(res.message || 'Failed to reject properties.');
            }
        },
        error: function() { 
            hideLoading();
            $('.btn').prop('disabled', false);
            alert('Error processing bulk rejection.'); 
        }
    });
}

function bulkView() {
    const selectedIds = getSelectedApprovalIds();
    if (selectedIds.length === 0) {
        alert('Please select properties to view.');
        return;
    }
    
    // Open multiple properties in new tabs
    selectedIds.forEach(id => {
        window.open('../view-property-detail.php?id=' + id, '_blank');
    });
}

function getSelectedApprovalIds() {
    const selectedIds = [];
    $('.approval-checkbox:checked').each(function() {
        selectedIds.push($(this).val());
    });
    return selectedIds;
}

// Filter Functions
function applyApprovalFilters() {
    const status = $('#approvalStatusFilter').val();
    const type = $('#approvalTypeFilter').val();
    const price = $('#approvalPriceFilter').val();
    
    $('.approval-row').each(function() {
        let show = true;
        const row = $(this);
        
        if (status && row.data('listing') !== status) show = false;
        if (type && row.data('type') !== type) show = false;
        if (price) {
            const rowPrice = parseInt(row.data('price'));
            const [min, max] = price.split('-').map(p => p === '+' ? Infinity : parseInt(p));
            if (rowPrice < min || rowPrice > max) show = false;
        }
        
        row.toggle(show);
    });
}

function clearApprovalFilters() {
    $('#approvalStatusFilter, #approvalTypeFilter, #approvalPriceFilter').val('');
    $('.approval-row').show();
}

// Select All Functionality
$(document).ready(function() {
    $('#selectAllCheckbox').change(function() {
        $('.approval-checkbox').prop('checked', $(this).is(':checked'));
    });
    
    $('.approval-checkbox').change(function() {
        const totalCheckboxes = $('.approval-checkbox').length;
        const checkedCheckboxes = $('.approval-checkbox:checked').length;
        $('#selectAllCheckbox').prop('checked', totalCheckboxes === checkedCheckboxes);
    });
});
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
        // Define all functions first to avoid ReferenceError
        function applyTransactionFilters() {
            const dateFilter = $('#dateFilter').val();
            const amountFilter = $('#amountFilter').val();

            $('.transaction-row').each(function() {
                let show = true;
                const $row = $(this);
                const amount = parseInt($row.data('amount'));
                const date = new Date($row.data('date'));

                // Date filter
                if (dateFilter) {
                    const now = new Date();
                    let filterDate = new Date();
                    
                    switch (dateFilter) {
                        case 'today':
                            filterDate.setDate(now.getDate() - 1);
                            break;
                        case 'week':
                            filterDate.setDate(now.getDate() - 7);
                            break;
                        case 'month':
                            filterDate.setMonth(now.getMonth() - 1);
                            break;
                        case 'year':
                            filterDate.setFullYear(now.getFullYear() - 1);
                            break;
                    }
                    
                    if (date < filterDate) {
                        show = false;
                    }
                }

                // Amount filter
                if (amountFilter) {
                    const [min, max] = amountFilter.split('-').map(Number);
                    if (amountFilter === '1000000+') {
                        if (amount < 1000000) show = false;
                    } else if (amount < min || amount > max) {
                        show = false;
                    }
                }

                if (show) {
                    $row.show();
                } else {
                    $row.hide();
                }
            });

            // Show message if no results
            const visibleRows = $('.transaction-row:visible').length;
            if (visibleRows === 0) {
                iziToast.info({
                    title: 'No Results',
                    message: 'No transactions match your filters.',
                    position: 'topRight'
                });
            }
        }

        function clearTransactionFilters() {
            $('#dateFilter').val('');
            $('#amountFilter').val('');
            $('.transaction-row').show();
        }

        function applyUserFilters() {
            const role = $('#roleFilter').val();
            const status = $('#userStatusFilter').val();

            $('.user-row').each(function() {
                let show = true;
                const $row = $(this);

                // Role filter
                if (role && $row.data('role') !== role) {
                    show = false;
                }

                // Status filter
                if (status && $row.data('status') !== status) {
                    show = false;
                }

                if (show) {
                    $row.show();
                } else {
                    $row.hide();
                }
            });

            // Show message if no results
            const visibleRows = $('.user-row:visible').length;
            if (visibleRows === 0) {
                iziToast.info({
                    title: 'No Results',
                    message: 'No users match your filters.',
                    position: 'topRight'
                });
            }
        }

        function clearUserFilters() {
            $('#roleFilter').val('');
            $('#userStatusFilter').val('');
            $('.user-row').show();
        }

        function applyFilters() {
            const status = $('#statusFilter').val();
            const city = $('#cityFilter').val();
            const type = $('#typeFilter').val();

            $('.property-row').each(function() {
                let show = true;
                const $row = $(this);

                // Status filter
                if (status && $row.data('status') !== status) {
                    show = false;
                }

                // City filter
                if (city && $row.data('city') !== city) {
                    show = false;
                }

                // Type filter
                if (type && $row.data('type') !== type) {
                    show = false;
                }

                if (show) {
                    $row.show();
                } else {
                    $row.hide();
                }
            });

            // Show message if no results
            const visibleRows = $('.property-row:visible').length;
            if (visibleRows === 0) {
                iziToast.info({
                    title: 'No Results',
                    message: 'No properties match your filters.',
                    position: 'topRight'
                });
            }
        }

        function clearFilters() {
            $('#statusFilter').val('');
            $('#cityFilter').val('');
            $('#typeFilter').val('');
            $('.property-row').show();
        }

        function deleteProperty(id) {
            if (confirm('Are you sure you want to delete this property?')) {
                $.ajax({
                    url: '../backend/delete-property.php',
                    method: 'POST',
                    data: { id: id },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            iziToast.success({
                                title: 'Success',
                                message: response.message,
                                position: 'topRight'
                            });
                            // Remove the row from the table
                            $(`.property-row[data-id="${id}"]`).fadeOut(400, function() {
                                $(this).remove();
                                // Update stats immediately after row removal
                                updatePropertyStats();
                            });
                        } else {
                            iziToast.error({
                                title: 'Error',
                                message: response.message,
                                position: 'topRight'
                            });
                        }
                    },
                    error: function() {
                        iziToast.error({
                            title: 'Error',
                            message: 'Failed to delete property',
                            position: 'topRight'
                        });
                    }
                });
            }
        }

        function deleteUser(userId) {
            if (confirm('Are you sure you want to delete this user?')) {
                $.ajax({
                    url: '../backend/delete-user.php',
                    method: 'POST',
                    data: { id: userId },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            iziToast.success({
                                title: 'Success',
                                message: response.message,
                                position: 'topRight'
                            });
                            // Remove the row from the table
                            $(`.user-row[data-id="${userId}"]`).fadeOut(400, function() {
                                $(this).remove();
                                // Update stats immediately after row removal
                                updateUserStats();
                            });
                        } else {
                            iziToast.error({
                                title: 'Error',
                                message: response.message,
                                position: 'topRight'
                            });
                        }
                    },
                    error: function() {
                        iziToast.error({
                            title: 'Error',
                            message: 'Failed to delete user',
                            position: 'topRight'
                        });
                    }
                });
            }
        }

        // Property Approval Functions
        function approveProperty(id) {
            if (confirm('Are you sure you want to approve this property?')) {
                $.ajax({
                    url: '../backend/approve-property.php?v=1754257249',
                    method: 'POST',
                    data: { 
                        id: id, 
                        action: 'approve' 
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            iziToast.success({
                                title: 'Success',
                                message: 'Property approved successfully!',
                                position: 'topRight'
                            });
                            updatePropertyRow(id, 'approved');
                        } else {
                            iziToast.error({
                                title: 'Error',
                                message: response.message || 'Failed to approve property.',
                                position: 'topRight'
                            });
                        }
                    },
                    error: function() {
                        iziToast.error({
                            title: 'Error',
                            message: 'Error approving property. Please try again.',
                            position: 'topRight'
                        });
                    }
                });
            }
        }

        function rejectProperty(id) {
            if (confirm('Are you sure you want to reject this property?')) {
                $.ajax({
                    url: '../backend/approve-property.php?v=1754257249',
                    method: 'POST',
                    data: { 
                        id: id, 
                        action: 'reject' 
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            iziToast.success({
                                title: 'Success',
                                message: 'Property rejected successfully!',
                                position: 'topRight'
                            });
                            updatePropertyRow(id, 'rejected');
                        } else {
                            iziToast.error({
                                title: 'Error',
                                message: response.message || 'Failed to reject property.',
                                position: 'topRight'
                            });
                        }
                    },
                    error: function() {
                        iziToast.error({
                            title: 'Error',
                            message: 'Error rejecting property. Please try again.',
                            position: 'topRight'
                        });
                    }
                });
            }
        }

        function toggleListingStatus(id, currentListing) {
            const newListingStatus = currentListing === 'approved' ? 'rejected' : 'approved';
            const action = currentListing === 'approved' ? 'reject' : 'approve';
            
            if (confirm(`Are you sure you want to ${action} this property?`)) {
                $.ajax({
                    url: '../backend/toggle-listing-status.php',
                    method: 'POST',
                    data: { 
                        id: id, 
                        action: action 
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            iziToast.success({
                                title: 'Success',
                                message: response.message,
                                position: 'topRight'
                            });
                            updateApprovalRow(id, newListingStatus);
                        } else {
                            iziToast.error({
                                title: 'Error',
                                message: response.message || 'Failed to update listing status.',
                                position: 'topRight'
                            });
                        }
                    },
                    error: function() {
                        iziToast.error({
                            title: 'Error',
                            message: 'Error updating listing status. Please try again.',
                            position: 'topRight'
                        });
                    }
                });
            }
        }

        function updatePropertyRow(propertyId, newListingStatus) {
            const row = $(`.property-row[data-id="${propertyId}"]`);
            if (row.length) {
                // Update status badge
                const statusCell = row.find('.status-badge');
                if (statusCell.length) {
                    statusCell.removeClass('badge-success badge-danger badge-warning')
                           .addClass(newListingStatus === 'approved' ? 'badge-success' : 
                                   newListingStatus === 'rejected' ? 'badge-danger' : 'badge-warning')
                           .text(newListingStatus.charAt(0).toUpperCase() + newListingStatus.slice(1));
                }

                // Update action buttons
                const actionCell = row.find('.action-buttons');
                if (actionCell.length) {
                    if (newListingStatus === 'approved') {
                        actionCell.html(`
                            <button class="btn btn-sm btn-danger" onclick="toggleListingStatus(${propertyId}, 'approved')" title="Reject">
                                <i class="fas fa-times"></i>
                            </button>
                        `);
                    } else if (newListingStatus === 'rejected') {
                        actionCell.html(`
                            <button class="btn btn-sm btn-success" onclick="toggleListingStatus(${propertyId}, 'rejected')" title="Approve">
                                <i class="fas fa-check"></i>
                            </button>
                        `);
                    } else {
                        actionCell.html(`
                            <button class="btn btn-sm btn-success" onclick="toggleListingStatus(${propertyId}, 'pending')" title="Approve">
                                <i class="fas fa-check"></i>
                            </button>
                            <button class="btn btn-sm btn-danger" onclick="toggleListingStatus(${propertyId}, 'pending')" title="Reject">
                                <i class="fas fa-times"></i>
                            </button>
                        `);
                    }
                }

                // Update data attribute
                row.attr('data-listing', newListingStatus);
            }
        }

        function applyApprovalFilters() {
            const status = $('#approvalStatusFilter').val();
            const type = $('#approvalTypeFilter').val();
            const price = $('#approvalPriceFilter').val();

            $('.approval-row').each(function() {
                let show = true;
                const row = $(this);
                const rowListing = row.data('listing');
                const rowType = row.data('type');
                const rowPrice = parseInt(row.data('price'));

                // Status filter
                if (status && rowListing !== status) {
                    show = false;
                }

                // Type filter
                if (type && rowType !== type) {
                    show = false;
                }

                // Price filter
                if (price) {
                    const [min, max] = price.split('-').map(Number);
                    if (price === '1000000+') {
                        if (rowPrice < 1000000) show = false;
                    } else if (rowPrice < min || rowPrice > max) {
                        show = false;
                    }
                }

                if (show) {
                    row.show();
                } else {
                    row.hide();
                }
            });

            // Show message if no results
            const visibleRows = $('.approval-row:visible').length;
            if (visibleRows === 0) {
                iziToast.info({
                    title: 'No Results',
                    message: 'No properties match your filters.',
                    position: 'topRight'
                });
            }
        }

        function clearApprovalFilters() {
            $('#approvalStatusFilter').val('');
            $('#approvalTypeFilter').val('');
            $('#approvalPriceFilter').val('');
            $('.approval-row').show();
        }

        // Property Approval Functions
        function approveProperty(id) {
            if (confirm('Are you sure you want to approve this property?')) {
                $.ajax({
                    url: '../backend/approve-property.php?v=1754257249',
                    method: 'POST',
                    data: { 
                        id: id, 
                        action: 'approve' 
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            iziToast.success({
                                title: 'Success',
                                message: 'Property approved successfully!',
                                position: 'topRight'
                            });
                            updatePropertyRow(id, 'approved');
                        } else {
                            iziToast.error({
                                title: 'Error',
                                message: response.message || 'Failed to approve property.',
                                position: 'topRight'
                            });
                        }
                    },
                    error: function() {
                        iziToast.error({
                            title: 'Error',
                            message: 'Error approving property. Please try again.',
                            position: 'topRight'
                        });
                    }
                });
            }
        }

        function rejectProperty(id) {
            if (confirm('Are you sure you want to reject this property?')) {
                $.ajax({
                    url: '../backend/approve-property.php?v=1754257249',
                    method: 'POST',
                    data: { 
                        id: id, 
                        action: 'reject' 
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            iziToast.success({
                                title: 'Success',
                                message: 'Property rejected successfully!',
                                position: 'topRight'
                            });
                            updatePropertyRow(id, 'rejected');
                        } else {
                            iziToast.error({
                                title: 'Error',
                                message: response.message || 'Failed to reject property.',
                                position: 'topRight'
                            });
                        }
                    },
                    error: function() {
                        iziToast.error({
                            title: 'Error',
                            message: 'Error rejecting property. Please try again.',
                            position: 'topRight'
                        });
                    }
                });
            }
        }

        function toggleListingStatus(id, currentListing) {
            const newListingStatus = currentListing === 'approved' ? 'rejected' : 'approved';
            const action = currentListing === 'approved' ? 'reject' : 'approve';
            
            if (confirm(`Are you sure you want to ${action} this property?`)) {
                $.ajax({
                    url: '../backend/toggle-listing-status.php',
                    method: 'POST',
                    data: { 
                        id: id, 
                        action: action 
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            iziToast.success({
                                title: 'Success',
                                message: response.message,
                                position: 'topRight'
                            });
                            updateApprovalRow(id, newListingStatus);
                        } else {
                            iziToast.error({
                                title: 'Error',
                                message: response.message || 'Failed to update listing status.',
                                position: 'topRight'
                            });
                        }
                    },
                    error: function() {
                        iziToast.error({
                            title: 'Error',
                            message: 'Error updating listing status. Please try again.',
                            position: 'topRight'
                        });
                    }
                });
            }
        }

        function updatePropertyRow(propertyId, newListingStatus) {
            const row = $(`.property-row[data-id="${propertyId}"]`);
            if (row.length) {
                // Update status badge
                const statusCell = row.find('.status-badge');
                if (statusCell.length) {
                    statusCell.removeClass('badge-success badge-danger badge-warning')
                           .addClass(newListingStatus === 'approved' ? 'badge-success' : 
                                   newListingStatus === 'rejected' ? 'badge-danger' : 'badge-warning')
                           .text(newListingStatus.charAt(0).toUpperCase() + newListingStatus.slice(1));
                }

                // Update action buttons
                const actionCell = row.find('.action-buttons');
                if (actionCell.length) {
                    if (newListingStatus === 'approved') {
                        actionCell.html(`
                            <button class="btn btn-sm btn-danger" onclick="toggleListingStatus(${propertyId}, 'approved')" title="Reject">
                                <i class="fas fa-times"></i>
                            </button>
                        `);
                    } else if (newListingStatus === 'rejected') {
                        actionCell.html(`
                            <button class="btn btn-sm btn-success" onclick="toggleListingStatus(${propertyId}, 'rejected')" title="Approve">
                                <i class="fas fa-check"></i>
                            </button>
                        `);
                    } else {
                        actionCell.html(`
                            <button class="btn btn-sm btn-success" onclick="toggleListingStatus(${propertyId}, 'pending')" title="Approve">
                                <i class="fas fa-check"></i>
                            </button>
                            <button class="btn btn-sm btn-danger" onclick="toggleListingStatus(${propertyId}, 'pending')" title="Reject">
                                <i class="fas fa-times"></i>
                            </button>
                        `);
                    }
                }

                // Update data attribute
                row.attr('data-listing', newListingStatus);
            }
        }

        function applyApprovalFilters() {
            const status = $('#approvalStatusFilter').val();
            const type = $('#approvalTypeFilter').val();
            const price = $('#approvalPriceFilter').val();

            $('.approval-row').each(function() {
                let show = true;
                const row = $(this);
                const rowListing = row.data('listing');
                const rowType = row.data('type');
                const rowPrice = parseInt(row.data('price'));

                // Status filter
                if (status && rowListing !== status) {
                    show = false;
                }

                // Type filter
                if (type && rowType !== type) {
                    show = false;
                }

                // Price filter
                if (price) {
                    const [min, max] = price.split('-').map(Number);
                    if (price === '1000000+') {
                        if (rowPrice < 1000000) show = false;
                    } else if (rowPrice < min || rowPrice > max) {
                        show = false;
                    }
                }

                if (show) {
                    row.show();
                } else {
                    row.hide();
                }
            });

            // Show message if no results
            const visibleRows = $('.approval-row:visible').length;
            if (visibleRows === 0) {
                iziToast.info({
                    title: 'No Results',
                    message: 'No properties match your filters.',
                    position: 'topRight'
                });
            }
        }

        function clearApprovalFilters() {
            $('#approvalStatusFilter').val('');
            $('#approvalTypeFilter').val('');
            $('#approvalPriceFilter').val('');
            $('.approval-row').show();
        }

        // View property function
        function viewProperty(id) {
            console.log('Viewing property with ID:', id);
            if (id) {
                const url = `../view-property-detail.php?id=${id}`;
                console.log('Opening URL:', url);
                window.open(url, '_blank');
            } else {
                iziToast.error({
                    title: 'Error',
                    message: 'Invalid property ID',
                    position: 'topRight'
                });
            }
        }

        // Toggle property status function
        function togglePropertyStatus(id, currentStatus) {
            const newStatus = currentStatus === 'active' ? 'inactive' : 'active';
            const action = currentStatus === 'active' ? 'deactivate' : 'activate';
            
            if (confirm(`Are you sure you want to ${action} this property?`)) {
                $.ajax({
                    url: '../backend/toggle-property-status.php',
                    method: 'POST',
                    data: { 
                        id: id, 
                        status: newStatus 
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            iziToast.success({
                                title: 'Success',
                                message: response.message,
                                position: 'topRight'
                            });
                            // Refresh the page to update all statuses
                            setTimeout(() => {
                                location.reload();
                            }, 1000);
                        } else {
                            iziToast.error({
                                title: 'Error',
                                message: response.message,
                                position: 'topRight'
                            });
                        }
                    },
                    error: function() {
                        iziToast.error({
                            title: 'Error',
                            message: 'Error updating property status. Please try again.',
                            position: 'topRight'
                        });
                    }
                });
            }
        }

        // User management functions
        function viewUserDetails(userId) {
            iziToast.info({
                title: 'User Details',
                message: 'User details feature coming soon!',
                position: 'topRight'
            });
        }
        
        
        function updateApprovalRow(propertyId, newListingStatus) {
            const row = $(`.approval-row[data-id="${propertyId}"]`);
            if (row.length) {
                // Update the status badge
                const statusBadge = row.find("td:nth-child(8) .badge");
                if (statusBadge.length) {
                    statusBadge.removeClass("bg-success bg-danger bg-warning")
                        .addClass(newListingStatus === "approved" ? "bg-success" : 
                                 ((newListingStatus === "rejected" ? "bg-danger" : "bg-warning")))
                        .text(newListingStatus.charAt(0).toUpperCase() + newListingStatus.slice(1));
                }
            
                const actionButtons = row.find("td:nth-child(10) .btn-group");
                if (actionButtons.length) {
                    if (newListingStatus === "approved") {
                        actionButtons.html(`
                            <button class="btn btn-sm btn-outline-primary" 
                                    onclick="viewPropertyForApproval(${propertyId})"
                                    title="View Details">
                                <i class="fas fa-eye"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-danger" 
                                    onclick="toggleListingStatus(${propertyId}, 'approved')"
                                    title="Hide Property">
                                <i class="fas fa-times"></i>
                            </button>
                        `);
                    } else if (newListingStatus === "rejected") {
                        actionButtons.html(`
                            <button class="btn btn-sm btn-outline-primary" 
                                    onclick="viewPropertyForApproval(${propertyId})"
                                    title="View Details">
                                <i class="fas fa-eye"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-success" 
                                    onclick="toggleListingStatus(${propertyId}, 'rejected')"
                                    title="Show Property">
                                <i class="fas fa-check"></i>
                            </button>
                        `);
                    } else {
                        actionButtons.html(`
                            <button class="btn btn-sm btn-outline-primary" 
                                    onclick="viewPropertyForApproval(${propertyId})"
                                    title="View Details">
                                <i class="fas fa-eye"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-success" 
                                    onclick="approveProperty(${propertyId})"
                                    title="Approve Property">
                                <i class="fas fa-check"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-danger" 
                                    onclick="rejectProperty(${propertyId})"
                                    title="Reject Property">
                                <i class="fas fa-times"></i>
                            </button>
                        `);
                    }
                }
                
                // Update the data attribute
                row.attr("data-listing", newListingStatus);
            }
        }
        
        
        function updatePropertyStats() {
            // Update the stats cards with new counts
            const totalProperties = $(".property-row").length;
            const activeProperties = $(".property-row[data-status=\"active\"]").length;
            const pendingProperties = $(".property-row[data-listing=\"pending\"]").length;
            
            console.log("Updating property stats:", { totalProperties, activeProperties, pendingProperties });
            
            // Update stats cards if they exist
            $(".stats-card h3").each(function() {
                const cardText = $(this).next("p").text().toLowerCase();
                if (cardText.includes("total properties")) {
                    $(this).text(totalProperties);
                } else if (cardText.includes("active properties")) {
                    $(this).text(activeProperties);
                } else if (cardText.includes("pending properties")) {
                    $(this).text(pendingProperties);
                }
            });
        });
        
        
        function updateApprovalRow(propertyId, newListingStatus) {
            const row = $(`.approval-row[data-id="${propertyId}"]`);
            if (row.length) {
                // Update the status badge
                const statusBadge = row.find("td:nth-child(8) .badge");
                if (statusBadge.length) {
                    statusBadge.removeClass("bg-success bg-danger bg-warning")
                        .addClass(newListingStatus === "approved" ? "bg-success" : 
                                 ((newListingStatus === "rejected" ? "bg-danger" : "bg-warning")))
                        .text(newListingStatus.charAt(0).toUpperCase() + newListingStatus.slice(1));
                }
                
                // Update the action buttons
                const actionButtons = row.find("td:nth-child(10) .btn-group");
                if (actionButtons.length) {
                    if (newListingStatus === "approved") {
                        actionButtons.html(`
                            <button class="btn btn-sm btn-outline-primary" 
                                    onclick="viewPropertyForApproval(${propertyId})"
                                    title="View Details">
                                <i class="fas fa-eye"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-danger" 
                                    onclick="toggleListingStatus(${propertyId}, 'approved')"
                                    title="Hide Property">
                                <i class="fas fa-times"></i>
                            </button>
                        `);
                    } else if (newListingStatus === "rejected") {
                        actionButtons.html(`
                            <button class="btn btn-sm btn-outline-primary" 
                                    onclick="viewPropertyForApproval(${propertyId})"
                                    title="View Details">
                                <i class="fas fa-eye"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-success" 
                                    onclick="toggleListingStatus(${propertyId}, 'rejected')"
                                    title="Show Property">
                                <i class="fas fa-check"></i>
                            </button>
                        `);
                    } else {
                        actionButtons.html(`
                            <button class="btn btn-sm btn-outline-primary" 
                                    onclick="viewPropertyForApproval(${propertyId})"
                                    title="View Details">
                                <i class="fas fa-eye"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-success" 
                                    onclick="approveProperty(${propertyId})"
                                    title="Approve Property">
                                <i class="fas fa-check"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-danger" 
                                    onclick="rejectProperty(${propertyId})"
                                    title="Reject Property">
                                <i class="fas fa-times"></i>
                            </button>
                        `);
                    }
                }
                
                // Update the data attribute
                row.attr("data-listing", newListingStatus);
            }
        }

        function viewUserProperties(userId) {
            iziToast.info({
                title: 'User Properties',
                message: 'Viewing properties for user ID: ' + userId,
                position: 'topRight'
            });
        }
        
        
        function updateApprovalRow(propertyId, newListingStatus) {
            const row = $(`.approval-row[data-id="${propertyId}"]`);
            if (row.length) {
                // Update the status badge
                const statusBadge = row.find("td:nth-child(8) .badge");
                if (statusBadge.length) {
                    statusBadge.removeClass("bg-success bg-danger bg-warning")
                        .addClass(newListingStatus === "approved" ? "bg-success" : 
                                 ((newListingStatus === "rejected" ? "bg-danger" : "bg-warning")))
                        .text(newListingStatus.charAt(0).toUpperCase() + newListingStatus.slice(1));
                }
                
                // Update the action buttons
                const actionButtons = row.find("td:nth-child(10) .btn-group");
                if (actionButtons.length) {
                    if (newListingStatus === "approved") {
                        actionButtons.html(`
                            <button class="btn btn-sm btn-outline-primary" 
                                    onclick="viewPropertyForApproval(${propertyId})"
                                    title="View Details">
                                <i class="fas fa-eye"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-danger" 
                                    onclick="toggleListingStatus(${propertyId}, 'approved')"
                                    title="Hide Property">
                                <i class="fas fa-times"></i>
                            </button>
                        `);
                    } else if (newListingStatus === "rejected") {
                        actionButtons.html(`
                            <button class="btn btn-sm btn-outline-primary" 
                                    onclick="viewPropertyForApproval(${propertyId})"
                                    title="View Details">
                                <i class="fas fa-eye"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-success" 
                                    onclick="toggleListingStatus(${propertyId}, 'rejected')"
                                    title="Show Property">
                                <i class="fas fa-check"></i>
                            </button>
                        `);
                    } else {
                        actionButtons.html(`
                            <button class="btn btn-sm btn-outline-primary" 
                                    onclick="viewPropertyForApproval(${propertyId})"
                                    title="View Details">
                                <i class="fas fa-eye"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-success" 
                                    onclick="approveProperty(${propertyId})"
                                    title="Approve Property">
                                <i class="fas fa-check"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-danger" 
                                    onclick="rejectProperty(${propertyId})"
                                    title="Reject Property">
                                <i class="fas fa-times"></i>
                            </button>
                        `);
                    }
                }
                
                // Update the data attribute
                row.attr("data-listing", newListingStatus);
            }
        }
        
        
        function updatePropertyStats() {
            // Update the stats cards with new counts
            const totalProperties = $(".property-row").length;
            const activeProperties = $(".property-row[data-status=\"active\"]").length;
            const pendingProperties = $(".property-row[data-listing=\"pending\"]").length;
            
            console.log("Updating property stats:", { totalProperties, activeProperties, pendingProperties });
            
            // Update stats cards if they exist
            $(".stats-card h3").each(function() {
                const cardText = $(this).next("p").text().toLowerCase();
                if (cardText.includes("total properties")) {
                    $(this).text(totalProperties);
                } else if (cardText.includes("active properties")) {
                    $(this).text(activeProperties);
                } else if (cardText.includes("pending properties")) {
                    $(this).text(pendingProperties);
                }
            });
        }
        
        
        function updateApprovalRow(propertyId, newListingStatus) {
            const row = $(`.approval-row[data-id="${propertyId}"]`);
            if (row.length) {
                // Update the status badge
                const statusBadge = row.find("td:nth-child(8) .badge");
                if (statusBadge.length) {
                    statusBadge.removeClass("bg-success bg-danger bg-warning")
                        .addClass(newListingStatus === "approved" ? "bg-success" : 
                                 ((newListingStatus === "rejected" ? "bg-danger" : "bg-warning")))
                        .text(newListingStatus.charAt(0).toUpperCase() + newListingStatus.slice(1));
                }
                
                // Update the action buttons
                const actionButtons = row.find("td:nth-child(10) .btn-group");
                if (actionButtons.length) {
                    if (newListingStatus === "approved") {
                        actionButtons.html(`
                            <button class="btn btn-sm btn-outline-primary" 
                                    onclick="viewPropertyForApproval(${propertyId})"
                                    title="View Details">
                                <i class="fas fa-eye"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-danger" 
                                    onclick="toggleListingStatus(${propertyId}, 'approved')"
                                    title="Hide Property">
                                <i class="fas fa-times"></i>
                            </button>
                        `);
                    } else if (newListingStatus === "rejected") {
                        actionButtons.html(`
                            <button class="btn btn-sm btn-outline-primary" 
                                    onclick="viewPropertyForApproval(${propertyId})"
                                    title="View Details">
                                <i class="fas fa-eye"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-success" 
                                    onclick="toggleListingStatus(${propertyId}, 'rejected')"
                                    title="Show Property">
                                <i class="fas fa-check"></i>
                            </button>
                        `);
                    } else {
                        actionButtons.html(`
                            <button class="btn btn-sm btn-outline-primary" 
                                    onclick="viewPropertyForApproval(${propertyId})"
                                    title="View Details">
                                <i class="fas fa-eye"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-success" 
                                    onclick="approveProperty(${propertyId})"
                                    title="Approve Property">
                                <i class="fas fa-check"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-danger" 
                                    onclick="rejectProperty(${propertyId})"
                                    title="Reject Property">
                                <i class="fas fa-times"></i>
                            </button>
                        `);
                    }
                }
                
                // Update the data attribute
                row.attr("data-listing", newListingStatus);
            }
        }

        function toggleUserRole(userId, currentRole) {
            const newRole = currentRole === 'admin' ? 'user' : 'admin';
            const action = currentRole === 'admin' ? 'remove admin' : 'make admin';
            
            if (confirm(`Are you sure you want to ${action} for this user?`)) {
                $.ajax({
                    url: '../backend/toggle-user-role.php',
                    method: 'POST',
                    data: { 
                        id: userId, 
                        role: newRole 
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            iziToast.success({
                                title: 'Success',
                                message: response.message,
                                position: 'topRight'
                            });
                            // Refresh the page to update all roles
                            setTimeout(() => {
                                location.reload();
                            }, 1000);
                        } else {
                            iziToast.error({
                                title: 'Error',
                                message: response.message,
                                position: 'topRight'
                            });
                        }
                    },
                    error: function() {
                        iziToast.error({
                            title: 'Error',
                            message: 'Error updating user role. Please try again.',
                            position: 'topRight'
                        });
                    }
                });
            }
        }

        function updateUserStats() {
            // Update the stats cards with new counts
            const totalUsers = $('.user-row').length;
            const adminUsers = $('.user-row[data-role="admin"]').length;
            const regularUsers = $('.user-row[data-role="user"]').length;
            const activeUsers = $('.user-row[data-status="active"]').length;
            
            console.log('Updating stats:', { totalUsers, adminUsers, regularUsers, activeUsers });
            
            // Update stats cards if they exist
            $('.stats-card h3').each(function() {
                const cardText = $(this).next('p').text().toLowerCase();
                if (cardText.includes('total users')) {
                    $(this).text(totalUsers);
                } else if (cardText.includes('admin users')) {
                    $(this).text(adminUsers);
                } else if (cardText.includes('regular users')) {
                    $(this).text(regularUsers);
                } else if (cardText.includes('active users')) {
                    $(this).text(activeUsers);
                }
            });
        }
        
        
        function updateApprovalRow(propertyId, newListingStatus) {
            const row = $(`.approval-row[data-id="${propertyId}"]`);
            if (row.length) {
                // Update the status badge
                const statusBadge = row.find("td:nth-child(8) .badge");
                if (statusBadge.length) {
                    statusBadge.removeClass("bg-success bg-danger bg-warning")
                        .addClass(newListingStatus === "approved" ? "bg-success" : 
                                 ((newListingStatus === "rejected" ? "bg-danger" : "bg-warning")))
                        .text(newListingStatus.charAt(0).toUpperCase() + newListingStatus.slice(1));
                }
                
                // Update the action buttons
                const actionButtons = row.find("td:nth-child(10) .btn-group");
                if (actionButtons.length) {
                    if (newListingStatus === "approved") {
                        actionButtons.html(`
                            <button class="btn btn-sm btn-outline-primary" 
                                    onclick="viewPropertyForApproval(${propertyId})"
                                    title="View Details">
                                <i class="fas fa-eye"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-danger" 
                                    onclick="toggleListingStatus(${propertyId}, 'approved')"
                                    title="Hide Property">
                                <i class="fas fa-times"></i>
                            </button>
                        `);
                    } else if (newListingStatus === "rejected") {
                        actionButtons.html(`
                            <button class="btn btn-sm btn-outline-primary" 
                                    onclick="viewPropertyForApproval(${propertyId})"
                                    title="View Details">
                                <i class="fas fa-eye"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-success" 
                                    onclick="toggleListingStatus(${propertyId}, 'rejected')"
                                    title="Show Property">
                                <i class="fas fa-check"></i>
                            </button>
                        `);
                    } else {
                        actionButtons.html(`
                            <button class="btn btn-sm btn-outline-primary" 
                                    onclick="viewPropertyForApproval(${propertyId})"
                                    title="View Details">
                                <i class="fas fa-eye"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-success" 
                                    onclick="approveProperty(${propertyId})"
                                    title="Approve Property">
                                <i class="fas fa-check"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-danger" 
                                    onclick="rejectProperty(${propertyId})"
                                    title="Reject Property">
                                <i class="fas fa-times"></i>
                            </button>
                        `);
                    }
                }
                
                // Update the data attribute
                row.attr("data-listing", newListingStatus);
            }
        }
        
        
        function updatePropertyStats() {
            // Update the stats cards with new counts
            const totalProperties = $(".property-row").length;
            const activeProperties = $(".property-row[data-status=\"active\"]").length;
            const pendingProperties = $(".property-row[data-listing=\"pending\"]").length;
            
            console.log("Updating property stats:", { totalProperties, activeProperties, pendingProperties });
            
            // Update stats cards if they exist
            $(".stats-card h3").each(function() {
                const cardText = $(this).next("p").text().toLowerCase();
                if (cardText.includes("total properties")) {
                    $(this).text(totalProperties);
                } else if (cardText.includes("active properties")) {
                    $(this).text(activeProperties);
                } else if (cardText.includes("pending properties")) {
                    $(this).text(pendingProperties);
                }
            });
        }
        
        
        function updateApprovalRow(propertyId, newListingStatus) {
            const row = $(`.approval-row[data-id="${propertyId}"]`);
            if (row.length) {
                // Update the status badge
                const statusBadge = row.find("td:nth-child(8) .badge");
                if (statusBadge.length) {
                    statusBadge.removeClass("bg-success bg-danger bg-warning")
                        .addClass(newListingStatus === "approved" ? "bg-success" : 
                                 ((newListingStatus === "rejected" ? "bg-danger" : "bg-warning")))
                        .text(newListingStatus.charAt(0).toUpperCase() + newListingStatus.slice(1));
                }
                
                // Update the action buttons
                const actionButtons = row.find("td:nth-child(10) .btn-group");
                if (actionButtons.length) {
                    if (newListingStatus === "approved") {
                        actionButtons.html(`
                            <button class="btn btn-sm btn-outline-primary" 
                                    onclick="viewPropertyForApproval(${propertyId})"
                                    title="View Details">
                                <i class="fas fa-eye"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-danger" 
                                    onclick="toggleListingStatus(${propertyId}, 'approved')"
                                    title="Hide Property">
                                <i class="fas fa-times"></i>
                            </button>
                        `);
                    } else if (newListingStatus === "rejected") {
                        actionButtons.html(`
                            <button class="btn btn-sm btn-outline-primary" 
                                    onclick="viewPropertyForApproval(${propertyId})"
                                    title="View Details">
                                <i class="fas fa-eye"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-success" 
                                    onclick="toggleListingStatus(${propertyId}, 'rejected')"
                                    title="Show Property">
                                <i class="fas fa-check"></i>
                            </button>
                        `);
                    } else {
                        actionButtons.html(`
                            <button class="btn btn-sm btn-outline-primary" 
                                    onclick="viewPropertyForApproval(${propertyId})"
                                    title="View Details">
                                <i class="fas fa-eye"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-success" 
                                    onclick="approveProperty(${propertyId})"
                                    title="Approve Property">
                                <i class="fas fa-check"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-danger" 
                                    onclick="rejectProperty(${propertyId})"
                                    title="Reject Property">
                                <i class="fas fa-times"></i>
                            </button>
                        `);
                    }
                }
                
                // Update the data attribute
                row.attr("data-listing", newListingStatus);
            }
        }

        // Transaction management functions
        function viewTransactionDetails(transactionId) {
            iziToast.info({
                title: 'Transaction Details',
                message: 'Transaction details feature coming soon!',
                position: 'topRight'
            });
        }
        
        
        function updateApprovalRow(propertyId, newListingStatus) {
            const row = $(`.approval-row[data-id="${propertyId}"]`);
            if (row.length) {
                // Update the status badge
                const statusBadge = row.find("td:nth-child(8) .badge");
                if (statusBadge.length) {
                    statusBadge.removeClass("bg-success bg-danger bg-warning")
                        .addClass(newListingStatus === "approved" ? "bg-success" : 
                                 ((newListingStatus === "rejected" ? "bg-danger" : "bg-warning")))
                        .text(newListingStatus.charAt(0).toUpperCase() + newListingStatus.slice(1));
                }
                
                // Update the action buttons
                const actionButtons = row.find("td:nth-child(10) .btn-group");
                if (actionButtons.length) {
                    if (newListingStatus === "approved") {
                        actionButtons.html(`
                            <button class="btn btn-sm btn-outline-primary" 
                                    onclick="viewPropertyForApproval(${propertyId})"
                                    title="View Details">
                                <i class="fas fa-eye"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-danger" 
                                    onclick="toggleListingStatus(${propertyId}, 'approved')"
                                    title="Hide Property">
                                <i class="fas fa-times"></i>
                            </button>
                        `);
                    } else if (newListingStatus === "rejected") {
                        actionButtons.html(`
                            <button class="btn btn-sm btn-outline-primary" 
                                    onclick="viewPropertyForApproval(${propertyId})"
                                    title="View Details">
                                <i class="fas fa-eye"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-success" 
                                    onclick="toggleListingStatus(${propertyId}, 'rejected')"
                                    title="Show Property">
                                <i class="fas fa-check"></i>
                            </button>
                        `);
                    } else {
                        actionButtons.html(`
                            <button class="btn btn-sm btn-outline-primary" 
                                    onclick="viewPropertyForApproval(${propertyId})"
                                    title="View Details">
                                <i class="fas fa-eye"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-success" 
                                    onclick="approveProperty(${propertyId})"
                                    title="Approve Property">
                                <i class="fas fa-check"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-danger" 
                                    onclick="rejectProperty(${propertyId})"
                                    title="Reject Property">
                                <i class="fas fa-times"></i>
                            </button>
                        `);
                    }
                }
                
                // Update the data attribute
                row.attr("data-listing", newListingStatus);
            }
        }
        
        
        function updatePropertyStats() {
            // Update the stats cards with new counts
            const totalProperties = $(".property-row").length;
            const activeProperties = $(".property-row[data-status=\"active\"]").length;
            const pendingProperties = $(".property-row[data-listing=\"pending\"]").length;
            
            console.log("Updating property stats:", { totalProperties, activeProperties, pendingProperties });
            
            // Update stats cards if they exist
            $(".stats-card h3").each(function() {
                const cardText = $(this).next("p").text().toLowerCase();
                if (cardText.includes("total properties")) {
                    $(this).text(totalProperties);
                } else if (cardText.includes("active properties")) {
                    $(this).text(activeProperties);
                } else if (cardText.includes("pending properties")) {
                    $(this).text(pendingProperties);
                }
            });
        }
        
        
        function updateApprovalRow(propertyId, newListingStatus) {
            const row = $(`.approval-row[data-id="${propertyId}"]`);
            if (row.length) {
                // Update the status badge
                const statusBadge = row.find("td:nth-child(8) .badge");
                if (statusBadge.length) {
                    statusBadge.removeClass("bg-success bg-danger bg-warning")
                        .addClass(newListingStatus === "approved" ? "bg-success" : 
                                 ((newListingStatus === "rejected" ? "bg-danger" : "bg-warning")))
                        .text(newListingStatus.charAt(0).toUpperCase() + newListingStatus.slice(1));
                }
                
                // Update the action buttons
                const actionButtons = row.find("td:nth-child(10) .btn-group");
                if (actionButtons.length) {
                    if (newListingStatus === "approved") {
                        actionButtons.html(`
                            <button class="btn btn-sm btn-outline-primary" 
                                    onclick="viewPropertyForApproval(${propertyId})"
                                    title="View Details">
                                <i class="fas fa-eye"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-danger" 
                                    onclick="toggleListingStatus(${propertyId}, 'approved')"
                                    title="Hide Property">
                                <i class="fas fa-times"></i>
                            </button>
                        `);
                    } else if (newListingStatus === "rejected") {
                        actionButtons.html(`
                            <button class="btn btn-sm btn-outline-primary" 
                                    onclick="viewPropertyForApproval(${propertyId})"
                                    title="View Details">
                                <i class="fas fa-eye"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-success" 
                                    onclick="toggleListingStatus(${propertyId}, 'rejected')"
                                    title="Show Property">
                                <i class="fas fa-check"></i>
                            </button>
                        `);
                    } else {
                        actionButtons.html(`
                            <button class="btn btn-sm btn-outline-primary" 
                                    onclick="viewPropertyForApproval(${propertyId})"
                                    title="View Details">
                                <i class="fas fa-eye"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-success" 
                                    onclick="approveProperty(${propertyId})"
                                    title="Approve Property">
                                <i class="fas fa-check"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-danger" 
                                    onclick="rejectProperty(${propertyId})"
                                    title="Reject Property">
                                <i class="fas fa-times"></i>
                            </button>
                        `);
                    }
                }
                
                // Update the data attribute
                row.attr("data-listing", newListingStatus);
            }
        }

        function viewPropertyDetails(propertyId) {
            if (propertyId) {
                const url = `../view-property-detail.php?id=${propertyId}`;
                window.open(url, '_blank');
            } else {
                iziToast.error({
                    title: 'Error',
                    message: 'Property not found',
                    position: 'topRight'
                });
            }
        }

        // Document ready function
        $(document).ready(function() {
            // Fetch rewards data with null checks
            fetch('../backend/fetch-referral-dashboard.php')
                .then(response => response.json())
                .then(data => {
                    console.log('Fetched data:', data);

                    if (data.status === 'success') {
                        // Add null checks for rewards elements
                        const rewardsPointsH1 = document.querySelector('.rewards-points h1');
                        const progressBar = document.querySelector('.progress-bar');
                        const rewardsProgressSmall = document.querySelector('.rewards-progress small');
                        const historyBody = document.querySelector('#rewards .table tbody');
                        
                        if (rewardsPointsH1) {
                            rewardsPointsH1.textContent = data.total_points;
                        }
                        if (progressBar) {
                            progressBar.style.width = `${data.progress_percent}%`;
                        }
                        if (rewardsProgressSmall) {
                            rewardsProgressSmall.textContent = `${data.points_to_next} more points needed`;
                        }
                        if (historyBody) {
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
                            <label>City</label>
                            <select name="city" id="editCity" class="form-select" required>
                                <option value="">Select City</option>
                                <option value="Karachi">Karachi</option>
                                <option value="Lahore">Lahore</option>
                                <option value="Islamabad">Islamabad</option>
                                <option value="Rawalpindi">Rawalpindi</option>
                                <option value="Faisalabad">Faisalabad</option>
                                <option value="Multan">Multan</option>
                                <option value="Peshawar">Peshawar</option>
                                <option value="Quetta">Quetta</option>
                                <option value="Sialkot">Sialkot</option>
                                <option value="Gujranwala">Gujranwala</option>
                                <option value="Hyderabad">Hyderabad</option>
                                <option value="Bahawalpur">Bahawalpur</option>
                                <option value="Sargodha">Sargodha</option>
                                <option value="Sukkur">Sukkur</option>
                                <option value="Abbottabad">Abbottabad</option>
                                <option value="Mardan">Mardan</option>
                                <option value="Rahim Yar Khan">Rahim Yar Khan</option>
                                <option value="Okara">Okara</option>
                                <option value="Dera Ghazi Khan">Dera Ghazi Khan</option>
                                <option value="Chiniot">Chiniot</option>
                                <option value="Jhelum">Jhelum</option>
                                <option value="Gujrat">Gujrat</option>
                                <option value="Larkana">Larkana</option>
                                <option value="Sheikhupura">Sheikhupura</option>
                                <option value="Mirpur Khas">Mirpur Khas</option>
                                <option value="Muzaffargarh">Muzaffargarh</option>
                                <option value="Kohat">Kohat</option>
                                <option value="Swat">Swat</option>
                                <option value="Gwadar">Gwadar</option>
                            </select>
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
                                <!-- <option value="square feet">Square Feet</option> -->
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

                        <div class="mb-3">
                            <label>Location on Map</label>
                            <input type="text" name="link" id="editLink" class="form-control" placeholder="Enter Google Maps embed iframe link">
                            <div class="invalid-feedback" id="editLinkError">
                                Please provide a valid Google Maps embed iframe link.
                            </div>
                            <div class="form-text">
                                <i class="fas fa-info-circle"></i> 
                                Paste the embed iframe code from Google Maps. Example: &lt;iframe src="https://www.google.com/maps/embed?..."&gt;
                            </div>
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
            $('#editCity').val(property.city);
            $('#editArea').val(property.area);
            $('#editUnit').val(property.unit);
            $('#editType').val(property.type);
            $('#editLink').val(property.link);


            $('#editPropertyModal').modal('show');
        });

        // Map link validation for edit modal
        $('#editLink').on('input blur', function() {
            validateEditMapLink(this.value);
        });

        function validateEditMapLink(value) {
            const errorDiv = document.getElementById('editLinkError');
            
            // If empty, it's valid (optional field)
            if (!value.trim()) {
                $(this).removeClass('is-invalid');
                errorDiv.textContent = 'Please provide a valid Google Maps embed iframe link.';
                return true;
            }
            
            // Check if it's a valid iframe embed link
            const iframePattern = /<iframe[^>]*src=["'](https?:\/\/www\.google\.com\/maps\/embed[^"']*)["'][^>]*>/i;
            
            if (!iframePattern.test(value)) {
                $(this).addClass('is-invalid');
                errorDiv.textContent = 'Please provide the complete iframe HTML code from Google Maps embed, not just the URL.';
                return false;
            }
            
            // Additional validation for iframe structure
            if (value.includes('<iframe') && !value.includes('src=')) {
                $(this).addClass('is-invalid');
                errorDiv.textContent = 'Invalid iframe structure. Please include the src attribute';
                return false;
            }
            
            // Ensure it has proper iframe closing tag
            if (value.includes('<iframe') && !value.includes('</iframe>')) {
                $(this).addClass('is-invalid');
                errorDiv.textContent = 'Invalid iframe structure. Please include the complete iframe HTML with closing tag';
                return false;
            }
            
            $(this).removeClass('is-invalid');
            errorDiv.textContent = 'Please provide a valid Google Maps embed iframe link.';
            return true;
        }
    </script>

    <script>
        // Global functions for admin properties
        function viewProperty(id) {
            console.log('Viewing property with ID:', id);
            if (id) {
                const url = `../view-property-detail.php?id=${id}`;
                console.log('Opening URL:', url);
                window.open(url, '_blank');
            } else {
                iziToast.error({
                    title: 'Error',
                    message: 'Invalid property ID',
                    position: 'topRight'
                });
            }
        }

        function togglePropertyStatus(id, currentStatus) {
            const newStatus = currentStatus === 'active' ? 'inactive' : 'active';
            const action = currentStatus === 'active' ? 'deactivate' : 'activate';
            
            if (confirm(`Are you sure you want to ${action} this property?`)) {
                $.ajax({
                    url: '../backend/toggle-property-status.php',
                    method: 'POST',
                    data: { 
                        id: id, 
                        status: newStatus 
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            iziToast.success({
                                title: 'Success',
                                message: response.message,
                                position: 'topRight'
                            });
                            // Refresh the page to update all statuses
                            setTimeout(() => {
                                location.reload();
                            }, 1000);
                        } else {
                            iziToast.error({
                                title: 'Error',
                                message: response.message,
                                position: 'topRight'
                            });
                        }
                    },
                    error: function() {
                        iziToast.error({
                            title: 'Error',
                            message: 'Error updating property status. Please try again.',
                            position: 'topRight'
                        });
                    }
                });
            }
        }

        // deleteProperty function already defined above

        function applyFilters() {
            const status = $('#statusFilter').val();
            const city = $('#cityFilter').val();
            const type = $('#typeFilter').val();

            $('.property-row').each(function() {
                let show = true;
                const $row = $(this);

                // Status filter
                if (status && $row.data('status') !== status) {
                    show = false;
                }

                // City filter
                if (city && $row.data('city') !== city) {
                    show = false;
                }

                // Type filter
                if (type && $row.data('type') !== type) {
                    show = false;
                }

                if (show) {
                    $row.show();
                } else {
                    $row.hide();
                }
            });

            // Show message if no results
            const visibleRows = $('.property-row:visible').length;
            if (visibleRows === 0) {
                iziToast.info({
                    title: 'No Results',
                    message: 'No properties match your filters.',
                    position: 'topRight'
                });
            }
        }

        function clearFilters() {
            $('#statusFilter').val('');
            $('#cityFilter').val('');
            $('#typeFilter').val('');
            $('.property-row').show();
        }

        // User Management Functions
        function applyUserFilters() {
            const role = $('#roleFilter').val();
            const status = $('#userStatusFilter').val();

            $('.user-row').each(function() {
                let show = true;
                const $row = $(this);

                // Role filter
                if (role && $row.data('role') !== role) {
                    show = false;
                }

                // Status filter
                if (status && $row.data('status') !== status) {
                    show = false;
                }

                if (show) {
                    $row.show();
                } else {
                    $row.hide();
                }
            });

            // Show message if no results
            const visibleRows = $('.user-row:visible').length;
            if (visibleRows === 0) {
                iziToast.info({
                    title: 'No Results',
                    message: 'No users match your filters.',
                    position: 'topRight'
                });
            }
        }

        function clearUserFilters() {
            $('#roleFilter').val('');
            $('#userStatusFilter').val('');
            $('.user-row').show();
        }

        function viewUserDetails(userId) {
            // Show user details in a modal or redirect to user profile
            iziToast.info({
                title: 'User Details',
                message: 'User details feature coming soon!',
                position: 'topRight'
            });
        }
        
        
        function updateApprovalRow(propertyId, newListingStatus) {
            const row = $(`.approval-row[data-id="${propertyId}"]`);
            if (row.length) {
                // Update the status badge
                const statusBadge = row.find("td:nth-child(8) .badge");
                if (statusBadge.length) {
                    statusBadge.removeClass("bg-success bg-danger bg-warning")
                        .addClass(newListingStatus === "approved" ? "bg-success" : 
                                 ((newListingStatus === "rejected" ? "bg-danger" : "bg-warning")))
                        .text(newListingStatus.charAt(0).toUpperCase() + newListingStatus.slice(1));
                }
                
                // Update the action buttons
                const actionButtons = row.find("td:nth-child(10) .btn-group");
                if (actionButtons.length) {
                    if (newListingStatus === "approved") {
                        actionButtons.html(`
                            <button class="btn btn-sm btn-outline-primary" 
                                    onclick="viewPropertyForApproval(${propertyId})"
                                    title="View Details">
                                <i class="fas fa-eye"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-danger" 
                                    onclick="toggleListingStatus(${propertyId}, 'approved')"
                                    title="Hide Property">
                                <i class="fas fa-times"></i>
                            </button>
                        `);
                    } else if (newListingStatus === "rejected") {
                        actionButtons.html(`
                            <button class="btn btn-sm btn-outline-primary" 
                                    onclick="viewPropertyForApproval(${propertyId})"
                                    title="View Details">
                                <i class="fas fa-eye"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-success" 
                                    onclick="toggleListingStatus(${propertyId}, 'rejected')"
                                    title="Show Property">
                                <i class="fas fa-check"></i>
                            </button>
                        `);
                    } else {
                        actionButtons.html(`
                            <button class="btn btn-sm btn-outline-primary" 
                                    onclick="viewPropertyForApproval(${propertyId})"
                                    title="View Details">
                                <i class="fas fa-eye"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-success" 
                                    onclick="approveProperty(${propertyId})"
                                    title="Approve Property">
                                <i class="fas fa-check"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-danger" 
                                    onclick="rejectProperty(${propertyId})"
                                    title="Reject Property">
                                <i class="fas fa-times"></i>
                            </button>
                        `);
                    }
                }
                
                // Update the data attribute
                row.attr("data-listing", newListingStatus);
            }
        }
        
        
        function updatePropertyStats() {
            // Update the stats cards with new counts
            const totalProperties = $(".property-row").length;
            const activeProperties = $(".property-row[data-status=\"active\"]").length;
            const pendingProperties = $(".property-row[data-listing=\"pending\"]").length;
            
            console.log("Updating property stats:", { totalProperties, activeProperties, pendingProperties });
            
            // Update stats cards if they exist
            $(".stats-card h3").each(function() {
                const cardText = $(this).next("p").text().toLowerCase();
                if (cardText.includes("total properties")) {
                    $(this).text(totalProperties);
                } else if (cardText.includes("active properties")) {
                    $(this).text(activeProperties);
                } else if (cardText.includes("pending properties")) {
                    $(this).text(pendingProperties);
                }
            });
        }
        
        
        function updateApprovalRow(propertyId, newListingStatus) {
            const row = $(`.approval-row[data-id="${propertyId}"]`);
            if (row.length) {
                // Update the status badge
                const statusBadge = row.find("td:nth-child(8) .badge");
                if (statusBadge.length) {
                    statusBadge.removeClass("bg-success bg-danger bg-warning")
                        .addClass(newListingStatus === "approved" ? "bg-success" : 
                                 ((newListingStatus === "rejected" ? "bg-danger" : "bg-warning")))
                        .text(newListingStatus.charAt(0).toUpperCase() + newListingStatus.slice(1));
                }
                
                // Update the action buttons
                const actionButtons = row.find("td:nth-child(10) .btn-group");
                if (actionButtons.length) {
                    if (newListingStatus === "approved") {
                        actionButtons.html(`
                            <button class="btn btn-sm btn-outline-primary" 
                                    onclick="viewPropertyForApproval(${propertyId})"
                                    title="View Details">
                                <i class="fas fa-eye"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-danger" 
                                    onclick="toggleListingStatus(${propertyId}, 'approved')"
                                    title="Hide Property">
                                <i class="fas fa-times"></i>
                            </button>
                        `);
                    } else if (newListingStatus === "rejected") {
                        actionButtons.html(`
                            <button class="btn btn-sm btn-outline-primary" 
                                    onclick="viewPropertyForApproval(${propertyId})"
                                    title="View Details">
                                <i class="fas fa-eye"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-success" 
                                    onclick="toggleListingStatus(${propertyId}, 'rejected')"
                                    title="Show Property">
                                <i class="fas fa-check"></i>
                            </button>
                        `);
                    } else {
                        actionButtons.html(`
                            <button class="btn btn-sm btn-outline-primary" 
                                    onclick="viewPropertyForApproval(${propertyId})"
                                    title="View Details">
                                <i class="fas fa-eye"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-success" 
                                    onclick="approveProperty(${propertyId})"
                                    title="Approve Property">
                                <i class="fas fa-check"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-danger" 
                                    onclick="rejectProperty(${propertyId})"
                                    title="Reject Property">
                                <i class="fas fa-times"></i>
                            </button>
                        `);
                    }
                }
                
                // Update the data attribute
                row.attr("data-listing", newListingStatus);
            }
        }

        function viewUserProperties(userId) {
            // Filter properties table to show only this user's properties
            iziToast.info({
                title: 'User Properties',
                message: 'Viewing properties for user ID: ' + userId,
                position: 'topRight'
            });
            // You can implement this to filter the properties table
        }

        function toggleUserRole(userId, currentRole) {
            const newRole = currentRole === 'admin' ? 'user' : 'admin';
            const action = currentRole === 'admin' ? 'remove admin' : 'make admin';
            
            if (confirm(`Are you sure you want to ${action} for this user?`)) {
                $.ajax({
                    url: '../backend/toggle-user-role.php',
                    method: 'POST',
                    data: { 
                        id: userId, 
                        role: newRole 
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            iziToast.success({
                                title: 'Success',
                                message: response.message,
                                position: 'topRight'
                            });
                            // Refresh the page to update all roles
                            setTimeout(() => {
                                location.reload();
                            }, 1000);
                        } else {
                            iziToast.error({
                                title: 'Error',
                                message: response.message,
                                position: 'topRight'
                            });
                        }
                    },
                    error: function() {
                        iziToast.error({
                            title: 'Error',
                            message: 'Error updating user role. Please try again.',
                            position: 'topRight'
                        });
                    }
                });
            }
        }

        function deleteUser(userId) {
            if (confirm('Are you sure you want to delete this user? This action cannot be undone and will also delete all their properties.')) {
                $.ajax({
                    url: '../backend/delete-user.php',
                    method: 'POST',
                    data: { id: userId },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            iziToast.success({
                                title: 'Success',
                                message: 'User deleted successfully!',
                                position: 'topRight'
                            });
                            // Remove the row from the table
                            $(`.user-row[data-id="${userId}"]`).fadeOut(400, function() {
                                $(this).remove();
                                // Update stats immediately after row removal
                                updateUserStats();
                            });
                        } else {
                            iziToast.error({
                                title: 'Error',
                                message: response.message,
                                position: 'topRight'
                            });
                        }
                    },
                    error: function() {
                        iziToast.error({
                            title: 'Error',
                            message: 'Error deleting user. Please try again.',
                            position: 'topRight'
                        });
                    }
                });
            }
        }

        function updateUserStats() {
            // Update the stats cards with new counts
            const totalUsers = $('.user-row').length; // Count all rows, not just visible ones
            const adminUsers = $('.user-row[data-role="admin"]').length;
            const regularUsers = $('.user-row[data-role="user"]').length;
            const activeUsers = $('.user-row[data-status="active"]').length;
            
            console.log('Updating stats:', { totalUsers, adminUsers, regularUsers, activeUsers });
            
            // Update stats cards if they exist
            $('.stats-card h3').each(function() {
                const cardText = $(this).next('p').text().toLowerCase();
                if (cardText.includes('total users')) {
                    $(this).text(totalUsers);
                } else if (cardText.includes('admin users')) {
                    $(this).text(adminUsers);
                } else if (cardText.includes('regular users')) {
                    $(this).text(regularUsers);
                } else if (cardText.includes('active users')) {
                    $(this).text(activeUsers);
                }
            });
        }
        
        
        function updateApprovalRow(propertyId, newListingStatus) {
            const row = $(`.approval-row[data-id="${propertyId}"]`);
            if (row.length) {
                // Update the status badge
                const statusBadge = row.find("td:nth-child(8) .badge");
                if (statusBadge.length) {
                    statusBadge.removeClass("bg-success bg-danger bg-warning")
                        .addClass(newListingStatus === "approved" ? "bg-success" : 
                                 ((newListingStatus === "rejected" ? "bg-danger" : "bg-warning")))
                        .text(newListingStatus.charAt(0).toUpperCase() + newListingStatus.slice(1));
                }
                
                // Update the action buttons
                const actionButtons = row.find("td:nth-child(10) .btn-group");
                if (actionButtons.length) {
                    if (newListingStatus === "approved") {
                        actionButtons.html(`
                            <button class="btn btn-sm btn-outline-primary" 
                                    onclick="viewPropertyForApproval(${propertyId})"
                                    title="View Details">
                                <i class="fas fa-eye"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-danger" 
                                    onclick="toggleListingStatus(${propertyId}, 'approved')"
                                    title="Hide Property">
                                <i class="fas fa-times"></i>
                            </button>
                        `);
                    } else if (newListingStatus === "rejected") {
                        actionButtons.html(`
                            <button class="btn btn-sm btn-outline-primary" 
                                    onclick="viewPropertyForApproval(${propertyId})"
                                    title="View Details">
                                <i class="fas fa-eye"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-success" 
                                    onclick="toggleListingStatus(${propertyId}, 'rejected')"
                                    title="Show Property">
                                <i class="fas fa-check"></i>
                            </button>
                        `);
                    } else {
                        actionButtons.html(`
                            <button class="btn btn-sm btn-outline-primary" 
                                    onclick="viewPropertyForApproval(${propertyId})"
                                    title="View Details">
                                <i class="fas fa-eye"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-success" 
                                    onclick="approveProperty(${propertyId})"
                                    title="Approve Property">
                                <i class="fas fa-check"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-danger" 
                                    onclick="rejectProperty(${propertyId})"
                                    title="Reject Property">
                                <i class="fas fa-times"></i>
                            </button>
                        `);
                    }
                }
                
                // Update the data attribute
                row.attr("data-listing", newListingStatus);
            }
        }
        
        
        function updatePropertyStats() {
            // Update the stats cards with new counts
            const totalProperties = $(".property-row").length;
            const activeProperties = $(".property-row[data-status=\"active\"]").length;
            const pendingProperties = $(".property-row[data-listing=\"pending\"]").length;
            
            console.log("Updating property stats:", { totalProperties, activeProperties, pendingProperties });
            
            // Update stats cards if they exist
            $(".stats-card h3").each(function() {
                const cardText = $(this).next("p").text().toLowerCase();
                if (cardText.includes("total properties")) {
                    $(this).text(totalProperties);
                } else if (cardText.includes("active properties")) {
                    $(this).text(activeProperties);
                } else if (cardText.includes("pending properties")) {
                    $(this).text(pendingProperties);
                }
            });
        }
        
        
        function updateApprovalRow(propertyId, newListingStatus) {
            const row = $(`.approval-row[data-id="${propertyId}"]`);
            if (row.length) {
                // Update the status badge
                const statusBadge = row.find("td:nth-child(8) .badge");
                if (statusBadge.length) {
                    statusBadge.removeClass("bg-success bg-danger bg-warning")
                        .addClass(newListingStatus === "approved" ? "bg-success" : 
                                 ((newListingStatus === "rejected" ? "bg-danger" : "bg-warning")))
                        .text(newListingStatus.charAt(0).toUpperCase() + newListingStatus.slice(1));
                }
                
                // Update the action buttons
                const actionButtons = row.find("td:nth-child(10) .btn-group");
                if (actionButtons.length) {
                    if (newListingStatus === "approved") {
                        actionButtons.html(`
                            <button class="btn btn-sm btn-outline-primary" 
                                    onclick="viewPropertyForApproval(${propertyId})"
                                    title="View Details">
                                <i class="fas fa-eye"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-danger" 
                                    onclick="toggleListingStatus(${propertyId}, 'approved')"
                                    title="Hide Property">
                                <i class="fas fa-times"></i>
                            </button>
                        `);
                    } else if (newListingStatus === "rejected") {
                        actionButtons.html(`
                            <button class="btn btn-sm btn-outline-primary" 
                                    onclick="viewPropertyForApproval(${propertyId})"
                                    title="View Details">
                                <i class="fas fa-eye"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-success" 
                                    onclick="toggleListingStatus(${propertyId}, 'rejected')"
                                    title="Show Property">
                                <i class="fas fa-check"></i>
                            </button>
                        `);
                    } else {
                        actionButtons.html(`
                            <button class="btn btn-sm btn-outline-primary" 
                                    onclick="viewPropertyForApproval(${propertyId})"
                                    title="View Details">
                                <i class="fas fa-eye"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-success" 
                                    onclick="approveProperty(${propertyId})"
                                    title="Approve Property">
                                <i class="fas fa-check"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-danger" 
                                    onclick="rejectProperty(${propertyId})"
                                    title="Reject Property">
                                <i class="fas fa-times"></i>
                            </button>
                        `);
                    }
                }
                
                // Update the data attribute
                row.attr("data-listing", newListingStatus);
            }
        }

        // Transaction Management Functions (already defined above)

        function viewTransactionDetails(transactionId) {
            iziToast.info({
                title: 'Transaction Details',
                message: 'Transaction details feature coming soon!',
                position: 'topRight'
            });
        }
        
        
        function updateApprovalRow(propertyId, newListingStatus) {
            const row = $(`.approval-row[data-id="${propertyId}"]`);
            if (row.length) {
                // Update the status badge
                const statusBadge = row.find("td:nth-child(8) .badge");
                if (statusBadge.length) {
                    statusBadge.removeClass("bg-success bg-danger bg-warning")
                        .addClass(newListingStatus === "approved" ? "bg-success" : 
                                 ((newListingStatus === "rejected" ? "bg-danger" : "bg-warning")))
                        .text(newListingStatus.charAt(0).toUpperCase() + newListingStatus.slice(1));
                }
                
                // Update the action buttons
                const actionButtons = row.find("td:nth-child(10) .btn-group");
                if (actionButtons.length) {
                    if (newListingStatus === "approved") {
                        actionButtons.html(`
                            <button class="btn btn-sm btn-outline-primary" 
                                    onclick="viewPropertyForApproval(${propertyId})"
                                    title="View Details">
                                <i class="fas fa-eye"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-danger" 
                                    onclick="toggleListingStatus(${propertyId}, 'approved')"
                                    title="Hide Property">
                                <i class="fas fa-times"></i>
                            </button>
                        `);
                    } else if (newListingStatus === "rejected") {
                        actionButtons.html(`
                            <button class="btn btn-sm btn-outline-primary" 
                                    onclick="viewPropertyForApproval(${propertyId})"
                                    title="View Details">
                                <i class="fas fa-eye"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-success" 
                                    onclick="toggleListingStatus(${propertyId}, 'rejected')"
                                    title="Show Property">
                                <i class="fas fa-check"></i>
                            </button>
                        `);
                    } else {
                        actionButtons.html(`
                            <button class="btn btn-sm btn-outline-primary" 
                                    onclick="viewPropertyForApproval(${propertyId})"
                                    title="View Details">
                                <i class="fas fa-eye"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-success" 
                                    onclick="approveProperty(${propertyId})"
                                    title="Approve Property">
                                <i class="fas fa-check"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-danger" 
                                    onclick="rejectProperty(${propertyId})"
                                    title="Reject Property">
                                <i class="fas fa-times"></i>
                            </button>
                        `);
                    }
                }
                
                // Update the data attribute
                row.attr("data-listing", newListingStatus);
            }
        }
        
        
        function updatePropertyStats() {
            // Update the stats cards with new counts
            const totalProperties = $(".property-row").length;
            const activeProperties = $(".property-row[data-status=\"active\"]").length;
            const pendingProperties = $(".property-row[data-listing=\"pending\"]").length;
            
            console.log("Updating property stats:", { totalProperties, activeProperties, pendingProperties });
            
            // Update stats cards if they exist
            $(".stats-card h3").each(function() {
                const cardText = $(this).next("p").text().toLowerCase();
                if (cardText.includes("total properties")) {
                    $(this).text(totalProperties);
                } else if (cardText.includes("active properties")) {
                    $(this).text(activeProperties);
                } else if (cardText.includes("pending properties")) {
                    $(this).text(pendingProperties);
                }
            });
        }
        
        
        function updateApprovalRow(propertyId, newListingStatus) {
            const row = $(`.approval-row[data-id="${propertyId}"]`);
            if (row.length) {
                // Update the status badge
                const statusBadge = row.find("td:nth-child(8) .badge");
                if (statusBadge.length) {
                    statusBadge.removeClass("bg-success bg-danger bg-warning")
                        .addClass(newListingStatus === "approved" ? "bg-success" : 
                                 ((newListingStatus === "rejected" ? "bg-danger" : "bg-warning")))
                        .text(newListingStatus.charAt(0).toUpperCase() + newListingStatus.slice(1));
                }
                
                // Update the action buttons
                const actionButtons = row.find("td:nth-child(10) .btn-group");
                if (actionButtons.length) {
                    if (newListingStatus === "approved") {
                        actionButtons.html(`
                            <button class="btn btn-sm btn-outline-primary" 
                                    onclick="viewPropertyForApproval(${propertyId})"
                                    title="View Details">
                                <i class="fas fa-eye"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-danger" 
                                    onclick="toggleListingStatus(${propertyId}, 'approved')"
                                    title="Hide Property">
                                <i class="fas fa-times"></i>
                            </button>
                        `);
                    } else if (newListingStatus === "rejected") {
                        actionButtons.html(`
                            <button class="btn btn-sm btn-outline-primary" 
                                    onclick="viewPropertyForApproval(${propertyId})"
                                    title="View Details">
                                <i class="fas fa-eye"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-success" 
                                    onclick="toggleListingStatus(${propertyId}, 'rejected')"
                                    title="Show Property">
                                <i class="fas fa-check"></i>
                            </button>
                        `);
                    } else {
                        actionButtons.html(`
                            <button class="btn btn-sm btn-outline-primary" 
                                    onclick="viewPropertyForApproval(${propertyId})"
                                    title="View Details">
                                <i class="fas fa-eye"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-success" 
                                    onclick="approveProperty(${propertyId})"
                                    title="Approve Property">
                                <i class="fas fa-check"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-danger" 
                                    onclick="rejectProperty(${propertyId})"
                                    title="Reject Property">
                                <i class="fas fa-times"></i>
                            </button>
                        `);
                    }
                }
                
                // Update the data attribute
                row.attr("data-listing", newListingStatus);
            }
        }

        function viewPropertyDetails(propertyId) {
            if (propertyId) {
                const url = `../view-property-detail.php?id=${propertyId}`;
                window.open(url, '_blank');
            } else {
                iziToast.error({
                    title: 'Error',
                    message: 'Property not found',
                    position: 'topRight'
                });
            }
        }

        // Apply same validation as signup form
        $(document).ready(function() {
            // Phone field validation (same as signup)
            $('input[name="phone"]').on('input', function(e) {
                // Remove all non-digits
                let value = this.value.replace(/\D/g, '');
                // Limit to 11 digits (4 for code, 7 for number)
                value = value.substring(0, 11);

                // Format as 0300-1234567
                let formatted = value;
                if (value.length > 4) {
                    formatted = value.substring(0, 4) + '-' + value.substring(4, 11);
                }
                this.value = formatted;
            });

            // Phone field paste validation
            $('input[name="phone"]').on('paste', function(e) {
                let paste = (e.clipboardData || window.clipboardData).getData('text');
                let digits = paste.replace(/\D/g, '').substring(0, 11);
                let formatted = digits;
                if (digits.length > 4) {
                    formatted = digits.substring(0, 4) + '-' + digits.substring(4, 11);
                }
                e.preventDefault();
                this.value = formatted;
            });
            
            // Location field validation (same as name field in signup)
            $('input[name="location"]').on('input', function(e) {
                // Only allow letters, spaces, and common punctuation
                let value = this.value.replace(/[^A-Za-z\s,.\-]/g, '');
                if (this.value !== value) {
                    this.value = value;
                }
            });

            // Location field paste validation
            $('input[name="location"]').on('paste', function(e) {
                let paste = (e.clipboardData || window.clipboardData).getData('text');
                let filtered = paste.replace(/[^A-Za-z\s,.\-]/g, '');
                e.preventDefault();
                // Insert filtered text at cursor position
                const start = this.selectionStart;
                const end = this.selectionEnd;
                this.value = this.value.slice(0, start) + filtered + this.value.slice(end);
                // Move cursor to end of inserted text
                this.selectionStart = this.selectionEnd = start + filtered.length;
            });

            // Full Name field validation (same as name field in signup)
            $('input[name="full_name"]').on('input', function(e) {
                // Only allow letters and spaces
                let value = this.value.replace(/[^A-Za-z\s]/g, '');
                if (this.value !== value) {
                    this.value = value;
                }
            });

            // Full Name field paste validation
            $('input[name="full_name"]').on('paste', function(e) {
                let paste = (e.clipboardData || window.clipboardData).getData('text');
                let filtered = paste.replace(/[^A-Za-z\s]/g, '');
                e.preventDefault();
                // Insert filtered text at cursor position
                const start = this.selectionStart;
                const end = this.selectionEnd;
                this.value = this.value.slice(0, start) + filtered + this.value.slice(end);
                // Move cursor to end of inserted text
                this.selectionStart = this.selectionEnd = start + filtered.length;
            });

            // CNIC field validation (same as signup)
            $('input[name="cnic"]').on('input', function(e) {
                let value = this.value.replace(/\D/g, ''); // Remove all non-digits
                if (value.length > 13) value = value.slice(0, 13); // Max 13 digits

                let formatted = '';
                if (value.length > 5) {
                    formatted += value.slice(0, 5) + '-';
                    if (value.length > 12) {
                        formatted += value.slice(5, 12) + '-' + value.slice(12, 13);
                    } else if (value.length > 5) {
                        formatted += value.slice(5, 12);
                    }
                } else {
                    formatted = value;
                }
                if (value.length > 12) {
                    formatted = value.slice(0, 5) + '-' + value.slice(5, 12) + '-' + value.slice(12, 13);
                }
                this.value = formatted;
            });

            // CNIC field paste validation
            $('input[name="cnic"]').on('paste', function(e) {
                let paste = (e.clipboardData || window.clipboardData).getData('text');
                let digits = paste.replace(/\D/g, '').substring(0, 13);
                let formatted = digits;
                if (digits.length > 5) {
                    formatted = digits.slice(0, 5) + '-';
                    if (digits.length > 12) {
                        formatted += digits.slice(5, 12) + '-' + digits.slice(12, 13);
                    } else if (digits.length > 5) {
                        formatted += digits.slice(5, 12);
                    }
                }
                e.preventDefault();
                this.value = formatted;
            });

            // Bio field character counter
            $('textarea[name="bio"]').on('input', function() {
                const maxLength = 500;
                const currentLength = this.value.length;
                const remaining = maxLength - currentLength;
                
                // Update character counter
                let counter = $(this).siblings('.char-counter');
                if (counter.length === 0) {
                    counter = $('<div class="form-text char-counter"></div>');
                    $(this).after(counter);
                }
                
                counter.text(`${currentLength}/${maxLength} characters`);
                
                if (currentLength > maxLength) {
                    this.value = this.value.substring(0, maxLength);
                    counter.text(`${maxLength}/${maxLength} characters`);
                }
                
                // Change color based on remaining characters
                if (remaining <= 50) {
                    counter.addClass('text-warning');
                } else {
                    counter.removeClass('text-warning');
                }
                
                if (remaining <= 10) {
                    counter.addClass('text-danger');
                } else {
                    counter.removeClass('text-danger');
                }
            });

            // Real-time validation feedback
            $('input[name="full_name"]').on('blur', function() {
                const pattern = /^[A-Za-z\s]+$/;
                if (this.value && !pattern.test(this.value)) {
                    $(this).addClass('is-invalid').removeClass('is-valid');
                } else if (this.value) {
                    $(this).addClass('is-valid').removeClass('is-invalid');
                } else {
                    $(this).removeClass('is-valid is-invalid');
                }
            });

            $('input[name="phone"]').on('blur', function() {
                const pattern = /^[0-9]{4}-[0-9]{7}$/;
                if (this.value && !pattern.test(this.value)) {
                    $(this).addClass('is-invalid').removeClass('is-valid');
                } else if (this.value) {
                    $(this).addClass('is-valid').removeClass('is-invalid');
                } else {
                    $(this).removeClass('is-valid is-invalid');
                }
            });

            $('input[name="location"]').on('blur', function() {
                const pattern = /^[A-Za-z\s,.\-]+$/;
                if (this.value && !pattern.test(this.value)) {
                    $(this).addClass('is-invalid').removeClass('is-valid');
                } else if (this.value) {
                    $(this).addClass('is-valid').removeClass('is-invalid');
                } else {
                    $(this).removeClass('is-valid is-invalid');
                }
            });

            $('input[name="cnic"]').on('blur', function() {
                const pattern = /^[0-9]{5}-[0-9]{7}-[0-9]{1}$/;
                if (this.value && !pattern.test(this.value)) {
                    $(this).addClass('is-invalid').removeClass('is-valid');
                } else if (this.value) {
                    $(this).addClass('is-valid').removeClass('is-invalid');
                } else {
                    $(this).removeClass('is-valid is-invalid');
                }
            });


        }
        
        
        function updateApprovalRow(propertyId, newListingStatus) {
            const row = $(`.approval-row[data-id="${propertyId}"]`);
            if (row.length) {
                // Update the status badge
                const statusBadge = row.find("td:nth-child(8) .badge");
                if (statusBadge.length) {
                    statusBadge.removeClass("bg-success bg-danger bg-warning")
                        .addClass(newListingStatus === "approved" ? "bg-success" : 
                                 ((newListingStatus === "rejected" ? "bg-danger" : "bg-warning")))
                        .text(newListingStatus.charAt(0).toUpperCase() + newListingStatus.slice(1));
                }
                
                // Update the action buttons
                const actionButtons = row.find("td:nth-child(10) .btn-group");
                if (actionButtons.length) {
                    if (newListingStatus === "approved") {
                        actionButtons.html(`
                            <button class="btn btn-sm btn-outline-primary" 
                                    onclick="viewPropertyForApproval(${propertyId})"
                                    title="View Details">
                                <i class="fas fa-eye"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-danger" 
                                    onclick="toggleListingStatus(${propertyId}, 'approved')"
                                    title="Hide Property">
                                <i class="fas fa-times"></i>
                            </button>
                        `);
                    } else if (newListingStatus === "rejected") {
                        actionButtons.html(`
                            <button class="btn btn-sm btn-outline-primary" 
                                    onclick="viewPropertyForApproval(${propertyId})"
                                    title="View Details">
                                <i class="fas fa-eye"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-success" 
                                    onclick="toggleListingStatus(${propertyId}, 'rejected')"
                                    title="Show Property">
                                <i class="fas fa-check"></i>
                            </button>
                        `);
                    } else {
                        actionButtons.html(`
                            <button class="btn btn-sm btn-outline-primary" 
                                    onclick="viewPropertyForApproval(${propertyId})"
                                    title="View Details">
                                <i class="fas fa-eye"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-success" 
                                    onclick="approveProperty(${propertyId})"
                                    title="Approve Property">
                                <i class="fas fa-check"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-danger" 
                                    onclick="rejectProperty(${propertyId})"
                                    title="Reject Property">
                                <i class="fas fa-times"></i>
                            </button>
                        `);
                    }
                }
                
                // Update the data attribute
                row.attr("data-listing", newListingStatus);
            }
        }
        
        
        // function updatePropertyStats() {
        //     // Update the stats cards with new counts
        //     const totalProperties = $(".property-row").length;
        //     const activeProperties = $(".property-row[data-status=\"active\"]").length;
        //     const pendingProperties = $(".property-row[data-listing=\"pending\"]").length;
            
        //     console.log("Updating property stats:", { totalProperties, activeProperties, pendingProperties });
            
        //     // Update stats cards if they exist
        //     $(".stats-card h3").each(function() {
        //         const cardText = $(this).next("p").text().toLowerCase();
        //         if (cardText.includes("total properties")) {
        //             $(this).text(totalProperties);
        //         } else if (cardText.includes("active properties")) {
        //             $(this).text(activeProperties);
        //         } else if (cardText.includes("pending properties")) {
        //             $(this).text(pendingProperties);
        //         }
        //     });
        // }
        
        
        // function updateApprovalRow(propertyId, newListingStatus) {
        //     const row = $(`.approval-row[data-id="${propertyId}"]`);
        //     if (row.length) {
        //         // Update the status badge
        //         const statusBadge = row.find("td:nth-child(8) .badge");
        //         if (statusBadge.length) {
        //             statusBadge.removeClass("bg-success bg-danger bg-warning")
        //                 .addClass(newListingStatus === "approved" ? "bg-success" : 
        //                          ((newListingStatus === "rejected" ? "bg-danger" : "bg-warning")))
        //                 .text(newListingStatus.charAt(0).toUpperCase() + newListingStatus.slice(1));
        //         }
                
        //         // Update the action buttons
        //         const actionButtons = row.find("td:nth-child(10) .btn-group");
        //         if (actionButtons.length) {
        //             if (newListingStatus === "approved") {
        //                 actionButtons.html(`
        //                     <button class="btn btn-sm btn-outline-primary" 
        //                             onclick="viewPropertyForApproval(${propertyId})"
        //                             title="View Details">
        //                         <i class="fas fa-eye"></i>
        //                     </button>
        //                     <button class="btn btn-sm btn-outline-danger" 
        //                             onclick="toggleListingStatus(${propertyId}, 'approved')"
        //                             title="Hide Property">
        //                         <i class="fas fa-times"></i>
        //                     </button>
        //                 `);
        //             } else if (newListingStatus === "rejected") {
        //                 actionButtons.html(`
        //                     <button class="btn btn-sm btn-outline-primary" 
        //                             onclick="viewPropertyForApproval(${propertyId})"
        //                             title="View Details">
        //                         <i class="fas fa-eye"></i>
        //                     </button>
        //                     <button class="btn btn-sm btn-outline-success" 
        //                             onclick="toggleListingStatus(${propertyId}, 'rejected')"
        //                             title="Show Property">
        //                         <i class="fas fa-check"></i>
        //                     </button>
        //                 `);
        //             } else {
        //                 actionButtons.html(`
        //                     <button class="btn btn-sm btn-outline-primary" 
        //                             onclick="viewPropertyForApproval(${propertyId})"
        //                             title="View Details">
        //                         <i class="fas fa-eye"></i>
        //                     </button>
        //                     <button class="btn btn-sm btn-outline-success" 
        //                             onclick="approveProperty(${propertyId})"
        //                             title="Approve Property">
        //                         <i class="fas fa-check"></i>
        //                     </button>
        //                     <button class="btn btn-sm btn-outline-danger" 
        //                             onclick="rejectProperty(${propertyId})"
        //                             title="Reject Property">
        //                         <i class="fas fa-times"></i>
        //                     </button>
        //                 `);
        //             }
        //         }
                
        //         // Update the data attribute
        //         row.attr("data-listing", newListingStatus);
        //     }
        // }
        
        
        function updatePropertyStats() {
            // Update the stats cards with new counts
            const totalProperties = $(".property-row").length;
            const activeProperties = $(".property-row[data-status=\"active\"]").length;
            const pendingProperties = $(".property-row[data-listing=\"pending\"]").length;
            
            console.log("Updating property stats:", { totalProperties, activeProperties, pendingProperties });
            
            // Update stats cards if they exist
            $(".stats-card h3").each(function() {
                const cardText = $(this).next("p").text().toLowerCase();
                if (cardText.includes("total properties")) {
                    $(this).text(totalProperties);
                } else if (cardText.includes("active properties")) {
                    $(this).text(activeProperties);
                } else if (cardText.includes("pending properties")) {
                    $(this).text(pendingProperties);
                }
            });
        }
        
        
        function updateApprovalRow(propertyId, newListingStatus) {
            const row = $(`.approval-row[data-id="${propertyId}"]`);
            if (row.length) {
                // Update the status badge
                const statusBadge = row.find("td:nth-child(8) .badge");
                if (statusBadge.length) {
                    statusBadge.removeClass("bg-success bg-danger bg-warning")
                        .addClass(newListingStatus === "approved" ? "bg-success" : 
                                 ((newListingStatus === "rejected" ? "bg-danger" : "bg-warning")))
                        .text(newListingStatus.charAt(0).toUpperCase() + newListingStatus.slice(1));
                }
                
                // Update the action buttons
                const actionButtons = row.find("td:nth-child(10) .btn-group");
                if (actionButtons.length) {
                    if (newListingStatus === "approved") {
                        actionButtons.html(`
                            <button class="btn btn-sm btn-outline-primary" 
                                    onclick="viewPropertyForApproval(${propertyId})"
                                    title="View Details">
                                <i class="fas fa-eye"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-danger" 
                                    onclick="toggleListingStatus(${propertyId}, 'approved')"
                                    title="Hide Property">
                                <i class="fas fa-times"></i>
                            </button>
                        `);
                    } else if (newListingStatus === "rejected") {
                        actionButtons.html(`
                            <button class="btn btn-sm btn-outline-primary" 
                                    onclick="viewPropertyForApproval(${propertyId})"
                                    title="View Details">
                                <i class="fas fa-eye"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-success" 
                                    onclick="toggleListingStatus(${propertyId}, 'rejected')"
                                    title="Show Property">
                                <i class="fas fa-check"></i>
                            </button>
                        `);
                    } else {
                        actionButtons.html(`
                            <button class="btn btn-sm btn-outline-primary" 
                                    onclick="viewPropertyForApproval(${propertyId})"
                                    title="View Details">
                                <i class="fas fa-eye"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-success" 
                                    onclick="approveProperty(${propertyId})"
                                    title="Approve Property">
                                <i class="fas fa-check"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-danger" 
                                    onclick="rejectProperty(${propertyId})"
                                    title="Reject Property">
                                <i class="fas fa-times"></i>
                            </button>
                        `);
                    }
                }
                
                // Update the data attribute
                row.attr("data-listing", newListingStatus);
            }
        });

        $(document).ready(function() { // Temporary debug
            console.log('Document ready triggered');
            
            // Add global error handler
            window.addEventListener('error', function(e) {
                console.error('JavaScript error:', e.error);
                alert('JavaScript error: ' + e.error.message);
            });
            
            // Only fetch saved properties for non-admin users
            <?php if (!isset($userRole) || strtolower($userRole) !== 'admin'): ?>
            fetchSavedProperties();
            <?php endif; ?>

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
                        $('#profileForm input[name="cnic"]').val(response.data.cnic || '')
                            .attr('placeholder', response.data.cnic ? '' : 'Enter your CNIC number');
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
                console.log('Profile form submit triggered');
                alert('Form submit triggered!');

                const form = this;
                let isValid = true;
                let validationErrors = [];

                // Debug: Log all form fields
                console.log('Form fields:');
                $(form).find('input, textarea').each(function() {
                    console.log($(this).attr('name') + ': ' + $(this).val());
                });

                // Validate required fields
                $(form).find('input[required]').each(function() {
                    console.log('Checking required field:', $(this).attr('name'), 'Value:', $(this).val());
                    if (!this.checkValidity()) {
                        isValid = false;
                        $(this).addClass('is-invalid');
                        validationErrors.push($(this).attr('name') + ' is required');
                        console.log('Required field validation failed:', $(this).attr('name'));
                    } else {
                        $(this).removeClass('is-invalid');
                        console.log('Required field validation passed:', $(this).attr('name'));
                    }
                });

                // Custom validation for phone format
                const phoneInput = $('input[name="phone"]');
                const phonePattern = /^[0-9]{4}-[0-9]{7}$/;
                console.log('Phone validation - Value:', phoneInput.val(), 'Pattern match:', phonePattern.test(phoneInput.val()));
                if (phoneInput.val() && !phonePattern.test(phoneInput.val())) {
                    isValid = false;
                    phoneInput.addClass('is-invalid');
                    validationErrors.push('Phone number must be in format: 03XX-XXXXXXX');
                    console.log('Phone validation failed');
                } else if (phoneInput.val()) {
                    phoneInput.removeClass('is-invalid');
                    console.log('Phone validation passed');
                }

                // Custom validation for CNIC format
                const cnicInput = $('input[name="cnic"]');
                const cnicPattern = /^[0-9]{5}-[0-9]{7}-[0-9]{1}$/;
                console.log('CNIC validation - Value:', cnicInput.val(), 'Pattern match:', cnicPattern.test(cnicInput.val()));
                if (cnicInput.val() && !cnicPattern.test(cnicInput.val())) {
                    isValid = false;
                    cnicInput.addClass('is-invalid');
                    validationErrors.push('CNIC must be in format: XXXXX-XXXXXXX-X');
                    console.log('CNIC validation failed');
                } else if (cnicInput.val()) {
                    cnicInput.removeClass('is-invalid');
                    console.log('CNIC validation passed');
                }

                // Show debug toast with validation results
                if (!isValid) {
                    console.log('Validation failed. Errors:', validationErrors);
                    iziToast.error({
                        title: 'Validation Failed',
                        message: 'Please fix these errors:\n' + validationErrors.join('\n'),
                        position: 'topRight',
                        timeout: 5000
                    });
                    return false;
                }

                // If validation passes, proceed with form submission
                console.log('Validation passed, creating FormData');
                iziToast.info({
                    title: 'Processing',
                    message: 'Saving profile changes...',
                    position: 'topRight'
                });
                
                var formData = new FormData(this);
                
                // Log form data for debugging
                for (let pair of formData.entries()) {
                    console.log(pair[0] + ': ' + pair[1]);
                }

                $.ajax({
                    url: '../backend/update-user-details.php',
                    type: 'POST',
                    data: formData,
                    contentType: false,
                    processData: false,
                    dataType: 'json',
                    beforeSend: function() {
                        console.log('Sending profile update request...');
                        iziToast.info({
                            title: 'Sending Request',
                            message: 'Submitting profile changes to server...',
                            position: 'topRight'
                        });
                    },
                    success: function(response) {
                        console.log('Profile Update Response:', response);
                        
                        // Debug toast to show the response
                        iziToast.info({
                            title: 'Server Response',
                            message: 'Response received: ' + JSON.stringify(response),
                            position: 'topRight',
                            timeout: 3000
                        });

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
                                title: 'Server Error',
                                message: response.message || 'Failed to update profile.',
                                position: 'topRight'
                            });
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Profile update error:', xhr.responseText);
                        console.error('Status:', status);
                        console.error('Error:', error);
                        
                        // Debug toast to show the error details
                        iziToast.error({
                            title: 'AJAX Error',
                            message: 'Status: ' + status + '\nError: ' + error + '\nResponse: ' + xhr.responseText,
                            position: 'topRight',
                            timeout: 5000
                        });
                    }
                });
            });

            // ----------------- Profile Picture Handling -------------------
            // Handle file input change for profile picture preview
            let isProcessingFile = false;
            
            // Remove any existing event handlers and add new one
            $('#profilePictureInput').off('change').on('change', function() {
                console.log('File input change event triggered');
                console.log('Files selected:', this.files.length);
                if (isProcessingFile) {
                    console.log('Already processing file, skipping');
                    return;
                }
                isProcessingFile = true;
                
                const file = this.files[0];
                if (file) {
                    console.log('File selected:', file.name, file.type, file.size);
                    
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
                        console.log('Image preview loaded');
                        $('.profile-avatar').attr('src', e.target.result);
                        isProcessingFile = false;
                    };
                    reader.onerror = function() {
                        console.error('Error reading file');
                        iziToast.error({
                            title: 'Error',
                            message: 'Failed to read the image file',
                            position: 'topRight'
                        });
                        isProcessingFile = false;
                    };
                    reader.readAsDataURL(file);
                } else {
                    console.log('No file selected');
                    isProcessingFile = false;
                }
            });

            // Handle upload button click
            $('#uploadPictureBtn').off('click').on('click', function(e) {
                e.preventDefault();
                console.log('Upload button clicked');
                $('#profilePictureInput').click();
            });



            // Test if file input exists and is accessible
            console.log('File input element:', $('#profilePictureInput').length);
            console.log('Upload button element:', $('#uploadPictureBtn').length);
            
            // Add a test button to manually trigger file input (for debugging)
            if ($('#uploadPictureBtn').length === 0) {
                console.error('Upload button not found!');
            } else {
                console.log('Upload button found and ready');
            }

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
                        <p class="card-text"><i class="fas fa-map-marker-alt"></i> ${property.city || 'Location not specified'}</p>
                        <div class="property-features">
                            <span><i class="fas fa-ruler-combined"></i> ${property.area} ${property.unit}</span>
                            <span><i class="fas fa-home"></i> ${property.type}</span>
                        </div>
                    </div>
                    <div class="card-footer bg-white">
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="property-stats">
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
                    const res = await fetch('../backend/fetch-user-saved-properties.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' }
                    });
                    console.log('Raw fetch response:', res);

                    if (!res.ok) {
                        throw new Error(`HTTP error! Status: ${res.status}`);
                    }

                    const data = await res.json();
                    console.log('fetch-user-saved-properties response:', data);
                    console.log('Response status:', data.status);
                    console.log('Properties array:', data.properties);

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
                                <p class="card-text"><i class="fas fa-map-marker-alt"></i> ${property.city || 'Location not specified'}</p>
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
        console.log('Setting up change password form handler');
        console.log('jQuery available:', typeof $ !== 'undefined');
        console.log('Form exists:', $('#changePasswordForm').length > 0);
        console.log('iziToast available:', typeof iziToast !== 'undefined');
        
        // Test if button exists
        console.log('Change password button exists:', $('#changePasswordBtn').length > 0);
        
        $('#changePasswordBtn').on('click', function(e) {
            console.log('Change password button clicked');
            e.preventDefault();
            e.stopPropagation();
            
            // Add visual feedback to confirm button is being clicked
            $(this).addClass('btn-success').removeClass('btn-primary');
            setTimeout(() => {
                $(this).removeClass('btn-success').addClass('btn-primary');
            }, 200);
            
            console.log('Change password form submitted');
            
            // Get form values
            const currentPassword = $('#currentPassword').val();
            const newPassword = $('#newPassword').val();
            const confirmPassword = $('#confirmPassword').val();
            
            console.log('Form values:', {
                currentPassword: currentPassword ? 'filled' : 'empty',
                newPassword: newPassword ? 'filled' : 'empty',
                confirmPassword: confirmPassword ? 'filled' : 'empty'
            });
            
            console.log('Form data:', {
                currentPassword: currentPassword ? 'filled' : 'empty',
                newPassword: newPassword ? 'filled' : 'empty',
                confirmPassword: confirmPassword ? 'filled' : 'empty'
            });
            
            // Validation
            if (!currentPassword || !newPassword || !confirmPassword) {
                console.log('Validation failed: missing fields');
                if (typeof iziToast !== 'undefined') {
                    iziToast.error({
                        title: 'Error',
                        message: 'Please fill in all fields.',
                        position: 'topRight'
                    });
                } else {
                    alert('Please fill in all fields.');
                }
                return;
            }
            
            if (newPassword !== confirmPassword) {
                console.log('Validation failed: passwords do not match');
                if (typeof iziToast !== 'undefined') {
                    iziToast.error({
                        title: 'Error',
                        message: 'New password and confirm password do not match.',
                        position: 'topRight'
                    });
                } else {
                    alert('New password and confirm password do not match.');
                }
                return;
            }
            
            if (newPassword.length < 6) {
                console.log('Validation failed: password too short');
                if (typeof iziToast !== 'undefined') {
                    iziToast.error({
                        title: 'Error',
                        message: 'New password must be at least 6 characters long.',
                        position: 'topRight'
                    });
                } else {
                    alert('New password must be at least 6 characters long.');
                }
                return;
            }
            
            console.log('Sending AJAX request to change password');
            
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
                    console.log('Change password response:', response);
                    if (response.success) {
                        if (typeof iziToast !== 'undefined') {
                            iziToast.success({
                                title: 'Success',
                                message: 'Password changed successfully!',
                                position: 'topRight'
                            });
                        } else {
                            alert('Password changed successfully!');
                        }
                        $('#changePasswordForm')[0].reset();
                    } else {
                        if (typeof iziToast !== 'undefined') {
                            iziToast.error({
                                title: 'Error',
                                message: response.message || 'Failed to change password.',
                                position: 'topRight'
                            });
                        } else {
                            alert(response.message || 'Failed to change password.');
                        }
                    }
                },
                error: function(xhr, status, error) {
                    console.log('Change password error:', {xhr, status, error});
                    if (typeof iziToast !== 'undefined') {
                        iziToast.error({
                            title: 'Error',
                            message: 'An error occurred while changing password.',
                            position: 'topRight'
                        });
                    } else {
                        alert('An error occurred while changing password.');
                    }
                }
            });
        }
        
        
        function updateApprovalRow(propertyId, newListingStatus) {
            const row = $(`.approval-row[data-id="${propertyId}"]`);
            if (row.length) {
                // Update the status badge
                const statusBadge = row.find("td:nth-child(8) .badge");
                if (statusBadge.length) {
                    statusBadge.removeClass("bg-success bg-danger bg-warning")
                        .addClass(newListingStatus === "approved" ? "bg-success" : 
                                 ((newListingStatus === "rejected" ? "bg-danger" : "bg-warning")))
                        .text(newListingStatus.charAt(0).toUpperCase() + newListingStatus.slice(1));
                }
                
                // Update the action buttons
                const actionButtons = row.find("td:nth-child(10) .btn-group");
                if (actionButtons.length) {
                    if (newListingStatus === "approved") {
                        actionButtons.html(`
                            <button class="btn btn-sm btn-outline-primary" 
                                    onclick="viewPropertyForApproval(${propertyId})"
                                    title="View Details">
                                <i class="fas fa-eye"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-danger" 
                                    onclick="toggleListingStatus(${propertyId}, 'approved')"
                                    title="Hide Property">
                                <i class="fas fa-times"></i>
                            </button>
                        `);
                    } else if (newListingStatus === "rejected") {
                        actionButtons.html(`
                            <button class="btn btn-sm btn-outline-primary" 
                                    onclick="viewPropertyForApproval(${propertyId})"
                                    title="View Details">
                                <i class="fas fa-eye"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-success" 
                                    onclick="toggleListingStatus(${propertyId}, 'rejected')"
                                    title="Show Property">
                                <i class="fas fa-check"></i>
                            </button>
                        `);
                    } else {
                        actionButtons.html(`
                            <button class="btn btn-sm btn-outline-primary" 
                                    onclick="viewPropertyForApproval(${propertyId})"
                                    title="View Details">
                                <i class="fas fa-eye"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-success" 
                                    onclick="approveProperty(${propertyId})"
                                    title="Approve Property">
                                <i class="fas fa-check"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-danger" 
                                    onclick="rejectProperty(${propertyId})"
                                    title="Reject Property">
                                <i class="fas fa-times"></i>
                            </button>
                        `);
                    }
                }
                
                // Update the data attribute
                row.attr("data-listing", newListingStatus);
            }
        }
        
        
        function updatePropertyStats() {
            // Update the stats cards with new counts
            const totalProperties = $(".property-row").length;
            const activeProperties = $(".property-row[data-status=\"active\"]").length;
            const pendingProperties = $(".property-row[data-listing=\"pending\"]").length;
            
            console.log("Updating property stats:", { totalProperties, activeProperties, pendingProperties });
            
            // Update stats cards if they exist
            $(".stats-card h3").each(function() {
                const cardText = $(this).next("p").text().toLowerCase();
                if (cardText.includes("total properties")) {
                    $(this).text(totalProperties);
                } else if (cardText.includes("active properties")) {
                    $(this).text(activeProperties);
                } else if (cardText.includes("pending properties")) {
                    $(this).text(pendingProperties);
                }
            });
        }
        
        
        function updateApprovalRow(propertyId, newListingStatus) {
            const row = $(`.approval-row[data-id="${propertyId}"]`);
            if (row.length) {
                // Update the status badge
                const statusBadge = row.find("td:nth-child(8) .badge");
                if (statusBadge.length) {
                    statusBadge.removeClass("bg-success bg-danger bg-warning")
                        .addClass(newListingStatus === "approved" ? "bg-success" : 
                                 ((newListingStatus === "rejected" ? "bg-danger" : "bg-warning")))
                        .text(newListingStatus.charAt(0).toUpperCase() + newListingStatus.slice(1));
                }
                
                // Update the action buttons
                const actionButtons = row.find("td:nth-child(10) .btn-group");
                if (actionButtons.length) {
                    if (newListingStatus === "approved") {
                        actionButtons.html(`
                            <button class="btn btn-sm btn-outline-primary" 
                                    onclick="viewPropertyForApproval(${propertyId})"
                                    title="View Details">
                                <i class="fas fa-eye"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-danger" 
                                    onclick="toggleListingStatus(${propertyId}, 'approved')"
                                    title="Hide Property">
                                <i class="fas fa-times"></i>
                            </button>
                        `);
                    } else if (newListingStatus === "rejected") {
                        actionButtons.html(`
                            <button class="btn btn-sm btn-outline-primary" 
                                    onclick="viewPropertyForApproval(${propertyId})"
                                    title="View Details">
                                <i class="fas fa-eye"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-success" 
                                    onclick="toggleListingStatus(${propertyId}, 'rejected')"
                                    title="Show Property">
                                <i class="fas fa-check"></i>
                            </button>
                        `);
                    } else {
                        actionButtons.html(`
                            <button class="btn btn-sm btn-outline-primary" 
                                    onclick="viewPropertyForApproval(${propertyId})"
                                    title="View Details">
                                <i class="fas fa-eye"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-success" 
                                    onclick="approveProperty(${propertyId})"
                                    title="Approve Property">
                                <i class="fas fa-check"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-danger" 
                                    onclick="rejectProperty(${propertyId})"
                                    title="Reject Property">
                                <i class="fas fa-times"></i>
                            </button>
                        `);
                    }
                }
                
                // Update the data attribute
                row.attr("data-listing", newListingStatus);
            }
        }

        // Password visibility toggle event listeners
        function setupPasswordToggles() {
            console.log('Setting up password toggles...');
            
            // Simple toggle function
            function togglePasswordVisibility(inputSelector, buttonSelector) {
                const input = $(inputSelector);
                const button = $(buttonSelector);
                const icon = button.find('i');
                
                console.log('Toggling password for:', inputSelector);
                console.log('Current type:', input.attr('type'));
                
                if (input.attr('type') === 'password') {
                    input.attr('type', 'text');
                    icon.removeClass('fa-eye').addClass('fa-eye-slash');
                    console.log('Changed to text, icon to eye-slash');
                } else {
                    input.attr('type', 'password');
                    icon.removeClass('fa-eye-slash').addClass('fa-eye');
                    console.log('Changed to password, icon to eye');
                }
            }
            
            // Set up event listeners
            $('#toggleCurrentPassword').off('click').on('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                console.log('Toggle current password clicked');
                togglePasswordVisibility('#currentPassword', '#toggleCurrentPassword');
            });
            
            $('#toggleNewPassword').off('click').on('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                console.log('Toggle new password clicked');
                togglePasswordVisibility('#newPassword', '#toggleNewPassword');
            });
            
            $('#toggleConfirmPassword').off('click').on('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                console.log('Toggle confirm password clicked');
                togglePasswordVisibility('#confirmPassword', '#toggleConfirmPassword');
            });
            
            console.log('Password toggles setup complete');
        }
        
        // Set up password toggles when document is ready
        $(document).ready(function() {
            console.log('Document ready - setting up password toggle listeners');
            console.log('Toggle elements exist:', {
                current: $('#toggleCurrentPassword').length > 0,
                new: $('#toggleNewPassword').length > 0,
                confirm: $('#toggleConfirmPassword').length > 0
            });
            
            // Initial setup
            setupPasswordToggles();
            
            // Also set up when navigating to account settings section
            $(document).on('click', '[data-section="account-settings"]', function() {
                setTimeout(setupPasswordToggles, 100);
            });
            
            // Fallback: Also try vanilla JavaScript approach
            setTimeout(function() {
                console.log('Trying vanilla JavaScript fallback for password toggles');
                
                const toggleCurrent = document.getElementById('toggleCurrentPassword');
                const toggleNew = document.getElementById('toggleNewPassword');
                const toggleConfirm = document.getElementById('toggleConfirmPassword');
                
                if (toggleCurrent) {
                    toggleCurrent.addEventListener('click', function(e) {
                        e.preventDefault();
                        console.log('Vanilla JS: Toggle current password clicked');
                        const input = document.getElementById('currentPassword');
                        const icon = this.querySelector('i');
                        
                        if (input.type === 'password') {
                            input.type = 'text';
                            icon.classList.remove('fa-eye');
                            icon.classList.add('fa-eye-slash');
                        } else {
                            input.type = 'password';
                            icon.classList.remove('fa-eye-slash');
                            icon.classList.add('fa-eye');
                        }
                    });
                }
                
                if (toggleNew) {
                    toggleNew.addEventListener('click', function(e) {
                        e.preventDefault();
                        console.log('Vanilla JS: Toggle new password clicked');
                        const input = document.getElementById('newPassword');
                        const icon = this.querySelector('i');
                        
                        if (input.type === 'password') {
                            input.type = 'text';
                            icon.classList.remove('fa-eye');
                            icon.classList.add('fa-eye-slash');
                        } else {
                            input.type = 'password';
                            icon.classList.remove('fa-eye-slash');
                            icon.classList.add('fa-eye');
                        }
                    });
                }
                
                if (toggleConfirm) {
                    toggleConfirm.addEventListener('click', function(e) {
                        e.preventDefault();
                        console.log('Vanilla JS: Toggle confirm password clicked');
                        const input = document.getElementById('confirmPassword');
                        const icon = this.querySelector('i');
                        
                        if (input.type === 'password') {
                            input.type = 'text';
                            icon.classList.remove('fa-eye');
                            icon.classList.add('fa-eye-slash');
                        } else {
                            input.type = 'password';
                            icon.classList.remove('fa-eye-slash');
                            icon.classList.add('fa-eye');
                        }
                    });
                }
            }, 500);
            
            // Fallback: Also try vanilla JavaScript approach for change password button
            setTimeout(function() {
                console.log('Setting up vanilla JS fallback for change password button');
                const changePasswordBtn = document.getElementById('changePasswordBtn');
                if (changePasswordBtn) {
                    changePasswordBtn.addEventListener('click', function(e) {
                        e.preventDefault();
                        console.log('Vanilla JS: Change password button clicked');
                        
                        // Get form values
                        const currentPassword = document.getElementById('currentPassword').value;
                        const newPassword = document.getElementById('newPassword').value;
                        const confirmPassword = document.getElementById('confirmPassword').value;
                        
                        console.log('Vanilla JS form values:', {
                            currentPassword: currentPassword ? 'filled' : 'empty',
                            newPassword: newPassword ? 'filled' : 'empty',
                            confirmPassword: confirmPassword ? 'filled' : 'empty'
                        });
                        
                        // Validation
                        if (!currentPassword || !newPassword || !confirmPassword) {
                            alert('Please fill in all fields.');
                            return;
                        }
                        
                        if (newPassword !== confirmPassword) {
                            alert('New password and confirm password do not match.');
                            return;
                        }
                        
                        if (newPassword.length < 6) {
                            alert('New password must be at least 6 characters long.');
                            return;
                        }
                        
                        console.log('Vanilla JS: Sending AJAX request');
                        
                        // Use fetch instead of jQuery AJAX
                        fetch('../backend/change-password.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded',
                            },
                            body: new URLSearchParams({
                                current_password: currentPassword,
                                new_password: newPassword,
                                confirm_password: confirmPassword
                            })
                        })
                        .then(response => response.json())
                        .then(data => {
                            console.log('Vanilla JS response:', data);
                            if (data.success) {
                                alert('Password changed successfully!');
                                document.getElementById('changePasswordForm').reset();
                            } else {
                                alert(data.message || 'Failed to change password.');
                            }
                        })
                        .catch(error => {
                            console.error('Vanilla JS error:', error);
                            alert('An error occurred while changing password.');
                        });
                    });
                }
            }, 1000);
        });

        // Admin Properties Functions
        function applyFilters() {
            const status = $('#statusFilter').val();
            const city = $('#cityFilter').val();
            const type = $('#typeFilter').val();

            $('.property-row').each(function() {
                let show = true;
                const $row = $(this);

                // Status filter
                if (status && $row.data('status') !== status) {
                    show = false;
                }

                // City filter
                if (city && $row.data('city') !== city) {
                    show = false;
                }

                // Type filter
                if (type && $row.data('type') !== type) {
                    show = false;
                }

                if (show) {
                    $row.show();
                } else {
                    $row.hide();
                }
            });

            // Show message if no results
            const visibleRows = $('.property-row:visible').length;
            if (visibleRows === 0) {
                iziToast.info({
                    title: 'No Results',
                    message: 'No properties match your filters.',
                    position: 'topRight'
                });
            }
        }

        function clearFilters() {
            $('#statusFilter').val('');
            $('#cityFilter').val('');
            $('#typeFilter').val('');
            $('.property-row').show();
        }

        function viewProperty(id) {
            console.log('Viewing property with ID:', id);
            if (id) {
                const url = `../view-property-detail.php?id=${id}`;
                console.log('Opening URL:', url);
                window.open(url, '_blank');
            } else {
                iziToast.error({
                    title: 'Error',
                    message: 'Invalid property ID',
                    position: 'topRight'
                });
            }
        }

        function togglePropertyStatus(id, currentStatus) {
            const newStatus = currentStatus === 'active' ? 'inactive' : 'active';
            const action = currentStatus === 'active' ? 'deactivate' : 'activate';
            
            if (confirm(`Are you sure you want to ${action} this property?`)) {
                $.ajax({
                    url: '../backend/toggle-property-status.php',
                    method: 'POST',
                    data: { 
                        id: id, 
                        status: newStatus 
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            iziToast.success({
                                title: 'Success',
                                message: response.message,
                                position: 'topRight'
                            });
                            // Refresh the page to update all statuses
                            setTimeout(() => {
                                location.reload();
                            }, 1000);
                        } else {
                            iziToast.error({
                                title: 'Error',
                                message: response.message,
                                position: 'topRight'
                            });
                        }
                    },
                    error: function() {
                        iziToast.error({
                            title: 'Error',
                            message: 'Error updating property status. Please try again.',
                            position: 'topRight'
                        });
                    }
                });
            }
        }

        // deleteProperty function already defined above

        // Property Approval Functions
        function approveProperty(id) {
            if (confirm('Are you sure you want to approve this property?')) {
                $.ajax({
                    url: '../backend/approve-property.php?v=1754257249',
                    method: 'POST',
                    data: { 
                        id: id, 
                        action: 'approve' 
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            iziToast.success({
                                title: 'Success',
                                message: 'Property approved successfully!',
                                position: 'topRight'
                            });
                            // Refresh the page to update all statuses
                            setTimeout(() => {
                                location.reload();
                            }, 1000);
                        } else {
                            iziToast.error({
                                title: 'Error',
                                message: response.message || 'Failed to approve property.',
                                position: 'topRight'
                            });
                        }
                    },
                    error: function() {
                        iziToast.error({
                            title: 'Error',
                            message: 'Error approving property. Please try again.',
                            position: 'topRight'
                        });
                    }
                });
            }
        }

        function rejectProperty(id) {
            if (confirm('Are you sure you want to reject this property?')) {
                $.ajax({
                    url: '../backend/approve-property.php?v=1754257249',
                    method: 'POST',
                    data: { 
                        id: id, 
                        action: 'reject' 
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            iziToast.success({
                                title: 'Success',
                                message: 'Property rejected successfully!',
                                position: 'topRight'
                            });
                            // Refresh the page to update all statuses
                            setTimeout(() => {
                                location.reload();
                            }, 1000);
                        } else {
                            iziToast.error({
                                title: 'Error',
                                message: response.message || 'Failed to reject property.',
                                position: 'topRight'
                            });
                        }
                    },
                    error: function() {
                        iziToast.error({
                            title: 'Error',
                            message: 'Error rejecting property. Please try again.',
                            position: 'topRight'
                        });
                    }
                });
            }
        }

        function changeApprovalStatus(id, currentStatus) {
            const newStatus = currentStatus === 'approved' ? 'rejected' : 'approved';
            const action = currentStatus === 'approved' ? 'reject' : 'approve';
            
            if (confirm(`Are you sure you want to ${action} this property?`)) {
                $.ajax({
                    url: '../backend/approve-property.php?v=1754257249',
                    method: 'POST',
                    data: { 
                        id: id, 
                        action: action 
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            iziToast.success({
                                title: 'Success',
                                message: response.message,
                                position: 'topRight'
                            });
                            // Refresh the page to update all statuses
                            setTimeout(() => {
                                location.reload();
                            }, 1000);
                        } else {
                            iziToast.error({
                                title: 'Error',
                                message: response.message || 'Failed to update approval status.',
                                position: 'topRight'
                            });
                        }
                    },
                    error: function() {
                        iziToast.error({
                            title: 'Error',
                            message: 'Error updating approval status. Please try again.',
                            position: 'topRight'
                        });
                    }
                });
            }
        }



    </script>

</body>

</html>
