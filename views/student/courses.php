<div class="page-heading mb-4">
    <div><span class="eyebrow">Choisissez votre prochaine étape</span><h1 class="h2 fw-bold mt-2 mb-1">Inscription aux cours</h1><p class="text-secondary mb-0">Votre demande sera envoyée à l’administration pour confirmation.</p></div>
</div>
<?php if (!$courses): ?>
    <div class="empty-state"><h2 class="h5">Aucun cours ouvert à l’inscription</h2><p class="mb-0 text-secondary">Les prochaines sessions apparaîtront ici.</p></div>
<?php else: ?>
    <div class="row g-3">
        <?php foreach ($courses as $course): ?>
            <div class="col-lg-6">
                <article class="content-card h-100">
                    <div class="d-flex justify-content-between align-items-start gap-3">
                        <div><div class="eyebrow"><?= e($course['niveau']) ?></div><h2 class="h4 fw-bold mt-2"><?= e($course['langue']) ?></h2></div>
                        <?= statusBadge((int) $course['places_restantes'] > 0 ? 'Disponible' : 'Complet') ?>
                    </div>
                    <div class="course-meta mt-3">
                        <div><span>Dates</span><strong><?= e(date('d/m/Y', strtotime($course['dateDebut']))) ?> – <?= e(date('d/m/Y', strtotime($course['dateFin']))) ?></strong></div>
                        <div><span>Places disponibles</span><strong><?= (int) $course['places_restantes'] ?> / <?= (int) $course['places_total'] ?></strong></div>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mt-4 pt-3 border-top">
                        <strong class="price"><?= e(number_format((float) $course['prix'], 2, ',', ' ')) ?> DH</strong>
                        <?php if ((int) $course['places_restantes'] > 0): ?>
                            <form method="post" action="index.php">
                                <?= csrfField() ?><input type="hidden" name="action" value="student-enroll"><input type="hidden" name="course_id" value="<?= (int) $course['idCours'] ?>">
                                <button class="btn btn-primary" type="submit">Demander une place</button>
                            </form>
                        <?php else: ?><span class="text-secondary small">Aucune place disponible</span><?php endif; ?>
                    </div>
                </article>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
