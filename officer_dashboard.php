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
        acc_position
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
        "acc_position" => ""
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

    <div class="brand">
        <h2>BARANGAY SAN ROQUE</h2>
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

async function createPost(type) {

    const title = prompt("Enter title:");

    if (title === null || title.trim() === "") {
        return;
    }

    const description = prompt("Enter description:");

    if (description === null || description.trim() === "") {
        return;
    }

    let date = "";

    if (type === "EVENT" || type === "PROJECT") {

        date = prompt("Enter date (YYYY-MM-DD), or leave blank:");

        if (date === null) {
            return;
        }

        date = date.trim();
    }

    const result = await apiRequest("create", {
        type: type,
        title: title,
        description: description,
        date: date
    });

    alert(result.message);

    if (result.success) {
        loadDashboard();
    }
}


/* ---------------------------------
   EDIT POST
---------------------------------- */

async function editPost(post) {

    const title = prompt("Edit title:", post.post_title);

    if (title === null || title.trim() === "") {
        return;
    }

    const description = prompt(
        "Edit description:",
        post.post_description
    );

    if (description === null || description.trim() === "") {
        return;
    }

    const date = prompt(
        "Edit date (YYYY-MM-DD), or leave blank:",
        post.post_date || ""
    );

    if (date === null) {
        return;
    }

    const result = await apiRequest("update", {
        id: Number(post.post_id),
        version: Number(post.version),
        title: title,
        description: description,
        date: date.trim()
    });

    alert(result.message);

    await loadDashboard();
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
    document.querySelector(".sidebar").classList.toggle("active");
    document.getElementById("sidebarOverlay").classList.toggle("active");
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