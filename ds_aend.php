<?php
// ds_aend.php (mit Bootstrap und modernem Layout)
require_once 'config.php';
require_once 'db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$loggedInUsername = $_SESSION['username'] ?? '';
$loggedInUserDepartment = $_SESSION['user_department'] ?? ''; // Abteilung des angemeldeten Benutzers


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


$kurz_options_list = [
    '' => 'Keine Auswahl',
    '(4)' => '(4) - Zeichnung DIN A4 (PF, PG, PP, Technologie)',
    '(3)' => '(3) - Zeichnung DIN A3 (PF, PG, PP, Technologie)',
    '(2)' => '(2) - Zeichnung DIN A2 (PF, PG, PP, Technologie)',
    '(1)' => '(1) - Zeichnung DIN A1 (PF, PG, PP, Technologie)',
    '(0)' => '(0) - Zeichnung DIN A0 (PF, PG, PP, Technologie)',
    '(SP)' => '(SP) - Schaltplan (PP, Technologie)',
    '(TB)' => '(TB) - Technologisches Beiblatt (PF, Technologie)',
    '(Assy TOP)' => '(Assy TOP) - Leiterzugseite Top (PF, Technologie)', // Beachten: Länge 9
    '(Assy BOT)' => '(Assy BOT) - Leiterzugseite Bottom (PF, Technologie)', // Beachten: Länge 10
    '(PS)' => '(PS) - Prüfspezifikation (PP, Technologie)', // Einmal aufgenommen
    '(TS)' => '(TS) - Testspezifikation (PP, Technologie)',
    '(PA)' => '(PA) - Prüfanweisung (PP, Technologie)',
    '(AA)' => '(AA) - Arbeitsanweisung (PF, PG, PP, Technologie)',
    '(AW)' => '(AW) - Arbeitsanweisung (PF, PG, PP, Technologie)', // Unterscheidet sich dies von AA? Falls nicht, ggf. zusammenfassen.
    '(AU)' => '(AU) - Arbeitsunterweisung (PF, PG, PP, Technologie)',
    '(UA)' => '(UA) - Umbauanleitung (PF, PG, PP, Technologie)',
    '(MA)' => '(MA) - Montageanweisung (PG, Technologie)',
    '(VA)' => '(VA) - Verpackungsanweisung (PF, PG, PP, Versand, Technologie)',
    '(LA)' => '(LA) - Lackieranweisung (PG, Technologie)',
    // '(PS)' => '(PS) - Prüfspezifikation (PP, Technologie)', // Zweites Vorkommen, ausgelassen
    '(KA)' => '(KA) - Klebeanweisung (PG, Technologie)',
    '(AGL)' => '(AGL) - Abgleichsliste (PF, PG, PP, Technologie)',
    '(FW)' => '(FW) - Firmware/ Software (PP, Technologie)',
    '(Etik)' => '(Etik) - Etikettenzeichnung (PF, PP, Wareneingang, Technologie)',
    'ZUSB' => 'ZUSB', // Keine Beschreibung gegeben, Kürzel als Text
    'ZUST' => 'ZUST',
    'ZUS' => 'ZUS'
];



$page_title = "Datensatz bearbeiten";
$id_to_edit = $_GET['id'] ?? null;
// suchsachnr_param für den Suchkontext der oberen Tabelle und für den "Zurück"-Link
$suchsachnr_param = trim($_REQUEST['suchsachnr'] ?? ''); // Von Link oder Formular-Neuladen

$record = null;
$sachnr_val = $kurz_val = $aez_val = $dokart_val = $teildok_val = $hinw_val = '';
$ind1_val = $ind2_val = $ind3_val = $ind4_val = $ind5_val = $ind6_val = $ind7_val = '';
$t_dat_val = $m_dat_val = $j_dat_val = '';

if (!$id_to_edit || !ctype_digit((string)$id_to_edit)) {
    $_SESSION['error_message'] = "Ungültige oder fehlende ID zum Ändern.";
    header('Location: scrolltab.php?suchsachnr=' . urlencode($suchsachnr_param));
    exit;
}

