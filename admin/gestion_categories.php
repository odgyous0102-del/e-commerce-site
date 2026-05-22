<?php
require_once 'config.php';
session_start();

// Vérifier si l'utilisateur est connecté et est admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

// Récupérer les paramètres de recherche et filtres
$search = $_GET['search'] ?? '';
$statut = $_GET['statut'] ?? '';
$page = $_GET['page'] ?? 1;
$limit = 20; // Nombre de catégories par page
$offset = ($page - 1) * $limit;

// Construire la requête avec les filtres
$whereConditions = [];
$params = [];

if (!empty($search)) {
    $whereConditions[] = "(c.nom LIKE ? OR c.description LIKE ?)";
    $searchTerm = "%$search%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

if ($statut === 'actif') {
    $whereConditions[] = "c.est_actif = 1";
} elseif ($statut === 'inactif') {
    $whereConditions[] = "c.est_actif = 0";
}

$whereClause = '';
if (!empty($whereConditions)) {
    $whereClause = "WHERE " . implode(" AND ", $whereConditions);
}

// Récupérer le nombre total de catégories pour la pagination
$countSql = "SELECT COUNT(*) as total FROM categorie_article c $whereClause";
$stmt = $pdo->prepare($countSql);
$stmt->execute($params);
$totalCategories = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
$totalPages = ceil($totalCategories / $limit);

// Récupérer les catégories avec le nombre d'articles
$sql = "SELECT c.*, 
               COUNT(a.id) as nombre_articles,
               p.nom as parent_nom
        FROM categorie_article c 
        LEFT JOIN article a ON c.id = a.categorie_id 
        LEFT JOIN categorie_article p ON c.parent_id = p.id 
        $whereClause 
        GROUP BY c.id 
        ORDER BY c.ordre_affichage ASC, c.nom ASC 
        LIMIT $limit OFFSET $offset";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Récupérer toutes les catégories pour le formulaire d'ajout/modification
$allCategories = [];
try {
    $stmt = $pdo->query("SELECT id, nom FROM categorie_article WHERE est_actif = 1 ORDER BY nom");
    $allCategories = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $error = "Erreur lors du chargement des catégories";
}

// Traitement des actions (ajout, modification, suppression, activation/désactivation)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $categorie_id = $_POST['categorie_id'] ?? '';
    
    try {
        switch ($action) {
            case 'add':
                $nom = trim($_POST['nom'] ?? '');
                $description = trim($_POST['description'] ?? '');
                $parent_id = $_POST['parent_id'] ?: null;
                $ordre_affichage = $_POST['ordre_affichage'] ?? 0;
                
                if (empty($nom)) {
                    $error = "Le nom de la catégorie est obligatoire";
                } else {
                    // Vérifier si la catégorie existe déjà
                    $stmt = $pdo->prepare("SELECT id FROM categorie_article WHERE nom = ?");
                    $stmt->execute([$nom]);
                    if ($stmt->fetch()) {
                        $error = "Cette catégorie existe déjà";
                    } else {
                        $sql = "INSERT INTO categorie_article (nom, description, parent_id, ordre_affichage) 
                                VALUES (?, ?, ?, ?)";
                        $stmt = $pdo->prepare($sql);
                        $stmt->execute([$nom, $description, $parent_id, $ordre_affichage]);
                        $success = "Catégorie ajoutée avec succès";
                    }
                }
                break;
                
            case 'edit':
                $nom = trim($_POST['nom'] ?? '');
                $description = trim($_POST['description'] ?? '');
                $parent_id = $_POST['parent_id'] ?: null;
                $ordre_affichage = $_POST['ordre_affichage'] ?? 0;
                
                if (empty($nom)) {
                    $error = "Le nom de la catégorie est obligatoire";
                } else {
                    // Vérifier si une autre catégorie a déjà ce nom
                    $stmt = $pdo->prepare("SELECT id FROM categorie_article WHERE nom = ? AND id != ?");
                    $stmt->execute([$nom, $categorie_id]);
                    if ($stmt->fetch()) {
                        $error = "Cette catégorie existe déjà";
                    } else {
                        $sql = "UPDATE categorie_article 
                                SET nom = ?, description = ?, parent_id = ?, ordre_affichage = ? 
                                WHERE id = ?";
                        $stmt = $pdo->prepare($sql);
                        $stmt->execute([$nom, $description, $parent_id, $ordre_affichage, $categorie_id]);
                        $success = "Catégorie modifiée avec succès";
                    }
                }
                break;
                
            case 'delete':
                // Vérifier si la catégorie contient des articles
                $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM article WHERE categorie_id = ?");
                $stmt->execute([$categorie_id]);
                $articleCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
                
                if ($articleCount > 0) {
                    $error = "Impossible de supprimer cette catégorie car elle contient des articles";
                } else {
                    $stmt = $pdo->prepare("DELETE FROM categorie_article WHERE id = ?");
                    $stmt->execute([$categorie_id]);
                    $success = "Catégorie supprimée avec succès";
                }
                break;
                
            case 'toggle_active':
                $stmt = $pdo->prepare("SELECT est_actif FROM categorie_article WHERE id = ?");
                $stmt->execute([$categorie_id]);
                $currentStatus = $stmt->fetch(PDO::FETCH_ASSOC)['est_actif'];
                
                $stmt = $pdo->prepare("UPDATE categorie_article SET est_actif = ? WHERE id = ?");
                $stmt->execute([$currentStatus ? 0 : 1, $categorie_id]);
                $success = "Statut de la catégorie modifié avec succès";
                break;
        }
        
        // Recharger la page pour voir les modifications
        header("Location: gestion_categories.php?" . $_SERVER['QUERY_STRING']);
        exit;
        
    } catch(PDOException $e) {
        $error = "Erreur lors de l'opération : " . $e->getMessage();
    }
}

