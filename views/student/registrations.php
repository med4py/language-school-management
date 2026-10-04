<div class="page-heading mb-4">
    <div><span class="eyebrow">Suivi de vos demandes</span><h1 class="h2 fw-bold mt-2 mb-1">Mes inscriptions</h1><p class="text-secondary mb-0">Retrouvez l’état de vos demandes et paiements.</p></div>
    <a class="btn btn-primary" href="index.php?page=student-courses">Découvrir les cours</a>
</div>
<section class="content-card p-0 overflow-hidden">
    <?php if (!$registrations): ?>
        <div class="empty-state m-3"><h2 class="h5">Vous n’avez pas encore de demande</h2><p class="mb-3 text-secondary">Parcourez les cours et choisissez votre prochaine session.</p><a class="btn btn-primary" href="index.php?page=student-courses">Voir les cours</a></div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead><tr><th>Cours</th><th>Date de demande</th><th>Statut</th><th>Paiement</th><th class="text-end">Action</th></tr></thead>
                <tbody>
                <?php foreach ($registrations as $registration): ?>
                    <tr>
                        <td><strong><?= e($registration['langue']) ?></strong><div class="small text-secondary"><?= e($registration['niveau']) ?></div></td>
                        <td><?= e(date('d/m/Y', strtotime($registration['dateInscription']))) ?></td>
                        <td><?= statusBadge($registration['statut']) ?></td>
                        <td><?= statusBadge($registration['paiement']) ?></td>
                        <td class="text-end">
                            <?php if ($registration['statut'] === 'en attente'): ?>
                                <form method="post" action="index.php" class="d-inline">
                                    <?= csrfField() ?><input type="hidden" name="action" value="student-registration-delete"><input type="hidden" name="registration_id" value="<?= (int) $registration['idInscription'] ?>">
                                    <button class="btn btn-sm btn-outline-danger" type="submit">Supprimer</button>
                                </form>
                            <?php else: ?><span class="text-secondary">—</span><?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>
