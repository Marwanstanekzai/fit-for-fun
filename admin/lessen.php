<?php
session_start();
require_once '../config/database.php';
require_once '../includes/auth.php';
checkAdmin();

$succes = '';
$fout   = '';

// 1. Toevoegen van een nieuwe les
if (isset($_POST['actie']) && $_POST['actie'] === 'toevoegen') {
    $naam           = trim($_POST['naam']);
    $prijs          = (float)$_POST['prijs'];
    $datum          = $_POST['datum'];
    $tijd           = $_POST['tijd'];
    $min_personen   = (int)$_POST['min_personen'];
    $max_personen   = (int)$_POST['max_personen'];

    if (strtotime($datum) < strtotime(date('Y-m-d'))) {
        $fout = "Je kunt geen les plannen in het verleden.";
    } elseif ($min_personen > $max_personen) {
        $fout = "Minimum aantal personen kan niet hoger zijn dan maximum.";
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO les (Naam, Prijs, Datum, Tijd, MinAantalPersonen, MaxAantalPersonen, Beschikbaarheid) VALUES (?, ?, ?, ?, ?, ?, 'Ingepland')");
            $stmt->execute([$naam, $prijs, $datum, $tijd, $min_personen, $max_personen]);
            $succes = "Les succesvol toegevoegd!";
        } catch (PDOException $e) {
            $fout = "Fout bij toevoegen: " . $e->getMessage();
        }
    }
}

// 2. Verwijderen van een les
if (isset($_GET['verwijder'])) {
    $id = (int)$_GET['verwijder'];
    try {
        $pdo->prepare("DELETE FROM les WHERE Id = ?")->execute([$id]);
        $succes = "Les verwijderd.";
    } catch (PDOException $e) {
        $fout = "Kan les niet verwijderen. Mogelijk zijn er nog reserveringen aan gekoppeld.";
    }
}

// 4. Bestaande les annuleren
if (isset($_GET['annuleer'])) {
    $id = (int)$_GET['annuleer'];
    $pdo->prepare("UPDATE les SET Beschikbaarheid = 'Geannuleerd' WHERE Id = ?")->execute([$id]);
    $succes = "Les geannuleerd.";
}

// 3. Bewerken van een les
if (isset($_POST['actie']) && $_POST['actie'] === 'bewerken') {
    $id             = (int)$_POST['id'];
    $naam           = trim($_POST['naam']);
    $prijs          = (float)$_POST['prijs'];
    $datum          = $_POST['datum'];
    $tijd           = $_POST['tijd'];
    $min_personen   = (int)$_POST['min_personen'];
    $max_personen   = (int)$_POST['max_personen'];
    $beschikbaarheid = trim($_POST['beschikbaarheid']);

    if ($min_personen > $max_personen) {
        $fout = "Minimum aantal personen kan niet hoger zijn dan maximum.";
    } else {
        try {
            $stmt = $pdo->prepare("UPDATE les SET Naam=?, Prijs=?, Datum=?, Tijd=?, MinAantalPersonen=?, MaxAantalPersonen=?, Beschikbaarheid=? WHERE Id=?");
            $stmt->execute([$naam, $prijs, $datum, $tijd, $min_personen, $max_personen, $beschikbaarheid, $id]);
            $succes = "Les bijgewerkt!";
        } catch (PDOException $e) {
            $fout = "Fout bij bijwerken: " . $e->getMessage();
        }
    }
}

// Haal altijd alle lessen op
$lessen = $pdo->query("SELECT * FROM les ORDER BY Datum, Tijd")->fetchAll();

