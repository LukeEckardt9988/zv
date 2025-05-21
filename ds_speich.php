<?php
// ds_speich.php (vollständig aktualisiert mit Logging)
require_once 'config.php'; // Stellt sicher, dass ggf. functions.php mit log_action() geladen wird
require_once 'db_connect.php';

if (!isset($_SESSION['user_id'])) {
    $_SESSION['error_message'] = "Nicht authentifiziert. Aktion abgebrochen.";
    header('Location: login.php');
    exit;
}

$action = $_POST['action'] ?? null;
$id = $_POST['id'] ?? null; // ID des zu bearbeitenden/löschenden Datensatzes
$suchsachnr_return = trim($_POST['suchsachnr_return'] ?? '');

// Formulardaten sammeln
$sachnr = trim($_POST['sachnr'] ?? '');
$kurz = trim($_POST['kurz'] ?? '');
$aez = trim($_POST['aez'] ?? '');
$dokart = trim($_POST['dokart'] ?? '');
$teildok = trim($_POST['teildok'] ?? '');
$hinw = trim($_POST['hinw'] ?? '');
$vers = trim($_POST['vers'] ?? '');

// 1. Automatisches Datum setzen
$dat_combined = date('Y-m-d');

// 2. Dynamische Indizes und deren Status verarbeiten
$indizes_to_save = [];
$submitted_indices_values = $_POST['dynamic_indices'] ?? [];
$submitted_indices_statuses_map = $_POST['dynamic_indices_status'] ?? [];

for ($i = 0; $i < 7; $i++) {
    $db_index_num = $i + 1;
    if (isset($submitted_indices_values[$i]) && $submitted_indices_values[$i] !== '') {
        $ind_val = trim($submitted_indices_values[$i]);
        $indizes_to_save["ind" . $db_index_num] = $ind_val;
        $status_val = $submitted_indices_statuses_map[$i] ?? '0';
        $indizes_to_save["ind" . $db_index_num . "_status"] = ($status_val == '1') ? 1 : 0;
    } else {
        $indizes_to_save["ind" . $db_index_num] = null;
        $indizes_to_save["ind" . $db_index_num . "_status"] = 0;
    }
}

// Globalen "record_status" verarbeiten
$record_status_for_save = 0;
if ($action === 'Änderung speichern') {
    $record_status_for_save = isset($_POST['record_status_checkbox']) && $_POST['record_status_checkbox'] == '1' ? 1 : 0;
}

$params = []; // Initialisiere $params für den catch-Block

