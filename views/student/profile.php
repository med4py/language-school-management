<div class="page-heading mb-4">
    <div><span class="eyebrow">Votre espace</span><h1 class="h2 fw-bold mt-2 mb-1">Mon profil</h1><p class="text-secondary mb-0">Consultez et modifiez vos informations personnelles.</p></div>
</div>
<div class="row g-4">
    <div class="col-lg-4">
        <section class="profile-summary h-100">
            <div class="profile-avatar"><?= e(strtoupper(substr($student['nom'], 0, 1))) ?></div>
            <h2 class="h4 fw-bold mt-3"><?= e($student['nom']) ?></h2>
            <p class="text-secondary"><?= e($student['email']) ?></p>
            <div class="profile-detail"><span>CIN</span><strong><?= e($student['cin']) ?></strong></div>
            <div class="profile-detail"><span>Téléphone</span><strong><?= e($student['tel'] ?: 'Non renseigné') ?></strong></div>
        </section>
    </div>
    <div class="col-lg-8">
        <section class="content-card">
            <div class="card-heading"><div><h2 class="h5 fw-bold mb-1">Informations personnelles</h2><p class="text-secondary small mb-0">Gardez vos coordonnées à jour.</p></div></div>
            <form method="post" action="index.php">
                <?= csrfField() ?><input type="hidden" name="action" value="student-profile-save">
                <div class="row g-3">
                    <div class="col-md-6"><label class="form-label" for="nom">Nom complet</label><input class="form-control" id="nom" name="nom" value="<?= e($student['nom']) ?>" maxlength="100" required></div>
                    <div class="col-md-6"><label class="form-label" for="cin">CIN</label><input class="form-control" id="cin" name="cin" value="<?= e($student['cin']) ?>" maxlength="20" required></div>
                    <div class="col-md-6"><label class="form-label" for="email">Adresse e-mail</label><input class="form-control" id="email" name="email" type="email" value="<?= e($student['email']) ?>" maxlength="150" required></div>
                    <div class="col-md-6"><label class="form-label" for="tel">Téléphone</label><input class="form-control" id="tel" name="tel" value="<?= e($student['tel']) ?>" maxlength="30"></div>
                </div>
                <div class="text-end mt-4"><button class="btn btn-primary" type="submit">Enregistrer les modifications</button></div>
            </form>
        </section>
    </div>
</div>
