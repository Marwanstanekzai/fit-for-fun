<?php
/**
 * make_hash.php
 * Hulpscript om wachtwoorden te hashen voor de admins-tabel.
 * ⚠️ VERWIJDER DIT BESTAND VAN DE SERVER NA GEBRUIK!
 */

$resultaten   = [];
$customHash   = '';
$customPlain  = '';
$sqlInsert    = '';

// ── Standaard wachtwoorden (altijd zichtbaar) ──────────────────────────────
$standaard = [
    'Admin123!'   => password_hash('Admin123!',   PASSWORD_BCRYPT),
    'Collega123!' => password_hash('Collega123!', PASSWORD_BCRYPT),
];

// ── Eigen wachtwoord hashen via formulier ──────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['wachtwoord'])) {
    $customPlain = $_POST['wachtwoord'];
    $customHash  = password_hash($customPlain, PASSWORD_BCRYPT);

    $naam     = trim($_POST['naam']     ?? 'Nieuwe Gebruiker');
    $username = trim($_POST['username'] ?? 'gebruiker');
    $rol      = in_array($_POST['rol'] ?? '', ['admin', 'collega']) ? $_POST['rol'] : 'collega';

    $sqlInsert = "INSERT INTO admins (naam, username, password, rol) VALUES\n"
               . "('{$naam}', '{$username}', '{$customHash}', '{$rol}');";
}
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <title>Wachtwoord Hasher – Fit for Fun</title>
    <style>
        * { margin:0; padding:0; box-sizing:border-box; font-family: Arial, Helvetica, sans-serif; }

        body {
            background: #1F3864;
            display: flex; justify-content: center; align-items: flex-start;
            min-height: 100vh; padding: 40px 20px;
        }

        .card {
            background: #fff;
            border-radius: 16px;
            border: 3px solid #000;
            box-shadow: 0 10px 30px rgba(0,0,0,0.5);
            width: 100%; max-width: 780px;
            padding: 36px 40px;
        }

        h1 { color: #1F3864; font-size: 26px; margin-bottom: 6px; }
        .subtitle { color: #666; font-size: 14px; margin-bottom: 28px; }

        h2 { color: #1F3864; font-size: 18px; margin: 28px 0 14px; border-bottom: 2px solid #eee; padding-bottom: 6px; }

        /* WAARSCHUWING */
        .warning {
            background: #fff3cd; border: 2px solid #f0ad4e; color: #856404;
            padding: 12px 16px; border-radius: 8px; font-size: 14px;
            margin-bottom: 24px; font-weight: bold;
        }

        /* SUCCESS / RESULT BOX */
        .result-box {
            background: #f0f8ff; border: 2px solid #b3d7f5; border-radius: 8px;
            padding: 16px 20px; margin: 16px 0; font-size: 14px;
        }
        .result-box .label { font-weight: bold; color: #555; margin-bottom: 4px; font-size: 12px; text-transform: uppercase; }
        .result-box .hash-val {
            font-family: 'Courier New', monospace; font-size: 13px; color: #1F3864;
            word-break: break-all; background: #e8f4fd; padding: 8px 12px; border-radius: 6px;
            cursor: pointer; border: 1px dashed #aac;
            user-select: all;
        }
        .result-box .hash-val:hover { background: #d0e8fa; }
        .copy-hint { font-size: 11px; color: #888; margin-top: 4px; }

        /* SQL BOX */
        .sql-box {
            background: #1e1e2e; color: #a6e3a1; border-radius: 8px;
            padding: 14px 18px; font-family: 'Courier New', monospace; font-size: 13px;
            white-space: pre-wrap; word-break: break-all; cursor: pointer;
            user-select: all; border: 2px solid #333;
        }
        .sql-box:hover { background: #252535; }

        /* FORMULIER */
        form { display: flex; flex-direction: column; gap: 14px; }
        .form-row { display: flex; gap: 14px; flex-wrap: wrap; }
        .form-group { display: flex; flex-direction: column; gap: 5px; flex: 1; min-width: 180px; }
        label { font-size: 13px; font-weight: bold; color: #444; }
        input[type="text"],
        input[type="password"],
        select {
            padding: 10px 14px; border: 2px solid #ddd; border-radius: 8px;
            font-size: 14px; outline: none; transition: border-color 0.2s;
        }
        input:focus, select:focus { border-color: #1F3864; }

        button[type="submit"] {
            width: 100%; height: 50px; font-size: 16px; font-weight: 800;
            cursor: pointer; border: 3px solid #000; border-radius: 40px;
            background-color: #ff8c00; color: #000;
            box-shadow: 0 5px 0 #1e88c9;
            transition: all 0.15s ease-in-out;
            margin-top: 6px;
        }
        button[type="submit"]:hover { transform: translateY(3px); box-shadow: 0 2px 0 #1e88c9; }
        button[type="submit"]:active { transform: translateY(5px); box-shadow: none; }

        /* TABEL STANDAARD HASHES */
        table { width: 100%; border-collapse: collapse; border: 2px solid #000; border-radius: 8px; overflow: hidden; }
        th { background: #1F3864; color: white; padding: 10px 14px; text-align: left; }
        td { padding: 10px 14px; border-bottom: 1px solid #eee; font-size: 13px; }
        td.mono { font-family: 'Courier New', monospace; word-break: break-all; font-size: 12px; color: #1F3864; cursor: pointer; user-select: all; }
        td.mono:hover { background: #e8f4fd; }
        tr:last-child td { border-bottom: none; }

        .badge-admin   { background:#1F3864; color:#fff; padding:2px 10px; border-radius:20px; font-size:12px; font-weight:bold; }
        .badge-collega { background:#2E75B6; color:#fff; padding:2px 10px; border-radius:20px; font-size:12px; font-weight:bold; }

        /* COPIED TOAST */
        #toast { position:fixed; bottom:30px; right:30px; background:#1F3864; color:#fff; padding:12px 20px; border-radius:10px; font-size:14px; opacity:0; transition:opacity 0.3s; pointer-events:none; }
        #toast.show { opacity:1; }
    </style>
</head>
<body>

<div class="card">
    <h1>🔐 Wachtwoord Hash Generator</h1>
    <p class="subtitle">Genereer BCrypt-hashes voor de <code>admins</code>-tabel in de Fit for Fun database.</p>

    <div class="warning">
        ⚠️ <strong>Let op:</strong> Verwijder dit bestand (<code>make_hash.php</code>) direct van de server na gebruik! Het is bedoeld als eenmalig hulpscript.
    </div>

    <!-- ── EIGEN WACHTWOORD GENERATOR ─────────────────────────────────── -->
    <h2>✏️ Hash een eigen wachtwoord</h2>

    <form method="POST">
        <div class="form-row">
            <div class="form-group">
                <label for="naam">Volledige naam</label>
                <input type="text" id="naam" name="naam" placeholder="bijv. Jan de Vries"
                       value="<?= htmlspecialchars($_POST['naam'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label for="username">Gebruikersnaam</label>
                <input type="text" id="username" name="username" placeholder="bijv. jdevries"
                       value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
            </div>
            <div class="form-group" style="max-width:140px;">
                <label for="rol">Rol</label>
                <select id="rol" name="rol">
                    <option value="collega" <?= ($_POST['rol'] ?? '') === 'collega' ? 'selected' : '' ?>>Medewerker</option>
                    <option value="admin"   <?= ($_POST['rol'] ?? '') === 'admin'   ? 'selected' : '' ?>>Administrator</option>
                </select>
            </div>
        </div>
        <div class="form-group">
            <label for="wachtwoord">Wachtwoord (min. 8 tekens)</label>
            <input type="password" id="wachtwoord" name="wachtwoord" placeholder="Voer wachtwoord in..." required minlength="8">
        </div>
        <button type="submit">🔒 Genereer Hash &amp; SQL</button>
    </form>

    <?php if ($customHash): ?>
    <div class="result-box" style="margin-top:20px;">
        <div class="label">Wachtwoord (plain-text)</div>
        <div class="hash-val" onclick="kopieer(this)"><?= htmlspecialchars($customPlain) ?></div>

        <div class="label" style="margin-top:12px;">BCrypt Hash (klik om te kopiëren)</div>
        <div class="hash-val" onclick="kopieer(this)"><?= htmlspecialchars($customHash) ?></div>
        <div class="copy-hint">💡 Klik op de hash om te kopiëren</div>
    </div>

    <div class="label" style="margin-top:16px; font-size:12px; font-weight:bold; color:#555; text-transform:uppercase;">SQL INSERT (klik om te kopiëren)</div>
    <div class="sql-box" onclick="kopieer(this)"><?= htmlspecialchars($sqlInsert) ?></div>
    <?php endif; ?>

    <!-- ── STANDAARD HASHES ───────────────────────────────────────────── -->
    <h2>📋 Standaard wachtwoord-hashes</h2>
    <p style="font-size:13px; color:#666; margin-bottom:14px;">
        Onderstaande hashes worden elke keer <em>opnieuw gegenereerd</em> (maar zijn altijd geldig voor het bijbehorende wachtwoord).
        Klik op een hash om hem te kopiëren.
    </p>

    <table>
        <thead>
            <tr>
                <th>Gebruiker</th>
                <th>Wachtwoord</th>
                <th>Rol</th>
                <th>BCrypt Hash</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Beheerder FitForFun</td>
                <td><code>Admin123!</code></td>
                <td><span class="badge-admin">admin</span></td>
                <td class="mono" onclick="kopieer(this)"><?= $standaard['Admin123!'] ?></td>
            </tr>
            <tr>
                <td>Marwan Stanekzai</td>
                <td><code>Collega123!</code></td>
                <td><span class="badge-collega">collega</span></td>
                <td class="mono" onclick="kopieer(this)"><?= $standaard['Collega123!'] ?></td>
            </tr>
            <tr>
                <td>Ayssar Medewerker</td>
                <td><code>Collega123!</code></td>
                <td><span class="badge-collega">collega</span></td>
                <td class="mono" onclick="kopieer(this)"><?= $standaard['Collega123!'] ?></td>
            </tr>
        </tbody>
    </table>

    <!-- ── KANT-EN-KLARE SQL ──────────────────────────────────────────── -->
    <h2>🗄️ Kant-en-klare SQL INSERT (klik om te kopiëren)</h2>
    <div class="sql-box" onclick="kopieer(this)"><?php
        echo "INSERT INTO admins (naam, username, password, rol) VALUES\n";
        echo "('Beheerder FitForFun', 'admin',  '" . $standaard['Admin123!']   . "', 'admin'),\n";
        echo "('Marwan Stanekzai',    'marwan', '" . $standaard['Collega123!'] . "', 'collega'),\n";
        echo "('Ayssar Medewerker',   'ayssar', '" . $standaard['Collega123!'] . "', 'collega');";
    ?></div>

    <p style="font-size:12px; color:#999; margin-top:20px; text-align:center;">
        Kopieer de SQL, plak het in phpMyAdmin → SQL-tabblad → Uitvoeren.
        Daarna: verwijder dit bestand!
    </p>
</div>

<!-- TOAST MELDING -->
<div id="toast">✅ Gekopieerd!</div>

<script>
function kopieer(el) {
    const tekst = el.innerText.trim();
    navigator.clipboard.writeText(tekst).then(() => {
        const toast = document.getElementById('toast');
        toast.classList.add('show');
        setTimeout(() => toast.classList.remove('show'), 2000);
    });
}
</script>
</body>
</html>