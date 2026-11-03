<?php
    session_start();
    require_once "config/db.php";

    /* ---------------------------------
    OFFICER ACCESS CHECK
    ---------------------------------- */

    if (!isset($_SESSION["acc_id"])) {
        header("Location: login.php");
        exit();
    }

    include "src/assets/controls/request.php";

    $info_id = (int) $officer["acc_info_id"];

    include "src/assets/controls/officer_profile.php";

?>

<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">

        <title>Officer Dashboard | SK Management</title>

        <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&display=swap">
        <link rel="stylesheet" href="src/assets/css/officer_dashboard.css">
    </head>

    <body>

        <button class="menu-toggle" id="menuToggle" onclick="toggleMenu()">☰</button>
        <div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleMenu()"></div>

        <aside class="sidebar">
            
            <div class="officer-photo-container">

                <div class="officer-photo-frame">

                    <?php if (!empty($profile["acc_picture"])) { ?>

                        <img
                            src="src/assets/images/officer_uploads/<?= htmlspecialchars($profile["acc_picture"]) ?>"
                            alt="Officer Photo"
                        >

                    <?php } else { ?>

                        <div class="officer-photo-placeholder">
                            <?= strtoupper(substr($profile["acc_firstname"], 0, 1)) ?>
                        </div>

                    <?php } ?>

                </div>

                <h3>
                    <?= htmlspecialchars(
                        $profile["acc_firstname"] . " " . $profile["acc_lastname"]
                    ) ?>
                </h3>

                <p> <?= htmlspecialchars($profile["acc_position"]) ?> </p>

            </div>

            <div class="brand">
                <h2>BARANGAY SALAZA</h2>
                <p>SANGGUNIANG KABATAAN</p>
            </div>

            <p class="menu-label">Officer Dashboard</p>

            <a class="menu-link active" onclick="showSection('overview', this)">Dashboard</a>
            <a class="menu-link" onclick="showSection('announcements', this)">Announcements</a>
            <a class="menu-link" onclick="showSection('events', this)">Events</a>
            <a class="menu-link" onclick="showSection('projects', this)">Future Projects</a>
            <a class="menu-link" onclick="showSection('account', this)"> Account Information</a>
            <a class="menu-link" href="public_announcements.php" target="_blank">View Public Page</a>

            <?php if ($_SESSION["acc_admin"] === "YES") { ?>

                <a href="admin_dashboard.php" class="menu-link">Admin Dashboard</a>

            <?php } ?>

            <a href="src/assets/controls/logout.php" class="menu-link logout">Logout</a>

        </aside>

        <main class="main-content">

            <header class="topbar">
                <div>
                    <h1>Officer Dashboard</h1>
                    <p>
                        Welcome,
                        <?= htmlspecialchars($profile["acc_firstname"] . " " . $profile["acc_lastname"]) ?>
                    </p>
                </div>

                <span class="sync-status" id="syncStatus">
                    ● Live synchronization enabled
                </span>
            </header>

            <section id="overview" class="content-section active">

                <div class="section-heading">
                    <h2>Dashboard Overview</h2>
                    <p>Shared information and activities of the Sangguniang Kabataan.</p>
                </div>

                <div class="summary">

                    <div class="summary-card">
                        <p>Total Announcements</p>
                        <h2 id="announcementCount">0</h2>
                    </div>

                    <div class="summary-card">
                        <p>Total Events</p>
                        <h2 id="eventCount">0</h2>
                    </div>

                    <div class="summary-card">
                        <p>Future Projects</p>
                        <h2 id="projectCount">0</h2>
                    </div>

                </div>

                <div class="section-heading">
                    <h2>Recent Updates</h2>
                    <p>Latest announcements, events, and future projects.</p>
                </div>

                <div class="post-list" id="recentPosts"></div>

            </section>

            <section id="announcements" class="content-section">

                <div class="section-heading">
                    <h2>Announcements</h2>
                    <p>Information and important notices for the barangay.</p>
                </div>

                <button class="btn-primary add-button"
                    onclick="createPost('ANNOUNCEMENT')">
                    + Add Announcement
                </button>

                <div class="post-list" id="announcementPosts"></div>

            </section>

            <section id="events" class="content-section">

                <div class="section-heading">
                    <h2>Events</h2>
                    <p>Upcoming and completed SK activities.</p>
                </div>

                <button class="btn-primary add-button"
                    onclick="createPost('EVENT')">
                    + Add Event
                </button>

                <div class="post-list" id="eventPosts"></div>

            </section>

            <section id="projects" class="content-section">

                <div class="section-heading">
                    <h2>Future Projects</h2>
                    <p>Proposed programs, plans, and projects of the SK.</p>
                </div>

                <button class="btn-primary add-button"
                    onclick="createPost('PROJECT')">
                    + Add Future Project
                </button>

                <div class="post-list" id="projectPosts"></div>

            </section>

            <section id="account" class="content-section">

                <div class="section-heading">
                    <h2>Account Information</h2>
                    <p>Manage your personal officer information.</p>
                </div>

                <div class="profile-card">

                    <form id="profileForm">

                        <label>Username</label>
                        <input type="text"
                            value="<?= htmlspecialchars($officer["acc_username"]) ?>"
                            readonly>

                        <label>First Name</label>
                        <input type="text" name="firstname"
                            value="<?= htmlspecialchars($profile["acc_firstname"]) ?>"
                            required>

                        <label>Last Name</label>
                        <input type="text" name="lastname"
                            value="<?= htmlspecialchars($profile["acc_lastname"]) ?>"
                            required>

                        <label>Email Address</label>
                        <input type="email" name="email"
                            value="<?= htmlspecialchars($profile["acc_email"]) ?>"
                            required>

                        <label>Position</label>
                        <input type="text"
                            value="<?= htmlspecialchars($profile["acc_position"]) ?>"
                            readonly>

                        <br><br>

                        <button type="submit" class="btn-primary">
                            Save Changes
                        </button>

                        <p class="message" id="profileMessage"></p>

                    </form>

                </div>

            </section>

            <div class="modal-overlay" id="postModal">

                <div class="modal">

                    <div class="modal-header">
                        <h2 id="modalTitle">Add Announcement</h2>

                        <button type="button"
                            class="modal-close"
                            onclick="closePostModal()">
                            &times;
                        </button>
                    </div>

                    <form id="postForm" enctype="multipart/form-data">

                        <input type="hidden" name="id" id="postId">
                        <input type="hidden" name="version" id="postVersion">
                        <input type="hidden" name="type" id="postType">

                        <label>Title</label>
                        <input type="text"
                            name="title"
                            id="postTitle"
                            required>

                        <label>Description</label>
                        <textarea name="description"
                            id="postDescription"
                            rows="5"
                            required></textarea>

                        <label>Event / Project Date</label>
                        <input type="date"
                            name="date"
                            id="postDate">

                        <label>Upload Image</label>
                        <input type="file"
                            name="image"
                            id="postImage"
                            accept="image/jpeg,image/png,image/webp">

                        <small class="image-hint">
                            Accepted formats: JPG, PNG, WEBP. Maximum size: 5 MB.
                        </small>

                        <div class="image-preview-container" id="imagePreviewContainer">

                            <img id="imagePreview" src="" alt="Image Preview">

                            <button type="button"
                                class="btn-secondary"
                                onclick="removeSelectedImage()">
                                Remove Image
                            </button>

                        </div>

                        <input type="hidden" name="remove_image" id="removeImage" value="0">

                        <div class="modal-actions">

                            <button type="button"
                                class="btn-secondary"
                                onclick="closePostModal()">
                                Cancel
                            </button>

                            <button type="submit"
                                class="btn-primary"
                                id="savePostButton">
                                Save Post
                            </button>

                        </div>

                        <p class="message" id="postMessage"></p>

                    </form>

                </div>

            </div>

        </main>

        <script>

            const csrfToken = <?= json_encode($csrf_token) ?>;
            let dashboardPosts = [];

        </script>
        <script src="src/assets/script/officer.js"></script>
    </body>
</html>