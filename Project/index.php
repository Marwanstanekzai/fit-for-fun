<?php
session_start();

// Als je al bent ingelogd, sturen we je direct naar het goede dashboard!
if (isset($_SESSION['rol'])) {
    if ($_SESSION['rol'] === 'admin') {
        header("Location: /admin/index.php");
    } else {
        header("Location: /collegas/index.php");
    }
    exit();
}

require_once 'config/database.php';
$fout = '';

// Check of het formulier gesubmit is
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username   = trim($_POST['username']);
    $wachtwoord = $_POST['wachtwoord'];

    // Zoek de gebruiker op basis van de Gebruikersnaam
    $stmt = $pdo->prepare("SELECT g.*, r.Naam as rol_naam FROM gebruiker g JOIN rol r ON g.Id = r.GebruikerId WHERE g.Gebruikersnaam = ?");
    $stmt->execute([$username]);
    $gebruiker = $stmt->fetch();

    // Vergelijk het wachtwoord (nu even platte tekst op basis van de nieuwe SQL)
    if ($gebruiker && $wachtwoord === $gebruiker['Wachtwoord']) {
        $_SESSION['gebruiker_id'] = $gebruiker['Id'];
        $_SESSION['naam']         = $gebruiker['Voornaam'];
        $_SESSION['rol']          = strtolower($gebruiker['rol_naam']);

        // Stuur admins naar admin/ en de rest naar collegas/
        if ($_SESSION['rol'] === 'administrator') {
            $_SESSION['rol'] = 'admin'; // We houden de interne rol naam 'admin' voor compatibiliteit
            header("Location: /admin/index.php");
        } else {
            $_SESSION['rol'] = 'collega';
            header("Location: /collegas/index.php");
        }
        exit();
    } else {
        $fout = "Ongeldige gebruikersnaam of wachtwoord.";
    }
}
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inloggen – Fit for Fun</title>
    <style>
        /* === LOGIN STYLING OP BASIS VAN "LOGIN MODAL" === */

        /* RESET: Zorgt ervoor dat alle standaard browser marges weg zijn */
        *{ margin:0; padding:0; box-sizing:border-box; font-family:Arial, Helvetica, sans-serif; }
        
        /* Achtergrond centreren over scherm */
        body { 
            background-size: 100% 100%;
            background-color: #333;
            display: flex; justify-content: center; align-items: center; 
            height: 100vh; margin: 0; 
        }

        /* LOGIN MODAL: Het zwevende venster in het midden */
        .login-modal {
            background:#ffffff;
            padding:40px;
            width:90%;
            max-width:350px;
            border-radius:20px;
            text-align:center;
            border: 3px solid #000;
            box-shadow:0 20px 40px rgba(0,0,0,0.6); /* Iets zwaardere schaduw voor contrast met donkere achtergrond */
            /* Animatie genaamd pop speelt direct na inladen in 0.3 sec af */
            animation:pop 0.3s ease;
        }

        /* KEYFRAMES voor een leuke pop-up animatie */
        @keyframes pop{
            from{ transform:scale(0.8); opacity:0; }
            to{ transform:scale(1); opacity:1; }
        }

        .login-modal h2 { margin-bottom:20px; color:#1F3864; font-size:28px; }
        
        /* De input veldjes in de modal layout (van de styling) */
        .login-modal input {
            width:100%;
            height:45px;
            margin-bottom:15px;
            padding:0 15px;
            border-radius:30px;
            border:2px solid #000;
            outline:none;
            font-size: 16px;
        }

        /* BUTTON STYLE: Toegepast op de login button */
        .modal-btn {
            width:100%;
            margin-top:10px;
            display:flex; align-items:center; justify-content:center; gap:10px;
            height:50px; padding:0 28px;
            background-color:#ff8c00; border-radius:40px; border:3px solid #000;
            box-shadow:0 5px 0 #1e88c9; 
            font-weight:800; font-size:16px; color:#000;
            transition:all 0.15s ease-in-out; cursor:pointer;
        }
        .modal-btn:hover { transform:translateY(3px); box-shadow:0 2px 0 #1e88c9; }
        .modal-btn:active { transform:translateY(5px); box-shadow:none; }
        
        /* Foutmelding vormgeving */
        .fout { background: #ffe0e0; color: #c0392b; padding: 10px; border-radius: 6px; margin-bottom: 15px; text-align: center; font-weight:bold; border:1px solid #c0392b; }
    </style>
</head>
<body>

    <!-- De login-box met de style van de nieuwe modal -->
    <div class="login-modal">
        <img src="/img/logo.png" alt="Fit for Fun Logo" style="height: 100px; margin-bottom: 20px;">
        
        <!-- Fout weergave (indien wachtwoord verkeerd is) -->
        <?php if ($fout): ?>
            <div class="fout"><?= htmlspecialchars($fout) ?></div>
        <?php endif; ?>
        
        <!-- Login Formulier -->
        <form method="POST">
            <!-- Gebruikersnaam Invoer -->
            <input type="text" name="username" required placeholder="Gebruikersnaam">
            
            <!-- Wachtwoord Invoer (gebruikt puntjes door type="password") -->
            <input type="password" name="wachtwoord" required placeholder="Wachtwoord">
            
            <!-- Inloggen knop -->
            <button type="submit" class="modal-btn">Inloggen</button>
        </form>
    </div>

</body>
</html>