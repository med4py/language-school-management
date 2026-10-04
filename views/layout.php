<?php
$flash = takeFlash();
$currentPage = queryString('page', 'home');
$isStudent = isset($_SESSION['student_id']);
$isAdmin = isset($_SESSION['admin_id']);
$isAuthenticated = $isStudent || $isAdmin;
$isAuthPage = str_starts_with($view, 'auth/');
$viewFile = __DIR__ . '/' . $view . '.php';
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Gérez facilement les cours et inscriptions de votre école de langues.">
    <meta name="theme-color" content="#0F2D5B">
    <title><?= e($title) ?> · EcoleLangue</title>
    <link rel="icon" type="image/svg+xml" href="assets/logo/favicon.svg">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
</head>
<body class="<?= $isAuthPage ? 'auth-document' : '' ?>">
<?php if (!$isAuthenticated && !$isAuthPage): ?>
    <nav class="navbar navbar-expand-lg navbar-dark public-navbar">
        <div class="container-fluid page-container">
            <a class="navbar-brand" href="index.php"><img src="assets/logo/logo-horizontal-white.svg" alt="EcoleLangue — Apprendre sans limites"></a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNavigation" aria-controls="mainNavigation" aria-expanded="false" aria-label="Ouvrir la navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="mainNavigation">
                <ul class="navbar-nav mx-auto align-items-lg-center gap-lg-3">
                    <li class="nav-item"><a class="nav-link <?= $currentPage === 'home' ? 'active' : '' ?>" href="index.php">Accueil</a></li>
                    <li class="nav-item"><a class="nav-link" href="index.php#cours">Nos cours</a></li>
                    <li class="nav-item"><a class="nav-link" href="index.php#apropos">À propos</a></li>
                    <li class="nav-item"><a class="nav-link" href="index.php#contact">Contact</a></li>
                </ul>
                <div class="navbar-actions d-flex flex-column flex-lg-row gap-2">
                    <a class="btn btn-login-student" href="index.php?page=student-login"><span aria-hidden="true">♙</span> Login Étudiant</a>
                    <a class="btn btn-login-admin" href="index.php?page=admin-login"><span aria-hidden="true">♙</span> Login Admin</a>
                </div>
            </div>
        </div>
    </nav>
