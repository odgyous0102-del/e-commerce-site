<?php
// Connexion à la base de données avec gestion d'erreurs améliorée
session_start();

try {
    $host = '127.0.0.1:3306';
    $dbname = 'e_commerce';
    $username = 'root';
    $password = '';
    
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    error_log("Erreur de connexion BD: " . $e->getMessage());
    die("Une erreur est survenue lors du chargement de la page");
}

// Récupération des catégories actives avec cache
$cache_duration = 300; // 5 minutes
$cache_file_categories = 'cache/categories.cache';

if (file_exists($cache_file_categories) && (time() - filemtime($cache_file_categories) < $cache_duration)) {
    $categories = unserialize(file_get_contents($cache_file_categories));
} else {
    // CORRECTION : Sélectionner uniquement les colonnes existantes
    $sqlCategories = "SELECT id, nom, description, parent_id, ordre_affichage, est_actif 
                     FROM categorie_article 
                     WHERE est_actif = 1 
                     ORDER BY ordre_affichage";
    $stmtCategories = $pdo->query($sqlCategories);
    $categories = $stmtCategories->fetchAll();
    
    // Création du dossier cache si inexistant
    if (!is_dir('cache')) mkdir('cache', 0755, true);
    file_put_contents($cache_file_categories, serialize($categories));
}

// Récupération des articles populaires
// CORRECTION : Supprimer les références aux colonnes inexistantes
$sqlArticles = "SELECT a.*, c.nom as categorie_nom
                FROM article a 
                LEFT JOIN categorie_article c ON a.categorie_id = c.id 
                WHERE a.est_actif = 1
                ORDER BY a.date_creation DESC 
                LIMIT 12";
$stmtArticles = $pdo->query($sqlArticles);
$articles = $stmtArticles->fetchAll();
?>

<!DOCTYPE html>
<html lang="fr" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Découvrez notre sélection de produits de qualité aux meilleurs prix. Livraison rapide et sécurisée.">
    <meta name="keywords" content="boutique en ligne, e-commerce, produits, achats en ligne">
    
    <title>Boutique en Ligne - Vos produits préférés au meilleur prix</title>
    
    <!-- Open Graph -->
    <meta property="og:title" content="Boutique en Ligne - Produits de qualité">
    <meta property="og:description" content="Découvrez notre sélection exclusive de produits aux meilleurs prix">
    <meta property="og:type" content="website">
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/aos@2.3.4/aos.css">
    
    <style>
        :root {
            --primary-color: #2c3e50;
            --secondary-color: #3498db;
            --accent-color: #e74c3c;
            --success-color: #27ae60;
        }
        
        .hero-section {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            padding: 100px 0;
            position: relative;
            overflow: hidden;
        }
        
        .hero-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1000 1000"><polygon fill="rgba(255,255,255,0.05)" points="0,1000 1000,0 1000,1000"/></svg>');
        }
        
        .product-card {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            border: none;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            height: 100%;
        }
        
        .product-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 25px rgba(0,0,0,0.15);
        }
        
        .product-image {
            height: 220px;
            object-fit: cover;
            transition: transform 0.3s ease;
        }
        
        .product-card:hover .product-image {
            transform: scale(1.05);
        }
        
        .badge-trending {
            position: absolute;
            top: 10px;
            left: 10px;
            z-index: 2;
        }
        
        .price-section {
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
            border-radius: 10px;
            padding: 15px;
        }
        
        .discount-badge {
            background: var(--accent-color);
            color: white;
            padding: 4px 8px;
            border-radius: 6px;
            font-size: 0.8em;
        }
        
        .category-nav {
            background: var(--primary-color);
            padding: 20px 0;
        }
        
        .category-item {
            text-align: center;
            color: white;
            text-decoration: none;
            transition: color 0.3s ease;
            padding: 10px;
        }
        
        .category-item:hover {
            color: var(--secondary-color);
        }
        
        .stats-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .loading-spinner {
            display: none;
            text-align: center;
            padding: 20px;
        }
        
        .stock-low {
            color: var(--accent-color);
            font-weight: bold;
        }
        
        .rating {
            color: #ffc107;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.1); }
            100% { transform: scale(1); }
        }
        
        @media (max-width: 768px) {
            .hero-section {
                padding: 60px 0;
            }
            
            .product-card {
                margin-bottom: 20px;
            }
        }
    </style>
