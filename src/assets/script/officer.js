// Navigation function
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

// API REQUEST

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
        "officer_dashboard.php?action=" + action, options
    );

    return await response.json();        
}

//LOAD SHARED POSTS

async function loadDashboard() {

    try {

        const result = await apiRequest("list");

        if (!result.success) {
            return;
        }

        dashboardPosts = result.data;

        renderDashboard();

        document.getElementById("syncStatus").textContent = "● Live synchronization enabled";

        } catch (error) {

            document.getElementById("syncStatus").textContent = "● Connection unavailable";

        }
    }
        
//RENDER POST CARD

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

//RENDER DASHBOARD

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

    document.getElementById("announcementCount").textContent = announcements.length;
    document.getElementById("eventCount").textContent = events.length;
    document.getElementById("projectCount").textContent = projects.length;

    renderList("announcementPosts", announcements);
    renderList("eventPosts", events);
    renderList("projectPosts", projects);

    renderList("recentPosts", dashboardPosts.slice(0, 4));
}

// RENDER POST LIST
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

// CREATE POST
function createPost(type) {

    document.getElementById("postForm").reset();

    document.getElementById("postId").value = "";
    document.getElementById("postVersion").value = "";

    document.getElementById("postType").value = type;

    document.getElementById("modalTitle").textContent = "Add " + formatPostType(type);

    document.getElementById("postDate").disabled = type === "ANNOUNCEMENT";

    document.getElementById("postMessage").textContent = "";

    document.getElementById("imagePreviewContainer").style.display = "none";

    document.getElementById("postModal").classList.add("active");
}

// FORMAT POST TYPE
function formatPostType(type) {

    if (type === "ANNOUNCEMENT") return "Announcement";
    if (type === "EVENT") return "Event";
    if (type === "PROJECT") return "Future Project";

    return type;
}

// CLOSING MODAL
function closePostModal() {

    document.getElementById("postModal").classList.remove("active");

}

// EDIT POST
function editPost(post) {

    document.getElementById("postForm").reset();
    document.getElementById("postId").value = post.post_id;
    document.getElementById("postVersion").value = post.version;
    document.getElementById("postType").value = post.post_type;
    document.getElementById("postTitle").value = post.post_title;
    document.getElementById("postDescription").value = post.post_description;
    document.getElementById("postDate").value = post.post_date || "";
    document.getElementById("postDate").disabled = post.post_type === "ANNOUNCEMENT";
    document.getElementById("modalTitle").textContent = "Edit " + formatPostType(post.post_type);
    document.getElementById("postMessage").textContent = "";

    if ("../src/assets/images/post/" + post.post_image) {

        document.getElementById("imagePreview").src = post.post_image;
        document.getElementById("imagePreviewContainer").style.display = "block";

    } else {

        document.getElementById("imagePreviewContainer").style.display = "none";

    }

    document.getElementById("postModal").classList.add("active");

}

// DELETE POST
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

// IMAGE HANDLING
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

    document.getElementById("imagePreviewContainer").style.display = "block";

});

// POST MODAL
document.getElementById("postForm").addEventListener("submit",
    
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

// REMOVE IMAGE
function removeSelectedImage() {

    document.getElementById("postImage").value = "";
    document.getElementById("imagePreview").src = "";
    document.getElementById("imagePreviewContainer").style.display ="none";
    document.getElementById("removeImage").value = "1";

}

// UPDATE INFORMATION
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

loadDashboard();

//Check for updates every second
setInterval(loadDashboard, 1000);

// SIDEBAR TOGGLE
function toggleMenu() {

    const sidebar = document.querySelector(".sidebar");
    const overlay = document.getElementById("sidebarOverlay");
    const hamburger = document.getElementById("menuToggle");
    const isOpening = !sidebar.classList.contains("active");

    sidebar.classList.toggle("active", isOpening);
    overlay.classList.toggle("active", isOpening);

    hamburger.classList.toggle("hidden", isOpening);
}

// CLOSE MENNU
function closeMenu() {

    document.querySelector(".sidebar").classList.remove("active");
    document.getElementById("sidebarOverlay").classList.remove("active");
    document.getElementById("menuToggle").classList.remove("hidden");
}

document.querySelectorAll(".sidebar .menu-link").forEach(link => {

    link.addEventListener("click", function() {

        if (window.innerWidth <= 768) {
            document.querySelector(".sidebar").classList.remove("active");
            document.getElementById("sidebarOverlay").classList.remove("active");
        }

    });

});