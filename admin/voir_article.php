<?php
require_once 'config.php';

$article_id = $_GET['id'] ?? 0;

// Récupérer l'article
$sql = "SELECT a.*, c.nom as categorie_nom 
        FROM article a 
        LEFT JOIN categorie_article c ON a.categorie_id = c.id 
        WHERE a.id = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$article_id]);
$article = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$article) {
    die("Article non trouvé");
}

// Récupérer les images supplémentaires
$images = $pdo->prepare("SELECT * FROM image_article WHERE article_id = ? ORDER BY ordre_affichage");
$images->execute([$article_id]);
$images = $images->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($article['titre']) ?> - E-commerce</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-4">
        <a href="liste_articles.php" class="btn btn-secondary mb-3">← Retour à la liste</a>
        
        <div class="row">
            <div class="col-md-6">
                <img src="<?= $article['image_principale'] ?: 'https://via.placeholder.com/500x400/6c757d/ffffff?text=Image+Manquante' ?>" 
                     class="img-fluid rounded" alt="<?= htmlspecialchars($article['titre']) ?>">
                
                <?php if (!empty($images)): ?>
                    <div class="row mt-3">
                        <?php foreach ($images as $image): ?>
                            <div class="col-4">
                                <img src="<?= $image['chemin_image'] ?>" class="img-thumbnail" style="height: 100px; object-fit: cover;">
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="col-md-6">
                <h1><?= htmlspecialchars($article['titre']) ?></h1>
                
                <?php if ($article['est_en_promotion'] && $article['prix_promotion']): ?>
                    <h2 class="text-danger"><?= number_format($article['prix_promotion'], 2, ',', ' ') ?>€</h2>
                    <h4 class="text-muted"><s><?= number_format($article['prix'], 2, ',', ' ') ?>€</s></h4>
                <?php else: ?>
                    <h2><?= number_format($article['prix'], 2, ',', ' ') ?>€</h2>
                <?php endif; ?>
                
                <p><strong>Référence:</strong> <?= htmlspecialchars($article['reference']) ?></p>
                <p><strong>Marque:</strong> <?= htmlspecialchars($article['marque'] ?: 'Non spécifiée') ?></p>
                <p><strong>Catégorie:</strong> <?= htmlspecialchars($article['categorie_nom'] ?: 'Non catégorisé') ?></p>
                <p><strong>Stock:</strong> 
                    <span class="badge <?= $article['quantite_stock'] < 5 ? 'bg-danger' : 'bg-success' ?>">
                        <?= $article['quantite_stock'] ?> unités
                    </span>
                </p>
                <p><strong>Statut:</strong> 
                    <span class="badge <?= $article['est_actif'] ? 'bg-success' : 'bg-secondary' ?>">
                        <?= $article['est_actif'] ? 'Actif' : 'Inactif' ?>
                    </span>
                </p>
                <p><strong>Date de création:</strong> <?= date('d/m/Y H:i', strtotime($article['date_creation'])) ?></p>
                
                <hr>
                
                <h4>Description</h4>
                <p><?= nl2br(htmlspecialchars($article['description'] ?: 'Aucune description disponible')) ?></p>
            </div>
        </div>
    </div>
</body>
</html>