</head>
<body>
    <?php 
    // Vérifier si le fichier header.php existe avant de l'inclure
    if (file_exists('header.php')) {
        include 'header.php'; 
    } else {
        // Créer un header minimal si le fichier n'existe pas
        echo '
        <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
            <div class="container">
                <a class="navbar-brand" href="#">Boutique en Ligne</a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarNav">
                    <ul class="navbar-nav ms-auto">
                        <li class="nav-item">
                            <a class="nav-link" href="#"><i class="fas fa-home me-1"></i>Accueil</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="boutique.php"><i class="fas fa-store me-1"></i>Boutique</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#"><i class="fas fa-shopping-cart me-1"></i>Panier <span id="cart-count" class="badge bg-primary">0</span></a>
                        </li>
                    </ul>
                </div>
            </div>
        </nav>';
    }
    ?>

    <!-- Navigation par catégories -->
    <nav class="category-nav">
        <div class="container">
            <div class="row g-3 text-center">
                <div class="col">
                    <a href="#" class="category-item active" data-category="all">
                        <i class="fas fa-th-large fa-2x mb-2"></i>
                        <div>Tous les produits</div>
                    </a>
                </div>
                <?php foreach($categories as $category): ?>
                <div class="col">
                    <a href="#" class="category-item" data-category="<?= htmlspecialchars($category['id']) ?>">
                        <!-- CORRECTION : Utiliser une icône par défaut puisque la colonne icone n'existe pas -->
                        <i class="fas fa-tag fa-2x mb-2"></i>
                        <div><?= htmlspecialchars($category['nom']) ?></div>
                    </a>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </nav>

    <!-- Bannière hero -->
    <section class="hero-section">
        <div class="container position-relative">
            <div class="row align-items-center">
                <div class="col-lg-6" data-aos="fade-right">
                    <h1 class="display-4 fw-bold mb-4">Votre Shopping de Confiance</h1>
                    <p class="lead mb-4">Découvrez des produits exceptionnels soigneusement sélectionnés pour leur qualité et leur rapport qualité-prix.</p>
                    <div class="d-flex gap-3 flex-wrap">
                        <a href="#products" class="btn btn-light btn-lg px-4 py-2">
                            <i class="fas fa-shopping-bag me-2"></i>Découvrir
                        </a>
                        <a href="#promotions" class="btn btn-outline-light btn-lg px-4 py-2">
                            <i class="fas fa-percent me-2"></i>Promotions
                        </a>
                    </div>
                </div>
                <div class="col-lg-6 text-center" data-aos="fade-left">
                    <!-- CORRECTION : Utiliser une image de secours si l'image n'existe pas -->
                    <?php
                    $imagePath = 'image.jpeg';
                    if (!file_exists($imagePath)) {
                        // Utiliser une image de placeholder si l'image n'existe pas
                        $imagePath = 'https://images.unsplash.com/photo-1556742049-0cfed4f6a45d?ixlib=rb-4.0.3&auto=format&fit=crop&w=500&q=80';
                    }
                    ?>
                    <img src="<?= $imagePath ?>" 
                         alt="Shopping en ligne" 
                         class="img-fluid rounded shadow" 
                         style="max-height: 400px;">
                </div>
            </div>
        </div>
    </section>

    <!-- Statistiques -->
    <section class="stats-section py-4">
        <div class="container">
            <div class="row text-center">
                <div class="col-md-3 col-6 mb-3">
                    <div class="h2 fw-bold">10K+</div>
                    <div>Clients satisfaits</div>
                </div>
                <div class="col-md-3 col-6 mb-3">
                    <div class="h2 fw-bold">500+</div>
                    <div>Produits disponibles</div>
                </div>
                <div class="col-md-3 col-6 mb-3">
                    <div class="h2 fw-bold">24/7</div>
                    <div>Support client</div>
                </div>
                <div class="col-md-3 col-6 mb-3">
                    <div class="h2 fw-bold">48h</div>
                    <div>Livraison express</div>
                </div>
            </div>
        </div>
    </section>

    <!-- Section produits -->
    <section id="products" class="py-5 bg-light">
        <div class="container">
            <div class="text-center mb-5" data-aos="fade-up">
                <h2 class="display-5 fw-bold mb-3">Nos Produits Populaires</h2>
                <p class="lead text-muted">Découvrez notre sélection des produits les plus appréciés</p>
            </div>
            
            <div class="loading-spinner" id="loadingSpinner">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Chargement...</span>
                </div>
                <p class="mt-2">Chargement des produits...</p>
            </div>
            
            <div class="row g-4" id="productsContainer">
                <?php foreach($articles as $article): ?>
                    <div class="col-xl-3 col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="<?= $article['id'] % 4 * 100 ?>">
                        <div class="card product-card">
                            <div class="position-relative">
                                <?php 
                                // CORRECTION : Vérifier si l'image existe
                                $imagePath = $article['image_principale'] ?? '';
                                if ($imagePath && file_exists($imagePath)): 
                                ?>
                                    <img src="<?= htmlspecialchars($imagePath) ?>" 
                                         class="card-img-top product-image" 
                                         alt="<?= htmlspecialchars($article['titre']) ?>"
                                         loading="lazy">
                                <?php else: ?>
                                    <!-- Image de secours -->
                                    <img src="https://images.unsplash.com/photo-1505740420928-5e560c06d30e?ixlib=rb-4.0.3&auto=format&fit=crop&w=500&q=80" 
                                         class="card-img-top product-image" 
                                         alt="Image non disponible"
                                         loading="lazy">
                                <?php endif; ?>
                                
                                <?php if(isset($article['est_en_promotion']) && $article['est_en_promotion']): ?>
                                    <span class="badge-trending discount-badge">
                                        <i class="fas fa-fire me-1"></i>Promo
                                    </span>
                                <?php endif; ?>
                            </div>
                            
                            <div class="card-body d-flex flex-column">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <span class="badge bg-secondary"><?= htmlspecialchars($article['categorie_nom'] ?? 'Non catégorisé') ?></span>
                                    <div class="rating">
                                        <i class="fas fa-star"></i>
                                        <i class="fas fa-star"></i>
                                        <i class="fas fa-star"></i>
                                        <i class="fas fa-star"></i>
                                        <i class="fas fa-star-half-alt"></i>
                                    </div>
                                </div>
                                
                                <h5 class="card-title"><?= htmlspecialchars($article['titre']) ?></h5>
                                <p class="card-text text-muted flex-grow-1">
                                    <?= substr(htmlspecialchars($article['description'] ?? ''), 0, 80) ?>...
                                </p>
                                
                                <div class="price-section mt-auto">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <div class="price">
                                            <?php if(isset($article['est_en_promotion']) && $article['est_en_promotion'] && isset($article['prix_promotion'])): ?>
                                                <span class="old-price text-muted text-decoration-line-through"><?= number_format($article['prix'], 2, ',', ' ') ?> €</span>
                                                <span class="h5 text-danger fw-bold ms-2"><?= number_format($article['prix_promotion'], 2, ',', ' ') ?> €</span>
                                            <?php else: ?>
                                                <span class="h5 text-primary fw-bold"><?= number_format($article['prix'], 2, ',', ' ') ?> €</span>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <small class="<?= ($article['quantite_stock'] ?? 0) < 10 ? 'stock-low' : 'text-muted' ?>">
                                            <i class="fas fa-box me-1"></i>
                                            <?= $article['quantite_stock'] ?? 0 ?> restants
                                        </small>
                                    </div>
                                    
                                    <div class="d-grid gap-2">
                                        <?php if (($article['quantite_stock'] ?? 0) > 0): ?>
                                            <button class="btn btn-primary add-to-cart" 
                                                    data-id="<?= $article['id'] ?>"
                                                    data-name="<?= htmlspecialchars($article['titre']) ?>">
                                                <i class="fas fa-cart-plus me-2"></i>Ajouter au panier
                                            </button>
                                            <a href="produit.php?id=<?= $article['id'] ?>" class="btn btn-outline-secondary">
                                                <i class="fas fa-eye me-2"></i>Aperçu rapide
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
            
            <div class="text-center mt-5">
                <a href="boutique.php" class="btn btn-outline-primary btn-lg">
                    <i class="fas fa-store me-2"></i>Voir tous nos produits
                </a>
            </div>
        </div>
    </section>

    <!-- Newsletter -->
    <section class="py-5 bg-primary text-white">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h3 class="fw-bold">Restez informé</h3>
                    <p class="mb-0">Inscrivez-vous à notre newsletter pour recevoir nos offres exclusives</p>
                </div>
                <div class="col-md-6">
                    <form class="d-flex gap-2" id="newsletterForm">
                        <input type="email" class="form-control" placeholder="Votre email" required>
                        <button type="submit" class="btn btn-light">
                            <i class="fas fa-paper-plane"></i>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </section>

    <!-- Toast de notification -->
    <div class="toast position-fixed top-0 end-0 m-3" id="cartToast" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="toast-header bg-success text-white">
            <i class="fas fa-check-circle me-2"></i>
            <strong class="me-auto">Succès</strong>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
        <div class="toast-body" id="toastMessage">
            Produit ajouté au panier !
        </div>
    </div>

    <?php 
    // Vérifier si le fichier footer.php existe avant de l'inclure
    if (file_exists('footer.php')) {
        include 'footer.php'; 
    } else {
        // Créer un footer minimal si le fichier n'existe pas
        echo '
        <footer class="bg-dark text-white py-4 mt-5">
            <div class="container">
                <div class="row">
                    <div class="col-md-6">
                        <h5>Boutique en Ligne</h5>
                        <p>Votre destination shopping de confiance.</p>
                    </div>
                    <div class="col-md-6 text-md-end">
                        <p>&copy; ' . date('Y') . ' Boutique en Ligne. Tous droits réservés.</p>
                    </div>
                </div>
            </div>
        </footer>';
    }
    ?>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/aos@2.3.4/aos.js"></script>
    
    <script>
        // Initialisation AOS (Animations On Scroll)
        document.addEventListener('DOMContentLoaded', function() {
            AOS.init({
                duration: 800,
                once: true,
                offset: 100
            });
            
            // Gestion du chargement des produits par catégorie
            const categoryLinks = document.querySelectorAll('.category-item');
            const productsContainer = document.getElementById('productsContainer');
            const loadingSpinner = document.getElementById('loadingSpinner');
            
            categoryLinks.forEach(link => {
                link.addEventListener('click', function(e) {
                    e.preventDefault();
                    
                    // Mise à jour de l'état actif
                    categoryLinks.forEach(l => l.classList.remove('active'));
                    this.classList.add('active');
                    
                    const categoryId = this.getAttribute('data-category');
                    loadProductsByCategory(categoryId);
                });
            });
            
            function loadProductsByCategory(categoryId) {
                loadingSpinner.style.display = 'block';
                productsContainer.style.opacity = '0.5';
                
                // Simulation du chargement (à remplacer par une vraie requête AJAX)
                setTimeout(() => {
                    // Ici, vous feriez une requête AJAX vers votre backend
                    console.log('Chargement des produits pour la catégorie:', categoryId);
                    
                    loadingSpinner.style.display = 'none';
                    productsContainer.style.opacity = '1';
                    
                    // Ajouter une animation de fade in
                    productsContainer.style.animation = 'fadeIn 0.5s ease-in';
                }, 800);
            }
            
            // Gestion de l'ajout au panier
            document.querySelectorAll('.add-to-cart').forEach(button => {
                button.addEventListener('click', function() {
                    const productId = this.getAttribute('data-id');
                    const productName = this.getAttribute('data-name');
                    
                    // Animation du bouton
                    const originalHTML = this.innerHTML;
                    this.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Ajout...';
                    this.disabled = true;
                    
                    // Simulation d'appel AJAX
                    setTimeout(() => {
                        // Mettre à jour le compteur du panier
                        const cartCount = document.getElementById('cart-count');
                        if (cartCount) {
                            const currentCount = parseInt(cartCount.textContent) || 0;
                            cartCount.textContent = currentCount + 1;
                            cartCount.style.animation = 'pulse 0.6s';
                            setTimeout(() => cartCount.style.animation = '', 600);
                        }
                        
                        // Afficher la notification
                        const toast = new bootstrap.Toast(document.getElementById('cartToast'));
                        document.getElementById('toastMessage').textContent = 
                            `${productName} a été ajouté à votre panier !`;
                        toast.show();
                        
                        // Réinitialiser le bouton
                        this.innerHTML = originalHTML;
                        this.disabled = false;
                    }, 1000);
                });
            });
            
            // Gestion de la newsletter
            document.getElementById('newsletterForm').addEventListener('submit', function(e) {
                e.preventDefault();
                const email = this.querySelector('input[type="email"]').value;
                
                // Simulation d'envoi
                const toast = new bootstrap.Toast(document.getElementById('cartToast'));
                document.getElementById('toastMessage').textContent = 
                    `Merci ! Vous êtes maintenant inscrit avec l'email: ${email}`;
                document.querySelector('#cartToast .toast-header').className = 'toast-header bg-info text-white';
                toast.show();
                
                this.reset();
            });
        });
    </script>
</body>
</html>