<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile Form Test</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/izitoast/1.4.0/css/iziToast.min.css">
    <style>
        .profile-avatar {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            object-fit: cover;
        }
        .profile-avatar-wrapper {
            position: relative;
            display: inline-block;
        }
        .avatar-upload {
            position: absolute;
            bottom: 0;
            right: 0;
            background: #007bff;
            color: white;
            border-radius: 50%;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
        }
    </style>
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header">
                        <h3>Profile Form Test</h3>
                    </div>
                    <div class="card-body">
                        <form id="profileForm">
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">Full Name <span class="text-danger">*</span></label>
                                    <input type="text" name="full_name" class="form-control" value="Test User" 
                                           pattern="^[A-Za-z\s]+$" title="Name should only contain letters and spaces." required>
                                    <div class="invalid-feedback">Please enter a valid name with only letters and spaces.</div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Email <span class="text-danger">*</span></label>
                                    <input type="email" name="email" class="form-control" value="test@example.com" readonly>
                                    <div class="form-text">Email cannot be changed for security reasons.</div>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">Phone <span class="text-danger">*</span></label>
                                    <input type="text" name="phone" class="form-control" value="0300-1234567" 
                                           placeholder="03XX-XXXXXXX" pattern="^[0-9]{4}-[0-9]{7}$" title="Please enter phone number in format: 03XX-XXXXXXX" required>
                                    <div class="invalid-feedback">Please enter a valid phone number in format: 03XX-XXXXXXX</div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Location <span class="text-danger">*</span></label>
                                    <input type="text" name="location" class="form-control" value="Lahore, Pakistan" 
                                           placeholder="City, Country" pattern="^[A-Za-z\s,.\-]+$" title="Location should only contain letters, spaces, commas, dots, and hyphens." required>
                                    <div class="invalid-feedback">Please enter a valid location with only letters, spaces, commas, dots, and hyphens.</div>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">CNIC <span class="text-danger">*</span></label>
                                    <input type="text" name="cnic" class="form-control" value="12345-1234567-1" 
                                           placeholder="12345-1234567-1" pattern="^[0-9]{5}-[0-9]{7}-[0-9]{1}$" title="Please enter CNIC in format: XXXXX-XXXXXXX-X" required>
                                    <div class="invalid-feedback">Please enter a valid CNIC in format: XXXXX-XXXXXXX-X</div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Account Type</label>
                                    <input type="text" class="form-control" value="User" readonly>
                                    <div class="form-text">Account type cannot be changed.</div>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Bio</label>
                                <textarea name="bio" class="form-control" rows="4" maxlength="500" placeholder="Write a short bio about yourself (max 500 characters)">This is a test bio for testing the profile form functionality.</textarea>
                                <div class="form-text">Maximum 500 characters allowed.</div>
                            </div>
                            
                            <div class="text-center">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-2"></i>Save Changes
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/izitoast/1.4.0/js/iziToast.min.js"></script>
    <script>
        $(document).ready(function() {
            console.log('Test page loaded');
            
            // Phone field validation
            $('input[name="phone"]').on('input', function(e) {
                let value = this.value.replace(/\D/g, '');
                value = value.substring(0, 11);
                let formatted = value;
                if (value.length > 4) {
                    formatted = value.substring(0, 4) + '-' + value.substring(4, 11);
                }
                this.value = formatted;
            });

            // Location field validation
            $('input[name="location"]').on('input', function(e) {
                let value = this.value.replace(/[^A-Za-z\s,.\-]/g, '');
                if (this.value !== value) {
                    this.value = value;
                }
            });

            // Full Name field validation
            $('input[name="full_name"]').on('input', function(e) {
                let value = this.value.replace(/[^A-Za-z\s]/g, '');
                if (this.value !== value) {
                    this.value = value;
                }
            });

            // CNIC field validation
            $('input[name="cnic"]').on('input', function(e) {
                let value = this.value.replace(/\D/g, '');
                if (value.length > 13) value = value.slice(0, 13);
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
                this.value = formatted;
            });

            // Bio character counter
            $('textarea[name="bio"]').on('input', function() {
                const maxLength = 500;
                const currentLength = this.value.length;
                const remaining = maxLength - currentLength;
                
                let counter = $(this).siblings('.char-counter');
                if (counter.length === 0) {
                    counter = $('<div class="form-text char-counter"></div>');
                    $(this).after(counter);
                }
                
                counter.text(`${currentLength}/${maxLength} characters`);
                
                if (currentLength > maxLength) {
                    this.value = this.value.substring(0, maxLength);
                    counter.text(`${maxLength}/${maxLength} characters`);
                }
                
                if (remaining <= 50) {
                    counter.addClass('text-warning');
                } else {
                    counter.removeClass('text-warning');
                }
                
                if (remaining <= 10) {
                    counter.addClass('text-danger');
                } else {
                    counter.removeClass('text-danger');
                }
            });

            // Form submission
            $('#profileForm').submit(function(e) {
                e.preventDefault();
                console.log('Form submitted');

                const form = this;
                let isValid = true;

                // Validate required fields
                $(form).find('input[required]').each(function() {
                    if (!this.checkValidity()) {
                        isValid = false;
                        $(this).addClass('is-invalid');
                    } else {
                        $(this).removeClass('is-invalid');
                    }
                });

                // Custom validation for phone format
                const phoneInput = $('input[name="phone"]');
                const phonePattern = /^[0-9]{4}-[0-9]{7}$/;
                if (phoneInput.val() && !phonePattern.test(phoneInput.val())) {
                    isValid = false;
                    phoneInput.addClass('is-invalid');
                }

                // Custom validation for CNIC format
                const cnicInput = $('input[name="cnic"]');
                const cnicPattern = /^[0-9]{5}-[0-9]{7}-[0-9]{1}$/;
                if (cnicInput.val() && !cnicPattern.test(cnicInput.val())) {
                    isValid = false;
                    cnicInput.addClass('is-invalid');
                }

                if (!isValid) {
                    iziToast.error({
                        title: 'Validation Error',
                        message: 'Please correct the errors in the form.',
                        position: 'topRight'
                    });
                    return false;
                }

                console.log('Validation passed, creating FormData');
                var formData = new FormData(this);
                
                // Log form data for debugging
                for (let pair of formData.entries()) {
                    console.log(pair[0] + ': ' + pair[1]);
                }

                $.ajax({
                    url: 'backend/update-user-details.php',
                    type: 'POST',
                    data: formData,
                    contentType: false,
                    processData: false,
                    dataType: 'json',
                    beforeSend: function() {
                        console.log('Sending profile update request...');
                    },
                    success: function(response) {
                        console.log('Profile Update Response:', response);

                        if (response.success) {
                            console.log('Profile update successful');
                            iziToast.success({
                                title: 'Success',
                                message: 'Profile updated successfully!',
                                position: 'topRight'
                            });
                        } else {
                            iziToast.error({
                                title: 'Error',
                                message: response.message || 'Failed to update profile.',
                                position: 'topRight'
                            });
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Profile update error:', xhr.responseText);
                        iziToast.error({
                            title: 'Error',
                            message: 'An error occurred while updating the profile: ' + error,
                            position: 'topRight'
                        });
                    }
                });
            });
        });
    </script>
</body>
</html> 