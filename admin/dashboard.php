<?php
session_start();
require_once 'config.php';

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Vérifier si c'est un admin
if ($_SESSION['user_role'] !== 'admin') {
    die("Accès réservé aux administrateurs");
}

// Récupérer les statistiques pour le tableau de bord
try {
    // Statistiques principales
    $stats = [
        'articles_total' => $pdo->query("SELECT COUNT(*) FROM article")->fetchColumn(),
        'articles_actifs' => $pdo->query("SELECT COUNT(*) FROM article WHERE est_actif = 1")->fetchColumn(),
        'articles_promo' => $pdo->query("SELECT COUNT(*) FROM article WHERE est_en_promotion = 1")->fetchColumn(),
        'articles_stock_faible' => $pdo->query("SELECT COUNT(*) FROM article WHERE quantite_stock < 5")->fetchColumn(),
        'categories_total' => $pdo->query("SELECT COUNT(*) FROM categorie_article")->fetchColumn(),
        'utilisateurs_total' => $pdo->query("SELECT COUNT(*) FROM utilisateurs")->fetchColumn(),
        'utilisateurs_admins' => $pdo->query("SELECT COUNT(*) FROM utilisateurs WHERE role = 'admin'")->fetchColumn(),
    ];

    // Articles récents
    $articles_recents = $pdo->query("
        SELECT a.*, c.nom as categorie_nom 
        FROM article a 
        LEFT JOIN categorie_article c ON a.categorie_id = c.id 
        ORDER BY a.date_creation DESC 
        LIMIT 5
    ")->fetchAll(PDO::FETCH_ASSOC);

    // Catégories avec nombre d'articles
    $categories_stats = $pdo->query("
        SELECT c.nom, COUNT(a.id) as nb_articles 
        FROM categorie_article c 
        LEFT JOIN article a ON c.id = a.categorie_id 
        GROUP BY c.id, c.nom 
        ORDER BY nb_articles DESC 
        LIMIT 6
    ")->fetchAll(PDO::FETCH_ASSOC);

    // Derniers utilisateurs inscrits
    $utilisateurs_recents = $pdo->query("
        SELECT nom, prenom, email, date_inscription, role 
        FROM utilisateurs 
        ORDER BY date_inscription DESC 
        LIMIT 5
    ")->fetchAll(PDO::FETCH_ASSOC);

} catch(PDOException $e) {
    $error = "Erreur lors du chargement des données: " . $e->getMessage();
}

// Gestion des actions rapides
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        try {
            switch ($_POST['action']) {
                case 'toggle_article_status':
                    $article_id = $_POST['article_id'];
                    $stmt = $pdo->prepare("UPDATE article SET est_actif = NOT est_actif WHERE id = ?");
                    $stmt->execute([$article_id]);
                    $success = "Statut de l'article modifié avec succès";
                    break;
                
                case 'delete_article':
                    $article_id = $_POST['article_id'];
                    $stmt = $pdo->prepare("DELETE FROM article WHERE id = ?");
                    $stmt->execute([$article_id]);
                    $success = "Article supprimé avec succès";
                    break;
                
                case 'add_stock':
                    $article_id = $_POST['article_id'];
                    $quantite = $_POST['quantite'];
                    $stmt = $pdo->prepare("UPDATE article SET quantite_stock = quantite_stock + ? WHERE id = ?");
                    $stmt->execute([$quantite, $article_id]);
                    $success = "Stock mis à jour avec succès";
                    break;
            }
            
            // Recharger la page pour voir les modifications
            header("Location: index.php");
            exit();
            
        } catch(PDOException $e) {
            $error = "Erreur lors de l'action: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tableau de Bord Admin - E-commerce</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #2c3e50;
            --secondary: #34495e;
            --success: #27ae60;
            --warning: #f39c12;
            --danger: #e74c3c;
            --info: #3498db;
        }
        
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .admin-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            margin: 20px;
            overflow: hidden;
        }
        
        .sidebar {
            background: var(--primary);
            color: white;
            min-height: 100vh;
            padding: 0;
        }
        
        .sidebar-header {
            background: var(--secondary);
            padding: 30px 20px;
            text-align: center;
        }
        
        .user-avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: var(--info);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px;
            font-size: 2rem;
        }
        
        .sidebar .nav-link {
            color: #ecf0f1;
            padding: 15px 25px;
            margin: 5px 15px;
            border-radius: 10px;
            transition: all 0.3s;
            font-weight: 500;
        }
        
        .sidebar .nav-link:hover, .sidebar .nav-link.active {
            background: var(--info);
            color: white;
            transform: translateX(5px);
        }
        
        .sidebar .nav-link i {
            width: 25px;
            text-align: center;
            margin-right: 10px;
        }
        
        .main-content {
            padding: 30px;
            background: #f8f9fa;
        }
        
        .welcome-card {
            background: linear-gradient(135deg, var(--info), var(--primary));
            color: white;
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            transition: transform 0.3s;
            margin-bottom: 20px;
            border-left: 5px solid;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
        }
        
        .stat-card.primary { border-left-color: var(--info); }
        .stat-card.success { border-left-color: var(--success); }
        .stat-card.warning { border-left-color: var(--warning); }
        .stat-card.danger { border-left-color: var(--danger); }
        
        .stat-number {
            font-size: 2.5rem;
            font-weight: bold;
            margin-bottom: 10px;
        }
        
        .quick-actions .btn {
            margin: 5px;
            border-radius: 10px;
            padding: 15px 20px;
            font-weight: 500;
        }
        
        .recent-item {
            background: white;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 10px;
            border-left: 4px solid var(--info);
            transition: all 0.3s;
        }
        
        .recent-item:hover {
            transform: translateX(5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .chart-container {
            background: white;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .badge-custom {
            font-size: 0.8em;
            padding: 5px 10px;
            border-radius: 20px;
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <div class="row g-0">
            <!-- Sidebar -->
            <div class="col-lg-2 sidebar">
                <div class="sidebar-header">
                    <div class="user-avatar">
                        <i class="fas fa-user-shield"></i>
                    </div>
                    <h5 class="mb-1"><?= htmlspecialchars($_SESSION['user_prenom'] . ' ' . $_SESSION['user_nom']) ?></h5>
                    <small class="text-light">Administrateur</small>
                </div>
                
                <nav class="nav flex-column pt-3">
                    <a class="nav-link active" href="index.php">
                        <i class="fas fa-tachometer-alt"></i> Tableau de Bord
                    </a>
                    <a class="nav-link" href="ajouter_article.php">
                        <i class="fas fa-plus-circle"></i> Ajouter Article
                    </a>
                    <a class="nav-link" href="liste_articles.php">
                        <i class="fas fa-boxes"></i> Gestion Articles
                    </a>
                    <a class="nav-link" href="gestion_categories.php">
                        <i class="fas fa-tags"></i> Catégories
                    </a>
                    </a>
                    <a class="nav-link " href="gestion_commande.php">
                        <i class="fas fa-shopping-cart"></i> Commandes
                    </a>
                    <a class="nav-link" href="gestion_utilisateurs.php">
                        <i class="fas fa-users-cog"></i> Utilisateurs
                    </a>
                    <a class="nav-link" href="parametres.php">
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

            <!-- Main Content -->
            <div class="col-lg-10 main-content">
                <?php if (isset($error)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?= htmlspecialchars($error) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($success)): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?= htmlspecialchars($success) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- En-tête -->
                <div class="welcome-card">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <h1 class="display-6 fw-bold">Bonjour, <?= htmlspecialchars($_SESSION['user_prenom']) ?> !</h1>
                            <p class="mb-0">Bienvenue dans votre tableau de bord administrateur</p>
                        </div>
                        <div class="col-md-4 text-end">
                            <div class="bg-white rounded-pill px-4 py-2 d-inline-block">
                                <i class="fas fa-clock text-primary me-2"></i>
                                <span id="live-clock"><?= date('H:i:s') ?></span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Statistiques principales -->
                <div class="row">
                    <div class="col-xl-3 col-md-6">
                        <div class="card stat-card primary">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-box-open fa-3x text-info me-3"></i>
                                    <div>
                                        <div class="stat-number"><?= $stats['articles_total'] ?></div>
                                        <h6 class="text-muted">Articles Total</h6>
                                        <small class="text-success"><?= $stats['articles_actifs'] ?> actifs</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-xl-3 col-md-6">
                        <div class="card stat-card success">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-tags fa-3x text-success me-3"></i>
                                    <div>
                                        <div class="stat-number"><?= $stats['categories_total'] ?></div>
                                        <h6 class="text-muted">Catégories</h6>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-xl-3 col-md-6">
                        <div class="card stat-card warning">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-users fa-3x text-warning me-3"></i>
                                    <div>
                                        <div class="stat-number"><?= $stats['utilisateurs_total'] ?></div>
                                        <h6 class="text-muted">Utilisateurs</h6>
                                        <small class="text-info"><?= $stats['utilisateurs_admins'] ?> admins</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-xl-3 col-md-6">
                        <div class="card stat-card danger">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-exclamation-triangle fa-3x text-danger me-3"></i>
                                    <div>
                                        <div class="stat-number"><?= $stats['articles_stock_faible'] ?></div>
                                        <h6 class="text-muted">Stock Faible</h6>
                                        <small class="text-danger">Attention nécessaire</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Actions rapides -->
                <div class="row mt-4">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header bg-dark text-white">
                                <h5 class="mb-0"><i class="fas fa-bolt me-2"></i>Actions Rapides</h5>
                            </div>
                            <div class="card-body">
                                <div class="quick-actions text-center">
                                    <a href="ajouter_article.php" class="btn btn-primary">
                                        <i class="fas fa-plus me-2"></i>Nouvel Article
                                    </a>
                                    <a href="gestion_categories.php" class="btn btn-success">
                                        <i class="fas fa-tags me-2"></i>Gérer Catégories
                                    </a>
                                    <a href="gestion_utilisateurs.php" class="btn btn-warning">
                                        <i class="fas fa-users me-2"></i>Utilisateurs
                                    </a>
                                    <a href="liste_articles.php" class="btn btn-info">
                                        <i class="fas fa-list me-2"></i>Tous les Articles
                                    </a>
                                    <a href="ajouter_categorie.php" class="btn btn-secondary">
                                        <i class="fas fa-folder-plus me-2"></i>Nouvelle Catégorie
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row mt-4">
                    <!-- Articles récents -->
                    <div class="col-lg-6">
                        <div class="card">
                            <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                                <h5 class="mb-0"><i class="fas fa-clock me-2"></i>Articles Récents</h5>
                                <span class="badge bg-light text-dark"><?= count($articles_recents) ?></span>
                            </div>
                            <div class="card-body">
                                <?php if (!empty($articles_recents)): ?>
                                    <?php foreach ($articles_recents as $article): ?>
                                        <div class="recent-item">
                                            <div class="d-flex justify-content-between align-items-start">
                                                <div class="flex-grow-1">
                                                    <h6 class="fw-bold mb-1"><?= htmlspecialchars($article['titre']) ?></h6>
                                                    <small class="text-muted d-block">
                                                        Ref: <?= htmlspecialchars($article['reference']) ?> | 
                                                        Cat: <?= htmlspecialchars($article['categorie_nom'] ?? 'Non catégorisé') ?>
                                                    </small>
                                                    <div class="mt-2">
                                                        <span class="badge bg-<?= $article['est_actif'] ? 'success' : 'secondary' ?> badge-custom">
                                                            <?= $article['est_actif'] ? 'Actif' : 'Inactif' ?>
                                                        </span>
                                                        <?php if ($article['est_en_promotion']): ?>
                                                            <span class="badge bg-warning badge-custom">Promo</span>
                                                        <?php endif; ?>
                                                        <span class="badge bg-<?= $article['quantite_stock'] < 5 ? 'danger' : 'info' ?> badge-custom">
                                                            Stock: <?= $article['quantite_stock'] ?>
                                                        </span>
                                                    </div>
                                                </div>
                                                <div class="text-end ms-3">
                                                    <strong class="text-primary"><?= number_format($article['prix'], 2, ',', ' ') ?>€</strong>
                                                    <div class="btn-group btn-group-sm mt-2">
                                                        <form method="POST" class="d-inline">
                                                            <input type="hidden" name="article_id" value="<?= $article['id'] ?>">
                                                            <button type="submit" name="action" value="toggle_article_status" 
                                                                    class="btn btn-<?= $article['est_actif'] ? 'warning' : 'success' ?> btn-sm"
                                                                    title="<?= $article['est_actif'] ? 'Désactiver' : 'Activer' ?>">
                                                                <i class="fas fa-power-off"></i>
                                                            </button>
                                                        </form>
                                                        <a href="modifier_article.php?id=<?= $article['id'] ?>" class="btn btn-info btn-sm" title="Modifier">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <p class="text-muted text-center py-4">Aucun article pour le moment</p>
                                <?php endif; ?>
                                <div class="text-center mt-3">
                                    <a href="liste_articles.php" class="btn btn-outline-primary btn-sm">
                                        Voir tous les articles <i class="fas fa-arrow-right ms-1"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Catégories et Utilisateurs -->
                    <div class="col-lg-6">
                        <div class="row">
                            <!-- Catégories populaires -->
                            <div class="col-12 mb-4">
                                <div class="card">
                                    <div class="card-header bg-success text-white">
                                        <h5 class="mb-0"><i class="fas fa-chart-pie me-2"></i>Catégories Populaires</h5>
                                    </div>
                                    <div class="card-body">
                                        <?php if (!empty($categories_stats)): ?>
                                            <?php foreach ($categories_stats as $categorie): ?>
                                                <div class="d-flex justify-content-between align-items-center mb-2 p-2 border rounded">
                                                    <div>
                                                        <i class="fas fa-folder text-success me-2"></i>
                                                        <strong><?= htmlspecialchars($categorie['nom']) ?></strong>
                                                    </div>
                                                    <span class="badge bg-primary"><?= $categorie['nb_articles'] ?> articles</span>
                                                </div>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <p class="text-muted text-center">Aucune catégorie</p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>

                            <!-- Derniers utilisateurs -->
                            <div class="col-12">
                                <div class="card">
                                    <div class="card-header bg-warning text-dark">
                                        <h5 class="mb-0"><i class="fas fa-user-plus me-2"></i>Derniers Utilisateurs</h5>
                                    </div>
                                    <div class="card-body">
                                        <?php if (!empty($utilisateurs_recents)): ?>
                                            <?php foreach ($utilisateurs_recents as $user): ?>
                                                <div class="d-flex justify-content-between align-items-center mb-2 p-2 border rounded">
                                                    <div>
                                                        <i class="fas fa-user me-2 text-<?= $user['role'] === 'admin' ? 'danger' : 'info' ?>"></i>
                                                        <strong><?= htmlspecialchars($user['prenom'] . ' ' . $user['nom']) ?></strong>
                                                        <br>
                                                        <small class="text-muted"><?= htmlspecialchars($user['email']) ?></small>
                                                    </div>
                                                    <span class="badge bg-<?= $user['role'] === 'admin' ? 'danger' : 'secondary' ?>">
                                                        <?= $user['role'] === 'admin' ? 'Admin' : 'Client' ?>
                                                    </span>
                                                </div>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <p class="text-muted text-center">Aucun utilisateur</p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Section d'alertes importantes -->
                <div class="row mt-4">
                    <div class="col-12">
                        <div class="card border-warning">
                            <div class="card-header bg-warning text-dark">
                                <h5 class="mb-0"><i class="fas fa-bell me-2"></i>Alertes Importantes</h5>
                            </div>
                            <div class="card-body">
                                <?php if ($stats['articles_stock_faible'] > 0): ?>
                                    <div class="alert alert-danger d-flex align-items-center">
                                        <i class="fas fa-exclamation-triangle fa-2x me-3"></i>
                                        <div>
                                            <strong>Stock faible détecté !</strong><br>
                                            <?= $stats['articles_stock_faible'] ?> article(s) ont un stock inférieur à 5 unités.
                                            <a href="liste_articles.php?filter=stock_faible" class="alert-link">Vérifier maintenant</a>
                                        </div>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if ($stats['articles_promo'] > 0): ?>
                                    <div class="alert alert-info d-flex align-items-center">
                                        <i class="fas fa-percentage fa-2x me-3"></i>
                                        <div>
                                            <strong>Promotions en cours</strong><br>
                                            <?= $stats['articles_promo'] ?> article(s) sont actuellement en promotion.
                                            <a href="liste_articles.php?filter=promo" class="alert-link">Voir les promotions</a>
                                        </div>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="alert alert-success d-flex align-items-center">
                                    <i class="fas fa-chart-line fa-2x me-3"></i>
                                    <div>
                                        <strong>Statistiques du jour</strong><br>
                                        Votre boutique compte <?= $stats['articles_total'] ?> articles 
                                        répartis dans <?= $stats['categories_total'] ?> catégories.
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Horloge en temps réel
        function updateClock() {
            const now = new Date();
            const timeString = now.toLocaleTimeString('fr-FR');
            document.getElementById('live-clock').textContent = timeString;
        }
        setInterval(updateClock, 1000);
        updateClock();

        // Animation des cartes statistiques
        document.addEventListener('DOMContentLoaded', function() {
            const statCards = document.querySelectorAll('.stat-card');
            statCards.forEach((card, index) => {
                setTimeout(() => {
                    card.style.transform = 'scale(0.9)';
                    card.style.opacity = '0';
                    card.style.transition = 'all 0.5s ease';
                    
                    setTimeout(() => {
                        card.style.transform = 'scale(1)';
                        card.style.opacity = '1';
                    }, 50);
                }, index * 100);
            });
        });

        // Confirmation pour les actions critiques
        document.querySelectorAll('form[method="POST"]').forEach(form => {
            form.addEventListener('submit', function(e) {
                if (this.querySelector('button[value="delete_article"]')) {
                    if (!confirm('Êtes-vous sûr de vouloir supprimer cet article ?')) {
                        e.preventDefault();
                    }
                }
            });
        });
    </script>
</body>
</html>