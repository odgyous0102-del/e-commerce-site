<?php
// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
?>
<!-- Sidebar -->
<div class="col-lg-2 sidebar">
    <div class="sidebar-header">
        <div class="user-avatar">
            <i class="fas fa-user-shield"></i>
        </div>
        <h5 class="mb-1"><?= htmlspecialchars(($_SESSION['user_prenom'] ?? '') . ' ' . ($_SESSION['user_nom'] ?? 'Administrateur')) ?></h5>
        <small class="text-light">Administrateur</small>
    </div>
    
    <nav class="nav flex-column pt-3">
        <a class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'index.php' ? 'active' : '' ?>" href="index.php">
            <i class="fas fa-tachometer-alt"></i> Tableau de Bord
        </a>
        <a class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'ajouter_article.php' ? 'active' : '' ?>" href="ajouter_article.php">
            <i class="fas fa-plus-circle"></i> Ajouter Article
        </a>
        <a class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'liste_articles.php' ? 'active' : '' ?>" href="liste_articles.php">
            <i class="fas fa-boxes"></i> Gestion Articles
        </a>
        <a class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'gestion_categories.php' ? 'active' : '' ?>" href="gestion_categories.php">
            <i class="fas fa-tags"></i> Catégories
        </a>
        <a class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'gestion_utilisateurs.php' ? 'active' : '' ?>" href="gestion_utilisateurs.php">
            <i class="fas fa-users-cog"></i> Utilisateurs
        </a>
        <a class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'parametres.php' ? 'active' : '' ?>" href="parametres.php">
            <i class="fas fa-cogs"></i> Paramètres
        </a>
        <div class="mt-4 pt-3 border-top">
            <a class="nav-link text-warning" href="../index.php" target="_blank">
                <i class="fas fa-external-link-alt"></i> Voir le Site
            </a>
            <a class="nav-link text-danger" href="logout.php">
                <i class="fas fa-sign-out-alt"></i> Déconnexion
            </a>
        </div>
    </nav>
</div>