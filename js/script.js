document.addEventListener("DOMContentLoaded", function () {
  const form = document.getElementById("propertySearchForm");
  const minPrice = document.getElementById("minPrice");
  const maxPrice = document.getElementById("maxPrice");
  const city = document.getElementById("city");
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

    // Check if any search criteria is entered
    const hasMinPrice = minPrice.value.trim() !== "";
    const hasMaxPrice = maxPrice.value.trim() !== "";
    const hasCity = city.value.trim() !== "";
    const hasPropertyType = propertyType.value.trim() !== "";
    const hasArea = area.value.trim() !== "" && area.value > 0;

    // If no search criteria is entered, show error and prevent navigation
    if (
      !hasMinPrice &&
      !hasMaxPrice &&
      !hasCity &&
      !hasPropertyType &&
      !hasArea
    ) {
      showNoSearchCriteriaError();
      return false;
    }

    // Validate price range if both min and max are provided
    if (hasMinPrice && hasMaxPrice) {
      const min = parseFloat(minPrice.value);
      const max = parseFloat(maxPrice.value);

      if (min > max) {
        setInvalidState(
          minPrice,
          "Minimum price cannot be greater than maximum price"
        );
        setInvalidState(
          maxPrice,
          "Maximum price must be greater than minimum price"
        );
        return false;
      }
    }

    // Validate area if provided
    if (hasArea && area.value <= 0) {
      setInvalidState(area, "Please enter a valid area");
      return false;
    }

    // If validation passes, proceed with search
    // Prepare search data
    const searchData = {
      minPrice: minPrice.value,
      maxPrice: maxPrice.value,
      city: city.value,
      propertyType: propertyType.value,
      area: area.value,
      areaUnit: areaUnit.value,
    };

    // Redirect to search results page with search parameters
    const searchParams = new URLSearchParams(searchData);
    window.location.href = `search-results.php?${searchParams.toString()}`;
  });

  // Real-time validation
  [minPrice, maxPrice, city, propertyType, area].forEach((input) => {
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
    [minPrice, maxPrice, city, propertyType, area].forEach((element) => {
      resetValidationState(element);
    });
  }

  function showNoSearchCriteriaError() {
    // Create a general error message for the form
    let formError = document.getElementById("formError");
    if (!formError) {
      formError = document.createElement("div");
      formError.id = "formError";
      formError.className = "alert alert-warning mt-3";
      formError.style.display = "block";
      form.insertBefore(formError, form.firstChild);
    }
    formError.textContent =
      "Please enter at least one search criteria to find properties.";
    formError.style.display = "block";

    // Hide the error after 5 seconds
    setTimeout(() => {
      formError.style.display = "none";
    }, 5000);
  }
});
