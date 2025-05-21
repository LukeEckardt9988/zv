<?php
// register.php
require_once 'config.php';
require_once 'db_connect.php';

$page_title = "Benutzerregistrierung";
$errors = [];
$success_message = '';
// Werte für das Formular vorbefüllen, falls ein Fehler auftritt
$username_val = '';
$email_val = '';
$department_val = ''; // NEU

// Abteilungsoptionen definieren
$department_options = [
    '' => 'Bitte Abteilung wählen...', // Leere Option
    'PP' => 'PP',
    'PG' => 'PG',
    'PF' => 'PF',
    'PV' => 'PV'
    // Fügen Sie hier bei Bedarf weitere Abteilungen hinzu
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username_val = trim($_POST['username'] ?? ''); // Für Formular-Prefill
    $email_val = trim($_POST['email'] ?? '');     // Für Formular-Prefill
    $department_val = trim($_POST['department'] ?? ''); // NEU: Für Formular-Prefill

    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $guest_password_attempt = $_POST['guest_password'] ?? '';

    if (empty($username_val)) $errors[] = 'Benutzername ist erforderlich.';
    if (empty($department_val)) $errors[] = 'Abteilung ist erforderlich.'; // NEUE Validierung
    if (empty($password)) $errors[] = 'Passwort ist erforderlich.';
    if ($password !== $confirm_password) $errors[] = 'Passwörter stimmen nicht überein.';
    if (strlen($password) < 8 && !empty($password)) $errors[] = 'Passwort muss mindestens 8 Zeichen lang sein.';
    if (empty($guest_password_attempt)) $errors[] = 'Gast-Zugangscode ist erforderlich.';
    elseif ($guest_password_attempt !== GUEST_PASSWORD) $errors[] = 'Ungültiger Gast-Zugangscode.';
    if (!empty($email_val) && !filter_var($email_val, FILTER_VALIDATE_EMAIL)) $errors[] = 'Ungültige E-Mail-Adresse.';
    // NEU: Prüfen, ob eine gültige Abteilung ausgewählt wurde (optional, aber gut)
    if (!empty($department_val) && !array_key_exists($department_val, $department_options)) {
        $errors[] = 'Ungültige Abteilung ausgewählt.';
    }


    if (empty($errors)) {
        try {
            // Prüfen, ob Benutzername oder E-Mail bereits existiert
            // Die Abfrage muss ggf. angepasst werden, wenn E-Mail nicht unique sein soll und leer sein darf
            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = :username OR (email = :email AND email IS NOT NULL AND email != '')");
            $stmt->execute(['username' => $username_val, 'email' => $email_val ?: null]);
            if ($stmt->fetch()) {
                $errors[] = 'Benutzername oder E-Mail ist bereits vergeben.';
            } else {
                $password_hash = password_hash($password, PASSWORD_DEFAULT);
                // NEU: department in SQL-Query und Parameter aufnehmen
                $stmt = $pdo->prepare("INSERT INTO users (username, password_hash, email, department) VALUES (:username, :password_hash, :email, :department)");
                $stmt->execute([
                    'username' => $username_val,
                    'password_hash' => $password_hash,
                    'email' => $email_val ?: null,
                    'department' => $department_val // NEUER Parameter
                ]);
                $success_message = 'Registrierung erfolgreich! Sie können sich nun <a href="login.php" class="alert-link">anmelden</a>.';
                // Formularwerte zurücksetzen nach Erfolg
                $username_val = $email_val = $department_val = '';
            }
        } catch (PDOException $e) {
            error_log("Registration Error: " . $e->getMessage());
            $errors[] = 'Ein Fehler ist bei der Registrierung aufgetreten. Bitte versuchen Sie es später erneut.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="de" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title) . " - " . htmlspecialchars(APP_TITLE); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Maven+Pro:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="custom_styles.css" rel="stylesheet">
</head>
<body>
    <div class="container">
        <div class="auth-container">
            <h2 class="auth-title"><?php echo htmlspecialchars($page_title); ?></h2>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger" role="alert">
                    <h4 class="alert-heading">Fehler!</h4>
                    <ul class="mb-0">
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <?php if ($success_message): ?>
                <div class="alert alert-success" role="alert">
                    <?php echo $success_message; // Enthält bereits HTML für Link ?>
                </div>
            <?php else: // Formular nur anzeigen, wenn keine Erfolgsmeldung da ist ?>
                <form action="register.php" method="post" novalidate>
                    <div class="mb-3">
                        <label for="username" class="form-label">Benutzername</label>
                        <input type="text" class="form-control" id="username" name="username" value="<?php echo htmlspecialchars($username_val); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label">E-Mail (Optional)</label>
                        <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($email_val); ?>">
                    </div>

                    <div class="mb-3">
                        <label for="department" class="form-label">Abteilung</label>
                        <select class="form-select" id="department" name="department" required>
                            <?php foreach ($department_options as $value => $label): ?>
                                <option value="<?php echo htmlspecialchars($value); ?>" <?php if ($department_val === $value) echo 'selected'; ?>>
                                    <?php echo htmlspecialchars($label); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Passwort (min. 8 Zeichen)</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                    <div class="mb-3">
                        <label for="confirm_password" class="form-label">Passwort bestätigen</label>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                    </div>
                    <div class="mb-3">
                        <label for="guest_password" class="form-label">Gast-Zugangscode</label>
                        <input type="password" class="form-control" id="guest_password" name="guest_password" required>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Registrieren</button>
                </form>
            <?php endif; ?>

            <div class="auth-links">
                Bereits registriert? <a href="login.php">Hier anmelden</a>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>