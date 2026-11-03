<?php
session_start();
require_once "config/db.php";

// ADMIN ACCESS ONLY
if (!isset($_SESSION["acc_admin"]) || $_SESSION["acc_admin"] !== "YES") {
    header("Location: src/auth/login.php");
    exit();
}

$message = "";
$activeSection = "account-management";

// Directory
$uploadDir = __DIR__ . "/src/assets/images/officer_uploads/";
$uploadUrl = "src/assets/images/officer_uploads/";

// Create upload directory if it does not exist
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

include "src/assets/controls/delete_officer_pic.php";
include "src/assets/controls/add_acc.php";
include "src/assets/controls/officer_picture_management.php";
include "src/assets/controls/acc_handler.php";
include "src/assets/controls/acc_query.php";

?>

<!DOCTYPE html>
<html lang="en">

    <head>

        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">

        <title>Account Management | SK System</title>

        <link rel="stylesheet" href="src/assets/css/admin_dashboard.css">

    </head>

    <body>

    <div class="admin_restriction">
        <p>
            Admin page cannot be visible in any mobile phone. To access please use laptop/desktop.
        </p>

        <a class="return" href="index.php">return</a>
    </div>

    <div class="sidebar">

        <h2>SK MANAGEMENT</h2>
        <p>ADMIN PANEL</p>

        <a href="#" class="menu-link <?php echo $activeSection == 'account-management' ? 'active' : ''; ?>"
            onclick="showSection('account-management', this); return false;">

            Account Management

        </a>

        <a href="#" class="menu-link <?php echo $activeSection == 'add-account' ? 'active' : ''; ?>"
            onclick="showSection('add-account', this); return false;">

            Add Officer Account

        </a>

        <a href="officer_dashboard.php" class="menu-link"> Officer Dashboard </a>

        <a href="logout.php">Logout</a>

    </div>

    <div class="main-content">

        <header>

            <h1>Account Management</h1>
            <p>Manage SK Officer profiles and system access.</p>

        </header>

        <div id="account-management" class="content-section"
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

                            <td>
                                <?php echo htmlspecialchars($row["acc_username"]); ?>
                            </td>

                            <td>

                                <?php
                                echo !empty($row["acc_created"])
                                    ? date("M d, Y", strtotime($row["acc_created"]))
                                    : "N/A";
                                ?>

                            </td>

                            <td>

                                <span class="status <?php echo strtolower($row["acc_status"]); ?>">

                                    <?php echo htmlspecialchars($row["acc_status"]); ?>

                                </span>

                            </td>

                            <td>
                                <?php echo (int)$row["acc_attempts"]; ?> / 3
                            </td>

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
                                    
                                    <button
                                        type="submit"
                                        name="action"
                                        value="make_admin"
                                        class="btn-admin"
                                        onclick="return confirm('Are you sure you want to promote this officer to administrator?');">
                                        Make Admin
                                    </button>

                                    <button
                                        type="submit"
                                        name="action"
                                        value="delete"
                                        class="btn-delete"
                                        onclick="return confirm('Are you sure you want to permanently delete this officer account? This action cannot be undone.');">
                                        Delete Account
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

        <div id="add-account"
            class="content-section"
            style="<?php echo $activeSection == 'add-account' ? '' : 'display:none;'; ?>">

            <div class="table-container">

                <h2>Add Officer Account</h2>

                <p>Create a new account for an SK Officer.</p>  

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
                        type="text"
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

    <script src="src/assets//script/admin.js"></script>

    </body>
</html>
