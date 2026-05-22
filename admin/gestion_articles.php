<?php
// Démarrer la session au tout début
session_start();

// Vérification de l'authentification et des droits admin
/*if (!isset($_SESSION['utilisateur']) || $_SESSION['utilisateur']['role'] !== 'admin') {
    header('Location: ../connexion.php');
    exit();
}*/

// Connexion à la base de données
require_once '../config/database.php';

// Variables pour le formulaire
$action = $_POST['action'] ?? $_GET['action'] ?? 'liste';
$article_id = $_POST['article_id'] ?? $_GET['id'] ?? null;
$message = '';
$message_type = '';

// Traitement des actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $resultat = [];
    
    switch ($action) {
        case 'ajouter':
            $resultat = ajouterArticle($pdo, $_POST);
            break;
            
        case 'modifier':
            $resultat = modifierArticle($pdo, $article_id, $_POST);
            break;
            
        case 'supprimer':
            $resultat = supprimerArticle($pdo, $article_id);
            break;
            
        case 'changer_statut':
            $resultat = changerStatutArticle($pdo, $article_id);
            break;
    }
    
    $message_type = $resultat['success'] ? 'success' : 'danger';
    $message = $resultat['message'];
    
    // Redirection après POST pour éviter la resoumission
    header("Location: gestion_articles.php?message=" . urlencode($message) . "&type=" . $message_type);
    exit();
}

// Récupérer le message de redirection si existant
if (isset($_GET['message'])) {
    $message = urldecode($_GET['message']);
    $message_type = $_GET['type'] ?? 'info';
}

// Fonctions de gestion des articles
function ajouterArticle($pdo, $data) {
    try {
        // Validation des données
        if (empty($data['titre']) || empty($data['reference']) || empty($data['prix'])) {
            return ['success' => false, 'message' => 'Tous les champs obligatoires doivent être remplis'];
        }
        
        // Vérifier si la référence existe déjà
        $stmt = $pdo->prepare("SELECT id FROM article WHERE reference = ?");
        $stmt->execute([$data['reference']]);
        if ($stmt->fetch()) {
            return ['success' => false, 'message' => 'Cette référence existe déjà'];
        }
        
        // Préparation de la requête d'insertion
        $sql = "INSERT INTO article (reference, titre, description, prix, quantite_stock, categorie_id, marque, image_principale, est_actif, est_en_promotion, prix_promotion) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $data['reference'],
            $data['titre'],
            $data['description'] ?? '',
            $data['prix'],
            $data['quantite_stock'] ?? 0,
            $data['categorie_id'] ?? null,
            $data['marque'] ?? '',
            $data['image_principale'] ?? '',
            isset($data['est_actif']) ? 1 : 0,
            isset($data['est_en_promotion']) ? 1 : 0,
            $data['prix_promotion'] ?? null
        ]);
        
        return ['success' => true, 'message' => 'Article ajouté avec succès', 'id' => $pdo->lastInsertId()];
        
    } catch (PDOException $e) {
        error_log("Erreur ajout article: " . $e->getMessage());
        return ['success' => false, 'message' => 'Erreur lors de l\'ajout de l\'article'];
    }
}

function modifierArticle($pdo, $id, $data) {
    try {
        if (empty($id)) {
            return ['success' => false, 'message' => 'ID article manquant'];
        }
        
        // Vérifier si la référence existe déjà pour un autre article
        $stmt = $pdo->prepare("SELECT id FROM article WHERE reference = ? AND id != ?");
        $stmt->execute([$data['reference'], $id]);
        if ($stmt->fetch()) {
            return ['success' => false, 'message' => 'Cette référence est déjà utilisée par un autre article'];
        }
        
        // Préparation de la requête de mise à jour
        $sql = "UPDATE article SET 
                reference = ?, titre = ?, description = ?, prix = ?, quantite_stock = ?, 
                categorie_id = ?, marque = ?, image_principale = ?, est_actif = ?, 
                est_en_promotion = ?, prix_promotion = ?, date_modification = NOW() 
                WHERE id = ?";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $data['reference'],
            $data['titre'],
            $data['description'] ?? '',
            $data['prix'],
            $data['quantite_stock'] ?? 0,
            $data['categorie_id'] ?? null,
            $data['marque'] ?? '',
            $data['image_principale'] ?? '',
            isset($data['est_actif']) ? 1 : 0,
            isset($data['est_en_promotion']) ? 1 : 0,
            $data['prix_promotion'] ?? null,
            $id
        ]);
        
        return ['success' => true, 'message' => 'Article modifié avec succès'];
        
    } catch (PDOException $e) {
        error_log("Erreur modification article: " . $e->getMessage());
        return ['success' => false, 'message' => 'Erreur lors de la modification de l\'article'];
    }
}

