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


    /* ALL OTHER ACTIONS REQUIRE POST */

    if ($_SERVER["REQUEST_METHOD"] !== "POST") {
        respond(false, "Invalid request method.");
    }

    $input = json_decode(file_get_contents("php://input"), true);

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

        $allowed_types = ["ANNOUNCEMENT", "EVENT", "PROJECT"];

        if (!in_array($type, $allowed_types, true)) {
            respond(false, "Invalid post category.");
        }

        if ($title === "" || $description === "") {
            respond(false, "Please complete the title and description.");
        }

        if ($date !== "" && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            respond(false, "Invalid date.");
        }

        $date_value = $date === "" ? null : $date;

        $stmt = $conn->prepare("
            INSERT INTO dashboard_posts
            (post_type, post_title, post_description, post_date, created_by, updated_by)
            VALUES (?, ?, ?, ?, ?, ?)
        ");

        $stmt->bind_param(
            "ssssii",
            $type,
            $title,
            $description,
            $date_value,
            $acc_id,
            $acc_id
        );

        if ($stmt->execute()) {
            respond(true, "Successfully added.");
        }

        respond(false, "Unable to add the item.");
    }


    /* UPDATE EXISTING POST WITH VERSION CHECK */

    if ($action === "update") {

        $id = (int) ($input["id"] ?? 0);
        $version = (int) ($input["version"] ?? 0);
        $title = trim($input["title"] ?? "");
        $description = trim($input["description"] ?? "");
        $date = trim($input["date"] ?? "");

        if ($id <= 0 || $version <= 0 || $title === "" || $description === "") {
            respond(false, "Please provide valid information.");
        }

        if ($date !== "" && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            respond(false, "Invalid date.");
        }

        $date_value = $date === "" ? null : $date;

        $stmt = $conn->prepare("
            UPDATE dashboard_posts
            SET
                post_title = ?,
                post_description = ?,
                post_date = ?,
                updated_by = ?,
                version = version + 1
            WHERE post_id = ?
              AND version = ?
        ");

        $stmt->bind_param(
            "sssiii",
            $title,
            $description,
            $date_value,
            $acc_id,
            $id,
            $version
        );

        $stmt->execute();

        if ($stmt->affected_rows === 1) {
            respond(true, "Changes saved successfully.");
        }

        respond(
            false,
            "This item was changed by another officer. The latest version will be loaded. Please review your changes and try again."
        );
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