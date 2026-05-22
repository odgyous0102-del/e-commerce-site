<?php
session_start();
require_once 'config/database.php';

// Définir les variables pour le header
$page_title = 'Boutique TechShop | Découvrez nos produits high-tech';
$page_description = 'Explorez notre sélection premium de produits technologiques. Smartphones, ordinateurs, accessoires et plus encore aux meilleurs prix.';

// Pagination
$articles_par_page = 12;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $articles_par_page;

// Filtres
$categorie_id = isset($_GET['categorie']) ? (int)$_GET['categorie'] : null;
$marque = isset($_GET['marque']) ? $_GET['marque'] : null;
$prix_min = isset($_GET['prix_min']) ? (float)$_GET['prix_min'] : null;
$prix_max = isset($_GET['prix_max']) ? (float)$_GET['prix_max'] : null;
$recherche = isset($_GET['recherche']) ? $_GET['recherche'] : '';
$tri = isset($_GET['tri']) ? $_GET['tri'] : 'date_desc';

// Construction de la requête - CORRIGÉ : retirer les références à la table avis qui n'existe pas
$sql = "SELECT a.*, c.nom as categorie_nom
        FROM article a 
        LEFT JOIN categorie_article c ON a.categorie_id = c.id 
        WHERE a.est_actif = 1";
$params = [];
$types = [];

if ($categorie_id) {
    $sql .= " AND a.categorie_id = ?";
    $params[] = $categorie_id;
    $types[] = PDO::PARAM_INT;
}

if ($marque) {
    $sql .= " AND a.marque = ?";
    $params[] = $marque;
    $types[] = PDO::PARAM_STR;
}

if ($prix_min !== null) {
    $sql .= " AND (a.est_en_promotion = 0 AND a.prix >= ? OR a.est_en_promotion = 1 AND a.prix_promotion >= ?)";
    $params[] = $prix_min;
    $params[] = $prix_min;
    $types[] = PDO::PARAM_STR;
    $types[] = PDO::PARAM_STR;
}

if ($prix_max !== null) {
    $sql .= " AND (a.est_en_promotion = 0 AND a.prix <= ? OR a.est_en_promotion = 1 AND a.prix_promotion <= ?)";
    $params[] = $prix_max;
    $params[] = $prix_max;
    $types[] = PDO::PARAM_STR;
    $types[] = PDO::PARAM_STR;
}

if ($recherche) {
    $sql .= " AND (a.titre LIKE ? OR a.description LIKE ? OR a.reference LIKE ? OR a.marque LIKE ?)";
    $search_term = "%$recherche%";
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
    $types[] = PDO::PARAM_STR;
    $types[] = PDO::PARAM_STR;
    $types[] = PDO::PARAM_STR;
    $types[] = PDO::PARAM_STR;
}

// Tri
switch ($tri) {
    case 'prix_asc':
        $sql .= " ORDER BY CASE WHEN a.est_en_promotion = 1 THEN a.prix_promotion ELSE a.prix END ASC";
        break;
    case 'prix_desc':
        $sql .= " ORDER BY CASE WHEN a.est_en_promotion = 1 THEN a.prix_promotion ELSE a.prix END DESC";
        break;
    case 'nom_asc':
        $sql .= " ORDER BY a.titre ASC";
        break;
    case 'nom_desc':
        $sql .= " ORDER BY a.titre DESC";
        break;
    case 'promo':
        $sql .= " ORDER BY a.est_en_promotion DESC, a.date_creation DESC";
        break;
    default:
        $sql .= " ORDER BY a.date_creation DESC";
        break;
}

// Compte total pour pagination - CORRIGÉ : retirer les références à avis
$sql_count = "SELECT COUNT(DISTINCT a.id) as total FROM article a 
              LEFT JOIN categorie_article c ON a.categorie_id = c.id 
              WHERE a.est_actif = 1";

