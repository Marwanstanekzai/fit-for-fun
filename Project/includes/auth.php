<?php
function checkLogin() {
    if (!isset($_SESSION['gebruiker_id'])) {
        header("Location: /");
        exit();
    }
}

function checkAdmin() {
    checkLogin();
    if ($_SESSION['rol'] !== 'admin') {
        header("Location: /error.php?code=403");
        exit();
    }
}

function checkCollega() {
    checkLogin();
    if (!in_array($_SESSION['rol'], ['admin', 'collega'])) {
        header("Location: /error.php?code=403");
        exit();
    }
}
?>