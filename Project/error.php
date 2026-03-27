<?php
session_start();
$code = $_GET['code'] ?? '500';
$msg  = $_GET['msg'] ?? 'Er is een onbekende fout opgetreden.';

$titles = [
    '403' => 'Toegang Geweigerd',
    '404' => 'Pagina Niet Gevonden',
    '500' => 'Systeemfout',
    'db'  => 'Database Verbindingsfout'
];

$descriptions = [
    '403' => 'Je hebt niet de juiste rechten om deze pagina te bekijken.',
    '404' => 'De opgevraagde pagina is niet beschikbaar of verplaatst.',
    '500' => 'Er is iets misgegaan op onze server. Probeer het later opnieuw.',
    'db'  => 'Het systeem kan momenteel geen verbinding maken met de database. Controleer of de database server draait.'
];

$title = $titles[$code] ?? 'Foutmelding';
$desc  = $descriptions[$code] ?? $msg;
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?> – Fit for Fun</title>
    <style>
        *{ margin:0; padding:0; box-sizing:border-box; font-family:Arial, Helvetica, sans-serif; }
        body { background:#f4f4f4; display:flex; justify-content:center; align-items:center; height:100vh; padding:20px; text-align:center; }
        .error-container { background:white; padding:40px; border-radius:20px; border:3px solid #000; box-shadow:0 10px 30px rgba(0,0,0,0.1); max-width:500px; width:100%; }
        .error-code { font-size:60px; font-weight:900; color:#ff8c00; margin-bottom:10px; text-shadow:2px 2px 0 #000; }
        h1 { color:#1F3864; margin-bottom:20px; }
        p { color:#555; margin-bottom:30px; line-height:1.6; }
        .button {
            display:inline-flex; align-items:center; justify-content:center;
            height:50px; padding:0 30px;
            background-color:#ff8c00; border-radius:40px; border:3px solid #000;
            box-shadow:0 5px 0 #1e88c9;
            font-weight:800; text-decoration:none; color:#000;
            transition:all 0.15s ease-in-out;
        }
        .button:hover { transform:translateY(3px); box-shadow:0 2px 0 #1e88c9; }
        .button:active { transform:translateY(5px); box-shadow:none; }
    </style>
</head>
<body>
    <div class="error-container">
        <div class="error-code"><?= is_numeric($code) ? $code : '!' ?></div>
        <h1><?= htmlspecialchars($title) ?></h1>
        <p><?= htmlspecialchars($desc) ?></p>
        <a href="/" class="button">Terug naar Home</a>
    </div>
</body>
</html>