function supprimerArticle($pdo, $id) {
    try {
        if (empty($id)) {
            return ['success' => false, 'message' => 'ID article manquant'];
        }
        
        $stmt = $pdo->prepare("DELETE FROM article WHERE id = ?");
        $stmt->execute([$id]);
        
        if ($stmt->rowCount() > 0) {
            return ['success' => true, 'message' => 'Article supprimé avec succès'];
        } else {
            return ['success' => false, 'message' => 'Article non trouvé'];
        }
        
    } catch (PDOException $e) {
        error_log("Erreur suppression article: " . $e->getMessage());
        return ['success' => false, 'message' => 'Erreur lors de la suppression de l\'article'];
    }
}

function changerStatutArticle($pdo, $id) {
    try {
        if (empty($id)) {
            return ['success' => false, 'message' => 'ID article manquant'];
        }
        
        // Récupérer le statut actuel
        $stmt = $pdo->prepare("SELECT est_actif FROM article WHERE id = ?");
        $stmt->execute([$id]);
        $article = $stmt->fetch();
        
        if (!$article) {
            return ['success' => false, 'message' => 'Article non trouvé'];
        }
        
        // Inverser le statut
        $nouveau_statut = $article['est_actif'] ? 0 : 1;
        
        $stmt = $pdo->prepare("UPDATE article SET est_actif = ?, date_modification = NOW() WHERE id = ?");
        $stmt->execute([$nouveau_statut, $id]);
        
        $statut_text = $nouveau_statut ? 'activé' : 'désactivé';
        return ['success' => true, 'message' => "Article $statut_text avec succès"];
        
    } catch (PDOException $e) {
        error_log("Erreur changement statut article: " . $e->getMessage());
        return ['success' => false, 'message' => 'Erreur lors du changement de statut'];
    }
}

// Récupérer les catégories pour les formulaires
$categories = [];
try {
    $stmt = $pdo->prepare("SELECT id, nom FROM categorie_article WHERE est_actif = 1 ORDER BY nom");
    $stmt->execute();
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Erreur récupération catégories: " . $e->getMessage());
}

