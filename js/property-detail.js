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
const handleBookmark = async () => {
  const bookmarkBtn = document.getElementById("bookmarkBtn");
  const bookmarkIcon = bookmarkBtn.querySelector("i");
  const propertyId =
    document.getElementById("property_id")?.value ||
    new URLSearchParams(window.location.search).get("id");

  if (!propertyId) {
    console.error("No property ID found");
    return;
  }

  // Check if property is already saved in database
  try {
    const response = await fetch("backend/fetch-save-properties.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({
        property_id: parseInt(propertyId),
      }),
    });

    const data = await response.json();
    const isSaved = data.status === "success" && data.is_saved === 1;

    // Update initial button state based on database
    if (isSaved) {
      bookmarkIcon.classList.replace("far", "fas");
    } else {
      bookmarkIcon.classList.replace("fas", "far");
    }
  } catch (err) {
    console.error("Failed to check save status:", err);
    // Keep default state (outline bookmark)
    bookmarkIcon.classList.replace("fas", "far");
  }

  bookmarkBtn.addEventListener("click", async () => {
    const isSaved = bookmarkIcon.classList.contains("fas");

    try {
      const response = await fetch("backend/save-property.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
          property_id: parseInt(propertyId),
          action: isSaved ? "remove" : "add",
        }),
      });

      const data = await response.json();

      // Check if user is not logged in
      if (data.status === "error" && data.message === "User not logged in") {
        showToast("Please login to save properties", "error");
        return;
      }

      if (data.status === "success") {
        // Update UI based on backend response
        if (data.is_saved === 1) {
          // Property is now saved
          bookmarkIcon.classList.replace("far", "fas");
          showToast("Property saved successfully");
        } else {
          // Property is now unsaved
          bookmarkIcon.classList.replace("fas", "far");
          showToast("Property removed from saved properties");
        }
      } else {
        showToast(data.message || "Failed to update saved property", "error");
      }
    } catch (err) {
      console.error("Failed to toggle save:", err);
      showToast("An error occurred while saving the property", "error");
    }
  });
};

// Handle Report Modal
const handleReport = () => {
  const reportBtn = document.getElementById("reportBtn");

  if (!reportBtn) {
    console.log("Report button not found, skipping report functionality");
    return;
  }

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

  if (!form) {
    console.error("Contact form not found!");
    return;
  }

  console.log("Setting up contact form handler");

  form.addEventListener("submit", async (e) => {
    console.log("Form submission intercepted");
    e.preventDefault();
    e.stopPropagation();
    e.stopImmediatePropagation();

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
      property_id: document.getElementById("property_id").value,
    };

    // Show loading state
    const submitBtn = form.querySelector('button[type="submit"]');
    const originalBtnText = submitBtn.innerHTML;
    submitBtn.innerHTML =
      '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Sending...';
    submitBtn.disabled = true;

    try {
      // Send to backend
      const response = await fetch("backend/contact-agent.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(formData),
      });

      const data = await response.json();

      if (data.status === "success") {
        // Store the property ID before reset
        const propertyIdValue = document.getElementById("property_id").value;

        // Reset form
        form.reset();
        form.classList.remove("was-validated");

        // Restore the property ID after reset
        document.getElementById("property_id").value = propertyIdValue;

        // Show success message
        const alertHtml = `
          <div class="alert alert-success alert-dismissible fade show mt-3" role="alert">
            ${data.message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
          </div>
        `;
        form.insertAdjacentHTML("beforebegin", alertHtml);

        // Show toast notification
        showToast("Message sent successfully!", "success");
      } else {
        // Show error message
        const alertHtml = `
          <div class="alert alert-danger alert-dismissible fade show mt-3" role="alert">
            ${data.message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
          </div>
        `;
        form.insertAdjacentHTML("beforebegin", alertHtml);

        // Show toast notification
        showToast(data.message, "error");
      }
    } catch (error) {
      console.error("Error sending message:", error);

      // Show error message
      const alertHtml = `
        <div class="alert alert-danger alert-dismissible fade show mt-3" role="alert">
          An error occurred while sending your message. Please try again.
          <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
      `;
      form.insertAdjacentHTML("beforebegin", alertHtml);

      // Show toast notification
      showToast("An error occurred while sending your message", "error");
    } finally {
      // Reset button
      submitBtn.innerHTML = originalBtnText;
      submitBtn.disabled = false;
    }
  });
};

// Toast notification function
const showToast = (message, type = "success") => {
  // Check if iziToast is available (from listings page)
  if (typeof iziToast !== "undefined") {
    iziToast[type]({
      title: type === "success" ? "Success" : "Error",
      message: message,
      position: "topRight",
    });
  } else {
    // Fallback to alert if iziToast is not available
    alert(message);
  }
};

// Initialize all functionality when DOM is loaded
document.addEventListener("DOMContentLoaded", async () => {
  console.log("DOM loaded, initializing property detail functionality");

  initializeGallery();
  handleVideoSection();
  await handleBookmark();
  handleReport();
  handleContactForm();

  // Initialize all tooltips
  const tooltips = document.querySelectorAll('[data-bs-toggle="tooltip"]');
  tooltips.forEach((tooltip) => new bootstrap.Tooltip(tooltip));
});

// Also set up form handler immediately if DOM is already loaded
if (document.readyState === "loading") {
  // DOM is still loading, wait for DOMContentLoaded
} else {
  // DOM is already loaded, set up form handler immediately
  console.log("DOM already loaded, setting up form handler immediately");
  handleContactForm();
}
