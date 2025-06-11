<?php
// ds_speich.php (FINALE KORREKTUR v4 - Behebt "Incorrect integer value" Fehler)
require_once 'config.php';
require_once 'db_connect.php';

if (!isset($_SESSION['user_id'])) {
    $_SESSION['error_message'] = "Nicht authentifiziert. Aktion abgebrochen.";
    header('Location: login.php');
    exit;
}

// --- START: ZENTRALE KONFIGURATION & BERECHTIGUNGEN ---
$authorized_user_ids = [4, 10, 3];
$department_map = [
    'PP' => 'PP',
    'PG' => 'PG',
    'PF' => 'PF',
    'PV' => 'PV',
    'VS' => 'Versand',
    'EK' => 'Einkauf'
];
$index_options_list = [
    ''    => 'Keine Auswahl',
    '140' => '140 - LP-Fertigung',
    '141' => '141 - PG/Montage',
    '142' => '142 - PG/Vorfertigung',
    '143' => '143 - Q',
    '145' => '145 - PP',
    '146' => '146 - PF',
    '152' => '152 - PG/Waschen, Lackieren',
    '153' => '153 - PG/Verguss',
    '154' => '154 - Versand',
    '174' => '174 - A',
    '175' => '175 - PP/MOPSY',
    '241' => '241 - PP/Thales',
    '600' => '600 - E, PA'
];
// --- ENDE: ZENTRALE KONFIGURATION & BERECHTIGUNGEN ---

$action = $_POST['action'] ?? null;
$id = $_POST['id'] ?? null;
$suchsachnr_return = trim($_POST['suchsachnr_return'] ?? '');

