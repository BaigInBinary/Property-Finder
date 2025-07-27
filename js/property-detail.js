// Initialize Swiper for image gallery
const initializeGallery = () => {
  const swiper = new Swiper(".swiper", {
    loop: true,
    pagination: {
      el: ".swiper-pagination",
      clickable: true,
    },
    navigation: {
      nextEl: ".swiper-button-next",
      prevEl: ".swiper-button-prev",
    },
  });
};

// Handle Video Tour Section
const handleVideoSection = () => {
  const videoSection = document.getElementById("videoTourSection");
  const videoUrl = videoSection.querySelector("iframe").src;

  // Show/hide video section based on video URL
  if (videoUrl && videoUrl !== "https://www.youtube.com/embed/your-video-id") {
    videoSection.style.display = "block";
  } else {
    videoSection.style.display = "none";
  }
};

// Handle Bookmark/Save functionality
const handleBookmark = () => {
  const bookmarkBtn = document.getElementById("bookmarkBtn");
  const bookmarkIcon = bookmarkBtn.querySelector("i");
  const propertyId = new URLSearchParams(window.location.search).get("id");

  // Check if property is already saved
  const savedProperties =
    JSON.parse(localStorage.getItem("savedProperties")) || [];
  const isSaved = savedProperties.some((p) => p.id === propertyId);

  // Update initial button state
  if (isSaved) {
    bookmarkIcon.classList.replace("far", "fas");
    bookmarkBtn.classList.remove("btn-primary");
    bookmarkBtn.classList.add("btn-primary");
  }

  bookmarkBtn.addEventListener("click", () => {
    const isSaved = bookmarkIcon.classList.contains("fas");
    const savedProperties =
      JSON.parse(localStorage.getItem("savedProperties")) || [];

    if (isSaved) {
      // Remove from saved properties
      const updatedProperties = savedProperties.filter(
        (p) => p.id !== propertyId
      );
      localStorage.setItem(
        "savedProperties",
        JSON.stringify(updatedProperties)
      );

      // Update UI
      bookmarkIcon.classList.replace("fas", "far");
      bookmarkBtn.classList.remove("btn-primary");
      bookmarkBtn.classList.add("btn-primary");
      showToast("Property removed from saved properties");
    } else {
      // Get current property data
      const propertyData = {
        id: propertyId,
        title: document.querySelector(".property-title").textContent,
        location: document
          .querySelector(".property-location")
          .textContent.trim(),
        price: parseFloat(
          document
            .querySelector(".property-price")
            .textContent.replace(/[^0-9.]/g, "")
        ),
        bedrooms:
          document
            .querySelector(".property-features .bedrooms")
            ?.textContent.trim() || "",
        size:
          document
            .querySelector(".property-features .size")
            ?.textContent.trim() || "",
        type:
          document
            .querySelector(".property-features .type")
            ?.textContent.trim() || "",
        image: document.querySelector(".property-gallery img")?.src || "",
      };

      // Add to saved properties
      savedProperties.push(propertyData);
      localStorage.setItem("savedProperties", JSON.stringify(savedProperties));

      // Update UI
      bookmarkIcon.classList.replace("far", "fas");
      bookmarkBtn.classList.remove("btn-primary");
      bookmarkBtn.classList.add("btn-primary");
      showToast("Property saved successfully");
    }
  });
};

// Handle Report Modal
const handleReport = () => {
  const reportBtn = document.getElementById("reportBtn");

  reportBtn.addEventListener("click", () => {
    // Create and show modal
    const modalHtml = `
            <div class="modal fade" id="reportModal" tabindex="-1">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Report Property</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <form id="reportForm">
                                <div class="mb-3">
                                    <label class="form-label">Reason for reporting</label>
                                    <select class="form-select" required>
                                        <option value="">Select a reason</option>
                                        <option value="incorrect">Incorrect Information</option>
                                        <option value="spam">Spam</option>
                                        <option value="duplicate">Duplicate Listing</option>
                                        <option value="fraud">Potential Fraud</option>
                                        <option value="other">Other</option>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Additional Details</label>
                                    <textarea class="form-control" rows="3" required></textarea>
                                </div>
                            </form>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="button" class="btn btn-danger">Submit Report</button>
                        </div>
                    </div>
                </div>
            </div>
        `;

    // Add modal to document if it doesn't exist
    if (!document.getElementById("reportModal")) {
      document.body.insertAdjacentHTML("beforeend", modalHtml);
    }

    // Show modal
    const reportModal = new bootstrap.Modal(
      document.getElementById("reportModal")
    );
    reportModal.show();
  });
};

// Contact Form Validation
const handleContactForm = () => {
  const form = document.getElementById("contactForm");

  form.addEventListener("submit", (e) => {
    e.preventDefault();

    if (!form.checkValidity()) {
      e.stopPropagation();
      form.classList.add("was-validated");
      return;
    }

    // Get form data
    const formData = {
      name: document.getElementById("name").value,
      email: document.getElementById("email").value,
      phone: document.getElementById("phone").value,
      message: document.getElementById("message").value,
    };

    // Show loading state
    const submitBtn = form.querySelector('button[type="submit"]');
    const originalBtnText = submitBtn.innerHTML;
    submitBtn.innerHTML =
      '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Sending...';
    submitBtn.disabled = true;

    // Simulate API call
    setTimeout(() => {
      // Reset form
      form.reset();
      form.classList.remove("was-validated");

      // Show success message
      const alertHtml = `
                <div class="alert alert-success alert-dismissible fade show mt-3" role="alert">
                    Your message has been sent successfully!
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            `;
      form.insertAdjacentHTML("beforebegin", alertHtml);

      // Reset button
      submitBtn.innerHTML = originalBtnText;
      submitBtn.disabled = false;
    }, 1500);
  });
};

// Initialize all functionality when DOM is loaded
document.addEventListener("DOMContentLoaded", () => {
  initializeGallery();
  handleVideoSection();
  handleBookmark();
  handleReport();
  handleContactForm();

  // Initialize all tooltips
  const tooltips = document.querySelectorAll('[data-bs-toggle="tooltip"]');
  tooltips.forEach((tooltip) => new bootstrap.Tooltip(tooltip));
});
