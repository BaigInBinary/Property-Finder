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
                    <h2 class="card-title text-center mb-4">Upload Property</h2>

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
                                    required min="500" max="500000000">
                            </div>
                            <div class="invalid-feedback">
                                Price must be between 500 and 500,000,000 PKR
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
                                <option value="square feet">Square Feet</option>
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
                                accept="image/*,.pdf" required>
                            <div class="invalid-feedback">
                                Please upload your CNIC image/PDF
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
                            <input type="text" class="form-control" id="mapLink" name="link" placeholder="Enter map iframe link" required>
                            <div class="invalid-feedback">
                                Please provide a valid map link (URL).
                            </div>
                        </div>



                        <!-- Form Buttons -->
                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <button type="button" class="btn btn-outline-primary" id="previewBtn">
                                <i class="fas fa-eye"></i> Preview
                            </button>
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
        .catch(error => {
            console.error(error);
        });
</script>

<!-- Bootstrap 5 JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<!-- Custom JS -->
<script src="ajax.js"></script>
<script src="js/upload-property.js"></script>
<?php include'inc/footer.php'?>