<?php include 'inc/header.php'; ?>
<?php
include 'backend/db.php';

$propertyId = $_GET['id'] ?? 0;
if (!$propertyId) {
    die('Property ID is missing');
}

$query = "SELECT p.user_id, p.title, p.price, p.area , p.type, p.location, p.unit, p.images_json, p.created_at, p.description, p.link, u.picture,u.name AS user_name 
          FROM properties p
          JOIN users u ON p.user_id = u.id
          WHERE p.id = ?";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $propertyId);
$stmt->execute();
$result = $stmt->get_result();
$property = $result->fetch_assoc();

if (!$property) {
    die('Property not found');
}

$images = json_decode($property['images_json'], true);

// Get user email from session if available
$userEmail = $_SESSION['user_email'] ?? '';

// Check if property is sold
$isSold = false;
$soldStmt = $conn->prepare("SELECT COUNT(*) FROM transactions WHERE property_id = ?");
$soldStmt->bind_param('i', $propertyId);
$soldStmt->execute();
$soldStmt->bind_result($soldCount);
$soldStmt->fetch();
$soldStmt->close();
if ($soldCount > 0) {
    $isSold = true;
}
?>

<!-- Main Content -->
<main class="property-detail-container mt-5 pt-4">
    <div class="container">
        <!-- Property Header -->
        <div class="property-header mb-4">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <h1 class="property-title"><?php echo $property['title'] ?></h1>
                    <p class="property-location">
                        <i class="fas fa-map-marker-alt" style="margin-right:10px;"></i><?php echo $property['location'] ?>
                        <span class="verified-badge ms-2">
                            <i class="fas fa-check-circle"></i> Verified
                        </span>
                    </p>
                </div>
                <div class="property-actions d-flex ">
                    <button class="btn btn-primary me-2" id="bookmarkBtn" data-bs-toggle="tooltip" title="Save Property">
                        <i class="far fa-bookmark"></i> Save
                    </button>
                    <!-- <button class="btn btn-outline-danger" id="reportBtn" data-bs-toggle="tooltip" title="Report Property">
                        <i class="fas fa-flag"></i> Report
                    </button> -->
                    <?php if ($isSold): ?>
                <div class="m-0">
                    <div class="alert alert-danger text-center m-0 fw-bold">This Property is Sold</div>
                </div>
                <?php else: ?>
                    <?php 
                    // Check if current user is the property creator
                    $currentUserId = $_SESSION['user_id'] ?? 0;
                    $isPropertyCreator = ($currentUserId == $property['user_id']);
                    $isLoggedIn = isset($_SESSION['user_id']);
                    ?>
                    
                    <?php if (!$isLoggedIn): ?>
                        <div class="m-0">
                            <div class="alert alert-warning text-center m-0 fw-bold">
                                <i class="fas fa-exclamation-triangle me-2"></i>Please <a href="login.php" class="alert-link">login</a> to buy this property
                            </div>
                        </div>
                    <?php elseif ($isPropertyCreator): ?>
                        <div class="m-0">
                            <div class="alert alert-info text-center m-0 fw-bold">
                                <i class="fas fa-info-circle me-2"></i>You cannot buy your own property
                            </div>
                        </div>
                    <?php else: ?>
                        <button class="btn btn-success" id="buyPropertyBtn" data-bs-toggle="modal" data-bs-target="#checkoutModal" 
                                data-property-creator="<?php echo $property['user_id']; ?>" 
                                data-current-user="<?php echo $currentUserId; ?>">
                            <i class="fas fa-shopping-cart me-2"></i>Buy Property
                        </button>
                    <?php endif; ?>
                <?php endif; ?>
                </div>
            </div>
        </div>


        <div class="row">
            <div class="col-xl-8">
                <!-- Image Gallery -->
                <?php
                $images = json_decode($property['images_json'], true);
                ?>

                <div class="property-gallery card mb-4">
                    <div class="swiper">
                        <div class="swiper-wrapper">
                            <?php if (!empty($images) && is_array($images)): ?>
                                <?php foreach ($images as $index => $image): ?>
                                    <div class="swiper-slide">
                                        <img src="<?php echo htmlspecialchars($image); ?>" alt="Property Image <?php echo $index + 1; ?>">
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="swiper-slide">
                                    <img src="https://placehold.co/800x600/png" alt="No Property Images">
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="swiper-pagination"></div>
                        <div class="swiper-button-prev"></div>
                        <div class="swiper-button-next"></div>
                    </div>
                </div>


                <!-- Video Tour -->
                <div class="property-video card mb-4" id="videoTourSection">
                    <div class="card-body">
                        <h3 class="card-title">Video Tour</h3>
                        <div class="ratio ratio-16x9">
                            <iframe src="https://www.youtube.com/embed/your-video-id"
                                title="Property Video Tour"
                                allowfullscreen></iframe>
                        </div>
                    </div>
                </div>

                <!-- Property Details -->
                <div class="property-details card mb-4">
                    <div class="card-body">
                        <h3 class="card-title">Property Details</h3>
                        <div class="row g-3">
                            <div class="col-sm-6 col-md-4">
                                <div class="detail-item">
                                    <i class="fas fa-money-bill-wave"></i>
                                    <span class="label">Price:</span>
                                    <span class="value"><?php echo  'Pkr' . ' ' .  $property['price'] ?></span>
                                </div>
                            </div>
                            <div class="col-sm-6 col-md-4">
                                <div class="detail-item">
                                    <i class="fas fa-ruler-combined"></i>
                                    <span class="label">Area:</span>
                                    <span class="value"><?php echo $property['area'] . ' ' .  $property['unit'] ?></span>
                                </div>
                            </div>
                            <div class="col-sm-6 col-md-4">
                                <div class="detail-item">
                                    <i class="fas fa-home"></i>
                                    <span class="label">Type:</span>
                                    <span class="value"><?php echo $property['type'] ?></span>
                                </div>
                            </div>
                            <div class="col-sm-8 col-md-6">
                                <div class="detail-item">
                                    <i class="fas fa-bed"></i>
                                    <span class="label">Posted By:</span>
                                    <span class="value"><?php echo $property['user_name'] ?></span>
                                </div>
                            </div>
                            <div class="col-sm-8 col-md-6">
                                <div class="detail-item">
                                    <i class="fas fa-bath"></i>
                                    <span class="label">Posted on:</span>
                                    <span class="value">
                                        <?php echo date('Y-m-d', strtotime($property['created_at'])); ?>
                                    </span>

                                </div>
                            </div>

                        </div>
                    </div>
                </div>

                <!-- Description -->
                <div class="property-description card mb-4">
                    <div class="card-body">
                        <h3 class="card-title">Description</h3>
                        <?php echo $property['description'] ?>
                    </div>
                </div>

                <!-- Location -->
                <div class="property-location card mb-4">
                    <div class="card-body">
                        <h3 class="card-title">Location</h3>
                        <script>
                        // Log the map link value for debugging
                        console.log('Map link:', <?php echo json_encode($property['link']); ?>);
                        </script>
                        <div class="map-container">
                            <?php 
                            $mapLink = $property['link'];
                            // Remove fixed width and height attributes and add responsive classes
                            $mapLink = preg_replace('/width="[^"]*"/', 'class="w-100"', $mapLink);
                            $mapLink = preg_replace('/height="[^"]*"/', 'style="height: 400px; border: 0;"', $mapLink);
                            echo html_entity_decode($mapLink); 
                            ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-4">
                <!-- Contact Form -->
                <div class="contact-form card sticky-top">
                    <div class="card-body">
                        <h3 class="card-title">Contact Agent</h3>
                        <div class="agent-info mb-3">
                            <img src="<?php echo !empty($property['picture']) ? htmlspecialchars($property['picture']) : 'https://placehold.co/100x100'; ?>"
                                alt="Agent"
                                class="agent-avatar">

                            <div class="agent-details">
                                <h5 class="agent-name"><?php echo $property['user_name'] ?></h5>
                                <p class="agent-title"> Property Consultant</p>
                            </div>
                        </div>
                        <form id="contactForm" novalidate>
                            <input type="hidden" id="property_id" value="<?php echo $propertyId; ?>">
                            <div class="mb-3">
                                <label for="name" class="form-label">Your Name</label>
                                <input type="text" class="form-control" id="name" required>
                                <div class="invalid-feedback">
                                    Please enter your name
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="email" class="form-label">Email Address</label>
                                <input type="email" class="form-control" id="email" required>
                                <div class="invalid-feedback">
                                    Please enter a valid email address
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="phone" class="form-label">Phone Number</label>
                                <input type="tel" class="form-control" id="phone" required>
                                <div class="invalid-feedback">
                                    Please enter your phone number
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="message" class="form-label">Message</label>
                                <textarea class="form-control" id="message" rows="4" required></textarea>
                                <div class="invalid-feedback">
                                    Please enter your message
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-paper-plane me-2"></i>Send Message
                            </button>
                        </form>
                    </div>
                </div>
                <!-- Buy Property Button -->
                <?php if ($isSold): ?>
                <div>
                </div>
                <?php else: ?>
                <div>
                </div>
                <!-- Bootstrap Modal for Checkout -->
                <div class="modal fade" id="checkoutModal" tabindex="-1" aria-labelledby="checkoutModalLabel" aria-hidden="true">
                  <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                      <div class="modal-header">
                        <h5 class="modal-title" id="checkoutModalLabel">Order Property</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                      </div>
                      <div class="modal-body">
                        <div class="modal-success-icon text-center mb-2">
                          <svg width="48" height="48" viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <circle cx="24" cy="24" r="24" fill="#4CAF50" />
                            <path d="M34 18L22 30L14 22" stroke="white" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" />
                          </svg>
                        </div>
                        <div class="modal-title fs-4 mb-1">Order Property</div>
                        <div class="modal-subtitle mb-3">Please enter your details to complete the order:</div>
                        <form id="checkout-form" autocomplete="off">
                          <div class="mb-3">
                            <label for="checkout-email" class="form-label">Email Address</label>
                            <input type="email" class="form-control" id="checkout-email" name="checkout-email" placeholder="Email Address" value="<?php echo htmlspecialchars($userEmail); ?>" <?php echo $userEmail ? 'disabled' : ''; ?> required />
                          </div>
                          <div class="mb-3">
                            <label for="checkout-name" class="form-label">Cardholder Name</label>
                            <input type="text" class="form-control" id="checkout-name" name="checkout-name" placeholder="Name on Card" required />
                          </div>
                          <div class="mb-3">
                            <label for="checkout-card" class="form-label">Card Number</label>
                            <div class="input-group">
                              <span class="input-group-text"><i class="fas fa-credit-card"></i></span>
                              <input type="text" class="form-control" id="checkout-card" name="checkout-card" placeholder="Card Number" maxlength="19" required />
                            </div>
                          </div>
                          <div class="row g-2 mb-3">
                            <div class="col">
                              <label for="checkout-expiry" class="form-label">Expiry</label>
                              <input type="text" class="form-control" id="checkout-expiry" name="checkout-expiry" placeholder="MM/YY" maxlength="5" required />
                            </div>
                            <div class="col">
                              <label for="checkout-cvv" class="form-label">CVV</label>
                              <input type="text" class="form-control" id="checkout-cvv" name="checkout-cvv" placeholder="CVV" maxlength="4" required />
                            </div>
                          </div>
                          <div id="modal-order-summary" class="mb-3">
                            <div class="d-flex justify-content-between border-bottom pb-1 mb-1">
                              <span class="fw-semibold">Property:</span>
                              <span><?php echo htmlspecialchars($property['title']); ?></span>
                            </div>
                            <div class="d-flex justify-content-between">
                              <span class="fw-semibold">Price:</span>
                              <span>Pkr <?php echo htmlspecialchars($property['price']); ?></span>
                            </div>
                          </div>
                          <button class="btn btn-success w-100 modal-ok-btn" id="modal-ok" type="submit">
                            Pay & Complete Order
                          </button>
                        </form>
                      </div>
                    </div>
                  </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <!-- Bootstrap Modal for Checkout -->
    <div class="modal fade" id="checkoutModal" tabindex="-1" aria-labelledby="checkoutModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="checkoutModalLabel">Order Property</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <div class="modal-success-icon text-center mb-2">
              <svg width="48" height="48" viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg">
                <circle cx="24" cy="24" r="24" fill="#4CAF50" />
                <path d="M34 18L22 30L14 22" stroke="white" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" />
              </svg>
            </div>
            <div class="modal-title fs-4 mb-1">Order Property</div>
            <div class="modal-subtitle mb-3">Please enter your details to complete the order:</div>
            <form id="checkout-form" autocomplete="off">
              <div class="mb-3">
                <label for="checkout-email" class="form-label">Email Address</label>
                <input type="email" class="form-control" id="checkout-email" name="checkout-email" placeholder="Email Address" value="<?php echo htmlspecialchars($userEmail); ?>" <?php echo $userEmail ? 'disabled' : ''; ?> required />
              </div>
              <div class="mb-3">
                <label for="checkout-name" class="form-label">Cardholder Name</label>
                <input type="text" class="form-control" id="checkout-name" name="checkout-name" placeholder="Name on Card" required />
              </div>
              <div class="mb-3">
                <label for="checkout-card" class="form-label">Card Number</label>
                <div class="input-group">
                  <span class="input-group-text"><i class="fas fa-credit-card"></i></span>
                  <input type="text" class="form-control" id="checkout-card" name="checkout-card" placeholder="Card Number" maxlength="19" required />
                </div>
              </div>
              <div class="row g-2 mb-3">
                <div class="col">
                  <label for="checkout-expiry" class="form-label">Expiry</label>
                  <input type="text" class="form-control" id="checkout-expiry" name="checkout-expiry" placeholder="MM/YY" maxlength="5" required />
                </div>
                <div class="col">
                  <label for="checkout-cvv" class="form-label">CVV</label>
                  <input type="text" class="form-control" id="checkout-cvv" name="checkout-cvv" placeholder="CVV" maxlength="4" required />
                </div>
              </div>
              <div id="modal-order-summary" class="mb-3">
                <div class="d-flex justify-content-between border-bottom pb-1 mb-1">
                  <span class="fw-semibold">Property:</span>
                  <span><?php echo htmlspecialchars($property['title']); ?></span>
                </div>
                <div class="d-flex justify-content-between">
                  <span class="fw-semibold">Price:</span>
                  <span>Pkr <?php echo htmlspecialchars($property['price']); ?></span>
                </div>
              </div>
              <button class="btn btn-success w-100 modal-ok-btn" id="modal-ok" type="submit">
                Pay & Complete Order
              </button>
            </form>
          </div>
        </div>
      </div>
    </div>
