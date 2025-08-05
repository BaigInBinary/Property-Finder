<!DOCTYPE html>
<html>
<head>
    <title>Profile AJAX Test</title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/izitoast/1.4.0/js/iziToast.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/izitoast/1.4.0/css/iziToast.min.css">
</head>
<body>
    <h2>Profile AJAX Test</h2>
    
    <form id="testProfileForm">
        <div>
            <label>Full Name:</label>
            <input type="text" name="full_name" value="Test User" required>
        </div>
        <div>
            <label>Email:</label>
            <input type="email" name="email" value="test@example.com" readonly>
        </div>
        <div>
            <label>Phone:</label>
            <input type="text" name="phone" value="0300-1234567" required>
        </div>
        <div>
            <label>Location:</label>
            <input type="text" name="location" value="Lahore, Pakistan" required>
        </div>
        <div>
            <label>CNIC:</label>
            <input type="text" name="cnic" value="12345-1234567-1" required>
        </div>
        <div>
            <label>Bio:</label>
            <textarea name="bio">Test bio</textarea>
        </div>
        <button type="submit">Test Save Changes</button>
    </form>

    <div id="result"></div>

    <script>
        $(document).ready(function() {
            $('#testProfileForm').submit(function(e) {
                e.preventDefault();
                console.log('Form submitted');
                
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
                        console.log('Sending request...');
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
        });
    </script>
</body>
</html> 