document.addEventListener("DOMContentLoaded", function () {
  const form = document.getElementById("propertyUploadForm");
  const previewBtn = document.getElementById("previewBtn");
  const previewModal = new bootstrap.Modal(
    document.getElementById("previewModal")
  );
  const confirmSubmitBtn = document.getElementById("confirmSubmit");
  const propertyImagesInput = document.getElementById("propertyImages");
  const imagePreviewContainer = document.getElementById(
    "imagePreviewContainer"
  );
  const countSpan = document.getElementById("selectedImageCount");
  const maxAllowed = 5;

  // Check if elements exist before proceeding
  if (!propertyImagesInput || !imagePreviewContainer || !countSpan) {
    console.log("Required elements not found:", {
      propertyImagesInput: !!propertyImagesInput,
      imagePreviewContainer: !!imagePreviewContainer,
      countSpan: !!countSpan,
    });
    return;
  }

  // Helper to format field labels
  function formatLabel(key) {
    return key
      .replace(/([A-Z])/g, " $1")
      .replace(/^./, (str) => str.toUpperCase())
      .replace(/([a-z])(\d)/gi, "$1 $2");
  }

  // Helper to reset file input and preview
  function resetPropertyImageInput() {
    propertyImagesInput.value = "";
    imagePreviewContainer.innerHTML = "";
    countSpan.textContent = "";
  }
  window.resetPropertyImageInput = resetPropertyImageInput;

  // Preview modal logic
  previewBtn.addEventListener("click", function () {
    const formData = new FormData(form);
    let previewHTML = "";

    for (const [key, value] of formData.entries()) {
      if (
        key === "propertyImages[]" ||
        key === "cnicImage" ||
        key === "ownershipDocs"
      ) {
        const files =
          key === "propertyImages[]"
            ? Array.from(formData.getAll(key))
                .map((f) => f.name)
                .join(", ")
            : value.name;

        previewHTML += `
                    <div class="mb-2">
                        <strong>${formatLabel(key)}:</strong> ${files}
                    </div>`;
      } else if (key === "description") {
        // Skip 'description' from FormData to avoid raw textarea content
        continue;
      } else {
        previewHTML += `
                    <div class="mb-2">
                        <strong>${formatLabel(key)}:</strong> ${value}
                    </div>`;
      }
    }

    // Add CKEditor (description) content manually
    if (descriptionEditor) {
      previewHTML += `
                <div class="mb-2">
                    <strong>Description:</strong> ${descriptionEditor.getData()}
                </div>`;
    }

    document.getElementById("previewContent").innerHTML = previewHTML;
    previewModal.show();
  });

  // Optional: clear previews after confirm
  confirmSubmitBtn.addEventListener("click", function () {
    previewModal.hide();
    imagePreviewContainer.innerHTML = ""; // Clear thumbnails if needed
  });

  // Also clear previews on form reset
  form.addEventListener("reset", function () {
    resetPropertyImageInput();
  });

  // Image preview logic for property images
  propertyImagesInput.addEventListener("change", function (e) {
    console.log("Property images change event triggered");
    let files = Array.from(propertyImagesInput.files);
    console.log("Selected files:", files.length);

    // Limit to 5 images
    if (files.length > maxAllowed) {
      const dt = new DataTransfer();
      files.slice(0, maxAllowed).forEach((file) => dt.items.add(file));
      propertyImagesInput.files = dt.files;
      alert(
        "You can select a maximum of 5 images. Only the first 5 will be used."
      );
      files = Array.from(propertyImagesInput.files);
    }

    // Show count
    countSpan.textContent =
      files.length > 0 ? `(${files.length} selected)` : "";
    console.log("Count span updated:", countSpan.textContent);

    // Clear previous previews
    imagePreviewContainer.innerHTML = "";
    console.log("Cleared previous previews");

    // Preview images
    files.forEach((file, index) => {
      console.log(`Processing file ${index + 1}:`, file.name, file.type);
      if (!file.type.startsWith("image/")) {
        console.log("Skipping non-image file:", file.name);
        return;
      }

      const reader = new FileReader();
      reader.onload = function (e) {
        console.log("File loaded, creating preview for:", file.name);
        const img = document.createElement("img");
        img.src = e.target.result;
        img.className = "img-thumbnail";
        img.alt = file.name;
        imagePreviewContainer.appendChild(img);
        console.log("Preview image added to container");
      };
      reader.onerror = function () {
        console.error("Error reading file:", file.name);
      };
      reader.readAsDataURL(file);
    });
  });

  // CNIC Number formatting (same as signup page)
  const cnicInput = document.getElementById("cnicNumber");
  if (cnicInput) {
    console.log("CNIC input found, adding event listeners");

    cnicInput.addEventListener("input", function (e) {
      console.log("CNIC input event triggered");
      let value = cnicInput.value.replace(/\D/g, ""); // Remove all non-digits
      if (value.length > 13) value = value.slice(0, 13); // Max 13 digits

      let formatted = "";
      if (value.length > 5) {
        formatted += value.slice(0, 5) + "-";
        if (value.length > 12) {
          formatted += value.slice(5, 12) + "-" + value.slice(12, 13);
        } else if (value.length > 5) {
          formatted += value.slice(5, 12);
        }
      } else {
        formatted = value;
      }
      if (value.length > 12) {
        formatted =
          value.slice(0, 5) +
          "-" +
          value.slice(5, 12) +
          "-" +
          value.slice(12, 13);
      }
      cnicInput.value = formatted;
    });

    // Also add keypress event to prevent non-numeric input
    cnicInput.addEventListener("keypress", function (e) {
      const char = String.fromCharCode(e.which);
      if (!/\d/.test(char)) {
        e.preventDefault();
      }
    });

    // Add paste event to handle pasted content
    cnicInput.addEventListener("paste", function (e) {
      e.preventDefault();
      let paste = (e.clipboardData || window.clipboardData).getData("text");
      let digits = paste.replace(/\D/g, "").substring(0, 13);

      let formatted = "";
      if (digits.length > 5) {
        formatted += digits.slice(0, 5) + "-";
        if (digits.length > 12) {
          formatted += digits.slice(5, 12) + "-" + digits.slice(12, 13);
        } else {
          formatted += digits.slice(5, 12);
        }
      } else {
        formatted = digits;
      }

      cnicInput.value = formatted;
    });
  } else {
    console.log("CNIC input not found");
  }
});
