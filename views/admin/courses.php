<?php
/** @var list<array<string, mixed>> $courses */
/** @var array<string, mixed>|null $editing */
?>
<div class="page-heading mb-4">
    <div><span class="eyebrow">Catalogue de formation</span><h1 class="h2 fw-bold mt-2 mb-1">Gestion des cours</h1><p class="text-secondary mb-0">Créez et mettez à jour les sessions proposées.</p></div>
</div>
<section class="content-card mb-4">
    <div class="card-heading"><div><h2 class="h5 fw-bold mb-1"><?= $editing ? 'Modifier le cours' : 'Ajouter un cours' ?></h2><p class="text-secondary small mb-0">Les places restantes sont calculées à partir des demandes déjà réservées.</p></div>
        <?php if ($editing): ?><a class="btn btn-sm btn-outline-secondary" href="index.php?page=admin-courses">Annuler</a><?php endif; ?>
    </div>
    <form method="post" action="index.php">
        <?= csrfField() ?><input type="hidden" name="action" value="admin-course-save"><input type="hidden" name="course_id" value="<?= (int) ($editing['idCours'] ?? 0) ?: '' ?>">
        <div class="row g-3">
            <div class="col-md-6"><label class="form-label" for="langue">Langue</label><input class="form-control" id="langue" name="langue" maxlength="80" value="<?= e($editing['langue'] ?? '') ?>" required></div>
            <div class="col-md-6"><label class="form-label" for="niveau">Niveau</label><input class="form-control" id="niveau" name="niveau" maxlength="80" placeholder="Débutant, intermédiaire…" value="<?= e($editing['niveau'] ?? '') ?>" required></div>
            <div class="col-md-4"><label class="form-label" for="prix">Prix (DH)</label><input class="form-control" id="prix" name="prix" type="number" min="0" step="0.01" value="<?= e($editing['prix'] ?? '') ?>" required></div>
            <div class="col-md-4"><label class="form-label" for="dateDebut">Date de début</label><input class="form-control" id="dateDebut" name="dateDebut" type="date" value="<?= e($editing['dateDebut'] ?? '') ?>" required></div>
            <div class="col-md-4"><label class="form-label" for="dateFin">Date de fin</label><input class="form-control" id="dateFin" name="dateFin" type="date" value="<?= e($editing['dateFin'] ?? '') ?>" required></div>
            <div class="col-md-4"><label class="form-label" for="places_total">Places totales</label><input class="form-control" id="places_total" name="places_total" type="number" min="1" step="1" value="<?= e($editing['places_total'] ?? '') ?>" required></div>
        </div>
        <div class="text-end mt-4"><button class="btn btn-primary" type="submit"><?= $editing ? 'Enregistrer le cours' : 'Créer le cours' ?></button></div>
    </form>
</section>
<section class="content-card p-0 overflow-hidden">
    <div class="table-responsive admin-course-table"><table class="table align-middle mb-0">
        <thead><tr><th>Langue / niveau</th><th>Dates</th><th>Tarif</th><th>Places</th><th>Disponibilité</th><th class="text-end">Actions</th></tr></thead>
        <tbody>
        <?php foreach ($courses as $course): ?>
            <tr>
                <td><strong><?= e($course['langue']) ?></strong><div class="small text-secondary"><?= e($course['niveau']) ?></div></td>
                <td><?= e(date('d/m/Y', strtotime($course['dateDebut']))) ?><div class="small text-secondary"><?= e(date('d/m/Y', strtotime($course['dateFin']))) ?></div></td>
                <td><?= e(number_format((float) $course['prix'], 2, ',', ' ')) ?> DH</td>
                <td><?= (int) $course['places_restantes'] ?> / <?= (int) $course['places_total'] ?></td>
                <td><?= statusBadge((int) $course['places_restantes'] > 0 ? 'Disponible' : 'Complet') ?></td>
                <td class="text-end text-nowrap">
                    <a class="btn btn-sm btn-outline-primary" href="index.php?page=admin-courses&amp;edit=<?= (int) $course['idCours'] ?>">Modifier</a>
                    <form method="post" action="index.php" class="d-inline">
                        <?= csrfField() ?><input type="hidden" name="action" value="admin-course-delete"><input type="hidden" name="course_id" value="<?= (int) $course['idCours'] ?>">
                        <button class="btn btn-sm btn-outline-danger" type="submit">Supprimer</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$courses): ?><tr><td colspan="6" class="text-center text-secondary py-4">Aucun cours enregistré.</td></tr><?php endif; ?>
        </tbody>
    </table></div>
    <div class="admin-course-cards">
        <?php foreach ($courses as $course): ?>
            <article class="admin-course-card">
                <div class="course-preview-heading">
                    <div><h2><?= e($course['langue']) ?></h2><span class="course-preview-level"><?= e($course['niveau']) ?></span></div>
                    <?= statusBadge((int) $course['places_restantes'] > 0 ? 'Disponible' : 'Complet') ?>
                </div>
                <dl class="course-preview-details">
                    <div><dt>Dates</dt><dd><?= e(date('d/m/Y', strtotime($course['dateDebut']))) ?> – <?= e(date('d/m/Y', strtotime($course['dateFin']))) ?></dd></div>
                    <div><dt>Tarif</dt><dd><?= e(number_format((float) $course['prix'], 2, ',', ' ')) ?> DH</dd></div>
                    <div><dt>Places restantes / total</dt><dd><?= (int) $course['places_restantes'] ?> / <?= (int) $course['places_total'] ?></dd></div>
                </dl>
                <div class="admin-course-actions">
                    <a class="btn btn-sm btn-outline-primary" href="index.php?page=admin-courses&amp;edit=<?= (int) $course['idCours'] ?>">Modifier</a>
                    <form method="post" action="index.php">
                        <?= csrfField() ?><input type="hidden" name="action" value="admin-course-delete"><input type="hidden" name="course_id" value="<?= (int) $course['idCours'] ?>">
                        <button class="btn btn-sm btn-outline-danger" type="submit">Supprimer</button>
                    </form>
                </div>
            </article>
        <?php endforeach; ?>
        <?php if (!$courses): ?><p class="text-center text-secondary py-4 mb-0">Aucun cours enregistré.</p><?php endif; ?>
    </div>
</section>
