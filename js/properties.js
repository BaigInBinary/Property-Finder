// Properties Page JavaScript

document.addEventListener("DOMContentLoaded", function () {
  // Handle "Select All" checkbox
  const selectAllCheckbox = document.querySelector(
    'thead input[type="checkbox"]'
  );
  const rowCheckboxes = document.querySelectorAll(
    'tbody input[type="checkbox"]'
  );

  if (selectAllCheckbox) {
    selectAllCheckbox.addEventListener("change", function () {
      rowCheckboxes.forEach((checkbox) => {
        checkbox.checked = selectAllCheckbox.checked;
      });
    });
  }

  // Handle individual checkbox changes
  rowCheckboxes.forEach((checkbox) => {
    checkbox.addEventListener("change", function () {
      const allChecked = Array.from(rowCheckboxes).every((cb) => cb.checked);
      if (selectAllCheckbox) {
        selectAllCheckbox.checked = allChecked;
      }
    });
  });

  // Handle search input
  const searchInput = document.querySelector(
    'input[placeholder="Search properties..."]'
  );
  if (searchInput) {
    searchInput.addEventListener("input", function (e) {
      const searchTerm = e.target.value.toLowerCase();
      const tableRows = document.querySelectorAll("tbody tr");

      tableRows.forEach((row) => {
        const text = row.textContent.toLowerCase();
        row.style.display = text.includes(searchTerm) ? "" : "none";
      });
    });
  }

  // Handle property type filter
  const typeFilter = document.querySelector("select:first-of-type");
  if (typeFilter) {
    typeFilter.addEventListener("change", function () {
      filterProperties();
    });
  }

  // Handle status filter
  const statusFilter = document.querySelector("select:nth-of-type(2)");
  if (statusFilter) {
    statusFilter.addEventListener("change", function () {
      filterProperties();
    });
  }

  // Filter properties based on selected filters
  function filterProperties() {
    const selectedType = typeFilter.value;
    const selectedStatus = statusFilter.value;
    const tableRows = document.querySelectorAll("tbody tr");

    tableRows.forEach((row) => {
      const type = row.querySelector("td:nth-child(3)").textContent;
      const status = row.querySelector(".badge").textContent;

      const typeMatch = selectedType === "All Types" || type === selectedType;
      const statusMatch =
        selectedStatus === "All Status" || status === selectedStatus;

      row.style.display = typeMatch && statusMatch ? "" : "none";
    });
  }

  // Handle delete button clicks
  const deleteButtons = document.querySelectorAll(".btn-outline-danger");
  deleteButtons.forEach((button) => {
    button.addEventListener("click", function (e) {
      if (confirm("Are you sure you want to delete this property?")) {
        // Here you would typically make an API call to delete the property
        const row = e.target.closest("tr");
        row.remove();
      }
    });
  });

  // Handle edit button clicks
  const editButtons = document.querySelectorAll(".btn-primary");
  editButtons.forEach((button) => {
    button.addEventListener("click", function (e) {
      const row = e.target.closest("tr");
      const propertyName = row.querySelector(".fw-bold").textContent;
      // Here you would typically populate and show an edit modal
      alert(`Edit property: ${propertyName}`);
    });
  });

  // Handle form submission in the Add Property modal
  const addPropertyForm = document.querySelector("#addPropertyModal form");
  if (addPropertyForm) {
    addPropertyForm.addEventListener("submit", function (e) {
      e.preventDefault();
      // Here you would typically make an API call to save the new property
      alert("Property added successfully!");
      const modal = bootstrap.Modal.getInstance(
        document.querySelector("#addPropertyModal")
      );
      modal.hide();
    });
  }
});
