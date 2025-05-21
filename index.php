<?php
// index.php
require_once 'config.php'; // Stellt sicher, dass die Session gestartet wird

// Überprüfen, ob der Benutzer bereits angemeldet ist
if (isset($_SESSION['user_id'])) {
    header('Location: scrolltab.php');
    exit;
}

// Wenn nicht angemeldet, zeige Links zu Login und Registrierung
$page_title = "Willkommen";
?>
<!DOCTYPE html>
<html lang="de" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title) . " - " . htmlspecialchars(APP_TITLE); // ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Maven+Pro:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="custom_styles.css" rel="stylesheet">
    <style>
        /* Zusätzliche Styles für die Index-Seite für ein zentriertes Layout */
        .welcome-container {
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            min-height: 80vh; /* Nimmt fast den gesamten Viewport-Bereich ein */
            text-align: center;
        }
        .welcome-actions a {
            margin: 0.5rem;
            min-width: 150px; /* Mindestbreite für die Buttons */
        }
        .welcome-title {
            color: var(--app-accent-color); /* */
            margin-bottom: 2rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="welcome-container">
            <h1 class="welcome-title display-4"><?php echo htmlspecialchars(APP_TITLE); // ?></h1>
            <p class="lead mb-4">Bitte melden Sie sich an oder registrieren Sie sich, um fortzufahren.</p>
            <div class="welcome-actions">
                <a href="login.php" class="btn btn-primary btn-lg">Anmelden</a>
                <a href="register.php" class="btn btn-secondary btn-lg">Registrieren</a>
            </div>
        </div>
    </div>

    <footer class="text-center text-body-secondary py-3 mt-auto">
        <small>&copy; <?php echo date("Y"); ?> EPSa. Alle Rechte vorbehalten.</small>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>