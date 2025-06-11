<?php
// in tabinh.php - FINALE KORREKTUR

foreach ($results as $row) {
    // Datumsformatierung
    $dat_formatted = '&nbsp;';
    if (!empty($row['dat']) && $row['dat'] !== '0000-00-00') {
        try {
            $date_obj = new DateTime($row['dat']);
            $dat_formatted = htmlspecialchars($date_obj->format('d.m.Y'));
        } catch (Exception $e) { /* bleibt leer */ }
    }

    // --- START DER NEUEN LOGIK ---

    // 1. Zuerst prüfen, ob die GANZE Zeile archiviert ist.
    $is_row_archived = (isset($row['record_status']) && $row['record_status'] == 2);

    // 2. Die Indizes durchgehen und die richtige Klasse zuweisen.
    $indizes_html_output = "";
    $temp_ind_array = [];
    for ($i = 1; $i <= 7; $i++) {
        $index_wert = $row["ind$i"] ?? null;
        $index_status = $row["ind" . $i . "_status"] ?? 0;

        if ($index_wert !== null && trim((string)$index_wert) !== '' && (string)$index_wert !== '0') {
            $class_name = "index-wert-erhalten"; // Standard (grün oder schwarz)

            if (!$index_status) { // Wenn der Index-Status "nicht erhalten" ist...
                if ($is_row_archived) {
                    // ... UND die ganze Zeile archiviert ist, dann nutze die NEUE graue Klasse.
                    $class_name = "index-wert-archiviert-und-nicht-erhalten";
                } else {
                    // ... UND die Zeile NICHT archiviert ist, nutze die bestehende orange Klasse.
                    $class_name = "index-wert-nicht-erhalten";
                }
            }
            $temp_ind_array[] = '<span class="' . $class_name . '">' . htmlspecialchars(trim((string)$index_wert)) . '</span>';
        }
    }
    $indizes_html_output = !empty($temp_ind_array) ? implode('<span class="text-body-secondary mx-1">/</span>', $temp_ind_array) : '&nbsp;';


    // 3. Die Klasse für die ganze Tabellenzeile bestimmen (orange ODER grau).
    $tr_class = '';
    if (isset($row['record_status'])) {
        if ($row['record_status'] == 0) {
            $tr_class = 'status-record-not-received'; // Orange Zeile
        } elseif ($row['record_status'] == 2) {
            $tr_class = 'status-archiviert'; // Dunkelgraue Zeile
        }
    }
    // --- ENDE DER NEUEN LOGIK ---

    $edit_link = "ds_aend.php?id=" . htmlspecialchars($row['id']) . "&suchsachnr=" . urlencode($suchsachnr ?? '');

    // Finale HTML-Ausgabe der Zeile
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