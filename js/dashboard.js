document.addEventListener("DOMContentLoaded", function () {
  // Sidebar toggle for mobile
  const sidebarCollapse = document.getElementById("sidebarCollapse");
  const mobileSidebarCollapse = document.getElementById(
    "mobileSidebarCollapse"
  );
  const sidebar = document.querySelector(".sidebar");

  if (sidebarCollapse) {
    sidebarCollapse.addEventListener("click", function () {
      sidebar.classList.toggle("active");
    });
  }

  if (mobileSidebarCollapse) {
    mobileSidebarCollapse.addEventListener("click", function () {
      sidebar.classList.toggle("active");
    });
  }

  // Close sidebar when clicking outside on mobile
  document.addEventListener("click", function (event) {
    const isClickInsideSidebar = sidebar.contains(event.target);
    const isClickOnToggle =
      sidebarCollapse?.contains(event.target) ||
      mobileSidebarCollapse?.contains(event.target);

    if (
      !isClickInsideSidebar &&
      !isClickOnToggle &&
      sidebar.classList.contains("active")
    ) {
      sidebar.classList.remove("active");
    }
  });

  // Section navigation
  const navLinks = document.querySelectorAll(".nav-link[data-section]");
  const sections = document.querySelectorAll(".dashboard-section");

  navLinks.forEach((link) => {
    link.addEventListener("click", function (e) {
      e.preventDefault();

      // Remove active class from all links and sections
      navLinks.forEach((l) => l.classList.remove("active"));
      sections.forEach((s) => s.classList.remove("active"));

      // Add active class to clicked link and corresponding section
      this.classList.add("active");
      const sectionId = this.getAttribute("data-section");
      document.getElementById(sectionId).classList.add("active");

      // Close sidebar on mobile after navigation
      if (window.innerWidth < 992) {
        sidebar.classList.remove("active");
      }
    });
  });

  // Profile form handling
  const profileForm = document.getElementById("profileForm");
  if (profileForm) {
    profileForm.addEventListener("submit", function (e) {
      e.preventDefault();
      // Removed success alert
    });
  }

  // Property actions
  const propertyActions = {
    edit: function (propertyId) {
      console.log("Edit property:", propertyId);
      // Implement edit functionality
    },
    delete: function (propertyId) {
      if (confirm("Are you sure you want to delete this property?")) {
        console.log("Delete property:", propertyId);
        // Implement delete functionality
      }
    },
    toggleBookmark: function (propertyId) {
      console.log("Toggle bookmark:", propertyId);
      // Implement bookmark functionality
    },
  };

  // Initialize tooltips
  const tooltips = document.querySelectorAll("[title]");
  tooltips.forEach((tooltip) => {
    new bootstrap.Tooltip(tooltip);
  });

  // Initialize dropdowns
  const dropdowns = document.querySelectorAll(".dropdown-toggle");
  dropdowns.forEach((dropdown) => {
    new bootstrap.Dropdown(dropdown);
  });

  // File upload preview - Removed conflicting handler (now handled in dashboard.php)
  // const avatarUpload = document.querySelector('.avatar-upload');
  // const profileAvatar = document.querySelector('.profile-avatar');

  // if (avatarUpload && profileAvatar) {
  //     avatarUpload.addEventListener('click', function() {
  //         const input = document.createElement('input');
  //         input.type = 'file';
  //         input.accept = 'image/*';
  //
  //         input.onchange = function() {
  //             const file = this.files[0];
  //             if (file) {
  //                 const reader = new FileReader();
  //                     reader.onload = function(e) {
  //                         profileAvatar.src = e.target.result;
  //                         // Removed profile picture updated alert
  //                     };
  //                     reader.readAsDataURL(file);
  //                 }
  //             };
  //
  //             input.click();
  //         });
  //     }
  // }

  // Notification handling
  const notificationActions = document.querySelectorAll(
    ".notification-action button"
  );
  notificationActions.forEach((button) => {
    button.addEventListener("click", function (e) {
      e.preventDefault();
      const notification = this.closest(".notification-item");
      notification.style.opacity = "0";
      setTimeout(() => {
        notification.remove();
        updateNotificationCount();
      }, 300);
    });
  });

  function updateNotificationCount() {
    const count = document.querySelectorAll(".notification-item").length;
    const badges = document.querySelectorAll(".badge");
    badges.forEach((badge) => {
      if (count === 0) {
        badge.style.display = "none";
      } else {
        badge.style.display = "inline-block";
        badge.textContent = count;
      }
    });
  }

  // Copy referral link
  const shareReferralBtn = document.querySelector(".rewards-points + button");
  if (shareReferralBtn) {
    shareReferralBtn.addEventListener("click", function () {
      const referralLink = "https://propfind.pk/ref/123456"; // Replace with actual referral link
      navigator.clipboard.writeText(referralLink);
      // Removed referral link copied alert
    });
  }
});
