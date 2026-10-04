<div class="auth-layout">
    <aside class="auth-brand-panel">
        <a class="auth-brand" href="index.php"><img src="assets/logo/logo-mark.svg" alt=""><span>Ecole<span>Langue</span><small>APPRENDRE SANS LIMITES</small></span></a>
        <div class="auth-brand-copy">
            <span class="auth-kicker">Espace de gestion</span>
            <h1>Ensemble,<br>faisons grandir<br><span>les talents.</span></h1>
            <p>Connectez-vous pour gérer les cours, les étudiants et les inscriptions de l’école.</p>
        </div>
        <img class="auth-illustration" src="assets/illustrations/language-world.svg" alt="">
        <span class="auth-greeting greeting-one">Bienvenue</span>
        <span class="auth-greeting greeting-two">Welcome!</span>
        <small class="auth-brand-footer">Une école ouverte sur le monde.</small>
    </aside>
    <section class="auth-form-panel">
        <div class="auth-form-inner">
            <a class="auth-mobile-brand" href="index.php"><img src="assets/logo/logo-mark.svg" alt="">Ecole<span>Langue</span></a>
            <span class="eyebrow">Espace de gestion</span>
            <h2>Connexion</h2>
            <p class="auth-subtitle">Choisissez votre type de compte</p>
            <div class="account-switch" role="group" aria-label="Type de compte">
                <a href="index.php?page=student-login">♙ &nbsp; Étudiant</a>
                <a class="active" href="index.php?page=admin-login" aria-current="page">▣ &nbsp; Admin</a>
            </div>
            <form method="post" action="index.php">
                <?= csrfField() ?><input type="hidden" name="action" value="admin-login">
                <div class="mb-3"><label class="form-label" for="login">Login ou email</label>
                    <div class="input-with-icon"><span aria-hidden="true">♙</span><input class="form-control" id="login" name="login" maxlength="150" autocomplete="username" placeholder="Entrez votre login ou email" required autofocus></div>
                </div>
                <div class="mb-4"><label class="form-label" for="password">Mot de passe</label>
                    <div class="input-with-icon"><span aria-hidden="true">♙</span><input class="form-control" id="password" type="password" name="password" autocomplete="current-password" placeholder="Entrez votre mot de passe" required><button class="password-toggle" type="button" data-toggle-password="password" aria-label="Afficher le mot de passe">◉</button></div>
                </div>
                <button class="btn btn-primary btn-lg w-100" type="submit">Se connecter <span aria-hidden="true">→</span></button>
            </form>
            <p class="auth-switch-note"><a href="index.php">← Retour à l’accueil</a></p>
        </div>
    </section>
</div>