// Récupérer les statistiques
$stats = [];
try {
    // Total catégories
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM categorie_article");
    $stats['total'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Catégories actives
    $stmt = $pdo->query("SELECT COUNT(*) as actives FROM categorie_article WHERE est_actif = 1");
    $stats['actives'] = $stmt->fetch(PDO::FETCH_ASSOC)['actives'];
    
    // Catégories avec articles
    $stmt = $pdo->query("SELECT COUNT(DISTINCT categorie_id) as avec_articles FROM article WHERE categorie_id IS NOT NULL");
    $stats['avec_articles'] = $stmt->fetch(PDO::FETCH_ASSOC)['avec_articles'];
    
    // Catégories sans parent
    $stmt = $pdo->query("SELECT COUNT(*) as racines FROM categorie_article WHERE parent_id IS NULL");
    $stats['racines'] = $stmt->fetch(PDO::FETCH_ASSOC)['racines'];
} catch(PDOException $e) {
    $error = "Erreur lors du chargement des statistiques";
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Catégories - E-commerce</title>
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
            padding: 25px;
            margin-bottom: 30px;
        }
        
        .form-card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            margin-bottom: 20px;
            overflow: hidden;
        }
        
        .form-card .card-header {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            padding: 20px 25px;
            border-bottom: none;
        }
        
        .form-card .card-body {
            padding: 30px;
        }
        
        .form-label {
            font-weight: 600;
            color: var(--primary);
            margin-bottom: 8px;
        }
        
        .form-control, .form-select {
            border: 2px solid #e9ecef;
            border-radius: 10px;
            padding: 12px 15px;
            transition: all 0.3s;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: var(--info);
            box-shadow: 0 0 0 0.2rem rgba(52, 152, 219, 0.25);
        }
        
        .btn-custom {
            border-radius: 10px;
            padding: 12px 30px;
            font-weight: 600;
            transition: all 0.3s;
            border: none;
        }
        
        .btn-primary-custom {
            background: linear-gradient(135deg, var(--info), var(--primary));
            color: white;
        }
        
        .btn-primary-custom:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(52, 152, 219, 0.4);
        }
        
        .btn-secondary-custom {
            background: linear-gradient(135deg, #95a5a6, #7f8c8d);
            color: white;
        }
        
        .stats-card {
            text-align: center;
            padding: 20px;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            transition: all 0.3s;
            border: none;
        }
        
        .stats-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.15);
        }
        
        .stats-number {
            font-size: 2.2rem;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .table-card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .table-card .card-header {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            padding: 20px 25px;
            border-bottom: none;
        }
        
        .table-responsive {
            border-radius: 0 0 15px 15px;
        }
        
        .table th {
            background-color: #f8f9fa;
            border-bottom: 2px solid #e9ecef;
            font-weight: 600;
            color: var(--primary);
            padding: 15px;
        }
        
        .table td {
            padding: 15px;
            vertical-align: middle;
        }
        
        .table-hover tbody tr:hover {
            background-color: rgba(52, 152, 219, 0.05);
        }
        
        .badge-success {
            background: linear-gradient(135deg, var(--success), #2ecc71);
        }
        
        .badge-danger {
            background: linear-gradient(135deg, var(--danger), #c0392b);
        }
        
        .badge-inactive {
            background: linear-gradient(135deg, #95a5a6, #7f8c8d);
            color: white;
        }
        
        .badge-info {
            background: linear-gradient(135deg, var(--info), #2980b9);
        }
        
        .table-actions {
            white-space: nowrap;
        }
        
        .table-actions .btn {
            margin-right: 5px;
            border-radius: 8px;
            padding: 8px 12px;
        }
        
        .pagination .page-link {
            border-radius: 8px;
            margin: 0 3px;
            border: none;
            color: var(--primary);
        }
        
        .pagination .page-item.active .page-link {
            background: linear-gradient(135deg, var(--info), var(--primary));
            border: none;
        }
        
        .feature-badge {
            background: linear-gradient(135deg, var(--success), #2ecc71);
            color: white;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 0.8em;
            margin-left: 10px;
        }
        
        .modal-content {
            border-radius: 15px;
            border: none;
            box-shadow: 0 20px 40px rgba(0,0,0,0.2);
        }
        
        .modal-header {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            border-radius: 15px 15px 0 0;
            border: none;
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
                    <h5 class="mb-1"><?= htmlspecialchars(($_SESSION['user_prenom'] ?? '') . ' ' . ($_SESSION['user_nom'] ?? 'Administrateur')) ?></h5>
                    <small class="text-light">Administrateur</small>
                </div>
                
                <nav class="nav flex-column pt-3">
                    <a class="nav-link" href="index.php">
                        <i class="fas fa-tachometer-alt"></i> Tableau de Bord
                    </a>
                    <a class="nav-link" href="ajouter_article.php">
                        <i class="fas fa-plus-circle"></i> Ajouter Article
                    </a>
                    <a class="nav-link" href="liste_articles.php">
                        <i class="fas fa-boxes"></i> Gestion Articles
                    </a>
                    <a class="nav-link active" href="gestion_categories.php">
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
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <?= htmlspecialchars($error) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($success)): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="fas fa-check-circle me-2"></i>
                        <?= htmlspecialchars($success) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- En-tête -->
                <div class="welcome-card">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <h1 class="display-6 fw-bold mb-2">Gestion des Catégories</h1>
                            <p class="mb-0">Organisez vos produits par catégories pour une meilleure navigation</p>
                        </div>
                        <div class="col-md-4 text-end">
                            <button type="button" class="btn btn-light btn-custom" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
                                <i class="fas fa-plus-circle me-2"></i>Nouvelle Catégorie
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Cartes de statistiques -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="stats-card bg-white">
                            <div class="stats-number text-primary"><?= $stats['total'] ?? 0 ?></div>
                            <div>Total Catégories</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stats-card bg-white">
                            <div class="stats-number text-success"><?= $stats['actives'] ?? 0 ?></div>
                            <div>Catégories Actives</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stats-card bg-white">
                            <div class="stats-number text-warning"><?= $stats['avec_articles'] ?? 0 ?></div>
                            <div>Avec Articles</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stats-card bg-white">
                            <div class="stats-number text-info"><?= $stats['racines'] ?? 0 ?></div>
                            <div>Catégories Racines</div>
                        </div>
                    </div>
                </div>

                <!-- Filtres et recherche -->
                <div class="form-card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-filter me-2"></i>Filtres et Recherche</h5>
                    </div>
                    <div class="card-body">
                        <form method="GET" class="row g-3">
                            <div class="col-md-6">
                                <input type="text" class="form-control" name="search" placeholder="Nom ou description..." value="<?= htmlspecialchars($search) ?>">
                            </div>
                            <div class="col-md-4">
                                <select class="form-select" name="statut">
                                    <option value="">Tous les statuts</option>
                                    <option value="actif" <?= $statut === 'actif' ? 'selected' : '' ?>>Actives</option>
                                    <option value="inactif" <?= $statut === 'inactif' ? 'selected' : '' ?>>Inactives</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <button type="submit" class="btn btn-primary-custom btn-custom w-100">
                                    <i class="fas fa-search me-2"></i>Filtrer
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Tableau des catégories -->
                <div class="table-card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="fas fa-list me-2"></i>Liste des Catégories (<?= $totalCategories ?>)</h5>
                        <small class="text-light">Page <?= $page ?> sur <?= $totalPages ?></small>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Nom</th>
                                        <th>Description</th>
                                        <th>Catégorie Parente</th>
                                        <th>Ordre</th>
                                        <th>Articles</th>
                                        <th>Statut</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($categories)): ?>
                                        <tr>
                                            <td colspan="8" class="text-center py-5">
                                                <i class="fas fa-tags fa-3x text-muted mb-3"></i>
                                                <h5 class="text-muted">Aucune catégorie trouvée</h5>
                                                <p class="text-muted">Essayez de modifier vos critères de recherche ou créez une nouvelle catégorie</p>
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($categories as $categorie): ?>
                                            <tr>
                                                <td><strong><?= $categorie['id'] ?></strong></td>
                                                <td>
                                                    <strong><?= htmlspecialchars($categorie['nom']) ?></strong>
                                                </td>
                                                <td>
                                                    <?= $categorie['description'] ? htmlspecialchars(substr($categorie['description'], 0, 100)) . (strlen($categorie['description']) > 100 ? '...' : '') : '<span class="text-muted">-</span>' ?>
                                                </td>
                                                <td>
                                                    <?= $categorie['parent_nom'] ? '<span class="badge bg-secondary">' . htmlspecialchars($categorie['parent_nom']) . '</span>' : '<span class="badge badge-info">Racine</span>' ?>
                                                </td>
                                                <td>
                                                    <span class="badge bg-light text-dark"><?= $categorie['ordre_affichage'] ?></span>
                                                </td>
                                                <td>
                                                    <span class="badge <?= $categorie['nombre_articles'] > 0 ? 'badge-success' : 'badge-secondary' ?>">
                                                        <?= $categorie['nombre_articles'] ?> article(s)
                                                    </span>
                                                </td>
                                                <td>
                                                    <?php if (!$categorie['est_actif']): ?>
                                                        <span class="badge badge-inactive">Inactive</span>
                                                    <?php else: ?>
                                                        <span class="badge badge-success">Active</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="table-actions">
                                                    <button type="button" class="btn btn-sm btn-outline-primary edit-category" 
                                                            data-bs-toggle="modal" data-bs-target="#editCategoryModal"
                                                            data-id="<?= $categorie['id'] ?>"
                                                            data-nom="<?= htmlspecialchars($categorie['nom']) ?>"
                                                            data-description="<?= htmlspecialchars($categorie['description']) ?>"
                                                            data-parent-id="<?= $categorie['parent_id'] ?>"
                                                            data-ordre="<?= $categorie['ordre_affichage'] ?>"
                                                            title="Modifier">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    
                                                    <form method="POST" class="d-inline" onsubmit="return confirm('Êtes-vous sûr de vouloir changer le statut de cette catégorie ?')">
                                                        <input type="hidden" name="categorie_id" value="<?= $categorie['id'] ?>">
                                                        <input type="hidden" name="action" value="toggle_active">
                                                        <button type="submit" class="btn btn-sm <?= $categorie['est_actif'] ? 'btn-outline-warning' : 'btn-outline-success' ?>" 
                                                                title="<?= $categorie['est_actif'] ? 'Désactiver' : 'Activer' ?>">
                                                            <i class="fas fa-power-off"></i>
                                                        </button>
                                                    </form>
                                                    
                                                    <form method="POST" class="d-inline" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer cette catégorie ? Cette action est irréversible.')">
                                                        <input type="hidden" name="categorie_id" value="<?= $categorie['id'] ?>">
                                                        <input type="hidden" name="action" value="delete">
                                                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Supprimer">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </form>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    
                    <!-- Pagination -->
                    <?php if ($totalPages > 1): ?>
                        <div class="card-footer">
                            <nav aria-label="Pagination">
                                <ul class="pagination justify-content-center mb-0">
                                    <?php if ($page > 1): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>">
                                                <i class="fas fa-chevron-left me-1"></i>Précédent
                                            </a>
                                        </li>
                                    <?php endif; ?>
                                    
                                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                        <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                                            <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>"><?= $i ?></a>
                                        </li>
                                    <?php endfor; ?>
                                    
                                    <?php if ($page < $totalPages): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>">
                                                Suivant<i class="fas fa-chevron-right ms-1"></i>
                                            </a>
                                        </li>
                                    <?php endif; ?>
                                </ul>
                            </nav>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Ajout Catégorie -->
    <div class="modal fade" id="addCategoryModal" tabindex="-1" aria-labelledby="addCategoryModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addCategoryModalLabel"><i class="fas fa-plus-circle me-2"></i>Nouvelle Catégorie</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add">
                        <div class="mb-3">
                            <label for="nom" class="form-label required">Nom de la catégorie</label>
                            <input type="text" class="form-control" id="nom" name="nom" required>
                        </div>
                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="parent_id" class="form-label">Catégorie parente</label>
                            <select class="form-select" id="parent_id" name="parent_id">
                                <option value="">Aucune (catégorie racine)</option>
                                <?php foreach ($allCategories as $cat): ?>
                                    <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['nom']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="ordre_affichage" class="form-label">Ordre d'affichage</label>
                            <input type="number" class="form-control" id="ordre_affichage" name="ordre_affichage" value="0" min="0">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary-custom btn-custom" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-primary-custom btn-custom">Créer la catégorie</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Modification Catégorie -->
    <div class="modal fade" id="editCategoryModal" tabindex="-1" aria-labelledby="editCategoryModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editCategoryModalLabel"><i class="fas fa-edit me-2"></i>Modifier la Catégorie</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="edit">
                        <input type="hidden" name="categorie_id" id="edit_categorie_id">
                        <div class="mb-3">
                            <label for="edit_nom" class="form-label required">Nom de la catégorie</label>
                            <input type="text" class="form-control" id="edit_nom" name="nom" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_description" class="form-label">Description</label>
                            <textarea class="form-control" id="edit_description" name="description" rows="3"></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="edit_parent_id" class="form-label">Catégorie parente</label>
                            <select class="form-select" id="edit_parent_id" name="parent_id">
                                <option value="">Aucune (catégorie racine)</option>
                                <?php foreach ($allCategories as $cat): ?>
                                    <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['nom']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="edit_ordre_affichage" class="form-label">Ordre d'affichage</label>
                            <input type="number" class="form-control" id="edit_ordre_affichage" name="ordre_affichage" value="0" min="0">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary-custom btn-custom" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-primary-custom btn-custom">Modifier la catégorie</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Animation au chargement de la page
        document.addEventListener('DOMContentLoaded', function() {
            // Animation des cartes de statistiques
            const statsCards = document.querySelectorAll('.stats-card');
            statsCards.forEach((card, index) => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(20px)';
                
                setTimeout(() => {
                    card.style.transition = 'all 0.5s ease';
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, index * 100);
            });
            
            // Animation des lignes du tableau
            const tableRows = document.querySelectorAll('tbody tr');
            tableRows.forEach((row, index) => {
                row.style.opacity = '0';
                row.style.transform = 'translateX(-20px)';
                
                setTimeout(() => {
                    row.style.transition = 'all 0.5s ease';
                    row.style.opacity = '1';
                    row.style.transform = 'translateX(0)';
                }, index * 50);
            });
            
            // Gestion des boutons d'édition
            const editButtons = document.querySelectorAll('.edit-category');
            editButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const id = this.getAttribute('data-id');
                    const nom = this.getAttribute('data-nom');
                    const description = this.getAttribute('data-description');
                    const parentId = this.getAttribute('data-parent-id');
                    const ordre = this.getAttribute('data-ordre');
                    
                    document.getElementById('edit_categorie_id').value = id;
                    document.getElementById('edit_nom').value = nom;
                    document.getElementById('edit_description').value = description;
                    document.getElementById('edit_parent_id').value = parentId;
                    document.getElementById('edit_ordre_affichage').value = ordre;
                });
            });
        });
    </script>
</body>
</html>