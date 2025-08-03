// Main navigation functionality
document.addEventListener("DOMContentLoaded", function () {
  // Initialize Bootstrap components
  const tooltips = document.querySelectorAll('[data-bs-toggle="tooltip"]');
  tooltips.forEach((tooltip) => new bootstrap.Tooltip(tooltip));

  const dropdowns = document.querySelectorAll(".dropdown-toggle");
  dropdowns.forEach((dropdown) => new bootstrap.Dropdown(dropdown));

  // Handle mobile menu toggle
  const navbarToggler = document.querySelector(".navbar-toggler");
  const navbarCollapse = document.querySelector(".navbar-collapse");

  if (navbarToggler && navbarCollapse) {
    navbarToggler.addEventListener("click", function () {
      navbarCollapse.classList.toggle("show");
    });

    // Close mobile menu when clicking outside
    document.addEventListener("click", function (event) {
      if (
        !navbarToggler.contains(event.target) &&
        !navbarCollapse.contains(event.target)
      ) {
        navbarCollapse.classList.remove("show");
      }
    });
  }

  // Handle More dropdown menu and admin panel visibility
  function addAdminPanelLink() {
    const moreDropdown = document.querySelector(".dropdown-menu");
    if (moreDropdown) {
      // Check if admin panel link already exists
      const existingAdminLink = moreDropdown.querySelector(
        '[href="admin-panel.html"]'
      );
      if (!existingAdminLink) {
        // Create divider
        // const divider = document.createElement("li");
        // divider.innerHTML = '<hr class="dropdown-divider">';
        // Create admin panel link
        // const adminItem = document.createElement("li");
        // adminItem.innerHTML = `
        //             <a class="dropdown-item" href="admin-panel.html">
        //                 <i class="fas fa-user-shield me-2"></i>Admin Panel
        //             </a>
        //         `;
        // Add divider and admin link to dropdown
        // moreDropdown.appendChild(divider);
        // moreDropdown.appendChild(adminItem);
      }
    }
  }

  // Always show admin panel for now (you can add proper authentication later)
  addAdminPanelLink();

  // Note: Active class is now handled by PHP in the header
  // JavaScript active class logic removed to prevent conflicts
});

