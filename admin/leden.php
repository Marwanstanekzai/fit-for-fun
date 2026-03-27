<?php
session_start();
require_once '../config/database.php';
require_once '../includes/auth.php';
checkAdmin();

$succes = '';
$fout   = '';

// 1. Toevoegen van een nieuw lid
if (isset($_POST['actie']) && $_POST['actie'] === 'toevoegen') {
    $voornaam       = trim($_POST['voornaam']);
    $achternaam     = trim($_POST['achternaam']);
    $email          = trim($_POST['email']);
    $mobiel         = trim($_POST['mobiel']);
    $relatienummer  = (int)$_POST['relatienummer'];

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $fout = "Ongeldig e-mailadres formaat.";
    } else {
        try {
            // Check op uniek email
            $check = $pdo->prepare("SELECT COUNT(*) FROM lid WHERE Email = ?");
            $check->execute([$email]);
            if ($check->fetchColumn() > 0) {
                $fout = "E-mailadres is al in gebruik door een ander lid.";
            } else {
                $stmt = $pdo->prepare("INSERT INTO lid (Voornaam, Achternaam, Email, Mobiel, Relatienummer) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$voornaam, $achternaam, $email, $mobiel, $relatienummer]);
                $succes = "Lid succesvol toegevoegd!";
            }
        } catch (PDOException $e) {
            $fout = "Fout bij toevoegen: " . $e->getMessage();
        }
    }
}

// 2. Verwijderen van een lid
if (isset($_GET['verwijder'])) {
    $id = (int)$_GET['verwijder'];
    try {
        $pdo->prepare("DELETE FROM lid WHERE Id = ?")->execute([$id]);
        $succes = "Lid verwijderd.";
    } catch (PDOException $e) {
        $fout = "Kan lid niet verwijderen. Mogelijk zijn er nog reserveringen aan dit lid gekoppeld.";
    }
}

// 3. Bewerken van een lid
if (isset($_POST['actie']) && $_POST['actie'] === 'bewerken') {
    $id         = (int)$_POST['id'];
    $voornaam   = trim($_POST['voornaam']);
    $achternaam = trim($_POST['achternaam']);
    $email      = trim($_POST['email']);
    $mobiel     = trim($_POST['mobiel']);

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $fout = "Ongeldig e-mailadres formaat.";
    } else {
        try {
            // Check op uniek email (behalve voor huidige record)
            $check = $pdo->prepare("SELECT COUNT(*) FROM lid WHERE Email = ? AND Id != ?");
            $check->execute([$email, $id]);
            if ($check->fetchColumn() > 0) {
                $fout = "E-mailadres is al in gebruik door een ander lid.";
            } else {
                $stmt = $pdo->prepare("UPDATE lid SET Voornaam=?, Achternaam=?, Email=?, Mobiel=? WHERE Id=?");
                $stmt->execute([$voornaam, $achternaam, $email, $mobiel, $id]);
                $succes = "Lid bijgewerkt!";
            }
        } catch (PDOException $e) {
            $fout = "Fout bij bijwerken: " . $e->getMessage();
        }
    }
}

// --- NIEUWE ZOEK LOGICA ---
// Haal de zoekterm op (indien ingevuld) via GET parameter
$zoekterm = $_GET['zoek'] ?? '';

// Als er een zoekterm is, filter op Voornaam of Achternaam of combinatie
if ($zoekterm !== '') {
    $stmt = $pdo->prepare("SELECT * FROM lid WHERE Voornaam LIKE ? OR Achternaam LIKE ? OR CONCAT(Voornaam, ' ', Achternaam) LIKE ? ORDER BY Achternaam, Voornaam");
    $termLike = '%' . $zoekterm . '%';
    $stmt->execute([$termLike, $termLike, $termLike]);
    $leden = $stmt->fetchAll();
} else {
    // Geen zoekterm? Dan halen we gewoon alle leden op
    $leden = $pdo->query("SELECT * FROM lid ORDER BY Achternaam, Voornaam")->fetchAll();
}
// ----------------------------