$bewerkLes = null;
if (isset($_GET['bewerk'])) {
    $stmt = $pdo->prepare("SELECT * FROM les WHERE Id = ?");
    $stmt->execute([(int)$_GET['bewerk']]);
    $bewerkLes = $stmt->fetch();
}
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lessen Beheren – Fit for Fun</title>
    <style>
        /* === RESET & BASIS === */
        *{ margin:0; padding:0; box-sizing:border-box; font-family:Arial, Helvetica, sans-serif; }

        /* NAVBAR: Opmaak voor de navigatiebalk bovenaan */
        .navbar{ background:#dcdcdc; min-height:110px; display:flex; justify-content:space-between; align-items:center; padding:0 80px; flex-wrap: wrap; gap: 20px; }
        .nav-left{ display:flex; align-items:center; gap:20px; }
        .logo{ height:90px; }

        @media screen and (max-width: 768px) {
            .navbar { padding: 20px; justify-content: center; text-align: center; }
            .nav-left { flex-direction: column; }
            .nav-links { flex-direction: column; gap: 15px !important; }
            .container { padding: 20px !important; }
            .toevoeg-form { flex-direction: column; align-items: stretch !important; }
            .toevoeg-form div { width: 100%; }
            .toevoeg-form input, .toevoeg-form select { width: 100% !important; }
            .table-container { overflow-x: auto; -webkit-overflow-scrolling: touch; border: 2px solid #000; border-radius: 8px; }
            table { min-width: 700px; border: none; }
        }
         
        /* NAV LINKS: Hoe de linkjes in de navigatie erbij staan */
        .nav-links{ display:flex; align-items:center; gap:40px; font-weight:bold; }
        .nav-links a{ text-decoration:none; color:#000; font-weight:600; letter-spacing:1px; }
         
        /* BUTTON STYLE: Een stoere oranje/blauwe 3D knop */
        .button, .login-btn{
            display:flex; align-items:center; justify-content:center; gap:10px;
            height:50px; padding:0 28px;
            background-color:#ff8c00; border-radius:40px; border:3px solid #000;
            box-shadow:0 5px 0 #1e88c9; /* Zorgt voor het lichte 3D effect onderaan de knop */
            font-weight:800; text-decoration:none; color:#000;
            transition:all 0.15s ease-in-out; cursor:pointer;
        }
        .button:hover, .login-btn:hover{ transform:translateY(3px); box-shadow:0 2px 0 #1e88c9; }
        .button:active, .login-btn:active{ transform:translateY(5px); box-shadow:none; }

        /* === BESTAANDE OPMAAK AANGEPAST VOOR TABELLEN / FORMS === */
        
        /* Container voor de website marges */
        .container { padding: 30px 80px; background: rgba(255, 255, 255, 0.95); min-height: calc(100vh - 110px); }
        h1 { color: #1F3864; margin-bottom: 20px;}
        .succes { background: #d4edda; color: #155724; padding: 10px; border-radius: 6px; margin-bottom: 15px; }
        .fout { background: #ffe0e0; color: #c0392b; padding: 10px; border-radius: 6px; margin-bottom: 15px; }
        
        /* Toevoeg formulier strakker maken in lijn met de CSS */
        form.toevoeg-form { background: #f9f9f9; padding: 20px; border-radius: 10px; border: 2px solid #000; margin-bottom: 30px; display: flex; flex-wrap: wrap; gap: 10px; align-items: flex-end; }
        form.toevoeg-form input { padding: 9px; border: 1px solid #ddd; border-radius: 6px; font-size: 14px; }
        form.toevoeg-form label { display: block; font-size: 12px; color: #555; margin-bottom: 3px; font-weight:bold; }
        
        /* Tabel layout strak en zwart omrand */
        table { width: 100%; border-collapse: collapse; background: white; border-radius: 8px; overflow: hidden; box-shadow: 0 5px 15px rgba(0,0,0,0.1); border: 2px solid #000;}
        th { background: #1F3864; color: white; padding: 12px; text-align: left; border-bottom: 2px solid #000; }
        td { padding: 11px 12px; border-bottom: 1px solid #eee; font-size: 14px; }
        tr:hover { background: #f5f9ff; }
        
        .btn-bewerk    { background: #2E75B6; color: white; padding: 5px 10px; border-radius: 5px; text-decoration: none; font-size: 13px; font-weight:bold; }
        .btn-verwijder { background: #c0392b; color: white; padding: 5px 10px; border-radius: 5px; text-decoration: none; font-size: 13px; font-weight:bold; }
        .btn-annuleer  { background: #e67e22; color: white; padding: 5px 10px; border-radius: 5px; text-decoration: none; font-size: 13px; font-weight:bold; }
        .terug { display: inline-block; margin-bottom: 20px; color: #1F3864; text-decoration: none; font-size: 14px; font-weight:bold;}
        .terug:hover { text-decoration: underline; }
        /* STATUS BADGES */
        .badge { display:inline-block; padding:3px 10px; border-radius:20px; font-size:12px; font-weight:bold; }
        .badge-ingepland    { background:#d4edda; color:#155724; border:1px solid #155724; }
        .badge-gestart      { background:#cce5ff; color:#004085; border:1px solid #004085; }
        .badge-geannuleerd  { background:#ffe0e0; color:#c0392b; border:1px solid #c0392b; }
        .badge-niet-gestart { background:#fff3cd; color:#856404; border:1px solid #856404; }
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
    <a class="terug" href="/admin/index.php">← Terug naar dashboard</a>
    <h1>📅 Lessen Beheren</h1>

    <?php if ($succes): ?><div class="succes"><?= htmlspecialchars($succes) ?></div><?php endif; ?>
    <?php if ($fout):   ?><div class="fout"><?= htmlspecialchars($fout) ?></div><?php endif; ?>

    <!-- Formulier voor het toevoegen / bewerken van een les -->
    <form method="POST" class="toevoeg-form">
        <!-- Verborgen veld om te zien welke actie we uitvoeren -->
        <input type="hidden" name="actie" value="<?= $bewerkLes ? 'bewerken' : 'toevoegen' ?>">
        <?php if ($bewerkLes): ?>
            <input type="hidden" name="id" value="<?= $bewerkLes['Id'] ?>">
        <?php endif; ?>
        
        <div>
            <label>Lesnaam</label>
            <input type="text" name="naam" required placeholder="bijv. Yoga" value="<?= htmlspecialchars($bewerkLes['Naam'] ?? '') ?>">
        </div>
        <div>
            <label>Prijs (€)</label>
            <input type="number" step="0.01" name="prijs" required placeholder="12.50" value="<?= $bewerkLes['Prijs'] ?? '' ?>" style="width:100px;">
        </div>
        <div>
            <label>Datum</label>
            <input type="date" name="datum" required value="<?= $bewerkLes['Datum'] ?? date('Y-m-d') ?>">
        </div>
        <div>
            <label>Tijd</label>
            <input type="time" name="tijd" required value="<?= $bewerkLes['Tijd'] ?? '' ?>">
        </div>
        <div>
            <label>Min. pers.</label>
            <input type="number" name="min_personen" required min="1" max="9" placeholder="3" value="<?= $bewerkLes['MinAantalPersonen'] ?? '3' ?>" style="width:70px;">
        </div>
        <div>
            <label>Max. pers.</label>
            <input type="number" name="max_personen" required min="3" max="9" placeholder="9" value="<?= $bewerkLes['MaxAantalPersonen'] ?? '9' ?>" style="width:70px;">
        </div>
        <?php if ($bewerkLes): ?>
        <div>
            <label>Beschikbaarheid</label>
            <select name="beschikbaarheid">
                <?php foreach (['Ingepland','Niet gestart','Gestart','Geannuleerd'] as $opt): ?>
                    <option value="<?= $opt ?>" <?= ($bewerkLes['Beschikbaarheid'] ?? '') === $opt ? 'selected' : '' ?>><?= $opt ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <?php endif; ?>
        
        <!-- Gebruik de button css class voor opslaan -->
        <button type="submit" class="button" style="height:38px; padding:0 20px; font-size:14px;"><?= $bewerkLes ? '💾 Opslaan' : '➕ Toevoegen' ?></button>
        <?php if ($bewerkLes): ?>
            <a href="/admin/lessen.php" class="button" style="height:38px; padding:0 20px; background:#888; border-color:#555; box-shadow:0 3px 0 #333; font-size:14px;">Annuleren</a>
        <?php endif; ?>
    </form>

    <!-- Tabel weergave van alle lessen -->
    <div class="table-container">
        <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Lesnaam</th>
                <th>Prijs</th>
                <th>Datum</th>
                <th>Tijd</th>
                <th>Deelnemers</th>
                <th>Status</th>
                <th>Acties</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($lessen as $les): ?>
            <tr>
                <td><?= $les['Id'] ?></td>
                <td><?= htmlspecialchars($les['Naam']) ?></td>
                <td>€<?= number_format($les['Prijs'], 2, ',', '.') ?></td>
                <td><?= date('d-m-Y', strtotime($les['Datum'])) ?></td>
                <td><?= substr($les['Tijd'], 0, 5) ?></td>
                <td><?= $les['MinAantalPersonen'] ?> - <?= $les['MaxAantalPersonen'] ?></td>
                <td>
                    <?php
                        $beschBadge = match($les['Beschikbaarheid'] ?? 'Ingepland') {
                            'Ingepland'   => 'badge-ingepland',
                            'Gestart'     => 'badge-gestart',
                            'Geannuleerd' => 'badge-geannuleerd',
                            default       => 'badge-niet-gestart'
                        };
                    ?>
                    <span class="badge <?= $beschBadge ?>"><?= htmlspecialchars($les['Beschikbaarheid'] ?? 'Ingepland') ?></span>
                </td>
                <td>
                    <a class="btn-bewerk" href="?bewerk=<?= $les['Id'] ?>">✏️ Bewerk</a>
                    <?php if (($les['Beschikbaarheid'] ?? '') !== 'Geannuleerd'): ?>
                        <a class="btn-annuleer" href="?annuleer=<?= $les['Id'] ?>" onclick="return confirm('Les annuleren? Dit kan niet ongedaan worden.')">❌ Annuleer</a>
                    <?php endif; ?>
                    <a class="btn-verwijder" href="?verwijder=<?= $les['Id'] ?>" onclick="return confirm('Weet je zeker dat je deze les wilt verwijderen?')">🗑️ Verwijder</a>
                </td>
            </tr>
            <?php endforeach; ?>
            
            <?php if (empty($lessen)): ?>
            <tr><td colspan="7" style="text-align:center; color:#999; padding:20px;">Nog geen lessen toegevoegd</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
    </div>
</div>
</body>
</html>