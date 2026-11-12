<?php
// Ends the logged-in session completely, then sends the visitor to the login page.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$_SESSION = [];
session_destroy();

// Open a brand-new anonymous session (new ID) just to carry the confirmation message
session_start();
session_regenerate_id(true);
$_SESSION['flash'] = ['type' => 'success', 'msg' => 'You have been logged out.'];

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Location: login.php?logged_out=1');
exit;
