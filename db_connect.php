<?php
// db_connect.php

require_once 'config.php';

$dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
} catch (\PDOException $e) {
    // Für den Benutzer eine generische Fehlermeldung anzeigen
    // Die eigentliche Fehlermeldung sollte geloggt werden (siehe config.php error_reporting)
    error_log("PDO Connection Error: " . $e->getMessage());
    // Nicht die $e->getMessage() dem Endbenutzer zeigen in Produktion!
    die("Datenbankverbindungsfehler. Bitte versuchen Sie es später erneut oder kontaktieren Sie den Administrator.");
}
?>