try {
    if ($action === 'Datensatz löschen' && $id && ctype_digit((string)$id)) {
        // Optional: Daten des zu löschenden Datensatzes für das Log holen
        $stmt_old_data = $pdo->prepare("SELECT sachnr FROM zeichnverw WHERE id = :id");
        $stmt_old_data->execute(['id' => $id]);
        $old_data_for_log = $stmt_old_data->fetch(PDO::FETCH_ASSOC);

        $stmt = $pdo->prepare("DELETE FROM zeichnverw WHERE id = :id");
        $stmt->execute(['id' => $id]);
        
        log_action($pdo, 'DELETE_RECORD', (int)$id, 'zeichnverw', ['sachnr_deleted' => $old_data_for_log['sachnr'] ?? 'N/A']);
        $_SESSION['success_message'] = "Datensatz (ID: " . htmlspecialchars($id) . ") erfolgreich gelöscht.";
        header('Location: scrolltab.php?suchsachnr=' . urlencode($suchsachnr_return));
        exit;

    } elseif ($action === 'Änderung speichern' && $id && ctype_digit((string)$id)) {
        // Optional: Alten Datensatz für detailliertes Logging laden (siehe vorherige Antwort für Implementierung)
        // $old_record = ...;

        $sql = "UPDATE zeichnverw SET
                    sachnr=:sachnr, kurz=:kurz, aez=:aez, dokart=:dokart, teildok=:teildok,
                    ind1=:ind1, ind1_status=:ind1_status, ind2=:ind2, ind2_status=:ind2_status,
                    ind3=:ind3, ind3_status=:ind3_status, ind4=:ind4, ind4_status=:ind4_status,
                    ind5=:ind5, ind5_status=:ind5_status, ind6=:ind6, ind6_status=:ind6_status,
                    ind7=:ind7, ind7_status=:ind7_status,
                    dat=:dat, hinw=:hinw, vers=:vers, record_status=:record_status
                WHERE id = :id";
        $params = [
            'sachnr' => $sachnr, 'kurz' => $kurz, 'aez' => $aez, 'dokart' => $dokart, 'teildok' => $teildok,
            'ind1' => $indizes_to_save['ind1'], 'ind1_status' => $indizes_to_save['ind1_status'],
            'ind2' => $indizes_to_save['ind2'], 'ind2_status' => $indizes_to_save['ind2_status'],
            'ind3' => $indizes_to_save['ind3'], 'ind3_status' => $indizes_to_save['ind3_status'],
            'ind4' => $indizes_to_save['ind4'], 'ind4_status' => $indizes_to_save['ind4_status'],
            'ind5' => $indizes_to_save['ind5'], 'ind5_status' => $indizes_to_save['ind5_status'],
            'ind6' => $indizes_to_save['ind6'], 'ind6_status' => $indizes_to_save['ind6_status'],
            'ind7' => $indizes_to_save['ind7'], 'ind7_status' => $indizes_to_save['ind7_status'],
            'dat' => $dat_combined, 'hinw' => $hinw, 'vers' => $vers,
            'record_status' => $record_status_for_save,
            'id' => $id
        ];
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        // Für detaillierteres Logging: $changes = vergleiche($old_record, $params);
        log_action($pdo, 'UPDATE_RECORD', (int)$id, 'zeichnverw', ['sachnr_updated' => $sachnr, 'fields_potentially_changed' => count($params)-1]);
        $_SESSION['success_message'] = "Änderungen für Datensatz (ID: " . htmlspecialchars($id) . ") erfolgreich gespeichert.";
        header('Location: ds_aend.php?id=' . $id . '&suchsachnr=' . urlencode($suchsachnr_return));
        exit;

    } elseif ($action === 'Speichern') { // Neuer Datensatz
        $sql = "INSERT INTO zeichnverw ( /* ... Spalten ... */ record_status) VALUES ( /* ... Platzhalter ... */ :record_status)";
        // (SQL-Statement wie in Ihrer Datei, hier gekürzt zur Übersicht)
        $sql = "INSERT INTO zeichnverw (
                    sachnr, kurz, aez, dokart, teildok,
                    ind1, ind1_status, ind2, ind2_status, ind3, ind3_status, ind4, ind4_status,
                    ind5, ind5_status, ind6, ind6_status, ind7, ind7_status,
                    dat, hinw, vers, record_status
                ) VALUES (
                    :sachnr, :kurz, :aez, :dokart, :teildok,
                    :ind1, :ind1_status, :ind2, :ind2_status, :ind3, :ind3_status, :ind4, :ind4_status,
                    :ind5, :ind5_status, :ind6, :ind6_status, :ind7, :ind7_status,
                    :dat, :hinw, :vers, :record_status
                )";
        $params = [
            'sachnr' => $sachnr, 'kurz' => $kurz, 'aez' => $aez, 'dokart' => $dokart, 'teildok' => $teildok,
            'ind1' => $indizes_to_save['ind1'], 'ind1_status' => $indizes_to_save['ind1_status'],
            'ind2' => $indizes_to_save['ind2'], 'ind2_status' => $indizes_to_save['ind2_status'],
            'ind3' => $indizes_to_save['ind3'], 'ind3_status' => $indizes_to_save['ind3_status'],
            'ind4' => $indizes_to_save['ind4'], 'ind4_status' => $indizes_to_save['ind4_status'],
            'ind5' => $indizes_to_save['ind5'], 'ind5_status' => $indizes_to_save['ind5_status'],
            'ind6' => $indizes_to_save['ind6'], 'ind6_status' => $indizes_to_save['ind6_status'],
            'ind7' => $indizes_to_save['ind7'], 'ind7_status' => $indizes_to_save['ind7_status'],
            'dat' => $dat_combined, 'hinw' => $hinw, 'vers' => $vers,
            'record_status' => 0 // Neue Datensätze sind standardmäßig "nicht erhalten"
        ];
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $new_id = $pdo->lastInsertId();

        log_action($pdo, 'CREATE_RECORD', (int)$new_id, 'zeichnverw', ['sachnr_created' => $sachnr]);
        $_SESSION['success_message'] = "Neuer Datensatz erfolgreich gespeichert (Neue ID: " . htmlspecialchars($new_id) . ").";
        header('Location: scrolltab.php?suchsachnr=' . urlencode($sachnr));
        exit;

    } else {
        $_SESSION['error_message'] = "Unbekannte oder ungültige Aktion: " . htmlspecialchars($action);
        header('Location: scrolltab.php?suchsachnr=' . urlencode($suchsachnr_return));
        exit;
    }

} catch (PDOException $e) {
    error_log("Database operation error in ds_speich.php: " . $e->getMessage() . " for action: " . $action . " with params: " . json_encode($params ?? []));
    $errorMessage = "Ein Datenbankfehler ist aufgetreten. ";
    // ... (Restliche Fehlerbehandlung) ...
    // (Der Rest des Catch-Blocks bleibt wie in Ihrer Datei)
    if (isset($e->errorInfo[1]) && $e->errorInfo[1] == 1062) {
        $errorMessage .= "Eintrag (z.B. Sachnummer) existiert bereits oder ein anderer UNIQUE Index wurde verletzt.";
    } else {
        $errorMessage .= "Details wurden protokolliert. Bitte versuchen Sie es später erneut.";
    }
    $_SESSION['error_message'] = $errorMessage;

    if ($action === 'Änderung speichern' && $id) {
        header('Location: ds_aend.php?id=' . $id . '&suchsachnr=' . urlencode($suchsachnr_return));
    } elseif ($action === 'Speichern') {
        header('Location: scrolltab.php?suchsachnr=' . urlencode($sachnr));
    } else {
        header('Location: scrolltab.php?suchsachnr=' . urlencode($suchsachnr_return));
    }
    exit;
}
?>