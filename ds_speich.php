<?php
// ds_speich.php (angepasst für abteilungsspezifische Index-Status-Checkbox)
require_once 'config.php'; // Stellt sicher, dass ggf. functions.php mit log_action() geladen wird
require_once 'db_connect.php'; // Stellt $pdo bereit

if (!isset($_SESSION['user_id'])) {
    $_SESSION['error_message'] = "Nicht authentifiziert. Aktion abgebrochen.";
    header('Location: login.php');
    exit;
}

$action = $_POST['action'] ?? null;
$id = $_POST['id'] ?? null;
$suchsachnr_return = trim($_POST['suchsachnr_return'] ?? '');

// Angemeldete Benutzerdaten für Berechtigungen holen
$loggedInUserDepartment = strtoupper($_SESSION['user_department'] ?? '');

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

// Globalen "record_status" verarbeiten (gesteuert durch PG)
$record_status_for_save = 0; // Standard für neue Datensätze
if ($action === 'Änderung speichern') {
    // Wert für globale Checkbox (die von PG bedient wird)
    $record_status_for_save = (isset($_POST['record_status_checkbox']) && $_POST['record_status_checkbox'] == '1') ? 1 : 0;

    // Wenn der aktuelle User nicht PG ist, MUSS der alte Wert von record_status aus der DB genommen werden,
    // da die Checkbox für ihn disabled ist und nicht im POST ankommt, wenn sie vorher 1 war.
    if ($loggedInUserDepartment !== 'PG') {
        $stmt_old_global_status = $pdo->prepare("SELECT record_status FROM zeichnverw WHERE id = :id");
        $stmt_old_global_status->execute(['id' => $id]);
        $old_global_status_data = $stmt_old_global_status->fetch(PDO::FETCH_ASSOC);
        if ($old_global_status_data) {
            $record_status_for_save = $old_global_status_data['record_status'];
        }
    }
}


// 2. Dynamische Indizes und deren abteilungsspezifischen Status verarbeiten
$indizes_to_save = [];
$submitted_indices_values = $_POST['dynamic_indices'] ?? [];
// Wert der NEUEN abteilungsspezifischen Checkbox "Alle meine [Abt.] Indizes als erhalten markieren"
$department_indices_batch_status_checked = (isset($_POST['my_department_indices_status']) && $_POST['my_department_indices_status'] == '1');

// Optionsliste wird benötigt, um die Abteilung eines Indexwertes zu bestimmen
// Es wird angenommen, dass $index_options_list in diesem Kontext verfügbar ist
// (z.B. aus config.php oder hier direkt definiert, falls nicht schon in config.php)
if (!isset($index_options_list) || !is_array($index_options_list)) {
    // Fallback oder Fehler, falls $index_options_list nicht verfügbar ist.
    // Für dieses Beispiel wird angenommen, sie ist global verfügbar oder wird hier geladen.
    // Sie haben sie in ds_aend.php und scrolltab.php definiert,
    // ds_speich.php braucht sie aber auch für die Zuordnung.
    // Am besten in config.php zentral definieren oder hier erneut.
    $index_options_list = [
        ''    => 'Keine Auswahl',
        '140' => '140 - Technologie',
        '141' => '141 - PG',
        '142' => '142 - PG',
        '145' => '145 - PP',
        '146' => '146 - PF',
        '600' => '600 - Einkauf',
        '241' => '241 - PP',
        '152' => '152 - PG',
        '153' => '153 - PG',
        '154' => '154 - Versand',
        '300' => '300 - Technologie'
    ];
}


if ($action === 'Änderung speichern' && $id) {
    $stmt_current_indices_data = $pdo->prepare(
        "SELECT ind1, ind1_status, ind2, ind2_status, ind3, ind3_status, ind4, ind4_status, 
                ind5, ind5_status, ind6, ind6_status, ind7, ind7_status 
         FROM zeichnverw WHERE id = :id"
    );
    $stmt_current_indices_data->execute(['id' => $id]);
    $current_db_indices = $stmt_current_indices_data->fetch(PDO::FETCH_ASSOC);

    for ($i = 0; $i < 7; $i++) {
        $db_col_num = $i + 1;
        $index_col_name = "ind" . $db_col_num;
        $status_col_name = "ind" . $db_col_num . "_status";
        $aktueller_index_wert_fuer_slot = null;

        // Index-Wert bestimmen (PV kann ändern, andere nicht - Wert kommt aus POST wenn PV, sonst DB)
        if (isset($submitted_indices_values[$i]) && $submitted_indices_values[$i] !== '' && $loggedInUserDepartment === 'PV') {
            $aktueller_index_wert_fuer_slot = trim($submitted_indices_values[$i]);
        } else if ($current_db_indices && isset($current_db_indices[$index_col_name])) {
            $aktueller_index_wert_fuer_slot = $current_db_indices[$index_col_name];
        }
        $indizes_to_save[$index_col_name] = $aktueller_index_wert_fuer_slot;

        // Status bestimmen
        $neuer_status_fuer_diesen_index = $current_db_indices[$status_col_name] ?? 0; // Standard: alten Status beibehalten

        if (!empty($aktueller_index_wert_fuer_slot)) {
            $index_department_code = '';
            if (isset($index_options_list[$aktueller_index_wert_fuer_slot])) {
                $option_display_text = $index_options_list[$aktueller_index_wert_fuer_slot];
                if (preg_match('/-\s*(PP|PG|PF|PV)\b/i', $option_display_text, $matches_dept)) {
                    $index_department_code = strtoupper($matches_dept[1]);
                }
            }

            // Wenn der Index zur Abteilung des angemeldeten Benutzers gehört,
            // wird sein Status durch die abteilungsspezifische Checkbox bestimmt.
            if ($loggedInUserDepartment === $index_department_code && in_array($loggedInUserDepartment, ['PP', 'PG', 'PF', 'PV'])) {
                $neuer_status_fuer_diesen_index = $department_indices_batch_status_checked ? 1 : 0;
            }
        }
        $indizes_to_save[$status_col_name] = $neuer_status_fuer_diesen_index;

        if (empty($indizes_to_save[$index_col_name])) {
            $indizes_to_save[$index_col_name] = null;
            $indizes_to_save[$status_col_name] = 0; // Wenn kein Indexwert, dann auch kein "erhalten" Status
        }
    }
} else if ($action === 'Speichern') { // Neuer Datensatz
    for ($i = 0; $i < 7; $i++) {
        $db_col_num = $i + 1;
        if (isset($submitted_indices_values[$i]) && $submitted_indices_values[$i] !== '') {
            $ind_val = trim($submitted_indices_values[$i]);
            $indizes_to_save["ind" . $db_col_num] = $ind_val;
            $indizes_to_save["ind" . $db_col_num . "_status"] = 0; // Bei neuen Datensätzen immer 0
        } else {
            $indizes_to_save["ind" . $db_col_num] = null;
            $indizes_to_save["ind" . $db_col_num . "_status"] = 0;
        }
    }
    // $record_status_for_save ist bereits 0 für neue Datensätze
}