$conditions = [];
if ($categorie_id) {
    $conditions[] = "a.categorie_id = " . $pdo->quote($categorie_id);
}
if ($marque) {
    $conditions[] = "a.marque = " . $pdo->quote($marque);
}
if ($prix_min !== null) {
    $conditions[] = "(a.est_en_promotion = 0 AND a.prix >= $prix_min OR a.est_en_promotion = 1 AND a.prix_promotion >= $prix_min)";
}
if ($prix_max !== null) {
    $conditions[] = "(a.est_en_promotion = 0 AND a.prix <= $prix_max OR a.est_en_promotion = 1 AND a.prix_promotion <= $prix_max)";
}
if ($recherche) {
    $search_term = "%$recherche%";
    $conditions[] = "(a.titre LIKE " . $pdo->quote($search_term) . " 
                      OR a.description LIKE " . $pdo->quote($search_term) . " 
                      OR a.reference LIKE " . $pdo->quote($search_term) . "
                      OR a.marque LIKE " . $pdo->quote($search_term) . ")";
}

if (!empty($conditions)) {
    $sql_count .= " AND " . implode(" AND ", $conditions);
}

$total_articles = $pdo->query($sql_count)->fetch()['total'];
$total_pages = ceil($total_articles / $articles_par_page);

// Requête principale avec pagination
$sql .= " LIMIT $articles_par_page OFFSET $offset";

$stmt = $pdo->prepare($sql);
foreach ($params as $key => $value) {
    $stmt->bindValue($key + 1, $value, $types[$key]);
}
$stmt->execute();
$articles = $stmt->fetchAll();

// Récupération des catégories et marques pour les filtres
$categories = $pdo->query("SELECT * FROM categorie_article WHERE est_actif = 1 ORDER BY nom")->fetchAll();
$marques = $pdo->query("SELECT DISTINCT marque FROM article WHERE marque IS NOT NULL AND marque != '' AND est_actif = 1 ORDER BY marque")->fetchAll();

