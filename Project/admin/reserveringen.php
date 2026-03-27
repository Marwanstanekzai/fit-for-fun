<?php
session_start();
require_once '../config/database.php';
require_once '../includes/auth.php';
checkAdmin();

$succes = '';
$fout   = '';

// =============================================
// 1. NIEUWE RESERVERING TOEVOEGEN (CREATE)
// =============================================
// 1. NIEUWE RESERVERING TOEVOEGEN (CREATE)
if (isset($_POST['actie']) && $_POST['actie'] === 'toevoegen') {
    $lid_id   = (int)$_POST['lid_id'];
    $les_id   = (int)$_POST['les_id'];
    $status   = trim($_POST['status']);

    try {
        // Haal lid info op
        $lidStmt = $pdo->prepare("SELECT Voornaam, Tussenvoegsel, Achternaam, Relatienummer FROM lid WHERE Id = ?");
        $lidStmt->execute([$lid_id]);
        $lid = $lidStmt->fetch();

        // Haal les info op (inclusief max personen)
        $lesStmt = $pdo->prepare("SELECT Datum, Tijd, MaxAantalPersonen FROM les WHERE Id = ?");
        $lesStmt->execute([$les_id]);
        $les = $lesStmt->fetch();

        if ($lid && $les) {
            // Unhappy Scenario 1: Reeds gereserveerd?
            $dupStmt = $pdo->prepare("SELECT COUNT(*) FROM reservering WHERE Nummer = ? AND Datum = ? AND Tijd = ? AND Reserveringstatus != 'Geannuleerd'");
            $dupStmt->execute([$lid['Relatienummer'], $les['Datum'], $les['Tijd']]);
            if ($dupStmt->fetchColumn() > 0) {
                $fout = "Dit lid heeft al een actieve reservering voor deze les.";
            } else {
                // Unhappy Scenario 2: Les vol?
                $countStmt = $pdo->prepare("SELECT COUNT(*) FROM reservering WHERE Datum = ? AND Tijd = ? AND Reserveringstatus != 'Geannuleerd'");
                $countStmt->execute([$les['Datum'], $les['Tijd']]);
                $huidigAantal = $countStmt->fetchColumn();

                if ($huidigAantal >= $les['MaxAantalPersonen']) {
                    $fout = "Helaas, deze les is al vol (max. {$les['MaxAantalPersonen']} personen).";
                } else {
                    $stmt = $pdo->prepare("INSERT INTO reservering (Voornaam, Tussenvoegsel, Achternaam, Nummer, Datum, Tijd, Reserveringstatus) VALUES (?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([
                        $lid['Voornaam'], 
                        $lid['Tussenvoegsel'], 
                        $lid['Achternaam'], 
                        $lid['Relatienummer'], 
                        $les['Datum'], 
                        $les['Tijd'], 
                        $status
                    ]);
                    $succes = "Reservering succesvol toegevoegd!";
                }
            }
        } else {
            $fout = "Lid of les niet gevonden.";
        }
    } catch (PDOException $e) {
        $fout = "Fout bij opslaan: " . $e->getMessage();
    }
}

// =============================================
// 2. RESERVERING VERWIJDEREN (DELETE)
// =============================================
if (isset($_GET['verwijder'])) {
    $id = (int)$_GET['verwijder'];
    try {
        $pdo->prepare("DELETE FROM reservering WHERE Id = ?")->execute([$id]);
        $succes = "Reservering verwijderd.";
    } catch (PDOException $e) {
        $fout = "Fout bij verwijderen.";
    }
}

// =============================================
// 3. RESERVERING WIJZIGEN (UPDATE)
// =============================================
if (isset($_POST['actie']) && $_POST['actie'] === 'bewerken') {
    $id     = (int)$_POST['id'];
    $status = trim($_POST['status']);

    try {
        $stmt = $pdo->prepare("UPDATE reservering SET Reserveringstatus=? WHERE Id=?");
        $stmt->execute([$status, $id]);
        $succes = "Reservering bijgewerkt!";
    } catch (PDOException $e) {
        $fout = "Fout bij bijwerken.";
    }
}

// =============================================
// READ: Zoeken & ophalen van reserveringen
// =============================================
$zoekterm = $_GET['zoek'] ?? '';

if ($zoekterm !== '') {
    $stmt = $pdo->prepare("SELECT * FROM reservering WHERE Voornaam LIKE ? OR Achternaam LIKE ? OR Reserveringstatus LIKE ? ORDER BY Datum, Tijd");
    $tLike = '%' . $zoekterm . '%';
    $stmt->execute([$tLike, $tLike, $tLike]);
    $reserveringen = $stmt->fetchAll();
} else {
    $reserveringen = $pdo->query("SELECT * FROM reservering ORDER BY Datum, Tijd")->fetchAll();
}

