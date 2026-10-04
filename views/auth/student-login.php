<div class="auth-layout">
    <aside class="auth-brand-panel">
        <a class="auth-brand" href="index.php"><img src="assets/logo/logo-mark.svg" alt=""><span>Ecole<span>Langue</span><small>APPRENDRE SANS LIMITES</small></span></a>
        <div class="auth-brand-copy">
            <span class="auth-kicker">Bienvenue à EcoleLangue</span>
            <h1>Apprenez.<br>Progressez.<br><span>Allez plus loin.</span></h1>
            <p>Connectez-vous pour accéder à votre espace et poursuivre votre parcours linguistique.</p>
        </div>
        <img class="auth-illustration" src="assets/illustrations/language-world.svg" alt="">
        <span class="auth-greeting greeting-one">Bonjour !</span>
        <span class="auth-greeting greeting-two">Hello!</span>
        <small class="auth-brand-footer">Une langue de plus, un monde de possibilités.</small>
    </aside>
    <section class="auth-form-panel">
        <div class="auth-form-inner">
            <a class="auth-mobile-brand" href="index.php"><img src="assets/logo/logo-mark.svg" alt="">Ecole<span>Langue</span></a>
            <span class="eyebrow">Votre espace personnel</span>
            <h2>Connexion</h2>
            <p class="auth-subtitle">Choisissez votre type de compte</p>
            <div class="account-switch" role="group" aria-label="Type de compte">
                <a class="active" href="index.php?page=student-login" aria-current="page">♙ &nbsp; Étudiant</a>
                <a href="index.php?page=admin-login">▣ &nbsp; Admin</a>
            </div>
            <form method="post" action="index.php">
                <?= csrfField() ?><input type="hidden" name="action" value="student-login">
                <div class="mb-3"><label class="form-label" for="login">Login ou email</label>
                    <div class="input-with-icon"><span aria-hidden="true">♙</span><input class="form-control" id="login" name="login" maxlength="150" autocomplete="username" placeholder="Entrez votre login ou email" required autofocus></div>
                </div>
                <div class="mb-4"><label class="form-label" for="password">Mot de passe</label>
                    <div class="input-with-icon"><span aria-hidden="true">♙</span><input class="form-control" id="password" type="password" name="password" autocomplete="current-password" placeholder="Entrez votre mot de passe" required><button class="password-toggle" type="button" data-toggle-password="password" aria-label="Afficher le mot de passe">◉</button></div>
                </div>
                <button class="btn btn-primary btn-lg w-100" type="submit">Se connecter <span aria-hidden="true">→</span></button>
            </form>
            <p class="auth-switch-note">Vous n’avez pas de compte ? <a href="index.php?page=student-login">Contactez l’administration</a></p>
        </div>
    </section>
</div>
