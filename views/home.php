<?php
$languageFlags = [
    'anglais' => '🇬🇧',
    'français' => '🇫🇷',
    'espagnol' => '🇪🇸',
    'allemand' => '🇩🇪',
    'italien' => '🇮🇹',
];
?>
<section class="public-showcase" id="apropos">
    <div class="showcase-intro">
        <span class="eyebrow">Apprendre sans limites</span>
        <h1>Apprenez une<br><span>nouvelle langue</span></h1>
        <p>Ouvrez de nouvelles opportunités personnelles et professionnelles !</p>
        <div class="language-list" aria-label="Langues enseignées">
            <?php foreach (['Anglais', 'Français', 'Espagnol', 'Allemand', 'Italien'] as $language): ?>
                <span><i aria-hidden="true"><?= $languageFlags[mb_strtolower($language)] ?></i><?= e($language) ?></span>
            <?php endforeach; ?>
        </div>
        <a class="btn btn-primary" href="#cours">Explorer les cours <span aria-hidden="true">→</span></a>
        <div class="showcase-art">
            <img src="assets/illustrations/language-world.svg" alt="Illustration de livres de langues et d’un globe terrestre">
        </div>
    </div>

    <div class="course-showcase" id="cours">
        <div class="course-panel">
            <div class="panel-heading">
                <div>
                    <span class="eyebrow">Trouvez votre prochain cours</span>
                    <h2>Liste des cours disponibles</h2>
                </div>
                <span class="course-count"><?= count($courses) ?> cours</span>
            </div>
            <?php if (!$courses): ?>
                <div class="empty-state"><h3 class="h5">Aucun cours à venir</h3><p class="mb-0 text-secondary">Revenez bientôt pour découvrir nos prochaines sessions.</p></div>
            <?php else: ?>
                <div class="table-responsive course-table-wrap course-list-table">
                    <table class="table course-table align-middle mb-0">
                        <thead>
                            <tr><th>Langue</th><th>Niveau</th><th>Prix</th><th>Date début</th><th>Date fin</th><th>Places (Total / Restantes)</th><th>État</th></tr>
                        </thead>
                        <tbody>
                        <?php foreach ($courses as $course): ?>
                            <tr>
                                <td><span class="table-language"><i aria-hidden="true"><?= $languageFlags[mb_strtolower($course['langue'])] ?? '🌐' ?></i><strong><?= e($course['langue']) ?></strong></span></td>
                                <td><?= e($course['niveau']) ?></td>
                                <td class="price-cell"><?= e(number_format((float) $course['prix'], 0, ',', ' ')) ?> DH</td>
                                <td><?= e(date('Y-m-d', strtotime($course['dateDebut']))) ?></td>
                                <td><?= e(date('Y-m-d', strtotime($course['dateFin']))) ?></td>
                                <td><?= (int) $course['places_total'] ?> / <?= (int) $course['places_restantes'] ?></td>
                                <td><?= statusBadge((int) $course['places_restantes'] > 0 ? 'Disponible' : 'Complet') ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div class="course-list-cards">
                    <?php foreach ($courses as $course): ?>
                        <article class="course-preview-card">
                            <div class="course-preview-heading">
                                <div>
                                    <span class="table-language"><i aria-hidden="true"><?= $languageFlags[mb_strtolower($course['langue'])] ?? '🌐' ?></i><strong><?= e($course['langue']) ?></strong></span>
                                    <span class="course-preview-level"><?= e($course['niveau']) ?></span>
                                </div>
                                <?= statusBadge((int) $course['places_restantes'] > 0 ? 'Disponible' : 'Complet') ?>
                            </div>
                            <dl class="course-preview-details">
                                <div><dt>Prix</dt><dd class="price-cell"><?= e(number_format((float) $course['prix'], 0, ',', ' ')) ?> DH</dd></div>
                                <div><dt>Date début</dt><dd><?= e(date('d/m/Y', strtotime($course['dateDebut']))) ?></dd></div>
                                <div><dt>Date fin</dt><dd><?= e(date('d/m/Y', strtotime($course['dateFin']))) ?></dd></div>
                                <div><dt>Places restantes</dt><dd><?= (int) $course['places_restantes'] ?> / <?= (int) $course['places_total'] ?></dd></div>
                            </dl>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            <div class="course-banner">
                <span class="banner-icon" aria-hidden="true">✦</span>
                <span><strong>Des cours adaptés à tous les niveaux !</strong><small>Développez vos compétences avec nos formateurs expérimentés.</small></span>
                <a class="btn btn-primary btn-sm" href="index.php?page=student-login">S’inscrire maintenant <span aria-hidden="true">→</span></a>
            </div>
        </div>
    </div>
</section>
