<div class="page-heading mb-4">
    <div><span class="eyebrow">Suivi des demandes</span><h1 class="h2 fw-bold mt-2 mb-1"><?= $pendingOnly ? 'Inscriptions en attente' : 'Toutes les inscriptions' ?></h1><p class="text-secondary mb-0">Consultez les demandes et mettez à jour leur statut.</p></div>
    <?php if ($pendingOnly): ?><a class="btn btn-outline-primary" href="index.php?page=admin-registrations">Voir toutes les inscriptions</a><?php else: ?><a class="btn btn-outline-primary" href="index.php?page=admin-pending">Voir les demandes en attente</a><?php endif; ?>
</div>
<section class="content-card p-0 overflow-hidden">
    <?php if (!$registrations): ?>
        <div class="empty-state m-3"><h2 class="h5">Aucune inscription <?= $pendingOnly ? 'en attente' : '' ?></h2><p class="mb-0 text-secondary">Les nouvelles demandes apparaîtront ici.</p></div>
    <?php else: ?>
        <div class="table-responsive"><table class="table align-middle mb-0">
            <thead><tr><th>Étudiant</th><th>Cours</th><th>Date</th><th>Statut</th><th>Paiement</th><th>Actions</th></tr></thead>
            <tbody>
            <?php foreach ($registrations as $registration): ?>
                <tr>
                    <td><strong><?= e($registration['etudiant_nom']) ?></strong><div class="small text-secondary">CIN <?= e($registration['cin']) ?></div></td>
                    <td><strong><?= e($registration['langue']) ?></strong><div class="small text-secondary"><?= e($registration['niveau']) ?></div></td>
                    <td><?= e(date('d/m/Y', strtotime($registration['dateInscription']))) ?></td>
                    <td><?= statusBadge($registration['statut']) ?></td>
                    <td><?= statusBadge($registration['paiement']) ?></td>
                    <td>
                        <div class="d-flex flex-wrap gap-1">
                            <?php if ($registration['statut'] === 'en attente'): ?>
                                <form method="post" action="index.php">
                                    <?= csrfField() ?><input type="hidden" name="action" value="admin-registration-confirm"><input type="hidden" name="registration_id" value="<?= (int) $registration['idInscription'] ?>">
                                    <button class="btn btn-sm btn-success" type="submit">Confirmer</button>
                                </form>
                            <?php endif; ?>
                            <?php if ($registration['statut'] !== 'annulé'): ?>
                                <form method="post" action="index.php">
                                    <?= csrfField() ?><input type="hidden" name="action" value="admin-registration-cancel"><input type="hidden" name="registration_id" value="<?= (int) $registration['idInscription'] ?>">
                                    <button class="btn btn-sm btn-outline-secondary" type="submit">Annuler</button>
                                </form>
                            <?php endif; ?>
                            <?php if ($registration['paiement'] !== 'payé'): ?>
                                <form method="post" action="index.php">
                                    <?= csrfField() ?><input type="hidden" name="action" value="admin-registration-paid"><input type="hidden" name="registration_id" value="<?= (int) $registration['idInscription'] ?>">
                                    <button class="btn btn-sm btn-outline-primary" type="submit">Marquer payé</button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table></div>
    <?php endif; ?>
</section>
