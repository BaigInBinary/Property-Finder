<?php include'inc/header.php';?>

    <!-- Main Content -->
    <main class="saved-properties-container mt-5 pt-4">
        <div class="container">
            <div class="row mb-4">
                <div class="col">
                    <h1 class="section-title">Saved Properties</h1>
                    <p class="text-muted">Manage your bookmarked properties</p>
                </div>
            </div>

            <!-- Properties Grid -->
            <div class="row g-4" id="savedPropertiesGrid">
                <!-- Properties will be dynamically inserted here -->
            </div>

            <!-- Empty State -->
            <div class="text-center empty-state d-none" id="emptyState">
                <i class="fas fa-bookmark fa-3x mb-3"></i>
                <h3>No Saved Properties</h3>
                <p>Properties you save will appear here</p>
                <a href="listings.php" class="btn btn-primary mt-3">
                    <i class="fas fa-search me-2"></i>Browse Properties
                </a>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer class="footer bg-light mt-5">
        <div class="container py-4">
            <div class="row">
                <div class="col-md-4">
                    <h5>About PropFind</h5>
                    <p>Your trusted partner in finding the perfect property in Pakistan.</p>
                </div>
                <div class="col-md-4">
                    <h5>Quick Links</h5>
                    <ul class="list-unstyled">
                        <li><a href="about.html">About Us</a></li>
                        <li><a href="contact.html">Contact</a></li>
                        <li><a href="privacy.html">Privacy Policy</a></li>
                        <li><a href="terms.html">Terms of Service</a></li>
                    </ul>
                </div>
                <div class="col-md-4">
                    <h5>Contact Us</h5>
                    <ul class="list-unstyled">
                        <li><i class="fas fa-phone me-2"></i>+92 300 1234567</li>
                        <li><i class="fas fa-envelope me-2"></i>contact@propfind.com</li>
                    </ul>
                </div>
            </div>
            <hr>
            <div class="text-center">
                <p class="mb-0">&copy; 2024 PropFind. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Custom JavaScript -->
    <script src="js/saved-properties.js"></script>
</body>
</html> 