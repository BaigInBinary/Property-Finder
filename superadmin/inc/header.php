

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Property Management - PropFind Admin</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="../css/admin.css" rel="stylesheet">
    <link href="../css/properties.css" rel="stylesheet">
</head>
<body>
    <!-- Top Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">PropFind Admin</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user-circle"></i> Admin
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <!-- <li><a class="dropdown-item" href="settings.html"><i class="fas fa-cog"></i> Settings</a></li> -->
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="index.html"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Admin Container -->
    <div class="admin-container">
    <?php
$currentPage = basename($_SERVER['PHP_SELF']);
?>

        <!-- Sidebar -->
        <div class="admin-sidebar">
    <div class="sidebar-header">
        <h4>Admin Panel</h4>
    </div>
    <ul class="sidebar-nav">
        <li class="nav-item">
            <a href="index.php" class="nav-link <?php echo ($currentPage == 'index.php') ? 'active' : ''; ?>">
                <i class="fas fa-tachometer-alt"></i>
                <span>Dashboard</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="all-users.php" class="nav-link <?php echo ($currentPage == 'all-users.php') ? 'active' : ''; ?>">
                <i class="fas fa-users-cog"></i>
                <span>User Management</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="all-properties.php" class="nav-link <?php echo ($currentPage == 'all-properties.php') ? 'active' : ''; ?>">
                <i class="fas fa-building"></i>
                <span>All Properties</span>
            </a>
        </li>

        <li class="nav-divider"></li>
        <li class="nav-item">
            <a href="logout.php" class="nav-link text-danger">
                <i class="fas fa-sign-out-alt"></i>
                <span>Logout</span>
            </a>
        </li>
    </ul>
</div>