// Produits populaires (pour la sidebar) - CORRIGÉ : utiliser ligne_commande au lieu de commande_ligne
$produits_populaires = $pdo->query("
    SELECT a.*, COUNT(lc.article_id) as nb_ventes 
    FROM article a 
    LEFT JOIN ligne_commande lc ON a.id = lc.article_id 
    WHERE a.est_actif = 1 
    GROUP BY a.id 
    ORDER BY nb_ventes DESC, a.date_creation DESC 
    LIMIT 5
")->fetchAll();

// Inclure le header
include 'header.php';
?>

<!-- Styles spécifiques à la boutique -->
<style>
    .product-card {
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        border: none;
        border-radius: 16px;
        overflow: hidden;
        box-shadow: 0 4px 6px rgba(0,0,0,0.05);
        height: 100%;
    }
    
    .product-card:hover {
        transform: translateY(-8px);
        box-shadow: 0 20px 40px rgba(0,0,0,0.15);
    }
    
    .product-image {
        height: 250px;
        object-fit: cover;
        transition: transform 0.3s ease;
    }
    
    .product-card:hover .product-image {
        transform: scale(1.05);
    }
    
    .old-price {
        text-decoration: line-through;
        color: #6c757d;
        font-size: 0.9em;
    }
    
    .promo-badge {
        position: absolute;
        top: 12px;
        right: 12px;
        background: var(--gradient-accent);
        color: white;
        padding: 0.5rem 1rem;
        border-radius: 25px;
        font-weight: 600;
        font-size: 0.8rem;
        box-shadow: 0 4px 12px rgba(245, 158, 11, 0.3);
        z-index: 2;
    }
    
    .stock-badge {
        position: absolute;
        top: 12px;
        left: 12px;
        z-index: 2;
    }
    
    .filter-section {
        background: white;
        border-radius: 16px;
        padding: 1.5rem;
        box-shadow: var(--shadow-sm);
        margin-bottom: 1.5rem;
    }
    
    .price-display {
        font-weight: 700;
        color: var(--primary-color);
    }
    
    .rating-stars {
        color: #ffc107;
    }
    
    .filter-group {
        margin-bottom: 1.5rem;
    }
    
    .filter-group:last-child {
        margin-bottom: 0;
    }
    
    .filter-title {
        font-weight: 600;
        margin-bottom: 0.75rem;
        color: var(--dark-color);
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    
    .product-actions {
        opacity: 0;
        transition: opacity 0.3s ease;
    }
    
    .product-card:hover .product-actions {
        opacity: 1;
    }
    
    .category-badge {
        background: var(--gradient-primary);
        color: white;
        padding: 0.25rem 0.75rem;
        border-radius: 20px;
        font-size: 0.75rem;
        font-weight: 500;
    }
    
    .sort-select {
        border: 1px solid var(--border-color);
        border-radius: 10px;
        padding: 0.5rem 1rem;
        background: white;
    }
    
    .price-slider {
        width: 100%;
        margin: 1rem 0;
    }
    
    .price-inputs {
        display: flex;
        gap: 0.5rem;
        margin-top: 0.5rem;
    }
    
    .price-inputs input {
        flex: 1;
        border: 1px solid var(--border-color);
        border-radius: 8px;
        padding: 0.5rem;
    }
    
    .popular-product {
        display: flex;
        align-items: center;
        gap: 1rem;
        padding: 1rem;
        border-radius: 12px;
        transition: background-color 0.3s ease;
        text-decoration: none;
        color: inherit;
    }
    
    .popular-product:hover {
        background-color: rgba(37, 99, 235, 0.05);
    }
    
    .popular-product img {
        width: 60px;
        height: 60px;
        object-fit: cover;
        border-radius: 8px;
    }
    
    .filter-active {
        background: var(--gradient-primary) !important;
        color: white !important;
        border-color: var(--primary-color) !important;
    }
    
    .results-info {
        background: white;
        padding: 1rem 1.5rem;
        border-radius: 12px;
        box-shadow: var(--shadow-sm);
        margin-bottom: 1.5rem;
    }
    
    @media (max-width: 768px) {
        .product-actions {
            opacity: 1;
        }
        
        .filter-section {
            margin-bottom: 1rem;
        }
    }
</style>

<div class="container-fluid py-4">
    <div class="row">
        <!-- Sidebar Filtres -->
        <div class="col-lg-3 col-md-4">
            <!-- Filtre de recherche mobile -->
            <div class="filter-section d-block d-md-none mb-3">
                <form method="GET" class="search-form">
                    <div class="input-group">
                        <input type="text" name="recherche" class="form-control" 
                               placeholder="Rechercher un produit..." 
                               value="<?= htmlspecialchars($recherche) ?>">
                        <button class="btn btn-primary" type="submit">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                </form>
            </div>

            <div class="filter-section">
                <h5 class="filter-title">
                    <i class="fas fa-filter"></i>Filtres
                </h5>
                
                <!-- Filtre par catégorie -->
                <div class="filter-group">
                    <h6 class="filter-title">
                        <i class="fas fa-tags"></i>Catégories
                    </h6>
                    <div class="d-flex flex-column gap-2">
                        <a href="boutique.php?<?= http_build_query(array_merge($_GET, ['categorie' => null, 'page' => 1])) ?>" 
                           class="btn btn-sm btn-outline-primary text-start <?= !$categorie_id ? 'filter-active' : '' ?>">
                            <i class="fas fa-th-large me-2"></i>Toutes les catégories
                        </a>
                        <?php foreach ($categories as $categorie): ?>
                            <a href="boutique.php?<?= http_build_query(array_merge($_GET, ['categorie' => $categorie['id'], 'page' => 1])) ?>" 
                               class="btn btn-sm btn-outline-primary text-start <?= $categorie_id == $categorie['id'] ? 'filter-active' : '' ?>">
                                <i class="fas fa-<?= $categorie['icone'] ?? 'tag' ?> me-2"></i>
                                <?= htmlspecialchars($categorie['nom']) ?>
                                <span class="badge bg-secondary ms-2"><?= $categorie['nb_articles'] ?? '' ?></span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Filtre par marque -->
                <div class="filter-group">
                    <h6 class="filter-title">
                        <i class="fas fa-copyright"></i>Marques
                    </h6>
                    <div class="d-flex flex-column gap-2">
                        <a href="boutique.php?<?= http_build_query(array_merge($_GET, ['marque' => null, 'page' => 1])) ?>" 
                           class="btn btn-sm btn-outline-primary text-start <?= !$marque ? 'filter-active' : '' ?>">
                            <i class="fas fa-list me-2"></i>Toutes les marques
                        </a>
                        <?php foreach ($marques as $m): ?>
                            <?php if (!empty($m['marque'])): ?>
                                <a href="boutique.php?<?= http_build_query(array_merge($_GET, ['marque' => $m['marque'], 'page' => 1])) ?>" 
                                   class="btn btn-sm btn-outline-primary text-start <?= $marque == $m['marque'] ? 'filter-active' : '' ?>">
                                    <i class="fas fa-check me-2"></i>
                                    <?= htmlspecialchars($m['marque']) ?>
                                </a>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Filtre par prix -->
                <div class="filter-group">
                    <h6 class="filter-title">
                        <i class="fas fa-euro-sign"></i>Prix
                    </h6>
                    <form method="GET" class="price-filter-form">
                        <?php if ($categorie_id): ?>
                            <input type="hidden" name="categorie" value="<?= $categorie_id ?>">
                        <?php endif; ?>
                        <?php if ($marque): ?>
                            <input type="hidden" name="marque" value="<?= htmlspecialchars($marque) ?>">
                        <?php endif; ?>
                        <?php if ($recherche): ?>
                            <input type="hidden" name="recherche" value="<?= htmlspecialchars($recherche) ?>">
                        <?php endif; ?>
                        <?php if ($tri): ?>
                            <input type="hidden" name="tri" value="<?= htmlspecialchars($tri) ?>">
                        <?php endif; ?>
                        
                        <div class="price-inputs">
                            <input type="number" name="prix_min" class="form-control form-control-sm" 
                                   placeholder="Min" value="<?= $prix_min ?? '' ?>">
                            <input type="number" name="prix_max" class="form-control form-control-sm" 
                                   placeholder="Max" value="<?= $prix_max ?? '' ?>">
                        </div>
                        <button type="submit" class="btn btn-primary btn-sm w-100 mt-2">
                            <i class="fas fa-check me-1"></i>Appliquer
                        </button>
                        <?php if ($prix_min !== null || $prix_max !== null): ?>
                            <a href="boutique.php?<?php 
                                $query = $_GET;
                                unset($query['prix_min']);
                                unset($query['prix_max']);
                                echo http_build_query($query);
                            ?>" class="btn btn-outline-secondary btn-sm w-100 mt-1">
                                <i class="fas fa-times me-1"></i>Effacer
                            </a>
                        <?php endif; ?>
                    </form>
                </div>

                <!-- Bouton reset filtres -->
                <?php if ($categorie_id || $marque || $prix_min !== null || $prix_max !== null || $recherche): ?>
                    <div class="filter-group">
                        <a href="boutique.php" class="btn btn-outline-danger btn-sm w-100">
                            <i class="fas fa-redo me-1"></i>Réinitialiser tous les filtres
                        </a>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Produits populaires -->
            <?php if (!empty($produits_populaires)): ?>
            <div class="filter-section">
                <h5 class="filter-title">
                    <i class="fas fa-fire"></i>Produits populaires
                </h5>
                <div class="d-flex flex-column gap-2">
                    <?php foreach ($produits_populaires as $produit): ?>
                        <a href="produit.php?id=<?= $produit['id'] ?>" class="popular-product">
                            <img src="<?= htmlspecialchars($produit['image_principale'] ?? 'https://via.placeholder.com/60x60?text=Produit') ?>" 
                                 alt="<?= htmlspecialchars($produit['titre']) ?>"
                                 onerror="this.src='https://via.placeholder.com/60x60?text=Image'">
                            <div class="flex-grow-1">
                                <h6 class="mb-1 fw-semibold"><?= htmlspecialchars($produit['titre']) ?></h6>
                                <div class="price-display small">
                                    <?php if ($produit['est_en_promotion'] && $produit['prix_promotion']): ?>
                                        <?= number_format($produit['prix_promotion'], 2, ',', ' ') ?> FCFA
                                    <?php else: ?>
                                        <?= number_format($produit['prix'], 2, ',', ' ') ?> FCFA
                                    <?php endif; ?>
                                </div>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Contenu principal -->
        <div class="col-lg-9 col-md-8">
            <!-- En-tête avec résultats et tri -->
            <div class="results-info">
                <div class="row align-items-center">
                    <div class="col-md-6">
                        <h2 class="h4 mb-0">Notre Boutique</h2>
                        <p class="text-muted mb-0">
                            <?= $total_articles ?> produit<?= $total_articles > 1 ? 's' : '' ?> trouvé<?= $total_articles > 1 ? 's' : '' ?>
                            <?php if ($recherche): ?>
                                pour "<?= htmlspecialchars($recherche) ?>"
                            <?php endif; ?>
                        </p>
                    </div>
                    <div class="col-md-6 text-md-end">
                        <form method="GET" class="d-inline-block">
                            <?php if ($categorie_id): ?>
                                <input type="hidden" name="categorie" value="<?= $categorie_id ?>">
                            <?php endif; ?>
                            <?php if ($marque): ?>
                                <input type="hidden" name="marque" value="<?= htmlspecialchars($marque) ?>">
                            <?php endif; ?>
                            <?php if ($prix_min !== null): ?>
                                <input type="hidden" name="prix_min" value="<?= $prix_min ?>">
                            <?php endif; ?>
                            <?php if ($prix_max !== null): ?>
                                <input type="hidden" name="prix_max" value="<?= $prix_max ?>">
                            <?php endif; ?>
                            <?php if ($recherche): ?>
                                <input type="hidden" name="recherche" value="<?= htmlspecialchars($recherche) ?>">
                            <?php endif; ?>
                            <select name="tri" class="sort-select" onchange="this.form.submit()">
                                <option value="date_desc" <?= $tri == 'date_desc' ? 'selected' : '' ?>>Nouveautés</option>
                                <option value="prix_asc" <?= $tri == 'prix_asc' ? 'selected' : '' ?>>Prix croissant</option>
                                <option value="prix_desc" <?= $tri == 'prix_desc' ? 'selected' : '' ?>>Prix décroissant</option>
                                <option value="nom_asc" <?= $tri == 'nom_asc' ? 'selected' : '' ?>>Nom A-Z</option>
                                <option value="nom_desc" <?= $tri == 'nom_desc' ? 'selected' : '' ?>>Nom Z-A</option>
                                <option value="promo" <?= $tri == 'promo' ? 'selected' : '' ?>>En promotion</option>
                            </select>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Produits -->
            <div class="row g-4">
                <?php if (empty($articles)): ?>
                    <div class="col-12 text-center py-5" data-aos="fade-up">
                        <div class="mb-4">
                            <i class="fas fa-search fa-4x text-muted"></i>
                        </div>
                        <h3 class="h4 text-muted mb-3">Aucun produit trouvé</h3>
                        <p class="text-muted mb-4">Essayez de modifier vos critères de recherche ou explorez nos autres catégories.</p>
                        <a href="boutique.php" class="btn btn-primary btn-lg">
                            <i class="fas fa-redo me-2"></i>Voir tous les produits
                        </a>
                    </div>
                <?php else: ?>
                    <?php foreach ($articles as $index => $article): ?>
                        <div class="col-xl-3 col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="<?= ($index % 4) * 100 ?>">
                            <div class="card product-card h-100">
                                <!-- Badges -->
                                <?php if ($article['est_en_promotion']): ?>
                                    <span class="promo-badge">PROMO -<?= round((($article['prix'] - $article['prix_promotion']) / $article['prix']) * 100) ?>%</span>
                                <?php endif; ?>
                                
                                <span class="stock-badge badge <?= $article['quantite_stock'] > 0 ? 'bg-success' : 'bg-danger' ?>">
                                    <i class="fas fa-<?= $article['quantite_stock'] > 0 ? 'check' : 'times' ?> me-1"></i>
                                    <?= $article['quantite_stock'] > 0 ? 'En stock' : 'Rupture' ?>
                                </span>
                                
                                <!-- Image -->
                                <div class="position-relative overflow-hidden">
                                    <img src="<?= htmlspecialchars($article['image_principale'] ?? 'https://via.placeholder.com/300x250?text=TechShop') ?>" 
                                         class="card-img-top product-image" 
                                         alt="<?= htmlspecialchars($article['titre']) ?>"
                                         onerror="this.src='https://via.placeholder.com/300x250?text=Image+Non+Disponible'">
                                    
                                    <!-- Actions au survol -->
                                    <div class="position-absolute bottom-0 start-0 end-0 p-3 product-actions">
                                        <div class="d-flex justify-content-center gap-2">
                                            <button class="btn btn-light btn-sm rounded-circle" 
                                                    data-bs-toggle="tooltip" 
                                                    title="Voir les détails"
                                                    onclick="window.location.href='produit.php?id=<?= $article['id'] ?>'">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <?php if ($article['quantite_stock'] > 0): ?>
                                                <button class="btn btn-primary btn-sm rounded-circle add-to-cart" 
                                                        data-id="<?= $article['id'] ?>"
                                                        data-bs-toggle="tooltip" 
                                                        title="Ajouter au panier">
                                                    <i class="fas fa-cart-plus"></i>
                                                </button>
                                            <?php endif; ?>
                                            <button class="btn btn-light btn-sm rounded-circle add-to-wishlist" 
                                                    data-id="<?= $article['id'] ?>"
                                                    data-bs-toggle="tooltip" 
                                                    title="Ajouter aux favoris">
                                                <i class="fas fa-heart"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Contenu -->
                                <div class="card-body d-flex flex-column">
                                    <!-- Catégorie -->
                                    <div class="mb-2">
                                        <span class="category-badge"><?= htmlspecialchars($article['categorie_nom']) ?></span>
                                    </div>
                                    
                                    <!-- Titre -->
                                    <h5 class="card-title fs-6 fw-bold mb-2">
                                        <a href="produit.php?id=<?= $article['id'] ?>" class="text-decoration-none text-dark">
                                            <?= htmlspecialchars($article['titre']) ?>
                                        </a>
                                    </h5>
                                    
                                    <!-- Marque -->
                                    <?php if ($article['marque']): ?>
                                        <p class="text-muted small mb-2">
                                            <i class="fas fa-copyright me-1"></i><?= htmlspecialchars($article['marque']) ?>
                                        </p>
                                    <?php endif; ?>
                                    
                                    <!-- Description -->
                                    <p class="card-text text-muted small flex-grow-1 mb-3">
                                        <?= substr(strip_tags($article['description'] ?? ''), 0, 80) ?>...
                                    </p>
                                    
                                    <!-- Prix et actions -->
                                    <div class="mt-auto">
                                        <div class="d-flex justify-content-between align-items-center mb-3">
                                            <div>
                                                <?php if ($article['est_en_promotion'] && $article['prix_promotion']): ?>
                                                    <span class="price-display h5 mb-0"><?= number_format($article['prix_promotion'], 2, ',', ' ') ?> FCFA</span>
                                                    <span class="old-price small"><?= number_format($article['prix'], 2, ',', ' ') ?> FCFA</span>
                                                <?php else: ?>
                                                    <span class="price-display h5 mb-0"><?= number_format($article['prix'], 2, ',', ' ') ?> FCFA</span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        
                                        <div class="d-grid gap-2">
                                            <a href="produit.php?id=<?= $article['id'] ?>" class="btn btn-outline-primary btn-sm">
                                                <i class="fas fa-eye me-1"></i>Voir détails
                                            </a>
                                            <?php if ($article['quantite_stock'] > 0): ?>
                                                <button class="btn btn-primary btn-sm add-to-cart" data-id="<?= $article['id'] ?>">
                                                    <i class="fas fa-cart-plus me-1"></i>Ajouter au panier
                                                </button>
                                            <?php else: ?>
                                                <button class="btn btn-secondary btn-sm" disabled>
                                                    <i class="fas fa-bell me-1"></i>Prévenir en stock
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
                <nav aria-label="Pagination" class="mt-5">
                    <ul class="pagination justify-content-center">
                        <?php if ($page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>">
                                    <i class="fas fa-chevron-left me-1"></i>Précédent
                                </a>
                            </li>
                        <?php endif; ?>

                        <?php 
                        $start_page = max(1, $page - 2);
                        $end_page = min($total_pages, $page + 2);
                        
                        if ($start_page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => 1])) ?>">1</a>
                            </li>
                            <?php if ($start_page > 2): ?>
                                <li class="page-item disabled">
                                    <span class="page-link">...</span>
                                </li>
                            <?php endif; ?>
                        <?php endif; ?>

                        <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
                            <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                                <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>"><?= $i ?></a>
                            </li>
                        <?php endfor; ?>

                        <?php if ($end_page < $total_pages): ?>
                            <?php if ($end_page < $total_pages - 1): ?>
                                <li class="page-item disabled">
                                    <span class="page-link">...</span>
                                </li>
                            <?php endif; ?>
                            <li class="page-item">
                                <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $total_pages])) ?>"><?= $total_pages ?></a>
                            </li>
                        <?php endif; ?>

                        <?php if ($page < $total_pages): ?>
                            <li class="page-item">
                                <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>">
                                    Suivant<i class="fas fa-chevron-right ms-1"></i>
                                </a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
