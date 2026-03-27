<?php
$isAdmin   = isset($_SESSION['rol']) && $_SESSION['rol'] === 'admin';
$isCollega = isset($_SESSION['rol']) && $_SESSION['rol'] === 'collega';
?>
<nav>
    <strong>🏋️ Fit for Fun</strong>
    <div>
        <?php if ($isAdmin): ?>
            <a href="/admin/index.php">Dashboard</a>
            <a href="/admin/leden.php">Leden</a>
            <a href="/admin/lessen.php">Lessen</a>
        <?php elseif ($isCollega): ?>
            <a href="/collegas/index.php">Dashboard</a>
        <?php endif; ?>
        <a href="/logout.php">Uitloggen</a>
    </div>
</nav>