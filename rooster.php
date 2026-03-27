<!DOCTYPE html>
<html lang="nl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Rooster - FitForFun</title>

<link rel="stylesheet" href="style.css">
</head>

<body>

<div class="page-content">

<!-- NAVBAR -->
<header class="navbar">

<div class="nav-left">

<a href="index.html">
<img src="./img/FFF.Logo.png" alt="FitForFun logo" class="logo">
</a>

<div class="search-bar">
<img src="./icon/search.png" alt="zoek icoon" class="icon search-icon">
<input type="text" placeholder="Zoeken...">
</div>

</div>

<nav class="nav-links">

<a href="index.html">HOME</a>
<a href="rooster.html" class="active">ROOSTER</a>
<a href="aanbidingen.html">AANBIEDINGEN</a>
<a href="informatie.html">INFO</a>

<button class="login-btn" id="openLogin">
INLOGGEN
<img src="./icon/profile.png" alt="profiel icoon" class="icon profile-icon">
</button>

</nav>

</header>

<!-- ROOSTER -->
<section class="rooster">

<h1>ROOSTER</h1>

<p class="rooster-sub">
Bekijk ons wekelijkse lesrooster
</p>

<div class="rooster-container">

<div class="rooster-card">
<h2>Maandag</h2>
<p>18:00 - Fitness</p>
<p>19:00 - Yoga</p>
<p>20:00 - Kickboksen</p>
</div>

<div class="rooster-card">
<h2>Dinsdag</h2>
<p>18:00 - Crossfit</p>
<p>19:00 - Spinning</p>
<p>20:00 - Bootcamp</p>
</div>

<div class="rooster-card">
<h2>Woensdag</h2>
<p>18:00 - Fitness</p>
<p>19:00 - HIIT</p>
<p>20:00 - Yoga</p>
</div>

<div class="rooster-card">
<h2>Donderdag</h2>
<p>18:00 - Crossfit</p>
<p>19:00 - Kickboksen</p>
<p>20:00 - Spinning</p>
</div>

<div class="rooster-card">
<h2>Vrijdag</h2>
<p>18:00 - Fitness</p>
<p>19:00 - Bootcamp</p>
<p>20:00 - Yoga</p>
</div>

</div>

</section>

</div>

<!-- LOGIN MODAL -->
<div class="login-overlay" id="loginOverlay">

<div class="login-modal">

<img src="./img/FFF.Logo.png" alt="logo" class="modal-logo">

<h2>Inloggen</h2>

<input type="text" placeholder="Gebruikersnaam">
<input type="password" placeholder="Wachtwoord">

<button class="button modal-btn">INLOGGEN</button>

<p class="close-modal" id="closeLogin">Sluiten ✕</p>

</div>
</div>

<script src="script.js"></script>

</body>
</html>