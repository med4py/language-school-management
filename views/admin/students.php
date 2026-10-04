<div class="page-heading mb-4">
    <div><span class="eyebrow">Comptes de l’école</span><h1 class="h2 fw-bold mt-2 mb-1">Étudiants</h1><p class="text-secondary mb-0"><?= count($students) ?> compte(s) enregistré(s).</p></div>
</div>
<section class="content-card p-0 overflow-hidden">
    <div class="table-responsive"><table class="table align-middle mb-0">
        <thead><tr><th>Étudiant</th><th>CIN</th><th>E-mail</th><th>Téléphone</th><th>Login</th></tr></thead>
        <tbody>
        <?php foreach ($students as $student): ?>
            <tr>
                <td><strong><?= e($student['nom']) ?></strong></td>
                <td><?= e($student['cin']) ?></td>
                <td><a href="mailto:<?= e($student['email']) ?>"><?= e($student['email']) ?></a></td>
                <td><?= e($student['tel'] ?: '—') ?></td>
                <td><?= e($student['login']) ?></td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$students): ?><tr><td colspan="5" class="text-center text-secondary py-4">Aucun étudiant enregistré.</td></tr><?php endif; ?>
        </tbody>
    </table></div>
</section>
