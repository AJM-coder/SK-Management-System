
<?php
session_start();
require_once "db.php";

// Get officer accounts and profiles
$sql = "SELECT accounts.*, acc_info.*
        FROM accounts
        INNER JOIN acc_info
        ON accounts.acc_info_id = acc_info.acc_info_id
        WHERE accounts.acc_role='OFFICER'
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

<!-- Sidebar -->
<div class="sidebar">

    <h2>SK MANAGEMENT</h2>
    <p>ADMIN PANEL</p>

    <a href="admin_dashboard.php" class="active">
        Account Management
    </a>

    <a href="logout.php">
        Logout
    </a>

</div>

<!-- Main Content -->
<div class="main-content">

    <header>
        <h1>Account Management</h1>
        <p>Manage SK Officer profiles and system access.</p>
    </header>

    <!-- Summary -->
    <div class="summary-card">
        <h3>Total Officer Accounts</h3>
        <h2><?php echo $total; ?></h2>
    </div>

    <!-- Accounts -->
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

                    <!-- Profile -->
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
                        <?php echo $row["acc_attempts"]; ?> / 3
                    </td>

                    <!-- Last Login -->
                    <td>
                        <?php
                        if ($row["acc_lastlog"] != "0000-00-00") {
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
                                   value="<?php echo $row["acc_id"]; ?>">

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

</body>
</html>
