// DOM Elements
const savedPropertiesGrid = document.getElementById("savedPropertiesGrid");
const emptyState = document.getElementById("emptyState");

// Load saved properties from database
const loadSavedProperties = async () => {
  try {
    const response = await fetch("backend/fetch-user-saved-properties.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
    });

    const data = await response.json();
    if (data.status === "success") {
      return data.properties;
    } else {
      console.error("Failed to load saved properties:", data.message);
      return [];
    }
  } catch (error) {
    console.error("Error loading saved properties:", error);
    return [];
  }
};

// Create property card
const createPropertyCard = (property) => {
  const card = document.createElement("div");
  card.className = "col-md-6 col-lg-4";
  card.innerHTML = `
        <div class="card property-card" data-property-id="${property.id}">
            <div class="property-image-wrapper">
                <img src="${
                  property.images && property.images.length > 0
                    ? property.images[0]
                    : "images/property-placeholder.jpg"
                }" class="card-img-top" alt="${property.title}">
                <button class="btn btn-danger bookmark-btn" title="Remove from saved">
                    <i class="fas fa-bookmark"></i>
                </button>
            </div>
            <div class="card-body">
                <h5 class="card-title">${property.title}</h5>
                <p class="card-text text-primary fw-bold">PKR ${property.price.toLocaleString()}</p>
                <p class="card-text">
                    <i class="fas fa-map-marker-alt"></i> ${property.location}
                </p>
                <div class="property-features">
                    ${
                      property.bedrooms
                        ? `<span><i class="fas fa-bed"></i> ${property.bedrooms}</span>`
                        : ""
                    }
                    ${
                      property.size
                        ? `<span><i class="fas fa-ruler-combined"></i> ${property.size}</span>`
                        : ""
                    }
                    ${
                      property.type
                        ? `<span><i class="fas fa-home"></i> ${property.type}</span>`
                        : ""
                    }
                </div>
            </div>
            <div class="card-footer bg-white">
                <a href="view-property-detail.php?id=${
                  property.id
                }" class="btn btn-primary w-100">View Details</a>
            </div>
        </div>
    `;

  // Add remove functionality
  const removeBtn = card.querySelector(".bookmark-btn");
  removeBtn.addEventListener("click", () => removeProperty(property.id));

  return card;
};

// Remove property from saved list
const removeProperty = async (propertyId) => {
  const card = document.querySelector(
    `[data-property-id="${propertyId}"]`
  ).parentElement;
  card.querySelector(".property-card").classList.add("removing");

  try {
    const response = await fetch("backend/save-property.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({
        property_id: propertyId,
        action: "remove",
      }),
    });

    const data = await response.json();

    if (data.status === "success") {
      // Wait for animation to complete
      setTimeout(() => {
        card.remove();

        // Show empty state if no properties left
        const remainingCards = document.querySelectorAll(".property-card");
        if (remainingCards.length === 0) {
          emptyState.classList.remove("d-none");
        }

        // Show toast notification
        showToast("Property removed from saved properties");
      }, 300);
    } else {
      console.error("Failed to remove property:", data.message);
      showToast("Failed to remove property", "error");
      // Remove the animation class if failed
      card.querySelector(".property-card").classList.remove("removing");
    }
  } catch (error) {
    console.error("Error removing property:", error);
    showToast("An error occurred while removing the property", "error");
    // Remove the animation class if failed
    card.querySelector(".property-card").classList.remove("removing");
  }
};

// Initialize the page
document.addEventListener("DOMContentLoaded", async () => {
  const savedProperties = await loadSavedProperties();

  if (savedProperties.length === 0) {
    emptyState.classList.remove("d-none");
    return;
  }

  savedProperties.forEach((property) => {
    const propertyCard = createPropertyCard(property);
    savedPropertiesGrid.appendChild(propertyCard);
  });

  // Initialize tooltips
  const tooltips = document.querySelectorAll('[data-bs-toggle="tooltip"]');
  tooltips.forEach((tooltip) => new bootstrap.Tooltip(tooltip));
});

// Toast notification function
const showToast = (message, type = "success") => {
  // Check if iziToast is available
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
