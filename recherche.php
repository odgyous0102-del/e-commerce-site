<?php
// recherche.php - Page de recherche complète
session_start();

// Connexion à la base de données
try {
    $host = '127.0.0.1:3306';
    $dbname = 'e_commerce';
    $username = 'root';
    $password = '';
    
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Erreur de connexion : " . $e->getMessage());
}

// Récupération du terme de recherche
$terme_recherche = isset($_GET['q']) ? trim($_GET['q']) : '';
$resultats = [];
$nombre_resultats = 0;

// Récupération des filtres optionnels
$categorie_filter = isset($_GET['categorie']) ? intval($_GET['categorie']) : 0;
$prix_min = isset($_GET['prix_min']) ? floatval($_GET['prix_min']) : 0;
$prix_max = isset($_GET['prix_max']) ? floatval($_GET['prix_max']) : 0;
$en_promotion = isset($_GET['promotion']) ? true : false;

if (!empty($terme_recherche)) {
    // Construction de la requête de recherche
    $sql = "SELECT a.*, c.nom as categorie_nom 
            FROM article a 
            LEFT JOIN categorie_article c ON a.categorie_id = c.id 
            WHERE a.est_actif = 1 
            AND (a.titre LIKE :terme 
                 OR a.description LIKE :terme 
                 OR a.marque LIKE :terme 
                 OR c.nom LIKE :terme)";
    
    // Ajout des filtres
    $params = [':terme' => '%' . $terme_recherche . '%'];
    
    if ($categorie_filter > 0) {
        $sql .= " AND a.categorie_id = :categorie";
        $params[':categorie'] = $categorie_filter;
    }
    
    if ($prix_min > 0) {
        $sql .= " AND a.prix >= :prix_min";
        $params[':prix_min'] = $prix_min;
    }
    
    if ($prix_max > 0) {
        $sql .= " AND a.prix <= :prix_max";
        $params[':prix_max'] = $prix_max;
    }
    
    if ($en_promotion) {
        $sql .= " AND a.est_en_promotion = 1";
    }
    
    $sql .= " ORDER BY a.date_creation DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    
    $resultats = $stmt->fetchAll();
    $nombre_resultats = count($resultats);
}

// Récupération des catégories pour les filtres
try {
    $sqlCategories = "SELECT id, nom FROM categorie_article WHERE est_actif = 1 ORDER BY nom";
    $stmtCategories = $pdo->query($sqlCategories);
    $categories = $stmtCategories->fetchAll();
} catch(PDOException $e) {
    $categories = [];
}

