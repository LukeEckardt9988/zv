<?php
// tabinh.php (angepasst für Bootstrap und Index-Status-Styling)

// Dieses Skript erwartet, dass $results (Array von PDO fetchAll)
// und $suchsachnr (für die Bearbeitungslinks) von der einbindenden Datei bereitgestellt werden.

if (!isset($results) || !is_array($results)) {
    echo "<tr><td colspan='8' class='text-center p-3 fst-italic text-body-secondary'>Keine Daten zum Laden vorhanden.</td></tr>";
    return;
}
if (empty($results)) {
    echo "<tr><td colspan='8' class='text-center p-3 fst-italic text-body-secondary'>Keine passenden Datensätze gefunden.</td></tr>";
    return;
}

foreach ($results as $row) {
    $dat_formatted = '&nbsp;';
    if (!empty($row['dat']) && $row['dat'] !== '0000-00-00') {
        try {
            $date_obj = new DateTime($row['dat']);
            $dat_formatted = htmlspecialchars($date_obj->format('d.m.Y'));
        } catch (Exception $e) {
            // Datum konnte nicht geparst werden, Standardwert bleibt
        }
    }

    // NEU: Logik zur Erstellung des HTML-Strings für Indizes mit Status-Styling
    $indizes_html_output = "";
    $temp_ind_array = [];
    for ($i = 1; $i <= 7; $i++) {
        $index_wert = $row["ind$i"] ?? null; // [from tabinh.php]
        $index_status = $row["ind" . $i . "_status"] ?? 0; // Default auf 0 (nicht erhalten), falls nicht vorhanden

        if ($index_wert !== null && trim((string)$index_wert) !== '' && (string)$index_wert !== '0') { // [from tabinh.php]
            $class_name = "index-wert-erhalten"; // Standard-Klasse
            if (!$index_status) { // Wenn Status 0 oder NULL (nicht erhalten)
                $class_name = "index-wert-nicht-erhalten"; // Klasse für orange Farbe
            }
            // Füge das HTML für den Index mit der entsprechenden Klasse zum Array hinzu
            $temp_ind_array[] = '<span class="' . $class_name . '">' . htmlspecialchars(trim((string)$index_wert)) . '</span>';
        }
    }
    $indizes_html_output = !empty($temp_ind_array) ? implode('<span class="text-body-secondary mx-1">/</span>', $temp_ind_array) : '&nbsp;';

    $tr_class = '';
    if (isset($row['record_status']) && $row['record_status'] == 0) {
        $tr_class = 'status-record-not-received'; // Klasse für orangefarbenen Hintergrund
    }

    $edit_link = "ds_aend.php?id=" . htmlspecialchars($row['id']) . "&suchsachnr=" . urlencode($suchsachnr ?? '');

    // Die Klasse der <tr> hinzufügen
    echo "<tr class='" . htmlspecialchars(trim($tr_class)) . "'> 
            <td><a href='" . $edit_link . "' class='text-decoration-none me-2' title='Datensatz bearbeiten'><i class='bi bi-pencil-square'></i></a> " . htmlspecialchars($row['sachnr']) . "</td>
            <td>" . htmlspecialchars($row['kurz'] ?? '') . "</td>
            <td>" . htmlspecialchars($row['aez'] ?? '') . "</td>
            <td>" . htmlspecialchars($row['dokart'] ?? '') . "</td>
            <td>" . htmlspecialchars($row['teildok'] ?? '') . "</td>
            <td>{$indizes_html_output}</td>
            <td>{$dat_formatted}</td>
            <td>" . htmlspecialchars($row['hinw'] ?? '') . "</td>
          </tr>";
}
