<?php
// ds_aend.php (FINALE VERSION mit allen Admin-Rechten und korrekter Feld-Anzeige)
require_once 'config.php';
require_once 'db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// --- START: ZENTRALE KONFIGURATION & BERECHTIGUNGEN ---

// 1. Definition der "Super-User" (Admins)
$authorized_user_ids = [4, 10, 3]; // Ihre Admin-IDs
$is_special_admin = isset($_SESSION['user_id']) && in_array($_SESSION['user_id'], $authorized_user_ids);

// 2. Das "Wörterbuch", das Kürzel auf Namen übersetzt
$department_map = [
    'PP' => 'PP',
    'PG' => 'PG',
    'PF' => 'PF',
    'PV' => 'PV',
    'VS' => 'Versand',
    'EK' => 'Einkauf'
];

// 3. Benutzerdaten aus der Session holen
$loggedInUsername = $_SESSION['username'] ?? '';
$loggedInUserDepartment = strtoupper($_SESSION['user_department'] ?? '');

// 4. NEUE, ZENTRALE BERECHTIGUNGSPRÜFUNG: Wer darf die Stammdaten bearbeiten?
// Ein Benutzer darf bearbeiten, wenn er zur Abteilung PV gehört ODER ein Admin ist.
$can_edit_master_data = ($loggedInUserDepartment === 'PV') || $is_special_admin;

// --- ENDE: ZENTRALE KONFIGURATION & BERECHTIGUNGEN ---


// Optionslisten
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
if (!$id_to_edit || !ctype_digit((string)$id_to_edit)) {
    $_SESSION['error_message'] = "Ungültige oder fehlende ID zum Ändern.";
    header('Location: scrolltab.php?suchsachnr=' . urlencode($suchsachnr_param));
    exit;
}

try {
    $stmt_fetch = $pdo->prepare("SELECT * FROM zeichnverw WHERE id = :id");
    $stmt_fetch->execute(['id' => $id_to_edit]);
    $record = $stmt_fetch->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) { /* ... Fehlerbehandlung ... */
}

