<?php
// get_logs.php - FINALE KORREKTUR (behebt den "htmlspecialchars array given" Fehler)

// Stellt sicher, dass eine Session existiert.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Lädt die Konfiguration und die DB-Verbindung.
require_once 'config.php';
require_once 'db_connect.php';

// Prüft die Authentifizierung des Benutzers.
if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    exit('Nicht authentifiziert.');
}

// Stellt sicher, dass das $pdo-Objekt existiert.
if (!isset($pdo) || !$pdo instanceof PDO) {
    http_response_code(500);
    error_log("FATAL in get_logs.php: PDO-Objekt ist nicht verfügbar.");
    exit('Serverfehler: Datenbankverbindungsobjekt konnte nicht initialisiert werden.');
}

// Holt die ID des Datensatzes aus der URL.
$id_from_url = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id_from_url) {
    http_response_code(400);
    exit('Ungültige oder fehlende Datensatz-ID.');
}

try {
    $stmt = $pdo->prepare(
        "SELECT log_timestamp, username, ip_address, details
         FROM action_log
         WHERE target_id = :target_id
         ORDER BY log_timestamp DESC"
    );
    
    $stmt->execute(['target_id' => $id_from_url]);
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($logs)) {
        echo '<div class="alert alert-info mt-3">Keine Logs für diesen Datensatz gefunden.</div>';
        exit;
    }

    $html = '<table class="table table-striped table-bordered table-sm mt-3">';
    $html .= '<thead class="thead-light"><tr><th style="width: 20%;">Zeitstempel</th><th style="width: 15%;">Benutzer</th><th style="width: 15%;">IP-Adresse</th><th>Details</th></tr></thead><tbody>';

    foreach ($logs as $log) {
        // --- START: Robuste Verarbeitung der "details"-Spalte ---
        $details_text = '';
        $details_json = json_decode($log['details'], true);

        if (json_last_error() === JSON_ERROR_NONE && is_array($details_json)) {
            // Es ist ein valides JSON-Objekt/Array
            foreach ($details_json as $key => $value) {
                $key_safe = htmlspecialchars($key, ENT_QUOTES, 'UTF-8');
                $value_safe = '';

                if (is_array($value) || is_object($value)) {
                    // Wenn der Wert selbst ein Array/Objekt ist, wird er als formatierter JSON-String dargestellt.
                    $value_safe = htmlspecialchars(json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8');
                } else {
                    // Wenn es ein einfacher Wert ist (Text, Zahl etc.), wird er normal behandelt.
                    $value_safe = htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
                }
                $details_text .= "<b>" . $key_safe . ":</b> " . $value_safe . "<br>";
            }
        } else {
            // Falls es kein valides JSON ist, wird der Originaltext sicher ausgegeben.
            $details_text = htmlspecialchars($log['details'], ENT_QUOTES, 'UTF-8');
        }
        // --- ENDE: Robuste Verarbeitung ---

        $html .= '<tr>';
        $html .= '<td>' . htmlspecialchars($log['log_timestamp'], ENT_QUOTES, 'UTF-8') . '</td>';
        $html .= '<td>' . htmlspecialchars($log['username'], ENT_QUOTES, 'UTF-8') . '</td>';
        $html .= '<td>' . htmlspecialchars($log['ip_address'], ENT_QUOTES, 'UTF-8') . '</td>';
        // Für die Details verwenden wir keinen <pre>-Tag mehr, um das HTML (<b>, <br>) korrekt darzustellen.
        $html .= '<td>' . $details_text . '</td>';
        $html .= '</tr>';
    }

    $html .= '</tbody></table>';
    echo $html;

} catch (PDOException $e) {
    http_response_code(500);
    error_log("Log fetch PDOException in get_logs.php: " . $e->getMessage());
    exit('Fehler bei der Datenbankabfrage zum Abrufen der Logs.');
}