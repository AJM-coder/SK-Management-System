
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SK Management System | Barangay Salaza</title>

    <link rel="stylesheet" href="index.css">
</head>
<body>

    <!-- Navigation -->
    <nav class="navbar">
        <div class="logo">
            <h2>BARANGAY SALAZA</h2>
            <span>SANGGUNIANG KABATAAN</span>
        </div>

        <div class="nav-links">
            <a href="index.php" class="active">Home</a>
            <a href="public/announcements.php">Announcements</a>
            <a href="public/events.php">Events</a>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="hero">

        <div class="hero-content">

            <span class="welcome">WELCOME TO</span>

            <h1>
                SK MANAGEMENT<br>
                <span>SYSTEM</span>
            </h1>

            <p>
                Stay informed and connected with the latest announcements,
                programs, and events of the Sangguniang Kabataan
                of Barangay Salaza.
            </p>

            <!-- Main Options -->
            <div class="options">

                <a href="login.php" class="option-card officer">
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

                <a href="public/index.php" class="option-card public">
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
