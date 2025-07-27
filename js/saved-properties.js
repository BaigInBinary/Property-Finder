// DOM Elements
const savedPropertiesGrid = document.getElementById('savedPropertiesGrid');
const emptyState = document.getElementById('emptyState');

// Load saved properties from localStorage
const loadSavedProperties = () => {
    const savedProperties = JSON.parse(localStorage.getItem('savedProperties')) || [];
    return savedProperties;
};

// Save properties to localStorage
const saveProperties = (properties) => {
    localStorage.setItem('savedProperties', JSON.stringify(properties));
};

// Create property card
const createPropertyCard = (property) => {
    const card = document.createElement('div');
    card.className = 'col-md-6 col-lg-4';
    card.innerHTML = `
        <div class="card property-card" data-property-id="${property.id}">
            <div class="property-image-wrapper">
                <img src="${property.image || 'images/property-placeholder.jpg'}" class="card-img-top" alt="${property.title}">
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
                    ${property.bedrooms ? `<span><i class="fas fa-bed"></i> ${property.bedrooms}</span>` : ''}
                    ${property.size ? `<span><i class="fas fa-ruler-combined"></i> ${property.size}</span>` : ''}
                    ${property.type ? `<span><i class="fas fa-home"></i> ${property.type}</span>` : ''}
                </div>
            </div>
            <div class="card-footer bg-white">
                <a href="property-detail.html?id=${property.id}" class="btn btn-primary w-100">View Details</a>
            </div>
        </div>
    `;

    // Add remove functionality
    const removeBtn = card.querySelector('.bookmark-btn');
    removeBtn.addEventListener('click', () => removeProperty(property.id));

    return card;
};

// Remove property from saved list
const removeProperty = (propertyId) => {
    const card = document.querySelector(`[data-property-id="${propertyId}"]`).parentElement;
    card.querySelector('.property-card').classList.add('removing');
    
    // Wait for animation to complete
    setTimeout(() => {
        let savedProperties = loadSavedProperties();
        savedProperties = savedProperties.filter(p => p.id !== propertyId);
        saveProperties(savedProperties);
        
        card.remove();
        
        // Show empty state if no properties left
        if (savedProperties.length === 0) {
            emptyState.classList.remove('d-none');
        }

        // Show toast notification
        showToast('Property removed from saved properties');
    }, 300);
};

// Initialize the page
document.addEventListener('DOMContentLoaded', () => {
    const savedProperties = loadSavedProperties();
    
    if (savedProperties.length === 0) {
        emptyState.classList.remove('d-none');
        return;
    }

    savedProperties.forEach(property => {
        const propertyCard = createPropertyCard(property);
        savedPropertiesGrid.appendChild(propertyCard);
    });

    // Initialize tooltips
    const tooltips = document.querySelectorAll('[data-bs-toggle="tooltip"]');
    tooltips.forEach(tooltip => new bootstrap.Tooltip(tooltip));
}); 