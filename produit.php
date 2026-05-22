<?php
// Démarrer la session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Initialiser le panier s'il n'existe pas
if (!isset($_SESSION['panier'])) {
    $_SESSION['panier'] = [];
}

// Calculer le nombre total d'articles dans le panier pour l'affichage
$total_panier = 0;
foreach ($_SESSION['panier'] as $item) {
    if (isset($item['quantity'])) {
        $total_panier += $item['quantity'];
    }
}

// Définir le titre de la page avant d'inclure le header
$page_title = 'Détails du Produit - E-Commerce';

// Connexion à la base de données
$host = '127.0.0.1:3306';
$dbname = 'e_commerce';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Erreur de connexion : " . $e->getMessage());
}

// Récupération de l'ID du produit depuis l'URL
$product_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($product_id <= 0) {
    header('Location: boutique.php');
    exit;
}

// Requête pour récupérer les informations du produit
$sql = "SELECT a.*, c.nom as categorie_nom 
        FROM article a 
        LEFT JOIN categorie_article c ON a.categorie_id = c.id 
        WHERE a.id = ? AND a.est_actif = 1";
$stmt = $pdo->prepare($sql);
$stmt->execute([$product_id]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$product) {
    header('Location: boutique.php');
    exit;
}

// Requête pour récupérer les images du produit
$sql_images = "SELECT * FROM image_article WHERE article_id = ? ORDER BY ordre_affichage";
$stmt_images = $pdo->prepare($sql_images);
$stmt_images->execute([$product_id]);
$images = $stmt_images->fetchAll(PDO::FETCH_ASSOC);

// Requête pour récupérer les produits similaires (même catégorie)
$sql_related = "SELECT * FROM article 
                WHERE categorie_id = ? AND id != ? AND est_actif = 1 
                ORDER BY date_creation DESC 
                LIMIT 4";
$stmt_related = $pdo->prepare($sql_related);
$stmt_related->execute([$product['categorie_id'], $product_id]);
$related_products = $stmt_related->fetchAll(PDO::FETCH_ASSOC);

// Récupérer les avis approuvés pour ce produit
$sql_avis = "SELECT a.*, u.prenom, u.nom 
             FROM avis a 
             JOIN utilisateurs u ON a.utilisateur_id = u.id 
             WHERE a.article_id = ? AND a.est_approuve = 1 
             ORDER BY a.date_creation DESC";
$stmt_avis = $pdo->prepare($sql_avis);
$stmt_avis->execute([$product_id]);
$avis = $stmt_avis->fetchAll(PDO::FETCH_ASSOC);

// Calculer la note moyenne
$note_moyenne = 0;
$nombre_avis = count($avis);
if ($nombre_avis > 0) {
    $somme_notes = 0;
    foreach ($avis as $a) {
        $somme_notes += $a['note'];
    }
    $note_moyenne = round($somme_notes / $nombre_avis, 1);
}

// Vérifier si l'utilisateur connecté a déjà posté un avis
$user_has_review = false;
if (isset($_SESSION['utilisateur_id'])) {
    $sql_check_review = "SELECT id FROM avis WHERE article_id = ? AND utilisateur_id = ?";
    $stmt_check_review = $pdo->prepare($sql_check_review);
    $stmt_check_review->execute([$product_id, $_SESSION['utilisateur_id']]);
    $user_has_review = $stmt_check_review->fetch() !== false;
}

// Mettre à jour le titre de la page avec le nom du produit
$page_title = htmlspecialchars($product['titre']) . ' - E-Commerce';

// Inclure le header
include 'header.php';
?>