// Initialisation des tooltips
document.addEventListener('DOMContentLoaded', function() {
    // Tooltips Bootstrap
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Ajout au panier
    document.querySelectorAll('.add-to-cart').forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const productId = this.getAttribute('data-id');
            const productCard = this.closest('.product-card');
            
            // Sauvegarder le contenu original
            const originalHTML = this.innerHTML;
            const originalClass = this.className;
            
            // Animation d'ajout
            this.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Ajout...';
            this.className = 'btn btn-success btn-sm';
            this.disabled = true;
            
            // Appel AJAX
            fetch('ajax/add_to_cart.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'product_id=' + productId + '&quantity=1'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Mettre à jour le compteur du panier
                    updateCartCount(data.cart_count);
                    
                    // Animation de confirmation
                    this.innerHTML = '<i class="fas fa-check me-1"></i>Ajouté !';
                    
                    // Afficher notification
                    showNotification('✓ Produit ajouté au panier', 'success');
                    
                    // Réinitialiser après 2 secondes
                    setTimeout(() => {
                        this.innerHTML = originalHTML;
                        this.className = originalClass;
                        this.disabled = false;
                    }, 2000);
                } else {
                    showNotification('❌ ' + data.message, 'error');
                    this.innerHTML = originalHTML;
                    this.className = originalClass;
                    this.disabled = false;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('❌ Erreur réseau', 'error');
                this.innerHTML = originalHTML;
                this.className = originalClass;
                this.disabled = false;
            });
        });
    });

    // Ajout aux favoris
    document.querySelectorAll('.add-to-wishlist').forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const productId = this.getAttribute('data-id');
            
            // Animation
            const originalHTML = this.innerHTML;
            this.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
            
            // Simuler l'ajout aux favoris (à remplacer par un vrai appel AJAX)
            setTimeout(() => {
                this.innerHTML = '<i class="fas fa-heart text-danger"></i>';
                showNotification('❤️ Produit ajouté aux favoris', 'success');
                
                // Réinitialiser après 2 secondes
                setTimeout(() => {
                    this.innerHTML = originalHTML;
                }, 2000);
            }, 1000);
        });
    });

    function updateCartCount(count) {
        const cartBadges = document.querySelectorAll('.cart-badge');
        cartBadges.forEach(badge => {
            badge.textContent = count > 99 ? '99+' : count;
        });
        
        // Si pas de badge mais qu'il y a des articles, créer le badge
        if (count > 0 && cartBadges.length === 0) {
            const cartLinks = document.querySelectorAll('a[href="panier.php"]');
            cartLinks.forEach(link => {
                const badge = document.createElement('span');
                badge.className = 'cart-badge position-absolute top-0 start-100 translate-middle';
                badge.textContent = count > 99 ? '99+' : count;
                link.appendChild(badge);
            });
        } else if (count === 0) {
            // Supprimer les badges si panier vide
            cartBadges.forEach(badge => badge.remove());
        }
    }

    function showNotification(message, type = 'info') {
        // Créer une notification toast
        const toast = document.createElement('div');
        toast.className = `alert alert-${type} position-fixed`;
        toast.style.cssText = `
            top: 20px;
            right: 20px;
            z-index: 1060;
            min-width: 300px;
            animation: slideInRight 0.3s ease-out;
            border: none;
            border-radius: 12px;
            box-shadow: var(--shadow-lg);
        `;
        toast.innerHTML = `
            <div class="d-flex align-items-center">
                <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-triangle'} me-2"></i>
                <span class="fw-medium">${message}</span>
                <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
            </div>
        `;
        
        document.body.appendChild(toast);
        
        // Auto-dismiss
        setTimeout(() => {
            if (toast.parentElement) {
                toast.remove();
            }
        }, 4000);
    }

    // Filtres prix en temps réel
    const priceInputs = document.querySelectorAll('.price-inputs input');
    priceInputs.forEach(input => {
        input.addEventListener('input', function() {
            const form = this.closest('form');
            const prixMin = form.querySelector('input[name="prix_min"]').value;
            const prixMax = form.querySelector('input[name="prix_max"]').value;
            
            // Validation basique
            if (prixMin && prixMax && parseFloat(prixMin) > parseFloat(prixMax)) {
                this.setCustomValidity('Le prix minimum ne peut pas être supérieur au prix maximum');
            } else {
                this.setCustomValidity('');
            }
        });
    });
});
</script>




<?php
// Inclure le footer
include 'footer.php';
?>
