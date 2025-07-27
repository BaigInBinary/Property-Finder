<?php include'inc/header.php'; ?>
<?php if (isset($_GET['message']) && $_GET['message'] === 'login_required'): ?>
    <script>
        iziToast.warning({
            title: 'Login Required',
            message: 'You need to be logged in to access this page.',
            position: 'topRight'
        });
    </script>
<?php endif; ?>

    <!-- Main Content -->
    <main class="auth-container mt-5 pt-4">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-md-6 col-lg-5">
                    <div class="auth-card">
                        <h1 class="text-center mb-4">Welcome Back</h1>
                        <p class="text-center text-muted mb-4">Login to access your PropFind account</p>
                        
                        <form id="loginForm" class="needs-validation ajax-form" data-action="login_user" novalidate>
                            <!-- Email -->
                            <div class="mb-3">
                                <label for="email" class="form-label">Email address</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                    <input type="email" class="form-control" name="email" id="email" placeholder="Enter your email" required>
                                </div>
                                <div class="invalid-feedback">Please enter a valid email address</div>
                            </div>

                            <!-- Password -->
                            <div class="mb-4">
                                <label for="password" class="form-label">Password</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                    <input type="password" name="password" class="form-control" id="password" placeholder="Enter your password" required>
                                    <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                                <div class="invalid-feedback">Please enter your password</div>
                            </div>

                            <!-- Remember Me & Forgot Password -->
                            <div class="d-flex justify-content-between align-items-center mb-4">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="rememberMe">
                                    <label class="form-check-label" for="rememberMe">
                                        Remember me
                                    </label>
                                </div>
                                <div class="mb-3 text-end">
                                    <a href="forgot-password.php" class="text-decoration-none">Forgot Password?</a>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-primary w-100 mb-3">Login</button>
                            
                            <div class="text-center">
                                <p class="mb-0">Don't have an account? <a href="signup.php" class="text-primary">Sign Up</a></p>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Bootstrap 5 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Custom JS -->

    <script src="ajax.js"></script>
</body>
</html> 