<?php
require_once "db.php";

// Fetch all active announcements, events, and projects
$sql = "
    (SELECT post_id, post_type, post_title, post_description, post_date, post_image, created_at 
     FROM dashboard_posts 
     WHERE post_status = 'ACTIVE' AND post_type = 'ANNOUNCEMENT' 
     ORDER BY post_date ASC LIMIT 3)
    
    UNION ALL
    
    (SELECT post_id, post_type, post_title, post_description, post_date, post_image, created_at 
     FROM dashboard_posts 
     WHERE post_status = 'ACTIVE' AND post_type = 'EVENT' 
     ORDER BY post_date ASC LIMIT 3)
    
    UNION ALL
    
    (SELECT post_id, post_type, post_title, post_description, post_date, post_image, created_at 
     FROM dashboard_posts 
     WHERE post_status = 'ACTIVE' AND post_type = 'PROJECT' 
     ORDER BY post_date ASC LIMIT 3)
";

$result = $conn->query($sql);

$announcements = [];
$events = [];
$projects = [];

if ($result) {
    while ($row = $result->fetch_assoc()) {
        if ($row["post_type"] === "ANNOUNCEMENT") {
            $announcements[] = $row;
        } elseif ($row["post_type"] === "EVENT") {
            $events[] = $row;
        } elseif ($row["post_type"] === "PROJECT") {
            $projects[] = $row;
        }
    }
}

// Safe output helper
function e($value) {
    return htmlspecialchars((string)($value ?? ""), ENT_QUOTES, "UTF-8");
}

// Display a post card
function displayPosts($posts) {
    if (empty($posts)) {
        echo '<div class="empty-state">No posts available at the moment.</div>';
        return;
    }

    foreach ($posts as $post) {
        $image = $post["post_image"] ?? "";
        ?>

        <article class="post-card">

            <?php if (!empty($image)) { ?>

                <div class="post-image">
                    <img
                        src="<?php echo e($image); ?>"
                        alt="<?php echo e($post["post_title"]); ?>"
                        loading="lazy"
                        onerror="this.parentElement.style.display='none';"
                    >
                </div>

            <?php } else { ?>

                <div class="no-image">
                    <span>SK MANAGEMENT</span>
                    <small>Barangay San Roque</small>
                </div>

            <?php } ?>

            <div class="post-content">

                <span class="post-category">
                    <?php echo e($post["post_type"]); ?>
                </span>

                <h3><?php echo e($post["post_title"]); ?></h3>

                <?php if (!empty($post["post_date"])) { ?>

                    <p class="post-date">
                        <i>Event Date:</i>
                        <?php echo date("F d, Y", strtotime($post["post_date"])); ?>
                    </p>

                <?php } ?>

                <p class="post-description">
                    <?php echo nl2br(e($post["post_description"])); ?>
                </p>

                <small class="posted-date">
                    Posted on
                    <?php echo date("F d, Y", strtotime($post["created_at"])); ?>
                </small>

            </div>

        </article>

        <?php
    }
}
?>