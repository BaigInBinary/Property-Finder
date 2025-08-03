<?php include 'inc/header.php'; ?>

<style>
.image-preview-container {
    display: flex;
    flex-direction: row;
    gap: 8px;
    margin-top: 10px;
    min-height: 130px;
}
.image-preview-container .img-thumbnail {
    width: 120px;
    height: 120px;
    object-fit: cover;
    background: #f8f9fa;
    border: 1px solid #dee2e6;
    display: inline-block;
}
</style>

<!-- Main Content -->
<main class="container mt-5 pt-4">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h1 class="card-title text-center fw-bold fs-4 mb-4">Upload Property</h1>

                    <!-- Upload Form -->
                    <form id="propertyUploadForm" class="ajax-img" data-action="upload_property" enctype="multipart/form-data">
                        <!-- Property Title -->
                        <div class="mb-3">
                            <label for="propertyTitle" class="form-label required">Property Title</label>
                            <input type="text" class="form-control" id="propertyTitle" name="propertyTitle"
                                required minlength="10" maxlength="100">
                            <div class="invalid-feedback">
                                Please enter a title (10-100 characters)
                            </div>
                        </div>

                        <!-- Price -->
                        <div class="mb-3">
                            <label for="price" class="form-label required">Price (PKR)</label>
                            <div class="input-group">
                                <span class="input-group-text">PKR</span>
                                <input type="number" class="form-control" id="price" name="price"
                                    required min="10000" max="500000000" pattern="[0-9]{5,}">
                            </div>
                            <div class="invalid-feedback">
                                Price must be at least 5 digits (minimum 10,000 PKR) and maximum 500,000,000 PKR
                            </div>
                        </div>

                        <!-- Property Type -->
                        <div class="mb-3">
                            <label for="propertyType" class="form-label required">Property Type</label>
                            <select class="form-select" id="propertyType" name="propertyType" required>
                                <option value="">Select property type</option>
                                <option value="House">House</option>
                                <option value="Plot">Plot</option>
                                <option value="Commercial">Commercial</option>
                                <option value="Apartment">Apartment</option>
                            </select>
                            <div class="invalid-feedback">
                                Please select a property type
                            </div>
                        </div>

                        <!-- Area -->
                        <div class="mb-3">
                            <label for="area" class="form-label required">Area (Marla)</label>
                            <input type="number" class="form-control" id="area" name="area"
                                required min="1" step="0.5">
                            <div class="invalid-feedback">
                                Please enter a valid area (minimum 1 Marla)
                            </div>
                        </div>

                        <!-- Unit Type -->
                        <div class="mb-3">
                            <label for="unit" class="form-label required">Unit</label>
                            <select class="form-select" id="unit" name="unit" required>
                                <option value="">Select Unit</option>
                                <option value="marla">Marla</option>
                                <!-- <option value="square feet">Square Feet</option> -->
                            </select>
                            <div class="invalid-feedback">
                                Please select a unit
                            </div>
                        </div>

                        <!-- City -->
                        <div class="mb-3">
                            <label for="city" class="form-label required">City</label>
                            <select class="form-select" id="city" name="city" required>
                                <option value="">Select City</option>
                                <option value="Karachi">Karachi</option>
                                <option value="Lahore">Lahore</option>
                                <option value="Islamabad">Islamabad</option>
                                <option value="Rawalpindi">Rawalpindi</option>
                                <option value="Faisalabad">Faisalabad</option>
                                <option value="Multan">Multan</option>
                                <option value="Peshawar">Peshawar</option>
                                <option value="Quetta">Quetta</option>
                                <option value="Sialkot">Sialkot</option>
                                <option value="Gujranwala">Gujranwala</option>
                                <option value="Hyderabad">Hyderabad</option>
                                <option value="Bahawalpur">Bahawalpur</option>
                                <option value="Sargodha">Sargodha</option>
                                <option value="Sukkur">Sukkur</option>
                                <option value="Abbottabad">Abbottabad</option>
                                <option value="Mardan">Mardan</option>
                                <option value="Rahim Yar Khan">Rahim Yar Khan</option>
                                <option value="Okara">Okara</option>
                                <option value="Dera Ghazi Khan">Dera Ghazi Khan</option>
                                <option value="Chiniot">Chiniot</option>
                                <option value="Jhelum">Jhelum</option>
                                <option value="Gujrat">Gujrat</option>
                                <option value="Larkana">Larkana</option>
                                <option value="Sheikhupura">Sheikhupura</option>
                                <option value="Mirpur Khas">Mirpur Khas</option>
                                <option value="Muzaffargarh">Muzaffargarh</option>
                                <option value="Kohat">Kohat</option>
                                <option value="Swat">Swat</option>
                                <option value="Gwadar">Gwadar</option>
                            </select>
                            <div class="invalid-feedback">
                                Please select a city
                            </div>
                        </div>


                        <!-- Description -->
                        <div class="mb-3">
                            <label for="description" class="form-label required">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="5"></textarea>
                            <div class="invalid-feedback">
                                Please provide a description.
                            </div>
                        </div>


                        <!-- Property Images -->
                        <div class="mb-3">
                            <label for="propertyImages" class="form-label required">Property Images</label>
                            <input type="file" class="form-control" id="propertyImages" name="propertyImages[]" accept="image/*" multiple required>
                            <div class="invalid-feedback">
                                Please upload at least one image
                            </div>
                            <small class="text-muted">Upload multiple images (max 5)</small>
                            <span id="selectedImageCount" class="text-info ms-2"></span>
                            <div id="imagePreviewContainer" class="image-preview-container"></div>
                        </div>

                        <!-- CNIC Number -->
                        <div class="mb-3">
                            <label for="cnicNumber" class="form-label required">CNIC Number (format: XXXXX-XXXXXXX-X)</label>
                            <input type="text" class="form-control" id="cnicNumber" name="cnicNumber"
                                required pattern="[0-9]{5}-[0-9]{7}-[0-9]">
                            <div class="invalid-feedback">
                                Please enter a valid CNIC number (format: 12345-1234567-1)
                            </div>
                        </div>

                        <!-- CNIC Image -->
                        <div class="mb-3">
                            <label for="cnicImage" class="form-label required">CNIC Image</label>
                            <input type="file" class="form-control" id="cnicImage" name="cnicImage"
                                accept="image/*" required>
                            <div class="invalid-feedback">
                                Please upload your CNIC image (images only)
                            </div>
                        </div>

                        <!-- Ownership Documents -->
                        <div class="mb-3">
                            <label for="ownershipDocs" class="form-label required">Ownership Documents</label>
                            <input type="file" class="form-control" id="ownershipDocs" name="ownershipDocs"
                                accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt" required>
                            <div class="invalid-feedback">
                                Please upload ownership documents
                            </div>
                        </div>

                        <!-- Iframe -->
                        <div class="mb-3">
                            <label for="mapLink" class="form-label required">Location on Map</label>
                            <input type="text" class="form-control" id="mapLink" name="link" placeholder="Enter Google Maps embed iframe link" required>
                            <div class="invalid-feedback" id="mapLinkError">
                                Please provide a valid Google Maps embed iframe link.
                            </div>
                            <div class="form-text">
                                <i class="fas fa-info-circle"></i> 
                                Paste the embed iframe code from Google Maps. Example: &lt;iframe src="https://www.google.com/maps/embed?..."&gt;
                            </div>
                        </div>



                        <!-- Form Buttons -->
                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <!-- <button type="button" class="btn btn-primary" id="previewBtn">
                                <i class="fas fa-eye"></i> Preview
                            </button> -->
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-upload"></i> Submit now
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</main>

