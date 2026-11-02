<?php
session_start();
require_once "db.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $username = trim($_POST["username"]);
    $password = $_POST["password"];

    // Check if fields are empty
    if (empty($username) || empty($password)) {
        echo "<script>alert('Please enter username and password.'); window.location='login.php';</script>";
        exit();
    }

    // Check if username exists
    $sql = "SELECT * FROM accounts WHERE acc_username = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $username);
    $stmt->execute();

    $result = $stmt->get_result();

    if ($result->num_rows == 1) {

        $row = $result->fetch_assoc();

        // Check account status
        if ($row["acc_status"] == "INACTIVE") {
            echo "<script>alert('Your account has been deactivated. Please contact the administrator.'); window.location='login.php';</script>";
            exit();
        }

        // Verify hashed password
        if (password_verify($password, $row["acc_password"])) {

            // Reset failed attempts and update last login
            $update = "UPDATE accounts 
                       SET acc_attempts = 0,
                           acc_lastlog = NOW()
                       WHERE acc_id = ?";

            $update_stmt = $conn->prepare($update);
            $update_stmt->bind_param("i", $row["acc_id"]);
            $update_stmt->execute();

            // Create user session
            session_regenerate_id(true);

            $_SESSION["user_id"] = $row["acc_id"];
            $_SESSION["username"] = $row["acc_username"];

            // Redirect to dashboard
            if($row['acc_admin']=='YES'){
                header("Location: admin_dashboard.php");
                exit();
            } else {
                header("Location: dashboard.php");
                exit();
            }

        } else {

            // Add failed login attempt
            $attempts = $row["acc_attempts"] + 1;

            if ($attempts >= 3) {

                // Deactivate account after 3 failed attempts
                $update = "UPDATE accounts 
                           SET acc_attempts = ?,
                               acc_status = 'INACTIVE'
                           WHERE acc_id = ?";

                $update_stmt = $conn->prepare($update);
                $update_stmt->bind_param("ii", $attempts, $row["acc_id"]);
                $update_stmt->execute();

                echo "<script>alert('Your account has been deactivated due to 3 incorrect login attempts. Contact the administrator.'); window.location='login.php';</script>";
                exit();

            } else {

                // Update failed attempts
                $update = "UPDATE accounts 
                           SET acc_attempts = ?
                           WHERE acc_id = ?";

                $update_stmt = $conn->prepare($update);
                $update_stmt->bind_param("ii", $attempts, $row["acc_id"]);
                $update_stmt->execute();

                $remaining = 3 - $attempts;

                echo "<script>alert('Incorrect password. Attempts remaining: $remaining'); window.location='login.php';</script>";
                exit();
            }
        }

    } else {

        // Username does not exist
        echo "<script>alert('Username does not exist.'); window.location='login.php';</script>";
        exit();
    }

} else {
    header("Location: login.php");
    exit();
}
?>
