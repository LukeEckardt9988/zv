<?php
// scrolltab.php
require_once 'config.php';
require_once 'db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$aktueller_tag = date('d');
$aktueller_monat = date('m');
$aktuelles_jahr = date('Y');

$page_title = "Zeichnungsübersicht";
$suchsachnr = trim($_REQUEST['suchsachnr'] ?? ''); // $_REQUEST für GET oder POST
$krit = !empty($suchsachnr) ? 'sachnr' : 'id DESC';

// In scrolltab.php
try {
    $sql = "SELECT id, sachnr, kurz, aez, dokart, teildok,
                   ind1, ind1_status, 
                   ind2, ind2_status, 
                   ind3, ind3_status, 
                   ind4, ind4_status, 
                   ind5, ind5_status, 
                   ind6, ind6_status, 
                   ind7, ind7_status, 
                   dat, hinw, record_status
            FROM zeichnverw
            WHERE sachnr LIKE :suchsachnr
            ORDER BY $krit, aez
            LIMIT 200";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['suchsachnr' => $suchsachnr . '%']);
    $results = $stmt->fetchAll();
    $num_rows = count($results); // Stellen Sie sicher, dass num_rows hier aktualisiert wird
} catch (PDOException $e) {
    error_log("scrolltab SQL Error: " . $e->getMessage());
    $_SESSION['error_message'] = "Fehler beim Laden der Daten.";
    $results = [];
    $num_rows = 0;
}

// NEU: Optionen für die Index-Dropdowns definieren
// Diese können auch in einer separaten Konfigurationsdatei ausgelagert werden,
// wenn sie an mehreren Stellen (z.B. auch in ds_aend.php) identisch verwendet werden.
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


?>
<!DOCTYPE html>
<html lang="de" data-bs-theme="dark">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title) . " - " . htmlspecialchars(APP_TITLE); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Maven+Pro:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="custom_styles.css" rel="stylesheet">
</head>

