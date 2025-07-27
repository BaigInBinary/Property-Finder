// Global variables
let selectedProperties = [];
const MAX_PROPERTIES = 3;

// DOM Elements
const propertySelect = document.getElementById("propertySelect");
const comparisonGrid = document.getElementById("comparisonGrid");
const emptyState = document.getElementById("emptyState");
const selectedCount = document.getElementById("selectedCount");
const clearAllBtn = document.getElementById("clearAll");
const propertyTemplate = document.getElementById("propertyCardTemplate");

// Use dynamic properties from PHP
const properties = window.allProperties || [];

// Format price in PKR
const formatPrice = (price) => {
  return `PKR ${Number(price || 0).toLocaleString()}`;
};

// Format size with unit
const formatSize = (area, unit) => {
  return `${Number(area || 0).toLocaleString()} ${unit || ""}`;
};

// Create property card
const createPropertyCard = (property) => {
  const template = propertyTemplate.content.cloneNode(true);
  const card = template.querySelector(".property-card");

  card.setAttribute("data-property-id", property.id);

  const imgSrc = property.images?.[0] || "https://placehold.co/800x600/png";
  card.querySelector("img").src = imgSrc;
  card.querySelector(".property-title").textContent =
    property.title || "No Title";
  card.querySelector(".property-location span").textContent =
    property.location || "N/A";
  card.querySelector(".price").textContent = formatPrice(property.price);
  card.querySelector(".size").textContent = formatSize(
    property.area,
    property.unit
  );
  card.querySelector(".type").textContent = property.type || "Unknown";

  const featuresList = card.querySelector(".features-list");
  if (property.features && Array.isArray(property.features)) {
    property.features.forEach((feature) => {
      const li = document.createElement("li");
      li.textContent = feature;
      featuresList.appendChild(li);
    });
  }

  card.querySelector(".remove-property").addEventListener("click", () => {
    removeProperty(property.id);
  });

  card.querySelector(
    ".view-details"
  ).href = `view-property-detail.php?id=${property.id}`;
  return card;
};

// Add property to comparison
const addProperty = (propertyId) => {
  if (selectedProperties.length >= MAX_PROPERTIES) {
    alert("You can compare up to 3 properties at a time");
    return;
  }

  const property = properties.find((p) => String(p.id) === String(propertyId));
  if (!property) return;

  if (selectedProperties.some((p) => String(p.id) === String(property.id))) {
    alert("This property is already in comparison");
    return;
  }

  selectedProperties.push(property);
  updateComparisonGrid();
  updatePropertySelect();
};

// Remove property from comparison
const removeProperty = (propertyId) => {
  selectedProperties = selectedProperties.filter((p) => p.id !== propertyId);
  updateComparisonGrid();
  updatePropertySelect();
};

// Update comparison grid
const updateComparisonGrid = () => {
  emptyState.style.display = selectedProperties.length === 0 ? "block" : "none";

  comparisonGrid
    .querySelectorAll(".property-card")
    .forEach((card) => card.remove());

  selectedProperties.forEach((property) => {
    const card = createPropertyCard(property);
    comparisonGrid.appendChild(card);
  });

  selectedCount.textContent = selectedProperties.length;
  highlightDifferences();
};

// Update property select options
const updatePropertySelect = () => {
  const options = propertySelect.querySelectorAll("option");
  options.forEach((option) => {
    if (option.value) {
      const isSelected = selectedProperties.some(
        (p) => p.id === parseInt(option.value)
      );
      option.disabled = isSelected;
    }
  });

  propertySelect.value = "";
  propertySelect.disabled = selectedProperties.length >= MAX_PROPERTIES;
};

// Highlight differences between properties
const highlightDifferences = () => {
  if (selectedProperties.length < 2) return;

  const specs = ["price", "size", "type"];
  specs.forEach((spec) => {
    const values = selectedProperties.map((p) => p[spec]);
    const allSame = values.every((v) => v === values[0]);

    if (!allSame) {
      document.querySelectorAll(`.spec-value.${spec}`).forEach((el) => {
        el.classList.add("highlight");
      });
    }
  });
};

// Event Listeners
propertySelect.addEventListener("change", (e) => {
  if (e.target.value) {
    addProperty(e.target.value);
  }
});

clearAllBtn.addEventListener("click", () => {
  selectedProperties = [];
  updateComparisonGrid();
  updatePropertySelect();
});

// Initialize
document.addEventListener("DOMContentLoaded", () => {
  updateComparisonGrid();
});
