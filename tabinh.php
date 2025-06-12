<?php
// tabinh.php - Finale, konsolidierte Version

// HINWEIS: Dieses Skript erwartet, dass die folgenden Variablen von der einbindenden
// Datei (scrolltab.php) bereitgestellt werden:
// - $results: Das Array mit den Datensätzen aus der Datenbank.
// - $suchsachnr: Der aktuelle Suchbegriff für die Rückkehr-Links.
// - $can_edit_master_data: Ein boolean (true/false), der angibt, ob der User Admin-Rechte hat.


// Sicherheitsabfrage, falls das Skript ohne Daten aufgerufen wird
if (!isset($results) || !is_array($results)) {
    echo "<tr><td colspan='8' class='text-center p-3 fst-italic text-body-secondary'>Fehler: Daten zum Laden nicht vorhanden.</td></tr>";
    return;
}

// Nachricht, wenn die Suche keine Ergebnisse liefert
if (empty($results)) {
    echo "<tr><td colspan='8' class='text-center p-3 fst-italic text-body-secondary'>Keine passenden Datensätze gefunden.</td></tr>";
    return;
}



// in tabinh.php - mit neu angeordneten Aktions-Buttons

foreach ($results as $row) {
    // Vorbereitungen (Datum, Indizes, Status-Klassen) bleiben wie bisher
    $dat_formatted = '&nbsp;';
    if (!empty($row['dat']) && $row['dat'] !== '0000-00-00') {
        try {
            $date_obj = new DateTime($row['dat']);
            $dat_formatted = htmlspecialchars($date_obj->format('d.m.Y'));
        } catch (Exception $e) {
        }
    }

    $is_row_archived = (isset($row['record_status']) && $row['record_status'] == 2);
    $indizes_html_output = "";
    $temp_ind_array = [];
    for ($i = 1; $i <= 7; $i++) {
        $index_wert = $row["ind$i"] ?? null;
        $index_status = $row["ind{$i}_status"] ?? 0;
        if ($index_wert !== null && trim((string)$index_wert) !== '' && (string)$index_wert !== '0') {
            $class_name = "index-wert-erhalten";
            if (!$index_status) {
                $class_name = $is_row_archived ? "index-wert-archiviert-und-nicht-erhalten" : "index-wert-nicht-erhalten";
            }
            $temp_ind_array[] = '<span class="' . $class_name . '">' . htmlspecialchars(trim((string)$index_wert)) . '</span>';
        }
    }
    $indizes_html_output = !empty($temp_ind_array) ? implode('<span class="text-body-secondary mx-1">/</span>', $temp_ind_array) : '&nbsp;';

    $tr_class = '';
    if (isset($row['record_status'])) {
        if ($row['record_status'] == 0) {
            $tr_class = 'status-record-not-received';
        } elseif ($row['record_status'] == 2) {
            $tr_class = 'status-archiviert';
        }
    }

    $edit_link = "ds_aend.php?id=" . htmlspecialchars($row['id']) . "&suchsachnr=" . urlencode($suchsachnr ?? '');

    // --- START: NEUE HTML-AUSGABE DER ZEILE ---
    echo "<tr class='" . htmlspecialchars(trim($tr_class)) . "'>";

    // Zelle 1: Aktionen und Sachnummer
    echo '<td>';
    echo '    <div class="d-flex align-items-center flex-nowrap">'; // Flexbox für saubere Anordnung

    // Gruppe für die Icons (links)
    echo '        <div class="action-icons me-2">';

    // Stift (Bearbeiten)
    echo '            <a href="' . $edit_link . '" class="text-decoration-none" title="Datensatz bearbeiten"><i class="bi bi-pencil-square"></i></a>';

    // Log-Button (NEU)
    echo '            <a href="#" class="text-decoration-none ms-2" data-bs-toggle="modal" data-bs-target="#logModal" data-id="' . htmlspecialchars($row['id']) . '" title="Log-Verlauf anzeigen"><i class="bi bi-clock-history"></i></a>';

    // Löschen-Button (nur für Admins & archivierte Zeilen)
    if ($is_row_archived && ($can_edit_master_data ?? false)) {
        echo '        <form action="ds_speich.php" method="post" class="d-inline" onsubmit="return confirm(\'ACHTUNG!\n\nDieser Datensatz wird endgültig und unwiderruflich aus der Datenbank gelöscht.\n\nWirklich fortfahren?\');">';
        echo '            <input type="hidden" name="id" value="' . htmlspecialchars($row['id']) . '">';
        echo '            <input type="hidden" name="suchsachnr_return" value="' . htmlspecialchars($suchsachnr ?? '') . '">';
        echo '            <button type="submit" name="action" value="endgueltig_loeschen" class="btn btn-link p-0 ms-2" title="Endgültig löschen" style="vertical-align: baseline;"><i class="bi bi-trash3-fill text-danger"></i></button>';
        echo '        </form>';
    }

    echo '        </div>'; // Ende der Icon-Gruppe

    // Sachnummer (rechts von den Icons)
    echo '        <span>' . htmlspecialchars($row['sachnr']) . '</span>';

    echo '    </div>'; // Ende Flexbox
    echo '</td>';

    // Restliche Zellen
    echo "<td>" . htmlspecialchars($row['kurz'] ?? '') . "</td>";
    echo "<td>" . htmlspecialchars($row['aez'] ?? '') . "</td>";
    echo "<td>" . htmlspecialchars($row['dokart'] ?? '') . "</td>";
    echo "<td>" . htmlspecialchars($row['teildok'] ?? '') . "</td>";
    echo "<td>{$indizes_html_output}</td>";
    echo "<td>{$dat_formatted}</td>";
    echo "<td>" . htmlspecialchars($row['hinw'] ?? '') . "</td>";

    echo "</tr>";
}
