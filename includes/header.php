<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Bepaal het pad naar de root
$is_subfolder = (strpos($_SERVER['PHP_SELF'], '/admin/') !== false || strpos($_SERVER['PHP_SELF'], '/collegas/') !== false);
$root_path = $is_subfolder ? '../' : './';

// Database verbinding
require_once __DIR__ . '/../config/Database.php';
$fout = '';

// Check of het formulier gesubmit is via de modal (kan vanaf elke pagina)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login_submit'])) {
    $username   = trim($_POST['username']);
    $wachtwoord = $_POST['wachtwoord'];

    // Zoek de gebruiker op basis van de Gebruikersnaam
    $stmt = $pdo->prepare("SELECT g.*, r.Naam as rol_naam FROM gebruiker g JOIN rol r ON g.Id = r.GebruikerId WHERE g.Gebruikersnaam = ?");
    $stmt->execute([$username]);
    $gebruiker = $stmt->fetch();

    if ($gebruiker) {
        $is_valid = false;
        // BCrypt ondersteuning
        if (strpos($gebruiker['Wachtwoord'], '$2y$') === 0) {
            $is_valid = password_verify($wachtwoord, $gebruiker['Wachtwoord']);
        } else {
            $is_valid = ($wachtwoord === $gebruiker['Wachtwoord']);
        }

        if ($is_valid) {
            $_SESSION['gebruiker_id'] = $gebruiker['Id'];
            $_SESSION['naam']         = $gebruiker['Voornaam'];
            $rol = strtolower($gebruiker['rol_naam']);
            
            if ($rol === 'administrator') {
                $_SESSION['rol'] = 'admin';
                header("Location: " . $root_path . "admin/index.php");
            } else {
                $_SESSION['rol'] = 'collega';
                header("Location: " . $root_path . "collegas/index.php");
            }
            exit();
        } else {
            $fout = "Ongeldig wachtwoord.";
        }
    } else {
        $fout = "Gebruiker niet gevonden.";
    }
}
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FitForFun</title>
    <link rel="stylesheet" href="<?= $root_path ?>style.css">
</head>
<body>

<div class="page-content">

<header class="navbar">
    <div class="nav-left">
        <a href="<?= $root_path ?>index.php">
            <img src="<?= $root_path ?>img/FFF.Logo.png" alt="FitForFun Logo" class="logo">
        </a>
        <div class="search-bar">
            <img src="<?= $root_path ?>icon/search.png" class="icon search-icon">
            <input type="text" placeholder="Zoeken...">
        </div>
    </div>
    
    <nav class="nav-links">
        <a href="<?= $root_path ?>index.php">HOME</a>
        <a href="<?= $root_path ?>rooster.php">ROOSTER</a>
        <a href="<?= $root_path ?>aanbiedingen.php">AANBIEDINGEN</a>
        <a href="<?= $root_path ?>informatie.php">INFO</a>

        <?php if (isset($_SESSION['rol'])): ?>
            <?php 
                $dashboard_link = ($_SESSION['rol'] === 'admin') ? 'admin/index.php' : 'collegas/index.php';
            ?>
            <a href="<?= $root_path . $dashboard_link ?>" class="button">DASHBOARD</a>
            <a href="<?= $root_path ?>logout.php" class="login-btn">
                UITLOGGEN
                <img src="<?= $root_path ?>icon/profile.png" class="icon profile-icon">
            </a>
        <?php else: ?>
            <button class="login-btn" id="openLogin">
                INLOGGEN
                <img src="<?= $root_path ?>icon/profile.png" class="icon profile-icon">
            </button>
        <?php endif; ?>
    </nav>
</header>