$bewerkLid = null;
if (isset($_GET['bewerk'])) {
    $stmt = $pdo->prepare("SELECT * FROM lid WHERE Id = ?");
    $stmt->execute([(int)$_GET['bewerk']]);
    $bewerkLid = $stmt->fetch();
}
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Leden Beheren – Fit for Fun</title>
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
            .search-bar { width: 100% !important; margin: 10px 0; }
            .nav-links { flex-direction: column; gap: 15px !important; }
            .container { padding: 20px !important; }
            .toevoeg-form { flex-direction: column; align-items: stretch !important; }
            .toevoeg-form div { width: 100%; }
            .toevoeg-form input { width: 100% !important; }
            .table-container { overflow-x: auto; -webkit-overflow-scrolling: touch; border: 2px solid #000; border-radius: 8px; }
            table { min-width: 700px; border: none; }
        }
        .icon{ display:block; }
         
        /* SEARCH BAR: Zorgt voor de unieke afgeronde zoekbalk styling in de navigatie */
        .search-bar{ display:flex; align-items:center; height:50px; width:320px; background:#e9e9e9; border:2px solid #000; border-radius:40px; padding:0 20px; }
        /* Het input veld zelf dat onzichtbaar binnen de robuuste zwarte rand is geplaatst */
        .search-bar input{ border:none; background:transparent; outline:none; flex:1; font-size:16px; }
         
        /* NAV LINKS: Hoe de linkjes in de navigatie (zoals login/logout) eruit zien */
        .nav-links{ display:flex; align-items:center; gap:40px; font-weight:bold; }
        .nav-links a{ text-decoration:none; color:#000; font-weight:600; letter-spacing:1px; }
         
        /* BUTTON STYLE: Een stoere oranje knop met een 3D effect door een dikke schaduw (#1e88c9 is blauw, dit valt extra op) */
        .button, .login-btn{
            display:flex; align-items:center; justify-content:center; gap:10px;
            height:50px; padding:0 28px;
            background-color:#ff8c00; border-radius:40px; border:3px solid #000;
            box-shadow:0 5px 0 #1e88c9; /* Zorgt voor het "eruit springen" van de knop */
            font-weight:800; text-decoration:none; color:#000;
            transition:all 0.15s ease-in-out; cursor:pointer;
        }
        /* Hover effect: Knop deels indrukken bij muisklik */
        .button:hover, .login-btn:hover{ transform:translateY(3px); box-shadow:0 2px 0 #1e88c9; }
        /* Active effect: De knop volledig indrukken doordat de box-shadow weggaat */
        .button:active, .login-btn:active{ transform:translateY(5px); box-shadow:none; }

        /* === BESTAANDE OPMAAK AANGEPAST VOOR DE NIEUWE STIJL === */
        .container { padding: 30px 80px; background: rgba(255, 255, 255, 0.95); min-height: calc(100vh - 110px); }
        h1 { color: #1F3864; margin-bottom: 20px;}
        .succes { background: #d4edda; color: #155724; padding: 10px; border-radius: 6px; margin-bottom: 15px; }
        .fout { background: #ffe0e0; color: #c0392b; padding: 10px; border-radius: 6px; margin-bottom: 15px; }
        
        /* Het formulier om leden toe te voegen */
        form.toevoeg-form { background: #f9f9f9; padding: 20px; border-radius: 10px; border: 2px solid #000; margin-bottom: 30px; display: flex; flex-wrap: wrap; gap: 10px; align-items: flex-end; }
        form.toevoeg-form input { padding: 9px; border: 1px solid #ddd; border-radius: 6px; font-size: 14px; }
        form.toevoeg-form label { display: block; font-size: 12px; color: #555; margin-bottom: 3px; font-weight:bold; }
        
        /* De tabel voor leden */
        table { width: 100%; border-collapse: collapse; background: white; border-radius: 8px; overflow: hidden; box-shadow: 0 5px 15px rgba(0,0,0,0.1); border: 2px solid #000;}
        th { background: #1F3864; color: white; padding: 12px; text-align: left; border-bottom: 2px solid #000; }
        td { padding: 11px 12px; border-bottom: 1px solid #eee; font-size: 14px; }
        tr:hover { background: #f5f9ff; }
        
        .btn-bewerk { background: #2E75B6; color: white; padding: 5px 10px; border-radius: 5px; text-decoration: none; font-size: 13px; font-weight:bold; }
        .btn-verwijder { background: #c0392b; color: white; padding: 5px 10px; border-radius: 5px; text-decoration: none; font-size: 13px; font-weight:bold; }
        .terug { display: inline-block; margin-bottom: 20px; color: #1F3864; text-decoration: none; font-size: 14px; font-weight:bold;}
        .terug:hover { text-decoration: underline; }
    </style>
</head>
<body>

<!-- De vernieuwde navigatiebalk mét zoekfunctie -->
<nav class="navbar">
    <div class="nav-left">
        <img src="/img/logo.png" alt="Fit for Fun Logo" class="logo">
        
        <!-- FORMULIER VOOR HET ZOEKEN -->
        <form method="GET" class="search-bar">
            <!-- Houd een eventuele bestaande zoekopdracht vast in de input value -->
            <input type="text" name="zoek" placeholder="Zoek op naam of achternaam..." value="<?= htmlspecialchars($zoekterm) ?>">
            <!-- Minimalistische knop voor het loepje icon -->
            <button type="submit" style="background:transparent; border:none; cursor:pointer; font-weight:bold; font-size:18px;">🔍</button>
        </form>
    </div>

    <!-- Rechter menu links (gebruikt de nieuwe login-btn style class) -->
    <div class="nav-links">
        Welkom, <?= htmlspecialchars($_SESSION['naam']) ?>
        <a href="/logout.php" class="login-btn">Uitloggen</a>
    </div>
</nav>

<div class="container">
    <a class="terug" href="/admin/index.php">← Terug naar dashboard</a>
    
    <!-- Laten zien of we een zoekfilter hebben -->
    <?php if ($zoekterm !== ''): ?>
        <h1>👥 Leden (gezocht op: "<?= htmlspecialchars($zoekterm) ?>")</h1>
        <!-- Knop om de zoekfilters te wissen door het veld leeg te maken via 'leden.php' zonder parameters -->
        <a href="/admin/leden.php" style="color:red; margin-bottom:15px; display:inline-block;">❌ Zoekopdracht wissen</a>
    <?php else: ?>
        <h1>👥 Leden Beheren</h1>
    <?php endif; ?>

    <?php if ($succes): ?><div class="succes"><?= htmlspecialchars($succes) ?></div><?php endif; ?>
    <?php if ($fout):   ?><div class="fout"><?= htmlspecialchars($fout) ?></div><?php endif; ?>

    <!-- Formulier voor het toevoegen / bewerken -->
    <form method="POST" class="toevoeg-form">
        <input type="hidden" name="actie" value="<?= $bewerkLid ? 'bewerken' : 'toevoegen' ?>">
        <?php if ($bewerkLid): ?>
            <input type="hidden" name="id" value="<?= $bewerkLid['Id'] ?>">
        <?php endif; ?>
        <div>
            <label>Voornaam</label>
            <input type="text" name="voornaam" required placeholder="Voornaam" value="<?= htmlspecialchars($bewerkLid['Voornaam'] ?? '') ?>">
        </div>
        <div>
            <label>Achternaam</label>
            <input type="text" name="achternaam" required placeholder="Achternaam" value="<?= htmlspecialchars($bewerkLid['Achternaam'] ?? '') ?>">
        </div>
        <div>
            <label>E-mail</label>
            <input type="email" name="email" required placeholder="email@voorbeeld.nl" value="<?= htmlspecialchars($bewerkLid['Email'] ?? '') ?>">
        </div>
        <div>
            <label>Mobiel</label>
            <input type="text" name="mobiel" placeholder="06-12345678" value="<?= htmlspecialchars($bewerkLid['Mobiel'] ?? '') ?>">
        </div>
        <?php if (!$bewerkLid): ?>
        <div>
            <label>Relatienummer</label>
            <input type="number" name="relatienummer" placeholder="bijv. 201" value="">
        </div>
        <?php endif; ?>
        
        <!-- Let op: we gebruiken de ".button" style class voor de opslaan knop -->
        <button type="submit" class="button" style="height:38px; padding:0 20px; font-size:14px;"><?= $bewerkLid ? '💾 Opslaan' : '➕ Toevoegen' ?></button>
        
        <?php if ($bewerkLid): ?>
            <a href="/admin/leden.php" class="button" style="height:38px; padding:0 20px; background:#888; border-color:#555; box-shadow:0 3px 0 #333; font-size:14px;">Annuleren</a>
        <?php endif; ?>
    </form>

    <!-- Tabel om de resultaten te tonen -->
    <div class="table-container">
        <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Voornaam</th>
                <th>Achternaam</th>
                <th>E-mail</th>
                <th>Telefoon</th>
                <th>Lid sinds</th>
                <th>Acties</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($leden as $lid): ?>
            <tr>
                <td><?= $lid['Id'] ?></td>
                <td><?= htmlspecialchars($lid['Voornaam']) ?></td>
                <td><?= htmlspecialchars($lid['Achternaam']) ?></td>
                <td><?= htmlspecialchars($lid['Email']) ?></td>
                <td><?= htmlspecialchars($lid['Mobiel']) ?></td>
                <td><?= date('d-m-Y', strtotime($lid['Datumaangemaakt'])) ?></td>
                <td>
                    <a class="btn-bewerk" href="?bewerk=<?= $lid['Id'] ?>">✏️ Bewerk</a>
                    <a class="btn-verwijder" href="?verwijder=<?= $lid['Id'] ?>" onclick="return confirm('Weet je zeker dat je dit lid wilt verwijderen?')">🗑️ Verwijder</a>
                </td>
            </tr>
            <?php endforeach; ?>
            
            <?php if (empty($leden)): ?>
            <tr>
                <td colspan="7" style="text-align:center; color:#999; padding:20px;">
                    Geen leden gevonden <?= $zoekterm ? 'voor "'.htmlspecialchars($zoekterm).'"' : '' ?>
                </td>
            </tr>
            <?php endif; ?>
        </tbody>
    </table>
    </div>
</div>
</body>
</html>