try {
    $stmt_fetch = $pdo->prepare("SELECT * FROM zeichnverw WHERE id = :id"); // [aus ds_aend.php]
    $stmt_fetch->execute(['id' => $id_to_edit]); // [aus ds_aend.php]
    $record = $stmt_fetch->fetch(); // [aus ds_aend.php]

    if ($record) {
        $sachnr_val = $record['sachnr']; // [aus ds_aend.php]
        $kurz_val = $record['kurz']; // [aus ds_aend.php]
        $aez_val = $record['aez']; // [aus ds_aend.php]
        $dokart_val = $record['dokart']; // [aus ds_aend.php]
        $teildok_val = $record['teildok']; // [aus ds_aend.php]
        $hinw_val = $record['hinw']; // [aus ds_aend.php]

        // Initialisierung der Index-Werte
        for ($i = 1; $i <= 7; $i++) {
            ${"ind" . $i . "_val"} = $record["ind$i"] ?? null; // [aus ds_aend.php], ggf. null als Default
        }

        // NEU bzw. KORREKTE PLATZIERUNG: Initialisierung der Index-Status-Werte
        for ($i_loop = 1; $i_loop <= 7; $i_loop++) {
            ${"ind" . $i_loop . "_status_val"} = $record["ind" . $i_loop . "_status"] ?? 0; // Default auf 0 (nicht erhalten)
        }

        // Datumsverarbeitung (bereits vorhanden)
        if ($record['dat'] && $record['dat'] !== '0000-00-00') { // [aus ds_aend.php]
            try {
                $date_obj = new DateTime($record['dat']); // [aus ds_aend.php]
                $t_dat_val = $date_obj->format('d'); // [aus ds_aend.php]
                $m_dat_val = $date_obj->format('m'); // [aus ds_aend.php]
                $j_dat_val = $date_obj->format('Y'); // [aus ds_aend.php]
            } catch (Exception $e) { /* Datum ungültig, Felder bleiben leer */
            } // [aus ds_aend.php]
        } else {
            $t_dat_val = $m_dat_val = $j_dat_val = ''; // [aus ds_aend.php]
        }
    } else {
        $_SESSION['error_message'] = "Datensatz mit ID " . htmlspecialchars($id_to_edit) . " nicht gefunden."; // [aus ds_aend.php]
        header('Location: scrolltab.php?suchsachnr=' . urlencode($suchsachnr_param)); // [aus ds_aend.php]
        exit; // [aus ds_aend.php]
    }
} catch (PDOException $e) {
    error_log("ds_aend Fetch Error: " . $e->getMessage()); // [aus ds_aend.php]
    $_SESSION['error_message'] = "Fehler beim Laden des Datensatzes."; // [aus ds_aend.php]
    header('Location: scrolltab.php?suchsachnr=' . urlencode($suchsachnr_param)); // [aus ds_aend.php]
    exit; // [aus ds_aend.php]
}


// Für die Top-Tabelle (Suchergebnisvorschau)
// Verwende suchsachnr_param für Konsistenz, es sei denn, ein spezifisches Formularfeld wird dafür verwendet
$suchsachnr_display_filter = trim($_REQUEST['suchsachnr_preview_filter'] ?? $suchsachnr_param);
$krit_display = !empty($suchsachnr_display_filter) ? 'sachnr' : 'id DESC';

try {
    $sql_display = "SELECT id, sachnr, kurz, aez, dokart, teildok, 
                           ind1, ind1_status, 
                           ind2, ind2_status, 
                           ind3, ind3_status, 
                           ind4, ind4_status, 
                           ind5, ind5_status, 
                           ind6, ind6_status, 
                           ind7, ind7_status, 
                           dat, hinw, record_status
                    FROM zeichnverw 
                    WHERE sachnr LIKE :suchsachnr_display_filter 
                    ORDER BY $krit_display 
                    LIMIT 50"; // Weniger Zeilen für die Vorschau
    $stmt_display = $pdo->prepare($sql_display);
    $stmt_display->execute(['suchsachnr_display_filter' => $suchsachnr_display_filter . '%']);
    $results_display = $stmt_display->fetchAll();
    $num_rows_display = count($results_display);
} catch (PDOException $e) {
    error_log("ds_aend Preview SQL Error: " . $e->getMessage());
    // Nicht kritisch, Formular unten wird trotzdem angezeigt
    $results_display = [];
    $num_rows_display = 0;
}

