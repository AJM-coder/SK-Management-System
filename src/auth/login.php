
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SK Barangay Salaza | Login</title>
    <link rel="stylesheet" href="../assets/css/login.css">
</head>
<body>

    <div class="login-container">

        <div class="login-header">

            <!-- LOGO -->
            <div class="logo-container">
                <img src="../assets/images/bg/SK_LOGO.png" alt="SK Barangay Salaza Logo">
            </div>

            <h2>SK BARANGAY SALAZA</h2>
            <p class="subtitle">Management System</p>

        </div>

        <div class="login-content">

            <h1>Welcome Back</h1>
            <p class="login-description">Sign in to your account to continue.</p>

            <form action="auth.php" method="POST">

                <div class="form-group">
                    <label for="username">Username</label>
                    <input
                        type="text"
                        id="username"
                        name="username"
                        placeholder="Enter your username"
                        required
                    >
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input
                        type="password"
                        id="password"
                        name="password"
                        placeholder="Enter your password"
                        required
                    >
                </div>

                <button type="submit">Sign In</button>

            </form>

            <p class="footer-text">
                Sangguniang Kabataan | Barangay Salaza
            </p>

        </div>

    </div>

</body>
</html>
