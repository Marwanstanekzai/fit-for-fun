<?php
session_start();
require_once '../config/database.php';
require_once '../includes/auth.php';
checkAdmin(); // Alleen admins mogen medewerkers beheren

$succes = '';
$fout   = '';

// =============================================
// 1. NIEUWE MEDEWERKER TOEVOEGEN (CREATE)
// =============================================
if (isset($_POST['actie']) && $_POST['actie'] === 'toevoegen') {
    $voornaam   = trim($_POST['naam']);
    $username   = trim($_POST['username']);
    $wachtwoord = $_POST['wachtwoord'];
    $rol_naam   = $_POST['rol'] === 'admin' ? 'Administrator' : 'Medewerker';

    if (strlen($wachtwoord) < 8) {
        $fout = "Wachtwoord moet minimaal 8 tekens lang zijn.";
    } else {
        try {
            // Check op unieke gebruikersnaam
            $check = $pdo->prepare("SELECT COUNT(*) FROM gebruiker WHERE Gebruikersnaam = ?");
            $check->execute([$username]);
            if ($check->fetchColumn() > 0) {
                $fout = "Gebruikersnaam '{$username}' is al in gebruik.";
            } else {
                $pdo->beginTransaction();
                
                // 1. Voeg gebruiker toe
                $stmt = $pdo->prepare("INSERT INTO gebruiker (Voornaam, Gebruikersnaam, Wachtwoord) VALUES (?, ?, ?)");
                $stmt->execute([$voornaam, $username, $wachtwoord]);
                $gebruikerId = $pdo->lastInsertId();

                // 2. Voeg rol toe
                $stmt = $pdo->prepare("INSERT INTO rol (GebruikerId, Naam) VALUES (?, ?)");
                $stmt->execute([$gebruikerId, $rol_naam]);

                $pdo->commit();
                $succes = "Medewerker '{$voornaam}' succesvol toegevoegd!";
            }
        } catch (PDOException $e) {
            if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
            $fout = "Fout bij toevoegen: " . $e->getMessage();
        }
    }
}

// =============================================
// 2. MEDEWERKER VERWIJDEREN (DELETE)
// =============================================
if (isset($_GET['verwijder'])) {
    $id = (int)$_GET['verwijder'];

    if ($id === (int)$_SESSION['gebruiker_id']) {
        $fout = "Je kunt je eigen account niet verwijderen!";
    } else {
        try {
            $pdo->prepare("DELETE FROM gebruiker WHERE Id = ?")->execute([$id]);
            $succes = "Medewerker verwijderd.";
        } catch (PDOException $e) {
            $fout = "Verwijderen mislukt.";
        }
    }
}

// =============================================
// 3. MEDEWERKER WIJZIGEN (UPDATE)
// =============================================
if (isset($_POST['actie']) && $_POST['actie'] === 'bewerken') {
    $id       = (int)$_POST['id'];
    $voornaam = trim($_POST['naam']);
    $username = trim($_POST['username']);
    $rol_naam = $_POST['rol'] === 'admin' ? 'Administrator' : 'Medewerker';
    $nieuwWachtwoord = trim($_POST['nieuw_wachtwoord']);

    if ($id === (int)$_SESSION['gebruiker_id'] && $_POST['rol'] !== 'admin') {
        $fout = "Je kunt je eigen Administrator rol niet wijzigen via dit formulier.";
    } elseif ($nieuwWachtwoord !== '' && strlen($nieuwWachtwoord) < 8) {
        $fout = "Nieuw wachtwoord moet minimaal 8 tekens lang zijn.";
    } else {
        try {
            // Check op unieke gebruikersnaam (behalve voor de huidige gebruiker)
            $check = $pdo->prepare("SELECT COUNT(*) FROM gebruiker WHERE Gebruikersnaam = ? AND Id != ?");
            $check->execute([$username, $id]);
            if ($check->fetchColumn() > 0) {
                $fout = "Gebruikersnaam '{$username}' is al in gebruik door een andere medewerker.";
            } else {
                $pdo->beginTransaction();

                if ($nieuwWachtwoord !== '') {
                    $stmt = $pdo->prepare("UPDATE gebruiker SET Voornaam=?, Gebruikersnaam=?, Wachtwoord=? WHERE Id=?");
                    $stmt->execute([$voornaam, $username, $nieuwWachtwoord, $id]);
                } else {
                    $stmt = $pdo->prepare("UPDATE gebruiker SET Voornaam=?, Gebruikersnaam=? WHERE Id=?");
                    $stmt->execute([$voornaam, $username, $id]);
                }

                // Rol bijwerken
                $stmt = $pdo->prepare("UPDATE rol SET Naam=? WHERE GebruikerId=?");
                $stmt->execute([$rol_naam, $id]);

                $pdo->commit();
                $succes = "Medewerker bijgewerkt!";
            }
        } catch (PDOException $e) {
            if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
            $fout = "Bijwerken mislukt: " . $e->getMessage();
        }
    }
}

