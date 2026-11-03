<?php
    // Delete an existing officer picture safely
    function deleteOfficerPicture($filename, $uploadDir) {

        if (empty($filename)) {
            return;
        }

        // Only allow deletion of files inside officer_uploads
        $filename = basename($filename);
        $filePath = $uploadDir . $filename;

        if (is_file($filePath)) {
            unlink($filePath);
        }
    }
?>