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
    });
}

// Essential JavaScript Functions
function updateApprovalRow(propertyId, newListingStatus) {
    const row = $(`.approval-row[data-id="${propertyId}"]`);
    if (row.length) {
        // Update the status badge
        const statusBadge = row.find("td:nth-child(8) .badge");
        if (statusBadge.length) {
            statusBadge.removeClass("bg-success bg-danger bg-warning")
                .addClass(newListingStatus === "approved" ? "bg-success" : 
                         (newListingStatus === "rejected" ? "bg-danger" : "bg-warning"))
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

function approveProperty(id) {
    if (confirm('Are you sure you want to approve this property?')) {
        $.ajax({
            url: '../backend/approve-property.php',
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
            url: '../backend/approve-property.php',
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

function toggleListingStatus(id, currentStatus) {
    const newStatus = currentStatus === 'approved' ? 'rejected' : 'approved';
    const action = currentStatus === 'approved' ? 'reject' : 'approve';
    
    if (confirm(`Are you sure you want to ${action} this property?`)) {
        $.ajax({
            url: '../backend/approve-property.php',
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

function viewPropertyForApproval(propertyId) {
    // Open property detail in a new window or modal
    window.open(`../view-property-detail.php?id=${propertyId}`, '_blank');
}

function getSelectedApprovalIds() {
    const selectedIds = [];
    $('.approval-checkbox:checked').each(function() {
        selectedIds.push($(this).val());
    });
    return selectedIds;
}

function bulkApprove() {
    const selectedIds = getSelectedApprovalIds();
    if (selectedIds.length === 0) {
        alert('Please select properties to approve.');
        return;
    }
    
    if (!confirm('Are you sure you want to approve ' + selectedIds.length + ' properties?')) return;
    
    $.ajax({
        url: '../backend/bulk-approve-properties.php',
        method: 'POST',
        data: { property_ids: selectedIds, action: 'approve' },
        dataType: 'json',
        success: function(res) {
            if(res.success) {
                iziToast.success({
                    title: 'Success',
                    message: res.message || 'Properties approved successfully.',
                    position: 'topRight'
                });
                location.reload();
            } else {
                iziToast.error({
                    title: 'Error',
                    message: res.message || 'Failed to approve properties.',
                    position: 'topRight'
                });
            }
        },
        error: function() {
            iziToast.error({
                title: 'Error',
                message: 'Error approving properties. Please try again.',
                position: 'topRight'
            });
        }
    });
}

function bulkReject() {
    const selectedIds = getSelectedApprovalIds();
    if (selectedIds.length === 0) {
        alert('Please select properties to reject.');
        return;
    }
    
    if (!confirm('Are you sure you want to reject ' + selectedIds.length + ' properties?')) return;
    
    $.ajax({
        url: '../backend/bulk-approve-properties.php',
        method: 'POST',
        data: { property_ids: selectedIds, action: 'reject' },
        dataType: 'json',
        success: function(res) {
            if(res.success) {
                iziToast.success({
                    title: 'Success',
                    message: res.message || 'Properties rejected successfully.',
                    position: 'topRight'
                });
                location.reload();
            } else {
                iziToast.error({
                    title: 'Error',
                    message: res.message || 'Failed to reject properties.',
                    position: 'topRight'
                });
            }
        },
        error: function() {
            iziToast.error({
                title: 'Error',
                message: 'Error rejecting properties. Please try again.',
                position: 'topRight'
            });
        }
    });
}

</script>

</body>

</html>
