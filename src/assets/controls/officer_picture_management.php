<?php

/** @var string $uploadDir */

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

?>