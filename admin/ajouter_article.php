<?php
require_once 'config.php';
session_start();

// Vérifier si l'utilisateur est connecté et est admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

// Récupérer les catégories depuis la base
$categories = [];
try {
    $stmt = $pdo->query("SELECT id, nom FROM categorie_article WHERE est_actif = 1 ORDER BY nom");
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $error = "Erreur lors du chargement des catégories";
}

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Validation des données
        $reference = trim($_POST['reference'] ?? '');
        $titre = trim($_POST['titre'] ?? '');
        $prix = floatval($_POST['prix'] ?? 0);
        $quantite_stock = intval($_POST['quantite_stock'] ?? 0);
        
        // Vérifications de base
        if (empty($reference)) {
            $error = "La référence est obligatoire";
        } elseif (empty($titre)) {
            $error = "Le titre est obligatoire";
        } elseif ($prix <= 0) {
            $error = "Le prix doit être supérieur à 0";
        } else {
            // Vérifier si la référence existe déjà
            $stmt = $pdo->prepare("SELECT id FROM article WHERE reference = ?");
            $stmt->execute([$reference]);
            if ($stmt->fetch()) {
                $error = "Cette référence existe déjà";
            } else {
                // Gestion de l'upload de l'image principale
                $image_principale = null;
                if (isset($_FILES['image_principale']) && $_FILES['image_principale']['error'] === UPLOAD_ERR_OK) {
                    $uploadDir = '../uploads/articles/';
                    
                    // Créer le dossier s'il n'existe pas
                    if (!is_dir($uploadDir)) {
                        mkdir($uploadDir, 0755, true);
                    }
                    
                    $fileExtension = strtolower(pathinfo($_FILES['image_principale']['name'], PATHINFO_EXTENSION));
                    $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                    
                    if (!in_array($fileExtension, $allowedExtensions)) {
                        $error = "Format d'image non autorisé. Formats acceptés : JPG, JPEG, PNG, GIF, WEBP";
                    } elseif ($_FILES['image_principale']['size'] > 5 * 1024 * 1024) {
                        $error = "L'image est trop volumineuse (max 5MB)";
                    } else {
                        // Générer un nom de fichier unique
                        $newFileName = uniqid() . '_' . preg_replace('/[^a-zA-Z0-9\.]/', '_', $reference) . '.' . $fileExtension;
                        $uploadFile = $uploadDir . $newFileName;
                        
                        if (move_uploaded_file($_FILES['image_principale']['tmp_name'], $uploadFile)) {
                            $image_principale = 'uploads/articles/' . $newFileName;
                        } else {
                            $error = "Erreur lors de l'upload de l'image principale";
                        }
                    }
                }
                
                // Si aucune erreur avec l'image, procéder à l'insertion
                if (!isset($error)) {
                    // Insertion de l'article
                    $sql = "INSERT INTO article (reference, titre, description, prix, quantite_stock, categorie_id, marque, image_principale, est_actif, est_en_promotion, prix_promotion) 
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                    
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([
                        $reference,
                        $titre,
                        trim($_POST['description'] ?? ''),
                        $prix,
                        $quantite_stock,
                        $_POST['categorie_id'] ? intval($_POST['categorie_id']) : null,
                        trim($_POST['marque'] ?? ''),
                        $image_principale,
                        isset($_POST['est_actif']) ? 1 : 0,
                        isset($_POST['est_en_promotion']) ? 1 : 0,
                        (isset($_POST['est_en_promotion']) && !empty($_POST['prix_promotion'])) ? floatval($_POST['prix_promotion']) : null
                    ]);
                    
                    $article_id = $pdo->lastInsertId();
                    
                    // Gestion des images supplémentaires
                    if (!isset($error) && isset($_FILES['images_supplementaires']) && is_array($_FILES['images_supplementaires']['name'])) {
                        $uploadDir = '../uploads/articles/';
                        
                        foreach ($_FILES['images_supplementaires']['tmp_name'] as $key => $tmp_name) {
                            if ($_FILES['images_supplementaires']['error'][$key] === UPLOAD_ERR_OK) {
                                $fileExtension = strtolower(pathinfo($_FILES['images_supplementaires']['name'][$key], PATHINFO_EXTENSION));
                                $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                                
                                if (in_array($fileExtension, $allowedExtensions) && $_FILES['images_supplementaires']['size'][$key] <= 5 * 1024 * 1024) {
                                    $newFileName = uniqid() . '_' . preg_replace('/[^a-zA-Z0-9\.]/', '_', $reference) . '_' . $key . '.' . $fileExtension;
                                    $uploadFile = $uploadDir . $newFileName;
                                    
                                    if (move_uploaded_file($tmp_name, $uploadFile)) {
                                        $sql_img = "INSERT INTO image_article (article_id, chemin_image, ordre_affichage, est_principale) 
                                                   VALUES (?, ?, ?, ?)";
                                        $stmt_img = $pdo->prepare($sql_img);
                                        $stmt_img->execute([$article_id, 'uploads/articles/' . $newFileName, $key, 0]);
                                    }
                                }
                            }
                        }
                    }
                    
                    $success = "Article ajouté avec succès!";
                    
                    // Réinitialiser le formulaire après succès
                    $_POST = array();
                }
            }
        }
    } catch(PDOException $e) {
        $error = "Erreur lors de l'ajout de l'article : " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ajouter un Article - E-commerce</title>
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
        
        .form-check-input:checked {
            background-color: var(--info);
            border-color: var(--info);
        }
        
        .form-switch .form-check-input {
            width: 3em;
            height: 1.5em;
        }
        
        .required:after {
            content: " *";
            color: var(--danger);
        }
        
        .preview-image {
            max-width: 200px;
            max-height: 200px;
            border-radius: 10px;
            margin-top: 10px;
            display: none;
        }
        
        .feature-badge {
            background: linear-gradient(135deg, var(--success), #2ecc71);
            color: white;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 0.8em;
            margin-left: 10px;
        }
        
        .promo-section {
            background: linear-gradient(135deg, #fff9e6, #fff3cd);
            border-radius: 10px;
            padding: 20px;
            border-left: 4px solid var(--warning);
            margin-top: 15px;
        }
        
        .image-preview-container {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 10px;
        }
        
        .image-preview-small {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 8px;
            border: 2px solid #e9ecef;
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
                    <a class="nav-link active" href="ajouter_article.php">
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
                            <h1 class="display-6 fw-bold mb-2">Ajouter un Nouvel Article</h1>
                            <p class="mb-0">Remplissez le formulaire ci-dessous pour ajouter un nouveau produit à votre boutique</p>
                        </div>
                        <div class="col-md-4 text-end">
                            <a href="liste_articles.php" class="btn btn-light btn-custom">
                                <i class="fas fa-arrow-left me-2"></i>Retour à la liste
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Formulaire -->
                <div class="form-card">
                    <div class="card-header">
                        <h3 class="mb-0"><i class="fas fa-plus-circle me-2"></i>Informations de l'article</h3>
                    </div>
                    <div class="card-body">
                        <form method="POST" enctype="multipart/form-data" id="articleForm">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-4">
                                        <label for="reference" class="form-label required">Référence produit</label>
                                        <input type="text" class="form-control" id="reference" name="reference" 
                                               value="<?= htmlspecialchars($_POST['reference'] ?? '') ?>" required
                                               placeholder="Ex: PROD-001">
                                        <small class="text-muted">Identifiant unique pour le produit</small>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-4">
                                        <label for="categorie_id" class="form-label required">Catégorie</label>
                                        <select class="form-select" id="categorie_id" name="categorie_id" required>
                                            <option value="">Sélectionnez une catégorie</option>
                                            <?php foreach ($categories as $categorie): ?>
                                                <option value="<?= $categorie['id'] ?>" 
                                                    <?= ($_POST['categorie_id'] ?? '') == $categorie['id'] ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($categorie['nom']) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-4">
                                <label for="titre" class="form-label required">Titre de l'article</label>
                                <input type="text" class="form-control" id="titre" name="titre" 
                                       value="<?= htmlspecialchars($_POST['titre'] ?? '') ?>" required
                                       placeholder="Ex: Smartphone Samsung Galaxy S23">
                            </div>
                            
                            <div class="mb-4">
                                <label for="description" class="form-label">Description détaillée</label>
                                <textarea class="form-control" id="description" name="description" rows="4" 
                                          placeholder="Décrivez votre produit en détail..."><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="mb-4">
                                        <label for="prix" class="form-label required">Prix (€)</label>
                                        <input type="number" class="form-control" id="prix" name="prix" 
                                               value="<?= htmlspecialchars($_POST['prix'] ?? '') ?>" min="0.01" step="0.01" required
                                               placeholder="0.00">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-4">
                                        <label for="quantite_stock" class="form-label">Quantité en stock</label>
                                        <input type="number" class="form-control" id="quantite_stock" name="quantite_stock" 
                                               value="<?= htmlspecialchars($_POST['quantite_stock'] ?? 0) ?>" min="0"
                                               placeholder="0">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-4">
                                        <label for="marque" class="form-label">Marque</label>
                                        <input type="text" class="form-control" id="marque" name="marque" 
                                               value="<?= htmlspecialchars($_POST['marque'] ?? '') ?>"
                                               placeholder="Ex: Samsung">
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Section Images -->
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-4">
                                        <label for="image_principale" class="form-label">Image principale</label>
                                        <input type="file" class="form-control" id="image_principale" name="image_principale" 
                                               accept="image/jpeg, image/png, image/gif, image/webp" onchange="previewImage(this, 'previewMain')">
                                        <img id="previewMain" class="preview-image" alt="Aperçu image principale">
                                        <div class="form-text">Formats acceptés : JPG, PNG, GIF, WEBP (max 5MB)</div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-4">
                                        <label for="images_supplementaires" class="form-label">Images supplémentaires</label>
                                        <input type="file" class="form-control" id="images_supplementaires" 
                                               name="images_supplementaires[]" multiple accept="image/*"
                                               onchange="previewMultipleImages(this, 'previewMultiple')">
                                        <div id="previewMultiple" class="image-preview-container mt-2"></div>
                                        <div class="form-text">Vous pouvez sélectionner plusieurs images (max 5MB par image)</div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-4">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" id="est_actif" name="est_actif" 
                                                <?= isset($_POST['est_actif']) ? 'checked' : 'checked' ?>>
                                            <label class="form-check-label fw-bold" for="est_actif">
                                                Article actif
                                                <span class="feature-badge">Visible</span>
                                            </label>
                                        </div>
                                        <small class="text-muted">Rendre l'article visible sur le site</small>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-4">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" id="est_en_promotion" name="est_en_promotion"
                                                <?= isset($_POST['est_en_promotion']) ? 'checked' : '' ?>>
                                            <label class="form-check-label fw-bold" for="est_en_promotion">
                                                En promotion
                                                <span class="feature-badge" style="background: linear-gradient(135deg, var(--warning), #e67e22);">Promo</span>
                                            </label>
                                        </div>
                                        <small class="text-muted">Activer les promotions pour cet article</small>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="promo-section" id="promotionSection" style="display: none;">
                                <h6 class="fw-bold text-warning mb-3">
                                    <i class="fas fa-tag me-2"></i>Informations promotionnelles
                                </h6>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="prix_promotion" class="form-label">Prix promotionnel (€)</label>
                                            <input type="number" class="form-control" id="prix_promotion" name="prix_promotion" 
                                                   value="<?= htmlspecialchars($_POST['prix_promotion'] ?? '') ?>" min="0.01" step="0.01"
                                                   placeholder="0.00">
                                            <small class="text-muted">Prix spécial pendant la promotion</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="d-flex justify-content-between align-items-center mt-5 pt-4 border-top">
                                <button type="reset" class="btn btn-secondary-custom btn-custom">
                                    <i class="fas fa-undo me-2"></i>Réinitialiser
                                </button>
                                <button type="submit" class="btn btn-primary-custom btn-custom">
                                    <i class="fas fa-plus-circle me-2"></i>Ajouter l'article
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Aide et conseils -->
                <div class="form-card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-lightbulb me-2"></i>Conseils pour un bon référencement</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="d-flex align-items-start mb-3">
                                    <i class="fas fa-image text-info me-3 mt-1"></i>
                                    <div>
                                        <h6 class="fw-bold">Images de qualité</h6>
                                        <p class="text-muted small">Utilisez des images haute résolution sous format JPEG ou PNG</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="d-flex align-items-start mb-3">
                                    <i class="fas fa-file-alt text-success me-3 mt-1"></i>
                                    <div>
                                        <h6 class="fw-bold">Description détaillée</h6>
                                        <p class="text-muted small">Une description complète améliore le référencement</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="d-flex align-items-start mb-3">
                                    <i class="fas fa-tags text-warning me-3 mt-1"></i>
                                    <div>
                                        <h6 class="fw-bold">Catégorisation</h6>
                                        <p class="text-muted small">Choisissez la catégorie la plus appropriée</p>
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
        // Gestion de l'affichage de la section promotion
        document.getElementById('est_en_promotion').addEventListener('change', function() {
            const promotionSection = document.getElementById('promotionSection');
            if (this.checked) {
                promotionSection.style.display = 'block';
                promotionSection.style.animation = 'fadeIn 0.5s ease-in';
            } else {
                promotionSection.style.display = 'none';
            }
        });

        // Déclencher l'événement au chargement pour l'état initial
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('est_en_promotion').dispatchEvent(new Event('change'));
        });

        // Prévisualisation d'image unique
        function previewImage(input, previewId) {
            const preview = document.getElementById(previewId);
            const file = input.files[0];
            
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.src = e.target.result;
                    preview.style.display = 'block';
                }
                reader.readAsDataURL(file);
            } else {
                preview.style.display = 'none';
            }
        }

        // Prévisualisation d'images multiples
        function previewMultipleImages(input, previewContainerId) {
            const previewContainer = document.getElementById(previewContainerId);
            previewContainer.innerHTML = '';
            
            if (input.files) {
                Array.from(input.files).forEach(file => {
                    const reader = new FileReader();
                    const imgContainer = document.createElement('div');
                    imgContainer.className = 'position-relative';
                    
                    const img = document.createElement('img');
                    img.className = 'image-preview-small';
                    
                    reader.onload = function(e) {
                        img.src = e.target.result;
                    }
                    reader.readAsDataURL(file);
                    
                    imgContainer.appendChild(img);
                    previewContainer.appendChild(imgContainer);
                });
            }
        }

        // Validation du formulaire
        document.getElementById('articleForm').addEventListener('submit', function(e) {
            const prix = document.getElementById('prix').value;
            const prixPromo = document.getElementById('prix_promotion').value;
            const estEnPromo = document.getElementById('est_en_promotion').checked;
            
            if (estEnPromo) {
                if (!prixPromo) {
                    alert('Veuillez saisir un prix promotionnel');
                    e.preventDefault();
                    return false;
                }
                
                if (parseFloat(prixPromo) >= parseFloat(prix)) {
                    alert('Le prix promotionnel doit être inférieur au prix normal');
                    e.preventDefault();
                    return false;
                }
                
                if (parseFloat(prixPromo) <= 0) {
                    alert('Le prix promotionnel doit être supérieur à 0');
                    e.preventDefault();
                    return false;
                }
            }
            
            return true;
        });

        // CSS pour l'animation fadeIn
        const style = document.createElement('style');
        style.textContent = `
            @keyframes fadeIn {
                from { opacity: 0; transform: translateY(-10px); }
                to { opacity: 1; transform: translateY(0); }
            }
        `;
        document.head.appendChild(style);
    </script>
</body>
</html>