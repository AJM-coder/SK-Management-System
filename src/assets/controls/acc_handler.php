<?php

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

?>