?>
<!DOCTYPE html>
<html lang="de" data-bs-theme="dark">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title) . " - ID: " . htmlspecialchars($id_to_edit) . " - " . htmlspecialchars(APP_TITLE); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Maven+Pro:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="custom_styles.css" rel="stylesheet">
</head>

<body>
    <header class="app-header">
        <div class="logo-left"><img src="EPSa_logo_group_diap.svg" alt="EPSa Logo"></div>
        <h1 class="app-title">
            <a href="index.php" style="color: inherit; text-decoration: none;" title="Zur Startseite">
                <?php echo htmlspecialchars(APP_TITLE); ?>
            </a>
        </h1>
        <div class="user-info">
            Angemeldet als: <strong><?php echo htmlspecialchars($_SESSION['username']); ?></strong>
            <a href="logout.php" style="margin-left: 0.75rem;">Logout</a>
        </div>
        <div class="logo-right"><img src="SeroEMSgroup_Logo_vc_DIAP.svg" alt="SeroEMS Logo"></div>
    </header>

    <div class="container-fluid mt-3">
        <div class="app-main-content">

            <?php if (isset($_SESSION['error_message'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php echo htmlspecialchars($_SESSION['error_message']);
                    unset($_SESSION['error_message']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            <?php if (isset($_SESSION['success_message'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php echo htmlspecialchars($_SESSION['success_message']);
                    unset($_SESSION['success_message']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <section class="form-section mb-4">
                <h3 class="mb-3">Kontext-Vorschau <small class="text-body-secondary fw-normal fs-6">(gefiltert nach aktueller Sachnr.)</small></h3>
                <form name="form_preview_filter" method="get" action="ds_aend.php" class="row g-3 align-items-center">
                    <input type="hidden" name="id" value="<?php echo htmlspecialchars($id_to_edit); ?>">
                    <div class="col-md-auto">
                        <label for="suchsachnr_preview_filter_input" class="col-form-label">Sachnummer-Filter für Vorschau:</label>
                    </div>
                    <div class="col-md-4">
                        <input id="suchsachnr_preview_filter_input" name="suchsachnr_preview_filter" type="text" class="form-control form-control-sm" value="<?php echo htmlspecialchars($suchsachnr_display_filter); ?>" placeholder="Teil der Sachnummer...">
                    </div>
                    <div class="col-md-auto">
                        <button type="submit" class="btn btn-secondary btn-sm">Vorschau Aktualisieren</button>
                    </div>
                    <div class="col-md text-md-end">
                        <small class="text-body-secondary"><?php echo $num_rows_display; ?> Datensätze in Vorschau</small>
                    </div>
                </form>
                <div class="table-responsive-custom-height mt-3" style="max-height: 200px;">
                    <table class="table table-striped table-hover table-sm table-bordered">
                        <thead class="table-dark">
                            <tr>
                                <th style="width: 20%;">Sachnummer</th>
                                <th style="width: 8%;">Kurz</th>
                                <th style="width: 8%;">ÄZ</th>
                                <th style="width: 10%;">Dok.-Art</th>
                                <th style="width: 10%;">Teil-Dok.</th>
                                <th style="width: 20%;">Indizes</th>
                                <th style="width: 10%;">Datum</th>
                                <th>Hinweis</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $results = $results_display; // Variable für tabinh.php
                            $suchsachnr = $suchsachnr_display_filter; // Suchkontext für Links in tabinh
                            include("tabinh.php");
                            ?>
                        </tbody>
                    </table>
                </div>
            </section>

            <hr class="my-4">

            <?php if ($record): ?>
                <section class="form-section">


                    <form action="ds_speich.php" method="post" name="form_edit_record">
                        <input type="hidden" name="id" value="<?php echo htmlspecialchars($id_to_edit); ?>">
                        <input type="hidden" name="action" value="Änderung speichern">
                        <input type="hidden" name="suchsachnr_return" value="<?php echo htmlspecialchars($suchsachnr_param); ?>">

                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h3 class="mb-0">Bearbeite Datensatz ID: <span class="text-info"><?php echo htmlspecialchars($id_to_edit); ?></span></h3>
                            <div class="form-check fs-5">
                                <input class="form-check-input" type="checkbox" value="1"
                                    id="record_status_checkbox_main" name="record_status_checkbox"
                                    <?php if ($record['record_status'] ?? 0) echo 'checked'; ?>
                                    <?php if (strtoupper($loggedInUserDepartment ?? '') !== 'PG') echo 'disabled'; ?>>
                                <label class="form-check-label fw-bold" for="record_status_checkbox_main">
                                    Zeichnung erhalten <?php if (strtoupper($loggedInUserDepartment ?? '') !== 'PG') echo '<small class="text-muted">(Nur Abt. PG)</small>'; ?>
                                </label>
                            </div>
                        </div>

                        <div class="row gx-2 align-items-end mb-3">
                            <div class="col-auto">
                                <label for="sachnr_edit" class="form-label">Sachnr.:</label>
                                <input id="sachnr_edit" name="sachnr" type="text" class="form-control form-control-sm" style="width: 250px;" value="<?php echo htmlspecialchars($sachnr_val); ?>" maxlength="50">
                            </div>
                            <div class="col-auto">
                                <label class="form-label" style="visibility: hidden; display: block;">&nbsp;</label>
                                <button type="button" id="add_kurz_btn" class="btn btn-success btn-sm"><i class="bi bi-plus-lg"></i> Kürzel</button>
                            </div>
                            <div class="col">
                                <label class="form-label">Kurz:</label>
                                <div id="kurz_selectors_container">
                                    <?php /* PHP-Logik zum Generieren der initialen Kurz-Dropdowns hier (wie zuvor) */
                                    $kurz_werte_array = (!empty($kurz_val)) ? explode(' ', trim($kurz_val)) : ['']; // Mind. 1 Dropdown
                                    foreach ($kurz_werte_array as $k_idx => $k_wert):
                                        $is_last_kurz = $k_idx === count($kurz_werte_array) - 1;
                                    ?>
                                        <div class="input-group kurz-selector-group <?php echo $k_idx > 0 ? 'mt-1' : ''; ?> w-100">
                                            <select class="form-select form-select-sm kurz-select">
                                                <?php foreach ($kurz_options_list as $value => $display_text): ?>
                                                    <option value="<?php echo htmlspecialchars($value); ?>" <?php if ($k_wert === $value) echo 'selected'; ?>><?php echo htmlspecialchars($display_text); ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                            <button type="button" class="btn btn-danger btn-sm remove-kurz-btn" style="<?php echo (count($kurz_werte_array) > 1) ? '' : 'display: none;'; ?>">-</button>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                <input type="hidden" name="kurz" id="kurz_combined_hidden" value="<?php echo htmlspecialchars($kurz_val); ?>">
                            </div>
                        </div>

                        <div class="row gx-2 align-items-end mb-3">
                            <div class="col-auto">
                                <label for="aez_edit" class="form-label">ÄZ/Vers.:</label>
                                <input id="aez_edit" name="aez" type="text" class="form-control form-control-sm" style="width: 150px;" value="<?php echo htmlspecialchars($aez_val); ?>" maxlength="9">
                            </div>
                            <div class="col-auto">
                                <label for="dokart_edit" class="form-label">Dok.-Art:</label>
                                <input id="dokart_edit" name="dokart" type="text" class="form-control form-control-sm" style="width: 150px;" value="<?php echo htmlspecialchars($dokart_val); ?>" maxlength="5">
                            </div>
                            <div class="col-auto">
                                <label for="teildok_edit" class="form-label">Teil-Dok.:</label>
                                <input id="teildok_edit" name="teildok" type="text" class="form-control form-control-sm" style="width: 150px;" value="<?php echo htmlspecialchars($teildok_val); ?>" maxlength="5">
                            </div>
                        </div>

                        <div class="row gx-3 align-items-start mb-3">
                            <div class="col-md-7">
                                <label class="form-label d-block mb-1">Indizes (max. 7):</label>
                                <div id="indices_selectors_container">
                                    <?php
                                    $is_pv_user = (strtoupper($loggedInUserDepartment ?? '') === 'PV');
                                    // $is_admin_user = (strtolower($loggedInUsername) === 'admin'); // Annahme für Admin-Logik

                                    $aktive_indizes_fuer_form = [];
                                    $hat_aktive_indizes = false;
                                    for ($i = 0; $i < 7; $i++) {
                                        $db_col_num = $i + 1;
                                        $index_wert_db = ${"ind" . $db_col_num . "_val"} ?? '';
                                        $index_status_db = ${"ind" . $db_col_num . "_status_val"} ?? 0;
                                        if ($index_wert_db !== '' && $index_wert_db != 0) {
                                            $aktive_indizes_fuer_form[$i] = ['value' => $index_wert_db, 'status' => $index_status_db];
                                            $hat_aktive_indizes = true;
                                        }
                                    }
                                    // Wenn keine aktiven Indizes da sind, zeige eine leere Gruppe als Startpunkt
                                    if (!$hat_aktive_indizes) {
                                        $aktive_indizes_fuer_form[0] = ['value' => '', 'status' => 0];
                                    }

                                    $form_idx_key = 0; // Fortlaufender Key für Formular-Elemente
                                    foreach ($aktive_indizes_fuer_form as $index_data_key => $index_data) { // $index_data_key ist der ursprüngliche Slot 0-6 falls benötigt
                                        $idx_wert = $index_data['value'];
                                        $idx_status = $index_data['status'];
                                        $unique_id_edit = 'idx_edit_' . $form_idx_key . '_' . substr(md5(uniqid(rand(), true)), 0, 5);

                                        $can_edit_this_index_status_permission = false; // Zurücksetzen für jeden Index
                                        if (!empty($idx_wert) && isset($index_options_list[$idx_wert])) {
                                            $option_display_text = $index_options_list[$idx_wert];
                                            if (preg_match('/-\s*(PP|PG|PF|PV)\b/i', $option_display_text, $matches)) {
                                                $index_department_code = strtoupper($matches[1]);
                                                if (strtoupper($loggedInUserDepartment ?? '') === $index_department_code) {
                                                    $can_edit_this_index_status_permission = true;
                                                }
                                            }
                                        }

                                        // Admin-Overrides oder spezifische Logik:
                                        // Wenn Admin, aber nicht PV: keine Bearbeitung der Indexwerte oder Hinzufügen/Entfernen
                                        // Status-Checkbox-Editierbarkeit hängt von $can_edit_this_index_status_permission ab
                                        $disable_select = (!$is_pv_user && $loggedInUsername === 'admin'); // Beispiel: Admin (nicht PV) kann Wert nicht ändern
                                        $disable_checkbox = $disable_select || !$can_edit_this_index_status_permission; // Checkbox auch deaktivieren wenn Select deaktiviert ist oder keine spez. Berechtigung
                                        $hide_remove_button = $disable_select || (count($aktive_indizes_fuer_form) <= 1 && !$hat_aktive_indizes);


                                        $select_classes = "form-select form-select-sm index-select";
                                        if (!empty($idx_wert) && !$idx_status) {
                                            $select_classes .= " status-not-received";
                                        } else {
                                            $select_classes .= " status-received";
                                        }
                                    ?>
                                        <div class="input-group mb-1 index-selector-group align-items-center">
                                            <select name="dynamic_indices[<?php echo $form_idx_key; ?>]" class="<?php echo $select_classes; ?>" style="width: 100px; min-width: 100px; flex-grow: 0;" <?php if ($disable_select) echo 'disabled'; ?>>
                                                <?php foreach ($index_options_list as $value_opt => $display_text_opt): ?>
                                                    <option value="<?php echo htmlspecialchars($value_opt); ?>" <?php if ((string)$idx_wert === (string)$value_opt) echo 'selected'; ?>><?php echo htmlspecialchars($display_text_opt); ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                            <div class="form-check ms-2">
                                                <input type="hidden" name="dynamic_indices_status[<?php echo $form_idx_key; ?>]" value="0">
                                                <input class="form-check-input index-status-checkbox" type="checkbox" name="dynamic_indices_status[<?php echo $form_idx_key; ?>]" value="1" id="<?php echo $unique_id_edit; ?>" <?php if ($idx_status) echo 'checked'; ?> <?php if ($disable_checkbox) echo 'disabled'; ?>>
                                                <label class="form-check-label small" for="<?php echo $unique_id_edit; ?>">Erhalten?</label>
                                            </div>
                                            <button type="button" class="btn btn-danger btn-sm remove-index-btn ms-2" style="<?php echo $hide_remove_button ? 'display:none;' : ''; ?>">-</button>
                                        </div>
                                    <?php $form_idx_key++;
                                    } // Ende foreach $aktive_indizes_fuer_form 
                                    ?>
                                </div>
                                <?php if ($is_pv_user): ?>
                                    <button type="button" id="add_index_btn" class="btn btn-success btn-sm mt-1"><i class="bi bi-plus-lg"></i> Index hinzufügen</button>
                                <?php endif; ?>
                            </div>

                            <div class="col-md-5">
                                <label class="form-label d-block mb-1">Datum:</label>
                                <div class="d-flex align-items-center">
                                    <input name="t_dat_display" type="text" class="form-control form-control-sm" placeholder="TT" style="width: 65px;" value="<?php echo htmlspecialchars($t_dat_val); ?>" readonly>
                                    <span class="ps-1 pe-1 text-center">.</span>
                                    <input name="m_dat_display" type="text" class="form-control form-control-sm" placeholder="MM" style="width: 65px;" value="<?php echo htmlspecialchars($m_dat_val); ?>" readonly>
                                    <span class="ps-1 pe-1 text-center">.</span>
                                    <input name="y_dat_display" type="text" class="form-control form-control-sm" placeholder="JJJJ" style="width: 85px;" value="<?php echo htmlspecialchars($j_dat_val); ?>" readonly>
                                </div>
                            </div>
                        </div>

                        <div class="row gx-2 mb-3">
                            <div class="col-12">
                                <label for="hinw_edit" class="form-label">Hinweis:</label>
                                <input id="hinw_edit" name="hinw" type="text" class="form-control form-control-sm" value="<?php echo htmlspecialchars($hinw_val); ?>" maxlength="50">
                            </div>
                        </div>

                        <div class="mt-4 d-flex gap-2">
                            <button type="submit" class="btn btn-success"><i class="bi bi-save"></i> Änderungen speichern</button>
                            <button type="submit" class="btn btn-danger" name="action" value="Datensatz löschen" onclick="return confirm('Sind Sie sicher, dass Sie diesen Datensatz (ID: <?php echo htmlspecialchars($id_to_edit); ?>) endgültig löschen möchten?');"><i class="bi bi-trash"></i> Datensatz löschen</button>
                            <a href="scrolltab.php?suchsachnr=<?php echo htmlspecialchars($suchsachnr_param); ?>" class="btn btn-secondary"><i class="bi bi-x-circle"></i> Abbrechen</a>
                        </div>
                    </form>
                </section>
            <?php else: ?>
                <div class="alert alert-warning">Der Datensatz konnte nicht geladen werden.</div>
            <?php endif; ?>
        </div>

        <footer class="text-center text-body-secondary py-3 mt-3">
            <small>&copy; <?php echo date("Y"); ?> Ihre Firma. Alle Rechte vorbehalten.</small>
        </footer>

        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                // --- KURZ-FELDER LOGIK (wie von Ihnen bereitgestellt, scheint zu funktionieren) ---
                const kurzContainer = document.getElementById('kurz_selectors_container');
                const addKurzButton = document.getElementById('add_kurz_btn');
                const hiddenCombinedKurzInput = document.getElementById('kurz_combined_hidden');

                function updateCombinedKurzField() {
                    if (!kurzContainer || !hiddenCombinedKurzInput) return;
                    const selectedValues = [];
                    kurzContainer.querySelectorAll('.kurz-select').forEach(function(select) {
                        if (select.value) {
                            selectedValues.push(select.value);
                        }
                    });
                    hiddenCombinedKurzInput.value = selectedValues.join(' ');
                    toggleKurzRemoveButtonVisibility();
                }

                function toggleKurzRemoveButtonVisibility() {
                    if (!kurzContainer) return;
                    const allKurzGroups = kurzContainer.querySelectorAll('.kurz-selector-group');
                    allKurzGroups.forEach((group) => {
                        const removeBtn = group.querySelector('.remove-kurz-btn');
                        if (removeBtn) {
                            removeBtn.style.display = (allKurzGroups.length > 1) ? 'inline-block' : 'none';
                        }
                    });
                }

                if (kurzContainer && addKurzButton) {
                    // Event-Listener für Änderungen an den Select-Boxen (delegiert)
                    kurzContainer.addEventListener('change', function(event) {
                        if (event.target.classList.contains('kurz-select')) {
                            updateCombinedKurzField();
                        }
                    });

                    addKurzButton.addEventListener('click', function() {
                        const firstKurzGroup = kurzContainer.querySelector('.kurz-selector-group');
                        if (!firstKurzGroup) return;

                        const newKurzGroup = firstKurzGroup.cloneNode(true);
                        newKurzGroup.querySelector('.kurz-select').value = ''; // Auswahl zurücksetzen

                        const removeKurzBtn = newKurzGroup.querySelector('.remove-kurz-btn');
                        if (removeKurzBtn) {
                            removeKurzBtn.style.display = 'inline-block'; // Für neue immer anzeigen
                            removeKurzBtn.addEventListener('click', function() {
                                newKurzGroup.remove();
                                updateCombinedKurzField();
                            }, {
                                once: true
                            }); // Listener nur einmal binden, da geklont
                        }
                        kurzContainer.appendChild(newKurzGroup);
                        updateCombinedKurzField(); // Aktualisiert auch Button-Sichtbarkeit
                    });
                }
                if (kurzContainer) { // Für initial geladene Elemente in ds_aend.php
                    kurzContainer.querySelectorAll('.kurz-selector-group').forEach(group => {
                        const removeBtn = group.querySelector('.remove-kurz-btn');
                        if (removeBtn && !removeBtn.dataset.listenerAttached) { // Nur wenn noch kein Listener
                            removeBtn.addEventListener('click', function() {
                                if (kurzContainer.querySelectorAll('.kurz-selector-group').length > 1) {
                                    group.remove();
                                    updateCombinedKurzField();
                                }
                            });
                            removeBtn.dataset.listenerAttached = 'true';
                        }
                    });
                }
                if (typeof updateCombinedKurzField === "function") updateCombinedKurzField();


                // --- INDIZES-FELDER LOGIK (STARK ÜBERARBEITET) ---
                const indicesContainer = document.getElementById('indices_selectors_container');
                const addIndexButton = document.getElementById('add_index_btn');
                const MAX_INDICES = 7;

                function generateUniqueId(prefix = 'id_') {
                    return prefix + Date.now().toString(36) + Math.random().toString(36).substr(2, 5);
                }

                function applyIndexStyling(groupElement) {
                    const select = groupElement.querySelector('.index-select');
                    const checkbox = groupElement.querySelector('.index-status-checkbox');
                    if (!select || !checkbox) return;

                    if (select.value && select.value !== '' && !checkbox.checked) {
                        select.classList.add('status-not-received');
                        select.classList.remove('status-received');
                    } else {
                        select.classList.remove('status-not-received');
                        select.classList.add('status-received');
                    }
                }

                function reassignIndexKeys() {
                    if (!indicesContainer) return;
                    const allIndexGroups = indicesContainer.querySelectorAll('.index-selector-group');
                    allIndexGroups.forEach((group, newKey) => {
                        const select = group.querySelector('.index-select');
                        const hiddenStatus = group.querySelector('input[type="hidden"]');
                        const checkbox = group.querySelector('.index-status-checkbox');
                        const label = group.querySelector('.form-check-label');
                        const uniqueId = generateUniqueId('idx_dyn_' + newKey + '_');

                        if (select) select.name = `dynamic_indices[${newKey}]`;
                        if (hiddenStatus) hiddenStatus.name = `dynamic_indices_status[${newKey}]`;
                        if (checkbox) {
                            checkbox.name = `dynamic_indices_status[${newKey}]`;
                            checkbox.id = uniqueId;
                        }
                        if (label) label.setAttribute('for', uniqueId);
                    });
                }

                function updateIndexControlsAndStyles() {
                    if (!indicesContainer) return;
                    const allIndexGroups = indicesContainer.querySelectorAll('.index-selector-group');

                    allIndexGroups.forEach((group) => {
                        const removeBtn = group.querySelector('.remove-index-btn');
                        if (removeBtn) {
                            removeBtn.style.display = (allIndexGroups.length > 1) ? 'inline-block' : 'none';
                        }
                        applyIndexStyling(group);
                    });

                    if (addIndexButton) {
                        addIndexButton.disabled = (allIndexGroups.length >= MAX_INDICES);
                        addIndexButton.classList.toggle('disabled', allIndexGroups.length >= MAX_INDICES);
                    }
                    reassignIndexKeys(); // Stelle sicher, dass die Keys nach dem Entfernen sequentiell sind
                }


                function setupEventListenersForIndexGroup(groupElement) {
                    const select = groupElement.querySelector('.index-select');
                    const checkbox = groupElement.querySelector('.index-status-checkbox');
                    const removeBtn = groupElement.querySelector('.remove-index-btn');

                    if (select) select.addEventListener('change', function() {
                        applyIndexStyling(groupElement);
                    });
                    if (checkbox) checkbox.addEventListener('change', function() {
                        applyIndexStyling(groupElement);
                    });

                    if (removeBtn) {
                        // Entferne alte Listener, um Duplikate zu vermeiden (wichtig beim Klonen)
                        const newRemoveBtn = removeBtn.cloneNode(true);
                        removeBtn.parentNode.replaceChild(newRemoveBtn, removeBtn);
                        newRemoveBtn.addEventListener('click', function() {
                            groupElement.remove();
                            updateIndexControlsAndStyles();
                        });
                    }
                }

                if (addIndexButton && indicesContainer) {
                    addIndexButton.addEventListener('click', function() {
                        const allIndexGroups = indicesContainer.querySelectorAll('.index-selector-group');
                        if (allIndexGroups.length < MAX_INDICES) {
                            const templateNode = indicesContainer.querySelector('.index-selector-group');
                            if (!templateNode) {
                                console.error("Index template node not found!");
                                return;
                            }
                            const newIndexGroup = templateNode.cloneNode(true);

                            const newSelect = newIndexGroup.querySelector('.index-select');
                            const newCheckbox = newIndexGroup.querySelector('.index-status-checkbox');

                            if (newSelect) newSelect.value = '';
                            if (newCheckbox) newCheckbox.checked = false;

                            // Schlüssel und IDs werden in reassignIndexKeys und setupEventListenersForIndexGroup gesetzt
                            indicesContainer.appendChild(newIndexGroup);
                            // Der neue Key wird in reassignIndexKeys nach dem Append bestimmt
                            reassignIndexKeys(); // Keys neu zuweisen, um Lücken zu füllen
                            setupEventListenersForIndexGroup(newIndexGroup); // Listener für neue Gruppe
                            applyIndexStyling(newIndexGroup);
                            updateIndexControlsAndStyles();
                        }
                    });
                }

                // Initial Event Listeners und Styling für PHP-gerenderte Elemente (besonders in ds_aend.php)
                if (indicesContainer) {
                    indicesContainer.querySelectorAll('.index-selector-group').forEach((group) => {
                        setupEventListenersForIndexGroup(group); // Setzt auch korrekte Namen durch reassign in updateControls
                    });
                    updateIndexControlsAndStyles(); // Initialisiert alles korrekt
                }
            });
        </script>
</body>

</html>