<?php
require_once 'config.php';
session_start();

// Vérifier si l'utilisateur est connecté et est admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

// Vérifier si l'ID de l'article est passé en paramètre
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: liste_articles.php');
    exit;
}

$article_id = intval($_GET['id']);

// Récupérer les catégories pour le formulaire
$categories = [];
try {
    $stmt = $pdo->query("SELECT id, nom FROM categorie_article WHERE est_actif = 1 ORDER BY nom");
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $error = "Erreur lors du chargement des catégories";
}

// Récupérer les données de l'article
$article = null;
try {
    $stmt = $pdo->prepare("SELECT * FROM article WHERE id = ?");
    $stmt->execute([$article_id]);
    $article = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$article) {
        $error = "Article non trouvé";
        header('Location: liste_articles.php');
        exit;
    }
} catch(PDOException $e) {
    $error = "Erreur lors du chargement de l'article : " . $e->getMessage();
}

// Traitement du formulaire de modification
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Récupération des données du formulaire
    $reference = trim($_POST['reference'] ?? '');
    $titre = trim($_POST['titre'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $prix = floatval($_POST['prix'] ?? 0);
    $quantite_stock = intval($_POST['quantite_stock'] ?? 0);
    $categorie_id = $_POST['categorie_id'] ? intval($_POST['categorie_id']) : null;
    $marque = trim($_POST['marque'] ?? '');
    $est_actif = isset($_POST['est_actif']) ? 1 : 0;
    $est_en_promotion = isset($_POST['est_en_promotion']) ? 1 : 0;
    $prix_promotion = $est_en_promotion ? floatval($_POST['prix_promotion'] ?? 0) : null;
    
    // Validation des données
    $errors = [];
    
    if (empty($reference)) {
        $errors[] = "La référence est obligatoire";
    }
    
    if (empty($titre)) {
        $errors[] = "Le titre est obligatoire";
    }
    
    if ($prix <= 0) {
        $errors[] = "Le prix doit être supérieur à 0";
    }
    
    if ($quantite_stock < 0) {
        $errors[] = "La quantité en stock ne peut pas être négative";
    }
    
    if ($est_en_promotion && $prix_promotion >= $prix) {
        $errors[] = "Le prix promotionnel doit être inférieur au prix normal";
    }
    
    if ($est_en_promotion && $prix_promotion <= 0) {
        $errors[] = "Le prix promotionnel doit être supérieur à 0";
    }
    
    // Vérifier si la référence est unique (sauf pour l'article actuel)
    try {
        $stmt = $pdo->prepare("SELECT id FROM article WHERE reference = ? AND id != ?");
        $stmt->execute([$reference, $article_id]);
        if ($stmt->fetch()) {
            $errors[] = "Cette référence est déjà utilisée par un autre article";
        }
    } catch(PDOException $e) {
        $errors[] = "Erreur lors de la vérification de la référence";
    }
    
    // Gestion de l'upload d'image
    $image_principale = $article['image_principale'];
    if (isset($_FILES['image_principale']) && $_FILES['image_principale']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = '../uploads/articles/';
        
        // Créer le dossier s'il n'existe pas
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        $fileExtension = strtolower(pathinfo($_FILES['image_principale']['name'], PATHINFO_EXTENSION));
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        
        if (!in_array($fileExtension, $allowedExtensions)) {
            $errors[] = "Format d'image non autorisé. Formats acceptés : JPG, JPEG, PNG, GIF, WEBP";
        } elseif ($_FILES['image_principale']['size'] > 5 * 1024 * 1024) { // 5MB max
            $errors[] = "L'image est trop volumineuse (max 5MB)";
        } else {
            // Générer un nom de fichier unique
            $newFileName = uniqid() . '_' . preg_replace('/[^a-zA-Z0-9\.]/', '_', $reference) . '.' . $fileExtension;
            $uploadFile = $uploadDir . $newFileName;
            
            if (move_uploaded_file($_FILES['image_principale']['tmp_name'], $uploadFile)) {
                // Supprimer l'ancienne image si elle existe
                if ($image_principale && file_exists('../' . $image_principale)) {
                    unlink('../' . $image_principale);
                }
                $image_principale = 'uploads/articles/' . $newFileName;
            } else {
                $errors[] = "Erreur lors de l'upload de l'image";
            }
        }
    }
    
    // Si aucune erreur, mettre à jour l'article
    if (empty($errors)) {
        try {
            $sql = "UPDATE article SET 
                    reference = ?, 
                    titre = ?, 
                    description = ?, 
                    prix = ?, 
                    quantite_stock = ?, 
                    categorie_id = ?, 
                    marque = ?, 
                    image_principale = ?, 
                    est_actif = ?, 
                    est_en_promotion = ?, 
                    prix_promotion = ?,
                    date_modification = NOW()
                    WHERE id = ?";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $reference,
                $titre,
                $description,
                $prix,
                $quantite_stock,
                $categorie_id,
                $marque,
                $image_principale,
                $est_actif,
                $est_en_promotion,
                $prix_promotion,
                $article_id
            ]);
            
            $success = "Article modifié avec succès";
            
            // Recharger les données de l'article
            $stmt = $pdo->prepare("SELECT * FROM article WHERE id = ?");
            $stmt->execute([$article_id]);
            $article = $stmt->fetch(PDO::FETCH_ASSOC);
            
        } catch(PDOException $e) {
            $error = "Erreur lors de la modification de l'article : " . $e->getMessage();
        }
    } else {
        $error = implode("<br>", $errors);
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifier l'Article - E-commerce</title>
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
        
        .form-control, .form-select, .form-check-input {
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
        
        .image-preview {
            max-width: 200px;
            max-height: 200px;
            border-radius: 10px;
            object-fit: cover;
            border: 3px solid #e9ecef;
        }
        
        .promo-section {
            background: linear-gradient(135deg, #fff9e6, #fff3cd);
            border-radius: 10px;
            padding: 20px;
            border-left: 4px solid var(--warning);
        }
        
        .form-check-input:checked {
            background-color: var(--info);
            border-color: var(--info);
        }
        
        .required::after {
            content: " *";
            color: var(--danger);
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
                        <?= $error ?>
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
                            <h1 class="display-6 fw-bold mb-2">Modifier l'Article</h1>
                            <p class="mb-0">Modifiez les informations de l'article "<?= htmlspecialchars($article['titre']) ?>"</p>
                        </div>
                        <div class="col-md-4 text-end">
                            <a href="liste_articles.php" class="btn btn-light btn-custom">
                                <i class="fas fa-arrow-left me-2"></i>Retour à la liste
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Formulaire de modification -->
                <div class="form-card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-edit me-2"></i>Modifier l'Article #<?= $article['id'] ?></h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" enctype="multipart/form-data">
                            <div class="row">
                                <div class="col-md-8">
                                    <!-- Informations de base -->
                                    <div class="row mb-4">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="reference" class="form-label required">Référence</label>
                                                <input type="text" class="form-control" id="reference" name="reference" 
                                                       value="<?= htmlspecialchars($article['reference']) ?>" required>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="marque" class="form-label">Marque</label>
                                                <input type="text" class="form-control" id="marque" name="marque" 
                                                       value="<?= htmlspecialchars($article['marque'] ?? '') ?>">
                                            </div>
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <label for="titre" class="form-label required">Titre de l'article</label>
                                        <input type="text" class="form-control" id="titre" name="titre" 
                                               value="<?= htmlspecialchars($article['titre']) ?>" required>
                                    </div>

                                    <div class="mb-3">
                                        <label for="description" class="form-label">Description</label>
                                        <textarea class="form-control" id="description" name="description" rows="5"><?= htmlspecialchars($article['description'] ?? '') ?></textarea>
                                    </div>

                                    <div class="row mb-4">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="prix" class="form-label required">Prix (€)</label>
                                                <input type="number" class="form-control" id="prix" name="prix" 
                                                       value="<?= number_format($article['prix'], 2, '.', '') ?>" step="0.01" min="0.01" required>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="quantite_stock" class="form-label required">Quantité en stock</label>
                                                <input type="number" class="form-control" id="quantite_stock" name="quantite_stock" 
                                                       value="<?= $article['quantite_stock'] ?>" min="0" required>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row mb-4">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="categorie_id" class="form-label">Catégorie</label>
                                                <select class="form-select" id="categorie_id" name="categorie_id">
                                                    <option value="">Aucune catégorie</option>
                                                    <?php foreach ($categories as $categorie): ?>
                                                        <option value="<?= $categorie['id'] ?>" 
                                                            <?= $article['categorie_id'] == $categorie['id'] ? 'selected' : '' ?>>
                                                            <?= htmlspecialchars($categorie['nom']) ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-4">
                                    <!-- Image et statuts -->
                                    <div class="mb-4">
                                        <label class="form-label">Image principale</label>
                                        <?php if ($article['image_principale']): ?>
                                            <div class="mb-3 text-center">
                                                <img src="../<?= htmlspecialchars($article['image_principale']) ?>" 
                                                     alt="<?= htmlspecialchars($article['titre']) ?>" 
                                                     class="image-preview mb-2">
                                                <div class="form-text">
                                                    Image actuelle. Laissez vide pour conserver cette image.
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                        <input type="file" class="form-control" id="image_principale" name="image_principale" 
                                               accept="image/jpeg, image/png, image/gif, image/webp">
                                        <div class="form-text">
                                            Formats acceptés : JPG, PNG, GIF, WEBP (max 5MB)
                                        </div>
                                    </div>

                                    <div class="mb-4">
                                        <div class="form-check form-switch mb-3">
                                            <input class="form-check-input" type="checkbox" id="est_actif" name="est_actif" 
                                                   <?= $article['est_actif'] ? 'checked' : '' ?>>
                                            <label class="form-check-label fw-bold" for="est_actif">
                                                Article actif
                                            </label>
                                        </div>

                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" id="est_en_promotion" name="est_en_promotion" 
                                                   <?= $article['est_en_promotion'] ? 'checked' : '' ?>>
                                            <label class="form-check-label fw-bold" for="est_en_promotion">
                                                En promotion
                                            </label>
                                        </div>
                                    </div>

                                    <div class="promo-section mb-4" id="promoSection" style="<?= $article['est_en_promotion'] ? '' : 'display: none;' ?>">
                                        <h6 class="fw-bold text-warning mb-3">
                                            <i class="fas fa-tag me-2"></i>Promotion
                                        </h6>
                                        <div class="mb-3">
                                            <label for="prix_promotion" class="form-label">Prix promotionnel (€)</label>
                                            <input type="number" class="form-control" id="prix_promotion" name="prix_promotion" 
                                                   value="<?= $article['prix_promotion'] ? number_format($article['prix_promotion'], 2, '.', '') : '' ?>" 
                                                   step="0.01" min="0.01">
                                        </div>
                                    </div>

                                    <div class="card bg-light">
                                        <div class="card-body">
                                            <h6 class="card-title fw-bold">
                                                <i class="fas fa-info-circle me-2"></i>Informations
                                            </h6>
                                            <div class="small">
                                                <div class="mb-1">
                                                    <strong>Créé le :</strong> 
                                                    <?= date('d/m/Y à H:i', strtotime($article['date_creation'])) ?>
                                                </div>
                                                <?php if ($article['date_modification'] && $article['date_modification'] !== $article['date_creation']): ?>
                                                    <div class="mb-1">
                                                        <strong>Modifié le :</strong> 
                                                        <?= date('d/m/Y à H:i', strtotime($article['date_modification'])) ?>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="row mt-4">
                                <div class="col-12">
                                    <div class="d-flex justify-content-between">
                                        <a href="liste_articles.php" class="btn btn-secondary-custom btn-custom">
                                            <i class="fas fa-times me-2"></i>Annuler
                                        </a>
                                        <button type="submit" class="btn btn-primary-custom btn-custom">
                                            <i class="fas fa-save me-2"></i>Enregistrer les modifications
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Gestion de l'affichage de la section promotion
            const promoCheckbox = document.getElementById('est_en_promotion');
            const promoSection = document.getElementById('promoSection');
            const prixPromotion = document.getElementById('prix_promotion');
            
            function togglePromoSection() {
                if (promoCheckbox.checked) {
                    promoSection.style.display = 'block';
                    prixPromotion.required = true;
                } else {
                    promoSection.style.display = 'none';
                    prixPromotion.required = false;
                    prixPromotion.value = '';
                }
            }
            
            promoCheckbox.addEventListener('change', togglePromoSection);
            togglePromoSection(); // Initialisation
            
            // Validation du prix promotionnel
            const prixNormal = document.getElementById('prix');
            
            function validatePromoPrice() {
                if (promoCheckbox.checked && prixPromotion.value) {
                    const prixPromo = parseFloat(prixPromotion.value);
                    const prixNorm = parseFloat(prixNormal.value);
                    
                    if (prixPromo >= prixNorm) {
                        prixPromotion.setCustomValidity('Le prix promotionnel doit être inférieur au prix normal');
                    } else {
                        prixPromotion.setCustomValidity('');
                    }
                } else {
                    prixPromotion.setCustomValidity('');
                }
            }
            
            prixPromotion.addEventListener('input', validatePromoPrice);
            prixNormal.addEventListener('input', validatePromoPrice);
            
            // Aperçu de l'image
            const imageInput = document.getElementById('image_principale');
            const imagePreview = document.querySelector('.image-preview');
            
            if (imageInput && imagePreview) {
                imageInput.addEventListener('change', function(e) {
                    const file = e.target.files[0];
                    if (file) {
                        const reader = new FileReader();
                        reader.onload = function(e) {
                            imagePreview.src = e.target.result;
                        }
                        reader.readAsDataURL(file);
                    }
                });
            }
        });
    </script>
</body>
</html>