try {
    // --- BERECHTIGUNGEN DES AKTUELLEN USERS PRÜFEN ---
    $loggedInUserId = $_SESSION['user_id'];
    $loggedInUserDept = strtoupper($_SESSION['user_department'] ?? '');
    $is_special_admin = in_array($loggedInUserId, $authorized_user_ids);
    $can_edit_master_data = ($loggedInUserDept === 'PV') || $is_special_admin;

    if ($action === 'Änderung speichern' && $id && ctype_digit((string)$id)) {

        $stmt_old_record = $pdo->prepare("SELECT * FROM zeichnverw WHERE id = :id");
        $stmt_old_record->execute(['id' => $id]);
        $old_record = $stmt_old_record->fetch(PDO::FETCH_ASSOC);

        if (!$old_record) {
            $_SESSION['error_message'] = "Datensatz zum Aktualisieren nicht gefunden (ID: $id).";
            header('Location: scrolltab.php');
            exit;
        }

        $data_to_save = [];

        if ($can_edit_master_data) {
            $data_to_save['sachnr'] = trim($_POST['sachnr'] ?? $old_record['sachnr']);
            $data_to_save['kurz'] = trim($_POST['kurz'] ?? $old_record['kurz']);
            $data_to_save['aez'] = trim($_POST['aez'] ?? $old_record['aez']);
            $data_to_save['dokart'] = trim($_POST['dokart'] ?? $old_record['dokart']);
            $data_to_save['teildok'] = trim($_POST['teildok'] ?? $old_record['teildok']);
            $data_to_save['hinw'] = trim($_POST['hinw'] ?? $old_record['hinw']);

            $submitted_indices = $_POST['dynamic_indices'] ?? [];
            for ($i = 1; $i <= 7; $i++) {
                $submitted_value = $submitted_indices[$i - 1] ?? $old_record['ind' . $i];
                // KORREKTUR: Leeren String in NULL umwandeln
                $data_to_save['ind' . $i] = ($submitted_value === '') ? null : $submitted_value;
            }
        } else {
            $data_to_save = array_merge($data_to_save, [
                'sachnr' => $old_record['sachnr'],
                'kurz' => $old_record['kurz'],
                'aez' => $old_record['aez'],
                'dokart' => $old_record['dokart'],
                'teildok' => $old_record['teildok'],
                'hinw' => $old_record['hinw']
            ]);
            for ($i = 1; $i <= 7; $i++) {
                $data_to_save['ind' . $i] = $old_record['ind' . $i];
            }
        }

        $data_to_save['dat'] = $old_record['dat'];

        if ($is_special_admin) {
            $data_to_save['record_status'] = isset($_POST['record_status_checkbox']) ? 1 : 0;
        } else {
            $data_to_save['record_status'] = $old_record['record_status'];
        }

        $submitted_dept_checkboxes = $_POST['department_status_checkbox'] ?? [];
        for ($i = 1; $i <= 7; $i++) {
            $current_index_code = $data_to_save['ind' . $i] ?? null;
            $current_status = $old_record['ind' . $i . '_status'];
            if (!empty($current_index_code)) {
                $index_desc = $index_options_list[$current_index_code] ?? '';
                $parts = explode(' - ', $index_desc);
                $dept_name = count($parts) > 1 ? end($parts) : null;
                $dept_code = $dept_name ? array_search($dept_name, $department_map) : null;
                if ($dept_code && isset($submitted_dept_checkboxes[$dept_code])) {
                    if ($loggedInUserDept === $dept_code || $is_special_admin) {
                        $current_status = 1;
                    }
                }
            }
            $data_to_save['ind' . $i . '_status'] = $current_status;
        }

        $sql = "UPDATE zeichnverw SET 
                    sachnr=:sachnr, kurz=:kurz, aez=:aez, dokart=:dokart, teildok=:teildok, hinw=:hinw, dat=:dat, record_status=:record_status,
                    ind1=:ind1, ind1_status=:ind1_status, ind2=:ind2, ind2_status=:ind2_status,
                    ind3=:ind3, ind3_status=:ind3_status, ind4=:ind4, ind4_status=:ind4_status,
                    ind5=:ind5, ind5_status=:ind5_status, ind6=:ind6, ind6_status=:ind6_status,
                    ind7=:ind7, ind7_status=:ind7_status
                WHERE id=:id";

        $params_for_sql = $data_to_save;
        $params_for_sql['id'] = $id;

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params_for_sql);

        // --- START: NEUE LOGIK ZUM ERKENNEN VON ÄNDERUNGEN ---

        // 1. Ein leeres Array für die Änderungen erstellen.
        $changes = [];

        // 2. Alle gespeicherten Felder durchgehen und mit dem alten Wert vergleichen.
        foreach ($data_to_save as $key => $new_value) {
            // Prüfen, ob sich der Wert vom alten Datensatz unterscheidet.
            // Wir ignorieren den Vergleich für das Datum, da es sich immer ändert.
            if ($key !== 'dat' && array_key_exists($key, $old_record) && $old_record[$key] != $new_value) {
                $changes[$key] = [
                    'alt' => $old_record[$key],
                    'neu' => $new_value
                ];
            }
        }

        // 3. Nur wenn es tatsächliche Änderungen gab, wird ein Log-Eintrag erstellt.
        if (!empty($changes)) {
            log_action($pdo, 'UPDATE_RECORD', (int)$id, 'zeichnverw', $changes);
        }
        // --- ENDE: NEUE LOGIK ---
        $_SESSION['success_message'] = "Änderungen für Datensatz (ID: " . htmlspecialchars($id) . ") erfolgreich gespeichert.";
        header('Location: ds_aend.php?id=' . $id . '&suchsachnr=' . urlencode($suchsachnr_return));
        exit;
    } elseif ($action === 'Speichern') {
        $params_for_sql = [
            'sachnr' => trim($_POST['sachnr'] ?? ''),
            'kurz' => trim($_POST['kurz'] ?? ''),
            'aez' => trim($_POST['aez'] ?? ''),
            'dokart' => trim($_POST['dokart'] ?? ''),
            'teildok' => trim($_POST['teildok'] ?? ''),
            'hinw' => trim($_POST['hinw'] ?? ''),
            'dat' => date('Y-m-d'),
            'record_status' => 0
        ];
        $submitted_indices = $_POST['dynamic_indices'] ?? [];
        for ($i = 1; $i <= 7; $i++) {
            $submitted_value = $submitted_indices[$i - 1] ?? null;
            // KORREKTUR: Leeren String in NULL umwandeln
            $params_for_sql['ind' . $i] = ($submitted_value === '') ? null : $submitted_value;
            $params_for_sql['ind' . $i . '_status'] = 0;
        }

        $sql = "INSERT INTO zeichnverw (sachnr, kurz, aez, dokart, teildok, hinw, dat, record_status, ind1, ind1_status, ind2, ind2_status, ind3, ind3_status, ind4, ind4_status, ind5, ind5_status, ind6, ind6_status, ind7, ind7_status) 
                VALUES (:sachnr, :kurz, :aez, :dokart, :teildok, :hinw, :dat, :record_status, :ind1, :ind1_status, :ind2, :ind2_status, :ind3, :ind3_status, :ind4, :ind4_status, :ind5, :ind5_status, :ind6, :ind6_status, :ind7, :ind7_status)";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params_for_sql);
        $new_id = $pdo->lastInsertId();
        log_action($pdo, 'CREATE_RECORD', (int)$new_id, 'zeichnverw', $params_for_sql);
        $_SESSION['success_message'] = "Neuer Datensatz erfolgreich gespeichert (Neue ID: " . htmlspecialchars($new_id) . ").";
        header('Location: scrolltab.php?suchsachnr=' . urlencode($params_for_sql['sachnr']));
        exit;
        // --- START: NEUE "SOFT DELETE" LOGIK ---
    } elseif ($action === 'Zeichnung entfernen' && $id && ctype_digit((string)$id)) {
        // Prüfen, ob der Benutzer die Berechtigung hat.
        if (!$can_edit_master_data) {
            $_SESSION['error_message'] = "Keine Berechtigung zum Entfernen.";
            header('Location: scrolltab.php');
            exit;
        }

        // 1. Hole alle Daten des Datensatzes, bevor wir ihn ändern, für das Log.
        $stmt_old_record = $pdo->prepare("SELECT * FROM zeichnverw WHERE id = :id");
        $stmt_old_record->execute(['id' => $id]);
        $record_to_archive = $stmt_old_record->fetch(PDO::FETCH_ASSOC);

        if ($record_to_archive) {
            // 2. Führe ein UPDATE statt eines DELETE aus. Setze den Status auf 0 (inaktiv/entfernt).
            $stmt_soft_delete = $pdo->prepare("UPDATE zeichnverw SET record_status = 2 WHERE id = :id");
            $stmt_soft_delete->execute(['id' => $id]);

            // 3. Logge die Aktion und speichere den kompletten Datensatz in den Details.
            log_action($pdo, 'RECORD_REMOVED', (int)$id, 'zeichnverw', $record_to_archive);

            $_SESSION['success_message'] = "Zeichnung (ID: " . htmlspecialchars($id) . ") erfolgreich entfernt und archiviert.";
        } else {
            $_SESSION['error_message'] = "Datensatz zum Entfernen nicht gefunden.";
        }

        header('Location: scrolltab.php?suchsachnr=' . urlencode($suchsachnr_return));
        exit;
        // --- ENDE: NEUE "SOFT DELETE" LOGIK ---
    } else {
        $_SESSION['error_message'] = "Unbekannte oder ungültige Aktion.";
        header('Location: scrolltab.php');
        exit;
    }
} catch (PDOException $e) {
    error_log("Database operation error in ds_speich.php: " . $e->getMessage());
    try {
        log_action($pdo, 'DB_ERROR', ($id ?? null), 'zeichnverw', ['error_code' => $e->getCode(), 'error_message' => $e->getMessage(), 'action' => $action]);
    } catch (PDOException $log_e) {
        error_log("Failed to write to action_log: " . $log_e->getMessage());
    }
    $_SESSION['error_message'] = "Ein Datenbankfehler ist aufgetreten. Details wurden im Server-Log protokolliert.";
    header('Location: scrolltab.php?suchsachnr=' . urlencode($suchsachnr_return));
    exit;
}