<style>
    .product-detail {
        background: white;
        border-radius: 10px;
        padding: 30px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        margin-bottom: 30px;
    }

    .main-image img {
        width: 100%;
        height: 400px;
        object-fit: cover;
        border-radius: 10px;
        margin-bottom: 15px;
    }

    .image-gallery {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 10px;
    }

    .gallery-item {
        border-radius: 5px;
        overflow: hidden;
        cursor: pointer;
        border: 2px solid transparent;
        transition: border 0.3s;
    }

    .gallery-item.active {
        border-color: #3498db;
    }

    .gallery-item img {
        width: 100%;
        height: 80px;
        object-fit: cover;
    }

    .current-price {
        font-size: 28px;
        font-weight: bold;
        color: #e74c3c;
    }

    .original-price {
        font-size: 18px;
        color: #777;
        text-decoration: line-through;
        margin-left: 10px;
    }

    .discount-badge {
        background-color: #e74c3c;
        color: white;
        padding: 3px 8px;
        border-radius: 4px;
        font-size: 14px;
        margin-left: 10px;
    }

    .quantity-controls {
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .quantity-btn {
        width: 35px;
        height: 35px;
        border: 1px solid #ddd;
        background: white;
        border-radius: 4px;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
    }

    .quantity-input {
        width: 60px;
        padding: 8px;
        border: 1px solid #ddd;
        border-radius: 4px;
        text-align: center;
        background: #f9f9f9;
        font-weight: bold;
    }

    .product-tabs {
        background: white;
        border-radius: 10px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        margin-bottom: 30px;
    }

    .tab-headers {
        display: flex;
        border-bottom: 1px solid #eee;
    }

    .tab-header {
        padding: 15px 25px;
        cursor: pointer;
        font-weight: 500;
        border-bottom: 3px solid transparent;
    }

    .tab-header.active {
        border-bottom-color: #3498db;
        color: #3498db;
    }

    .tab-content {
        padding: 25px;
    }

    .tab-pane {
        display: none;
    }

    .tab-pane.active {
        display: block;
    }

    .product-card {
        background: white;
        border-radius: 10px;
        overflow: hidden;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        transition: transform 0.3s;
        text-decoration: none;
        color: inherit;
        height: 100%;
    }

    .product-card:hover {
        transform: translateY(-5px);
        text-decoration: none;
        color: inherit;
    }

    .product-image {
        height: 200px;
        overflow: hidden;
    }

    .product-image img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform 0.5s;
    }

    .product-card:hover .product-image img {
        transform: scale(1.05);
    }

    .breadcrumb {
        background: none;
        padding: 15px 0;
        margin-bottom: 20px;
    }

    .breadcrumb a {
        color: #3498db;
        text-decoration: none;
    }

    .stock-status.in-stock {
        color: #28a745;
    }

    .stock-status.low-stock {
        color: #ffc107;
    }

    .stock-status.out-of-stock {
        color: #dc3545;
    }

    /* Styles pour les avis */
    .review-item {
        border-bottom: 1px solid #eee;
        padding: 20px 0;
    }

    .review-item:last-child {
        border-bottom: none;
    }

    .review-header {
        display: flex;
        justify-content: between;
        align-items: center;
        margin-bottom: 10px;
    }

    .review-author {
        font-weight: bold;
        color: #333;
    }

    .review-date {
        color: #777;
        font-size: 0.9em;
    }

    .review-rating {
        color: #ffc107;
        margin: 5px 0;
    }

    .review-comment {
        color: #555;
        line-height: 1.5;
    }

    .rating-stars {
        display: flex;
        gap: 5px;
        margin: 10px 0;
    }

    .star {
        font-size: 1.2em;
        cursor: pointer;
        color: #ddd;
        transition: color 0.2s;
    }

    .star.active,
    .star:hover {
        color: #ffc107;
    }

    .rating-summary {
        background: #f8f9fa;
        padding: 20px;
        border-radius: 10px;
        margin-bottom: 20px;
    }

    .average-rating {
        font-size: 2.5em;
        font-weight: bold;
        color: #333;
    }

    @media (max-width: 768px) {
        .image-gallery {
            grid-template-columns: repeat(2, 1fr);
        }
        
        .main-image img {
            height: 300px;
        }
    }

    .pulse {
        animation: pulse 0.5s ease-in-out;
    }
    
    @keyframes pulse {
        0% { transform: scale(1); }
        50% { transform: scale(1.3); }
        100% { transform: scale(1); }
    }
</style>

<div class="breadcrumb">
    <a href="index.php" class="text-decoration-none">Accueil</a> 
    <span class="mx-2">/</span>
    <a href="boutique.php" class="text-decoration-none">Boutique</a>
    <span class="mx-2">/</span>
    <span class="text-muted"><?php echo htmlspecialchars($product['titre']); ?></span>
</div>