$est_connecte = isset($_SESSION['utilisateur_id']);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Résultats de recherche - Boutique en Ligne</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        .search-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 3rem 0;
            margin-bottom: 2rem;
        }
        
        .product-card {
            transition: transform 0.3s ease;
            height: 100%;
            margin-bottom: 1.5rem;
            border: none;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        
        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 15px rgba(0,0,0,0.2);
        }
        
        .product-image {
            height: 200px;
            object-fit: cover;
            transition: transform 0.3s ease;
        }
        
        .product-card:hover .product-image {
            transform: scale(1.05);
        }
        
        .badge-promo {
            position: absolute;
            top: 10px;
            left: 10px;
            z-index: 2;
        }
        
        .no-results {
            text-align: center;
            padding: 3rem 0;
        }
        
        .search-stats {
            background-color: #f8f9fa;
            padding: 1rem;
            border-radius: 0.375rem;
            margin-bottom: 2rem;
        }
        
        .filter-sidebar {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 1.5rem;
            height: fit-content;
        }
        
        .filter-section {
            margin-bottom: 1.5rem;
        }
        
        .filter-title {
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: #2c3e50;
        }
        
        .price-range {
            display: flex;
            gap: 10px;
            margin-bottom: 1rem;
        }
        
        .price-input {
            flex: 1;
        }
        
        .result-count {
            font-size: 1.1rem;
            font-weight: 500;
        }
        
        .sort-options {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }
        
        .sort-btn {
            border-radius: 20px;
            padding: 0.375rem 0.75rem;
            font-size: 0.875rem;
        }
        
        @media (max-width: 768px) {
            .search-header {
                padding: 2rem 0;
            }
            
            .sort-options {
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <?php 
    // Inclure le header
    $page_title = "Résultats de recherche";
    include 'header.php'; 
    ?>

    <!-- En-tête de recherche -->
    <section class="search-header">
        <div class="container">
            <div class="row">
                <div class="col-12 text-center">
                    <h1 class="display-5 fw-bold mb-3">Résultats de recherche</h1>
                    
                    <!-- Barre de recherche dans la page de résultats -->
                    <form class="row g-3 justify-content-center" action="recherche.php" method="GET">
                        <div class="col-md-8">
                            <div class="input-group input-group-lg">
                                <input type="search" 
                                       class="form-control" 
                                       name="q" 
                                       placeholder="Rechercher un produit..." 
                                       value="<?= htmlspecialchars($terme_recherche) ?>"
                                       required>
                                <button class="btn btn-light" type="submit">
                                    <i class="fas fa-search"></i>
                                </button>
                            </div>
                        </div>
                    </form>
                    
                    <?php if (!empty($terme_recherche)): ?>
                        <p class="lead mt-3">
                            <?= $nombre_resultats ?> résultat(s) pour "<?= htmlspecialchars($terme_recherche) ?>"
                        </p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>

    <!-- Résultats -->
    <section class="py-5">
        <div class="container">
            <?php if (empty($terme_recherche)): ?>
                <div class="no-results">
                    <i class="fas fa-search fa-4x text-muted mb-3"></i>
                    <h3 class="text-muted">Entrez un terme de recherche</h3>
                    <p class="text-muted">Utilisez la barre de recherche pour trouver des produits</p>
                </div>
            <?php elseif ($nombre_resultats === 0): ?>
                <div class="no-results">
                    <i class="fas fa-search fa-4x text-muted mb-3"></i>
                    <h3 class="text-muted">Aucun résultat trouvé</h3>
                    <p class="text-muted">Aucun produit ne correspond à votre recherche "<?= htmlspecialchars($terme_recherche) ?>"</p>
                    <div class="mt-4">
                        <h5>Suggestions :</h5>
                        <ul class="list-unstyled">
                            <li>✓ Vérifiez l'orthographe des mots</li>
                            <li>✓ Utilisez des termes plus généraux</li>
                            <li>✓ Essayez d'autres mots-clés</li>
                            <li>✓ Vérifiez les filtres appliqués</li>
                        </ul>
                        <a href="boutique.php" class="btn btn-primary mt-3">
                            <i class="fas fa-store me-2"></i>Voir tous les produits
                        </a>
                    </div>
                </div>
            <?php else: ?>
                <div class="row">
                    <!-- Sidebar des filtres -->
                    <div class="col-lg-3 mb-4">
                        <div class="filter-sidebar">
                            <h5 class="filter-title">Filtrer les résultats</h5>
                            
                            <form id="filterForm" method="GET" action="recherche.php">
                                <input type="hidden" name="q" value="<?= htmlspecialchars($terme_recherche) ?>">
                                
                                <!-- Filtre par catégorie -->
                                <div class="filter-section">
                                    <h6 class="filter-title">Catégorie</h6>
                                    <select class="form-select" name="categorie" onchange="this.form.submit()">
                                        <option value="">Toutes les catégories</option>
                                        <?php foreach($categories as $cat): ?>
                                            <option value="<?= $cat['id'] ?>" 
                                                <?= ($categorie_filter == $cat['id']) ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($cat['nom']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <!-- Filtre par prix -->
                                <div class="filter-section">
                                    <h6 class="filter-title">Prix</h6>
                                    <div class="price-range">
                                        <input type="number" class="form-control price-input" name="prix_min" 
                                               placeholder="Min" value="<?= $prix_min > 0 ? $prix_min : '' ?>" 
                                               min="0" step="0.01">
                                        <input type="number" class="form-control price-input" name="prix_max" 
                                               placeholder="Max" value="<?= $prix_max > 0 ? $prix_max : '' ?>" 
                                               min="0" step="0.01">
                                    </div>
                                    <button type="submit" class="btn btn-sm btn-outline-primary w-100">Appliquer</button>
                                </div>
                                
                                <!-- Filtre promotion -->
                                <div class="filter-section">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="promotion" id="promotionCheck" 
                                            <?= $en_promotion ? 'checked' : '' ?> onchange="this.form.submit()">
                                        <label class="form-check-label" for="promotionCheck">
                                            En promotion seulement
                                        </label>
                                    </div>
                                </div>
                                
                                <!-- Bouton réinitialiser -->
                                <div class="filter-section">
                                    <a href="recherche.php?q=<?= urlencode($terme_recherche) ?>" class="btn btn-outline-secondary w-100">
                                        <i class="fas fa-times me-2"></i>Réinitialiser
                                    </a>
                                </div>
                            </form>
                        </div>
                    </div>
                    
                    <!-- Résultats de recherche -->
                    <div class="col-lg-9">
                        <div class="search-stats">
                            <div class="row align-items-center">
                                <div class="col-md-6">
                                    <span class="result-count">
                                        <strong><?= $nombre_resultats ?></strong> produit(s) trouvé(s) pour "<em><?= htmlspecialchars($terme_recherche) ?></em>"
                                    </span>
                                </div>
                                <div class="col-md-6 text-md-end">
                                    <div class="sort-options">
                                        <span class="me-2">Trier par:</span>
                                        <button class="btn btn-outline-secondary btn-sm sort-btn active">Pertinence</button>
                                        <button class="btn btn-outline-secondary btn-sm sort-btn">Prix croissant</button>
                                        <button class="btn btn-outline-secondary btn-sm sort-btn">Prix décroissant</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <?php foreach($resultats as $article): ?>
                                <div class="col-xl-4 col-lg-6 col-md-6">
                                    <div class="card product-card h-100">
                                        <div class="position-relative">
                                            <?php 
                                            $imagePath = $article['image_principale'] ?? '';
                                            if ($imagePath && file_exists($imagePath)): 
                                            ?>
                                                <img src="<?= htmlspecialchars($imagePath) ?>" 
                                                     class="card-img-top product-image" 
                                                     alt="<?= htmlspecialchars($article['titre']) ?>">
                                            <?php else: ?>
                                                <img src="https://images.unsplash.com/photo-1505740420928-5e560c06d30e?ixlib=rb-4.0.3&auto=format&fit=crop&w=500&q=80" 
                                                     class="card-img-top product-image" 
                                                     alt="Image non disponible">
                                            <?php endif; ?>
                                            
                                            <?php if(isset($article['est_en_promotion']) && $article['est_en_promotion']): ?>
                                                <span class="badge-promo badge bg-danger">
                                                    <i class="fas fa-fire me-1"></i>Promo
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <div class="card-body d-flex flex-column">
                                            <div class="d-flex justify-content-between align-items-start mb-2">
                                                <span class="badge bg-secondary"><?= htmlspecialchars($article['categorie_nom']) ?></span>
                                                <small class="<?= $article['quantite_stock'] < 10 ? 'text-danger' : 'text-muted' ?>">
                                                    <i class="fas fa-box me-1"></i><?= $article['quantite_stock'] ?>
                                                </small>
                                            </div>
                                            
                                            <h5 class="card-title"><?= htmlspecialchars($article['titre']) ?></h5>
                                            <p class="card-text text-muted flex-grow-1">
                                                <?= substr(htmlspecialchars($article['description']), 0, 100) ?>...
                                            </p>
                                            
                                            <div class="mt-auto">
                                                <div class="d-flex justify-content-between align-items-center mb-3">
                                                    <div class="price">
                                                        <?php if($article['est_en_promotion'] && $article['prix_promotion']): ?>
                                                            <span class="text-muted text-decoration-line-through">
                                                                <?= number_format($article['prix'], 2, ',', ' ') ?> €
                                                            </span>
                                                            <span class="h5 text-danger fw-bold ms-2">
                                                                <?= number_format($article['prix_promotion'], 2, ',', ' ') ?> €
                                                            </span>
                                                        <?php else: ?>
                                                            <span class="h5 text-primary fw-bold">
                                                                <?= number_format($article['prix'], 2, ',', ' ') ?> €
                                                            </span>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                                
                                                <div class="d-grid gap-2">
                                                    <?php if ($article['quantite_stock'] > 0): ?>
                                                        <button class="btn btn-primary add-to-cart" 
                                                                data-id="<?= $article['id'] ?>"
                                                                data-name="<?= htmlspecialchars($article['titre']) ?>">
                                                            <i class="fas fa-cart-plus me-2"></i>Ajouter au panier
                                                        </button>
                                                        <a href="produit.php?id=<?= $article['id'] ?>" class="btn btn-outline-secondary">
                                                            <i class="fas fa-eye me-2"></i>Voir détails
                                                        </a>
                                                    <?php else: ?>
                                                        <button class="btn btn-secondary" disabled>
                                                            <i class="fas fa-times me-2"></i>Rupture de stock
                                                        </button>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <?php 
    // Inclure le footer
    include 'footer.php'; 
    ?>

    <!-- Script Bootstrap -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Gestion de l'ajout au panier
        document.querySelectorAll('.add-to-cart').forEach(button => {
            button.addEventListener('click', function() {
                const productId = this.getAttribute('data-id');
                const productName = this.getAttribute('data-name');
                
                // Animation du bouton
                const originalHTML = this.innerHTML;
                this.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Ajout...';
                this.disabled = true;
                
                // Appel AJAX pour ajouter au panier
                fetch('ajax/ajouter_panier.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'id_article=' + productId + '&quantite=1'
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Mettre à jour le compteur du panier
                        const cartCount = document.getElementById('panier-count');
                        if (cartCount) {
                            const currentCount = parseInt(cartCount.textContent) || 0;
                            cartCount.textContent = currentCount + 1;
                            cartCount.style.display = 'inline';
                        }
                        
                        // Message de succès
                        showFlashMessage(`${productName} a été ajouté à votre panier !`, 'success');
                    } else {
                        showFlashMessage('Erreur: ' + data.message, 'danger');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showFlashMessage('Erreur lors de l\'ajout au panier', 'danger');
                })
                .finally(() => {
                    // Réinitialiser le bouton
                    this.innerHTML = originalHTML;
                    this.disabled = false;
                });
            });
        });

        // Fonction pour afficher les messages flash
        function showFlashMessage(message, type = 'info') {
            // Créer l'élément d'alerte
            const alertDiv = document.createElement('div');
            alertDiv.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
            alertDiv.style.cssText = 'top: 100px; right: 20px; z-index: 1050; min-width: 300px;';
            alertDiv.innerHTML = `
                <div class="d-flex align-items-center">
                    <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'danger' ? 'exclamation-circle' : 'info-circle'} me-2"></i>
                    <span>${message}</span>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            
            document.body.appendChild(alertDiv);
            
            // Auto-dismiss après 5 secondes
            setTimeout(() => {
                if (alertDiv.parentElement) {
                    const bsAlert = new bootstrap.Alert(alertDiv);
                    bsAlert.close();
                }
            }, 5000);
        }

        // Gestion des boutons de tri
        document.querySelectorAll('.sort-btn').forEach(button => {
            button.addEventListener('click', function() {
                document.querySelectorAll('.sort-btn').forEach(btn => btn.classList.remove('active'));
                this.classList.add('active');
                // Ici vous pouvez ajouter la logique de tri
            });
        });
    </script>
</body>
</html>