<?php
session_start();
require_once 'config/database.php';

// Initialiser le panier s'il n'existe pas
if (!isset($_SESSION['panier'])) {
    $_SESSION['panier'] = [];
}

// CORRECTION : Gérer la suppression d'un article AVANT d'envoyer du contenu
if (isset($_GET['remove'])) {
    $product_id = (int)$_GET['remove'];
    foreach ($_SESSION['panier'] as $index => $item) {
        if ($item['product_id'] == $product_id) {
            unset($_SESSION['panier'][$index]);
            $_SESSION['panier'] = array_values($_SESSION['panier']); // Réindexer
            break;
        }
    }
    header('Location: panier.php');
    exit;
}

// CORRECTION : Gérer la mise à jour des quantités AVANT d'envoyer du contenu
if (isset($_POST['update_quantities'])) {
    foreach ($_POST['quantities'] as $product_id => $quantity) {
        $quantity = (int)$quantity;
        $product_id = (int)$product_id;
        
        if ($quantity <= 0) {
            // Supprimer l'article si quantité = 0
            foreach ($_SESSION['panier'] as $index => $item) {
                if ($item['product_id'] == $product_id) {
                    unset($_SESSION['panier'][$index]);
                    break;
                }
            }
        } else {
            // Mettre à jour la quantité
            foreach ($_SESSION['panier'] as $index => &$item) {
                if ($item['product_id'] == $product_id) {
                    // Vérifier le stock disponible
                    $stmt = $pdo->prepare("SELECT quantite_stock FROM article WHERE id = ?");
                    $stmt->execute([$product_id]);
                    $stock = $stmt->fetch()['quantite_stock'];
                    
                    if ($quantity <= $stock) {
                        $item['quantity'] = $quantity;
                        $item['total_price'] = $quantity * $item['price'];
                    }
                    break;
                }
            }
        }
    }
    $_SESSION['panier'] = array_values($_SESSION['panier']); // Réindexer
    
    // CORRECTION : Redirection après mise à jour pour éviter la soumission multiple
    header('Location: panier.php');
    exit;
}

// Calculer le total du panier
$total_panier = 0;
foreach ($_SESSION['panier'] as $item) {
    $total_panier += $item['total_price'];
}

// CORRECTION : Maintenant on peut inclure le header après avoir traité toutes les redirections
$page_title = "Mon Panier";
include 'header.php';
?>

<div class="container mt-4">
    <h2>Mon Panier</h2>
    
    <?php if (empty($_SESSION['panier'])): ?>
        <div class="alert alert-info text-center">
            <i class="fas fa-shopping-cart fa-3x mb-3"></i>
            <h4>Votre panier est vide</h4>
            <p>Découvrez nos produits et ajoutez-les à votre panier !</p>
            <a href="boutique.php" class="btn btn-primary">Voir la boutique</a>
        </div>
    <?php else: ?>
        <form method="POST" action="panier.php">
            <div class="row">
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">Articles dans le panier</h5>
                        </div>
                        <div class="card-body">
                            <?php foreach ($_SESSION['panier'] as $item): ?>
                                <div class="row align-items-center mb-3 pb-3 border-bottom">
                                    <div class="col-md-2">
                                        <img src="<?= htmlspecialchars($item['image'] ?? 'images/default-product.jpg') ?>" 
                                             class="img-fluid rounded" 
                                             alt="<?= htmlspecialchars($item['titre']) ?>"
                                             style="height: 80px; object-fit: cover;">
                                    </div>
                                    <div class="col-md-4">
                                        <h6 class="mb-0"><?= htmlspecialchars($item['titre']) ?></h6>
                                    </div>
                                    <div class="col-md-2">
                                        <span class="h6"><?= number_format($item['price'], 2, ',', ' ') ?> FCFA</span>
                                    </div>
                                    <div class="col-md-2">
                                        <input type="number" 
                                               name="quantities[<?= $item['product_id'] ?>]" 
                                               value="<?= $item['quantity'] ?>" 
                                               min="0" 
                                               max="10"
                                               class="form-control form-control-sm">
                                    </div>
                                    <div class="col-md-2">
                                        <span class="h6"><?= number_format($item['total_price'], 2, ',', ' ') ?> FCFA</span>
                                        <a href="panier.php?remove=<?= $item['product_id'] ?>" 
                                           class="btn btn-sm btn-outline-danger mt-1"
                                           onclick="return confirm('Supprimer cet article ?')">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="card-footer">
                            <button type="submit" name="update_quantities" class="btn btn-outline-primary">
                                <i class="fas fa-sync-alt"></i> Mettre à jour le panier
                            </button>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">Récapitulatif</h5>
                        </div>
                        <div class="card-body">
                            <div class="d-flex justify-content-between mb-2">
                                <span>Sous-total:</span>
                                <span><?= number_format($total_panier, 2, ',', ' ') ?> FCFA</span>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span>Frais de livraison:</span>
                                <span>À calculer</span>
                            </div>
                            <hr>
                            <div class="d-flex justify-content-between mb-3">
                                <strong>Total:</strong>
                                <strong><?= number_format($total_panier, 2, ',', ' ') ?> FCFA</strong>
                            </div>
                            
                            <a href="checkout.php" class="btn btn-primary w-100">
                                <i class="fas fa-credit-card"></i> Commander
                            </a>
                            <a href="boutique.php" class="btn btn-outline-secondary w-100 mt-2">
                                <i class="fas fa-shopping-bag"></i> Continuer mes achats
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    <?php endif; ?>
</div>

<?php include 'footer.php'; ?>