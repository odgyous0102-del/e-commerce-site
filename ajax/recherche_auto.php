<?php
// Démarrer la session si pas déjà fait
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Vérifier si l'utilisateur est connecté
$est_connecte = isset($_SESSION['utilisateur_id']);
$est_admin = false;
$nom_utilisateur = '';

if ($est_connecte) {
    // Récupérer les informations de l'utilisateur depuis la session
    $est_admin = isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
    $nom_utilisateur = '';
    
    // Construction du nom d'utilisateur avec vérification des clés
    if (isset($_SESSION['user_prenom']) && isset($_SESSION['user_nom'])) {
        $nom_utilisateur = $_SESSION['user_prenom'] . ' ' . $_SESSION['user_nom'];
    } elseif (isset($_SESSION['user_prenom'])) {
        $nom_utilisateur = $_SESSION['user_prenom'];
    } elseif (isset($_SESSION['user_nom'])) {
        $nom_utilisateur = $_SESSION['user_nom'];
    } else {
        $nom_utilisateur = 'Utilisateur';
    }
}

function getCartItemCount() {
    if (!isset($_SESSION['panier']) || empty($_SESSION['panier'])) {
        return 0;
    }
    
    $total = 0;
    foreach ($_SESSION['panier'] as $item) {
        $total += $item['quantite'];
    }
    return $total;
}

$cart_item_count = getCartItemCount();

