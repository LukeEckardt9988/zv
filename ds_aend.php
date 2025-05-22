<?php
// ds_aend.php (Version mit zentraler Abteilungs-Checkbox für Index-Status)
require_once 'config.php';
require_once 'db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Angemeldete Benutzerdaten für Berechtigungen holen
$loggedInUsername = $_SESSION['username'] ?? '';
$loggedInUserDepartment = strtoupper($_SESSION['user_department'] ?? ''); // Immer als Großbuchstaben für konsistenten Vergleich
$is_pv_user = ($loggedInUserDepartment === 'PV');

// Optionslisten
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
    '(Assy TOP)' => '(Assy TOP) - Leiterzugseite Top (PF, Technologie)',
    '(Assy BOT)' => '(Assy BOT) - Leiterzugseite Bottom (PF, Technologie)',
    '(PS)' => '(PS) - Prüfspezifikation (PP, Technologie)',
    '(TS)' => '(TS) - Testspezifikation (PP, Technologie)',
    '(PA)' => '(PA) - Prüfanweisung (PP, Technologie)',
    '(AA)' => '(AA) - Arbeitsanweisung (PF, PG, PP, Technologie)',
    '(AW)' => '(AW) - Arbeitsanweisung (PF, PG, PP, Technologie)',
    '(AU)' => '(AU) - Arbeitsunterweisung (PF, PG, PP, Technologie)',
    '(UA)' => '(UA) - Umbauanleitung (PF, PG, PP, Technologie)',
    '(MA)' => '(MA) - Montageanweisung (PG, Technologie)',
    '(VA)' => '(VA) - Verpackungsanweisung (PF, PG, PP, Versand, Technologie)',
    '(LA)' => '(LA) - Lackieranweisung (PG, Technologie)',
    '(KA)' => '(KA) - Klebeanweisung (PG, Technologie)',
    '(AGL)' => '(AGL) - Abgleichsliste (PF, PG, PP, Technologie)',
    '(FW)' => '(FW) - Firmware/ Software (PP, Technologie)',
    '(Etik)' => '(Etik) - Etikettenzeichnung (PF, PP, Wareneingang, Technologie)',
    'ZUSB' => 'ZUSB',
    'ZUST' => 'ZUST',
    'ZUS' => 'ZUS'
];

$page_title = "Datensatz bearbeiten";
$id_to_edit = $_GET['id'] ?? null;
$suchsachnr_param = trim($_REQUEST['suchsachnr'] ?? '');

$record = null;
$sachnr_val = $kurz_val = $aez_val = $dokart_val = $teildok_val = $hinw_val = '';
$t_dat_val = $m_dat_val = $j_dat_val = '';
for ($i = 1; $i <= 7; $i++) {
    ${"ind" . $i . "_val"} = null;
    ${"ind" . $i . "_status_val"} = 0;
}

if (!$id_to_edit || !ctype_digit((string)$id_to_edit)) {
    $_SESSION['error_message'] = "Ungültige oder fehlende ID zum Ändern.";
    header('Location: scrolltab.php?suchsachnr=' . urlencode($suchsachnr_param));
    exit;
}

try {
    $stmt_fetch = $pdo->prepare("SELECT * FROM zeichnverw WHERE id = :id");
    $stmt_fetch->execute(['id' => $id_to_edit]);
    $record = $stmt_fetch->fetch(PDO::FETCH_ASSOC);

    if ($record) {
        $sachnr_val = $record['sachnr'];
        $kurz_val = $record['kurz'];
        $aez_val = $record['aez'];
        $dokart_val = $record['dokart'];
        $teildok_val = $record['teildok'];
        $hinw_val = $record['hinw'];
        for ($i = 1; $i <= 7; $i++) {
            ${"ind" . $i . "_val"} = $record["ind$i"] ?? null;
            ${"ind" . $i . "_status_val"} = $record["ind" . $i . "_status"] ?? 0;
        }
        if ($record['dat'] && $record['dat'] !== '0000-00-00') {
            try {
                $date_obj = new DateTime($record['dat']);
                $t_dat_val = $date_obj->format('d');
                $m_dat_val = $date_obj->format('m');
                $j_dat_val = $date_obj->format('Y');
            } catch (Exception $e) {
            }
        }
    } else { /* ... Fehlerbehandlung ... */
    }
} catch (PDOException $e) { /* ... Fehlerbehandlung ... */
}