<!-- Reviews Section -->
<div class="property-reviews container card mb-4">
    <div class="card-body">
        <h3 class="card-title">Reviews & Ratings</h3>
        <div id="averageRating" class="mb-2"></div>
        <?php if (isset($_SESSION['user_id'])): ?>
        <form id="reviewForm" class="mb-4">
            <input type="hidden" name="property_id" value="<?php echo $propertyId; ?>">
            <div class="mb-2">
                <label class="form-label">Your Rating:</label>
                <div id="starRating" class="mb-1">
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                        <i class="fa fa-star star text-warning" data-value="<?php echo $i; ?>" style="font-size:1.5rem;cursor:pointer;"></i>
                    <?php endfor; ?>
                </div>
                <input type="hidden" name="rating" id="ratingInput" value="0">
            </div>
            <div class="mb-2">
                <textarea class="form-control" name="review" id="reviewText" rows="2" placeholder="Write your review..."></textarea>
            </div>
            <button type="submit" class="btn btn-primary">Submit Review</button>
            <div id="reviewMsg" class="mt-2"></div>
        </form>
        <?php else: ?>
        <div class="alert alert-info">Please <a href="login.php">login</a> to leave a review.</div>
        <?php endif; ?>
        <div id="reviewsList"></div>
    </div>
