<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

// Vérifier que la requête est POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit;
}

// Vérifier que l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Veuillez vous connecter pour ajouter au panier']);
    exit;
}

// Récupérer les données
$product_id = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
$quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;

// Validation des données
if ($product_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Produit invalide']);
    exit;
}

if ($quantity <= 0) {
    echo json_encode(['success' => false, 'message' => 'Quantité invalide']);
    exit;
}

try {
    // Vérifier que le produit existe et est en stock
    $stmt = $pdo->prepare("
        SELECT id, titre, prix, prix_promotion, est_en_promotion, quantite_stock, image_principale 
        FROM article 
        WHERE id = ? AND est_actif = 1
    ");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch();

    if (!$product) {
        echo json_encode(['success' => false, 'message' => 'Produit non trouvé']);
        exit;
    }

    if ($product['quantite_stock'] < $quantity) {
        echo json_encode(['success' => false, 'message' => 'Stock insuffisant']);
        exit;
    }

    // Calculer le prix (prix normal ou promotion)
    $prix_final = $product['est_en_promotion'] && $product['prix_promotion'] 
        ? $product['prix_promotion'] 
        : $product['prix'];

    // Initialiser le panier s'il n'existe pas
    if (!isset($_SESSION['panier'])) {
        $_SESSION['panier'] = [];
    }

    // Vérifier si le produit est déjà dans le panier
    $product_index = -1;
    foreach ($_SESSION['panier'] as $index => $item) {
        if ($item['product_id'] == $product_id) {
            $product_index = $index;
            break;
        }
    }

    if ($product_index >= 0) {
        // Mettre à jour la quantité
        $new_quantity = $_SESSION['panier'][$product_index]['quantity'] + $quantity;
        
        // Vérifier le stock disponible
        if ($new_quantity > $product['quantite_stock']) {
            echo json_encode(['success' => false, 'message' => 'Quantité demandée dépasse le stock disponible']);
            exit;
        }
        
        $_SESSION['panier'][$product_index]['quantity'] = $new_quantity;
        $_SESSION['panier'][$product_index]['total_price'] = $new_quantity * $prix_final;
    } else {
        // Ajouter le nouveau produit au panier
        $_SESSION['panier'][] = [
            'product_id' => $product_id,
            'titre' => $product['titre'],
            'price' => $prix_final,
            'quantity' => $quantity,
            'total_price' => $quantity * $prix_final,
            'image' => $product['image_principale']
        ];
    }

    // Calculer le nombre total d'articles dans le panier
    $cart_count = 0;
    foreach ($_SESSION['panier'] as $item) {
        $cart_count += $item['quantity'];
    }

    echo json_encode([
        'success' => true,
        'message' => 'Produit ajouté au panier avec succès',
        'cart_count' => $cart_count
    ]);

} catch (Exception $e) {
    error_log("Erreur ajout panier: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erreur lors de l\'ajout au panier']);
}
?>