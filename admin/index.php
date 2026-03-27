<?php
session_start();
require_once '../config/database.php';
require_once '../includes/auth.php';
checkAdmin();

// Haal statisiteken op voor het dashboard (hoeveel leden, lessen etc)
$aantalLeden     = $pdo->query("SELECT COUNT(*) FROM lid")->fetchColumn();
$aantalLessen    = $pdo->query("SELECT COUNT(*) FROM les")->fetchColumn();
$aantalReserv    = $pdo->query("SELECT COUNT(*) FROM reservering")->fetchColumn();
$aantalMedew     = $pdo->query("SELECT COUNT(*) FROM medewerker")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard – Fit for Fun</title>
    <style>
        /* === NIEUWE OPMAAK VAN DE GEBRUIKER MET UITLEG === */

        /* RESET: Zorgt ervoor dat alle standaard browser marges weg zijn */
        *{ margin:0; padding:0; box-sizing:border-box; font-family:Arial, Helvetica, sans-serif; }
         
        /* Blur class voor modale vensters (bijv popups) in de toekomst */
        .page-content.blur{ filter:blur(8px); transition:0.3s ease; }
         
        /* NAVBAR: Opmaak voor de navigatiebalk bovenaan */
        .navbar{ background:#dcdcdc; min-height:110px; display:flex; justify-content:space-between; align-items:center; padding:0 80px; flex-wrap: wrap; gap: 20px; }
        .nav-left{ display:flex; align-items:center; gap:20px; }
        .logo{ height:90px; }
        .icon{ display:block; }

        @media screen and (max-width: 768px) {
            .navbar { padding: 20px; justify-content: center; text-align: center; }
            .nav-left { flex-direction: column; }
            .nav-links { flex-direction: column; gap: 15px !important; }
            .container { padding: 20px !important; }
            .kaarten { gap: 10px !important; }
            .kaart { min-width: 100% !important; }
            .menu-links { flex-direction: column; width: 100%; }
            .menu-links .button { width: 100%; }
        }
         
        /* NAV LINKS: Hoe de linkjes in de navigatie (zoals uitloggen) erbij staan */
        .nav-links{ display:flex; align-items:center; gap:40px; font-weight:bold; }
        .nav-links a{ text-decoration:none; color:#000; font-weight:600; letter-spacing:1px; }
         
        /* BUTTON STYLE: Een stoere oranje/blauwe 3D knop */
        .button, .login-btn{
            display:flex; align-items:center; justify-content:center; gap:10px;
            height:50px; padding:0 28px;
            background-color:#ff8c00; border-radius:40px; border:3px solid #000;
            box-shadow:0 5px 0 #1e88c9; /* Zorgt voor het "eruit springen" van de rand */
            font-weight:800; text-decoration:none; color:#000;
            transition:all 0.15s ease-in-out; cursor:pointer;
        }
        /* Hover effect: Wat er gebeurd als je muis over de knop zit */
        .button:hover, .login-btn:hover{ transform:translateY(3px); box-shadow:0 2px 0 #1e88c9; }
        /* Active effect: De knop indrukken (gaat omlaag, schaduw verdwijnt) */
        .button:active, .login-btn:active{ transform:translateY(5px); box-shadow:none; }

     

        /* === BESTAANDE OPMAAK AANGEPAST VOOR DASHBOARD === */
        .container { padding: 30px 80px; background: rgba(255, 255, 255, 0.95); min-height: calc(100vh - 110px); }
        h1 { color: #1F3864; margin-bottom: 20px;}
        
        /* De kaarten waarop de cijfers staan */
        .kaarten { display: flex; gap: 20px; margin-top: 20px; flex-wrap: wrap; }
        .kaart { background: #f9f9f9; padding: 25px; border-radius: 10px; border: 2px solid #000; box-shadow: 0 5px 15px rgba(0,0,0,0.1); flex: 1; min-width: 150px; text-align: center; }
        .kaart h2 { font-size: 40px; color: #ff8c00; margin: 0; text-shadow: 1px 1px 0 #000; }
        .kaart p { color: #555; margin: 5px 0 0; font-weight: bold; }
        
        /* Menu knoppen onderin het dashboard */
        .menu-links { margin-top: 30px; display: flex; gap: 15px; flex-wrap: wrap; }
    </style>
</head>
<body>

<!-- De vernieuwde navigatiebalk -->
<nav class="navbar">
    <div class="nav-left">
        <img src="/img/logo.png" alt="Fit for Fun Logo" class="logo">
    </div>

    <!-- Rechter menu links (gebruikt de nieuwe login-btn style class) -->
    <div class="nav-links">
        Welkom, <?= htmlspecialchars($_SESSION['naam']) ?>
        <a href="/logout.php" class="login-btn">Uitloggen</a>
    </div>
</nav>

<div class="container">
    <h1>Dashboard</h1>
    <div class="kaarten">
        <div class="kaart"><h2><?= $aantalLeden ?></h2><p>Leden</p></div>
        <div class="kaart"><h2><?= $aantalLessen ?></h2><p>Lessen</p></div>
        <div class="kaart"><h2><?= $aantalReserv ?></h2><p>Reserveringen</p></div>
        <div class="kaart"><h2><?= $aantalMedew ?></h2><p>Medewerkers</p></div>
    </div>
    
    <div class="menu-links">
        <a href="/admin/leden.php"         class="button">👥 Leden beheren</a>
        <a href="/admin/lessen.php"        class="button">📅 Lessen beheren</a>
        <a href="/admin/reserveringen.php" class="button">📋 Reserveringen beheren</a>
        <a href="/admin/medewerkers.php"   class="button">👤 Medewerkers beheren</a>
    </div>
</div>

</body>
</html>