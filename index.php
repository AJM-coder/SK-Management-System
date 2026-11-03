
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SK Management System | Barangay Salaza</title>

    <link rel="stylesheet" href="src/assets/css/index.css">
    <link rel="icon" type="image/png" href="src/assets/images/bg/SK_LOGO.png">
</head>
<body>

    <!-- Navigation -->
    <header class="navbar">
        <div class="logo">
            <h2>SANGGUNIANG KABATAAN</h2>
        </div>
    </header>

    <!-- Main Content -->
    <main class="hero">

        <div class="hero-content">

            <span class="welcome">WELCOME TO</span>

            <h1>
                BARANGAY SALAZA
            </h1>

            <p>
                Stay informed and connected with the latest announcements,
                programs, and events of the Sangguniang Kabataan
                of Barangay Salaza.
            </p>

            <!-- Main Options -->
            <div class="options">

                <a href="src/auth/login.php" class="option-card officer">
                    <!-- <div class="option-icon"></div> -->

                    <h3>SK OFFICER</h3>

                    <p>
                        Access the management system
                        and administrative dashboard.
                    </p>

                    <span class="option-link">
                        Login to Account →
                    </span>
                </a>

                <a href="public_announcements.php" class="option-card public">
                    <!-- <div class="option-icon"></div> -->

                    <h3>PUBLIC VIEWING</h3>

                    <p>
                        View public announcements,
                        upcoming events, and SK activities.
                    </p>

                    <span class="option-link">
                        Explore Updates →
                    </span>
                </a>

            </div>

        </div>

    </main>

    <!-- Footer -->
    <footer>
        <p>
            &copy; <?php echo "2026"; ?>
            Sangguniang Kabataan - Barangay Salaza.
        </p>
    </footer>

</body>
</html>
