<!DOCTYPE html>
<html>
<head>
    <title>Simple Profile Test</title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/izitoast/dist/js/iziToast.min.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/izitoast/dist/css/iziToast.min.css">
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; }
        input, textarea { width: 100%; padding: 8px; border: 1px solid #ddd; }
        button { padding: 10px 20px; margin: 5px; cursor: pointer; }
        .btn-primary { background: #007bff; color: white; border: none; }
        .btn-secondary { background: #6c757d; color: white; border: none; }
    </style>
</head>
<body>
    <h2>Simple Profile Form Test</h2>
    
    <form id="testProfileForm">
        <div class="form-group">
            <label>Full Name:</label>
            <input type="text" name="full_name" value="Test User" required>
        </div>
        <div class="form-group">
            <label>Email:</label>
            <input type="email" name="email" value="test@example.com" readonly>
        </div>
        <div class="form-group">
            <label>Phone:</label>
            <input type="text" name="phone" value="0300-1234567" required>
        </div>
        <div class="form-group">
            <label>Location:</label>
            <input type="text" name="location" value="Lahore, Pakistan" required>
        </div>
        <div class="form-group">
            <label>CNIC:</label>
            <input type="text" name="cnic" value="12345-1234567-1" required>
        </div>
        <div class="form-group">
            <label>Bio:</label>
            <textarea name="bio">Test bio</textarea>
        </div>
        <button type="submit" class="btn-primary">Save Changes</button>
        <button type="button" class="btn-secondary" id="debugBtn">Debug Submit</button>
    </form>

    <div id="result" style="margin-top: 20px; padding: 10px; border: 1px solid #ddd;"></div>

    <script>
        $(document).ready(function() {
            console.log('Document ready - Simple test');
            alert('Simple test page loaded!');
            
            // Test regular submit
            $('#testProfileForm').submit(function(e) {
                e.preventDefault();
                console.log('Form submitted');
                alert('Form submitted!');
                
                var formData = new FormData(this);
                
                // Log form data
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
                        $('#result').html('Sending request...');
                    },
                    success: function(response) {
                        console.log('Response:', response);
                        $('#result').html('<pre>' + JSON.stringify(response, null, 2) + '</pre>');
                        
                        if (response.success) {
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
                        console.error('Error:', xhr.responseText);
                        $('#result').html('<pre>Error: ' + xhr.responseText + '</pre>');
                        iziToast.error({
                            title: 'Error',
                            message: 'An error occurred: ' + error,
                            position: 'topRight'
                        });
                    }
                });
            });
            
            // Test debug button
            $('#debugBtn').click(function() {
                console.log('Debug button clicked');
                alert('Debug button clicked!');
                
                var formData = new FormData($('#testProfileForm')[0]);
                
                $.ajax({
                    url: 'backend/update-user-details.php',
                    type: 'POST',
                    data: formData,
                    contentType: false,
                    processData: false,
                    dataType: 'json',
                    success: function(response) {
                        console.log('Debug Response:', response);
                        $('#result').html('<pre>Debug: ' + JSON.stringify(response, null, 2) + '</pre>');
                    },
                    error: function(xhr, status, error) {
                        console.error('Debug Error:', xhr.responseText);
                        $('#result').html('<pre>Debug Error: ' + xhr.responseText + '</pre>');
                    }
                });
            });
        });
    </script>
</body>
</html> 