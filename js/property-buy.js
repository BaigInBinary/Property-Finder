// Modal logic for property buying

document.addEventListener("DOMContentLoaded", function () {
  const form = document.getElementById("checkout-form");
  const buyPropertyBtn = document.getElementById("buyPropertyBtn");
  let checkoutModal = null;
  if (window.bootstrap && document.getElementById("checkoutModal")) {
    checkoutModal = new bootstrap.Modal(
      document.getElementById("checkoutModal")
    );
  }

  // Check if user is property creator and prevent modal opening
  if (buyPropertyBtn) {
    buyPropertyBtn.addEventListener("click", function (e) {
      const propertyCreator = this.getAttribute("data-property-creator");
      const currentUser = this.getAttribute("data-current-user");

      // Check if user is logged in
      if (!currentUser || currentUser === "0") {
        e.preventDefault();
        e.stopPropagation();
        alert("Please login to buy this property.");
        return false;
      }

      // Check if current user is the property creator
      if (propertyCreator && currentUser && propertyCreator === currentUser) {
        e.preventDefault();
        e.stopPropagation();
        alert(
          "You cannot buy your own property. You are the one who posted this property."
        );
        return false;
      }
    });
  }

  // Autofill email if available (even if disabled)
  const emailInput = document.getElementById("checkout-email");
  if (emailInput && emailInput.hasAttribute("value")) {
    emailInput.value = emailInput.getAttribute("value");
  }

  // Cardholder name validation - only letters and spaces
  const cardholderNameInput = document.getElementById("checkout-name");
  if (cardholderNameInput) {
    cardholderNameInput.addEventListener("input", function (e) {
      // Remove any non-letter characters except spaces
      let value = this.value.replace(/[^a-zA-Z\s]/g, "");
      // Remove extra spaces
      value = value.replace(/\s+/g, " ").trim();
      this.value = value;
    });
  }

  // Card number formatting: XXXX XXXX XXXX XXXX
  const cardInput = document.getElementById("checkout-card");
  if (cardInput) {
    cardInput.addEventListener("input", function (e) {
      let value = cardInput.value.replace(/\D/g, "");
      value = value.substring(0, 16);
      let formatted = "";
      for (let i = 0; i < value.length; i += 4) {
        if (i > 0) formatted += " ";
        formatted += value.substring(i, i + 4);
      }
      cardInput.value = formatted;
    });
  }

  // Expiry formatting: MM/YY
  const expiryInput = document.getElementById("checkout-expiry");
  if (expiryInput) {
    expiryInput.addEventListener("input", function (e) {
      let value = expiryInput.value.replace(/\D/g, "");
      value = value.substring(0, 4);
      if (value.length > 2) {
        value = value.substring(0, 2) + "/" + value.substring(2, 4);
      }
      expiryInput.value = value;
    });
  }

  // Function to validate expiry date
  function validateExpiryDate(expiry) {
    if (!expiry || expiry.length !== 5) return false;

    const [month, year] = expiry.split("/");
    const currentDate = new Date();
    const currentYear = currentDate.getFullYear() % 100; // Get last 2 digits
    const currentMonth = currentDate.getMonth() + 1; // January is 0

    const expMonth = parseInt(month);
    const expYear = parseInt(year);

    // Debug logging
    console.log("Expiry validation:", {
      input: expiry,
      month: expMonth,
      year: expYear,
      currentYear: currentYear,
      currentMonth: currentMonth,
    });

    // Check if month is valid (1-12)
    if (expMonth < 1 || expMonth > 12) {
      console.log("Invalid month:", expMonth);
      return false;
    }

    // Check if expiry date is in the future
    if (expYear < currentYear) {
      console.log("Year in past:", expYear, "<", currentYear);
      return false;
    }
    if (expYear === currentYear && expMonth < currentMonth) {
      console.log(
        "Month in past for current year:",
        expMonth,
        "<",
        currentMonth
      );
      return false;
    }

    return true;
  }

  // CVV: only 3 digits
  const cvvInput = document.getElementById("checkout-cvv");
  if (cvvInput) {
    cvvInput.addEventListener("input", function (e) {
      let value = cvvInput.value.replace(/\D/g, "");
      value = value.substring(0, 3);
      cvvInput.value = value;
    });
  }

  // Handle form submit
  if (form) {
    form.addEventListener("submit", function (e) {
      e.preventDefault();
      let email = emailInput.disabled
        ? emailInput.getAttribute("value")
        : emailInput.value;
      const card = cardInput.value.replace(/\s/g, "");
      const expiry = expiryInput.value;
      const cvv = cvvInput.value;
      const cardholderName = document
        .getElementById("checkout-name")
        .value.trim();
      const urlParams = new URLSearchParams(window.location.search);
      const propertyId = urlParams.get("id");

      // Validate cardholder name
      if (!cardholderName) {
        alert("Please enter the cardholder name.");
        return;
      }

      // Check if cardholder name contains only letters and spaces
      if (!/^[a-zA-Z\s]+$/.test(cardholderName)) {
        alert("Cardholder name should only contain letters and spaces.");
        return;
      }

      // Check if cardholder name has at least 2 characters
      if (cardholderName.length < 2) {
        alert("Please enter a valid cardholder name (at least 2 characters).");
        return;
      }

      // Validate expiry date
      if (!validateExpiryDate(expiry)) {
        const [month, year] = expiry.split("/");
        const expMonth = parseInt(month);

        if (expMonth < 1 || expMonth > 12) {
          alert("Invalid month. Month must be between 01 and 12.");
        } else {
          alert(
            "Please enter a valid expiry date (MM/YY format). The card must not be expired."
          );
        }
        return;
      }

      // Validate card number (basic check)
      if (card.length !== 16) {
        alert("Please enter a valid 16-digit card number.");
        return;
      }

      // Validate CVV
      if (cvv.length < 3) {
        alert("Please enter a valid CVV.");
        return;
      }

      fetch("backend/property-buy-request.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
          property_id: propertyId,
          email: email,
          cardholder_name: cardholderName,
          card: card,
          expiry: expiry,
          cvv: cvv,
        }),
      })
        .then((res) => res.json())
        .then((data) => {
          if (data.success) {
            form.reset();
            if (checkoutModal) checkoutModal.hide();
            alert(
              "Your request has been submitted! You will be notified after admin approval."
            );
          } else {
            alert(data.message || "Failed to submit request.");
          }
        })
        .catch(() => {
          alert("An error occurred. Please try again.");
        });
    });
  }
});