</div>
<script>
const stars = document.querySelectorAll('#starRating .star');
let selectedRating = 0;
stars.forEach(star => {
    star.addEventListener('mouseenter', function() {
        const val = parseInt(this.getAttribute('data-value'));
        stars.forEach(s => {
            if (parseInt(s.getAttribute('data-value')) <= val) {
                s.classList.add('hovered');
            } else {
                s.classList.remove('hovered');
            }
        });
    });
    star.addEventListener('mouseleave', function() {
        stars.forEach(s => s.classList.remove('hovered'));
    });
    star.addEventListener('click', function() {
        selectedRating = parseInt(this.getAttribute('data-value'));
        document.getElementById('ratingInput').value = selectedRating;
        stars.forEach(s => {
            if (parseInt(s.getAttribute('data-value')) <= selectedRating) {
                s.classList.add('selected');
                s.classList.remove('text-primary');
                s.classList.add('text-warning');
            } else {
                s.classList.remove('selected');
                s.classList.remove('text-warning');
                s.classList.add('text-primary');
            }
        });
    });
});
document.getElementById('starRating').addEventListener('mouseleave', function() {
    stars.forEach(s => s.classList.remove('hovered'));
});
// Submit review
const reviewForm = document.getElementById('reviewForm');
if (reviewForm) {
    reviewForm.addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        fetch('backend/add-review.php', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            console.log('data', data);
            const msgDiv = document.getElementById('reviewMsg');
            if (data.success) {
                msgDiv.innerHTML = '<div class="alert alert-success">Review submitted!</div>';
                alert('Review submitted!');
                reviewForm.reset();
                document.getElementById('ratingInput').value = 0;
                document.getElementById('reviewText').value = '';
                stars.forEach(s => {
                    s.classList.remove('selected');
                    s.classList.remove('text-warning');
                    s.classList.add('text-primary');
                });
                loadReviews();
            } else {
                msgDiv.innerHTML = '<div class="alert alert-danger">' + (data.message || 'Failed to submit review.') + '</div>';
                alert(data.message || 'Failed to submit review.');
                // Only disable after alert
                if (data.message && data.message.includes('already reviewed')) {
                    setTimeout(() => {
                        reviewForm.querySelectorAll('input, textarea, button').forEach(el => el.disabled = true);
                    }, 100); // Give time for alert to show
                }
                loadReviews();
            }
        });
    });
}
// Load reviews
function loadReviews() {
    fetch('backend/fetch-reviews.php?property_id=<?php echo $propertyId; ?>')
    .then(res => res.json())
    .then(data => {
        const reviewsDiv = document.getElementById('reviewsList');
        const avgDiv = document.getElementById('averageRating');
        if (data.success && data.reviews.length) {
            let sum = 0;
            let html = '';
            data.reviews.forEach(r => {
                sum += parseInt(r.rating);
                html += `<div class='review-item mb-3'>
                    <div><strong>${r.user_name}</strong> <span class='text-warning'>${'★'.repeat(r.rating)}${'☆'.repeat(5 - r.rating)}</span></div>
                    <div class='text-muted small mb-1'>${r.created_at}</div>
                    <div>${r.review ? r.review.replace(/</g, '&lt;') : ''}</div>
                </div>`;
            });
            reviewsDiv.innerHTML = html;
            const avg = (sum / data.reviews.length).toFixed(1);
            avgDiv.innerHTML = `<span class='text-warning fw-bold'>${avg} / 5</span> (${data.reviews.length} reviews)`;
        } else {
            reviewsDiv.innerHTML = '<div class="text-muted">No reviews yet.</div>';
            avgDiv.innerHTML = '';
        }
    });
}
loadReviews();
// Set stars to zero by default on page load
window.addEventListener('DOMContentLoaded', function() {
    document.getElementById('ratingInput').value = 0;
    stars.forEach(s => {
        s.classList.remove('selected');
        s.classList.remove('text-warning');
        s.classList.add('text-primary');
    });
});