// Traitement de la recherche
$terme_recherche = '';
$resultats_recherche = [];
if (isset($_GET['q']) && !empty(trim($_GET['q']))) {
    $terme_recherche = trim($_GET['q']);
    
    // Connexion à la base de données pour la recherche
    try {
        $host = '127.0.0.1:3306';
        $dbname = 'e_commerce';
        $username = 'root'; // À adapter selon votre configuration
        $password = ''; // À adapter selon votre configuration
        
        $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Requête de recherche
        $sql = "SELECT * FROM article 
                WHERE (titre LIKE :terme OR description LIKE :terme OR marque LIKE :terme OR reference LIKE :terme)
                AND est_actif = 1
                ORDER BY 
                    CASE 
                        WHEN titre LIKE :terme_exact THEN 1
                        WHEN titre LIKE :terme_debut THEN 2
                        ELSE 3
                    END,
                    titre ASC
                LIMIT 50";
        
        $stmt = $pdo->prepare($sql);
        $terme_like = '%' . $terme_recherche . '%';
        $terme_exact = $terme_recherche;
        $terme_debut = $terme_recherche . '%';
        
        $stmt->bindParam(':terme', $terme_like);
        $stmt->bindParam(':terme_exact', $terme_exact);
        $stmt->bindParam(':terme_debut', $terme_debut);
        $stmt->execute();
        
        $resultats_recherche = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
    } catch (PDOException $e) {
        error_log("Erreur de recherche: " . $e->getMessage());
        // En cas d'erreur, on continue sans résultats
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $terme_recherche ? 'Résultats pour "' . htmlspecialchars($terme_recherche) . '" - ' : ''; ?>E-Commerce</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- Style personnalisé -->
    <style>
        :root {
            --primary-color: #2c3e50;
            --secondary-color: #3498db;
            --accent-color: #e74c3c;
            --light-color: #ecf0f1;
            --dark-color: #2c3e50;
        }

        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .navbar-brand {
            font-weight: bold;
            color: var(--light-color) !important;
            font-size: 1.5rem;
        }

        .navbar-dark.bg-dark {
            background-color: var(--primary-color) !important;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .nav-link {
            color: var(--light-color) !important;
            transition: color 0.3s ease;
            font-weight: 500;
        }

        .nav-link:hover {
            color: var(--secondary-color) !important;
        }

        .card {
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            border: none;
            border-radius: 10px;
            overflow: hidden;
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 15px rgba(0,0,0,0.2);
        }

        .product-image {
            height: 200px;
            object-fit: cover;
            transition: transform 0.3s ease;
        }

        .card:hover .product-image {
            transform: scale(1.05);
        }

        .price {
            color: #28a745;
            font-weight: bold;
            font-size: 1.2rem;
        }

        .old-price {
            text-decoration: line-through;
            color: #6c757d;
            font-size: 0.9rem;
        }

        .discount-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            background: var(--accent-color);
            color: white;
            padding: 5px 10px;
            border-radius: 15px;
            font-weight: bold;
            z-index: 1;
        }

        .dropdown-menu {
            border: none;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            border-radius: 10px;
            padding: 10px 0;
        }

        .dropdown-item {
            padding: 8px 20px;
            transition: background-color 0.3s ease;
        }

        .dropdown-item:hover {
            background-color: var(--light-color);
            color: var(--dark-color);
        }

        .cart-badge {
            background-color: var(--accent-color);
            color: white;
            border-radius: 50%;
            padding: 3px 8px;
            font-size: 0.8rem;
            margin-left: 5px;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.1); }
            100% { transform: scale(1); }
        }

        .cart-icon {
            position: relative;
        }

        .user-welcome {
            color: var(--light-color);
            margin-right: 10px;
            font-weight: 500;
        }

        .btn-primary {
            background-color: var(--secondary-color);
            border-color: var(--secondary-color);
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            background-color: #2980b9;
            border-color: #2980b9;
            transform: translateY(-2px);
        }

        .alert {
            border-radius: 10px;
            border: none;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }

        .featured-section {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 60px 0;
            margin-bottom: 40px;
            border-radius: 0 0 20px 20px;
        }

        .search-box {
            max-width: 400px;
        }

        .search-results-count {
            color: #6c757d;
            font-size: 0.9rem;
            margin-bottom: 20px;
        }

        .search-highlight {
            background-color: #fff3cd;
            padding: 2px 4px;
            border-radius: 3px;
        }

        .autocomplete-suggestions {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: white;
            border: 1px solid #ddd;
            border-radius: 0 0 5px 5px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            z-index: 1000;
            max-height: 300px;
            overflow-y: auto;
        }

        .autocomplete-suggestion {
            padding: 10px 15px;
            cursor: pointer;
            border-bottom: 1px solid #f8f9fa;
            transition: background-color 0.2s;
        }

        .autocomplete-suggestion:hover {
            background-color: #f8f9fa;
        }

        .autocomplete-suggestion:last-child {
            border-bottom: none;
        }

        .suggestion-title {
            font-weight: 500;
            color: var(--dark-color);
        }

        .suggestion-price {
            color: #28a745;
            font-weight: bold;
        }

        .footer {
            background-color: var(--dark-color);
            color: var(--light-color);
            padding: 40px 0;
            margin-top: 60px;
        }

        .search-container {
            position: relative;
        }

        @media (max-width: 991px) {
            .search-box {
                max-width: 100%;
                margin-bottom: 1rem;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark sticky-top">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-store me-2"></i>E-Commerce
            </a>
            
            <!-- Bouton de recherche (visible sur mobile) -->
            <button class="btn btn-outline-light me-2 d-lg-none" type="button" data-bs-toggle="collapse" data-bs-target="#searchCollapse">
                <i class="fas fa-search"></i>
            </button>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <!-- Barre de recherche améliorée -->
                <div class="collapse d-lg-block mb-3 mb-lg-0 me-lg-3" id="searchCollapse">
                    <form class="d-flex search-box" action="boutique.php" method="GET" id="searchForm">
                        <div class="search-container" style="position: relative; flex: 1;">
                            <input class="form-control me-2" type="search" name="q" id="searchInput" 
                                   placeholder="Rechercher un produit..." aria-label="Search"
                                   value="<?php echo htmlspecialchars($terme_recherche); ?>"
                                   autocomplete="off">
                            <div id="autocompleteResults" class="autocomplete-suggestions" style="display: none;"></div>
                        </div>
                        <button class="btn btn-outline-light" type="submit">
                            <i class="fas fa-search"></i>
                        </button>
                    </form>
                </div>
                
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">
                            <i class="fas fa-home me-1"></i> Accueil
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="boutique.php">
                            <i class="fas fa-shopping-bag me-1"></i> Boutique
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="contact.php">
                            <i class="fas fa-envelope me-1"></i> Contact
                        </a>
                    </li>
                    
                    <?php if ($est_connecte && $est_admin): ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarAdmin" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-cog me-1"></i> Administration
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="admin/index.php">
                                <i class="fas fa-tachometer-alt me-2"></i>Tableau de bord
                            </a></li>
                            <li><a class="dropdown-item" href="admin/articles.php">
                                <i class="fas fa-boxes me-2"></i>Gestion Articles
                            </a></li>
                            <li><a class="dropdown-item" href="admin/commandes.php">
                                <i class="fas fa-shopping-cart me-2"></i>Gestion Commandes
                            </a></li>
                            <li><a class="dropdown-item" href="admin/utilisateurs.php">
                                <i class="fas fa-users me-2"></i>Gestion Utilisateurs
                            </a></li>
                        </ul>
                    </li>
                    <?php endif; ?>
                </ul>
                
                <ul class="navbar-nav ms-auto">
                    <!-- Panier avec badge -->
                    <li class="nav-item">
                        <a class="nav-link cart-icon" href="panier.php">
                            <i class="fas fa-shopping-cart me-1"></i> Panier
                            <?php if ($cart_item_count > 0): ?>
                                <span class="cart-badge" id="panier-count"><?php echo $cart_item_count; ?></span>
                            <?php endif; ?>
                        </a>
                    </li>
                    
                    <?php if ($est_connecte): ?>
                    <!-- Utilisateur connecté -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarUser" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user me-1"></i> <?= htmlspecialchars($nom_utilisateur) ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="mon-compte.php">
                                <i class="fas fa-user-circle me-2"></i>Mon Compte
                            </a></li>
                            <li><a class="dropdown-item" href="mes-commandes.php">
                                <i class="fas fa-receipt me-2"></i>Mes Commandes
                            </a></li>
                            <li><a class="dropdown-item" href="mes-favoris.php">
                                <i class="fas fa-heart me-2"></i>Mes Favoris
                            </a></li>
                            <?php if ($est_admin): ?>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-primary" href="admin/index.php">
                                <i class="fas fa-cog me-2"></i>Administration
                            </a></li>
                            <?php endif; ?>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="deconnexion.php">
                                <i class="fas fa-sign-out-alt me-2"></i>Déconnexion
                            </a></li>
                        </ul>
                    </li>
                    <?php else: ?>
                    <!-- Utilisateur non connecté -->
                    <li class="nav-item">
                        <a class="nav-link" href="connexion.php">
                            <i class="fas fa-sign-in-alt me-1"></i> Connexion
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="inscription.php">
                            <i class="fas fa-user-plus me-1"></i> Inscription
                        </a>
                    </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Messages flash -->
    <?php if (isset($_SESSION['flash_message'])): ?>
    <div class="container mt-3">
        <div class="alert alert-<?= $_SESSION['flash_type'] ?? 'info' ?> alert-dismissible fade show">
            <div class="d-flex align-items-center">
                <i class="fas fa-<?= 
                    ($_SESSION['flash_type'] ?? 'info') === 'success' ? 'check-circle' : 
                    (($_SESSION['flash_type'] ?? 'info') === 'danger' ? 'exclamation-circle' : 'info-circle')
                ?> me-2"></i>
                <span><?= htmlspecialchars($_SESSION['flash_message']) ?></span>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    </div>
    <?php 
    unset($_SESSION['flash_message']);
    unset($_SESSION['flash_type']);
    endif; ?>

    <!-- Contenu principal -->
    <main class="container my-4">
        <?php if ($terme_recherche): ?>
            <!-- Résultats de recherche -->
            <div class="row">
                <div class="col-12">
                    <h2 class="mb-4">
                        <i class="fas fa-search me-2"></i>
                        Résultats pour "<?php echo htmlspecialchars($terme_recherche); ?>"
                    </h2>
                    
                    <div class="search-results-count">
                        <?php 
                        $count = count($resultats_recherche);
                        echo $count . ' produit' . ($count > 1 ? 's' : '') . ' trouvé' . ($count > 1 ? 's' : '');
                        ?>
                    </div>

                    <?php if (empty($resultats_recherche)): ?>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            Aucun produit trouvé pour "<?php echo htmlspecialchars($terme_recherche); ?>".
                            <a href="boutique.php" class="alert-link">Voir tous les produits</a>
                        </div>
                    <?php else: ?>
                        <div class="row">
                            <?php foreach ($resultats_recherche as $produit): ?>
                                <div class="col-md-4 col-lg-3 mb-4">
                                    <div class="card h-100">
                                        <?php if ($produit['image_principale']): ?>
                                            <img src="<?php echo htmlspecialchars($produit['image_principale']); ?>" 
                                                 class="card-img-top product-image" 
                                                 alt="<?php echo htmlspecialchars($produit['titre']); ?>">
                                        <?php else: ?>
                                            <div class="card-img-top product-image bg-light d-flex align-items-center justify-content-center">
                                                <i class="fas fa-image fa-3x text-muted"></i>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <div class="card-body d-flex flex-column">
                                            <h5 class="card-title"><?php echo htmlspecialchars($produit['titre']); ?></h5>
                                            <p class="card-text flex-grow-1"><?php 
                                                $description = $produit['description'];
                                                if (strlen($description) > 100) {
                                                    $description = substr($description, 0, 100) . '...';
                                                }
                                                echo htmlspecialchars($description);
                                            ?></p>
                                            
                                            <div class="mt-auto">
                                                <div class="price mb-2">
                                                    <?php echo number_format($produit['prix'], 2, ',', ' '); ?> €
                                                </div>
                                                
                                                <?php if ($produit['quantite_stock'] > 0): ?>
                                                    <button class="btn btn-primary w-100" 
                                                            onclick="ajouterAuPanier(<?php echo $produit['id']; ?>)">
                                                        <i class="fas fa-cart-plus me-2"></i>Ajouter au panier
                                                    </button>
                                                <?php else: ?>
                                                    <button class="btn btn-outline-secondary w-100" disabled>
                                                        <i class="fas fa-times me-2"></i>Rupture de stock
                                                    </button>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php else: ?>
            <!-- Contenu normal de la page -->
            <div class="row">
                <div class="col-12 text-center">
                    <h1>Bienvenue sur notre boutique en ligne</h1>
                    <p class="lead">Découvrez nos produits exceptionnels</p>
                </div>
            </div>
            
            <!-- Vous pouvez ajouter ici le contenu spécifique de chaque page -->
            
        <?php endif; ?>
    </main>

    <!-- Script Bootstrap -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Script pour les interactions -->
    <script>
        // Fonction pour ajouter un produit au panier
        function ajouterAuPanier(produitId, quantite = 1) {
            fetch('ajax/ajouter_panier.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    produit_id: produitId,
                    quantite: quantite
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Mettre à jour le compteur du panier
                    const panierCount = document.getElementById('panier-count');
                    if (panierCount) {
                        panierCount.textContent = data.nouveau_total;
                    }
                    
                    // Afficher un message de succès
                    showFlashMessage('Produit ajouté au panier !', 'success');
                } else {
                    showFlashMessage('Erreur: ' + data.message, 'danger');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showFlashMessage('Erreur lors de l\'ajout au panier', 'danger');
            });
        }

        // Fonction pour afficher les messages flash
        function showFlashMessage(message, type = 'info') {
            const alertClass = `alert-${type}`;
            const iconClass = type === 'success' ? 'check-circle' : 
                            type === 'danger' ? 'exclamation-circle' : 'info-circle';
            
            const alertDiv = document.createElement('div');
            alertDiv.className = `alert ${alertClass} alert-dismissible fade show`;
            alertDiv.innerHTML = `
                <div class="d-flex align-items-center">
                    <i class="fas fa-${iconClass} me-2"></i>
                    <span>${message}</span>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            
            document.querySelector('main').insertBefore(alertDiv, document.querySelector('main').firstChild);
            
            // Auto-dismiss after 5 seconds
            setTimeout(() => {
                if (alertDiv.parentElement) {
                    alertDiv.remove();
                }
            }, 5000);
        }

        // Auto-complétion de la recherche
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('searchInput');
            const autocompleteResults = document.getElementById('autocompleteResults');
            let timeoutId;

            if (searchInput && autocompleteResults) {
                searchInput.addEventListener('input', function() {
                    clearTimeout(timeoutId);
                    const query = this.value.trim();
                    
                    if (query.length < 2) {
                        autocompleteResults.style.display = 'none';
                        return;
                    }
                    
                    timeoutId = setTimeout(() => {
                        fetch(`ajax/recherche_auto.php?q=${encodeURIComponent(query)}`)
                            .then(response => response.json())
                            .then(data => {
                                if (data.length > 0) {
                                    autocompleteResults.innerHTML = data.map(product => `
                                        <div class="autocomplete-suggestion" onclick="selectSuggestion('${product.titre.replace(/'/g, "\\'")}')">
                                            <div class="suggestion-title">${product.titre}</div>
                                            <div class="suggestion-price">${parseFloat(product.prix).toFixed(2)} €</div>
                                        </div>
                                    `).join('');
                                    autocompleteResults.style.display = 'block';
                                } else {
                                    autocompleteResults.style.display = 'none';
                                }
                            })
                            .catch(error => {
                                console.error('Error:', error);
                                autocompleteResults.style.display = 'none';
                            });
                    }, 300);
                });

                // Cacher les suggestions quand on clique ailleurs
                document.addEventListener('click', function(e) {
                    if (!searchInput.contains(e.target) && !autocompleteResults.contains(e.target)) {
                        autocompleteResults.style.display = 'none';
                    }
                });

                // Navigation clavier dans les suggestions
                searchInput.addEventListener('keydown', function(e) {
                    const suggestions = autocompleteResults.querySelectorAll('.autocomplete-suggestion');
                    let activeIndex = -1;
                    
                    suggestions.forEach((suggestion, index) => {
                        if (suggestion.classList.contains('active')) {
                            activeIndex = index;
                            suggestion.classList.remove('active');
                        }
                    });

                    if (e.key === 'ArrowDown') {
                        e.preventDefault();
                        activeIndex = (activeIndex + 1) % suggestions.length;
                    } else if (e.key === 'ArrowUp') {
                        e.preventDefault();
                        activeIndex = (activeIndex - 1 + suggestions.length) % suggestions.length;
                    } else if (e.key === 'Enter' && activeIndex !== -1) {
                        e.preventDefault();
                        suggestions[activeIndex].click();
                        return;
                    }

                    if (activeIndex !== -1) {
                        suggestions[activeIndex].classList.add('active');
                        searchInput.value = suggestions[activeIndex].querySelector('.suggestion-title').textContent;
                    }
                });
            }
        });

        // Sélectionner une suggestion
        function selectSuggestion(titre) {
            document.getElementById('searchInput').value = titre;
            document.getElementById('autocompleteResults').style.display = 'none';
            document.getElementById('searchForm').submit();
        }

        // Animation pour les cartes au chargement
        document.addEventListener('DOMContentLoaded', function() {
            const cards = document.querySelectorAll('.card');
            cards.forEach((card, index) => {
                card.style.animationDelay = `${index * 0.1}s`;
            });
        });
    </script>
</body>
</html>