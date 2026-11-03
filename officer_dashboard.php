    <?php
    session_start();
    require_once "db.php";

    /* ---------------------------------
    OFFICER ACCESS CHECK
    ---------------------------------- */

    if (!isset($_SESSION["acc_id"])) {
        header("Location: login.php");
        exit();
    }

    include "request.php";


    /* ---------------------------------
    GET OFFICER PROFILE
    ---------------------------------- */

    $info_id = (int) $officer["acc_info_id"];

    $stmt = $conn->prepare("
        SELECT
            acc_firstname,
            acc_lastname,
            acc_email,
            acc_position,
            acc_picture
        FROM acc_info
        WHERE acc_info_id = ?
    ");

    $stmt->bind_param("i", $info_id);
    $stmt->execute();

    $profile = $stmt->get_result()->fetch_assoc();

    if (!$profile) {
        $profile = [
            "acc_firstname" => "",
            "acc_lastname" => "",
            "acc_email" => "",
            "acc_position" => "",
            "acc_picture" => null
        ];
    }
    ?>

    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">

        <title>Officer Dashboard | SK Management</title>

        <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&display=swap">
        <link rel="stylesheet" href="officer_dashboard.css">
    </head>

    <body>

    <button class="menu-toggle" id="menuToggle" onclick="toggleMenu()">
    ☰
</button>

<div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleMenu()"></div>

    <aside class="sidebar">

        
    <!-- OFFICER PHOTO -->
    <div class="officer-photo-container">

    <div class="officer-photo-frame">

        <?php if (!empty($profile["acc_picture"])) { ?>

            <img
                src="officer_uploads/<?= htmlspecialchars($profile["acc_picture"]) ?>"
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

    <p>
        <?= htmlspecialchars($profile["acc_position"]) ?>
    </p>

    </div>

        <div class="brand">
            <h2>BARANGAY SALAZA</h2>
            <p>SANGGUNIANG KABATAAN</p>
        </div>

        <p class="menu-label">Officer Dashboard</p>

        <a class="menu-link active" onclick="showSection('overview', this)">
            Dashboard
        </a>

        <a class="menu-link" onclick="showSection('announcements', this)">
            Announcements
        </a>

        <a class="menu-link" onclick="showSection('events', this)">
            Events
        </a>

        <a class="menu-link" onclick="showSection('projects', this)">
            Future Projects
        </a>

        <a class="menu-link" onclick="showSection('account', this)">
            Account Information
        </a>

        <a class="menu-link" href="public_announcements.php" target="_blank">
            View Public Page
        </a>

        <?php if ($_SESSION["acc_admin"] === "YES") { ?>

            <a href="admin_dashboard.php" class="menu-link">
                Admin Dashboard
            </a>

        <?php } ?>

        <a href="logout.php" class="menu-link logout">
            Logout
        </a>

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


        <!-- DASHBOARD OVERVIEW -->

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


        <!-- ANNOUNCEMENTS -->

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


        <!-- EVENTS -->

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


        <!-- FUTURE PROJECTS -->

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


        <!-- ACCOUNT INFORMATION -->

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

    <!-- POST MODAL -->

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


    /* ---------------------------------
    SECTION NAVIGATION
    ---------------------------------- */

    function showSection(sectionId, element) {

document.querySelectorAll(".content-section").forEach(section => {
    section.classList.remove("active");
});

document.querySelectorAll(".menu-link").forEach(link => {
    link.classList.remove("active");
});

document.getElementById(sectionId).classList.add("active");

element.classList.add("active");

if (window.innerWidth <= 768) {
    closeMenu();
}
}


    /* ---------------------------------
    API REQUEST
    ---------------------------------- */

    async function apiRequest(action, data = null) {

        const options = {
            method: data ? "POST" : "GET",
            headers: {}
        };

        if (data) {
            options.headers["Content-Type"] = "application/json";
            options.headers["X-CSRF-Token"] = csrfToken;
            options.body = JSON.stringify(data);
        }

        const response = await fetch(
            "officer_dashboard.php?action=" + action,
            options
        );

        return await response.json();
    }


    /* ---------------------------------
    LOAD SHARED POSTS
    ---------------------------------- */

    async function loadDashboard() {

        try {

            const result = await apiRequest("list");

            if (!result.success) {
                return;
            }

            dashboardPosts = result.data;

            renderDashboard();

            document.getElementById("syncStatus").textContent =
                "● Live synchronization enabled";

        } catch (error) {

            document.getElementById("syncStatus").textContent =
                "● Connection unavailable";

        }
    }


    /* ---------------------------------
    RENDER POST CARD
    ---------------------------------- */

    function createPostCard(post) {

        const card = document.createElement("div");
        card.className = "post-card";

        const type = document.createElement("span");
        type.className = "post-type";
        type.textContent = post.post_type;

        const title = document.createElement("h3");
        title.textContent = post.post_title;

        const description = document.createElement("p");
        description.textContent = post.post_description;

        const meta = document.createElement("div");
        meta.className = "post-meta";

        const dateText = post.post_date
            ? "Date: " + post.post_date
            : "No scheduled date";

        meta.textContent =
            dateText + " | Last updated by: " +
            (post.acc_username || "Officer");

        const actions = document.createElement("div");
        actions.className = "post-actions";

        const editButton = document.createElement("button");
        editButton.className = "btn-edit";
        editButton.textContent = "Edit";
        editButton.onclick = () => editPost(post);

        const deleteButton = document.createElement("button");
        deleteButton.className = "btn-delete";
        deleteButton.textContent = "Delete";
        deleteButton.onclick = () => deletePost(post.post_id);

        actions.append(editButton, deleteButton);

        card.append(type, title, description, meta, actions);

        return card;
    }


    /* ---------------------------------
    RENDER DASHBOARD
    ---------------------------------- */

    function renderDashboard() {

        const announcements = dashboardPosts.filter(
            post => post.post_type === "ANNOUNCEMENT"
        );

        const events = dashboardPosts.filter(
            post => post.post_type === "EVENT"
        );

        const projects = dashboardPosts.filter(
            post => post.post_type === "PROJECT"
        );

        document.getElementById("announcementCount").textContent =
            announcements.length;

        document.getElementById("eventCount").textContent =
            events.length;

        document.getElementById("projectCount").textContent =
            projects.length;

        renderList("announcementPosts", announcements);
        renderList("eventPosts", events);
        renderList("projectPosts", projects);

        renderList("recentPosts", dashboardPosts.slice(0, 4));
    }


    function renderList(elementId, posts) {

        const container = document.getElementById(elementId);

        container.replaceChildren();

        if (posts.length === 0) {

            const empty = document.createElement("div");
            empty.className = "empty-state";
            empty.textContent = "No records available.";

            container.appendChild(empty);
            return;
        }

        posts.forEach(post => {
            container.appendChild(createPostCard(post));
        });
    }


    /* ---------------------------------
    CREATE POST
    ---------------------------------- */
    function createPost(type) {

    document.getElementById("postForm").reset();

    document.getElementById("postId").value = "";
    document.getElementById("postVersion").value = "";

    document.getElementById("postType").value = type;

    document.getElementById("modalTitle").textContent =
        "Add " + formatPostType(type);

    document.getElementById("postDate").disabled =
        type === "ANNOUNCEMENT";

    document.getElementById("postMessage").textContent = "";

    document.getElementById("imagePreviewContainer").style.display =
        "none";

    document.getElementById("postModal").classList.add("active");
    }


    function formatPostType(type) {

    if (type === "ANNOUNCEMENT") return "Announcement";
    if (type === "EVENT") return "Event";
    if (type === "PROJECT") return "Future Project";

    return type;
    }


    function closePostModal() {

    document.getElementById("postModal").classList.remove("active");

    }


    /* ---------------------------------
    EDIT POST
    ---------------------------------- */

    function editPost(post) {

    document.getElementById("postForm").reset();

    document.getElementById("postId").value = post.post_id;

    document.getElementById("postVersion").value = post.version;

    document.getElementById("postType").value = post.post_type;

    document.getElementById("postTitle").value = post.post_title;

    document.getElementById("postDescription").value =
        post.post_description;

    document.getElementById("postDate").value =
        post.post_date || "";

    document.getElementById("postDate").disabled =
        post.post_type === "ANNOUNCEMENT";

    document.getElementById("modalTitle").textContent =
        "Edit " + formatPostType(post.post_type);

    document.getElementById("postMessage").textContent = "";

    if (post.post_image) {

        document.getElementById("imagePreview").src =
            post.post_image;

        document.getElementById("imagePreviewContainer").style.display =
            "block";

    } else {

        document.getElementById("imagePreviewContainer").style.display =
            "none";

    }

    document.getElementById("postModal").classList.add("active");

    }

    /* ---------------------------------
    DELETE POST
    ---------------------------------- */

    async function deletePost(id) {

        if (!confirm("Are you sure you want to delete this item?")) {
            return;
        }

        const result = await apiRequest("delete", {
            id: Number(id)
        });

        alert(result.message);

        if (result.success) {
            loadDashboard();
        }
    }

    const postImage = document.getElementById("postImage");

    postImage.addEventListener("change", function() {

        const file = this.files[0];

        if (!file) {
            return;
        }

        const allowedTypes = [
            "image/jpeg",
            "image/png",
            "image/webp"
        ];

        if (!allowedTypes.includes(file.type)) {

            alert("Please select a JPG, PNG, or WEBP image.");

            this.value = "";

            return;
        }

        if (file.size > 5 * 1024 * 1024) {

            alert("Image must not exceed 5 MB.");

            this.value = "";

            return;
        }

        const preview = document.getElementById("imagePreview");

        preview.src = URL.createObjectURL(file);

        document.getElementById("imagePreviewContainer").style.display =
            "block";

    });


    function removeSelectedImage() {

        document.getElementById("postImage").value = "";

        document.getElementById("imagePreview").src = "";

        document.getElementById("imagePreviewContainer").style.display =
            "none";

    }

    document.getElementById("postForm").addEventListener(
        "submit",
        async function(event) {

            event.preventDefault();

            const formData = new FormData(this);

            const id = formData.get("id");

            const action = id ? "update" : "create";

            const response = await fetch(
                "officer_dashboard.php?action=" + action,
                {
                    method: "POST",

                    headers: {
                        "X-CSRF-Token": csrfToken
                    },

                    body: formData
                }
            );

            const result = await response.json();

            document.getElementById("postMessage").textContent =
                result.message;

            if (result.success) {

                closePostModal();

                await loadDashboard();

            }

        }
    );

    function removeSelectedImage() {

    document.getElementById("postImage").value = "";

    document.getElementById("imagePreview").src = "";

    document.getElementById("imagePreviewContainer").style.display =
        "none";

    document.getElementById("removeImage").value = "1";

    }

    /* ---------------------------------
    UPDATE ACCOUNT INFORMATION
    ---------------------------------- */

    document.getElementById("profileForm").addEventListener("submit", async function(event) {

        event.preventDefault();

        const formData = new FormData(this);

        const result = await apiRequest("update_profile", {
            firstname: formData.get("firstname"),
            lastname: formData.get("lastname"),
            email: formData.get("email")
        });

        document.getElementById("profileMessage").textContent =
            result.message;

        if (result.success) {
            loadDashboard();
        }

    });


    /* ---------------------------------
    AUTOMATIC SYNCHRONIZATION
    ---------------------------------- */

    loadDashboard();

    /* Check for updates every second */
    setInterval(loadDashboard, 1000);

    /* HAMBURGER MENU */

    function toggleMenu() {

const sidebar = document.querySelector(".sidebar");
const overlay = document.getElementById("sidebarOverlay");
const hamburger = document.getElementById("menuToggle");

const isOpening = !sidebar.classList.contains("active");

sidebar.classList.toggle("active", isOpening);
overlay.classList.toggle("active", isOpening);

// Hide hamburger only while sidebar is open
hamburger.classList.toggle("hidden", isOpening);
}

function closeMenu() {

document.querySelector(".sidebar").classList.remove("active");

document.getElementById("sidebarOverlay").classList.remove("active");

// Always show hamburger when sidebar closes
document.getElementById("menuToggle").classList.remove("hidden");
}


    /* CLOSE MENU AFTER SELECTING A SECTION */

    document.querySelectorAll(".sidebar .menu-link").forEach(link => {

        link.addEventListener("click", function() {

            if (window.innerWidth <= 768) {
                document.querySelector(".sidebar").classList.remove("active");
                document.getElementById("sidebarOverlay").classList.remove("active");
            }

        });

    });
    </script>

    </body>
    </html>