// Initialen Zustand der abteilungsspezifischen Index-Status-Checkbox bestimmen
$department_specific_indices_all_received = false;
$current_user_has_relevant_indices_in_record = false;
$berechtigte_abteilungen_fuer_status_cb = ['PP', 'PG', 'PF', 'PV'];

if (in_array($loggedInUserDepartment, $berechtigte_abteilungen_fuer_status_cb)) {
    $all_found_and_received_for_user_dept = true;
    for ($i_php = 0; $i_php < 7; $i_php++) {
        $db_col_num = $i_php + 1;
        $index_wert_db = ${"ind" . $db_col_num . "_val"} ?? '';
        $index_status_db = ${"ind" . $db_col_num . "_status_val"} ?? 0;

        if (!empty($index_wert_db) && $index_wert_db != 0) {
            $index_department_code = '';
            if (isset($index_options_list[$index_wert_db])) {
                $option_display_text = $index_options_list[$index_wert_db];
                if (preg_match('/-\s*(PP|PG|PF|PV)\b/i', $option_display_text, $matches_dept)) {
                    $index_department_code = strtoupper($matches_dept[1]);
                }
            }
            if ($loggedInUserDepartment === $index_department_code) {
                $current_user_has_relevant_indices_in_record = true;
                if (!$index_status_db) {
                    $all_found_and_received_for_user_dept = false;
                    break;
                }
            }
        }
    }
    if ($current_user_has_relevant_indices_in_record && $all_found_and_received_for_user_dept) {
        $department_specific_indices_all_received = true;
    }
}