// Function to make all links open in new tab
function makeLinksOpenInNewTab() {
    // Find all links in the page
    const links = document.querySelectorAll('a[href]');
    
    links.forEach(link => {
        const href = link.getAttribute('href');
        
        // Check if it's an external link (starts with http://, https://, or www.)
        // Also check that it doesn't contain malformed HTML
        if (href && 
            (href.startsWith('http://') || href.startsWith('https://') || href.startsWith('www.')) &&
            !href.includes('<') && !href.includes('>') && !href.includes('%')) {
            // Add target="_blank" and rel="noopener noreferrer" for security
            link.setAttribute('target', '_blank');
            link.setAttribute('rel', 'noopener noreferrer');
        }
    });
    
    // Also handle any dynamically added content (like reviews)
    const observer = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            if (mutation.type === 'childList') {
                mutation.addedNodes.forEach(function(node) {
                    if (node.nodeType === 1) { // Element node
                        const newLinks = node.querySelectorAll ? node.querySelectorAll('a[href]') : [];
                        newLinks.forEach(link => {
                            const href = link.getAttribute('href');
                            if (href && 
                                (href.startsWith('http://') || href.startsWith('https://') || href.startsWith('www.')) &&
                                !href.includes('<') && !href.includes('>') && !href.includes('%')) {
                                link.setAttribute('target', '_blank');
                                link.setAttribute('rel', 'noopener noreferrer');
                            }
                        });
                    }
                });
            }
        });
    });
    
    // Start observing the document body for changes
    observer.observe(document.body, {
        childList: true,
        subtree: true
    });
}

// Call the function when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    makeLinksOpenInNewTab();
});
</script>
</main>
<!-- Bootstrap 5 JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<!-- Swiper JS -->
<script src="https://cdn.jsdelivr.net/npm/swiper@10/swiper-bundle.min.js"></script>
<!-- Custom JS -->
<script src="js/property-detail.js"></script>
<script src="js/property-buy.js"></script>
<?php 'inc/footer.php'?>