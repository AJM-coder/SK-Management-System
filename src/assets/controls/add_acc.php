<?php

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

?>