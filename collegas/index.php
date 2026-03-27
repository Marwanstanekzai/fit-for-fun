<?php
session_start();
require_once '../config/database.php';
require_once '../includes/auth.php';
checkCollega();

// Haal aankomende lessen op (vanaf vandaag)
$lessen = $pdo->query("
    SELECT l.*, 
           COUNT(r.Id) AS aantalGereserveerd
    FROM les l
    LEFT JOIN reservering r ON r.Datum = l.Datum AND r.Tijd = l.Tijd AND r.Reserveringstatus != 'Geannuleerd'
    WHERE l.Datum >= CURDATE() AND l.Beschikbaarheid != 'Geannuleerd'
    GROUP BY l.Id
    ORDER BY l.Datum, l.Tijd
    LIMIT 10
")->fetchAll();

// Statistieken voor de collega
$aantalLeden  = $pdo->query("SELECT COUNT(*) FROM lid")->fetchColumn();
$aantalLessen = $pdo->query("SELECT COUNT(*) FROM les WHERE Datum >= CURDATE()")->fetchColumn();
$aantalReserv = $pdo->query("SELECT COUNT(*) FROM reservering WHERE Reserveringstatus = 'Gereserveerd'")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Medewerker Dashboard – Fit for Fun</title>
    <style>
        /* === RESET & BASIS === */
        *{ margin:0; padding:0; box-sizing:border-box; font-family:Arial, Helvetica, sans-serif; }

        /* NAVBAR */
        .navbar{ background:#dcdcdc; min-height:110px; display:flex; justify-content:space-between; align-items:center; padding:0 80px; flex-wrap: wrap; gap: 20px; }
        .nav-left{ display:flex; align-items:center; gap:20px; }
        .logo{ height:90px; }

        @media screen and (max-width: 768px) {
            .navbar { padding: 20px; justify-content: center; text-align: center; }
            .nav-left { flex-direction: column; }
            .nav-links { flex-direction: column; gap: 15px !important; }
            .container { padding: 20px !important; }
            .stats { flex-direction: column; }
            .stat { width: 100%; }
            .table-container { overflow-x: auto; -webkit-overflow-scrolling: touch; border: 2px solid #000; border-radius: 8px; }
            table { min-width: 600px; border: none; }
            .menu-links { flex-direction: column; width: 100%; }
            .menu-links .button { width: 100%; }
        }

        /* NAV LINKS */
        .nav-links{ display:flex; align-items:center; gap:40px; font-weight:bold; }
        .nav-links a{ text-decoration:none; color:#000; font-weight:600; letter-spacing:1px; }

        /* KNOPPEN */
        .button, .login-btn{
            display:flex; align-items:center; justify-content:center; gap:10px;
            height:50px; padding:0 28px;
            background-color:#ff8c00; border-radius:40px; border:3px solid #000;
            box-shadow:0 5px 0 #1e88c9;
            font-weight:800; text-decoration:none; color:#000;
            transition:all 0.15s ease-in-out; cursor:pointer;
        }
        .button:hover, .login-btn:hover{ transform:translateY(3px); box-shadow:0 2px 0 #1e88c9; }
        .button:active, .login-btn:active{ transform:translateY(5px); box-shadow:none; }



        /* CONTAINER */
        .container { padding: 30px 80px; background: rgba(255, 255, 255, 0.95); min-height: calc(100vh - 110px); }
        h1 { color: #1F3864; margin-bottom: 20px; }
        h2 { color: #1F3864; margin-bottom: 15px; font-size:20px; }

        /* STATISTIEKEN */
        .stats { display:flex; gap:15px; margin-bottom:30px; flex-wrap:wrap; }
        .stat  { background:#f9f9f9; border:2px solid #000; border-radius:10px; padding:15px 25px; text-align:center; flex:1; min-width:120px; }
        .stat h3 { font-size:32px; color:#ff8c00; margin:0; text-shadow:1px 1px 0 #000; }
        .stat p  { color:#555; font-size:13px; margin:3px 0 0; font-weight:bold; }

        /* TABEL */
        table { width: 100%; border-collapse: collapse; background: white; border-radius: 8px; overflow: hidden; box-shadow: 0 5px 15px rgba(0,0,0,0.1); border: 2px solid #000; }
        th { background: #1F3864; color: white; padding: 12px; text-align: left; border-bottom: 2px solid #000; }
        td { padding: 12px; border-bottom: 1px solid #eee; font-size: 14px; }
        tr:hover { background: #f5f9ff; cursor:pointer; }

        /* BADGE */
        .badge { display:inline-block; padding:3px 10px; border-radius:20px; font-size:12px; font-weight:bold; }
        .badge-vol    { background:#ffe0e0; color:#c0392b; border:1px solid #c0392b; }
        .badge-vrij   { background:#d4edda; color:#155724; border:1px solid #155724; }
        .badge-bijna  { background:#fff3cd; color:#856404; border:1px solid #856404; }

        /* SNELKOPPELINGEN */
        .menu-links { margin-top: 30px; display: flex; gap: 15px; flex-wrap: wrap; }
    </style>
</head>
<body>

<!-- NAVIGATIEBALK -->
<nav class="navbar">
    <div class="nav-left">
        <img src="/img/logo.png" alt="Fit for Fun Logo" class="logo">
    </div>
    <div class="nav-links">
        Welkom, <?= htmlspecialchars($_SESSION['naam']) ?>
        <a href="/logout.php" class="login-btn">Uitloggen</a>
    </div>
</nav>

<div class="container">
    <h1>📊 Medewerker Dashboard</h1>

    <!-- STATISTIEKEN -->
    <div class="stats">
        <div class="stat"><h3><?= $aantalLeden ?></h3><p>Leden</p></div>
        <div class="stat"><h3><?= $aantalLessen ?></h3><p>Aankomende Lessen</p></div>
        <div class="stat"><h3><?= $aantalReserv ?></h3><p>Actieve Reserveringen</p></div>
    </div>

    <!-- LESSEN OVERZICHT -->
    <h2>📅 Aankomende Lessen</h2>
    <div class="table-container">
        <table>
        <thead>
            <tr>
                <th>Les</th>
                <th>Datum</th>
                <th>Tijd</th>
                <th>Bezetting</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($lessen as $les): ?>
            <?php
                $vrij  = $les['MaxAantalPersonen'] - $les['aantalGereserveerd'];
                $pct   = ($les['aantalGereserveerd'] / $les['MaxAantalPersonen']) * 100;
                $bezettingClass = $pct >= 100 ? 'badge-vol' : ($pct >= 66 ? 'badge-bijna' : 'badge-vrij');
                $bezettingTxt   = $pct >= 100 ? 'Vol!' : "{$les['aantalGereserveerd']}/{$les['MaxAantalPersonen']}";
            ?>
            <tr>
                <td><?= htmlspecialchars($les['Naam']) ?></td>
                <td><?= date('d-m-Y', strtotime($les['Datum'])) ?></td>
                <td><?= substr($les['Tijd'], 0, 5) ?></td>
                <td><span class="badge <?= $bezettingClass ?>"><?= $bezettingTxt ?></span></td>
                <td><?= htmlspecialchars($les['Beschikbaarheid'] ?? 'Ingepland') ?></td>
            </tr>
            <?php endforeach; ?>

            <?php if (empty($lessen)): ?>
            <tr><td colspan="6" style="text-align:center; color:#999; padding:20px;">Geen lessen gepland</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
    </div>

    <!-- SNELKOPPELINGEN -->
    <div class="menu-links">
        <a href="/collegas/reserveringen.php" class="button">📋 Reserveringen bekijken</a>
    </div>
</div>

</body>
</html>
