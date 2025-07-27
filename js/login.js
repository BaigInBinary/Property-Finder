document.addEventListener('DOMContentLoaded', function() {
    const loginForm = document.getElementById('loginForm');
    const emailInput = document.getElementById('email');
    const passwordInput = document.getElementById('password');
    const togglePasswordButton = document.getElementById('togglePassword');

    // Password visibility toggle
    togglePasswordButton.addEventListener('click', function() {
        const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
        passwordInput.setAttribute('type', type);
        
        // Toggle eye icon
        const icon = this.querySelector('i');
        icon.classList.toggle('fa-eye');
        icon.classList.toggle('fa-eye-slash');
    });

    // Form validation
    loginForm.addEventListener('submit', function(event) {
        event.preventDefault();
        let isValid = true;

        // Email validation
        if (!emailInput.value) {
            setInvalidState(emailInput, 'Please enter your email address');
            isValid = false;
        } else if (!isValidEmail(emailInput.value)) {
            setInvalidState(emailInput, 'Please enter a valid email address');
            isValid = false;
        } else {
            resetValidationState(emailInput);
        }

        // Password validation
        if (!passwordInput.value) {
            setInvalidState(passwordInput, 'Please enter your password');
            isValid = false;
        } else {
            resetValidationState(passwordInput);
        }

        if (isValid) {
            // Here you would typically send the form data to your server
            console.log('Form is valid, ready to submit');
            console.log('Email:', emailInput.value);
            console.log('Password:', passwordInput.value);
            console.log('Remember Me:', document.getElementById('rememberMe').checked);
        }
    });

    // Real-time validation
    emailInput.addEventListener('input', function() {
        if (this.value) {
            if (!isValidEmail(this.value)) {
                setInvalidState(this, 'Please enter a valid email address');
            } else {
                resetValidationState(this);
            }
        }
    });

    passwordInput.addEventListener('input', function() {
        if (this.value) {
            resetValidationState(this);
        }
    });

    // Helper functions
    function setInvalidState(element, message) {
        element.classList.add('is-invalid');
        const feedback = element.parentElement.nextElementSibling;
        if (feedback && feedback.classList.contains('invalid-feedback')) {
            feedback.textContent = message;
        }
    }

    function resetValidationState(element) {
        element.classList.remove('is-invalid');
    }

    function isValidEmail(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    }
}); 