<!-- Preview Modal -->
<div class="modal fade" id="previewModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Preview Property Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="previewContent">
                <!-- Preview content will be inserted here -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="confirmSubmit">Confirm & Submit</button>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.ckeditor.com/ckeditor5/41.0.0/classic/ckeditor.js"></script>
<script>
    ClassicEditor
        .create(document.querySelector('#description'), {
            toolbar: [
                'heading',
                'bold',
                'italic',
                'link',
                'bulletedList',
                'numberedList',
                'blockQuote',
                'undo',
                'redo'
            ]
        })
        .then(editor => {
            // Make the editor instance globally accessible
            window.descriptionEditor = editor;
        })
        .catch(error => {
            console.error(error);
        });
</script>

<!-- Bootstrap 5 JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<!-- Custom JS -->
<script src="ajax.js"></script>
<script src="js/upload-property.js"></script>

<!-- CNIC Formatting Script -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Price validation - ensure minimum 5 digits
    const priceInput = document.getElementById('price');
    if (priceInput) {
        priceInput.addEventListener('input', function(e) {
            const value = this.value;
            if (value && value.length < 5) {
                this.setCustomValidity('Price must be at least 5 digits (minimum 10,000 PKR)');
            } else if (value && parseInt(value) < 10000) {
                this.setCustomValidity('Price must be at least 10,000 PKR');
            } else {
                this.setCustomValidity('');
            }
        });
        
        // Also validate on blur
        priceInput.addEventListener('blur', function(e) {
            const value = this.value;
            if (value && value.length < 5) {
                this.setCustomValidity('Price must be at least 5 digits (minimum 10,000 PKR)');
            } else if (value && parseInt(value) < 10000) {
                this.setCustomValidity('Price must be at least 10,000 PKR');
            } else {
                this.setCustomValidity('');
            }
        });
    }

    // Map link validation
    const mapLinkInput = document.getElementById('mapLink');
    if (mapLinkInput) {
        mapLinkInput.addEventListener('input', function(e) {
            validateMapLink(this.value);
        });
        
        mapLinkInput.addEventListener('blur', function(e) {
            validateMapLink(this.value);
        });
        
        function validateMapLink(value) {
            const errorDiv = document.getElementById('mapLinkError');
            
            if (!value.trim()) {
                mapLinkInput.setCustomValidity('Map link is required');
                errorDiv.textContent = 'Map link is required';
                return false;
            }
            
            // Check if it's a valid iframe embed link
            const iframePattern = /<iframe[^>]*src=["'](https?:\/\/www\.google\.com\/maps\/embed[^"']*)["'][^>]*>/i;
            const googleMapsEmbedPattern = /https?:\/\/www\.google\.com\/maps\/embed/i;
            
            if (!iframePattern.test(value) && !googleMapsEmbedPattern.test(value)) {
                mapLinkInput.setCustomValidity('Please provide a valid Google Maps embed iframe link');
                errorDiv.textContent = 'Please provide a valid Google Maps embed iframe link. It should contain "google.com/maps/embed"';
                return false;
            }
            
            // Additional validation for iframe structure
            if (value.includes('<iframe') && !value.includes('src=')) {
                mapLinkInput.setCustomValidity('Invalid iframe structure. Please include the src attribute');
                errorDiv.textContent = 'Invalid iframe structure. Please include the src attribute';
                return false;
            }
            
            mapLinkInput.setCustomValidity('');
            errorDiv.textContent = 'Please provide a valid Google Maps embed iframe link.';
            return true;
        }
    }

    const cnicInput = document.getElementById('cnicNumber');
    if (cnicInput) {
        console.log("CNIC input found in inline script");
        
        cnicInput.addEventListener('input', function(e) {
            console.log("CNIC input event triggered from inline script");
            let value = cnicInput.value.replace(/\D/g, ''); // Remove all non-digits
            if (value.length > 13) value = value.slice(0, 13); // Max 13 digits

            let formatted = '';
            if (value.length > 5) {
                formatted += value.slice(0, 5) + '-';
                if (value.length > 12) {
                    formatted += value.slice(5, 12) + '-' + value.slice(12, 13);
                } else if (value.length > 5) {
                    formatted += value.slice(5, 12);
                }
            } else {
                formatted = value;
            }
            if (value.length > 12) {
                formatted = value.slice(0, 5) + '-' + value.slice(5, 12) + '-' + value.slice(12, 13);
            }
            cnicInput.value = formatted;
        });

        // Prevent non-numeric input
        cnicInput.addEventListener('keypress', function(e) {
            const char = String.fromCharCode(e.which);
            if (!/\d/.test(char)) {
                e.preventDefault();
            }
        });

        // Handle paste events
        cnicInput.addEventListener('paste', function(e) {
            e.preventDefault();
            let paste = (e.clipboardData || window.clipboardData).getData('text');
            let digits = paste.replace(/\D/g, '').substring(0, 13);
            
            let formatted = '';
            if (digits.length > 5) {
                formatted += digits.slice(0, 5) + '-';
                if (digits.length > 12) {
                    formatted += digits.slice(5, 12) + '-' + digits.slice(12, 13);
                } else {
                    formatted += digits.slice(5, 12);
                }
            } else {
                formatted = digits;
            }
            
            cnicInput.value = formatted;
        });
    } else {
        console.log("CNIC input not found in inline script");
    }

    // Image preview functionality
    const propertyImagesInput = document.getElementById('propertyImages');
    const imagePreviewContainer = document.getElementById('imagePreviewContainer');
    const countSpan = document.getElementById('selectedImageCount');
    
    if (propertyImagesInput && imagePreviewContainer && countSpan) {
        console.log("Image preview elements found in inline script");
        
        propertyImagesInput.addEventListener('change', function(e) {
            console.log("Property images change event triggered from inline script");
            let files = Array.from(propertyImagesInput.files);
            console.log("Selected files:", files.length);

            // Limit to 5 images
            if (files.length > 5) {
                const dt = new DataTransfer();
                files.slice(0, 5).forEach((file) => dt.items.add(file));
                propertyImagesInput.files = dt.files;
                alert("You can select a maximum of 5 images. Only the first 5 will be used.");
                files = Array.from(propertyImagesInput.files);
            }

            // Show count
            countSpan.textContent = files.length > 0 ? `(${files.length} selected)` : "";
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
                reader.onload = function(e) {
                    console.log("File loaded, creating preview for:", file.name);
                    const img = document.createElement("img");
                    img.src = e.target.result;
                    img.className = "img-thumbnail";
                    img.alt = file.name;
                    imagePreviewContainer.appendChild(img);
                    console.log("Preview image added to container");
                };
                reader.onerror = function() {
                    console.error("Error reading file:", file.name);
                };
                reader.readAsDataURL(file);
            });
        });
    } else {
        console.log("Image preview elements not found in inline script:", {
            propertyImagesInput: !!propertyImagesInput,
            imagePreviewContainer: !!imagePreviewContainer,
            countSpan: !!countSpan
        });
    }
});
</script>

<?php include'inc/footer.php'?>