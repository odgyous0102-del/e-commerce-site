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
        if (isset($item['quantity'])) {
            $total += $item['quantity'];
        } elseif (isset($item['quantite'])) {
            $total += $item['quantite'];
        }
    }
    return $total;
}

$cart_item_count = getCartItemCount();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TechShop - Votre boutique en ligne premium</title>
    <meta name="description" content="Découvrez notre sélection premium de produits électroniques et technologiques aux meilleurs prix">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- AOS CSS (Animate On Scroll) -->
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    
    <!-- Style personnalisé -->
    <style>
        :root {
            --primary-color: #2563eb;
            --primary-dark: #1d4ed8;
            --secondary-color: #64748b;
            --accent-color: #f59e0b;
            --success-color: #10b981;
            --light-color: #f8fafc;
            --dark-color: #1e293b;
            --border-color: #e2e8f0;
            --gradient-primary: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --gradient-accent: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            font-family: 'Inter', sans-serif;
            color: var(--dark-color);
            line-height: 1.6;
            min-height: 100vh;
            position: relative;
        }

        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: 
                radial-gradient(circle at 20% 80%, rgba(120, 119, 198, 0.1) 0%, transparent 50%),
                radial-gradient(circle at 80% 20%, rgba(255, 119, 198, 0.1) 0%, transparent 50%),
                radial-gradient(circle at 40% 40%, rgba(120, 219, 255, 0.05) 0%, transparent 50%);
            pointer-events: none;
            z-index: -1;
        }

        /* Navigation dynamique */
        .navbar {
            background: rgba(30, 41, 59, 0.95) !important;
            backdrop-filter: blur(20px);
            padding: 1rem 0;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            box-shadow: 0 8px 32px rgba(0,0,0,0.1);
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .navbar.scrolled {
            background: rgba(15, 23, 42, 0.98) !important;
            padding: 0.5rem 0;
            box-shadow: 0 4px 20px rgba(0,0,0,0.15);
        }

        .navbar-brand {
            font-weight: 700;
            color: var(--light-color) !important;
            font-size: 1.75rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            position: relative;
            overflow: hidden;
        }

        .navbar-brand::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.1), transparent);
            transition: left 0.6s ease;
        }

        .navbar-brand:hover::before {
            left: 100%;
        }

        .navbar-brand i {
            color: var(--accent-color);
            filter: drop-shadow(0 0 8px rgba(245, 158, 11, 0.4));
        }

        .nav-link {
            color: var(--light-color) !important;
            font-weight: 500;
            padding: 0.5rem 1rem !important;
            border-radius: 8px;
            transition: all 0.3s ease;
            margin: 0 0.1rem;
            position: relative;
            overflow: hidden;
        }

        .nav-link::before {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            width: 0;
            height: 2px;
            background: var(--accent-color);
            transition: all 0.3s ease;
            transform: translateX(-50%);
        }

        .nav-link:hover::before,
        .nav-link.active::before {
            width: 80%;
        }

        .nav-link:hover {
            color: var(--accent-color) !important;
            transform: translateY(-2px);
        }

        .nav-link.active {
            background: rgba(37, 99, 235, 0.2);
            color: white !important;
        }

        /* Cart badge animé */
        .cart-badge {
            background: var(--gradient-accent);
            color: white;
            border-radius: 50%;
            padding: 0.25rem 0.5rem;
            font-size: 0.75rem;
            font-weight: 600;
            margin-left: 0.5rem;
            animation: pulse 2s infinite, bounce 2s infinite;
            box-shadow: 0 4px 12px rgba(245, 158, 11, 0.4);
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.1); }
        }

        @keyframes bounce {
            0%, 20%, 50%, 80%, 100% { transform: translateY(0); }
            40% { transform: translateY(-3px); }
            60% { transform: translateY(-2px); }
        }

        /* Dropdown menu animé */
        .dropdown-menu {
            border: none;
            box-shadow: 0 20px 40px rgba(0,0,0,0.15);
            border-radius: 16px;
            padding: 0.5rem;
            margin-top: 0.5rem;
            border: 1px solid var(--border-color);
            backdrop-filter: blur(20px);
            background: rgba(255,255,255,0.95);
            opacity: 0;
            transform: translateY(-10px);
            animation: dropdownFadeIn 0.3s ease forwards;
        }

        @keyframes dropdownFadeIn {
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .dropdown-item {
            padding: 0.75rem 1rem;
            border-radius: 8px;
            font-weight: 500;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            position: relative;
            overflow: hidden;
        }

        .dropdown-item::before {
            content: '';
            position: absolute;
            left: -100%;
            top: 0;
            width: 100%;
            height: 100%;
            background: var(--gradient-primary);
            transition: left 0.3s ease;
            z-index: -1;
        }

        .dropdown-item:hover::before {
            left: 0;
        }

        .dropdown-item:hover {
            color: white !important;
            transform: translateX(8px);
        }

        /* Search box animée */
        .search-box {
            max-width: 400px;
            position: relative;
        }

        .search-box .form-control {
            border-radius: 25px;
            border: 1px solid rgba(255,255,255,0.2);
            padding: 0.75rem 1.5rem;
            font-size: 0.9rem;
            transition: all 0.4s ease;
            background: rgba(255,255,255,0.1);
            color: white;
            backdrop-filter: blur(10px);
        }

        .search-box .form-control::placeholder {
            color: rgba(255,255,255,0.7);
        }

        .search-box .form-control:focus {
            background: rgba(255,255,255,0.15);
            border-color: var(--accent-color);
            box-shadow: 0 0 0 3px rgba(245, 158, 11, 0.2);
            transform: scale(1.02);
        }

        .search-box .btn {
            border-radius: 25px;
            padding: 0.75rem 1.5rem;
            margin-left: -50px;
            background: var(--gradient-primary);
            border: none;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .search-box .btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
            transition: left 0.6s ease;
        }

        .search-box .btn:hover::before {
            left: 100%;
        }

        .search-box .btn:hover {
            transform: translateY(-2px) scale(1.05);
            box-shadow: 0 8px 20px rgba(37, 99, 235, 0.4);
        }

        /* Alertes animées */
        .alert {
            border: none;
            border-radius: 16px;
            padding: 1rem 1.5rem;
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
            border-left: 6px solid;
            backdrop-filter: blur(10px);
            animation: slideInRight 0.5s ease-out;
            position: relative;
            overflow: hidden;
        }

        .alert::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, rgba(255,255,255,0.1) 0%, transparent 100%);
            pointer-events: none;
        }

        @keyframes slideInRight {
            from {
                opacity: 0;
                transform: translateX(100%);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        .alert-success {
            background: linear-gradient(135deg, #f0fdf4, #dcfce7);
            border-left-color: var(--success-color);
            color: #065f46;
        }

        .alert-danger {
            background: linear-gradient(135deg, #fef2f2, #fee2e2);
            border-left-color: #ef4444;
            color: #991b1b;
        }

        .alert-info {
            background: linear-gradient(135deg, #f0f9ff, #e0f2fe);
            border-left-color: var(--primary-color);
            color: #1e40af;
        }

        /* User welcome animé */
        .user-welcome {
            color: var(--light-color);
            font-weight: 500;
            background: rgba(255,255,255,0.1);
            padding: 0.5rem 1rem;
            border-radius: 25px;
            margin-right: 1rem;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255,255,255,0.1);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .user-welcome::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.1), transparent);
            transition: left 0.6s ease;
        }

        .user-welcome:hover::before {
            left: 100%;
        }

        .user-welcome:hover {
            background: rgba(255,255,255,0.15);
            transform: translateY(-2px);
        }

        /* Boutons améliorés avec animations */
        .btn {
            border-radius: 12px;
            font-weight: 600;
            padding: 0.75rem 1.5rem;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            border: none;
            position: relative;
            overflow: hidden;
        }

        .btn::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            background: radial-gradient(circle, rgba(255,255,255,0.3) 0%, transparent 70%);
            transition: all 0.4s ease;
            transform: translate(-50%, -50%);
        }

        .btn:hover::before {
            width: 300px;
            height: 300px;
        }

        .btn-primary {
            background: var(--gradient-primary);
            box-shadow: 0 8px 20px rgba(37, 99, 235, 0.3);
        }

        .btn-primary:hover {
            transform: translateY(-3px) scale(1.02);
            box-shadow: 0 12px 30px rgba(37, 99, 235, 0.4);
        }

        .btn-outline-light {
            border: 1px solid rgba(255,255,255,0.3);
            color: white;
            background: rgba(255,255,255,0.1);
            backdrop-filter: blur(10px);
        }

        .btn-outline-light:hover {
            background: rgba(255,255,255,0.2);
            border-color: rgba(255,255,255,0.5);
            transform: translateY(-2px) scale(1.05);
        }

        /* Animations de base */
        .fade-in {
            animation: fadeInUp 0.8s ease-out;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(40px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .slide-in-left {
            animation: slideInLeft 0.8s ease-out;
        }

        @keyframes slideInLeft {
            from {
                opacity: 0;
                transform: translateX(-50px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        /* Effet de flottement */
        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }

        .float {
            animation: float 3s ease-in-out infinite;
        }

        /* Container principal */
        .container {
            max-width: 1400px;
        }

        /* Scrollbar personnalisée */
        ::-webkit-scrollbar {
            width: 8px;
        }

        ::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 10px;
        }

        ::-webkit-scrollbar-thumb {
            background: var(--gradient-primary);
            border-radius: 10px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: var(--primary-dark);
        }

        /* Responsive */
        @media (max-width: 768px) {
            .navbar-nav {
                padding: 1rem 0;
            }
            
            .nav-link {
                margin: 0.25rem 0;
            }
            
            .search-box {
                margin: 1rem 0;
            }
        }

        /* Loading states améliorés */
        .loading {
            position: relative;
            overflow: hidden;
        }

        .loading::after {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.4), transparent);
            animation: loading 1.5s infinite;
        }

        @keyframes loading {
            0% { left: -100%; }
            100% { left: 100%; }
        }

        /* Particules background (optionnel) */
        .particles {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: -1;
        }

        .particle {
            position: absolute;
            background: var(--gradient-primary);
            border-radius: 50%;
            animation: float 6s ease-in-out infinite;
        }
    </style>
</head>
<body>
    <!-- Background particles -->
    <div class="particles" id="particles"></div>

    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark sticky-top" id="mainNav">
        <div class="container">
            <a class="navbar-brand slide-in-left" href="index.php">
                <i class="fas fa-gem float"></i>TechShop
            </a>
            
            <!-- Bouton de recherche mobile -->
            <button class="btn btn-outline-light me-2 d-lg-none" type="button" data-bs-toggle="collapse" data-bs-target="#searchCollapse">
                <i class="fas fa-search"></i>
            </button>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <!-- Barre de recherche -->
                <div class="collapse d-lg-block mb-3 mb-lg-0 mx-auto" id="searchCollapse">
                    <form class="d-flex search-box" action="boutique.php" method="GET" id="searchForm">
                        <input class="form-control" type="search" name="q" placeholder="Rechercher un produit, une marque..." aria-label="Search" id="searchInput">
                        <button class="btn btn-primary" type="submit">
                            <i class="fas fa-search"></i>
                        </button>
                    </form>
                </div>
                
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link " href="index.php" data-aos="fade-down" data-aos-delay="100">
                            <i class="fas fa-home me-1"></i> Accueil
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="boutique.php" data-aos="fade-down" data-aos-delay="200">
                            <i class="fas fa-shopping-bag me-1"></i> Boutique
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="contact.php" data-aos="fade-down" data-aos-delay="200">
                            <i class="fas fa-headset me-1"></i> Contact 
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="apropos.php" data-aos="fade-down" data-aos-delay="200">
                            <i class="fas fa-info-circle me-1"></i> A propos
                        </a>
                    </li>
                    
                    <?php if ($est_connecte && $est_admin): ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarAdmin" role="button" data-bs-toggle="dropdown" data-aos="fade-down" data-aos-delay="400">
                            <i class="fas fa-cog me-1"></i> Administration
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="admin/index.php">
                                <i class="fas fa-tachometer-alt me-2"></i>Tableau de bord
                            </a></li>
                            <li><a class="dropdown-item" href="admin/articles.php">
                                <i class="fas fa-box me-2"></i>Gestion Articles
                            </a></li>
                            <li><a class="dropdown-item" href="admin/commandes.php">
                                <i class="fas fa-receipt me-2"></i>Gestion Commandes
                            </a></li>
                            <li><a class="dropdown-item" href="admin/utilisateurs.php">
                                <i class="fas fa-users me-2"></i>Gestion Utilisateurs
                            </a></li>
                        </ul>
                    </li>
                    <?php endif; ?>
                </ul>
                
                <ul class="navbar-nav ms-auto align-items-center">
                    <!-- Panier avec badge -->
                    <li class="nav-item me-2">
                        <a class="nav-link position-relative" href="panier.php" data-aos="fade-down" data-aos-delay="500">
                            <i class="fas fa-shopping-cart me-1"></i> Panier
                            <?php if ($cart_item_count > 0): ?>
                                <span class="cart-badge position-absolute top-0 start-100 translate-middle">
                                    <?php echo $cart_item_count; ?>
                                </span>
                            <?php endif; ?>
                        </a>
                    </li>
                    
                    <?php if ($est_connecte): ?>
                    <!-- Utilisateur connecté -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" id="navbarUser" role="button" data-bs-toggle="dropdown" data-aos="fade-down" data-aos-delay="600">
                            <div class="user-welcome">
                                <i class="fas fa-user-circle me-2"></i><?= htmlspecialchars($nom_utilisateur) ?>
                            </div>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="mon-compte.php">
                                <i class="fas fa-user-cog me-2"></i>Mon Compte
                            </a></li>
                            <li><a class="dropdown-item" href="mes-commandes.php">
                                <i class="fas fa-history me-2"></i>Historique Commandes
                            </a></li>
                            <li><a class="dropdown-item" href="mes-favoris.php">
                                <i class="fas fa-heart me-2"></i>Mes Favoris
                            </a></li>
                            <?php if ($est_admin): ?>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-primary" href="admin/index.php">
                                <i class="fas fa-shield-alt me-2"></i>Espace Admin
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
                        <a class="nav-link" href="connexion.php" data-aos="fade-down" data-aos-delay="500">
                            <i class="fas fa-sign-in-alt me-1"></i> Connexion
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="btn btn-primary ms-2" href="inscription.php" data-aos="fade-down" data-aos-delay="600">
                            <i class="fas fa-user-plus me-1"></i> S'inscrire
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
        <div class="alert alert-<?= $_SESSION['flash_type'] ?? 'info' ?> alert-dismissible fade show fade-in">
            <div class="d-flex align-items-center">
                <i class="fas fa-<?= 
                    ($_SESSION['flash_type'] ?? 'info') === 'success' ? 'check-circle' : 
                    (($_SESSION['flash_type'] ?? 'info') === 'danger' ? 'exclamation-triangle' : 'info-circle')
                ?> me-2 fs-5"></i>
                <span class="fw-medium"><?= htmlspecialchars($_SESSION['flash_message']) ?></span>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    </div>
    <?php 
    unset($_SESSION['flash_message']);
    unset($_SESSION['flash_type']);
    endif; ?>

    <!-- Contenu principal -->
    <main class="container my-5 fade-in">
        <!-- Le contenu spécifique à chaque page sera inséré ici -->

    <!-- Script Bootstrap -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- AOS JS -->
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    
    <!-- Script pour les interactions dynamiques -->
    <script>
        // Initialiser AOS
        AOS.init({
            duration: 800,
            once: true,
            offset: 50
        });

        // Gestion du scroll de la navbar
        window.addEventListener('scroll', function() {
            const navbar = document.getElementById('mainNav');
            if (window.scrollY > 50) {
                navbar.classList.add('scrolled');
            } else {
                navbar.classList.remove('scrolled');
            }
        });

        // Créer des particules de fond
        function createParticles() {
            const particlesContainer = document.getElementById('particles');
            const particleCount = 15;
            
            for (let i = 0; i < particleCount; i++) {
                const particle = document.createElement('div');
                particle.className = 'particle';
                
                // Propriétés aléatoires
                const size = Math.random() * 60 + 10;
                const posX = Math.random() * 100;
                const posY = Math.random() * 100;
                const delay = Math.random() * 5;
                const duration = Math.random() * 10 + 5;
                
                particle.style.width = `${size}px`;
                particle.style.height = `${size}px`;
                particle.style.left = `${posX}%`;
                particle.style.top = `${posY}%`;
                particle.style.opacity = Math.random() * 0.1 + 0.05;
                particle.style.animationDelay = `${delay}s`;
                particle.style.animationDuration = `${duration}s`;
                
                particlesContainer.appendChild(particle);
            }
        }

        // Recherche en temps réel avec debounce
        let searchTimeout;
        document.getElementById('searchInput')?.addEventListener('input', function(e) {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                if (e.target.value.length > 2) {
                    // Simuler des suggestions de recherche
                    showSearchSuggestions(e.target.value);
                }
            }, 300);
        });

        function showSearchSuggestions(query) {
            // Ici vous intégreriez une requête AJAX vers votre backend
            console.log('Recherche:', query);
            // Pour l'instant, on simule juste le comportement
        }

        // Animation des éléments au survol
        document.addEventListener('DOMContentLoaded', function() {
            createParticles();
            
            // Ajouter des animations aux cartes produits
            const cards = document.querySelectorAll('.card');
            cards.forEach(card => {
                card.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-10px) scale(1.02)';
                    this.style.boxShadow = '0 20px 40px rgba(0,0,0,0.15)';
                });
                
                card.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateY(0) scale(1)';
                    this.style.boxShadow = '0 4px 6px rgba(0,0,0,0.1)';
                });
            });

            // Animation de typing pour le placeholder de recherche
            const searchInput = document.getElementById('searchInput');
            if (searchInput) {
                const phrases = [
                    "Rechercher un smartphone...",
                    "Trouver des écouteurs...",
                    "Découvrir des accessoires...",
                    "Explorer les promotions..."
                ];
                let currentPhrase = 0;
                let currentChar = 0;
                let isDeleting = false;
                
                function typeWriter() {
                    const currentText = phrases[currentPhrase];
                    
                    if (isDeleting) {
                        searchInput.placeholder = currentText.substring(0, currentChar - 1);
                        currentChar--;
                    } else {
                        searchInput.placeholder = currentText.substring(0, currentChar + 1);
                        currentChar++;
                    }
                    
                    if (!isDeleting && currentChar === currentText.length) {
                        isDeleting = true;
                        setTimeout(typeWriter, 2000);
                    } else if (isDeleting && currentChar === 0) {
                        isDeleting = false;
                        currentPhrase = (currentPhrase + 1) % phrases.length;
                        setTimeout(typeWriter, 500);
                    } else {
                        setTimeout(typeWriter, isDeleting ? 50 : 100);
                    }
                }
                
                // Démarrer l'animation après un délai
                setTimeout(typeWriter, 1000);
            }
        });

        // Fonction pour ajouter un produit au panier avec animations avancées
        function ajouterAuPanier(produitId, quantite = 1, element) {
            // Animation de clic
            if (element) {
                element.style.transform = 'scale(0.95)';
                const originalText = element.innerHTML;
                element.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Ajout...';
                element.classList.add('loading');
                
                setTimeout(() => {
                    element.style.transform = 'scale(1)';
                }, 150);
            }

            fetch('ajax/add_to_cart.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'product_id=' + produitId + '&quantity=' + quantite
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Animation du badge de panier
                    updateCartBadge(data.cart_count);
                    
                    // Créer une animation de confirmation
                    createConfirmationAnimation(element);
                    
                    // Feedback visuel
                    showFlashMessage('✓ Produit ajouté au panier avec succès', 'success');
                } else {
                    showFlashMessage('❌ ' + data.message, 'danger');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showFlashMessage('❌ Erreur lors de l\'ajout au panier', 'danger');
            })
            .finally(() => {
                // Restaurer le bouton
                if (element) {
                    setTimeout(() => {
                        element.innerHTML = originalText;
                        element.classList.remove('loading');
                    }, 1000);
                }
            });
        }

        function updateCartBadge(count) {
            const cartBadge = document.querySelector('.cart-badge');
            const cartLink = document.querySelector('a[href="panier.php"]');
            
            if (count > 0) {
                if (cartBadge) {
                    // Animation de mise à jour
                    cartBadge.style.animation = 'none';
                    setTimeout(() => {
                        cartBadge.textContent = count;
                        cartBadge.style.animation = 'pulse 2s infinite, bounce 2s infinite';
                    }, 50);
                } else {
                    // Créer le badge
                    const badge = document.createElement('span');
                    badge.className = 'cart-badge position-absolute top-0 start-100 translate-middle';
                    badge.textContent = count;
                    badge.style.animation = 'pulse 2s infinite, bounce 2s infinite';
                    cartLink.appendChild(badge);
                }
            } else if (cartBadge) {
                cartBadge.remove();
            }
        }

        function createConfirmationAnimation(element) {
            if (!element) return;
            
            const rect = element.getBoundingClientRect();
            const confirmation = document.createElement('div');
            confirmation.innerHTML = '✓';
            confirmation.style.cssText = `
                position: fixed;
                left: ${rect.left + rect.width/2}px;
                top: ${rect.top + rect.height/2}px;
                background: var(--success-color);
                color: white;
                border-radius: 50%;
                width: 40px;
                height: 40px;
                display: flex;
                align-items: center;
                justify-content: center;
                font-weight: bold;
                z-index: 10000;
                pointer-events: none;
                animation: flyToCart 1s ease-in-out forwards;
            `;
            
            document.body.appendChild(confirmation);
            
            setTimeout(() => {
                confirmation.remove();
            }, 1000);
        }

        // Fonction pour afficher les messages flash avec style amélioré
        function showFlashMessage(message, type = 'info') {
            const alertClass = `alert-${type}`;
            const iconClass = type === 'success' ? 'check-circle' : 
                            type === 'danger' ? 'exclamation-triangle' : 'info-circle';
            
            const alertDiv = document.createElement('div');
            alertDiv.className = `alert ${alertClass} alert-dismissible fade show`;
            alertDiv.innerHTML = `
                <div class="d-flex align-items-center">
                    <i class="fas fa-${iconClass} me-2 fs-5"></i>
                    <span class="fw-medium">${message}</span>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            
            const container = document.querySelector('main');
            container.insertBefore(alertDiv, container.firstChild);
            
            // Auto-dismiss après 5 secondes
            setTimeout(() => {
                if (alertDiv.parentElement) {
                    alertDiv.remove();
                }
            }, 5000);
        }

        // Animation de vol vers le panier
        const style = document.createElement('style');
        style.textContent = `
            @keyframes flyToCart {
                0% {
                    transform: scale(1) translate(0, 0);
                    opacity: 1;
                }
                50% {
                    transform: scale(0.8) translate(var(--tx), var(--ty));
                    opacity: 0.8;
                }
                100% {
                    transform: scale(0.3) translate(var(--tx-end), var(--ty-end));
                    opacity: 0;
                }
            }
        `;
        document.head.appendChild(style);

        // Amélioration de l'expérience mobile
        document.addEventListener('DOMContentLoaded', function() {
            const navLinks = document.querySelectorAll('.nav-link');
            const navbarCollapse = document.querySelector('.navbar-collapse');
            
            navLinks.forEach(link => {
                link.addEventListener('click', () => {
                    if (window.innerWidth < 992) {
                        const bsCollapse = new bootstrap.Collapse(navbarCollapse);
                        bsCollapse.hide();
                    }
                });
            });
        });

        // Effet de parallaxe simple
        window.addEventListener('scroll', function() {
            const scrolled = window.pageYOffset;
            const parallaxElements = document.querySelectorAll('.parallax');
            
            parallaxElements.forEach(element => {
                const speed = element.dataset.speed || 0.5;
                element.style.transform = `translateY(${scrolled * speed}px)`;
            });
        });
    </script>
</body>
</html>