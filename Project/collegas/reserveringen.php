<?php
session_start();
require_once '../config/database.php';
require_once '../includes/auth.php';
checkCollega();

// Zoekterm ophalen
$zoekterm = $_GET['zoek'] ?? '';

// Reserveringen ophalen (read-only voor medewerkers)
// Reserveringen ophalen (read-only voor medewerkers)
if ($zoekterm !== '') {
    $stmt = $pdo->prepare("
        SELECT r.*,
               l.Email AS lid_email,
               l.Mobiel AS lid_mobiel
        FROM reservering r
        LEFT JOIN lid l ON r.Nummer = l.Relatienummer
        WHERE r.Voornaam LIKE ?
           OR r.Achternaam LIKE ?
           OR CONCAT(r.Voornaam, ' ', r.Achternaam) LIKE ?
           OR r.Reserveringstatus LIKE ?
        ORDER BY r.Datum DESC, r.Tijd
    ");
    $tLike = '%' . $zoekterm . '%';
    $stmt->execute([$tLike, $tLike, $tLike, $tLike]);
    $reserveringen = $stmt->fetchAll();
} else {
    $reserveringen = $pdo->query("
        SELECT r.*,
               l.Email AS lid_email,
               l.Mobiel AS lid_mobiel
        FROM reservering r
        LEFT JOIN lid l ON r.Nummer = l.Relatienummer
        ORDER BY r.Datum DESC, r.Tijd
    ")->fetchAll();
}

// Statistieken (zichtbaar voor medewerkers)
$totaal       = $pdo->query("SELECT COUNT(*) FROM reservering")->fetchColumn();
$gereserveerd = $pdo->query("SELECT COUNT(*) FROM reservering WHERE Reserveringstatus = 'Gereserveerd'")->fetchColumn();
$aanwezig     = $pdo->query("SELECT COUNT(*) FROM reservering WHERE Reserveringstatus = 'Aanwezig'")->fetchColumn();
$geannuleerd  = $pdo->query("SELECT COUNT(*) FROM reservering WHERE Reserveringstatus = 'Geannuleerd'")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reserveringen Overzicht – Fit for Fun</title>
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
            .search-bar { width: 100% !important; margin: 10px 0; }
            .nav-links { flex-direction: column; gap: 15px !important; }
            .container { padding: 20px !important; }
            .stats { flex-direction: column; gap: 10px !important; }
            .stat { width: 100%; }
            .table-container { overflow-x: auto; -webkit-overflow-scrolling: touch; border: 2px solid #000; border-radius: 8px; }
            table { min-width: 700px; border: none; }
        }

        /* ZOEKBALK */
        .search-bar{ display:flex; align-items:center; height:50px; width:320px; background:#e9e9e9; border:2px solid #000; border-radius:40px; padding:0 20px; }
        .search-bar input{ border:none; background:transparent; outline:none; flex:1; font-size:16px; }

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

        /* STATISTIEKEN */
        .stats { display:flex; gap:15px; margin-bottom:20px; flex-wrap:wrap; }
        .stat  { background:#f9f9f9; border:2px solid #000; border-radius:10px; padding:15px 25px; text-align:center; flex:1; min-width:110px; }
        .stat h3 { font-size:28px; color:#ff8c00; margin:0; text-shadow:1px 1px 0 #000; }
        .stat p  { color:#555; font-size:13px; margin:3px 0 0; font-weight:bold; }

        /* TABEL */
        table { width: 100%; border-collapse: collapse; background: white; border-radius: 8px; overflow: hidden; box-shadow: 0 5px 15px rgba(0,0,0,0.1); border: 2px solid #000; }
        th { background: #1F3864; color: white; padding: 12px; text-align: left; border-bottom: 2px solid #000; }
        td { padding: 11px 12px; border-bottom: 1px solid #eee; font-size: 14px; }
        tr:hover { background: #f5f9ff; }

        /* STATUS BADGES */
        .badge { display:inline-block; padding:3px 10px; border-radius:20px; font-size:12px; font-weight:bold; }
        .badge-gereserveerd { background:#d4edda; color:#155724; border:1px solid #155724; }
        .badge-geannuleerd  { background:#ffe0e0; color:#c0392b; border:1px solid #c0392b; }
        .badge-aanwezig     { background:#cce5ff; color:#004085; border:1px solid #004085; }
        .badge-afwezig      { background:#fff3cd; color:#856404; border:1px solid #856404; }

        /* OVERIGE */
        .terug { display:inline-block; margin-bottom:20px; color:#1F3864; text-decoration:none; font-size:14px; font-weight:bold; }
        .terug:hover { text-decoration:underline; }

        .info-note { background:#e8f4fd; border:1px solid #b3d7f5; color:#004085; padding:10px 15px; border-radius:6px; margin-bottom:15px; font-size:14px; }

        .wis-link { color:#c0392b; margin-bottom:15px; display:inline-block; text-decoration:none; font-weight:bold; font-size:14px; }
        .wis-link:hover { text-decoration:underline; }
    </style>
</head>
<body>

<!-- NAVIGATIEBALK -->
<nav class="navbar">
    <div class="nav-left">
        <img src="/img/logo.png" alt="Fit for Fun Logo" class="logo">
        <form method="GET" class="search-bar">
            <input type="text" name="zoek" placeholder="Zoek op naam of status..." value="<?= htmlspecialchars($zoekterm) ?>">
            <button type="submit" style="background:transparent; border:none; cursor:pointer; font-size:18px;">🔍</button>
        </form>
    </div>
    <div class="nav-links">
        Welkom, <?= htmlspecialchars($_SESSION['naam']) ?>
        <a href="/logout.php" class="login-btn">Uitloggen</a>
    </div>
</nav>

<div class="container">
    <a class="terug" href="/collegas/index.php">← Terug naar dashboard</a>

    <?php if ($zoekterm !== ''): ?>
        <h1>📋 Reserveringen – gezocht op: "<?= htmlspecialchars($zoekterm) ?>"</h1>
        <a class="wis-link" href="/collegas/reserveringen.php">❌ Zoekopdracht wissen</a>
    <?php else: ?>
        <h1>📋 Reserveringen Overzicht</h1>
    <?php endif; ?>

    <!-- STATISTIEKEN -->
    <?php if ($zoekterm === ''): ?>
    <div class="stats">
        <div class="stat"><h3><?= $totaal ?></h3><p>Totaal</p></div>
        <div class="stat"><h3><?= $gereserveerd ?></h3><p>Gereserveerd</p></div>
        <div class="stat"><h3><?= $aanwezig ?></h3><p>Aanwezig</p></div>
        <div class="stat"><h3><?= $geannuleerd ?></h3><p>Geannuleerd</p></div>
    </div>
    <?php endif; ?>

    <div class="info-note">ℹ️ Als medewerker kun je reserveringen alleen <strong>bekijken</strong>. Neem contact op met de administrator voor wijzigingen.</div>

    <!-- TABEL: OVERZICHT VAN RESERVERINGEN -->
    <div class="table-container">
        <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Lid</th>
                <th>Mobiel</th>
                <th>Datum</th>
                <th>Tijd</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($reserveringen as $res): ?>
            <tr>
                <td><?= $res['Id'] ?></td>
                <td><?= htmlspecialchars($res['Voornaam'] . ' ' . $res['Achternaam']) ?></td>
                <td><?= htmlspecialchars($res['lid_mobiel'] ?? '-') ?></td>
                <td><?= date('d-m-Y', strtotime($res['Datum'])) ?></td>
                <td><?= substr($res['Tijd'], 0, 5) ?></td>
                <td>
                    <?php
                        $badgeClass = match($res['Reserveringstatus']) {
                            'Gereserveerd' => 'badge-gereserveerd',
                            'Geannuleerd'  => 'badge-geannuleerd',
                            'Aanwezig'     => 'badge-aanwezig',
                            'Afwezig'      => 'badge-afwezig',
                            default        => ''
                        };
                    ?>
                    <span class="badge <?= $badgeClass ?>"><?= htmlspecialchars($res['Reserveringstatus']) ?></span>
                </td>
            </tr>
            <?php endforeach; ?>

            <?php if (empty($reserveringen)): ?>
            <tr>
                <td colspan="8" style="text-align:center; color:#999; padding:20px;">
                    Geen reserveringen gevonden<?= $zoekterm ? ' voor "' . htmlspecialchars($zoekterm) . '"' : '' ?>
                </td>
            </tr>
            <?php endif; ?>
        </tbody>
    </table>
    </div>
</div>
</body>
</html>