<body>
    <header class="app-header">
        <div class="logo-left"><img src="EPSa_logo_group_diap.svg" alt="EPSa Logo"></div>
        <h1 class="app-title">
            <?php echo htmlspecialchars(APP_TITLE); // Der Titel ist jetzt nicht mehr der Link 
            ?>
        </h1>
        <div class="user-info d-flex align-items-center"> 
            <a href="index.php" title="Zur Startseite" class="text-light me-3">
                <i class="bi bi-house-door-fill" style="font-size: 1.7rem; vertical-align: middle;"></i> 
            </a>
            <span>Angemeldet als: </span>
            <strong style="margin-left: 0.3rem; margin-right: 0.75rem;"><?php echo htmlspecialchars($_SESSION['username']); ?></strong>
            <a href="logout.php">Logout</a>
        </div>
        <div class="logo-right"><img src="SeroEMSgroup_Logo_vc_DIAP.svg" alt="SeroEMS Logo"></div>
    </header>



    <div class="container-fluid mt-3">
        <div class="app-main-content">

            <?php if (isset($_SESSION['success_message'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php echo htmlspecialchars($_SESSION['success_message']);
                    unset($_SESSION['success_message']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            <?php if (isset($_SESSION['error_message'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php echo htmlspecialchars($_SESSION['error_message']);
                    unset($_SESSION['error_message']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <section class="form-section mb-4">
                <h3 class="mb-3">Datensatz suchen</h3>
                <form name="form_search" method="post" action="scrolltab.php" class="row g-3 align-items-center">
                    <div class="col-md-auto">
                        <label for="suchsachnr_input" class="col-form-label">Sachnummer:</label>
                    </div>
                    <div class="col-md-4">
                        <input id="suchsachnr_input" name="suchsachnr" type="text" class="form-control form-control-sm" value="<?php echo htmlspecialchars($suchsachnr); ?>" placeholder="Teil der Sachnummer...">
                    </div>
                    <div class="col-md-auto">
                        <button name="SubmitSearch" type="submit" class="btn btn-primary btn-sm">Suchen</button>
                    </div>
                    <div class="col-md text-md-end">
                        <small class="text-body-secondary"><?php echo $num_rows; ?> Datensätze selektiert</small>
                    </div>
                </form>
            </section>

            <section class="table-section">
                <h3 class="mb-3">Gefundene Datensätze</h3>
                <div class="table-responsive-custom-height">
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
                            <?php include("tabinh.php"); // $results und $suchsachnr sind hier verfügbar
                            ?>
                        </tbody>
                    </table>
                </div>
            </section>

            <section class="form-section mt-4">
                <h3 class="mb-3">Neuen Datensatz anlegen</h3>
                <form action="ds_speich.php" method="post" name="form_new_entry">
                    <input name="action" type="hidden" value="Speichern">
                    <input name="suchsachnr_return" type="hidden" value="<?php echo htmlspecialchars($suchsachnr); ?>">

                    <div class="row gx-2 align-items-end mb-3">
                        <div class="col-auto">
                            <label for="sachnr_neu" class="form-label">Sachnr.:</label>
                            <input id="sachnr_neu" name="sachnr" type="text" class="form-control form-control-sm" style="width: 250px;" maxlength="50">
                        </div>
                        <div class="col-auto">
                            <label class="form-label" style="visibility: hidden; display: block;">&nbsp;</label>
                            <button type="button" id="add_kurz_btn" class="btn btn-success btn-sm"><i class="bi bi-plus-lg"></i> Kürzel</button>
                        </div>
                        <div class="col">
                            <label class="form-label">Kurz:</label>
                            <div id="kurz_selectors_container">
                                <div class="input-group kurz-selector-group w-100">
                                    <select class="form-select form-select-sm kurz-select">
                                        <?php foreach ($kurz_options_list as $value => $display_text): ?>
                                            <option value="<?php echo htmlspecialchars($value); ?>"><?php echo htmlspecialchars($display_text); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <button type="button" class="btn btn-danger btn-sm remove-kurz-btn" style="display: none;">-</button>
                                </div>
                            </div>
                            <input type="hidden" name="kurz" id="kurz_combined_hidden">
                        </div>
                    </div>

                    <div class="row gx-2 align-items-end mb-3">
                        <div class="col-auto">
                            <label for="aez_neu" class="form-label">ÄZ/Vers.:</label>
                            <input id="aez_neu" name="aez" type="text" class="form-control form-control-sm" style="width: 150px;" maxlength="9">
                        </div>
                        <div class="col-auto">
                            <label for="dokart_neu" class="form-label">Dok.-Art:</label>
                            <input id="dokart_neu" name="dokart" type="text" class="form-control form-control-sm" style="width: 150px;" maxlength="5">
                        </div>
                        <div class="col-auto">
                            <label for="teildok_neu" class="form-label">Teil-Dok.:</label>
                            <input id="teildok_neu" name="teildok" type="text" class="form-control form-control-sm" style="width: 150px;" maxlength="5">
                        </div>
                        <div class="col-auto">
                            <label class="form-label" style="visibility: hidden; display: block;">&nbsp;</label>
                            <button type="button" id="add_index_btn" class="btn btn-success btn-sm"><i class="bi bi-plus-lg"></i> Index</button>
                        </div>
                        <div class="col-auto">
                            <label class="form-label">Indizes (max. 7):</label>
                            <div id="indices_selectors_container">
                                <div class="input-group mb-1 index-selector-group align-items-center">
                                    <select name="dynamic_indices[0]" class="form-select form-select-sm index-select status-not-received" style="width: 100px; min-width: 100px; flex-grow: 0;">
                                        <?php foreach ($index_options_list as $value => $display_text): ?>
                                            <option value="<?php echo htmlspecialchars($value); ?>"><?php echo htmlspecialchars($display_text); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <div class="form-check ms-2">
                                        <!-- Ich habe die Echeckboxen versteckt.. damit automatisch 0 gespeichert wird.. ist nicht schön aber funktioniert. -->
                                        <input type="hidden" name="dynamic_indices_status[0]" value="0">
                                        <input class="form-check-input index-status-checkbox hiddencheckbox" type="checkbox" name="dynamic_indices_status[0]" value="1" id="index_status_new_0">
                                        <label class="form-check-label small" id="index_status_new_1" for="index_status_new_0">erhalten</label>
                                    </div>
                                    <button type="button" class="btn btn-danger btn-sm remove-index-btn ms-2" style="display: none;">-</button>
                                </div>

                            </div>
                        </div>


                        <div class="col-auto ms-2">
                            <label class="form-label mb-0">Datum:</label>
                        </div>
                        <div class="col-auto">
                            <input name="t_dat" type="text" class="form-control form-control-sm" placeholder="TT" style="width: 65px;" value="<?php echo htmlspecialchars($aktueller_tag); ?>" readonly>
                        </div>
                        <div class="col-auto ps-1 pe-1 text-center">.</div>
                        <div class="col-auto">
                            <input name="m_dat" type="text" class="form-control form-control-sm" placeholder="MM" style="width: 65px;" value="<?php echo htmlspecialchars($aktueller_monat); ?>" readonly>
                        </div>
                        <div class="col-auto ps-1 pe-1 text-center">.</div>
                        <div class="col-auto">
                            <input name="y_dat" type="text" class="form-control form-control-sm" placeholder="JJJJ" style="width: 85px;" value="<?php echo htmlspecialchars($aktuelles_jahr); ?>" readonly>
                        </div>
                    </div>

                    <div class="row gx-2 mb-3">
                        <div class="col-12">
                            <label for="hinw_neu" class="form-label">Hinweis:</label>
                            <input id="hinw_neu" name="hinw" type="text" class="form-control form-control-sm" maxlength="50">
                        </div>
                    </div>

                    <div class="mt-4">
                        <button type="submit" class="btn btn-success"><i class="bi bi-check-lg"></i> Neuen Datensatz speichern</button>
                    </div>
                </form>
            </section>


            <footer class="text-center text-body-secondary py-3">
                <small>&copy; <?php echo date("Y"); ?> EPSa Alle Rechte vorbehalten.</small>
            </footer>

            <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    // --- KURZ-FELDER LOGIK (unverändert von Ihrer Datei) ---
                    const kurzContainer = document.getElementById('kurz_selectors_container');
                    const addKurzButton = document.getElementById('add_kurz_btn');
                    const hiddenCombinedKurzInput = document.getElementById('kurz_combined_hidden');

                    function updateCombinedKurzField() {
                        if (!kurzContainer || !hiddenCombinedKurzInput) return; // Sicherstellen, dass Elemente existieren
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
                        allKurzGroups.forEach((group, idx) => {
                            const removeBtn = group.querySelector('.remove-kurz-btn');
                            if (removeBtn) {
                                removeBtn.style.display = (allKurzGroups.length > 1) ? 'inline-block' : 'none';
                            }
                        });
                    }

                    if (kurzContainer && addKurzButton) {
                        kurzContainer.addEventListener('change', function(event) {
                            if (event.target.classList.contains('kurz-select')) {
                                updateCombinedKurzField();
                            }
                        });

                        addKurzButton.addEventListener('click', function() {
                            const firstKurzGroup = kurzContainer.querySelector('.kurz-selector-group');
                            if (!firstKurzGroup) return;

                            const newKurzGroup = firstKurzGroup.cloneNode(true);
                            newKurzGroup.querySelector('.kurz-select').value = '';

                            const removeKurzBtn = newKurzGroup.querySelector('.remove-kurz-btn');
                            if (removeKurzBtn) {
                                removeKurzBtn.style.display = 'inline-block';
                                removeKurzBtn.addEventListener('click', function() {
                                    newKurzGroup.remove();
                                    updateCombinedKurzField();
                                });
                            }
                            kurzContainer.appendChild(newKurzGroup);
                            updateCombinedKurzField();
                        });
                    }

                    // Event Listener für initial vorhandene "Entfernen"-Buttons im Kurz-Bereich
                    if (kurzContainer) {
                        kurzContainer.querySelectorAll('.kurz-selector-group').forEach(group => {
                            const removeBtn = group.querySelector('.remove-kurz-btn');
                            if (removeBtn) {
                                // Stellt sicher, dass der Listener nur einmal hinzugefügt wird, falls das Skript mehrmals läuft
                                if (!removeBtn.dataset.listenerAttached) {
                                    removeBtn.addEventListener('click', function() {
                                        const allKurzGroups = kurzContainer.querySelectorAll('.kurz-selector-group');
                                        if (allKurzGroups.length > 1) { // Nur entfernen, wenn es nicht die letzte Gruppe ist
                                            group.remove();
                                            updateCombinedKurzField();
                                        }
                                    });
                                    removeBtn.dataset.listenerAttached = 'true';
                                }
                            }
                        });
                    }
                    updateCombinedKurzField(); // Initial aufrufen


                    // --- INDIZES-FELDER LOGIK (aktualisiert) ---
                    const indicesContainer = document.getElementById('indices_selectors_container');
                    const addIndexButton = document.getElementById('add_index_btn');
                    const MAX_INDICES = 7;

                    function generateUniqueId(prefix = 'id_') {
                        return prefix + Math.random().toString(36).substr(2, 9);
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

                    function updateIndexControls() {
                        if (!indicesContainer) return;
                        const allIndexGroups = indicesContainer.querySelectorAll('.index-selector-group');
                        allIndexGroups.forEach((group) => {
                            const removeBtn = group.querySelector('.remove-index-btn');
                            if (removeBtn) {
                                removeBtn.style.display = (allIndexGroups.length > 1) ? 'inline-block' : 'none';
                            }
                            applyIndexStyling(group); // Styling für jede Gruppe anwenden/prüfen
                        });

                        if (addIndexButton) {
                            addIndexButton.disabled = (allIndexGroups.length >= MAX_INDICES);
                            addIndexButton.classList.toggle('disabled', allIndexGroups.length >= MAX_INDICES);
                        }
                    }

                    function addEventListenersToGroup(groupElement, key) {
                        groupElement.querySelector('.index-select').name = `dynamic_indices[${key}]`;

                        const hiddenStatus = groupElement.querySelector('input[type="hidden"]');
                        if (hiddenStatus) hiddenStatus.name = `dynamic_indices_status[${key}]`;

                        const checkbox = groupElement.querySelector('.index-status-checkbox');
                        checkbox.name = `dynamic_indices_status[${key}]`;

                        const label = groupElement.querySelector('.form-check-label');
                        const newId = generateUniqueId('index_status_' + key + '_');
                        checkbox.id = newId;
                        label.setAttribute('for', newId);

                        checkbox.addEventListener('change', function() {
                            applyIndexStyling(groupElement);
                        });
                        groupElement.querySelector('.index-select').addEventListener('change', function() {
                            applyIndexStyling(groupElement);
                        });

                        const removeBtn = groupElement.querySelector('.remove-index-btn');
                        if (removeBtn) {
                            removeBtn.addEventListener('click', function() {
                                groupElement.remove();
                                updateIndexControls();
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

                                // Den nächsten freien numerischen Schlüssel für die Namen finden
                                let newIndexKey = 0;
                                const existingKeys = new Set();
                                indicesContainer.querySelectorAll('.index-select').forEach(sel => {
                                    const match = sel.name.match(/\[(\d+)\]/);
                                    if (match) existingKeys.add(parseInt(match[1]));
                                });
                                while (existingKeys.has(newIndexKey)) {
                                    newIndexKey++;
                                }

                                // Namen und IDs für geklonte Elemente setzen
                                newIndexGroup.querySelector('.index-select').value = '';
                                newIndexGroup.querySelector('.index-status-checkbox').checked = false;

                                addEventListenersToGroup(newIndexGroup, newIndexKey); // Setzt Namen und Listener

                                indicesContainer.appendChild(newIndexGroup);
                                applyIndexStyling(newIndexGroup); // Initiales Styling für die neue Gruppe
                                updateIndexControls();
                            }
                        });
                    }

                    // Initial Event Listeners und Styling für PHP-gerenderte Elemente (besonders in ds_aend.php)
                    if (indicesContainer) {
                        indicesContainer.querySelectorAll('.index-selector-group').forEach((group, key) => {
                            // Stellt sicher, dass die von PHP generierten Namen korrekt indiziert sind (falls nicht schon geschehen)
                            // Dies ist eher eine Aufgabe für das PHP-Rendering, aber JS kann es hier korrigieren/sicherstellen.
                            const select = group.querySelector('.index-select');
                            const hiddenStatus = group.querySelector('input[type="hidden"]');
                            const checkbox = group.querySelector('.index-status-checkbox');

                            if (select && select.name.endsWith("[]")) select.name = `dynamic_indices[${key}]`;
                            if (hiddenStatus && hiddenStatus.name.endsWith("[]")) hiddenStatus.name = `dynamic_indices_status[${key}]`;
                            if (checkbox && checkbox.name.endsWith("[]")) checkbox.name = `dynamic_indices_status[${key}]`;

                            addEventListenersToGroup(group, key); // Fügt Listener hinzu und setzt Namen falls noch nötig
                            applyIndexStyling(group);
                        });
                        updateIndexControls();
                    }
                });
            </script>
</body>

</html>