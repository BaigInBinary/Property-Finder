document.addEventListener("DOMContentLoaded", function () {
  const form = document.getElementById("propertySearchForm");
  const minPrice = document.getElementById("minPrice");
  const maxPrice = document.getElementById("maxPrice");
  const location = document.getElementById("location");
  const propertyType = document.getElementById("propertyType");
  const area = document.getElementById("area");
  const areaUnit = document.getElementById("areaUnit");
  const searchButton = document.getElementById("searchButton");

  // Add price range validation
  minPrice.addEventListener("input", validatePriceRange);
  maxPrice.addEventListener("input", validatePriceRange);

  function validatePriceRange() {
    const min = parseFloat(minPrice.value);
    const max = parseFloat(maxPrice.value);

    if (min && max && min > max) {
      minPrice.classList.add("is-invalid");
      maxPrice.classList.add("is-invalid");
      searchButton.disabled = true;

      // Update error messages
      const minPriceFeedback = minPrice.nextElementSibling;
      const maxPriceFeedback = maxPrice.nextElementSibling;

      if (
        minPriceFeedback &&
        minPriceFeedback.classList.contains("invalid-feedback")
      ) {
        minPriceFeedback.textContent =
          "Minimum price cannot be greater than maximum price";
      }
      if (
        maxPriceFeedback &&
        maxPriceFeedback.classList.contains("invalid-feedback")
      ) {
        maxPriceFeedback.textContent =
          "Maximum price must be greater than minimum price";
      }

      return false;
    } else {
      minPrice.classList.remove("is-invalid");
      maxPrice.classList.remove("is-invalid");
      searchButton.disabled = false;

      // Reset error messages
      const minPriceFeedback = minPrice.nextElementSibling;
      const maxPriceFeedback = maxPrice.nextElementSibling;

      if (
        minPriceFeedback &&
        minPriceFeedback.classList.contains("invalid-feedback")
      ) {
        minPriceFeedback.textContent = "Please enter a valid minimum price";
      }
      if (
        maxPriceFeedback &&
        maxPriceFeedback.classList.contains("invalid-feedback")
      ) {
        maxPriceFeedback.textContent = "Please enter a valid maximum price";
      }

      return true;
    }
  }

  // Form validation
  form.addEventListener("submit", function (e) {
    e.preventDefault(); // Prevent default form submission

    // Reset validation states
    resetValidationStates();

    // Validate all fields
    let isValid = true;

    if (!location.value) {
      setInvalidState(location, "Please select a location");
      isValid = false;
    }

    if (!propertyType.value) {
      setInvalidState(propertyType, "Please select a property type");
      isValid = false;
    }

    if (!area.value || area.value <= 0) {
      setInvalidState(area, "Please enter a valid area");
      isValid = false;
    }

    // Validate price range
    const min = parseFloat(minPrice.value);
    const max = parseFloat(maxPrice.value);

    if (!minPrice.value) {
      setInvalidState(minPrice, "Please enter a minimum price");
      isValid = false;
    }

    if (!maxPrice.value) {
      setInvalidState(maxPrice, "Please enter a maximum price");
      isValid = false;
    }

    if (min && max && min > max) {
      setInvalidState(
        minPrice,
        "Minimum price cannot be greater than maximum price"
      );
      setInvalidState(
        maxPrice,
        "Maximum price must be greater than minimum price"
      );
      isValid = false;
    }

    if (isValid) {
      // Prepare search data
      const searchData = {
        minPrice: minPrice.value,
        maxPrice: maxPrice.value,
        location: location.value,
        propertyType: propertyType.value,
        area: area.value,
        areaUnit: areaUnit.value,
      };

      // Redirect to listings page with search parameters
      const searchParams = new URLSearchParams(searchData);
      window.location.href = `listings.php?${searchParams.toString()}`;
    }
  });

  // Real-time validation
  [minPrice, maxPrice, location, propertyType, area].forEach((input) => {
    input.addEventListener("change", function () {
      if (this.value) {
        resetValidationState(this);
      }
    });
  });

  // Helper functions
  function setInvalidState(element, message) {
    element.classList.add("is-invalid");

    // Create or update invalid feedback message
    let feedback = element.nextElementSibling;
    if (!feedback || !feedback.classList.contains("invalid-feedback")) {
      feedback = document.createElement("div");
      feedback.className = "invalid-feedback";
      element.parentNode.insertBefore(feedback, element.nextSibling);
    }
    feedback.textContent = message;
    feedback.style.display = "block";
  }

  function resetValidationState(element) {
    element.classList.remove("is-invalid");
    const feedback = element.nextElementSibling;
    if (feedback && feedback.classList.contains("invalid-feedback")) {
      feedback.style.display = "none";
    }
  }

  function resetValidationStates() {
    [minPrice, maxPrice, location, propertyType, area].forEach((element) => {
      resetValidationState(element);
    });
  }
});