<?php endif; ?>
<?php if ($isAuthenticated): ?>
    <header class="workspace-mobilebar d-lg-none">
        <button class="sidebar-toggle" type="button" data-bs-toggle="offcanvas" data-bs-target="#workspaceSidebar" aria-controls="workspaceSidebar" aria-label="Ouvrir le menu">☰</button>
        <a href="index.php?page=<?= $isAdmin ? 'admin-dashboard' : 'student-profile' ?>"><img src="assets/logo/logo-horizontal-white.svg" alt="EcoleLangue"></a>
        <span class="mobile-user"><?= e($isAdmin ? ($_SESSION['admin_name'] ?? 'Admin') : ($_SESSION['student_name'] ?? 'Étudiant')) ?></span>
    </header>
    <div class="workspace">
        <aside class="offcanvas-lg offcanvas-start workspace-sidebar" tabindex="-1" id="workspaceSidebar" aria-labelledby="sidebarBrand">
            <div class="offcanvas-header d-lg-none">
                <a id="sidebarBrand" href="index.php"><img src="assets/logo/logo-horizontal-white.svg" alt="EcoleLangue"></a>
                <button type="button" class="btn-close" data-bs-dismiss="offcanvas" data-bs-target="#workspaceSidebar" aria-label="Fermer"></button>
            </div>
            <div class="sidebar-brand d-none d-lg-block"><a href="index.php"><img src="assets/logo/logo-horizontal-white.svg" alt="EcoleLangue"></a></div>
            <div class="sidebar-user">
                <span class="sidebar-avatar"><?= e(strtoupper(substr($isAdmin ? ($_SESSION['admin_name'] ?? 'A') : ($_SESSION['student_name'] ?? 'E'), 0, 1))) ?></span>
                <span><strong><?= e($isAdmin ? ($_SESSION['admin_name'] ?? 'Admin') : ($_SESSION['student_name'] ?? 'Étudiant')) ?></strong><small><?= $isAdmin ? 'Administration' : 'Espace étudiant' ?></small></span>
            </div>
            <span class="sidebar-section-label">MENU PRINCIPAL</span>
            <nav class="sidebar-nav">
                <?php if ($isAdmin): ?>
                    <a class="sidebar-link <?= $currentPage === 'admin-dashboard' ? 'active' : '' ?>" href="index.php?page=admin-dashboard"><span class="sidebar-icon">⌂</span>Dashboard</a>
                    <a class="sidebar-link <?= $currentPage === 'admin-registrations' ? 'active' : '' ?>" href="index.php?page=admin-registrations"><span class="sidebar-icon">▤</span>Inscriptions</a>
                    <a class="sidebar-link <?= $currentPage === 'admin-pending' ? 'active' : '' ?>" href="index.php?page=admin-pending"><span class="sidebar-icon">◷</span>En attente</a>
                    <a class="sidebar-link <?= $currentPage === 'admin-courses' ? 'active' : '' ?>" href="index.php?page=admin-courses"><span class="sidebar-icon">▣</span>Cours</a>
                    <a class="sidebar-link <?= $currentPage === 'admin-students' ? 'active' : '' ?>" href="index.php?page=admin-students"><span class="sidebar-icon">♙</span>Étudiants</a>
                <?php else: ?>
                    <a class="sidebar-link <?= $currentPage === 'student-profile' ? 'active' : '' ?>" href="index.php?page=student-profile"><span class="sidebar-icon">♙</span>Profil</a>
                    <a class="sidebar-link <?= $currentPage === 'student-courses' ? 'active' : '' ?>" href="index.php?page=student-courses"><span class="sidebar-icon">▣</span>Inscription</a>
                    <a class="sidebar-link <?= $currentPage === 'student-registrations' ? 'active' : '' ?>" href="index.php?page=student-registrations"><span class="sidebar-icon">▤</span>Mes inscriptions</a>
                <?php endif; ?>
            </nav>
            <div class="sidebar-bottom">
                <a class="sidebar-link" href="index.php"><span class="sidebar-icon">⌂</span>Accueil du site</a>
                <form method="post" action="index.php">
                    <?= csrfField() ?><input type="hidden" name="action" value="logout">
                    <button class="sidebar-link sidebar-logout" type="submit"><span class="sidebar-icon">⇥</span>Déconnexion</button>
                </form>
            </div>
        </aside>
        <div class="workspace-content">
            <header class="workspace-topbar d-none d-lg-flex">
                <span><?= $isAdmin ? 'Espace administration' : 'Espace étudiant' ?></span>
                <span class="topbar-user"><?= e($isAdmin ? ($_SESSION['admin_name'] ?? 'Admin') : ($_SESSION['student_name'] ?? 'Étudiant')) ?></span>
            </header>
            <main class="workspace-main">
                <div class="workspace-container">
<?php elseif ($isAuthPage): ?>
    <main class="auth-main">
<?php else: ?>
    <main class="public-main">
        <div class="page-container">
<?php endif; ?>
        <?php if ($flash): ?>
            <div class="alert alert-<?= e($flash['type']) ?> alert-dismissible fade show" role="alert">
                <?= e($flash['message']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fermer"></button>
            </div>
        <?php endif; ?>
        <?php require $viewFile; ?>
</main>
<?php if ($isAuthenticated): ?>
                </div>
            </main>
        </div>
    </div>
<?php elseif (!$isAuthPage): ?>
    </div>
    <footer class="app-footer" id="contact">
        <div class="page-container d-flex flex-column flex-sm-row justify-content-between gap-2">
            <span>© <?= date('Y') ?> EcoleLangue · Apprendre sans limites</span>
            <span>Apprendre une langue, ouvrir le monde.</span>
        </div>
    </footer>
<?php endif; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="js/app.js"></script>
</body>
</html>
