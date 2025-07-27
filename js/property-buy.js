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
      const urlParams = new URLSearchParams(window.location.search);
      const propertyId = urlParams.get("id");

      fetch("backend/property-buy-request.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
          property_id: propertyId,
          email: email,
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
