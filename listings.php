<?php include'inc/header.php'; ?>
    <!-- Main Content -->
    <main class="listings-container mt-5 pt-4">
        <div class="container">
            <!-- Page Header -->
            <div class="row mb-4">
                <div class="col-md-8">
                    <h1 class="h2">Property Listings</h1>
                    <p class="text-muted">Find your perfect property from our extensive collection</p>
                </div>
                <div class="col-md-4 text-end">
                    <div class="btn-group view-toggle" role="group" aria-label="View toggle">
                        <button type="button" class="btn active" id="gridView">
                            <i class="fas fa-th-large"></i> Grid
                        </button>
                        <button type="button" class="btn" id="listView">
                            <i class="fas fa-list"></i> List
                        </button>
                    </div>
                </div>
            </div>

            <div class="row">
    <!-- Filters Sidebar -->
    <div class="col-lg-3 mb-4">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title mb-4">Filters</h5>
                <form id="filterForm">
                    <!-- Price Range -->
                    <div class="mb-4">
                        <label class="form-label">Price Range (PKR)</label>
                        <div class="d-flex gap-2">
                            <input type="number" class="form-control" placeholder="Min" id="minPrice" name="minPrice" min="0">
                            <input type="number" class="form-control" placeholder="Max" id="maxPrice" name="maxPrice" min="0">
                        </div>
                    </div>

                    <!-- City -->
                    <div class="mb-4">
                        <label class="form-label">City</label>
                        <select class="form-select" id="city" name="city">
                            <option value="">All Cities</option>
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
                    </div>

                    <!-- Size Range -->
                    <div class="mb-4">
                        <label class="form-label">Size (Marla)</label>
                        <div class="d-flex gap-2">
                            <input type="number" class="form-control" placeholder="Min" id="minSize" name="minSize">
                            <input type="number" class="form-control" placeholder="Max" id="maxSize" name="maxSize">
                        </div>
                    </div>

                    <!-- Property Type -->
                    <div class="mb-4">
                        <label class="form-label">Property Type</label>
                        <select class="form-select" id="propertyType" name="propertyType">
                            <option value="">All Types</option>
                            <option value="House">House</option>
                            <option value="Flat">Flat</option>
                            <option value="Plot">Plot</option>
                            <option value="Commercial">Commercial</option>
                        </select>
                    </div>

                    <!-- Bedrooms filter removed -->

                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary">Apply Filters</button>
                        <button type="button" class="btn btn-outline-secondary" id="clearFilters">Clear All Filters</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Listings Grid -->
    <div class="col-lg-9">
        <div id="loadingMessage" class="text-center my-3" style="display: none;">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
        </div>

        <div id="noResults" class="alert alert-warning text-center" style="display: none;">
            No properties found matching your criteria.
        </div>

        <div class="row g-4" id="propertiesGrid">
            <!-- Property cards will be dynamically inserted here by JS -->
        </div>

        <!-- Pagination (if needed later) -->
        <nav class="mt-4" aria-label="Page navigation">
    <ul class="pagination justify-content-center" id="pagination">
        <!-- Pagination items will be inserted here dynamically -->
    </ul>
</nav>

    </div>
</div>

        </div>
    </main>

  


    <!-- Bootstrap 5 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Custom JS -->
    <script src="js/listings.js"></script>
    <?php include'inc/footer.php'?>