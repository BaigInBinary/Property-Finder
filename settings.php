<?php include'inc/header.php'?>

    <!-- Main Content -->
    <main class="settings-container">
        <div class="container py-5">
            <div class="row">
                <!-- Settings Sidebar -->
                <div class="col-lg-3">
                    <div class="settings-sidebar">
                        <div class="user-profile text-center mb-4">
                            <div class="profile-image mb-3">
                                <img src="images/default-avatar.jpg" alt="Profile Picture" class="rounded-circle">
                                <button class="btn btn-sm btn-light edit-photo">
                                    <i class="fas fa-camera"></i>
                                </button>
                            </div>
                            <h5 class="mb-1">John Doe</h5>
                            <p class="text-muted mb-0">Premium Member</p>
                        </div>
                        <div class="nav flex-column nav-pills">
                            <a class="nav-link active" href="#account" data-bs-toggle="pill">
                                <i class="fas fa-user me-2"></i>Account Settings
                            </a>
                            <a class="nav-link" href="#notifications" data-bs-toggle="pill">
                                <i class="fas fa-bell me-2"></i>Notifications
                            </a>
                            <a class="nav-link" href="#privacy" data-bs-toggle="pill">
                                <i class="fas fa-shield-alt me-2"></i>Privacy & Security
                            </a>
                            <a class="nav-link" href="#preferences" data-bs-toggle="pill">
                                <i class="fas fa-sliders-h me-2"></i>Preferences
                            </a>
                            <a class="nav-link" href="#billing" data-bs-toggle="pill">
                                <i class="fas fa-credit-card me-2"></i>Billing & Plans
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Settings Content -->
                <div class="col-lg-9">
                    <div class="tab-content">
                        <!-- Account Settings -->
                        <div class="tab-pane fade show active" id="account">
                            <div class="settings-card">
                                <h4 class="mb-4">Account Settings</h4>
                                <form>
                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <label class="form-label">First Name</label>
                                            <input type="text" class="form-control" value="John">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Last Name</label>
                                            <input type="text" class="form-control" value="Doe">
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Email Address</label>
                                        <input type="email" class="form-control" value="john.doe@example.com">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Phone Number</label>
                                        <input type="tel" class="form-control" value="+1 234 567 8900">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Bio</label>
                                        <textarea class="form-control" rows="3">Real estate enthusiast with 5 years of experience in property investment.</textarea>
                                    </div>
                                    <button type="submit" class="btn btn-primary">Save Changes</button>
                                </form>
                            </div>
                        </div>

                        <!-- Notifications -->
                        <div class="tab-pane fade" id="notifications">
                            <div class="settings-card">
                                <h4 class="mb-4">Notification Settings</h4>
                                <div class="notification-settings">
                                    <div class="notification-item">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" checked>
                                            <label class="form-check-label">Email Notifications</label>
                                        </div>
                                        <p class="text-muted">Receive email updates about your account activity</p>
                                    </div>
                                    <div class="notification-item">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" checked>
                                            <label class="form-check-label">Property Alerts</label>
                                        </div>
                                        <p class="text-muted">Get notified when new properties match your criteria</p>
                                    </div>
                                    <div class="notification-item">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox">
                                            <label class="form-check-label">SMS Notifications</label>
                                        </div>
                                        <p class="text-muted">Receive text messages for important updates</p>
                                    </div>
                                    <div class="notification-item">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" checked>
                                            <label class="form-check-label">Newsletter</label>
                                        </div>
                                        <p class="text-muted">Subscribe to our monthly newsletter</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Privacy & Security -->
                        <div class="tab-pane fade" id="privacy">
                            <div class="settings-card">
                                <h4 class="mb-4">Privacy & Security</h4>
                                <div class="privacy-settings">
                                    <div class="mb-4">
                                        <h5>Password</h5>
                                        <form>
                                            <div class="mb-3">
                                                <label class="form-label">Current Password</label>
                                                <input type="password" class="form-control">
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">New Password</label>
                                                <input type="password" class="form-control">
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Confirm New Password</label>
                                                <input type="password" class="form-control">
                                            </div>
                                            <button type="submit" class="btn btn-primary">Update Password</button>
                                        </form>
                                    </div>
                                    <div class="mb-4">
                                        <h5>Two-Factor Authentication</h5>
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox">
                                            <label class="form-check-label">Enable 2FA</label>
                                        </div>
                                        <p class="text-muted">Add an extra layer of security to your account</p>
                                    </div>
                                    <div class="mb-4">
                                        <h5>Profile Visibility</h5>
                                        <select class="form-select">
                                            <option>Public</option>
                                            <option>Private</option>
                                            <option>Friends Only</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Preferences -->
                        <div class="tab-pane fade" id="preferences">
                            <div class="settings-card">
                                <h4 class="mb-4">Preferences</h4>
                                <div class="preferences-settings">
                                    <div class="mb-4">
                                        <h5>Language</h5>
                                        <select class="form-select">
                                            <option>English</option>
                                            <option>Urdu</option>
                                            <option>Arabic</option>
                                        </select>
                                    </div>
                                    <div class="mb-4">
                                        <h5>Currency</h5>
                                        <select class="form-select">
                                            <option>PKR (₨)</option>
                                            <option>USD ($)</option>
                                            <option>EUR (€)</option>
                                        </select>
                                    </div>
                                    <div class="mb-4">
                                        <h5>Theme</h5>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="theme" checked>
                                            <label class="form-check-label">Light</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="theme">
                                            <label class="form-check-label">Dark</label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Billing & Plans -->
                        <div class="tab-pane fade" id="billing">
                            <div class="settings-card">
                                <h4 class="mb-4">Billing & Plans</h4>
                                <div class="current-plan mb-4">
                                    <h5>Current Plan</h5>
                                    <div class="plan-details">
                                        <div class="badge bg-success mb-2">Premium</div>
                                        <p>Your plan renews on Dec 31, 2024</p>
                                    </div>
                                </div>
                                <div class="payment-methods mb-4">
                                    <h5>Payment Methods</h5>
                                    <div class="card-item">
                                        <i class="fab fa-cc-visa me-2"></i>
                                        <span>•••• •••• •••• 4242</span>
                                        <span class="badge bg-primary ms-2">Default</span>
                                    </div>
                                    <button class="btn btn-primary mt-3">
                                        <i class="fas fa-plus me-2"></i>Add Payment Method
                                    </button>
                                </div>
                                <div class="billing-history">
                                    <h5>Billing History</h5>
                                    <div class="table-responsive">
                                        <table class="table">
                                            <thead>
                                                <tr>
                                                    <th>Date</th>
                                                    <th>Amount</th>
                                                    <th>Status</th>
                                                    <th>Invoice</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr>
                                                    <td>Nov 1, 2024</td>
                                                    <td>$29.99</td>
                                                    <td><span class="badge bg-success">Paid</span></td>
                                                    <td><a href="#"><i class="fas fa-download"></i></a></td>
                                                </tr>
                                                <tr>
                                                    <td>Oct 1, 2024</td>
                                                    <td>$29.99</td>
                                                    <td><span class="badge bg-success">Paid</span></td>
                                                    <td><a href="#"><i class="fas fa-download"></i></a></td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer class="footer-dark">
        <div class="container py-4">
            <div class="row">
                <div class="col-md-6">
                    <p class="mb-0">&copy; 2024 PropFind. All rights reserved.</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <div class="social-links">
                        <a href="#" class="social-link"><i class="fab fa-facebook-f"></i></a>
                        <a href="#" class="social-link"><i class="fab fa-twitter"></i></a>
                        <a href="#" class="social-link"><i class="fab fa-instagram"></i></a>
                        <a href="#" class="social-link"><i class="fab fa-linkedin-in"></i></a>
                    </div>
                </div>
            </div>
        </div>
    </footer>

    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 