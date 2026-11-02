
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

        // Check existing username or email
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

                // Generate next profile ID
                $idResult = $conn->query(
                    "SELECT COALESCE(MAX(acc_info_id), 0) + 1 AS next_id
                     FROM acc_info"
                );

                $idRow = $idResult->fetch_assoc();
                $info_id = (int)$idRow["next_id"];

                // Hash password
                $hashedPassword = password_hash(
                    $password,
                    PASSWORD_DEFAULT
                );

                // Insert officer account
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

                // Insert officer profile
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

                // Save both records
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
// EXISTING ACCOUNT ACTIONS
// ==========================================

if (isset($_POST["action"])) {

    $id = intval($_POST["acc_id"]);
    $action = $_POST["action"];

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

    if (isset($sql)) {

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

// Total officer accounts
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

<!-- ===================================== -->
<!-- SIDEBAR -->
<!-- ===================================== -->

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


<!-- ===================================== -->
<!-- MAIN CONTENT -->
<!-- ===================================== -->

<div class="main-content">

    <header>

        <h1>Account Management</h1>

        <p>Manage SK Officer profiles and system access.</p>

    </header>


    <!-- ===================================== -->
    <!-- ACCOUNT MANAGEMENT SECTION -->
    <!-- ===================================== -->

    <div id="account-management"
         class="content-section"
         style="<?php echo $activeSection == 'account-management' ? '' : 'display:none;'; ?>">

        <!-- Summary -->
        <div class="summary-card">

            <h3>Total Officer Accounts</h3>

            <h2><?php echo $total; ?></h2>

        </div>


        <!-- Officer Accounts Table -->
        <div class="table-container">

            <h2>SK Officer Accounts</h2>

            <table>

                <thead>

                    <tr>
                        <th>Officer Profile</th>
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

                        <!-- Officer Profile -->
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


                        <!-- Username -->
                        <td>
                            <?php echo htmlspecialchars($row["acc_username"]); ?>
                        </td>


                        <!-- Created -->
                        <td>

                            <?php
                            echo date(
                                "M d, Y",
                                strtotime($row["acc_created"])
                            );
                            ?>

                        </td>


                        <!-- Status -->
                        <td>

                            <span class="status <?php echo strtolower($row["acc_status"]); ?>">

                                <?php echo htmlspecialchars($row["acc_status"]); ?>

                            </span>

                        </td>


                        <!-- Attempts -->
                        <td>
                            <?php echo (int)$row["acc_attempts"]; ?> / 3
                        </td>


                        <!-- Last Login -->
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


                        <!-- Actions -->
                        <td>

                            <form method="POST" class="actions">

                                <input type="hidden"
                                       name="acc_id"
                                       value="<?php echo (int)$row["acc_id"]; ?>">


                                <?php if ($row["acc_status"] == "ACTIVE") { ?>

                                    <button type="submit"
                                            name="action"
                                            value="deactivate"
                                            class="btn-danger">

                                        Deactivate

                                    </button>

                                <?php } else { ?>

                                    <button type="submit"
                                            name="action"
                                            value="activate"
                                            class="btn-success">

                                        Activate

                                    </button>

                                <?php } ?>


                                <button type="submit"
                                        name="action"
                                        value="reset"
                                        class="btn-warning">

                                    Reset Attempts

                                </button>

                            </form>

                        </td>

                    </tr>

                    <?php } ?>

                <?php } else { ?>

                    <tr>

                        <td colspan="7" class="empty">
                            No SK Officer accounts found.
                        </td>

                    </tr>

                <?php } ?>

                </tbody>

            </table>

        </div>

    </div>


    <!-- ===================================== -->
    <!-- ADD OFFICER ACCOUNT SECTION -->
    <!-- ===================================== -->

    <div id="add-account"
         class="content-section"
         style="<?php echo $activeSection == 'add-account' ? '' : 'display:none;'; ?>">

        <div class="table-container">

            <h2>Add Officer Account</h2>

            <p>Create a new account for an SK Officer.</p>


            <!-- Message -->
            <?php if ($message != "") { ?>

                <p class="message">

                    <?php echo htmlspecialchars($message); ?>

                </p>

            <?php } ?>


            <!-- Add Account Form -->
            <form method="POST" class="add-account-form">

                <input type="text"
                       name="firstname"
                       placeholder="First Name"
                       required>


                <input type="text"
                       name="lastname"
                       placeholder="Last Name"
                       required>


                <input type="email"
                       name="email"
                       placeholder="Email Address"
                       required>


                <input type="text"
                       name="position"
                       placeholder="Officer Position"
                       required>


                <input type="text"
                       name="username"
                       placeholder="Username"
                       maxlength="15"
                       required>


                <input type="text"
                       name="password"
                       placeholder="Password"
                       required>


                <button type="submit" name="add_officer">

                    Add Officer Account

                </button>

            </form>

        </div>

    </div>

</div>


<!-- ===================================== -->
<!-- JAVASCRIPT MENU NAVIGATION -->
<!-- ===================================== -->

<script>

function showSection(sectionId, element) {

    // Hide all sections
    const sections = document.querySelectorAll(".content-section");

    sections.forEach(section => {
        section.style.display = "none";
    });

    // Show selected section
    document.getElementById(sectionId).style.display = "block";

    // Remove active class from menu links
    const links = document.querySelectorAll(".menu-link");

    links.forEach(link => {
        link.classList.remove("active");
    });

    // Set selected menu as active
    element.classList.add("active");

}

</script>

</body>
</html>