document.addEventListener("DOMContentLoaded", function () {
  console.log("Fetching latest properties...");
  fetch("backend/fetch-latest-properties.php")
    .then((res) => res.json())
    .then((data) => {
      console.log("API Response:", data);
      const section = document.getElementById("latestPropertySection");
      console.log("Section element:", section);

      if (data.status === "success" && data.properties.length) {
        const createCard = (property) => {
          const image =
            property.images && property.images.length
              ? property.images[0]
              : "images/placeholder.jpg";
          const bookmarkIconClass = property.is_saved
            ? "fas fa-bookmark text-primary"
            : "far fa-bookmark";
          const areaText =
            property.area && property.unit
              ? `${property.area} ${property.unit}`
              : "";

          return `
                    <div class="col-lg-3 col-md-6 mb-4">
                        <div class="property-card card h-100 shadow-sm">
                            <div class="property-image-wrapper position-relative">
                                <img src="${image}" class="card-img-top" alt="${
            property.title
          }" style="height: 200px; object-fit: cover;">
                                <div class="verified-badge position-absolute top-0 start-0 m-2">
                                    <i class="fas fa-check-circle"></i> Verified
                                </div>
                                <button class="favorite-btn position-absolute top-0 end-0 m-2" 
                                        data-property-id="${property.id}" 
                                        data-owner-id="${property.user_id}" 
                                        data-bs-toggle="tooltip" 
                                        title="Save Property">
                                    <i class="${bookmarkIconClass}"></i>
                                </button>
                            </div>
                            <a href="view-property-detail.php?id=${
                              property.id
                            }" class="text-decoration-none text-dark">
                                <div class="card-body">
                                    <h6 class="card-title fw-bold">${
                                      property.title
                                    }</h6>
                                    <p class="card-text text-primary fw-bold mb-2">PKR ${Number(
                                      property.price
                                    ).toLocaleString()}</p>
                                    <p class="card-text text-muted small mb-2">
                                        <i class="fas fa-map-marker-alt me-1"></i> 
                                        ${
                                          property.location ||
                                          property.city ||
                                          "Location not specified"
                                        }
                                    </p>
                                    <div class="property-features small text-muted">
                                        ${
                                          property.type
                                            ? `<span class="me-2"><i class="fas fa-home me-1"></i>${property.type}</span>`
                                            : ""
                                        }
                                        ${
                                          areaText
                                            ? `<span><i class="fas fa-ruler-combined me-1"></i>${areaText}</span>`
                                            : ""
                                        }
                                    </div>
                                </div>
                            </a>
                        </div>
                    </div>
                `;
        };

        const html = data.properties
          .map((property) => createCard(property))
          .join("");
        section.innerHTML = html;

        const tooltips = document.querySelectorAll(
          '[data-bs-toggle="tooltip"]'
        );
        tooltips.forEach((el) => new bootstrap.Tooltip(el));

        document.querySelectorAll(".favorite-btn").forEach((btn) => {
          btn.addEventListener("click", function () {
            const propertyId = this.getAttribute("data-property-id");
            const ownerId = this.getAttribute("data-owner-id");
            const icon = this.querySelector("i");

            fetch("backend/check-session.php")
              .then((res) => res.json())
              .then((sessionData) => {
                if (!sessionData.logged_in) {
                  iziToast.warning({
                    title: "Login Required",
                    message: "You need to be logged in to save this property.",
                    position: "topRight",
                  });
                  return;
                }

                if (sessionData.user_id == ownerId) {
                  iziToast.error({
                    title: "Not Allowed",
                    message: "You cannot save your own property.",
                    position: "topRight",
                  });
                  return;
                }

                fetch("backend/save-property.php", {
                  method: "POST",
                  headers: {
                    "Content-Type": "application/json",
                  },
                  body: JSON.stringify({
                    property_id: propertyId,
                  }),
                })
                  .then((res) => res.json())
                  .then((saveData) => {
                    if (saveData.status === "success") {
                      if (saveData.is_saved === 1) {
                        icon.classList.remove("far");
                        icon.classList.add("fas", "text-primary");
                        iziToast.success({
                          title: "Saved",
                          message: "Property saved to favorites.",
                          position: "topRight",
                        });
                      } else {
                        icon.classList.remove("fas", "text-primary");
                        icon.classList.add("far");
                        iziToast.info({
                          title: "Removed",
                          message: "Property removed from favorites.",
                          position: "topRight",
                        });
                      }
                    } else {
                      iziToast.error({
                        title: "Error",
                        message: saveData.message || "Failed to save property.",
                        position: "topRight",
                      });
                    }
                  })
                  .catch((err) => {
                    console.error("Error saving property:", err);
                    iziToast.error({
                      title: "Error",
                      message: "Failed to save property.",
                      position: "topRight",
                    });
                  });
              })
              .catch((err) => {
                console.error("Session check failed:", err);
                iziToast.error({
                  title: "Error",
                  message: "Failed to check session.",
                  position: "topRight",
                });
              });
          });
        });
      } else {
        console.log("No properties found or error in response");
        section.innerHTML = `
          <div class="col-12">
              <p class="text-center text-muted">No listings found.</p>
          </div>
      `;
      }
    })
    .catch((err) => {
      console.error("Failed to fetch latest properties:", err);
      document.getElementById("latestPropertySection").innerHTML =
        '<p class="text-danger text-center">Failed to load latest listings.</p>';
    });
});

document.addEventListener("DOMContentLoaded", function () {
  // Initialize tooltips
  const tooltips = document.querySelectorAll('[data-bs-toggle="tooltip"]');
  tooltips.forEach((tooltip) => new bootstrap.Tooltip(tooltip));

  // Handle favorite buttons
  document.querySelectorAll(".favorite-btn").forEach((btn) => {
    btn.addEventListener("click", function () {
      const icon = this.querySelector("i");
      const isFavorite = icon.classList.contains("fas");

      // Toggle icon
      icon.classList.toggle("far");
      icon.classList.toggle("fas");

      // Add animation class
      icon.classList.add("favorite-animate");

      // Update tooltip
      const tooltip = bootstrap.Tooltip.getInstance(this);
      this.setAttribute(
        "title",
        isFavorite ? "Add to Favorites" : "Remove from Favorites"
      );
      if (tooltip) {
        tooltip.dispose();
        new bootstrap.Tooltip(this);
      }

      // Show toast notification
      showToast(isFavorite ? "Removed from Favorites" : "Added to Favorites");

      // Remove animation class after animation completes
      setTimeout(() => {
        icon.classList.remove("favorite-animate");
      }, 300);
    });
  });

  // Toast notification function
  function showToast(message) {
    const toastContainer = document.querySelector(".toast-container");
    const toastHTML = `
            <div class="toast align-items-center text-white bg-dark border-0" role="alert" aria-live="assertive" aria-atomic="true">
                <div class="d-flex">
                    <div class="toast-body">
                        ${message}
                    </div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
            </div>
        `;

    toastContainer.insertAdjacentHTML("beforeend", toastHTML);
    const toast = toastContainer.lastElementChild;
    const bsToast = new bootstrap.Toast(toast, {
      autohide: true,
      delay: 2000,
    });

    bsToast.show();

    // Remove toast element after it's hidden
    toast.addEventListener("hidden.bs.toast", function () {
      toast.remove();
    });
  }
});