$params = []; // Für den catch-Block initialisieren

try {
    if ($action === 'Datensatz löschen' && $id && ctype_digit((string)$id)) {
        // ... (Logik wie zuvor)
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
        $sql = "UPDATE zeichnverw SET
                    sachnr=:sachnr, kurz=:kurz, aez=:aez, dokart=:dokart, teildok=:teildok,
                    ind1=:ind1, ind1_status=:ind1_status, ind2=:ind2, ind2_status=:ind2_status,
                    ind3=:ind3, ind3_status=:ind3_status, ind4=:ind4, ind4_status=:ind4_status,
                    ind5=:ind5, ind5_status=:ind5_status, ind6=:ind6, ind6_status=:ind6_status,
                    ind7=:ind7, ind7_status=:ind7_status,
                    dat=:dat, hinw=:hinw, vers=:vers, record_status=:record_status
                WHERE id = :id";
        $params = [
            'sachnr' => $sachnr,
            'kurz' => $kurz,
            'aez' => $aez,
            'dokart' => $dokart,
            'teildok' => $teildok,
            'ind1' => $indizes_to_save['ind1'],
            'ind1_status' => $indizes_to_save['ind1_status'],
            'ind2' => $indizes_to_save['ind2'],
            'ind2_status' => $indizes_to_save['ind2_status'],
            'ind3' => $indizes_to_save['ind3'],
            'ind3_status' => $indizes_to_save['ind3_status'],
            'ind4' => $indizes_to_save['ind4'],
            'ind4_status' => $indizes_to_save['ind4_status'],
            'ind5' => $indizes_to_save['ind5'],
            'ind5_status' => $indizes_to_save['ind5_status'],
            'ind6' => $indizes_to_save['ind6'],
            'ind6_status' => $indizes_to_save['ind6_status'],
            'ind7' => $indizes_to_save['ind7'],
            'ind7_status' => $indizes_to_save['ind7_status'],
            'dat' => $dat_combined,
            'hinw' => $hinw,
            'vers' => $vers,
            'record_status' => $record_status_for_save,
            'id' => $id
        ];
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        log_action($pdo, 'UPDATE_RECORD', (int)$id, 'zeichnverw', ['sachnr_updated' => $sachnr]);
        $_SESSION['success_message'] = "Änderungen für Datensatz (ID: " . htmlspecialchars($id) . ") erfolgreich gespeichert.";
        header('Location: ds_aend.php?id=' . $id . '&suchsachnr=' . urlencode($suchsachnr_return));
        exit;
    } elseif ($action === 'Speichern') { // Neuer Datensatz
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
            'sachnr' => $sachnr,
            'kurz' => $kurz,
            'aez' => $aez,
            'dokart' => $dokart,
            'teildok' => $teildok,
            'ind1' => $indizes_to_save['ind1'],
            'ind1_status' => $indizes_to_save['ind1_status'],
            'ind2' => $indizes_to_save['ind2'],
            'ind2_status' => $indizes_to_save['ind2_status'],
            'ind3' => $indizes_to_save['ind3'],
            'ind3_status' => $indizes_to_save['ind3_status'],
            'ind4' => $indizes_to_save['ind4'],
            'ind4_status' => $indizes_to_save['ind4_status'],
            'ind5' => $indizes_to_save['ind5'],
            'ind5_status' => $indizes_to_save['ind5_status'],
            'ind6' => $indizes_to_save['ind6'],
            'ind6_status' => $indizes_to_save['ind6_status'],
            'ind7' => $indizes_to_save['ind7'],
            'ind7_status' => $indizes_to_save['ind7_status'],
            'dat' => $dat_combined,
            'hinw' => $hinw,
            'vers' => $vers,
            'record_status' => 0 // Neue Datensätze sind standardmäßig "nicht erhalten" für globalen Status
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