<div class="row product-detail">
    <div class="col-md-6">
        <div class="product-images">
            <div class="main-image">
                <?php
                $main_image = $product['image_principale'] ?: 'https://via.placeholder.com/600x500/3498db/ffffff?text=Image+Non+Disponible';
                echo '<img id="main-product-image" src="' . htmlspecialchars($main_image) . '" alt="' . htmlspecialchars($product['titre']) . '" class="img-fluid">';
                ?>
            </div>
            <?php if (!empty($images)): ?>
            <div class="image-gallery">
                <?php foreach ($images as $index => $image): ?>
                <div class="gallery-item <?php echo $index === 0 ? 'active' : ''; ?>" 
                     data-image="admin/<?php echo htmlspecialchars($image['chemin_image']); ?>">
                    <img src="admin/<?php echo htmlspecialchars($image['chemin_image']); ?>" 
                         alt="Image <?php echo $index + 1; ?> du produit" class="img-fluid">
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="col-md-6">
        <div class="product-info">
            <h1 class="h2 mb-3"><?php echo htmlspecialchars($product['titre']); ?></h1>
            
            <div class="d-flex align-items-center mb-3">
                <div class="rating text-warning me-3">
                    <?php
                    // Afficher les étoiles selon la note moyenne
                    $full_stars = floor($note_moyenne);
                    $half_star = ($note_moyenne - $full_stars) >= 0.5;
                    $empty_stars = 5 - $full_stars - ($half_star ? 1 : 0);
                    
                    for ($i = 0; $i < $full_stars; $i++) {
                        echo '<i class="fas fa-star"></i>';
                    }
                    if ($half_star) {
                        echo '<i class="fas fa-star-half-alt"></i>';
                    }
                    for ($i = 0; $i < $empty_stars; $i++) {
                        echo '<i class="far fa-star"></i>';
                    }
                    ?>
                    <span class="text-muted ms-1">(<?php echo $note_moyenne; ?>/5 - <?php echo $nombre_avis; ?> avis)</span>
                </div>
                <div class="sku text-muted">Réf: <?php echo htmlspecialchars($product['reference']); ?></div>
            </div>

            <div class="price-section bg-light p-3 rounded mb-3">
                <span class="current-price">
                    <?php 
                    $price = $product['est_en_promotion'] && $product['prix_promotion'] 
                        ? $product['prix_promotion'] 
                        : $product['prix'];
                    echo number_format($price, 0, ',', ' ') . ' FCFA';
                    ?>
                </span>
                <?php if ($product['est_en_promotion'] && $product['prix_promotion']): ?>
                <span class="original-price">
                    <?php echo number_format($product['prix'], 0, ',', ' ') . ' FCFA'; ?>
                </span>
                <span class="discount-badge">
                    -<?php 
                    $discount = (($product['prix'] - $product['prix_promotion']) / $product['prix']) * 100;
                    echo round($discount) . '%';
                    ?>
                </span>
                <?php endif; ?>
            </div>

            <div class="stock-status mb-3 <?php 
                echo $product['quantite_stock'] > 10 ? 'in-stock' : 
                     ($product['quantite_stock'] > 0 ? 'low-stock' : 'out-of-stock'); 
            ?>">
                <i class="fas <?php 
                    echo $product['quantite_stock'] > 10 ? 'fa-check-circle' : 
                         ($product['quantite_stock'] > 0 ? 'fa-exclamation-triangle' : 'fa-times-circle'); 
                ?> me-2"></i>
                <?php
                if ($product['quantite_stock'] > 10) {
                    echo 'En stock (' . $product['quantite_stock'] . ' disponibles)';
                } elseif ($product['quantite_stock'] > 0) {
                    echo 'Stock limité (' . $product['quantite_stock'] . ' disponibles)';
                } else {
                    echo 'Rupture de stock';
                }
                ?>
            </div>

            <div class="product-description mb-4">
                <p class="text-muted"><?php echo nl2br(htmlspecialchars($product['description'])); ?></p>
            </div>

            <?php if ($product['quantite_stock'] > 0): ?>
            <div class="add-to-cart-container">
                <div class="quantity-selector mb-4">
                    <label for="quantity" class="form-label fw-bold">Quantité</label>
                    <div class="quantity-controls">
                        <button type="button" class="quantity-btn minus" onclick="decreaseQuantity()">-</button>
                        <input type="number" id="quantity" name="quantity" class="quantity-input" value="1" 
                               min="1" max="<?php echo $product['quantite_stock']; ?>" readonly>
                        <button type="button" class="quantity-btn plus" onclick="increaseQuantity()">+</button>
                    </div>
                    <div class="form-text">
                        <?php echo $product['quantite_stock']; ?> disponibles
                    </div>
                </div>

                <div class="action-buttons d-grid gap-2 d-md-flex">
                    <button type="button" class="btn btn-primary btn-lg flex-fill" id="add-to-cart-btn" onclick="addToCart()">
                        <i class="fas fa-shopping-cart me-2"></i>
                        <span class="btn-text">Ajouter au panier</span>
                    </button>
                    <button type="button" class="btn btn-outline-secondary btn-lg" id="add-to-wishlist">
                        <i class="fas fa-heart me-2"></i> Favoris
                    </button>
                </div>
                
                <div id="cart-message" class="alert mt-3" style="display: none;"></div>
            </div>
            <?php else: ?>
            <div class="out-of-stock-message text-center p-4 bg-light rounded">
                <button class="btn btn-secondary btn-lg w-100 mb-3" disabled>
                    <i class="fas fa-times me-2"></i> Produit indisponible
                </button>
                <p class="text-muted mb-3">Ce produit est actuellement en rupture de stock.</p>
                <button type="button" class="btn btn-outline-primary" id="notify-me">
                    <i class="fas fa-bell me-2"></i> Me prévenir quand disponible
                </button>
            </div>
            <?php endif; ?>

            <div class="product-features mt-4">
                <div class="row">
                    <div class="col-6">
                        <ul class="list-unstyled">
                            <li class="mb-2"><i class="fas fa-check text-success me-2"></i> Marque: <strong><?php echo htmlspecialchars($product['marque']); ?></strong></li>
                            <li class="mb-2"><i class="fas fa-check text-success me-2"></i> Catégorie: <strong><?php echo htmlspecialchars($product['categorie_nom'] ?? 'Non catégorisé'); ?></strong></li>
                        </ul>
                    </div>
                    <div class="col-6">
                        <ul class="list-unstyled">
                            <li class="mb-2"><i class="fas fa-check text-success me-2"></i> Livraison gratuite</li>
                            <li class="mb-2"><i class="fas fa-check text-success me-2"></i> Retour sous 30 jours</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="product-tabs">
    <div class="tab-headers">
        <div class="tab-header active" data-tab="description">Description</div>
        <div class="tab-header" data-tab="specifications">Spécifications</div>
        <div class="tab-header" data-tab="reviews">Avis (<?php echo $nombre_avis; ?>)</div>
    </div>
    <div class="tab-content">
        <div class="tab-pane active" id="description">
            <h4 class="mb-3">Description détaillée</h4>
            <div class="description-content">
                <?php echo nl2br(htmlspecialchars($product['description'])); ?>
            </div>
        </div>
        
        <div class="tab-pane" id="specifications">
            <h4 class="mb-3">Caractéristiques techniques</h4>
            <div class="table-responsive">
                <table class="table table-striped">
                    <tbody>
                        <tr>
                            <td class="fw-bold" style="width: 30%;">Référence</td>
                            <td><?php echo htmlspecialchars($product['reference']); ?></td>
                        </tr>
                        <tr>
                            <td class="fw-bold">Marque</td>
                            <td><?php echo htmlspecialchars($product['marque']); ?></td>
                        </tr>
                        <tr>
                            <td class="fw-bold">Catégorie</td>
                            <td><?php echo htmlspecialchars($product['categorie_nom'] ?? 'Non catégorisé'); ?></td>
                        </tr>
                        <tr>
                            <td class="fw-bold">Prix original</td>
                            <td><?php echo number_format($product['prix'], 0, ',', ' ') . ' FCFA'; ?></td>
                        </tr>
                        <?php if ($product['est_en_promotion'] && $product['prix_promotion']): ?>
                        <tr>
                            <td class="fw-bold">Prix promotionnel</td>
                            <td class="text-success"><?php echo number_format($product['prix_promotion'], 0, ',', ' ') . ' FCFA'; ?></td>
                        </tr>
                        <?php endif; ?>
                        <tr>
                            <td class="fw-bold">Stock disponible</td>
                            <td><?php echo $product['quantite_stock']; ?> unités</td>
                        </tr>
                        <tr>
                            <td class="fw-bold">Statut</td>
                            <td>
                                <span class="badge <?php echo $product['est_actif'] ? 'bg-success' : 'bg-secondary'; ?>">
                                    <?php echo $product['est_actif'] ? 'Actif' : 'Inactif'; ?>
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <td class="fw-bold">Date d'ajout</td>
                            <td><?php echo date('d/m/Y à H:i', strtotime($product['date_creation'])); ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        
        <div class="tab-pane" id="reviews">
            <h4 class="mb-3">Avis clients (<?php echo $nombre_avis; ?>)</h4>
            
            <?php if ($nombre_avis > 0): ?>
                <div class="rating-summary mb-4">
                    <div class="row align-items-center">
                        <div class="col-md-4 text-center">
                            <div class="average-rating text-primary"><?php echo $note_moyenne; ?>/5</div>
                            <div class="rating text-warning mb-2">
                                <?php
                                for ($i = 0; $i < 5; $i++) {
                                    if ($i < floor($note_moyenne)) {
                                        echo '<i class="fas fa-star"></i>';
                                    } elseif ($i == floor($note_moyenne) && $note_moyenne - floor($note_moyenne) >= 0.5) {
                                        echo '<i class="fas fa-star-half-alt"></i>';
                                    } else {
                                        echo '<i class="far fa-star"></i>';
                                    }
                                }
                                ?>
                            </div>
                            <p class="text-muted">Basé sur <?php echo $nombre_avis; ?> avis</p>
                        </div>
                        <div class="col-md-8">
                            <!-- Vous pouvez ajouter une répartition des notes ici si vous le souhaitez -->
                        </div>
                    </div>
                </div>
                
                <div class="reviews-list">
                    <?php foreach ($avis as $a): ?>
                        <div class="review-item">
                            <div class="review-header">
                                <div class="review-author"><?php echo htmlspecialchars($a['prenom'] . ' ' . $a['nom']); ?></div>
                                <div class="review-date text-muted">
                                    <?php echo date('d/m/Y', strtotime($a['date_creation'])); ?>
                                </div>
                            </div>
                            <div class="review-rating">
                                <?php
                                for ($i = 0; $i < 5; $i++) {
                                    if ($i < $a['note']) {
                                        echo '<i class="fas fa-star text-warning"></i>';
                                    } else {
                                        echo '<i class="far fa-star text-warning"></i>';
                                    }
                                }
                                ?>
                                <span class="ms-2"><?php echo $a['note']; ?>/5</span>
                            </div>
                            <div class="review-comment">
                                <?php echo nl2br(htmlspecialchars($a['commentaire'])); ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="text-center py-4">
                    <i class="fas fa-comments fa-3x text-muted mb-3"></i>
                    <h5 class="text-muted">Aucun avis pour le moment</h5>
                    <p class="text-muted">Soyez le premier à donner votre avis sur ce produit !</p>
                </div>
            <?php endif; ?>

            <!-- Formulaire d'ajout d'avis -->
            <div class="add-review mt-5">
                <h5>Donnez votre avis</h5>
                <?php if (isset($_SESSION['utilisateur_id'])): ?>
                    <?php if (!$user_has_review): ?>
                        <form id="review-form">
                            <input type="hidden" name="article_id" value="<?php echo $product_id; ?>">
                            
                            <div class="mb-3">
                                <label class="form-label">Note</label>
                                <div class="rating-stars" id="rating-stars">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <span class="star" data-rating="<?php echo $i; ?>">
                                            <i class="far fa-star"></i>
                                        </span>
                                    <?php endfor; ?>
                                </div>
                                <input type="hidden" name="note" id="rating-value" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="commentaire" class="form-label">Commentaire</label>
                                <textarea class="form-control" id="commentaire" name="commentaire" rows="4" 
                                          placeholder="Partagez votre expérience avec ce produit..." required></textarea>
                            </div>
                            
                            <button type="submit" class="btn btn-primary" id="submit-review">
                                <i class="fas fa-paper-plane me-2"></i>Publier mon avis
                            </button>
                        </form>
                        <div id="review-message" class="alert mt-3" style="display: none;"></div>
                    <?php else: ?>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            Vous avez déjà donné votre avis sur ce produit.
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        Vous devez être <a href="connexion.php" class="alert-link">connecté</a> pour donner votre avis.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php if (!empty($related_products)): ?>
<div class="related-products mt-5">
    <h3 class="mb-4">Produits similaires</h3>
    <div class="row">
        <?php foreach ($related_products as $related): ?>
        <div class="col-lg-3 col-md-6 mb-4">
            <a href="produit.php?id=<?php echo $related['id']; ?>" class="product-card text-decoration-none text-dark d-block">
                <div class="product-image">
                    <img src="<?php echo htmlspecialchars($related['image_principale'] ?: 'https://via.placeholder.com/300x200/3498db/ffffff?text=Image+Non+Disponible'); ?>" 
                         alt="<?php echo htmlspecialchars($related['titre']); ?>" class="img-fluid">
                </div>
                <div class="p-3">
                    <h6 class="product-title mb-2"><?php echo htmlspecialchars($related['titre']); ?></h6>
                    <div class="price fw-bold text-success">
                        <?php 
                        $related_price = $related['est_en_promotion'] && $related['prix_promotion'] 
                            ? $related['prix_promotion'] 
                            : $related['prix'];
                        echo number_format($related_price, 0, ',', ' ') . ' FCFA';
                        ?>
                    </div>
                    <?php if ($related['est_en_promotion'] && $related['prix_promotion']): ?>
                    <div class="original-price text-muted small text-decoration-line-through">
                        <?php echo number_format($related['prix'], 0, ',', ' ') . ' FCFA'; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </a>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<script>
    // Gestion de la galerie d'images
    document.querySelectorAll('.gallery-item').forEach(item => {
        item.addEventListener('click', function() {
            const mainImage = document.getElementById('main-product-image');
            mainImage.src = this.getAttribute('data-image');
            
            document.querySelectorAll('.gallery-item').forEach(i => {
                i.classList.remove('active');
            });
            this.classList.add('active');
        });
    });

    // Gestion des onglets
    document.querySelectorAll('.tab-header').forEach(tab => {
        tab.addEventListener('click', function() {
            const tabId = this.getAttribute('data-tab');
            
            document.querySelectorAll('.tab-header').forEach(t => {
                t.classList.remove('active');
            });
            this.classList.add('active');
            
            document.querySelectorAll('.tab-pane').forEach(pane => {
                pane.classList.remove('active');
            });
            document.getElementById(tabId).classList.add('active');
        });
    });

    // Gestion des quantités
    function increaseQuantity() {
        const quantityInput = document.getElementById('quantity');
        const max = parseInt(quantityInput.getAttribute('max'));
        let currentValue = parseInt(quantityInput.value);
        
        if (currentValue < max) {
            quantityInput.value = currentValue + 1;
        }
    }

    function decreaseQuantity() {
        const quantityInput = document.getElementById('quantity');
        let currentValue = parseInt(quantityInput.value);
        
        if (currentValue > 1) {
            quantityInput.value = currentValue - 1;
        }
    }

    // Fonction pour ajouter au panier
    function addToCart() {
        const productId = <?php echo $product_id; ?>;
        const quantity = parseInt(document.getElementById('quantity').value);
        const submitBtn = document.getElementById('add-to-cart-btn');
        const messageDiv = document.getElementById('cart-message');
        
        const originalText = submitBtn.querySelector('.btn-text').textContent;
        submitBtn.querySelector('.btn-text').textContent = 'Ajout en cours...';
        submitBtn.disabled = true;
        
        fetch('ajax/add_to_cart.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'product_id=' + productId + '&quantity=' + quantity
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Erreur réseau: ' + response.status);
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                showCartMessage('✅ ' + data.message, 'success');
                updateCartCounter(data.cart_count);
            } else {
                showCartMessage('❌ ' + data.message, 'danger');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showCartMessage('❌ Erreur lors de l\'ajout au panier. Veuillez réessayer.', 'danger');
        })
        .finally(() => {
            submitBtn.querySelector('.btn-text').textContent = originalText;
            submitBtn.disabled = false;
        });
    }

    // Gestion du système de notation
    const stars = document.querySelectorAll('.star');
    let currentRating = 0;

    stars.forEach(star => {
        star.addEventListener('click', function() {
            const rating = parseInt(this.getAttribute('data-rating'));
            currentRating = rating;
            document.getElementById('rating-value').value = rating;
            
            stars.forEach(s => {
                const starRating = parseInt(s.getAttribute('data-rating'));
                const icon = s.querySelector('i');
                
                if (starRating <= rating) {
                    icon.className = 'fas fa-star';
                    s.classList.add('active');
                } else {
                    icon.className = 'far fa-star';
                    s.classList.remove('active');
                }
            });
        });
        
        star.addEventListener('mouseover', function() {
            const rating = parseInt(this.getAttribute('data-rating'));
            
            stars.forEach(s => {
                const starRating = parseInt(s.getAttribute('data-rating'));
                const icon = s.querySelector('i');
                
                if (starRating <= rating) {
                    icon.className = 'fas fa-star';
                } else {
                    icon.className = 'far fa-star';
                }
            });
        });
        
        star.addEventListener('mouseout', function() {
            stars.forEach(s => {
                const starRating = parseInt(s.getAttribute('data-rating'));
                const icon = s.querySelector('i');
                
                if (starRating <= currentRating) {
                    icon.className = 'fas fa-star';
                s.classList.add('active');
                } else {
                    icon.className = 'far fa-star';
                    s.classList.remove('active');
                }
            });
        });
    });

    // Soumission du formulaire d'avis
    document.getElementById('review-form')?.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const form = this;
        const submitBtn = document.getElementById('submit-review');
        const messageDiv = document.getElementById('review-message');
        
        if (!currentRating) {
            showReviewMessage('Veuillez sélectionner une note', 'warning');
            return;
        }
        
        const originalText = submitBtn.textContent;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Publication...';
        submitBtn.disabled = true;
        
        const formData = new FormData(form);
        
        fetch('ajax/ajouter_avis.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showReviewMessage('✅ ' + data.message, 'success');
                form.reset();
                currentRating = 0;
                
                // Réinitialiser les étoiles
                stars.forEach(star => {
                    const icon = star.querySelector('i');
                    icon.className = 'far fa-star';
                    star.classList.remove('active');
                });
                
                // Recharger la page après 2 secondes pour voir le nouvel avis
                setTimeout(() => {
                    window.location.reload();
                }, 2000);
            } else {
                showReviewMessage('❌ ' + data.message, 'danger');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showReviewMessage('❌ Erreur lors de la publication de l\'avis', 'danger');
        })
        .finally(() => {
            submitBtn.innerHTML = originalText;
            submitBtn.disabled = false;
        });
    });

    function showReviewMessage(text, type) {
        const messageDiv = document.getElementById('review-message');
        messageDiv.textContent = text;
        messageDiv.className = `alert alert-${type}`;
        messageDiv.style.display = 'block';
        
        setTimeout(() => {
            messageDiv.style.display = 'none';
        }, 5000);
    }

    function showCartMessage(text, type) {
        const messageDiv = document.getElementById('cart-message');
        messageDiv.textContent = text;
        messageDiv.className = `alert alert-${type}`;
        messageDiv.style.display = 'block';
        
        setTimeout(() => {
            messageDiv.style.display = 'none';
        }, 4000);
    }

    function updateCartCounter(totalItems) {
        const cartBadge = document.getElementById('panier-count');
        if (cartBadge) {
            cartBadge.textContent = totalItems;
            if (totalItems > 0) {
                cartBadge.style.display = 'inline-block';
            } else {
                cartBadge.style.display = 'none';
            }
        }
        
        if (cartBadge && totalItems > 0) {
            cartBadge.classList.add('pulse');
            setTimeout(() => {
                cartBadge.classList.remove('pulse');
            }, 500);
        }
    }

    // Gestion des favoris
    document.getElementById('add-to-wishlist')?.addEventListener('click', function() {
        showCartMessage('❤️ Produit ajouté aux favoris!', 'success');
    });

    // Notification de disponibilité
    document.getElementById('notify-me')?.addEventListener('click', function() {
        showCartMessage('🔔 Nous vous préviendrons quand ce produit sera disponible!', 'success');
    });

    // Animation d'entrée
    document.addEventListener('DOMContentLoaded', function() {
        const elements = document.querySelectorAll('.product-detail, .product-tabs, .related-products');
        elements.forEach((element, index) => {
            element.style.opacity = '0';
            element.style.transform = 'translateY(20px)';
            
            setTimeout(() => {
                element.style.transition = 'all 0.6s ease';
                element.style.opacity = '1';
                element.style.transform = 'translateY(0)';
            }, index * 200);
        });

        const initialTotal = <?php echo $total_panier; ?>;
        updateCartCounter(initialTotal);
    });
</script>

<?php
// Inclure le footer
include 'footer.php';
?>