// =============================================
// READ: Alle medewerkers ophalen
// =============================================
$medewerkers = $pdo->query("SELECT g.Id as id, g.Voornaam as naam, g.Gebruikersnaam as username, r.Naam as rol, g.Datumaangemaakt FROM gebruiker g JOIN rol r ON g.Id = r.GebruikerId ORDER BY r.Naam, g.Voornaam")->fetchAll();

// Haal bewerkgegevens op als we een medewerker bewerken
$bewerkMed = null;
if (isset($_GET['bewerk'])) {
    $stmt = $pdo->prepare("SELECT g.*, r.Naam as rol_naam FROM gebruiker g JOIN rol r ON g.Id = r.GebruikerId WHERE g.Id = ?");
    $stmt->execute([(int)$_GET['bewerk']]);
    $bewerkMed = $stmt->fetch();
}
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Medewerkers Beheren – Fit for Fun</title>
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
            .toevoeg-form { flex-direction: column; align-items: stretch !important; }
            .toevoeg-form div { width: 100%; }
            .toevoeg-form input, .toevoeg-form select { width: 100% !important; }
            .table-container { overflow-x: auto; -webkit-overflow-scrolling: touch; border: 2px solid #000; border-radius: 8px; }
            table { min-width: 600px; border: none; }
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

        /* MELDINGEN */
        .succes { background: #d4edda; color: #155724; padding: 10px; border-radius: 6px; margin-bottom: 15px; }
        .fout   { background: #ffe0e0; color: #c0392b; padding: 10px; border-radius: 6px; margin-bottom: 15px; }
        .waarschuwing { background: #fff3cd; color: #856404; padding: 10px; border-radius: 6px; margin-bottom: 15px; font-size: 13px; border: 1px solid #856404; }

        /* FORMULIER */
        form.toevoeg-form {
            background: #f9f9f9; padding: 20px; border-radius: 10px;
            border: 2px solid #000; margin-bottom: 30px;
            display: flex; flex-wrap: wrap; gap: 10px; align-items: flex-end;
        }
        form.toevoeg-form input,
        form.toevoeg-form select { padding: 9px; border: 1px solid #ddd; border-radius: 6px; font-size: 14px; min-width: 160px; }
        form.toevoeg-form label { display: block; font-size: 12px; color: #555; margin-bottom: 3px; font-weight: bold; }

        /* TABEL */
        table { width: 100%; border-collapse: collapse; background: white; border-radius: 8px; overflow: hidden; box-shadow: 0 5px 15px rgba(0,0,0,0.1); border: 2px solid #000; }
        th { background: #1F3864; color: white; padding: 12px; text-align: left; border-bottom: 2px solid #000; }
        td { padding: 11px 12px; border-bottom: 1px solid #eee; font-size: 14px; }
        tr:hover { background: #f5f9ff; }

        /* ROL BADGES */
        .badge { display:inline-block; padding:3px 12px; border-radius:20px; font-size:12px; font-weight:bold; }
        .badge-admin   { background:#1F3864; color:#fff; }
        .badge-collega { background:#2E75B6; color:#fff; }

        /* IK-ZEL MARKERING */
        .ikzelf { background: #fffbe6 !important; }

        /* ACTIEKNOPPEN */
        .btn-bewerk   { background: #2E75B6; color: white; padding: 5px 10px; border-radius: 5px; text-decoration: none; font-size: 13px; font-weight:bold; }
        .btn-verwijder{ background: #c0392b; color: white; padding: 5px 10px; border-radius: 5px; text-decoration: none; font-size: 13px; font-weight:bold; }
        .btn-disabled { background: #aaa; color: white; padding: 5px 10px; border-radius: 5px; font-size: 13px; font-weight:bold; cursor: not-allowed; }
        .terug { display: inline-block; margin-bottom: 20px; color: #1F3864; text-decoration: none; font-size: 14px; font-weight:bold; }
        .terug:hover { text-decoration: underline; }
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
    <a class="terug" href="/admin/index.php">← Terug naar dashboard</a>
    <h1>👤 Medewerkers Beheren</h1>

    <!-- MELDINGEN -->
    <?php if ($succes): ?><div class="succes">✅ <?= htmlspecialchars($succes) ?></div><?php endif; ?>
    <?php if ($fout):   ?><div class="fout">❌ <?= htmlspecialchars($fout) ?></div><?php endif; ?>

    <div class="waarschuwing">⚠️ <strong>Let op:</strong> Wachtwoorden worden versleuteld opgeslagen. Laat het wachtwoordveld leeg bij bewerken om het huidige wachtwoord te behouden.</div>

    <!-- FORMULIER: TOEVOEGEN / BEWERKEN -->
    <form method="POST" class="toevoeg-form">
        <input type="hidden" name="actie" value="<?= $bewerkMed ? 'bewerken' : 'toevoegen' ?>">
        <?php if ($bewerkMed): ?>
            <input type="hidden" name="id" value="<?= $bewerkMed['Id'] ?>">
        <?php endif; ?>

        <div>
            <label>Volledige naam</label>
            <input type="text" name="naam" required placeholder="Jan de Vries" value="<?= htmlspecialchars($bewerkMed['Voornaam'] ?? '') ?>">
        </div>
        <div>
            <label>Gebruikersnaam</label>
            <input type="text" name="username" required placeholder="jdevries" value="<?= htmlspecialchars($bewerkMed['Gebruikersnaam'] ?? '') ?>">
        </div>
        <div>
            <label><?= $bewerkMed ? 'Nieuw wachtwoord (leeg = behouden)' : 'Wachtwoord (min. 8 tekens)' ?></label>
            <input type="password" name="<?= $bewerkMed ? 'nieuw_wachtwoord' : 'wachtwoord' ?>"
                   <?= $bewerkMed ? '' : 'required' ?>
                   placeholder="<?= $bewerkMed ? 'Laat leeg om ongewijzigd te laten' : 'Wachtwoord...' ?>">
        </div>
        <div>
            <label>Rol</label>
            <select name="rol" required>
                <option value="collega" <?= (strtolower($bewerkMed['rol_naam'] ?? '') === 'medewerker') ? 'selected' : '' ?>>Medewerker</option>
                <option value="admin"   <?= (strtolower($bewerkMed['rol_naam'] ?? '') === 'administrator') ? 'selected' : '' ?>>Administrator</option>
            </select>
        </div>

        <button type="submit" class="button" style="height:38px; padding:0 20px; font-size:14px;">
            <?= $bewerkMed ? '💾 Opslaan' : '➕ Toevoegen' ?>
        </button>
        <?php if ($bewerkMed): ?>
            <a href="/admin/medewerkers.php" class="button" style="height:38px; padding:0 20px; background:#888; border-color:#555; box-shadow:0 3px 0 #333; font-size:14px;">Annuleren</a>
        <?php endif; ?>
    </form>

    <!-- TABEL: OVERZICHT VAN MEDEWERKERS -->
    <div class="table-container">
        <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Naam</th>
                <th>Gebruikersnaam</th>
                <th>Rol</th>
                <th>Aangemaakt op</th>
                <th>Acties</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($medewerkers as $med): ?>
            <?php $isIkzelf = ($med['id'] == $_SESSION['gebruiker_id']); ?>
            <tr class="<?= $isIkzelf ? 'ikzelf' : '' ?>">
                <td><?= $med['id'] ?></td>
                <td>
                    <?= htmlspecialchars($med['naam']) ?>
                    <?php if ($isIkzelf): ?>
                        <span style="font-size:11px; color:#856404; font-weight:bold;">(jij)</span>
                    <?php endif; ?>
                </td>
                <td><?= htmlspecialchars($med['username']) ?></td>
                <td>
                    <?php 
                        $rolKlasse = strtolower($med['rol']) === 'administrator' ? 'badge-admin' : 'badge-collega';
                    ?>
                    <span class="badge <?= $rolKlasse ?>">
                        <?= htmlspecialchars($med['rol']) ?>
                    </span>
                </td>
                <td><?= date('d-m-Y', strtotime($med['Datumaangemaakt'])) ?></td>
                <td>
                    <a class="btn-bewerk" href="?bewerk=<?= $med['id'] ?>">✏️ Bewerk</a>
                    <?php if (!$isIkzelf): ?>
                        <a class="btn-verwijder" href="?verwijder=<?= $med['id'] ?>"
                           onclick="return confirm('Medewerker \'<?= htmlspecialchars(addslashes($med['naam'])) ?>\' verwijderen?')">
                            🗑️ Verwijder
                        </a>
                    <?php else: ?>
                        <span class="btn-disabled" title="Je kunt jezelf niet verwijderen">🔒 Eigen account</span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>

            <?php if (empty($medewerkers)): ?>
            <tr><td colspan="6" style="text-align:center; color:#999; padding:20px;">Geen medewerkers gevonden</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
    </div>
</div>
</body>
</html>
