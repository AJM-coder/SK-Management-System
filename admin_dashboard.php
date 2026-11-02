
<?php
session_start();
require_once "db.php";

// ADMIN ACCESS ONLY
if (!isset($_SESSION["acc_admin"]) || $_SESSION["acc_admin"] !== "YES") {
    header("Location: login.php");
    exit();
}

$message = "";
$activeSection = "account-management";

// ==========================================
// OFFICER PICTURE CONFIGURATION
// ==========================================

$uploadDir = __DIR__ . "/officer_uploads/";
$uploadUrl = "officer_uploads/";

// Create upload directory if it does not exist
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

// Delete an existing officer picture safely
function deleteOfficerPicture($filename, $uploadDir) {

    if (empty($filename)) {
        return;
    }

    // Only allow deletion of files inside officer_uploads
    $filename = basename($filename);
    $filePath = $uploadDir . $filename;

    if (is_file($filePath)) {
        unlink($filePath);
    }
}

// ==========================================
// ADD OFFICER ACCOUNT
// ==========================================

if (isset($_POST["add_officer"])) {

    $firstname = trim($_POST["firstname"]);
    $lastname  = trim($_POST["lastname"]);
    $email     = trim($_POST["email"]);
    $position  = trim($_POST["position"]);
    $username  = trim($_POST["username"]);
    $password  = $_POST["password"];

    $activeSection = "add-account";

    if (
        $firstname == "" ||
        $lastname == "" ||
        $email == "" ||
        $position == "" ||
        $username == "" ||
        $password == ""
    ) {

        $message = "Please complete all fields.";

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $message = "Please enter a valid email address.";

    } elseif (strlen($username) > 15) {

        $message = "Username must not exceed 15 characters.";

    } else {

        $check = $conn->prepare(
            "SELECT accounts.acc_id
             FROM accounts
             LEFT JOIN acc_info
             ON accounts.acc_info_id = acc_info.acc_info_id
             WHERE accounts.acc_username = ?
             OR acc_info.acc_email = ?"
        );

        $check->bind_param("ss", $username, $email);
        $check->execute();

        $existing = $check->get_result();

        if ($existing->num_rows > 0) {

            $message = "Username or email already exists.";

        } else {

            try {

                $conn->begin_transaction();

                $idResult = $conn->query(
                    "SELECT COALESCE(MAX(acc_info_id), 0) + 1 AS next_id
                     FROM acc_info"
                );

                $idRow = $idResult->fetch_assoc();
                $info_id = (int)$idRow["next_id"];

                $hashedPassword = password_hash(
                    $password,
                    PASSWORD_DEFAULT
                );

                $stmt = $conn->prepare(
                    "INSERT INTO accounts
                    (
                        acc_username,
                        acc_password,
                        acc_role,
                        acc_attempts,
                        acc_status,
                        acc_admin,
                        acc_info_id
                    )
                    VALUES (?, ?, 'OFFICER', 0, 'ACTIVE', 'NO', ?)"
                );

                $stmt->bind_param(
                    "ssi",
                    $username,
                    $hashedPassword,
                    $info_id
                );

                $stmt->execute();

                $stmt2 = $conn->prepare(
                    "INSERT INTO acc_info
                    (
                        acc_info_id,
                        acc_firstname,
                        acc_lastname,
                        acc_email,
                        acc_position
                    )
                    VALUES (?, ?, ?, ?, ?)"
                );

                $stmt2->bind_param(
                    "issss",
                    $info_id,
                    $firstname,
                    $lastname,
                    $email,
                    $position
                );

                $stmt2->execute();

                $conn->commit();

                $message = "Officer account successfully created.";

            } catch (Throwable $e) {

                $conn->rollback();

                $message = "Unable to create account. Please check your database.";

            }
        }

        $check->close();
    }
}

// ==========================================
// OFFICER PICTURE MANAGEMENT
// ==========================================

