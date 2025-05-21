<?php
// login.php
require_once 'config.php';
require_once 'db_connect.php';

$page_title = "Anmeldung";
$error = '';
$username_val = ''; // Um den Benutzernamen im Formular bei Fehler beizubehalten

if (isset($_SESSION['user_id'])) {
    header('Location: scrolltab.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username_val = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username_val) || empty($password)) {
        $error = 'Benutzername und Passwort sind erforderlich.';
    } else {
        try {
            $stmt = $pdo->prepare("SELECT id, username, password_hash FROM users WHERE username = :username");
            $stmt->execute(['username' => $username_val]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password_hash'])) {
                // Benutzerdaten aus der Datenbank holen (inklusive Abteilung)
                $stmt_user_details = $pdo->prepare("SELECT id, username, department FROM users WHERE id = :id");
                $stmt_user_details->execute(['id' => $user['id']]);
                $user_details = $stmt_user_details->fetch();

                if ($user_details) {
                    $_SESSION['user_id'] = $user_details['id'];
                    $_SESSION['username'] = $user_details['username'];
                    $_SESSION['user_department'] = $user_details['department']; // NEU: Abteilung in Session speichern
                    log_action($pdo, 'LOGIN_SUCCESS', $user_details['id'], 'users'); // Loggt den erfolgreichen Login

                    header('Location: scrolltab.php');
                    exit;
                } else {
                    // Sollte nicht passieren, wenn $user gefunden wurde, aber als Fallback
                    $error = 'Fehler beim Laden der Benutzerdetails.';
                }
            } else {
                $error = 'Ungültiger Benutzername oder Passwort.';
            }
        } catch (PDOException $e) {
            error_log("Login Error: " . $e->getMessage());
            $error = 'Ein Fehler ist bei der Anmeldung aufgetreten.';
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

            <?php if ($error): ?>
                <div class="alert alert-danger" role="alert">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <form action="login.php" method="post" novalidate>
                <div class="mb-3">
                    <label for="username" class="form-label">Benutzername</label>
                    <input type="text" class="form-control" id="username" name="username" value="<?php echo htmlspecialchars($username_val); ?>" required>
                </div>
                <div class="mb-3">
                    <label for="password" class="form-label">Passwort</label>
                    <input type="password" class="form-control" id="password" name="password" required>
                </div>
                <button type="submit" class="btn btn-primary w-100">Anmelden</button>
            </form>
            <div class="auth-links">
                Noch kein Konto? <a href="register.php">Hier registrieren</a>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>