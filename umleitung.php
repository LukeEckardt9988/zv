<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="refresh" content="15;url=http://ubserv01.saalfeld.epsa.intern/zv/index.php">
    <title>Seite abgeschaltet - Weiterleitung</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap');

        body {
            font-family: 'Roboto', -apple-system, BlinkMacSystemFont, "Segoe UI", "Helvetica Neue", Arial, sans-serif;
            background-color: #f0f2f5;
            color: #333;
            margin: 0;
            padding: 20px;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            text-align: center;
        }

        .container {
            background-color: #ffffff;
            padding: 40px 50px;
            border-radius: 12px;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.1);
            max-width: 600px;
            width: 100%;
            border-top: 5px solid #d93025; /* Rote Akzentfarbe für "Warnung" */
        }

        h1 {
            color: #d93025;
            font-size: 24px;
            margin-bottom: 15px;
        }

        p {
            font-size: 16px;
            line-height: 1.6;
            margin: 15px 0;
        }
        
        .countdown-text {
            color: #555;
            font-size: 14px;
        }

        .link, a {
            color: #007bff;
            font-weight: bold;
            text-decoration: none;
            word-break: break-all;
        }

        a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>

    <div class="container">
        <h1>Wichtiger Hinweis: Seite abgeschaltet</h1>
        
        <p>
            Vielen Dank für die Nutzung dieses Systems. Diese Seite existiert nicht mehr und wird nicht länger gewartet.
        </p>
        
        <p>
            Sie werden automatisch zur neuen Zeichnungsverwaltung weitergeleitet. Die neue Adresse lautet:
            <br>
            <a class="link" href="http://ubserv01.saalfeld.epsa.intern/zv/index.php">ubserv01.saalfeld.epsa.intern/zv/index.php</a>
        </p>

        <p class="countdown-text">
            Weiterleitung in <span id="countdown">7</span> Sekunden...
        </p>
        
        <hr style="border: none; border-top: 1px solid #eee; margin: 30px 0;">

        <p>
            Bei Problemen mit der Anmeldung auf der neuen Seite senden Sie bitte eine E-Mail an:
            <br>
            <a class="link" href="mailto:luke.eckardt@ihre-firma.de">Luke Eckardt</a>
        </p>
    </div>

    <script>
        // Kleines Skript für den Countdown-Zähler
        let seconds = 15;
        const countdownElement = document.getElementById('countdown');

        const interval = setInterval(() => {
            seconds--;
            if (countdownElement) {
                countdownElement.textContent = seconds;
            }
            if (seconds <= 0) {
                clearInterval(interval);
            }
        }, 1000);
    </script>

</body>
</html>