if (isset($_POST["picture_action"])) {

    $activeSection = "account-management";

    $acc_id = isset($_POST["acc_id"])
        ? (int)$_POST["acc_id"]
        : 0;

    $pictureAction = $_POST["picture_action"];

    // Confirm that the account belongs to an officer
    $checkOfficer = $conn->prepare(
        "SELECT accounts.acc_info_id, acc_info.acc_picture
         FROM accounts
         INNER JOIN acc_info
         ON accounts.acc_info_id = acc_info.acc_info_id
         WHERE accounts.acc_id = ?
         AND accounts.acc_role = 'OFFICER'"
    );

    $checkOfficer->bind_param("i", $acc_id);
    $checkOfficer->execute();

    $officerResult = $checkOfficer->get_result();

    if ($officerResult->num_rows === 0) {

        $message = "Officer account not found.";

    } else {

        $officer = $officerResult->fetch_assoc();

        $info_id = (int)$officer["acc_info_id"];
        $oldPicture = $officer["acc_picture"];

        // --------------------------------------
        // REMOVE PICTURE
        // --------------------------------------

        if ($pictureAction === "remove") {

            $stmt = $conn->prepare(
                "UPDATE acc_info
                 SET acc_picture = NULL
                 WHERE acc_info_id = ?"
            );

            $stmt->bind_param("i", $info_id);

            if ($stmt->execute()) {

                deleteOfficerPicture($oldPicture, $uploadDir);

                $message = "Officer picture successfully removed.";

            } else {

                $message = "Unable to remove officer picture.";

            }

            $stmt->close();

        }

        // --------------------------------------
        // UPLOAD OR CHANGE PICTURE
        // --------------------------------------

        elseif ($pictureAction === "upload") {

            if (
                !isset($_FILES["officer_picture"]) ||
                $_FILES["officer_picture"]["error"] !== UPLOAD_ERR_OK
            ) {

                $message = "Please select a valid picture to upload.";

            } elseif ($_FILES["officer_picture"]["size"] > 3 * 1024 * 1024) {

                $message = "Picture must not exceed 3 MB.";

            } else {

                $tmpFile = $_FILES["officer_picture"]["tmp_name"];

                // Detect actual file MIME type
                $finfo = new finfo(FILEINFO_MIME_TYPE);
                $mimeType = $finfo->file($tmpFile);

                $allowedTypes = [
                    "image/jpeg" => "jpg",
                    "image/png"  => "png",
                    "image/webp" => "webp"
                ];

                if (!isset($allowedTypes[$mimeType])) {

                    $message = "Only JPG, PNG, and WEBP images are allowed.";

                } elseif (getimagesize($tmpFile) === false) {

                    $message = "The uploaded file is not a valid image.";

                } else {

                    // Generate a unique filename
                    $extension = $allowedTypes[$mimeType];

                    $newFilename = "officer_" .
                        $acc_id . "_" .
                        bin2hex(random_bytes(8)) .
                        "." . $extension;

                    $destination = $uploadDir . $newFilename;

                    if (move_uploaded_file($tmpFile, $destination)) {

                        $stmt = $conn->prepare(
                            "UPDATE acc_info
                             SET acc_picture = ?
                             WHERE acc_info_id = ?"
                        );

                        $stmt->bind_param(
                            "si",
                            $newFilename,
                            $info_id
                        );

                        if ($stmt->execute()) {

                            // Delete old image only after successful update
                            deleteOfficerPicture($oldPicture, $uploadDir);

                            $message = "Officer picture successfully uploaded.";

                        } else {

                            // Remove new file if database update fails
                            deleteOfficerPicture($newFilename, $uploadDir);

                            $message = "Unable to save the officer picture.";

                        }

                        $stmt->close();

                    } else {

                        $message = "Unable to upload picture. Check folder permissions.";

                    }
                }
            }

        } else {

            $message = "Invalid picture action.";

        }
    }

    $checkOfficer->close();
}

// ==========================================
// EXISTING ACCOUNT ACTIONS
// ==========================================

if (isset($_POST["action"])) {

    $id = intval($_POST["acc_id"]);
    $action = $_POST["action"];

    $sql = null;

    if ($action == "activate") {

        $sql = "UPDATE accounts
                SET acc_status = 'ACTIVE',
                    acc_attempts = 0
                WHERE acc_id = ?
                AND acc_role = 'OFFICER'
                AND acc_admin = 'NO'";

    } elseif ($action == "deactivate") {

        $sql = "UPDATE accounts
                SET acc_status = 'INACTIVE'
                WHERE acc_id = ?
                AND acc_role = 'OFFICER'
                AND acc_admin = 'NO'";

    } elseif ($action == "reset") {

        $sql = "UPDATE accounts
                SET acc_attempts = 0
                WHERE acc_id = ?
                AND acc_role = 'OFFICER'
                AND acc_admin = 'NO'";

    }

    if ($sql !== null) {

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();
    }
}

// ==========================================
// GET OFFICER ACCOUNTS
// ==========================================

