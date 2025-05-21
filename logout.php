<?php
// logout.php
require_once 'config.php'; // Für session_start()

$_SESSION = array(); // Alle Session-Variablen löschen

if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

session_destroy(); // Session zerstören

header('Location: login.php'); // Zur Login-Seite weiterleiten
exit;
?>