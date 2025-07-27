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

  if (!propertyImagesInput || !imagePreviewContainer || !countSpan) return;

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
    let files = Array.from(propertyImagesInput.files);

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

    // Clear previous previews
    imagePreviewContainer.innerHTML = "";

    // Preview images
    files.forEach((file) => {
      if (!file.type.startsWith("image/")) return;
      const reader = new FileReader();
      reader.onload = function (e) {
        const img = document.createElement("img");
        img.src = e.target.result;
        img.className = "img-thumbnail";
        imagePreviewContainer.appendChild(img);
      };
      reader.readAsDataURL(file);
    });
  });
});