// Haal bewerkgegevens op als we een reservering bewerken
$bewerkRes = null;
if (isset($_GET['bewerk'])) {
    $stmt = $pdo->prepare("SELECT * FROM reservering WHERE Id = ?");
    $stmt->execute([(int)$_GET['bewerk']]);
    $bewerkRes = $stmt->fetch();
}

// Haal alle leden en lessen op voor de dropdown
$alleleden  = $pdo->query("SELECT Id as id, Voornaam as voornaam, Achternaam as achternaam FROM lid ORDER BY Achternaam, Voornaam")->fetchAll();
$allelessen = $pdo->query("SELECT Id as id, Naam as naam, Datum as datum, Tijd as tijd FROM les WHERE Beschikbaarheid != 'Geannuleerd' ORDER BY Datum, Tijd")->fetchAll();
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reserveringen Beheren – Fit for Fun</title>
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
            .toevoeg-form { flex-direction: column; align-items: stretch !important; }
            .toevoeg-form div { width: 100%; }
            .toevoeg-form select, .toevoeg-form input { width: 100% !important; }
            .table-container { overflow-x: auto; -webkit-overflow-scrolling: touch; border: 2px solid #000; border-radius: 8px; }
            table { min-width: 750px; border: none; }
            .stats { flex-direction: column; }
            .stat { width: 100%; }
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
        .succes { background: #d4edda; color: #155724; padding: 10px; border-radius: 6px; margin-bottom: 15px; }
        .fout   { background: #ffe0e0; color: #c0392b; padding: 10px; border-radius: 6px; margin-bottom: 15px; }

        /* FORMULIER */
        form.toevoeg-form { background: #f9f9f9; padding: 20px; border-radius: 10px; border: 2px solid #000; margin-bottom: 30px; display: flex; flex-wrap: wrap; gap: 10px; align-items: flex-end; }
        form.toevoeg-form select,
        form.toevoeg-form input { padding: 9px; border: 1px solid #ddd; border-radius: 6px; font-size: 14px; min-width: 180px; }
        form.toevoeg-form label { display: block; font-size: 12px; color: #555; margin-bottom: 3px; font-weight:bold; }

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

        /* ACTIE KNOPPEN */
        .btn-bewerk   { background: #2E75B6; color: white; padding: 5px 10px; border-radius: 5px; text-decoration: none; font-size: 13px; font-weight:bold; }
        .btn-verwijder{ background: #c0392b; color: white; padding: 5px 10px; border-radius: 5px; text-decoration: none; font-size: 13px; font-weight:bold; }
        .terug { display: inline-block; margin-bottom: 20px; color: #1F3864; text-decoration: none; font-size: 14px; font-weight:bold; }
        .terug:hover { text-decoration: underline; }

        /* STATISTIEKEN */
        .stats { display:flex; gap:15px; margin-bottom:20px; flex-wrap:wrap; }
        .stat  { background:#f9f9f9; border:2px solid #000; border-radius:10px; padding:15px 25px; text-align:center; }
        .stat h3 { font-size:28px; color:#ff8c00; margin:0; }
        .stat p  { color:#555; font-size:13px; margin:3px 0 0; font-weight:bold; }
    </style>
</head>
<body>

<!-- NAVIGATIEBALK -->
<nav class="navbar">
    <div class="nav-left">
        <img src="/img/logo.png" alt="Fit for Fun Logo" class="logo">

        <!-- Zoekformulier voor reserveringen -->
        <form method="GET" class="search-bar">
            <input type="text" name="zoek" placeholder="Zoek op naam of les..." value="<?= htmlspecialchars($zoekterm) ?>">
            <button type="submit" style="background:transparent; border:none; cursor:pointer; font-weight:bold; font-size:18px;">🔍</button>
        </form>
    </div>

    <div class="nav-links">
        Welkom, <?= htmlspecialchars($_SESSION['naam']) ?>
        <a href="/logout.php" class="login-btn">Uitloggen</a>
    </div>
</nav>

<div class="container">
    <a class="terug" href="/admin/index.php">← Terug naar dashboard</a>

    <?php if ($zoekterm !== ''): ?>
        <h1>📋 Reserveringen (gezocht op: "<?= htmlspecialchars($zoekterm) ?>")</h1>
        <a href="/admin/reserveringen.php" style="color:red; margin-bottom:15px; display:inline-block;">❌ Zoekopdracht wissen</a>
    <?php else: ?>
        <h1>📋 Reserveringen Beheren</h1>
    <?php endif; ?>

    <!-- SUCCESMELDING / FOUTMELDING -->
    <?php if ($succes): ?><div class="succes">✅ <?= htmlspecialchars($succes) ?></div><?php endif; ?>
    <?php if ($fout):   ?><div class="fout">❌ <?= htmlspecialchars($fout) ?></div><?php endif; ?>

    <!-- STATISTIEKEN OVERZICHT -->
    <?php if ($zoekterm === ''): ?>
    <?php
        $totaal       = $pdo->query("SELECT COUNT(*) FROM reservering")->fetchColumn();
        $gereserveerd = $pdo->query("SELECT COUNT(*) FROM reservering WHERE Reserveringstatus = 'Gereserveerd'")->fetchColumn();
        $geannuleerd  = $pdo->query("SELECT COUNT(*) FROM reservering WHERE Reserveringstatus = 'Geannuleerd'")->fetchColumn();
        $aanwezig     = $pdo->query("SELECT COUNT(*) FROM reservering WHERE Reserveringstatus = 'Aanwezig'")->fetchColumn();
    ?>
    <div class="stats">
        <div class="stat"><h3><?= $totaal ?></h3><p>Totaal</p></div>
        <div class="stat"><h3><?= $gereserveerd ?></h3><p>Gereserveerd</p></div>
        <div class="stat"><h3><?= $aanwezig ?></h3><p>Aanwezig</p></div>
        <div class="stat"><h3><?= $geannuleerd ?></h3><p>Geannuleerd</p></div>
    </div>
    <?php endif; ?>

    <!-- FORMULIER: TOEVOEGEN / BEWERKEN -->
    <form method="POST" class="toevoeg-form">
        <input type="hidden" name="actie" value="<?= $bewerkRes ? 'bewerken' : 'toevoegen' ?>">
        <?php if ($bewerkRes): ?>
            <input type="hidden" name="id" value="<?= $bewerkRes['Id'] ?>">
        <?php endif; ?>

        <?php if ($bewerkRes): ?>
            <!-- Bij bewerken tonen we even wie het is (naam aanpassen zit in Leden beheer) -->
            <div style="margin-bottom:10px; font-weight:bold; color:#1F3864;">
                Bewerken voor: <?= htmlspecialchars($bewerkRes['Voornaam'] . ' ' . $bewerkRes['Achternaam']) ?>
            </div>
        <?php else: ?>
            <!-- Lid selectie -->
            <div>
                <label>Lid</label>
                <select name="lid_id" required>
                    <option value="">-- Kies een lid --</option>
                    <?php foreach ($alleleden as $lid): ?>
                        <option value="<?= $lid['id'] ?>">
                            <?= htmlspecialchars($lid['voornaam'] . ' ' . $lid['achternaam']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Les selectie -->
            <div>
                <label>Les</label>
                <select name="les_id" required>
                    <option value="">-- Kies een les --</option>
                    <?php foreach ($allelessen as $les): ?>
                        <option value="<?= $les['id'] ?>">
                            <?= htmlspecialchars($les['naam']) ?> – <?= date('d-m-Y', strtotime($les['datum'])) ?> <?= substr($les['tijd'], 0, 5) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        <?php endif; ?>

        <!-- Status selectie -->
        <div>
            <label>Status</label>
            <select name="status" required>
                <?php $statussen = ['Gereserveerd', 'Aanwezig', 'Afwezig', 'Geannuleerd']; ?>
                <?php foreach ($statussen as $s): ?>
                    <option value="<?= $s ?>" <?= ($bewerkRes && $bewerkRes['Reserveringstatus'] === $s) ? 'selected' : '' ?>>
                        <?= $s ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <button type="submit" class="button" style="height:38px; padding:0 20px; font-size:14px;">
            <?= $bewerkRes ? '💾 Opslaan' : '➕ Toevoegen' ?>
        </button>

        <?php if ($bewerkRes): ?>
            <a href="/admin/reserveringen.php" class="button" style="height:38px; padding:0 20px; background:#888; border-color:#555; box-shadow:0 3px 0 #333; font-size:14px;">Annuleren</a>
        <?php endif; ?>
    </form>

    <!-- TABEL: OVERZICHT VAN RESERVERINGEN -->
    <div class="table-container">
        <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Lid</th>
                <th>Les</th>
                <th>Datum</th>
                <th>Tijd</th>
                <th>Status</th>
                <th>Acties</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($reserveringen as $res): ?>
            <tr>
                <td><?= $res['Id'] ?></td>
                <td><?= htmlspecialchars($res['Voornaam'] . ' ' . $res['Achternaam']) ?></td>
                <td>-</td> <!-- In het nieuwe schema staat de lesnaam niet, alleen datum/tijd -->
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
                <td>
                    <a class="btn-bewerk" href="?bewerk=<?= $res['Id'] ?>">✏️ Bewerk</a>
                    <a class="btn-verwijder" href="?verwijder=<?= $res['Id'] ?>"
                       onclick="return confirm('Weet je zeker dat je deze reservering wilt verwijderen?')">🗑️ Verwijder</a>
                </td>
            </tr>
            <?php endforeach; ?>

            <?php if (empty($reserveringen)): ?>
            <tr>
                <td colspan="7" style="text-align:center; color:#999; padding:20px;">
                    Geen reserveringen gevonden <?= $zoekterm ? 'voor "'.htmlspecialchars($zoekterm).'"' : '' ?>
                </td>
            </tr>
            <?php endif; ?>
        </tbody>
    </table>
    </div>
</div>
</body>
</html>
