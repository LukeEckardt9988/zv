<?php
// config.php

// ** Datenbankeinstellungen **
// Ersetzen Sie diese Werte mit Ihren tatsächlichen Datenbankzugangsdaten
define('DB_HOST', 'localhost'); // Ihr Datenbank-Host (z.B. 'localhost' oder IP)
define('DB_NAME', 'p_zv');   // Name Ihrer Datenbank
define('DB_USER', 'root'); // Ihr Datenbankbenutzer
define('DB_PASS', ''); // Ihr Datenbankpasswort
define('DB_CHARSET', 'utf8mb4'); // Zeichensatz für die PDO-Verbindung

// ** Anwendungseinstellungen **
define('GUEST_PASSWORD', 'Gast'); // Ändern Sie dies unbedingt!
define('APP_TITLE', 'Zeichnungsverwaltung'); // Titel der Anwendung

// Session starten, falls noch nicht geschehen
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Fehlerberichterstattung (für Entwicklung)
// In Produktion sollten Fehler in Logs geschrieben und nicht direkt angezeigt werden
error_reporting(E_ALL);
ini_set('display_errors', 1); // Auf 0 setzen in Produktion

/**
 * Schreibt einen Eintrag in das Action-Log.
 *
 * @param PDO $pdo Die PDO-Datenbankverbindung.
 * @param string $actionType Art der Aktion (z.B. 'UPDATE_RECORD').
 * @param int|null $targetId ID des betroffenen Datensatzes.
 * @param string|null $targetTable Name der betroffenen Tabelle.
 * @param array|string|null $details Zusätzliche Details (kann ein Array sein, das dann als JSON gespeichert wird, oder ein String).
 */
function log_action(PDO $pdo, string $actionType, ?int $targetId = null, ?string $targetTable = null, $details = null) {
    $userId = $_SESSION['user_id'] ?? null;
    $username = $_SESSION['username'] ?? 'System/Unbekannt'; // Für Aktionen ohne eingeloggten User
    $ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;

    if (is_array($details) || is_object($details)) {
        $detailsString = json_encode($details, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    } else {
        $detailsString = $details;
    }

    try {
        $stmt = $pdo->prepare("INSERT INTO action_log (user_id, username, action_type, target_table, target_id, details, ip_address) 
                               VALUES (:user_id, :username, :action_type, :target_table, :target_id, :details, :ip_address)");
        $stmt->execute([
            ':user_id' => $userId,
            ':username' => $username,
            ':action_type' => $actionType,
            ':target_table' => $targetTable,
            ':target_id' => $targetId,
            ':details' => $detailsString,
            ':ip_address' => $ipAddress
        ]);
    } catch (PDOException $e) {
        // Fehler beim Loggen sollte die Hauptanwendung nicht unbedingt stoppen.
        // Hier ist es wichtig, den Fehler zu protokollieren (z.B. ins PHP-Error-Log).
        error_log("Fehler beim Schreiben ins Action-Log: " . $e->getMessage());
    }
}
?>