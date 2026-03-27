<!-- FOOTER -->
<footer class="footer">
    <div class="footer-container">
        <div class="footer-box">
            <img src="<?= $root_path ?>img/FFF.Logo.png" class="footer-logo">
            <p>Blijf fit, blijf sterk met FitForFun.</p>
        </div>

        <div class="footer-box">
            <h3>Pagina's</h3>
            <a href="<?= $root_path ?>index.php">Home</a>
            <a href="<?= $root_path ?>rooster.php">Rooster</a>
            <a href="<?= $root_path ?>aanbiedingen.php">Aanbiedingen</a>
            <a href="<?= $root_path ?>informatie.php">Over ons</a>
        </div>

        <div class="footer-box">
            <h3>Contact</h3>
            <p>Email: info@fitforfun.nl</p>
            <p>Tel: 0612345678</p>
            <p>Adres: Amsterdam</p>
        </div>

        <div class="footer-box">
            <h3>Openingstijden</h3>
            <p>Ma - Vr: 08:00 - 22:00</p>
            <p>Za: 09:00 - 18:00</p>
            <p>Zo: 10:00 - 16:00</p>
        </div>
    </div>

    <div class="footer-bottom">
        <p>© 2026 FitForFun - Alle rechten voorbehouden</p>
    </div>
</footer>

<script>
    // Login Modal Handlers
    const openBtn = document.getElementById("openLogin");
    const closeBtn = document.getElementById("closeLogin");
    const overlay = document.getElementById("loginOverlay");
    const page = document.querySelector(".page-content");

    if (openBtn && overlay) {
        openBtn.addEventListener("click", function(e){
            e.preventDefault();
            overlay.classList.add("active");
            if (page) page.classList.add("blur");
        });
    }

    if (closeBtn && overlay) {
        closeBtn.addEventListener("click", function(){
            overlay.classList.remove("active");
            if (page) page.classList.remove("blur");
        });
    }
</script>

<!-- LOGIN MODAL (Gedeeld) -->
<div class="login-overlay <?= (isset($fout) && $fout) ? 'active' : '' ?>" id="loginOverlay">
    <div class="login-modal">
        <img src="<?= $root_path ?>img/FFF.Logo.png" class="modal-logo">
        <h2>Inloggen</h2>

        <?php if (isset($fout) && $fout): ?>
            <p style="color:red; margin-bottom:15px; font-weight:bold;">⚠️ <?= htmlspecialchars($fout) ?></p>
        <?php endif; ?>

        <form method="POST">
            <input type="hidden" name="login_submit" value="1">
            <input type="text" name="username" placeholder="Gebruikersnaam" required value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
            <input type="password" name="wachtwoord" placeholder="Wachtwoord" required>
            <button type="submit" class="button modal-btn">INLOGGEN</button>
        </form>

        <p class="close-modal" id="closeLogin">Sluiten ✕</p>
    </div>
</div>

</body>
</html>
