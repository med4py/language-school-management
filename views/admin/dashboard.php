<div class="page-heading mb-4">
    <div><span class="eyebrow">Vue d’ensemble</span><h1 class="h2 fw-bold mt-2 mb-1">Tableau de bord</h1><p class="text-secondary mb-0">Bienvenue, <?= e($_SESSION['admin_name'] ?? 'administrateur') ?>. Voici l’activité de l’école.</p></div>
    <a class="btn btn-primary" href="index.php?page=admin-pending">Voir les demandes en attente</a>
</div>
<div class="row g-3">
    <?php
    $cards = [
        ['Étudiants', $stats['students'], '♙', 'admin-students'],
        ['Cours', $stats['courses'], '▣', 'admin-courses'],
        ['Inscriptions', $stats['registrations'], '▤', 'admin-registrations'],
        ['Inscriptions payées', $stats['paid'], '✓', 'admin-registrations'],
    ];
    foreach ($cards as [$label, $value, $icon, $link]):
    ?>
        <div class="col-sm-6 col-xl-3"><a class="stat-card" href="index.php?page=<?= e($link) ?>"><span class="stat-icon"><?= $icon ?></span><span class="stat-label"><?= e($label) ?></span><strong class="stat-value"><?= (int) $value ?></strong><span class="stat-link">Consulter <span aria-hidden="true">→</span></span></a></div>
    <?php endforeach; ?>
</div>
<section class="content-card mt-4">
    <h2 class="h5 fw-bold">Raccourcis de gestion</h2>
    <p class="text-secondary">Gérez les sessions de cours, les comptes étudiants et les demandes d’inscription depuis le menu d’administration.</p>
    <div class="d-flex flex-wrap gap-2">
        <a class="btn btn-outline-primary" href="index.php?page=admin-courses">Gérer les cours</a>
        <a class="btn btn-outline-primary" href="index.php?page=admin-students">Consulter les étudiants</a>
        <a class="btn btn-outline-primary" href="index.php?page=admin-registrations">Toutes les inscriptions</a>
    </div>
</section>
