<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$currentPage = basename($_SERVER['PHP_SELF']); // e.g., upload-property.php

// Check if on upload-property.php and not logged in
if ($currentPage === 'upload-property.php' && !isset($_SESSION['user_id'])) {
    echo '
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            iziToast.error({
                title: "Access Denied",
                message: "You must be logged in to upload properties.",
                position: "topRight"
            });
        });
    </script>
    ';
}
?>


<!DOCTYPE php>
<php lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PropFind - Smart Property Search</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="css/styles.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/swiper@10/swiper-bundle.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="css/property-detail.css" rel="stylesheet">
    <style>
        .favorite-btn {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: rgba(255, 255, 255, 0.9);
            border: none;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: transform 0.2s, background-color 0.2s;
            z-index: 1;
            position: absolute;
            top: 15px;
            right: 15px;
        }

        .favorite-btn:hover {
            transform: scale(1.1);
            background-color: rgba(255, 255, 255, 1);
        }

        .favorite-btn:active {
            transform: scale(0.95);
        }

        .favorite-btn i {
            color: #dc3545;
            font-size: 1.25rem;
            transition: transform 0.2s;
        }

        .favorite-btn:hover i {
            transform: scale(1.1);
        }

        .verified-badge {
            position: absolute;
            top: 15px;
            left: 15px;
            background-color: rgba(25, 135, 84, 0.9);
            color: white;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            display: flex;
            align-items: center;
            gap: 5px;
            z-index: 1;
        }

        .verified-badge i {
            color: #fff;
            font-size: 0.9rem;
        }

        @keyframes favoriteAnimation {
            0% { transform: scale(1); }
            50% { transform: scale(1.3); }
            100% { transform: scale(1); }
        }

        .favorite-animate {
            animation: favoriteAnimation 0.3s ease-in-out;
        }

        .property-image-wrapper {
            position: relative;
            padding-top: 66.67%; /* 3:2 aspect ratio */
            overflow: hidden;
        }

        .property-image-wrapper img {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        /* Update property card styles */
        .property-card {
            transition: transform 0.2s, box-shadow 0.2s;
            height: 100%;
        }

        .property-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }
    </style>
        <link href="css/auth.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/izitoast/dist/css/iziToast.min.css">
<script src="https://cdn.jsdelivr.net/npm/izitoast/dist/js/iziToast.min.js"></script></body>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-xl navbar-light fixed-top">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-home"></i>
                <span>PropFind</span>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <!-- Main Navigation Items -->
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'index.php') ? 'active' : ''; ?>" href="index.php">
                            <i class="fas fa-home d-xl-none me-2"></i>Home
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'listings.php') ? 'active' : ''; ?>" href="listings.php">
                            <i class="fas fa-list d-xl-none me-2"></i>All Listings
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'upload-property.php') ? 'active' : ''; ?>" href="upload-property.php">
                            <i class="fas fa-upload d-xl-none me-2"></i>Upload Property
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'dashboard.php') ? 'active' : ''; ?>" href="dashboard/dashboard.php">
                            <i class="fas fa-tachometer-alt d-xl-none me-2"></i>Dashboard
                        </a>
                    </li>
                    
                    <!-- More Options Dropdown -->
                    <li class="nav-item dropdown more-dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-ellipsis-h d-xl-none me-2"></i>More
                        </a>
                        <ul class="dropdown-menu">
                            <li>
                                <a class="dropdown-item <?php echo (basename($_SERVER['PHP_SELF']) == 'compare.php') ? 'active' : ''; ?>" href="compare.php">
                                    <i class="fas fa-exchange-alt me-2"></i>Compare Properties
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item <?php echo (basename($_SERVER['PHP_SELF']) == 'chat.php') ? 'active' : ''; ?>" href="chat.php">
                                    <i class="fas fa-comments me-2"></i>Chat
                                </a>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a class="dropdown-item <?php echo (basename($_SERVER['PHP_SELF']) == 'about.php') ? 'active' : ''; ?>" href="about.php">
                                    <i class="fas fa-info-circle me-2"></i>About Us
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item <?php echo (basename($_SERVER['PHP_SELF']) == 'contact.php') ? 'active' : ''; ?>" href="contact.php">
                                    <i class="fas fa-envelope me-2"></i>Contact
                                </a>
                            </li> 
                        </ul>
                    </li>
                </ul>
                
                <!-- Auth Buttons -->
                <div class="nav-auth">
    <?php if (isset($_SESSION['user_name'])): ?>
        <div class="alert alert-warning d-inline-flex align-items-center mt-2 gap-2 p-2 rounded-pill">
            <i class="fas fa-user-circle"></i>
            <strong>Hello, <?php echo htmlspecialchars($_SESSION['user_name']); ?>!</strong>
        </div>
        <a href="logout.php" class="btn btn-danger ms-2">Logout</a>
    <?php else: ?>
        <a href="login.php" class="btn btn-outline-primary me-2">Login</a>
        <a href="signup.php" class="btn btn-primary">Sign Up</a>
    <?php endif; ?>
</div>


            </div>
        </div>
    </nav>
