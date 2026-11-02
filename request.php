<?php

$acc_id = (int) $_SESSION["acc_id"];

/* Confirm that the logged-in account is an active officer */
$check = $conn->prepare("
    SELECT acc_id, acc_username, acc_info_id
    FROM accounts
    WHERE acc_id = ?
      AND acc_role = 'OFFICER'
      AND acc_status = 'ACTIVE'
");

$check->bind_param("i", $acc_id);
$check->execute();

$officer = $check->get_result()->fetch_assoc();

if (!$officer) {
    session_destroy();
    header("Location: login.php");
    exit();
}

if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

$csrf_token = $_SESSION["csrf_token"];


/* ---------------------------------
   JSON RESPONSE FUNCTION
---------------------------------- */

function respond($success, $message = "", $data = []) {
    header("Content-Type: application/json");

    echo json_encode([
        "success" => $success,
        "message" => $message,
        "data" => $data
    ]);

    exit();
}

/* ---------------------------------
   AJAX REQUEST HANDLERS
---------------------------------- */

if (isset($_GET["action"])) {

    $action = $_GET["action"];

    /* GET ALL SHARED POSTS */

    if ($action === "list") {

        $result = $conn->query("
            SELECT
                p.*,
                a.acc_username
            FROM dashboard_posts p
            LEFT JOIN accounts a
                ON p.updated_by = a.acc_id
            ORDER BY p.created_at DESC
        ");

        $posts = [];

        while ($row = $result->fetch_assoc()) {
            $posts[] = $row;
        }

        respond(true, "Dashboard updated.", $posts);
    }

    function uploadPostImage($file) {

        if (!isset($file) || $file["error"] === UPLOAD_ERR_NO_FILE) {
            return null;
        }
    
        if ($file["error"] !== UPLOAD_ERR_OK) {
            respond(false, "Image upload failed.");
        }
    
        if ($file["size"] > 5 * 1024 * 1024) {
            respond(false, "Image must not exceed 5 MB.");
        }
    
        $allowed_types = [
            "image/jpeg" => "jpg",
            "image/png" => "png",
            "image/webp" => "webp"
        ];
    
        $mime = mime_content_type($file["tmp_name"]);
    
        if (!isset($allowed_types[$mime])) {
            respond(false, "Invalid image format.");
        }
    
        $extension = $allowed_types[$mime];
    
        $upload_directory = __DIR__ . "/uploads/posts/";
    
        if (!is_dir($upload_directory)) {
            mkdir($upload_directory, 0755, true);
        }
    
        $filename = bin2hex(random_bytes(16)) . "." . $extension;
    
        $destination = $upload_directory . $filename;
    
        if (!move_uploaded_file($file["tmp_name"], $destination)) {
            respond(false, "Unable to save uploaded image.");
        }
    
        return "uploads/posts/" . $filename;
    
    }


    /* ALL OTHER ACTIONS REQUIRE POST */

    if ($_SERVER["REQUEST_METHOD"] !== "POST") {
        respond(false, "Invalid request method.");
    }

    $is_upload_request =
    isset($_SERVER["CONTENT_TYPE"]) &&
    stripos($_SERVER["CONTENT_TYPE"], "multipart/form-data") === 0;

if ($is_upload_request) {

    $input = $_POST;

} else {

    $input = json_decode(file_get_contents("php://input"), true);

}

if (!is_array($input)) {
    respond(false, "Invalid request data.");
}

    /* CSRF CHECK */

    $token = $_SERVER["HTTP_X_CSRF_TOKEN"] ?? "";

    if (!hash_equals($_SESSION["csrf_token"], $token)) {
        respond(false, "Invalid security token. Please refresh the page.");
    }


    /* CREATE ANNOUNCEMENT, EVENT, OR PROJECT */

    if ($action === "create") {

        $type = strtoupper(trim($input["type"] ?? ""));
        $title = trim($input["title"] ?? "");
        $description = trim($input["description"] ?? "");
        $date = trim($input["date"] ?? "");
    
        $allowed_types = [
            "ANNOUNCEMENT",
            "EVENT",
            "PROJECT"
        ];
    
        if (!in_array($type, $allowed_types, true)) {
            respond(false, "Invalid post category.");
        }
    
        if ($title === "" || $description === "") {
            respond(false, "Please complete the required fields.");
        }
    
        if ($date !== "" &&
            (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) ||
            !checkdate(
                (int)substr($date, 5, 2),
                (int)substr($date, 8, 2),
                (int)substr($date, 0, 4)
            ))) {
            respond(false, "Invalid date.");
        }
    
        $date_value = $date === "" ? null : $date;
    
        $image = uploadPostImage($_FILES["image"] ?? null);
    
        $stmt = $conn->prepare("
            INSERT INTO dashboard_posts
            (
                post_type,
                post_title,
                post_description,
                post_date,
                post_image,
                created_by,
                updated_by
            )
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
    
        $stmt->bind_param(
            "sssssii",
            $type,
            $title,
            $description,
            $date_value,
            $image,
            $acc_id,
            $acc_id
        );
    
        if ($stmt->execute()) {
            respond(true, "Successfully added.");
        }
    
        if ($image !== null) {
            @unlink(__DIR__ . "/" . $image);
        }
    
        respond(false, "Unable to add the item.");
    }


    /* UPDATE EXISTING POST WITH VERSION CHECK */

    if ($action === "update") {

        $id = (int)($input["id"] ?? 0);
        $version = (int)($input["version"] ?? 0);
    
        $title = trim($input["title"] ?? "");
        $description = trim($input["description"] ?? "");
        $date = trim($input["date"] ?? "");
    
        if ($id <= 0 || $version <= 0 ||
            $title === "" || $description === "") {
    
            respond(false, "Please provide valid information.");
        }
    
        if ($date !== "" &&
            (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) ||
            !checkdate(
                (int)substr($date, 5, 2),
                (int)substr($date, 8, 2),
                (int)substr($date, 0, 4)
            ))) {
    
            respond(false, "Invalid date.");
        }
    
        $date_value = $date === "" ? null : $date;
    
        $check = $conn->prepare("
            SELECT post_image
            FROM dashboard_posts
            WHERE post_id = ? AND version = ?
        ");
    
        $check->bind_param("ii", $id, $version);
        $check->execute();
    
        $existing = $check->get_result()->fetch_assoc();
    
        if (!$existing) {
            respond(false, "This item was changed by another officer. Please reload.");
        }
    
        $old_image = $existing["post_image"];
    
        $new_image = uploadPostImage($_FILES["image"] ?? null);
    
        $remove_image = ($_POST["remove_image"] ?? "") === "1";
    
        $final_image = $new_image !== null
            ? $new_image
            : ($remove_image ? null : $old_image);
    
        $stmt = $conn->prepare("
            UPDATE dashboard_posts
            SET
                post_title = ?,
                post_description = ?,
                post_date = ?,
                post_image = ?,
                updated_by = ?,
                version = version + 1
            WHERE post_id = ?
              AND version = ?
        ");
    
        $stmt->bind_param(
            "ssssiii",
            $title,
            $description,
            $date_value,
            $final_image,
            $acc_id,
            $id,
            $version
        );
    
        if ($stmt->execute() && $stmt->affected_rows === 1) {
    
            if ($old_image &&
                ($new_image !== null || $remove_image)) {
    
                $old_path = __DIR__ . "/" . $old_image;
    
                if (is_file($old_path)) {
                    @unlink($old_path);
                }
    
            }
    
            respond(true, "Changes saved successfully.");
        }
    
        if ($new_image !== null) {
            @unlink(__DIR__ . "/" . $new_image);
        }
    
        respond(false, "Unable to update the item.");
    }


    /* DELETE POST */

    if ($action === "delete") {

        $id = (int) ($input["id"] ?? 0);

        if ($id <= 0) {
            respond(false, "Invalid item.");
        }

        $stmt = $conn->prepare("
            DELETE FROM dashboard_posts
            WHERE post_id = ?
        ");

        $stmt->bind_param("i", $id);

        if ($stmt->execute() && $stmt->affected_rows === 1) {
            respond(true, "Item deleted successfully.");
        }

        respond(false, "Item could not be deleted.");
    }


    /* UPDATE LOGGED-IN OFFICER'S ACCOUNT INFORMATION */

    if ($action === "update_profile") {

        $firstname = trim($input["firstname"] ?? "");
        $lastname = trim($input["lastname"] ?? "");
        $email = trim($input["email"] ?? "");

        if ($firstname === "" || $lastname === "" || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            respond(false, "Please provide a valid name and email address.");
        }

        $info_id = (int) $officer["acc_info_id"];

        $stmt = $conn->prepare("
            SELECT acc_info_id
            FROM acc_info
            WHERE acc_email = ?
              AND acc_info_id != ?
        ");

        $stmt->bind_param("si", $email, $info_id);
        $stmt->execute();

        if ($stmt->get_result()->num_rows > 0) {
            respond(false, "That email address is already being used.");
        }

        $stmt = $conn->prepare("
            UPDATE acc_info
            SET acc_firstname = ?,
                acc_lastname = ?,
                acc_email = ?
            WHERE acc_info_id = ?
        ");

        $stmt->bind_param(
            "sssi",
            $firstname,
            $lastname,
            $email,
            $info_id
        );

        if ($stmt->execute()) {
            respond(true, "Account information updated.");
        }

        respond(false, "Unable to update account information.");
    }


    respond(false, "Unknown action.");
}
?>