// Récupérer les articles pour l'affichage
$articles = [];
try {
    $recherche = $_GET['recherche'] ?? '';
    $where = '';
    $params = [];
    
    if ($recherche) {
        $where = "WHERE (a.titre LIKE ? OR a.reference LIKE ? OR a.marque LIKE ?)";
        $param_recherche = "%$recherche%";
        $params = [$param_recherche, $param_recherche, $param_recherche];
    }
    
    $stmt = $pdo->prepare("
        SELECT a.*, c.nom as categorie_nom 
        FROM article a 
        LEFT JOIN categorie_article c ON a.categorie_id = c.id 
        $where 
        ORDER BY a.date_creation DESC
    ");
    $stmt->execute($params);
    $articles = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Erreur récupération articles: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Articles - Administration</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root {
            --primary-color: #4361ee;
            --secondary-color: #3f37c9;
            --success-color: #4cc9f0;
            --warning-color: #f72585;
            --light-bg: #f8f9fa;
            --border-color: #dee2e6;
            --shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            --transition: all 0.3s ease;
        }
        
        body {
            background-color: #f5f7fb;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .navbar-brand {
            font-weight: 700;
            font-size: 1.5rem;
        }
        
        .nav-pills .nav-link {
            border-radius: 0.5rem;
            margin: 0.2rem;
            transition: var(--transition);
            font-weight: 500;
        }
        
        .nav-pills .nav-link.active {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            box-shadow: var(--shadow);
        }
        
        .nav-pills .nav-link:hover:not(.active) {
            background-color: rgba(67, 97, 238, 0.1);
            transform: translateY(-2px);
        }
        
        .card {
            border: none;
            border-radius: 1rem;
            box-shadow: var(--shadow);
            transition: var(--transition);
            margin-bottom: 1.5rem;
        }
        
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 15px rgba(0, 0, 0, 0.15);
        }
        
        .card-header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            border-radius: 1rem 1rem 0 0 !important;
            padding: 1.25rem;
            border: none;
        }
        
        .table-responsive {
            border-radius: 0 0 1rem 1rem;
            overflow: hidden;
        }
        
        .table {
            margin-bottom: 0;
        }
        
        .table thead th {
            background: linear-gradient(135deg, #2d3748, #4a5568);
            color: white;
            border: none;
            padding: 1rem;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.85rem;
            letter-spacing: 0.5px;
        }
        
        .table tbody tr {
            transition: var(--transition);
        }
        
        .table tbody tr:hover {
            background-color: rgba(67, 97, 238, 0.05);
            transform: scale(1.01);
        }
        
        .table tbody td {
            padding: 1rem;
            vertical-align: middle;
            border-color: var(--border-color);
        }
        
        .btn {
            border-radius: 0.5rem;
            font-weight: 600;
            transition: var(--transition);
            border: none;
            padding: 0.5rem 1rem;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            box-shadow: var(--shadow);
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(67, 97, 238, 0.3);
        }
        
        .btn-group-sm > .btn {
            padding: 0.4rem 0.8rem;
            border-radius: 0.375rem;
        }
        
        .badge {
            border-radius: 0.375rem;
            font-weight: 600;
            padding: 0.4rem 0.8rem;
            font-size: 0.75rem;
        }
        
        .alert {
            border: none;
            border-radius: 0.75rem;
            box-shadow: var(--shadow);
            border-left: 4px solid;
        }
        
        .alert-success {
            border-left-color: #28a745;
            background: linear-gradient(135deg, #d4edda, #c3e6cb);
        }
        
        .alert-danger {
            border-left-color: #dc3545;
            background: linear-gradient(135deg, #f8d7da, #f5c6cb);
        }
        
        .modal-content {
            border: none;
            border-radius: 1rem;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }
        
        .modal-header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            border-radius: 1rem 1rem 0 0;
            border: none;
        }
        
        .btn-close {
            filter: invert(1);
        }
        
        .form-control, .form-select {
            border-radius: 0.5rem;
            border: 2px solid #e2e8f0;
            padding: 0.75rem;
            transition: var(--transition);
        }
        
        .form-control:focus, .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(67, 97, 238, 0.25);
        }
        
        .table img {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 0.5rem;
            box-shadow: var(--shadow);
        }
        
        .image-placeholder {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, #e2e8f0, #cbd5e0);
            border-radius: 0.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #718096;
        }
        
        .search-box {
            background: linear-gradient(135deg, #ffffff, #f8fafc);
            border-radius: 1rem;
            padding: 1.5rem;
        }
        
        .stats-card {
            text-align: center;
            padding: 1.5rem;
        }
        
        .stats-number {
            font-size: 2.5rem;
            font-weight: 700;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            -webkit-background-clip : text;
            -webkit-text-fill-color: transparent;
        }
        
        .action-buttons .btn {
            margin: 0 2px;
            border-radius: 0.375rem;
        }
        
        .price-promo {
            color: #dc3545;
            font-weight: 700;
        }
        
        .price-original {
            text-decoration: line-through;
            color: #6c757d;
            font-size: 0.9em;
        }
        
        .empty-state {
            padding: 3rem 1rem;
            text-align: center;
            color: #6c757d;
        }
        
        .empty-state i {
            font-size: 4rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }
        
        @media (max-width: 768px) {
            .table-responsive {
                font-size: 0.875rem;
            }
            
            .btn-group-sm > .btn {
                padding: 0.3rem 0.6rem;
            }
            
            .card-body {
                padding: 1rem;
            }
        }
        
        .gradient-bg {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        
        .text-gradient {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark gradient-bg">
        <div class="container-fluid">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-cog me-2"></i>AdminStore
            </a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="../index.php">
                    <i class="fas fa-store me-1"></i>Site public
                </a>
                <a class="nav-link" href="deconnexion.php">
                    <i class="fas fa-sign-out-alt me-1"></i>Déconnexion
                </a>
            </div>
        </div>
    </nav>

    <!-- Menu admin -->
    <div class="bg-white border-bottom">
        <div class="container-fluid">
            <nav class="nav nav-pills py-2">
                <a class="nav-link" href="index.php">
                    <i class="fas fa-tachometer-alt me-1"></i>Dashboard
                </a>
                <a class="nav-link active" href="gestion_articles.php">
                    <i class="fas fa-boxes me-1"></i>Articles
                </a>
                <a class="nav-link" href="gestion_categories.php">
                    <i class="fas fa-tags me-1"></i>Catégories
                </a>
                <a class="nav-link" href="gestion_utilisateurs.php">
                    <i class="fas fa-users me-1"></i>Utilisateurs
                </a>
            </nav>
        </div>
    </div>

    <div class="container-fluid py-4">
        <div class="row">
            <div class="col-12">
                <!-- En-tête avec statistiques -->
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h1 class="text-gradient"><i class="fas fa-boxes me-2"></i>Gestion des Articles</h1>
                        <p class="text-muted">Gérez votre catalogue de produits</p>
                    </div>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="modalArticle">
                    <a href="ajouter_article.php"></a>
                        <i class="fas fa-plus me-1"></i>Nouvel Article
                    </button>
                </div>

                <!-- Messages -->
                <?php if ($message): ?>
                    <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show">
                        <div class="d-flex align-items-center">
                            <?php if ($message_type === 'success'): ?>
                                <i class="fas fa-check-circle me-3 fa-2x"></i>
                            <?php else: ?>
                                <i class="fas fa-exclamation-triangle me-3 fa-2x"></i>
                            <?php endif; ?>
                            <div>
                                <h5 class="alert-heading mb-1"><?php echo $message_type === 'success' ? 'Succès !' : 'Attention !'; ?></h5>
                                <?php echo htmlspecialchars($message); ?>
                            </div>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- Barre de recherche -->
                <div class="card search-box">
                    <div class="card-body p-0">
                        <form method="GET" class="row g-3 align-items-center">
                            <div class="col-md-8">
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-end-0">
                                        <i class="fas fa-search text-muted"></i>
                                    </span>
                                    <input type="text" name="recherche" class="form-control border-start-0" 
                                           placeholder="Rechercher par référence, titre ou marque..." 
                                           value="<?php echo htmlspecialchars($_GET['recherche'] ?? ''); ?>">
                                </div>
                            </div>
                            <div class="col-md-2">
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="fas fa-search me-1"></i>Rechercher
                                </button>
                            </div>
                            <div class="col-md-2">
                                <a href="gestion_articles.php" class="btn btn-outline-secondary w-100">
                                    <i class="fas fa-rotate-left me-1"></i>Réinitialiser
                                </a>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Tableau des articles -->
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0 text-white">
                            <i class="fas fa-list me-2"></i>Liste des Articles 
                            <span class="badge bg-light text-dark ms-2"><?php echo count($articles); ?></span>
                        </h5>
                        <div class="text-white">
                            <small>Dernière mise à jour: <?php echo date('d/m/Y H:i'); ?></small>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-dark">
                                    <tr>
                                        <th width="80">ID</th>
                                        <th width="120">Référence</th>
                                        <th>Article</th>
                                        <th width="120">Prix</th>
                                        <th width="100">Stock</th>
                                        <th width="120">Catégorie</th>
                                        <th width="120">Marque</th>
                                        <th width="100">Statut</th>
                                        <th width="80">Promo</th>
                                        <th width="100">Date</th>
                                        <th width="150" class="text-center">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($articles)): ?>
                                        <tr>
                                            <td colspan="11" class="empty-state">
                                                <i class="fas fa-inbox"></i>
                                                <h5 class="mt-3">Aucun article trouvé</h5>
                                                <p class="text-muted">
                                                    <?php if ($recherche): ?>
                                                        Aucun résultat pour "<?php echo htmlspecialchars($recherche); ?>"
                                                    <?php else: ?>
                                                        Commencez par ajouter votre premier article
                                                    <?php endif; ?>
                                                </p>
                                                <?php if ($recherche): ?>
                                                    <a href="gestion_articles.php" class="btn btn-primary mt-2">
                                                        <i class="fas fa-list me-1"></i>Voir tous les articles
                                                    </a>
                                                <?php else: ?>
                                                    <button class="btn btn-primary mt-2" data-bs-toggle="modal" data-bs-target="#modalArticle">
                                                        <i class="fas fa-plus me-1"></i>Ajouter un article
                                                    </button>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($articles as $article): ?>
                                            <tr>
                                                <td class="fw-bold text-muted">#<?php echo $article['id']; ?></td>
                                                <td>
                                                    <span class="badge bg-light text-dark"><?php echo htmlspecialchars($article['reference']); ?></span>
                                                </td>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <?php if ($article['image_principale']): ?>
                                                            <img src="<?php echo htmlspecialchars($article['image_principale']); ?>" 
                                                                 alt="<?php echo htmlspecialchars($article['titre']); ?>"
                                                                 class="rounded me-3">
                                                        <?php else: ?>
                                                            <div class="image-placeholder me-3">
                                                                <i class="fas fa-image"></i>
                                                            </div>
                                                        <?php endif; ?>
                                                        <div>
                                                            <div class="fw-bold text-dark"><?php echo htmlspecialchars($article['titre']); ?></div>
                                                            <small class="text-muted">
                                                                <?php 
                                                                $description = htmlspecialchars($article['description']);
                                                                echo strlen($description) > 60 ? substr($description, 0, 60) . '...' : $description; 
                                                                ?>
                                                            </small>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <?php if ($article['est_en_promotion'] && $article['prix_promotion']): ?>
                                                        <span class="price-promo"><?php echo number_format($article['prix_promotion'], 2, ',', ' '); ?> €</span>
                                                        <br>
                                                        <small class="price-original"><?php echo number_format($article['prix'], 2, ',', ' '); ?> €</small>
                                                    <?php else: ?>
                                                        <span class="fw-bold text-dark"><?php echo number_format($article['prix'], 2, ',', ' '); ?> €</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <span class="badge bg-<?php echo $article['quantite_stock'] > 10 ? 'success' : ($article['quantite_stock'] > 0 ? 'warning' : 'danger'); ?>">
                                                        <i class="fas fa-<?php echo $article['quantite_stock'] > 10 ? 'check' : ($article['quantite_stock'] > 0 ? 'exclamation' : 'times'); ?> me-1"></i>
                                                        <?php echo $article['quantite_stock']; ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="badge bg-info"><?php echo htmlspecialchars($article['categorie_nom'] ?? 'Non catégorisé'); ?></span>
                                                </td>
                                                <td>
                                                    <small class="text-muted"><?php echo htmlspecialchars($article['marque'] ?? '-'); ?></small>
                                                </td>
                                                <td>
                                                    <span class="badge bg-<?php echo $article['est_actif'] ? 'success' : 'secondary'; ?>">
                                                        <i class="fas fa-<?php echo $article['est_actif'] ? 'play' : 'pause'; ?> me-1"></i>
                                                        <?php echo $article['est_actif'] ? 'Actif' : 'Inactif'; ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <?php if ($article['est_en_promotion']): ?>
                                                        <span class="badge bg-danger"><i class="fas fa-tag me-1"></i>PROMO</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-secondary">-</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <small class="text-muted"><?php echo date('d/m/Y', strtotime($article['date_creation'])); ?></small>
                                                </td>
                                                <td class="action-buttons">
                                                    <div class="btn-group btn-group-sm w-100">
                                                        <button class="btn btn-outline-primary" 
                                                                onclick="chargerArticle(<?php echo $article['id']; ?>)" 
                                                                data-bs-toggle="modal" 
                                                                data-bs-target="#modalArticle"
                                                                title="Modifier">
                                                            <i class="fas fa-edit"></i>
                                                        </button>
                                                        <form method="POST" class="d-inline">
                                                            <input type="hidden" name="article_id" value="<?php echo $article['id']; ?>">
                                                            <input type="hidden" name="action" value="changer_statut">
                                                            <button type="submit" class="btn btn-<?php echo $article['est_actif'] ? 'warning' : 'success'; ?>" 
                                                                    title="<?php echo $article['est_actif'] ? 'Désactiver' : 'Activer'; ?>">
                                                                <i class="fas fa-<?php echo $article['est_actif'] ? 'pause' : 'play'; ?>"></i>
                                                            </button>
                                                        </form>
                                                        <button class="btn btn-outline-danger" 
                                                                onclick="confirmerSuppression(<?php echo $article['id']; ?>, '<?php echo htmlspecialchars($article['titre']); ?>')"
                                                                title="Supprimer">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal pour ajouter/modifier un article -->
    <div class="modal fade" id="modalArticle" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="POST" id="formArticle">
                    <div class="modal-header">
                        <h5 class="modal-title" id="modalTitle">Nouvel Article</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="action" id="formAction" value="ajouter">
                        <input type="hidden" name="article_id" id="articleId">
                        
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Référence *</label>
                                <input type="text" name="reference" class="form-control" required maxlength="50" placeholder="REF-001">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Titre *</label>
                                <input type="text" name="titre" class="form-control" required maxlength="255" placeholder="Nom de l'article">
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-bold">Description</label>
                                <textarea name="description" class="form-control" rows="3" maxlength="1000" placeholder="Description détaillée de l'article..."></textarea>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Prix (€) *</label>
                                <input type="number" name="prix" class="form-control" step="0.01" min="0" required placeholder="0.00">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Quantité en stock</label>
                                <input type="number" name="quantite_stock" class="form-control" min="0" value="0">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Catégorie</label>
                                <select name="categorie_id" class="form-select">
                                    <option value="">Sélectionner une catégorie</option>
                                    <?php foreach ($categories as $categorie): ?>
                                        <option value="<?php echo $categorie['id']; ?>"><?php echo htmlspecialchars($categorie['nom']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Marque</label>
                                <input type="text" name="marque" class="form-control" maxlength="100" placeholder="Marque du produit">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Image principale (URL)</label>
                                <input type="url" name="image_principale" class="form-control" placeholder="https://example.com/image.jpg">
                            </div>
                            <div class="col-md-6">
                                <div class="form-check form-switch">
                                    <input type="checkbox" name="est_actif" class="form-check-input" id="estActif" checked>
                                    <label class="form-check-label fw-bold" for="estActif">Article actif</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-check form-switch">
                                    <input type="checkbox" name="est_en_promotion" class="form-check-input" id="estPromotion">
                                    <label class="form-check-label fw-bold" for="estPromotion">En promotion</label>
                                </div>
                            </div>
                            <div class="col-md-6" id="prixPromotionContainer" style="display: none;">
                                <label class="form-label fw-bold">Prix promotionnel (€)</label>
                                <input type="number" name="prix_promotion" class="form-control" step="0.01" min="0" placeholder="0.00">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i>Enregistrer
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal de confirmation de suppression -->
    <div class="modal fade" id="modalConfirmation" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title text-danger"><i class="fas fa-exclamation-triangle me-2"></i>Confirmation</h5>
                    <button type="button" class="btn-close" data-bs