// Für die Kontext-Vorschau
$suchsachnr_display_filter = trim($_REQUEST['suchsachnr_preview_filter'] ?? ($record['sachnr'] ?? ''));
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
        <h1 class="app-title"><?php echo htmlspecialchars(APP_TITLE); ?></h1>
        <div class="user-info d-flex align-items-center">
            <a href="index.php" title="Zur Startseite" class="text-light me-3"><i class="bi bi-house-door-fill" style="font-size: 1.7rem; vertical-align: middle;"></i></a>
            <span>Angemeldet als: </span>
            <strong style="margin-left: 0.3rem; margin-right: 0.75rem;"><?php echo htmlspecialchars($_SESSION['username']); ?></strong>
            <a href="logout.php">Logout</a>
        </div>
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
                            <div class="form-check fs-5">
                                <input class="form-check-input" type="checkbox" value="1" id="record_status_checkbox_main" name="record_status_checkbox" <?php if ($record['record_status'] ?? 0) echo 'checked'; ?> <?php if (!$is_special_admin) echo 'disabled'; ?>>
                                <label class="form-check-label fw-bold" for="record_status_checkbox_main">Zeichnung global erhalten <small class="text-muted">(Nur für Admins)</small></label>
                            </div>
                        </div>

                        <?php
                        // --- START: NEUER, FLEXIBLER BLOCK FÜR ABTEILUNGS-CHECKBOXEN ---
                        if (isset($record['record_status']) && $record['record_status']) {
                            $relevant_departments = [];
                            for ($i = 1; $i <= 7; $i++) {
                                $index_code = $record['ind' . $i] ?? null;
                                if (!empty($index_code) && isset($index_options_list[$index_code])) {
                                    $index_desc = $index_options_list[$index_code];
                                    $parts = explode(' - ', $index_desc);
                                    $dept_name = count($parts) > 1 ? end($parts) : null;
                                    if ($dept_name) {
                                        $dept_code = array_search($dept_name, $department_map);
                                        if ($dept_code) {
                                            $relevant_departments[$dept_code] = true;
                                        }
                                    }
                                }
                            }
                            $departments_to_display = [];
                            if ($is_special_admin) {
                                $departments_to_display = array_keys($relevant_departments);
                            } elseif (isset($relevant_departments[$loggedInUserDepartment])) {
                                $departments_to_display[] = $loggedInUserDepartment;
                            }
                            foreach ($departments_to_display as $dept_code) {
                                $is_dept_confirmed = true;
                                for ($i = 1; $i <= 7; $i++) {
                                    $index_code_loop = $record['ind' . $i] ?? null;
                                    $index_status_loop = $record['ind' . $i . '_status'] ?? 0;
                                    if ($index_code_loop) {
                                        $index_desc_loop = $index_options_list[$index_code_loop] ?? '';
                                        $parts_loop = explode(' - ', $index_desc_loop);
                                        $dept_name_loop = count($parts_loop) > 1 ? end($parts_loop) : null;
                                        $dept_code_loop = array_search($dept_name_loop, $department_map);
                                        if ($dept_code_loop === $dept_code && $index_status_loop == 0) {
                                            $is_dept_confirmed = false;
                                            break;
                                        }
                                    }
                                }
                                $can_confirm_this_department = (strtoupper($loggedInUserDepartment) === $dept_code) || $is_special_admin;
                        ?>
                                <section class="department-confirmation-section mb-3 p-3 border rounded bg-light-subtle">
                                    <h5 class="mb-2">Empfangsbestätigung für Abteilung: <span class="badge bg-info text-dark"><?php echo htmlspecialchars($dept_code); ?></span></h5>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" value="1" id="dept_status_<?php echo htmlspecialchars($dept_code); ?>" name="department_status_checkbox[<?php echo htmlspecialchars($dept_code); ?>]" <?php if ($is_dept_confirmed) echo 'checked'; ?> <?php if (!$can_confirm_this_department) echo 'disabled'; ?>>
                                        <label class="form-check-label" for="dept_status_<?php echo htmlspecialchars($dept_code); ?>">Zeichnung für Abteilung <strong><?php echo htmlspecialchars($dept_code); ?></strong> erhalten</label>
                                    </div>
                                </section>
                        <?php
                            }
                        }
                        // --- ENDE DES NEUEN BLOCKS ---
                        ?>

                        <hr class="my-3">

                        <div class="row gx-2 align-items-start mb-3">
                            <div class="col-auto">
                                <label for="sachnr_edit" class="form-label">Sachnr.:</label>
                                <?php if ($can_edit_master_data): ?>
                                    <input id="sachnr_edit" name="sachnr" type="text" class="form-control form-control-sm" style="width: 250px;" value="<?php echo htmlspecialchars($record['sachnr']); ?>" maxlength="50">
                                <?php else: ?>
                                    <div class="form-control-plaintext form-control-sm" style="width: 250px;"><?php echo htmlspecialchars($record['sachnr']); ?></div>
                                    <input type="hidden" name="sachnr" value="<?php echo htmlspecialchars($record['sachnr']); ?>">
                                <?php endif; ?>
                            </div>
                            <div class="col-auto">
                                <label class="form-label" style="visibility: hidden; display: block;">&nbsp;</label>
                                <button type="button" id="add_kurz_btn" class="btn btn-success btn-sm" <?php if (!$can_edit_master_data) echo 'disabled'; ?>><i class="bi bi-plus-lg"></i> Kürzel</button>
                            </div>
                            <div class="col">
                                <label class="form-label">Kurz:</label>
                                <?php if ($can_edit_master_data): ?>
                                    <div id="kurz_selectors_container">
                                        <?php $kurz_werte_array = (!empty($record['kurz'])) ? explode(' ', trim($record['kurz'])) : [''];
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
                                    <input type="hidden" name="kurz" id="kurz_combined_hidden" value="<?php echo htmlspecialchars($record['kurz']); ?>">
                                <?php else: ?>
                                    <div class="read-only-kurz p-2 rounded form-control-sm" style="background-color: var(--bs-tertiary-bg); border: 1px solid var(--bs-border-color); min-height: 31px; line-height:normal; display:flex; align-items:center;">
                                        <?php if (!empty($record['kurz'])) {
                                            $kurz_display_array = [];
                                            $temp_kurz_werte = explode(' ', trim($record['kurz']));
                                            foreach ($temp_kurz_werte as $k_wert_ro) {
                                                $kurz_display_array[] = htmlspecialchars($kurz_options_list[$k_wert_ro] ?? $k_wert_ro);
                                            }
                                            echo implode('<span class="text-body-secondary mx-1">/</span>', $kurz_display_array);
                                        } else {
                                            echo '<span class="text-muted fst-italic">Kein Kürzel</span>';
                                        } ?>
                                    </div>
                                    <input type="hidden" name="kurz" value="<?php echo htmlspecialchars($record['kurz']); ?>">
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="row gx-2 align-items-end mb-3">
                            <div class="col-auto"><label for="aez_edit" class="form-label">ÄZ/Vers.:</label><input id="aez_edit" name="aez" type="text" class="form-control form-control-sm" style="width: 150px;" value="<?php echo htmlspecialchars($record['aez']); ?>" maxlength="9" <?php if (!$can_edit_master_data) echo 'readonly'; ?>></div>
                            <div class="col-auto"><label for="dokart_edit" class="form-label">Dok.-Art:</label><input id="dokart_edit" name="dokart" type="text" class="form-control form-control-sm" style="width: 150px;" value="<?php echo htmlspecialchars($record['dokart']); ?>" maxlength="5" <?php if (!$can_edit_master_data) echo 'readonly'; ?>></div>
                            <div class="col-auto"><label for="teildok_edit" class="form-label">Teil-Dok.:</label><input id="teildok_edit" name="teildok" type="text" class="form-control form-control-sm" style="width: 150px;" value="<?php echo htmlspecialchars($record['teildok']); ?>" maxlength="5" <?php if (!$can_edit_master_data) echo 'readonly'; ?>></div>
                        </div>

                        <div class="row gx-3 align-items-start mb-3">
                            <div class="col-md-7">
                                <label class="form-label d-block mb-1">Indizes (max. 7):</label>
                                <div id="indices_selectors_container">
                                    <?php
                                    $hat_aktive_indizes_display = false;
                                    for ($i = 1; $i <= 7; $i++) {
                                        if (!empty($record["ind$i"])) {
                                            $hat_aktive_indizes_display = true;
                                            break;
                                        }
                                    }
                                    if ($can_edit_master_data || $hat_aktive_indizes_display) {
                                        $form_idx_key_display = 0;
                                        for ($i = 1; $i <= 7; $i++) {
                                            $idx_wert_disp = $record["ind$i"] ?? '';
                                            $idx_status_disp = $record["ind{$i}_status"] ?? 0;
                                            if ($can_edit_master_data || !empty($idx_wert_disp)) {
                                    ?>
                                                <div class="input-group mb-1 index-selector-group align-items-center">
                                                    <select name="dynamic_indices[<?php echo $form_idx_key_display; ?>]" class="form-select form-select-sm index-select <?php echo !$idx_status_disp ? 'status-not-received' : 'status-received'; ?>" <?php if (!$can_edit_master_data) echo 'disabled'; ?>>
                                                        <?php foreach ($index_options_list as $value_opt => $display_text_opt): ?>
                                                            <option value="<?php echo htmlspecialchars($value_opt); ?>" <?php if ((string)$idx_wert_disp === (string)$value_opt) echo 'selected'; ?>><?php echo htmlspecialchars($display_text_opt); ?></option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                    <?php if ($can_edit_master_data): ?>
                                                        <button type="button" class="btn btn-danger btn-sm remove-index-btn ms-2">-</button>
                                                    <?php endif; ?>
                                                </div>
                                    <?php
                                                $form_idx_key_display++;
                                            }
                                        }
                                    } else {
                                        echo '<div class="read-only-indices p-2 rounded" style="background-color: var(--bs-body-bg); border: 1px solid var(--bs-border-color);"><span class="text-muted fst-italic">Keine Indizes zugeordnet.</span></div>';
                                    }
                                    ?>
                                </div>
                                <?php if ($can_edit_master_data): ?>
                                    <button type="button" id="add_index_btn" class="btn btn-success btn-sm mt-1"><i class="bi bi-plus-lg"></i> Index hinzufügen</button>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-5">
                                <label class="form-label d-block mb-1">Datum:</label>
                                <div class="d-flex align-items-center">
                                    <?php
                                    $date_obj = null;
                                    if ($record['dat'] && $record['dat'] !== '0000-00-00') {
                                        try {
                                            $date_obj = new DateTime($record['dat']);
                                        } catch (Exception $e) {
                                        }
                                    }
                                    ?>
                                    <input name="t_dat_display" type="text" class="form-control form-control-sm" placeholder="TT" style="width: 65px;" value="<?php echo $date_obj ? $date_obj->format('d') : ''; ?>" readonly>
                                    <span class="ps-1 pe-1 text-center">.</span>
                                    <input name="m_dat_display" type="text" class="form-control form-control-sm" placeholder="MM" style="width: 65px;" value="<?php echo $date_obj ? $date_obj->format('m') : ''; ?>" readonly>
                                    <span class="ps-1 pe-1 text-center">.</span>
                                    <input name="y_dat_display" type="text" class="form-control form-control-sm" placeholder="JJJJ" style="width: 85px;" value="<?php echo $date_obj ? $date_obj->format('Y') : ''; ?>" readonly>
                                </div>
                            </div>
                        </div>

                        <div class="row gx-2 mb-3">
                            <div class="col-12">
                                <label for="hinw_edit" class="form-label">Hinweis:</label>
                                <input id="hinw_edit" name="hinw" type="text" class="form-control form-control-sm" value="<?php echo htmlspecialchars($record['hinw']); ?>" maxlength="50" <?php if (!$can_edit_master_data) echo 'readonly'; ?>>
                            </div>
                        </div>

                        <div class="mt-4 d-flex gap-2">
                            <button type="submit" class="btn btn-success"><i class="bi bi-save"></i> Änderungen speichern</button>
                            <?php if ($can_edit_master_data): ?>
                              <button type="submit" class="btn btn-warning" name="action" value="Zeichnung entfernen" onclick="return confirm('Soll diese Zeichnung wirklich entfernt und archiviert werden? Der Datensatz bleibt erhalten.')"><i class="bi bi-archive"></i> Zeichnung entfernen</button>
                            <?php endif; ?>
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
            // Ihr JavaScript zur Steuerung der dynamischen Felder "Kurz" und "Indizes"
            document.addEventListener('DOMContentLoaded', function() {
                // ... (Hier würde der JavaScript-Code stehen, der die "+"- und "-"-Buttons steuert)
            });
        </script>
</body>

</html>