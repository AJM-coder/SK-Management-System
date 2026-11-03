<?php
    
    // GET OFFICER PROFILE

    $stmt = $conn->prepare("
        SELECT
            acc_firstname,
            acc_lastname,
            acc_email,
            acc_position,
            acc_picture
        FROM acc_info
        WHERE acc_info_id = ?
        ");

    $stmt->bind_param("i", $info_id);
    $stmt->execute();

    $profile = $stmt->get_result()->fetch_assoc();

    if (!$profile) {
        $profile = [
            "acc_firstname" => "",
            "acc_lastname" => "",
            "acc_email" => "",
            "acc_position" => "",
            "acc_picture" => null
        ];
    }

?>