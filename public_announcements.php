<?php
include "src/assets/controls/fetch_post.php"
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>SK Announcements | Barangay Salaza</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="src/assets/css/public_announcements.css">
</head>

<body>

    <!-- HEADER -->

    <header class="header">

        <div class="brand">
            <h2>BARANGAY SALAZA</h2>
            <p>SANGGUNIANG KABATAAN</p>
        </div>

        <nav class="header-nav">
            <a href="index.php">Home</a>
            <a href="#announcements">Announcements</a>
            <a href="#events">Events</a>
            <a href="#projects">Projects</a>
        </nav>

    </header>


    <!-- HERO -->

    <section class="hero">

        <h1>SK Public Information</h1>

        <p>
            Stay informed about the latest announcements, upcoming events,
            and future projects of the Sangguniang Kabataan of Barangay Salaza.
        </p>

    </section>


    <!-- MAIN CONTENT -->

    <main class="container">

        <!-- ANNOUNCEMENTS -->

        <section class="section" id="announcements">

            <div class="section-heading">
                <h2>Announcements</h2>
                <span><?php echo count($announcements); ?> Posts</span>
            </div>

            <div class="post-grid">
                <?php displayPosts($announcements); ?>
            </div>

        </section>


        <!-- EVENTS -->

        <section class="section" id="events">

            <div class="section-heading">
                <h2>Upcoming Events</h2>
                <span><?php echo count($events); ?> Posts</span>
            </div>

            <div class="post-grid">
                <?php displayPosts($events); ?>
            </div>

        </section>


        <!-- FUTURE PROJECTS -->

        <section class="section" id="projects">

            <div class="section-heading">
                <h2>Projects</h2>
                <span><?php echo count($projects); ?> Posts</span>
            </div>

            <div class="post-grid">
                <?php displayPosts($projects); ?>
            </div>

        </section>

    </main>


    <!-- FOOTER -->

    <footer class="footer">

        <p>
            Sangguniang Kabataan Management System<br>
            © <?php echo "2026"; ?> All Rights Reserved.
        </p>

    </footer>

</body>
</html>
