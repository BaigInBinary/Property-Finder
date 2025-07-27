<?php include 'inc/header.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - PropFind</title>
    <link rel="stylesheet" href="css/auth.css">
    <link rel="stylesheet" href="css/styles.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body class="auth-body">
    <div class="container d-flex justify-content-center align-items-center min-vh-100">
        <div class="auth-form-wrapper p-4 shadow rounded bg-white" style="max-width: 400px; width: 100%;">
            <h2 class="mb-3 text-center">Forgot Password</h2>
            <p class="text-muted text-center mb-4">Enter your email to receive a password reset link.</p>
            <form id="forgotPasswordForm" method="POST" action="backend/forgot-password.php">
                <div class="mb-3">
                    <label for="email" class="form-label">Email address</label>
                    <input type="email" class="form-control" id="email" name="email" required autofocus>
                </div>
                <button type="submit" class="btn btn-primary w-100">Send Reset Link</button>
            </form>
            <div id="forgotPasswordMsg" class="mt-3"></div>
            <div class="mt-3 text-center">
                <a href="login.php" class="text-decoration-none">Back to Login</a>
            </div>
        </div>
    </div>
    <script>
    document.getElementById('forgotPasswordForm').addEventListener('submit', function(e) {
        e.preventDefault();
        var form = this;
        var msgDiv = document.getElementById('forgotPasswordMsg');
        msgDiv.innerHTML = '';
        var formData = new FormData(form);
        fetch(form.action, {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                msgDiv.innerHTML = '<div class="alert alert-success">' + data.message + '</div>';
            } else {
                msgDiv.innerHTML = '<div class="alert alert-danger">' + (data.message || 'Failed to send reset link.') + '</div>';
            }
        })
        .catch(() => {
            msgDiv.innerHTML = '<div class="alert alert-danger">An error occurred. Please try again.</div>';
        });
    });
    </script>
</body>
</html> 