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
$limit = 20; // Nombre de commandes par page
$offset = ($page - 1) * $limit;

// Construire la requête avec les filtres
$whereConditions = [];
$params = [];

if (!empty($search)) {
    $whereConditions[] = "(c.reference_commande LIKE ? OR u.nom LIKE ? OR u.prenom LIKE ? OR u.email LIKE ?)";
    $searchTerm = "%$search%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

if (!empty($statut)) {
    $whereConditions[] = "sc.code = ?";
    $params[] = $statut;
}

$whereClause = '';
if (!empty($whereConditions)) {
    $whereClause = "WHERE " . implode(" AND ", $whereConditions);
}

// Récupérer le nombre total de commandes pour la pagination
$countSql = "SELECT COUNT(*) as total FROM commande c 
             LEFT JOIN utilisateurs u ON c.utilisateur_id = u.id 
             LEFT JOIN statut_commande sc ON c.statut_id = sc.id 
             $whereClause";
$stmt = $pdo->prepare($countSql);
$stmt->execute($params);
$totalCommandes = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
$totalPages = ceil($totalCommandes / $limit);

// Récupérer les commandes avec les informations utilisateur
$sql = "SELECT c.*, 
               u.nom as user_nom, 
               u.prenom as user_prenom,
               u.email as user_email,
               sc.code as statut_code,
               sc.nom as statut_nom,
               COUNT(lc.id) as nombre_articles,
               SUM(lc.quantite * lc.prix_unitaire) as total_commande_calcule
        FROM commande c 
        LEFT JOIN utilisateurs u ON c.utilisateur_id = u.id 
        LEFT JOIN statut_commande sc ON c.statut_id = sc.id 
        LEFT JOIN ligne_commande lc ON c.id = lc.commande_id 
        $whereClause 
        GROUP BY c.id 
        ORDER BY c.date_commande DESC 
        LIMIT $limit OFFSET $offset";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$commandes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Traitement des actions (modification statut, suppression)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $commande_id = $_POST['commande_id'] ?? '';
    
    try {
        switch ($action) {
            case 'update_status':
                $nouveau_statut_code = $_POST['statut'] ?? '';
                $statuts_valides = ['en_attente', 'confirmee', 'expediee', 'livree', 'annulee'];
                
                if (in_array($nouveau_statut_code, $statuts_valides)) {
                    // Récupérer l'ID du statut à partir du code
                    $stmt = $pdo->prepare("SELECT id FROM statut_commande WHERE code = ?");
                    $stmt->execute([$nouveau_statut_code]);
                    $statut_data = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($statut_data) {
                        $sql = "UPDATE commande SET statut_id = ?, date_modification = NOW() WHERE id = ?";
                        $stmt = $pdo->prepare($sql);
                        $stmt->execute([$statut_data['id'], $commande_id]);
                        $success = "Statut de la commande mis à jour avec succès";
                    } else {
                        $error = "Statut introuvable";
                    }
                } else {
                    $error = "Statut invalide";
                }
                break;
                
            case 'delete':
                // Vérifier si la commande peut être supprimée
                $stmt = $pdo->prepare("SELECT sc.code as statut_code 
                                      FROM commande c 
                                      JOIN statut_commande sc ON c.statut_id = sc.id 
                                      WHERE c.id = ?");
                $stmt->execute([$commande_id]);
                $currentStatus = $stmt->fetch(PDO::FETCH_ASSOC)['statut_code'];
                
                if (in_array($currentStatus, ['en_attente', 'annulee'])) {
                    // Supprimer d'abord les lignes de commande
                    $stmt = $pdo->prepare("DELETE FROM ligne_commande WHERE commande_id = ?");
                    $stmt->execute([$commande_id]);
                    
                    // Puis supprimer la commande
                    $stmt = $pdo->prepare("DELETE FROM commande WHERE id = ?");
                    $stmt->execute([$commande_id]);
                    $success = "Commande supprimée avec succès";
                } else {
                    $error = "Impossible de supprimer une commande confirmée, expédiée ou livrée";
                }
                break;
        }
        
        // Recharger la page pour voir les modifications
        header("Location: gestion_commande.php?" . $_SERVER['QUERY_STRING']);
        exit;
        
    } catch(PDOException $e) {
        $error = "Erreur lors de l'opération : " . $e->getMessage();
    }
}

// Récupérer les statistiques - CORRIGÉ
$stats = [];
try {
    // Total commandes
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM commande");
    $stats['total'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Commandes par statut - CORRIGÉ
    $statuts = ['en_attente', 'confirmee', 'expediee', 'livree', 'annulee'];
    foreach ($statuts as $stat) {
        $stmt = $pdo->prepare("SELECT COUNT(*) as count 
                              FROM commande c 
                              JOIN statut_commande sc ON c.statut_id = sc.id 
                              WHERE sc.code = ?");
        $stmt->execute([$stat]);
        $stats[$stat] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    }
    
    // Chiffre d'affaires du mois - CORRIGÉ
    $stmt = $pdo->query("SELECT SUM(c.total_commande) as ca_mois 
                         FROM commande c 
                         JOIN statut_commande sc ON c.statut_id = sc.id 
                         WHERE sc.code IN ('confirmee', 'expediee', 'livree') 
                         AND MONTH(c.date_commande) = MONTH(CURRENT_DATE()) 
                         AND YEAR(c.date_commande) = YEAR(CURRENT_DATE())");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $stats['ca_mois'] = $result['ca_mois'] ?? 0;
    
} catch(PDOException $e) {
    $error = "Erreur lors du chargement des statistiques: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Commandes - E-commerce</title>
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
        
        .badge-en_attente {
            background: linear-gradient(135deg, #f39c12, #e67e22);
            color: white;
        }
        
        .badge-confirmee {
            background: linear-gradient(135deg, #3498db, #2980b9);
            color: white;
        }
        
        .badge-expediee {
            background: linear-gradient(135deg, #9b59b6, #8e44ad);
            color: white;
        }
        
        .badge-livree {
            background: linear-gradient(135deg, #27ae60, #2ecc71);
            color: white;
        }
        
        .badge-annulee {
            background: linear-gradient(135deg, #e74c3c, #c0392b);
            color: white;
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
        
        .status-badge {
            font-size: 0.9em;
            padding: 6px 12px;
            border-radius: 20px;
        }
        
        .text-purple {
            color: #9b59b6 !important;
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
                    <a class="nav-link" href="gestion_categories.php">
                        <i class="fas fa-tags"></i> Catégories
                    </a>
                    <a class="nav-link active" href="gestion_commande.php">
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
                            <h1 class="display-6 fw-bold mb-2">Gestion des Commandes</h1>
                            <p class="mb-0">Suivez et gérez toutes les commandes de votre boutique en ligne</p>
                        </div>
                        <div class="col-md-4 text-end">
                            <span class="btn btn-light btn-custom">
                                <i class="fas fa-chart-line me-2"></i>CA: <?= number_format($stats['ca_mois'] ?? 0, 2, ',', ' ') ?> €
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Cartes de statistiques -->
                <div class="row mb-4">
                    <div class="col-md-2">
                        <div class="stats-card bg-white">
                            <div class="stats-number text-primary"><?= $stats['total'] ?? 0 ?></div>
                            <div>Total Commandes</div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="stats-card bg-white">
                            <div class="stats-number text-warning"><?= $stats['en_attente'] ?? 0 ?></div>
                            <div>En Attente</div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="stats-card bg-white">
                            <div class="stats-number text-info"><?= $stats['confirmee'] ?? 0 ?></div>
                            <div>Confirmées</div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="stats-card bg-white">
                            <div class="stats-number text-purple"><?= $stats['expediee'] ?? 0 ?></div>
                            <div>Expédiées</div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="stats-card bg-white">
                            <div class="stats-number text-success"><?= $stats['livree'] ?? 0 ?></div>
                            <div>Livrées</div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="stats-card bg-white">
                            <div class="stats-number text-danger"><?= $stats['annulee'] ?? 0 ?></div>
                            <div>Annulées</div>
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
                            <div class="col-md-5">
                                <input type="text" class="form-control" name="search" placeholder="Référence, nom client ou email..." value="<?= htmlspecialchars($search) ?>">
                            </div>
                            <div class="col-md-5">
                                <select class="form-select" name="statut">
                                    <option value="">Tous les statuts</option>
                                    <option value="en_attente" <?= $statut === 'en_attente' ? 'selected' : '' ?>>En attente</option>
                                    <option value="confirmee" <?= $statut === 'confirmee' ? 'selected' : '' ?>>Confirmée</option>
                                    <option value="expediee" <?= $statut === 'expediee' ? 'selected' : '' ?>>Expédiée</option>
                                    <option value="livree" <?= $statut === 'livree' ? 'selected' : '' ?>>Livrée</option>
                                    <option value="annulee" <?= $statut === 'annulee' ? 'selected' : '' ?>>Annulée</option>
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

                <!-- Tableau des commandes -->
                <div class="table-card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="fas fa-list me-2"></i>Liste des Commandes (<?= $totalCommandes ?>)</h5>
                        <small class="text-light">Page <?= $page ?> sur <?= $totalPages ?></small>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th>Référence</th>
                                        <th>Client</th>
                                        <th>Date</th>
                                        <th>Articles</th>
                                        <th>Total</th>
                                        <th>Statut</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($commandes)): ?>
                                        <tr>
                                            <td colspan="7" class="text-center py-5">
                                                <i class="fas fa-shopping-cart fa-3x text-muted mb-3"></i>
                                                <h5 class="text-muted">Aucune commande trouvée</h5>
                                                <p class="text-muted">Essayez de modifier vos critères de recherche</p>
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($commandes as $commande): ?>
                                            <tr>
                                                <td>
                                                    <strong><?= htmlspecialchars($commande['reference_commande']) ?></strong>
                                                </td>
                                                <td>
                                                    <div>
                                                        <strong><?= htmlspecialchars($commande['user_prenom'] . ' ' . $commande['user_nom']) ?></strong>
                                                        <br>
                                                        <small class="text-muted"><?= htmlspecialchars($commande['user_email']) ?></small>
                                                    </div>
                                                </td>
                                                <td>
                                                    <?= date('d/m/Y H:i', strtotime($commande['date_commande'])) ?>
                                                </td>
                                                <td>
                                                    <span class="badge bg-primary"><?= $commande['nombre_articles'] ?> article(s)</span>
                                                </td>
                                                <td>
                                                    <strong><?= number_format($commande['total_commande_calcule'] ?? $commande['total_commande'] ?? 0, 2, ',', ' ') ?> €</strong>
                                                </td>
                                                <td>
                                                    <span class="status-badge badge-<?= $commande['statut_code'] ?>">
                                                        <?= htmlspecialchars($commande['statut_nom']) ?>
                                                    </span>
                                                </td>
                                                <td class="table-actions">
                                                    <button type="button" class="btn btn-sm btn-outline-primary edit-status" 
                                                            data-bs-toggle="modal" data-bs-target="#editStatusModal"
                                                            data-id="<?= $commande['id'] ?>"
                                                            data-reference="<?= htmlspecialchars($commande['reference_commande']) ?>"
                                                            data-statut="<?= $commande['statut_code'] ?>"
                                                            title="Modifier le statut">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    
                                                    <a href="detail_commande.php?id=<?= $commande['id'] ?>" class="btn btn-sm btn-outline-info" title="Voir le détail">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    
                                                    <form method="POST" class="d-inline" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer cette commande ? Cette action est irréversible.')">
                                                        <input type="hidden" name="commande_id" value="<?= $commande['id'] ?>">
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

    <!-- Modal Modification Statut -->
    <div class="modal fade" id="editStatusModal" tabindex="-1" aria-labelledby="editStatusModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editStatusModalLabel"><i class="fas fa-edit me-2"></i>Modifier le Statut</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="update_status">
                        <input type="hidden" name="commande_id" id="edit_commande_id">
                        
                        <div class="mb-3">
                            <label class="form-label">Référence</label>
                            <p class="form-control-plaintext fw-bold" id="edit_reference_display"></p>
                        </div>
                        
                        <div class="mb-3">
                            <label for="edit_statut" class="form-label required">Nouveau statut</label>
                            <select class="form-select" id="edit_statut" name="statut" required>
                                <option value="en_attente">En attente</option>
                                <option value="confirmee">Confirmée</option>
                                <option value="expediee">Expédiée</option>
                                <option value="livree">Livrée</option>
                                <option value="annulee">Annulée</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary-custom btn-custom" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-primary-custom btn-custom">Mettre à jour</button>
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
            
            // Gestion des boutons de modification de statut
            const editButtons = document.querySelectorAll('.edit-status');
            editButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const id = this.getAttribute('data-id');
                    const reference = this.getAttribute('data-reference');
                    const statut = this.getAttribute('data-statut');
                    
                    document.getElementById('edit_commande_id').value = id;
                    document.getElementById('edit_reference_display').textContent = reference;
                    document.getElementById('edit_statut').value = statut;
                });
            });
        });
    </script>
</body>
</html>