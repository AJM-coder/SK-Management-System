<?php

    $sql = "SELECT accounts.*, acc_info.*
            FROM accounts
            INNER JOIN acc_info
            ON accounts.acc_info_id = acc_info.acc_info_id
            WHERE accounts.acc_role = 'OFFICER'
            ORDER BY acc_info.acc_lastname ASC";

    $result = $conn->query($sql);

    $total = $result->num_rows;

?>