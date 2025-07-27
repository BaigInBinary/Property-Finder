// Form validation and submission handling
document.addEventListener("DOMContentLoaded", function () {
  const signupForm = document.getElementById("signupForm");
  const loginForm = document.getElementById("loginForm");
  const togglePassword = document.getElementById("togglePassword");
  const toggleConfirmPassword = document.getElementById(
    "toggleConfirmPassword"
  );
  const passwordInput = document.getElementById("password");
  const confirmPasswordInput = document.getElementById("confirmPassword");
  const cnicInput = document.getElementById("cnic");

  // Toggle password visibility
  if (togglePassword) {
    togglePassword.addEventListener("click", function () {
      const type =
        passwordInput.getAttribute("type") === "password" ? "text" : "password";
      passwordInput.setAttribute("type", type);
      this.querySelector("i").classList.toggle("fa-eye");
      this.querySelector("i").classList.toggle("fa-eye-slash");
    });
  }

  // Toggle confirm password visibility
  if (toggleConfirmPassword) {
    toggleConfirmPassword.addEventListener("click", function () {
      const type =
        confirmPasswordInput.getAttribute("type") === "password"
          ? "text"
          : "password";
      confirmPasswordInput.setAttribute("type", type);
      this.querySelector("i").classList.toggle("fa-eye");
      this.querySelector("i").classList.toggle("fa-eye-slash");
    });
  }

  // CNIC format validation
  if (cnicInput) {
    cnicInput.addEventListener("input", function (e) {
      let value = e.target.value.replace(/\D/g, "");
      if (value.length > 0) {
        if (value.length <= 5) {
          value = value;
        } else if (value.length <= 12) {
          value = value.slice(0, 5) + "-" + value.slice(5);
        } else {
          value =
            value.slice(0, 5) +
            "-" +
            value.slice(5, 12) +
            "-" +
            value.slice(12, 13);
        }
      }
      e.target.value = value;
    });
  }

  // Handle signup form submission
  if (signupForm) {
    signupForm.addEventListener("submit", async function (e) {
      e.preventDefault();

      // Reset validation
      this.classList.remove("was-validated");

      // Get form data
      const name = document.getElementById("name").value;
      const email = document.getElementById("email").value;
      const cnic = document.getElementById("cnic").value;
      const role = document.getElementById("role").value;
      const password = document.getElementById("password").value;
      const confirmPassword = document.getElementById("confirmPassword").value;

      // Validate passwords match
      if (password !== confirmPassword) {
        confirmPasswordInput.setCustomValidity("Passwords do not match");
        this.classList.add("was-validated");
        return;
      } else {
        confirmPasswordInput.setCustomValidity("");
      }

      // Validate CNIC format
      const cnicPattern = /^[0-9]{5}-[0-9]{7}-[0-9]{1}$/;
      if (!cnicPattern.test(cnic)) {
        cnicInput.setCustomValidity("Invalid CNIC format");
        this.classList.add("was-validated");
        return;
      } else {
        cnicInput.setCustomValidity("");
      }

      // Validate password strength
      const passwordPattern = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{8,}$/;
      if (!passwordPattern.test(password)) {
        passwordInput.setCustomValidity(
          "Password must be at least 8 characters long and include uppercase, lowercase, and numbers"
        );
        this.classList.add("was-validated");
        return;
      } else {
        passwordInput.setCustomValidity("");
      }

      try {
        const response = await fetch("/backend/signup.php", {
          method: "POST",
          headers: {
            "Content-Type": "application/json",
          },
          body: JSON.stringify({
            name: name,
            email: email,
            cnic: cnic,
            role: role,
            password: password,
          }),
        });

        const data = await response.json();

        if (data.status === "success") {
          // Store user data in localStorage
          localStorage.setItem("user", JSON.stringify(data.user));

          // Redirect based on role
          if (data.user.role === "admin") {
            window.location.href = "/admin-panel.html";
          } else {
            window.location.href = "/dashboard.html";
          }
        } else {
          showError(data.message);
        }
      } catch (error) {
        showError("An error occurred. Please try again.");
        console.error("Signup error:", error);
      }
    });
  }

  // Handle login form submission
  if (loginForm) {
    loginForm.addEventListener("submit", async function (e) {
      e.preventDefault();

      // Get form data
      const email = document.getElementById("email").value;
      const password = document.getElementById("password").value;
      const rememberMe = document.getElementById("rememberMe").checked;

      try {
        const response = await fetch("/backend/login.php", {
          method: "POST",
          headers: {
            "Content-Type": "application/json",
          },
          body: JSON.stringify({
            email: email,
            password: password,
            remember_me: rememberMe,
          }),
        });

        const data = await response.json();

        if (data.status === "success") {
          // Store user data in localStorage if remember me is checked
          if (rememberMe) {
            localStorage.setItem("user", JSON.stringify(data.user));
          }

          // Redirect based on user role
          if (data.user.role === "admin") {
            window.location.href = "/admin-panel.html";
          } else {
            window.location.href = "/dashboard.html";
          }
        } else {
          showError(data.message);
        }
      } catch (error) {
        showError("An error occurred. Please try again.");
        console.error("Login error:", error);
      }
    });
  }
});

// Show error message
function showError(message) {
  const errorDiv = document.createElement("div");
  errorDiv.className = "alert alert-danger alert-dismissible fade show";
  errorDiv.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    `;

  const form =
    document.getElementById("signupForm") ||
    document.getElementById("loginForm");
  form.insertBefore(errorDiv, form.firstChild);

  // Auto-dismiss after 5 seconds
  setTimeout(() => {
    errorDiv.remove();
  }, 5000);
}

// Check if user is logged in
function checkAuth() {
  const user = JSON.parse(localStorage.getItem("user"));
  if (user) {
    // Redirect if trying to access auth pages while logged in
    if (
      window.location.pathname.includes("login.html") ||
      window.location.pathname.includes("signup.html")
    ) {
      window.location.href =
        user.role === "admin" ? "/admin-panel.html" : "/dashboard.html";
    }
  } else {
    // Redirect to login if trying to access protected pages
    if (
      window.location.pathname.includes("dashboard.html") ||
      window.location.pathname.includes("admin-panel.html")
    ) {
      window.location.href = "/login.html";
    }
  }
}

// Run auth check on page load
checkAuth();
