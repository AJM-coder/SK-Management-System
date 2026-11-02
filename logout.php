
<?php
session_start();

// Clear all session variables
session_unset();

// Destroy the session
session_destroy();

// Redirect to main landing page
header("Location: index.php");
exit();
?>