$sql = "SELECT accounts.*, acc_info.*
        FROM accounts
        INNER JOIN acc_info
        ON accounts.acc_info_id = acc_info.acc_info_id
        WHERE accounts.acc_role = 'OFFICER'
        AND accounts.acc_role = 'OFFICER'
        ORDER BY acc_info.acc_lastname ASC";

$result = $conn->query($sql);

$total = $result->num_rows;

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Account Management | SK System</title>

    <link rel="stylesheet" href="admin_dashboard.css">

</head>

<body>

<!-- SIDEBAR -->

<div class="sidebar">

    <h2>SK MANAGEMENT</h2>
    <p>ADMIN PANEL</p>

    <a href="#"
       class="menu-link <?php echo $activeSection == 'account-management' ? 'active' : ''; ?>"
       onclick="showSection('account-management', this); return false;">

        Account Management

    </a>

    <a href="#"
       class="menu-link <?php echo $activeSection == 'add-account' ? 'active' : ''; ?>"
       onclick="showSection('add-account', this); return false;">

        Add Officer Account

    </a>

    <a href="officer_dashboard.php" class="menu-link">
        Officer Dashboard
    </a>

    <a href="logout.php">
        Logout
    </a>

</div>


<!-- MAIN CONTENT -->

<div class="main-content">

    <header>

        <h1>Account Management</h1>

        <p>Manage SK Officer profiles and system access.</p>

    </header>


    <!-- ACCOUNT MANAGEMENT -->

    <div id="account-management"
         class="content-section"
         style="<?php echo $activeSection == 'account-management' ? '' : 'display:none;'; ?>">

        <div class="summary-card">

            <h3>Total Officer Accounts</h3>

            <h2><?php echo $total; ?></h2>

        </div>


        <?php if ($message != "") { ?>

            <p class="message">
                <?php echo htmlspecialchars($message); ?>
            </p>

        <?php } ?>


        <div class="table-container">

            <h2>SK Officer Accounts</h2>

            <table>

                <thead>

                    <tr>
                        <th>Officer Profile</th>
                        <th>Officer Picture</th>
                        <th>Username</th>
                        <th>Created</th>
                        <th>Status</th>
                        <th>Attempts</th>
                        <th>Last Login</th>
                        <th>Actions</th>
                    </tr>

                </thead>

                <tbody>

                <?php if ($total > 0) { ?>

                    <?php while ($row = $result->fetch_assoc()) { ?>

                    <tr>

                        <!-- OFFICER PROFILE -->

                        <td>

                            <strong>
                                <?php
                                echo htmlspecialchars(
                                    $row["acc_firstname"] . " " .
                                    $row["acc_lastname"]
                                );
                                ?>
                            </strong>

                            <small>
                                <?php echo htmlspecialchars($row["acc_position"]); ?>
                            </small>

                            <small>
                                <?php echo htmlspecialchars($row["acc_email"]); ?>
                            </small>

                        </td>


                        <!-- OFFICER PICTURE -->

                        <td>

                            <div class="officer-picture-wrapper">

                                <?php if (!empty($row["acc_picture"])) { ?>

                                    <img
                                        src="<?php
                                            echo htmlspecialchars(
                                                $uploadUrl . basename($row["acc_picture"])
                                            );
                                        ?>"
                                        alt="Officer Picture"
                                        class="officer-picture"
                                    >

                                <?php } else { ?>

                                    <div class="officer-picture-placeholder">
                                        <?php
                                        echo strtoupper(
                                            substr($row["acc_firstname"], 0, 1)
                                        );
                                        ?>
                                    </div>

                                <?php } ?>

                            </div>


                            <!-- UPLOAD / CHANGE PICTURE -->

                            <form method="POST"
                                  enctype="multipart/form-data"
                                  class="picture-form">

                                <input
                                    type="hidden"
                                    name="acc_id"
                                    value="<?php echo (int)$row["acc_id"]; ?>"
                                >

                                <input
                                    type="file"
                                    name="officer_picture"
                                    accept="image/jpeg,image/png,image/webp"
                                    required
                                    class="picture-input"
                                >

                                <button
                                    type="submit"
                                    name="picture_action"
                                    value="upload"
                                    class="btn-picture"
                                >
                                    <?php echo !empty($row["acc_picture"]) ? "Change" : "Add Picture"; ?>
                                </button>

                            </form>


                            <!-- REMOVE PICTURE -->

                            <?php if (!empty($row["acc_picture"])) { ?>

                                <form method="POST"
                                      onsubmit="return confirm('Remove this officer picture?');">

                                    <input
                                        type="hidden"
                                        name="acc_id"
                                        value="<?php echo (int)$row["acc_id"]; ?>"
                                    >

                                    <button
                                        type="submit"
                                        name="picture_action"
                                        value="remove"
                                        class="btn-remove-picture"
                                    >
                                        Remove Picture
                                    </button>

                                </form>

                            <?php } ?>

                        </td>


                        <!-- USERNAME -->

                        <td>
                            <?php echo htmlspecialchars($row["acc_username"]); ?>
                        </td>


                        <!-- CREATED -->

                        <td>

                            <?php
                            echo !empty($row["acc_created"])
                                ? date("M d, Y", strtotime($row["acc_created"]))
                                : "N/A";
                            ?>

                        </td>


                        <!-- STATUS -->

                        <td>

                            <span class="status <?php echo strtolower($row["acc_status"]); ?>">

                                <?php echo htmlspecialchars($row["acc_status"]); ?>

                            </span>

                        </td>


                        <!-- ATTEMPTS -->

                        <td>
                            <?php echo (int)$row["acc_attempts"]; ?> / 3
                        </td>


                        <!-- LAST LOGIN -->

                        <td>

                            <?php

                            if (
                                !empty($row["acc_lastlog"]) &&
                                $row["acc_lastlog"] != "0000-00-00" &&
                                $row["acc_lastlog"] != "0000-00-00 00:00:00"
                            ) {

                                echo date(
                                    "M d, Y",
                                    strtotime($row["acc_lastlog"])
                                );

                            } else {

                                echo "Never logged in";

                            }

                            ?>

                        </td>


                        <!-- ACCOUNT ACTIONS -->

                        <td>

                            <form method="POST" class="actions">

                                <input
                                    type="hidden"
                                    name="acc_id"
                                    value="<?php echo (int)$row["acc_id"]; ?>"
                                >

                                <?php if ($row["acc_status"] == "ACTIVE") { ?>

                                    <button
                                        type="submit"
                                        name="action"
                                        value="deactivate"
                                        class="btn-danger"
                                    >
                                        Deactivate
                                    </button>

                                <?php } else { ?>

                                    <button
                                        type="submit"
                                        name="action"
                                        value="activate"
                                        class="btn-success"
                                    >
                                        Activate
                                    </button>

                                <?php } ?>

                                <button
                                    type="submit"
                                    name="action"
                                    value="reset"
                                    class="btn-warning"
                                >
                                    Reset Attempts
                                </button>

                            </form>

                        </td>

                    </tr>

                    <?php } ?>

                <?php } else { ?>

                    <tr>

                        <td colspan="8" class="empty">
                            No SK Officer accounts found.
                        </td>

                    </tr>

                <?php } ?>

                </tbody>

            </table>

        </div>

    </div>


    <!-- ADD OFFICER ACCOUNT -->

    <div id="add-account"
         class="content-section"
         style="<?php echo $activeSection == 'add-account' ? '' : 'display:none;'; ?>">

        <div class="table-container">

            <h2>Add Officer Account</h2>

            <p>Create a new account for an SK Officer.</p>


            <?php if ($message != "") { ?>

                <p class="message">
                    <?php echo htmlspecialchars($message); ?>
                </p>

            <?php } ?>


            <form method="POST" class="add-account-form">

                <input
                    type="text"
                    name="firstname"
                    placeholder="First Name"
                    required
                >

                <input
                    type="text"
                    name="lastname"
                    placeholder="Last Name"
                    required
                >

                <input
                    type="email"
                    name="email"
                    placeholder="Email Address"
                    required
                >

                <input
                    type="text"
                    name="position"
                    placeholder="Officer Position"
                    required
                >

                <input
                    type="text"
                    name="username"
                    placeholder="Username"
                    maxlength="15"
                    required
                >

                <input
                    type="password"
                    name="password"
                    placeholder="Password"
                    required
                >

                <button type="submit" name="add_officer">
                    Add Officer Account
                </button>

            </form>

        </div>

    </div>

</div>


<!-- JAVASCRIPT -->

<script>

function showSection(sectionId, element) {

    const sections = document.querySelectorAll(".content-section");

    sections.forEach(section => {
        section.style.display = "none";
    });

    document.getElementById(sectionId).style.display = "block";

    const links = document.querySelectorAll(".menu-link");

    links.forEach(link => {
        link.classList.remove("active");
    });

    element.classList.add("active");

}

</script>

</body>
</html>
