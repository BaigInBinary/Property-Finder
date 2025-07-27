<?php
session_start();
require_once 'backend/db.php'; // adjust path if needed

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php?message=login_required");
    exit;
}
?>

<?php include'inc/header.php';?>
<style>
        .referral-card {
            background: linear-gradient(135deg, #6B73FF 0%, #000DFF 100%);
            color: white;
            border-radius: 15px;
            overflow: hidden;
            transition: transform 0.3s;
        }

        .referral-card:hover {
            transform: translateY(-5px);
        }

        .referral-link {
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 8px;
            padding: 15px;
            font-family: monospace;
            word-break: break-all;
        }

        .stats-card {
            border-radius: 12px;
            border: none;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s;
        }

        .stats-card:hover {
            transform: translateY(-5px);
        }

        .stats-icon {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
        }

        .referral-activity {
            border-left: 3px solid #e9ecef;
            padding-left: 20px;
            position: relative;
        }

        .referral-activity::before {
            content: '';
            position: absolute;
            left: -8px;
            top: 0;
            width: 13px;
            height: 13px;
            border-radius: 50%;
            background: #fff;
            border: 3px solid #0d6efd;
        }

        .status-pending {
            color: #ffc107;
        }

        .status-confirmed {
            color: #198754;
        }

        .status-paid {
            color: #0d6efd;
        }

        .share-btn {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: transform 0.2s;
        }

        .share-btn:hover {
            transform: scale(1.1);
        }

        .how-it-works-step {
            position: relative;
            padding-left: 50px;
            margin-bottom: 1.5rem;
        }

        .step-number {
            position: absolute;
            left: 0;
            top: 0;
            width: 35px;
            height: 35px;
            background: #0d6efd;
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
        }

        .toast-container {
            z-index: 1050;
        }
    </style>

    <!-- Toast Container -->
    <div class="toast-container position-fixed top-0 end-0 p-3"></div>

    <!-- Main Content -->
    <main class="container mt-5 pt-5">
        <!-- Referral Link Card -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card referral-card">
                    <div class="card-body p-4">
                        <div class="row align-items-center">
                            <div class="col-md-8">
                                <h4 class="mb-3">Your Referral Code</h4>
                                <div class="referral-link mb-3"></div>
                                <div class="d-flex gap-2 flex-wrap">
                                    <button class="btn btn-light" id="copyLink">
                                        <i class="fas fa-copy me-2"></i>Copy Code
                                    </button>
                                    <button class="btn btn-light share-btn" data-bs-toggle="tooltip" title="Share on Facebook">
                                        <i class="fab fa-facebook-f"></i>
                                    </button>
                                    <button class="btn btn-light share-btn" data-bs-toggle="tooltip" title="Share on Twitter">
                                        <i class="fab fa-twitter"></i>
                                    </button>
                                    <button class="btn btn-light share-btn" data-bs-toggle="tooltip" title="Share on WhatsApp">
                                        <i class="fab fa-whatsapp"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="col-md-4 text-md-end mt-4 mt-md-0">
                            <h3 class="mb-2 total-rewards">PKR 0</h3>
                                <p class="mb-0">Total Rewards Earned</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="row g-4 mb-4">
            <div class="col-md-6">
                <div class="card stats-card">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="stats-icon bg-primary bg-opacity-10 text-primary me-3">
                                <i class="fas fa-users"></i>
                            </div>
                            <div>
                                <h6 class="mb-1">Total Referrals</h6>
                                <h3 class="mb-0 total-referrals">0</h3>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card stats-card">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="stats-icon bg-success bg-opacity-10 text-success me-3">
                                <i class="fas fa-check-circle"></i>
                            </div>
                            <div>
                                <h6 class="mb-1">Successful Referrals Rewards Used</h6>
                                <h3 class="mb-0 successful-rewards-used">0</h3>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Recent Activity -->
            <div class="col-lg-8 mb-4">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title mb-4">Recent Referral Activity</h5>
                        <div class="referral-activities"></div>
                    </div>
                </div>
            </div>

            <!-- How It Works -->
            <div class="col-lg-4 mb-4">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title mb-4">How It Works</h5>
                        <div class="how-it-works-steps">
                            <div class="how-it-works-step">
                                <div class="step-number">1</div>
                                <h6>Share Your Code</h6>
                                <p class="text-muted">Share your unique referral code with friends and family</p>
                            </div>
                            <div class="how-it-works-step">
                                <div class="step-number">2</div>
                                <h6>Friend Signs Up</h6>
                                <p class="text-muted">When they join using your code, they get verified</p>
                            </div>
                            <div class="how-it-works-step">
                                <div class="step-number">3</div>
                                <h6>Earn Rewards</h6>
                                <p class="text-muted">Get PKR 1,000 for each successful referral</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Bootstrap 5 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
document.addEventListener('DOMContentLoaded', function() {
    fetch('backend/fetch-referral-stats.php')
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                // Referral Link
                document.querySelector('.referral-link').textContent = `${data.referral_code}`;

                // Total Rewards
                document.querySelector('.total-rewards').textContent = `PKR ${data.total_rewards}`;

                // Total Referrals
                document.querySelector('.total-referrals').textContent = data.total_referrals;

                // Successful Rewards Used
                document.querySelector('.successful-rewards-used').textContent = data.successful_rewards_used;

                // Recent Referrals
                const activitiesContainer = document.querySelector('.referral-activities');
                activitiesContainer.innerHTML = '';

                data.recent_referrals.forEach(ref => {
                    const activityDiv = document.createElement('div');
                    activityDiv.className = 'referral-activity mb-4';
                    activityDiv.innerHTML = `
                        <p class="mb-1"><strong>${ref.referred_name}</strong> joined using your referral</p>
                        <small class="text-muted">${timeAgo(ref.referred_at)}</small>
                        <span class="badge bg-success ms-2">PKR ${ref.bonus_points_awarded} Earned</span>
                    `;
                    activitiesContainer.appendChild(activityDiv);
                });
            }
        });

    function timeAgo(dateStr) {
        const date = new Date(dateStr);
        const seconds = Math.floor((new Date() - date) / 1000);
        const intervals = [
            { label: 'year', seconds: 31536000 },
            { label: 'month', seconds: 2592000 },
            { label: 'day', seconds: 86400 },
            { label: 'hour', seconds: 3600 },
            { label: 'minute', seconds: 60 },
            { label: 'second', seconds: 1 }
        ];

        for (let i = 0; i < intervals.length; i++) {
            const interval = intervals[i];
            const count = Math.floor(seconds / interval.seconds);
            if (count > 0) {
                return `${count} ${interval.label}${count !== 1 ? 's' : ''} ago`;
            }
        }
        return 'just now';
    }
});
</script>
<script>
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize tooltips
            const tooltips = document.querySelectorAll('[data-bs-toggle="tooltip"]');
            tooltips.forEach(tooltip => new bootstrap.Tooltip(tooltip));

            // Copy link functionality
            const copyLinkBtn = document.getElementById('copyLink');
            copyLinkBtn.addEventListener('click', function() {
                const referralLink = document.querySelector('.referral-link').textContent.trim();
                navigator.clipboard.writeText(referralLink).then(() => {
                    showToast('Referral link copied to clipboard!');
                }).catch(err => {
                    showToast('Failed to copy link. Please try again.', 'error');
                });
            });

            // Social share buttons
            document.querySelectorAll('.share-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    const referralLink = document.querySelector('.referral-link').textContent.trim();
                    const platform = this.querySelector('i').classList[1];
                    let shareUrl = '';

                    switch(platform) {
                        case 'fa-facebook-f':
                            shareUrl = `https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(referralLink)}`;
                            break;
                        case 'fa-twitter':
                            shareUrl = `https://twitter.com/intent/tweet?url=${encodeURIComponent(referralLink)}&text=${encodeURIComponent('Join PropFind using my referral link!')}`;
                            break;
                        case 'fa-whatsapp':
                            shareUrl = `https://wa.me/?text=${encodeURIComponent('Join PropFind using my referral link: ' + referralLink)}`;
                            break;
                    }

                    if (shareUrl) {
                        window.open(shareUrl, '_blank', 'width=600,height=400');
                    }
                });
            });

            // Toast notification function
            function showToast(message, type = 'success') {
                const toastContainer = document.querySelector('.toast-container');
                const toastHTML = `
                    <div class="toast align-items-center text-white bg-${type === 'success' ? 'dark' : 'danger'} border-0" 
                         role="alert" aria-live="assertive" aria-atomic="true">
                        <div class="d-flex">
                            <div class="toast-body">
                                ${message}
                            </div>
                            <button type="button" class="btn-close btn-close-white me-2 m-auto" 
                                    data-bs-dismiss="toast" aria-label="Close"></button>
                        </div>
                    </div>
                `;
                
                toastContainer.insertAdjacentHTML('beforeend', toastHTML);
                const toast = toastContainer.lastElementChild;
                const bsToast = new bootstrap.Toast(toast, { autohide: true, delay: 3000 });
                
                bsToast.show();
                
                toast.addEventListener('hidden.bs.toast', function() {
                    toast.remove();
                });
            }
        });
    </script>


</body>
</html> 