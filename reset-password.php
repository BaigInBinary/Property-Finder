<?php include 'inc/header.php'; ?>
<?php $token = $_GET['token'] ?? ''; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - PropFind</title>
    <link rel="stylesheet" href="css/auth.css">
    <link rel="stylesheet" href="css/styles.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body class="auth-body">
    <div class="container d-flex justify-content-center align-items-center min-vh-100">
        <div class="auth-form-wrapper p-4 shadow rounded bg-white" style="max-width: 400px; width: 100%;">
            <h2 class="mb-3 text-center">Reset Password</h2>
            <?php if (!$token): ?>
                <div class="alert alert-danger">Invalid or missing token.</div>
            <?php else: ?>
            <form id="resetPasswordForm" method="POST" action="backend/reset-password.php">
                <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
                <div class="mb-3">
                    <label for="password" class="form-label">New Password</label>
                    <div class="input-group">
                        <input type="password" class="form-control" id="password" name="password" required minlength="8">
                        <button class="btn btn-outline-secondary toggle-password" type="button" tabindex="-1"><i class="fas fa-eye"></i></button>
                    </div>
                </div>
                <div class="mb-3">
                    <label for="confirmPassword" class="form-label">Confirm Password</label>
                    <div class="input-group">
                        <input type="password" class="form-control" id="confirmPassword" name="confirmPassword" required minlength="8">
                        <button class="btn btn-outline-secondary toggle-password" type="button" tabindex="-1"><i class="fas fa-eye"></i></button>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary w-100">Reset Password</button>
            </form>
            <div id="resetPasswordMsg" class="mt-3"></div>
            <?php endif; ?>
            <div class="mt-3 text-center">
                <a href="login.php" class="text-decoration-none">Back to Login</a>
            </div>
        </div>
    </div>
    <script>
    document.getElementById('resetPasswordForm')?.addEventListener('submit', function(e) {
        e.preventDefault();
        var form = this;
        var msgDiv = document.getElementById('resetPasswordMsg');
        msgDiv.innerHTML = '';
        var formData = new FormData(form);
        if (form.password.value !== form.confirmPassword.value) {
            msgDiv.innerHTML = '<div class="alert alert-danger">Passwords do not match.</div>';
            return;
        }
        fetch(form.action, {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                msgDiv.innerHTML = '<div class="alert alert-success">' + data.message + '</div>';
                form.reset();
            } else {
                msgDiv.innerHTML = '<div class="alert alert-danger">' + (data.message || 'Failed to reset password.') + '</div>';
            }
        })
        .catch(() => {
            msgDiv.innerHTML = '<div class="alert alert-danger">An error occurred. Please try again.</div>';
        });
    });

    // Show/hide password toggle
    document.querySelectorAll('.toggle-password').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var input = this.parentElement.querySelector('input');
            if (input.type === 'password') {
                input.type = 'text';
                this.querySelector('i').classList.remove('fa-eye');
                this.querySelector('i').classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                this.querySelector('i').classList.remove('fa-eye-slash');
                this.querySelector('i').classList.add('fa-eye');
            }
        });
    });
    </script>
</body>
</html> 