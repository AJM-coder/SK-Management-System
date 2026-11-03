<?php

/** @var string $uploadDir */

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

    } elseif ($action == "make_admin"){

        $sql = "UPDATE accounts
                    SET acc_admin = 'YES',
                        acc_status = 'ACTIVE',
                        acc_attempts = 0
                    WHERE acc_id = ?
                    AND acc_role = 'OFFICER'
                    AND acc_admin = 'NO'";
    } elseif ($action == "delete") {

        // Retrieve officer information and picture
        $check = $conn->prepare(
            "SELECT accounts.acc_info_id, acc_info.acc_picture
             FROM accounts
             INNER JOIN acc_info
             ON accounts.acc_info_id = acc_info.acc_info_id
             WHERE accounts.acc_id = ?
             AND accounts.acc_role = 'OFFICER'
             AND accounts.acc_admin = 'NO'"
        );

            $check->bind_param("i", $id);
            $check->execute();

            $officer = $check->get_result()->fetch_assoc();
            $check->close();

            if (!$officer) {

                $message = "Officer account not found.";

            } else {

                $info_id = (int)$officer["acc_info_id"];
                $picture = $officer["acc_picture"];

                $conn->begin_transaction();

                try {

                    // Delete officer account
                    $stmt = $conn->prepare(
                        "DELETE FROM accounts
                         WHERE acc_id = ?
                         AND acc_role = 'OFFICER'
                         AND acc_admin = 'NO'"
                    );

                    $stmt->bind_param("i", $id);
                    $stmt->execute();

                    if ($stmt->affected_rows !== 1) {
                        throw new Exception("Account could not be deleted.");
                    }

                    $stmt->close();

                    // Delete account information only if no account uses it
                    $stmt = $conn->prepare(
                        "SELECT COUNT(*) AS total
                         FROM accounts
                         WHERE acc_info_id = ?"
                    );

                    $stmt->bind_param("i", $info_id);
                    $stmt->execute();

                    $remaining = $stmt->get_result()->fetch_assoc();
                    $stmt->close();

                    if ((int)$remaining["total"] === 0) {

                        $stmt = $conn->prepare(
                            "DELETE FROM acc_info
                             WHERE acc_info_id = ?"
                        );

                        $stmt->bind_param("i", $info_id);
                        $stmt->execute();
                        $stmt->close();
                    }

                    $conn->commit();

                    // Delete picture after successful database deletion
                    if ((int)$remaining["total"] === 0) {
                        deleteOfficerPicture($picture, $uploadDir);
                    }

                    $message = "Officer account successfully deleted.";

                } catch (Throwable $e) {

                    $conn->rollback();

                    $message = "Unable to delete officer account.";
                }
            }
        }


    if ($sql !== null) {

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();
    }
}

?>