// Für die Kontext-Vorschau (bleibt wie zuvor)
$suchsachnr_display_filter = trim($_REQUEST['suchsachnr_preview_filter'] ?? $sachnr_val);
$krit_display = !empty($suchsachnr_display_filter) ? 'sachnr ASC, aez ASC' : 'id DESC';
try {
    $sql_display = "SELECT id, sachnr, kurz, aez, dokart, teildok, ind1, ind1_status, ind2, ind2_status, ind3, ind3_status, ind4, ind4_status, ind5, ind5_status, ind6, ind6_status, ind7, ind7_status, dat, hinw, record_status FROM zeichnverw WHERE sachnr LIKE :suchsachnr_display_filter ORDER BY $krit_display LIMIT 50";
    $stmt_display = $pdo->prepare($sql_display);
    $stmt_display->execute(['suchsachnr_display_filter' => $suchsachnr_display_filter . '%']);
    $results_display = $stmt_display->fetchAll();
    $num_rows_display = count($results_display);
} catch (PDOException $e) {
    $results_display = [];
    $num_rows_display = 0;
    error_log("ds_aend Preview SQL Error: " . $e->getMessage());
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
    <link href="custom_styles.css" rel="stylesheet">
</head>

<body>
    <header class="app-header">
        <div class="logo-left"><img src="EPSa_logo_group_diap.svg" alt="EPSa Logo"></div>
        <h1 class="app-title"><a href="index.php" style="color: inherit; text-decoration: none;" title="Zur Startseite"><?php echo htmlspecialchars(APP_TITLE); ?></a></h1>
        <div class="user-info">Angemeldet als: <strong><?php echo htmlspecialchars($_SESSION['username']); ?></strong><a href="logout.php" style="margin-left: 0.75rem;">Logout</a></div>
        <div class="logo-right"><img src="SeroEMSgroup_Logo_vc_DIAP.svg" alt="SeroEMS Logo"></div>
    </header>

    <div class="container-fluid mt-3">
        <div class="app-main-content">
            <?php if (isset($_SESSION['error_message'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert"><?php echo htmlspecialchars($_SESSION['error_message']);
                                                                                            unset($_SESSION['error_message']); ?><button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>
            <?php endif; ?>
            <?php if (isset($_SESSION['success_message'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert"><?php echo htmlspecialchars($_SESSION['success_message']);
                                                                                            unset($_SESSION['success_message']); ?><button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>
            <?php endif; ?>

            <section class="form-section mb-4">
                <h3 class="mb-3">Kontext-Vorschau <small class="text-body-secondary fw-normal fs-6">(gefiltert nach aktueller Sachnr. des Datensatzes)</small></h3>
                <form name="form_preview_filter" method="get" action="ds_aend.php" class="row g-3 align-items-center">
                    <input type="hidden" name="id" value="<?php echo htmlspecialchars($id_to_edit); ?>">
                    <div class="col-md-auto"><label for="suchsachnr_preview_filter_input" class="col-form-label">Filter für Vorschau:</label></div>
                    <div class="col-md-4"><input id="suchsachnr_preview_filter_input" name="suchsachnr_preview_filter" type="text" class="form-control form-control-sm" value="<?php echo htmlspecialchars($suchsachnr_display_filter); ?>" placeholder="Teil der Sachnummer..."></div>
                    <div class="col-md-auto"><button type="submit" class="btn btn-secondary btn-sm">Vorschau Aktualisieren</button></div>
                    <div class="col-md text-md-end"><small class="text-body-secondary"><?php echo $num_rows_display; ?> Datensätze in Vorschau</small></div>
                </form>
                <div class="table-responsive-custom-height mt-3" style="max-height: 200px;">
                    <table class="table table-striped table-hover table-sm table-bordered">
                        <thead class="table-dark">
                            <tr>
                                <th>Sachnummer</th>
                                <th>Kurz</th>
                                <th>ÄZ</th>
                                <th>Dok.-Art</th>
                                <th>Teil-Dok.</th>
                                <th>Indizes</th>
                                <th>Datum</th>
                                <th>Hinweis</th>
                            </tr>
                        </thead>
                        <tbody><?php $results = $results_display;
                                $suchsachnr = $suchsachnr_display_filter;
                                include("tabinh.php"); ?></tbody>
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
                            <?php // Globale "Zeichnung erhalten" Checkbox - bleibt PG-gesteuert 
                            ?>
                            <div class="form-check fs-5">
                                <input class="form-check-input" type="checkbox" value="1" id="record_status_checkbox_main" name="record_status_checkbox" <?php if ($record['record_status'] ?? 0) echo 'checked'; ?> <?php if (strtoupper($loggedInUserDepartment ?? '') !== 'PG') echo 'disabled'; ?>>
                                <label class="form-check-label fw-bold" for="record_status_checkbox_main">Zeichnung empfangen <?php if (strtoupper($loggedInUserDepartment ?? '') !== 'PG') echo '<small class="text-muted">(Nur Abt. PG)</small>'; ?></label>
                            </div>
                        </div>

                        <?php // Abteilungsspezifische "Alle meine Indizes erhalten" Checkbox
                        if (in_array($loggedInUserDepartment, $berechtigte_abteilungen_fuer_status_cb)): ?>
                            <div class="row mb-2">
                                <div class="col-12">
                                    <div class="form-check form-check-lg">
                                        <input class="form-check-input" type="checkbox" value="1" id="my_department_indices_status_checkbox" name="my_department_indices_status" <?php if ($department_specific_indices_all_received) echo 'checked'; ?> <?php if (!$current_user_has_relevant_indices_in_record) echo 'disabled'; ?>>
                                        <label class="form-check-label" for="my_department_indices_status_checkbox"><strong><?php echo htmlspecialchars($loggedInUserDepartment); ?></strong> bestätigt den erhalt der Zeichnung <?php if (!$current_user_has_relevant_indices_in_record) echo '<small class="text-muted">(Keine Indizes Ihrer Abt. in diesem DS)</small>'; ?></label>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                        <hr class="my-3">

                        <div class="row gx-2 align-items-start mb-3">
                            <div class="col-auto">
                                <label for="sachnr_edit" class="form-label">Sachnr.:</label>
                                <input id="sachnr_edit" name="sachnr" type="text" class="form-control form-control-sm" style="width: 250px;" value="<?php echo htmlspecialchars($sachnr_val); ?>" maxlength="50">
                            </div>
                            <?php if ($is_pv_user): ?>
                                <div class="col-auto"><label class="form-label" style="visibility: hidden; display: block;">&nbsp;</label><button type="button" id="add_kurz_btn" class="btn btn-success btn-sm"><i class="bi bi-plus-lg"></i> Kürzel</button></div>
                                <div class="col">
                                    <label class="form-label">Kurz:</label>
                                    <div id="kurz_selectors_container">
                                        <?php $kurz_werte_array = (!empty($kurz_val)) ? explode(' ', trim($kurz_val)) : [''];
                                        foreach ($kurz_werte_array as $k_idx => $k_wert): ?>
                                            <div class="input-group kurz-selector-group <?php echo $k_idx > 0 ? 'mt-1' : ''; ?> w-100">
                                                <select class="form-select form-select-sm kurz-select">
                                                    <?php foreach ($kurz_options_list as $value => $display_text): ?>
                                                        <option value="<?php echo htmlspecialchars($value); ?>" <?php if ($k_wert === $value) echo 'selected'; ?>><?php echo htmlspecialchars($display_text); ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                                <button type="button" class="btn btn-danger btn-sm remove-kurz-btn" style="<?php echo (count($kurz_werte_array) > 1 || !empty($k_wert)) ? '' : 'display: none;'; ?>">-</button>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                    <input type="hidden" name="kurz" id="kurz_combined_hidden" value="<?php echo htmlspecialchars($kurz_val); ?>">
                                </div>
                            <?php else: ?>
                                <div class="col">
                                    <label class="form-label">Kurz:</label>
                                    <div class="read-only-kurz p-2 rounded form-control-sm" style="background-color: var(--bs-tertiary-bg); border: 1px solid var(--bs-border-color); min-height: 31px; line-height:normal; display:flex; align-items:center;">
                                        <?php if (!empty($kurz_val)) {
                                            $kurz_display_array = [];
                                            $temp_kurz_werte = explode(' ', trim($kurz_val));
                                            foreach ($temp_kurz_werte as $k_wert_ro) {
                                                $kurz_display_array[] = htmlspecialchars($kurz_options_list[$k_wert_ro] ?? $k_wert_ro);
                                            }
                                            echo implode('<span class="text-body-secondary mx-1">/</span>', $kurz_display_array);
                                        } else {
                                            echo '<span class="text-muted fst-italic">Kein Kürzel</span>';
                                        } ?>
                                    </div>
                                    <input type="hidden" name="kurz" value="<?php echo htmlspecialchars($kurz_val); ?>">
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="row gx-2 align-items-end mb-3">
                            <div class="col-auto"><label for="aez_edit" class="form-label">ÄZ/Vers.:</label><input id="aez_edit" name="aez" type="text" class="form-control form-control-sm" style="width: 150px;" value="<?php echo htmlspecialchars($aez_val); ?>" maxlength="9"></div>
                            <div class="col-auto"><label for="dokart_edit" class="form-label">Dok.-Art:</label><input id="dokart_edit" name="dokart" type="text" class="form-control form-control-sm" style="width: 150px;" value="<?php echo htmlspecialchars($dokart_val); ?>" maxlength="5"></div>
                            <div class="col-auto"><label for="teildok_edit" class="form-label">Teil-Dok.:</label><input id="teildok_edit" name="teildok" type="text" class="form-control form-control-sm" style="width: 150px;" value="<?php echo htmlspecialchars($teildok_val); ?>" maxlength="5"></div>
                        </div>

                        <div class="row gx-3 align-items-start mb-3">
                            <div class="col-md-7">
                                <label class="form-label d-block mb-1">Indizes (max. 7):</label>
                                <div id="indices_selectors_container">
                                    <?php
                                    $aktive_indizes_fuer_form_display = [];
                                    $hat_aktive_indizes_display = false;
                                    for ($i_php_disp = 0; $i_php_disp < 7; $i_php_disp++) {
                                        $db_col_num_disp = $i_php_disp + 1;
                                        $index_wert_db_disp = ${"ind" . $db_col_num_disp . "_val"} ?? '';
                                        $index_status_db_disp = ${"ind" . $db_col_num_disp . "_status_val"} ?? 0;
                                        if (!empty($index_wert_db_disp) && $index_wert_db_disp != 0) {
                                            $aktive_indizes_fuer_form_display[$i_php_disp] = ['value' => $index_wert_db_disp, 'status' => $index_status_db_disp];
                                            $hat_aktive_indizes_display = true;
                                        }
                                    }
                                    if (!$hat_aktive_indizes_display && $is_pv_user) {
                                        $aktive_indizes_fuer_form_display[0] = ['value' => '', 'status' => 0];
                                    }

                                    if ($is_pv_user || $hat_aktive_indizes_display) {
                                        $form_idx_key_display = 0;
                                        foreach ($aktive_indizes_fuer_form_display as $index_data) {
                                            $idx_wert_disp = $index_data['value'];
                                            $idx_status_disp = $index_data['status'];

                                            $select_classes_disp = "form-select form-select-sm index-select";
                                            if (!empty($idx_wert_disp) && !$idx_status_disp) {
                                                $select_classes_disp .= " status-not-received";
                                            } else {
                                                $select_classes_disp .= " status-received";
                                            }
                                    ?>
                                            <div class="input-group mb-1 index-selector-group align-items-center">
                                                <?php if ($is_pv_user): ?>
                                                    <select name="dynamic_indices[<?php echo $form_idx_key_display; ?>]" class="<?php echo $select_classes_disp; ?>" style="width: 100px; min-width: 100px; flex-grow: 0;">
                                                        <?php foreach ($index_options_list as $value_opt => $display_text_opt): ?>
                                                            <option value="<?php echo htmlspecialchars($value_opt); ?>" <?php if ((string)$idx_wert_disp === (string)$value_opt) echo 'selected'; ?>><?php echo htmlspecialchars($display_text_opt); ?></option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                <?php else: /* Nicht-PV User sehen Text */ ?>
                                                    <span class="form-control-plaintext <?php echo $select_classes_disp; ?>" style="width: auto; min-width: 120px; /* Mehr Platz für Text */ padding: .25rem .5rem; border: 1px solid var(--bs-border-color); border-radius:var(--bs-border-radius-sm); background-color: var(--bs-tertiary-bg); display:inline-block; height: calc(1.5em + .5rem + 2px); font-size: .875em;">
                                                        <?php echo isset($index_options_list[$idx_wert_disp]) ? htmlspecialchars($index_options_list[$idx_wert_disp]) : htmlspecialchars($idx_wert_disp); ?>
                                                    </span>
                                                    <input type="hidden" name="dynamic_indices[<?php echo $form_idx_key_display; ?>]" value="<?php echo htmlspecialchars($idx_wert_disp); ?>">
                                                <?php endif; ?>

                                                <?php if ($is_pv_user):
                                                    $show_remove_btn = (count($aktive_indizes_fuer_form_display) > 1 || ($hat_aktive_indizes_display && count($aktive_indizes_fuer_form_display) == 1 && !empty($idx_wert_disp))); ?>
                                                    <button type="button" class="btn btn-danger btn-sm remove-index-btn ms-2" style="<?php echo $show_remove_btn ? '' : 'display:none;'; ?>">-</button>
                                                <?php else: // Platzhalter für Nicht-PV 
                                                ?>
                                                    <div style="width: 38px; /* Ca. Breite des Buttons inkl. Margin */ height: 1px; display:inline-block; margin-left:0.5rem;"></div>
                                                <?php endif; ?>
                                            </div>
                                        <?php $form_idx_key_display++;
                                        }
                                    } else if (!$is_pv_user && !$hat_aktive_indizes_display) { ?>
                                        <div class="read-only-indices p-2 rounded" style="background-color: var(--bs-body-bg); border: 1px solid var(--bs-border-color); min-height:31px; line-height: normal; display: flex; align-items: center;">
                                            <span class="text-muted fst-italic">Keine Indizes zugeordnet.</span>
                                        </div>
                                    <?php } ?>
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
    </div>

    <footer class="text-center text-body-secondary py-3 mt-3">
        <small>&copy; <?php echo date("Y"); ?> Ihre Firma. Alle Rechte vorbehalten.</small>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Der JavaScript-Block muss ebenfalls angepasst werden! 
    </script>
</body>

</html>