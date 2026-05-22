<?php
// Connexion à la base de données
$host = '127.0.0.1:3306';
$dbname = 'e_commerce';
$username = 'root'; // À modifier selon votre configuration
$password = ''; // À modifier selon votre configuration

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Erreur de connexion : " . $e->getMessage());
}

// Récupération des paramètres de filtrage
$categorie_id = isset($_GET['categorie']) ? intval($_GET['categorie']) : 0;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$marque = isset($_GET['marque']) ? trim($_GET['marque']) : '';
$min_price = isset($_GET['min_price']) ? floatval($_GET['min_price']) : 0;
$max_price = isset($_GET['max_price']) ? floatval($_GET['max_price']) : 0;
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'date_desc';
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = 12; // Nombre de produits par page
$offset = ($page - 1) * $limit;

// Construction de la requête de base
$sql = "SELECT a.*, c.nom as categorie_nom 
        FROM article a 
        LEFT JOIN categorie_article c ON a.categorie_id = c.id 
        WHERE a.est_actif = 1";
$count_sql = "SELECT COUNT(*) as total FROM article a WHERE a.est_actif = 1";
$params = [];
$count_params = [];

// Application des filtres
if ($categorie_id > 0) {
    $sql .= " AND a.categorie_id = ?";
    $count_sql .= " AND a.categorie_id = ?";
    $params[] = $categorie_id;
    $count_params[] = $categorie_id;
}

if (!empty($search)) {
    $sql .= " AND (a.titre LIKE ? OR a.description LIKE ? OR a.reference LIKE ?)";
    $count_sql .= " AND (a.titre LIKE ? OR a.description LIKE ? OR a.reference LIKE ?)";
    $search_term = "%$search%";
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
    $count_params[] = $search_term;
    $count_params[] = $search_term;
    $count_params[] = $search_term;
}

if (!empty($marque)) {
    $sql .= " AND a.marque = ?";
    $count_sql .= " AND a.marque = ?";
    $params[] = $marque;
    $count_params[] = $marque;
}

if ($min_price > 0) {
    $sql .= " AND (CASE WHEN a.est_en_promotion = 1 AND a.prix_promotion IS NOT NULL THEN a.prix_promotion ELSE a.prix END) >= ?";
    $count_sql .= " AND (CASE WHEN a.est_en_promotion = 1 AND a.prix_promotion IS NOT NULL THEN a.prix_promotion ELSE a.prix END) >= ?";
    $params[] = $min_price;
    $count_params[] = $min_price;
}

if ($max_price > 0) {
    $sql .= " AND (CASE WHEN a.est_en_promotion = 1 AND a.prix_promotion IS NOT NULL THEN a.prix_promotion ELSE a.prix END) <= ?";
    $count_sql .= " AND (CASE WHEN a.est_en_promotion = 1 AND a.prix_promotion IS NOT NULL THEN a.prix_promotion ELSE a.prix END) <= ?";
    $params[] = $max_price;
    $count_params[] = $max_price;
}

// Application du tri
switch ($sort) {
    case 'price_asc':
        $sql .= " ORDER BY (CASE WHEN a.est_en_promotion = 1 AND a.prix_promotion IS NOT NULL THEN a.prix_promotion ELSE a.prix END) ASC";
        break;
    case 'price_desc':
        $sql .= " ORDER BY (CASE WHEN a.est_en_promotion = 1 AND a.prix_promotion IS NOT NULL THEN a.prix_promotion ELSE a.prix END) DESC";
        break;
    case 'name_asc':
        $sql .= " ORDER BY a.titre ASC";
        break;
    case 'name_desc':
        $sql .= " ORDER BY a.titre DESC";
        break;
    case 'date_desc':
    default:
        $sql .= " ORDER BY a.date_creation DESC";
        break;
}

// Ajout de la pagination
$sql .= " LIMIT ? OFFSET ?";
$params[] = $limit;
$params[] = $offset;

// Exécution de la requête des produits
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Comptage du nombre total de produits pour la pagination
$count_stmt = $pdo->prepare($count_sql);
$count_stmt->execute($count_params);
$total_products = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];
$total_pages = ceil($total_products / $limit);

// Récupération des catégories pour le filtre
$sql_categories = "SELECT * FROM categorie_article WHERE est_actif = 1 ORDER BY ordre_affichage, nom";
$categories = $pdo->query($sql_categories)->fetchAll(PDO::FETCH_ASSOC);

// Récupération des marques pour le filtre
$sql_marques = "SELECT DISTINCT marque FROM article WHERE marque IS NOT NULL AND marque != '' AND est_actif = 1 ORDER BY marque";
$marques = $pdo->query($sql_marques)->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Catalogue - Site E-commerce</title>
    <style>
        :root {
            --primary-color: #3498db;
            --secondary-color: #2c3e50;
            --accent-color: #e74c3c;
            --light-color: #ecf0f1;
            --dark-color: #34495e;
            --success-color: #2ecc71;
            --border-radius: 8px;
            --box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background-color: #f9f9f9;
            color: #333;
            line-height: 1.6;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }
        
        header {
            background-color: white;
            box-shadow: var(--box-shadow);
            position: sticky;
            top: 0;
            z-index: 100;
        }
        
        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 0;
        }
        
        .logo {
            font-size: 24px;
            font-weight: bold;
            color: var(--primary-color);
            text-decoration: none;
        }
        
        nav ul {
            display: flex;
            list-style: none;
        }
        
        nav ul li {
            margin-left: 20px;
        }
        
        nav ul li a {
            text-decoration: none;
            color: var(--dark-color);
            font-weight: 500;
            transition: color 0.3s;
        }
        
        nav ul li a:hover {
            color: var(--primary-color);
        }
        
        .user-actions a {
            margin-left: 15px;
            text-decoration: none;
            color: var(--dark-color);
        }
        
        .cart-count {
            background-color: var(--accent-color);
            color: white;
            border-radius: 50%;
            padding: 2px 6px;
            font-size: 12px;
            margin-left: 5px;
        }
        
        .breadcrumb {
            padding: 15px 0;
            font-size: 14px;
            color: #777;
        }
        
        .breadcrumb a {
            color: var(--primary-color);
            text-decoration: none;
        }
        
        .catalog-header {
            display: flex;
            justify-content: between;
            align-items: center;
            margin: 30px 0 20px;
        }
        
        .catalog-title {
            font-size: 32px;
            color: var(--secondary-color);
        }
        
        .results-count {
            color: #777;
            margin-top: 5px;
        }
        
        .catalog-container {
            display: grid;
            grid-template-columns: 250px 1fr;
            gap: 30px;
            margin: 30px 0;
        }
        
        .filters-sidebar {
            background: white;
            border-radius: var(--border-radius);
            padding: 25px;
            box-shadow: var(--box-shadow);
            height: fit-content;
            position: sticky;
            top: 100px;
        }
        
        .filter-section {
            margin-bottom: 25px;
        }
        
        .filter-title {
            font-size: 18px;
            margin-bottom: 15px;
            color: var(--secondary-color);
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }
        
        .filter-group {
            margin-bottom: 15px;
        }
        
        .filter-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            font-size: 14px;
        }
        
        .filter-select, .filter-input {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            background-color: white;
            font-size: 14px;
        }
        
        .price-inputs {
            display: flex;
            gap: 10px;
        }
        
        .price-inputs input {
            flex: 1;
        }
        
        .filter-checkbox {
            display: flex;
            align-items: center;
            margin-bottom: 8px;
        }
        
        .filter-checkbox input {
            margin-right: 8px;
        }
        
        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: var(--border-radius);
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            font-size: 14px;
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            color: white;
        }
        
        .btn-primary:hover {
            background-color: #2980b9;
        }
        
        .btn-secondary {
            background-color: var(--light-color);
            color: var(--dark-color);
        }
        
        .btn-secondary:hover {
            background-color: #dde4e6;
        }
        
        .btn-block {
            width: 100%;
            margin-top: 10px;
        }
        
        .search-box {
            position: relative;
            margin-bottom: 20px;
        }
        
        .search-input {
            width: 100%;
            padding: 12px 45px 12px 15px;
            border: 1px solid #ddd;
            border-radius: var(--border-radius);
            font-size: 16px;
        }
        
        .search-button {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            font-size: 18px;
            cursor: pointer;
            color: #777;
        }
        
        .catalog-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            background: white;
            padding: 15px 20px;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
        }
        
        .sort-select {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            background-color: white;
        }
        
        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 25px;
        }
        
        .product-card {
            background: white;
            border-radius: var(--border-radius);
            overflow: hidden;
            box-shadow: var(--box-shadow);
            transition: transform 0.3s, box-shadow 0.3s;
            text-decoration: none;
            color: inherit;
            position: relative;
        }
        
        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 15px rgba(0, 0, 0, 0.1);
        }
        
        .product-badge {
            position: absolute;
            top: 10px;
            left: 10px;
            background-color: var(--accent-color);
            color: white;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: bold;
            z-index: 2;
        }
        
        .product-image {
            height: 200px;
            overflow: hidden;
            position: relative;
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
        
        .product-card-content {
            padding: 15px;
        }
        
        .product-card-title {
            font-size: 16px;
            margin-bottom: 10px;
            height: 40px;
            overflow: hidden;
            color: var(--secondary-color);
        }
        
        .product-card-category {
            font-size: 12px;
            color: #777;
            margin-bottom: 8px;
        }
        
        .product-card-price {
            font-weight: bold;
            color: var(--accent-color);
            font-size: 18px;
            margin-bottom: 5px;
        }
        
        .product-card-original-price {
            text-decoration: line-through;
            color: #777;
            font-size: 14px;
            margin-left: 8px;
        }
        
        .product-card-reference {
            font-size: 12px;
            color: #999;
            margin-bottom: 10px;
        }
        
        .product-card-stock {
            font-size: 12px;
            margin-bottom: 10px;
        }
        
        .in-stock {
            color: var(--success-color);
        }
        
        .low-stock {
            color: #f39c12;
        }
        
        .out-of-stock {
            color: var(--accent-color);
        }
        
        .pagination {
            display: flex;
            justify-content: center;
            margin: 40px 0;
            gap: 5px;
        }
        
        .pagination a, .pagination span {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            text-decoration: none;
            color: var(--dark-color);
            transition: all 0.3s;
        }
        
        .pagination a:hover {
            background-color: var(--primary-color);
            color: white;
            border-color: var(--primary-color);
        }
        
        .pagination .current {
            background-color: var(--primary-color);
            color: white;
            border-color: var(--primary-color);
        }
        
        .no-products {
            grid-column: 1 / -1;
            text-align: center;
            padding: 60px 20px;
            color: #777;
        }
        
        .no-products-icon {
            font-size: 64px;
            margin-bottom: 20px;
            color: #ddd;
        }
        
        footer {
            background-color: var(--secondary-color);
            color: white;
            padding: 50px 0 20px;
            margin-top: 50px;
        }
        
        .footer-content {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 30px;
            margin-bottom: 30px;
        }
        
        .footer-column h3 {
            font-size: 18px;
            margin-bottom: 20px;
            position: relative;
            padding-bottom: 10px;
        }
        
        .footer-column h3::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 30px;
            height: 2px;
            background-color: var(--primary-color);
        }
        
        .footer-column ul {
            list-style: none;
        }
        
        .footer-column ul li {
            margin-bottom: 10px;
        }
        
        .footer-column ul li a {
            color: #bbb;
            text-decoration: none;
            transition: color 0.3s;
        }
        
        .footer-column ul li a:hover {
            color: white;
        }
        
        .copyright {
            text-align: center;
            padding-top: 20px;
            border-top: 1px solid #444;
            color: #bbb;
            font-size: 14px;
        }
        
        @media (max-width: 992px) {
            .catalog-container {
                grid-template-columns: 1fr;
            }
            
            .filters-sidebar {
                position: static;
                margin-bottom: 20px;
            }
            
            .footer-content {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        
        @media (max-width: 768px) {
            .products-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .catalog-actions {
                flex-direction: column;
                gap: 15px;
                align-items: flex-start;
            }
            
            .header-content {
                flex-direction: column;
                text-align: center;
            }
            
            nav ul {
                margin-top: 15px;
                justify-content: center;
            }
            
            .user-actions {
                margin-top: 15px;
            }
        }
        
        @media (max-width: 576px) {
            .products-grid {
                grid-template-columns: 1fr;
            }
            
            .footer-content {
                grid-template-columns: 1fr;
            }
            
            .price-inputs {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <header>
        <div class="container">
            <div class="header-content">
                <a href="index.php" class="logo">MonShop</a>
                <nav>
                    <ul>
                        <li><a href="index.php">Accueil</a></li>
                        <li><a href="catalogue.php" style="color: var(--primary-color);">Catalogue</a></li>
                        <li><a href="promotions.php">Promotions</a></li>
                        <li><a href="contact.php">Contact</a></li>
                    </ul>
                </nav>
                <div class="user-actions">
                    <a href="panier.php">Panier <span class="cart-count">0</span></a>
                    <a href="compte.php">Mon Compte</a>
                </div>
            </div>
        </div>
    </header>

    <main class="container">
        <div class="breadcrumb">
            <a href="index.php">Accueil</a> > Catalogue
            <?php if ($categorie_id > 0): ?>
                > <?php 
                    $categorie_nom = '';
                    foreach ($categories as $cat) {
                        if ($cat['id'] == $categorie_id) {
                            $categorie_nom = $cat['nom'];
                            break;
                        }
                    }
                    echo htmlspecialchars($categorie_nom);
                ?>
            <?php endif; ?>
        </div>

        <div class="catalog-header">
            <div>
                <h1 class="catalog-title">Notre Catalogue</h1>
                <p class="results-count">
                    <?php 
                    echo $total_products . ' produit' . ($total_products > 1 ? 's' : '') . ' trouvé' . ($total_products > 1 ? 's' : '');
                    if (!empty($search)) {
                        echo ' pour "' . htmlspecialchars($search) . '"';
                    }
                    ?>
                </p>
            </div>
        </div>

        <div class="catalog-container">
            <aside class="filters-sidebar">
                <h3>Filtres</h3>
                
                <form method="GET" id="filter-form">
                    <div class="search-box">
                        <input type="text" name="search" class="search-input" placeholder="Rechercher un produit..." 
                               value="<?php echo htmlspecialchars($search); ?>">
                        <button type="submit" class="search-button">🔍</button>
                    </div>
                    
                    <div class="filter-section">
                        <h4 class="filter-title">Catégories</h4>
                        <div class="filter-group">
                            <select name="categorie" class="filter-select" onchange="this.form.submit()">
                                <option value="">Toutes les catégories</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?php echo $cat['id']; ?>" 
                                        <?php echo $categorie_id == $cat['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($cat['nom']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="filter-section">
                        <h4 class="filter-title">Marques</h4>
                        <div class="filter-group">
                            <select name="marque" class="filter-select" onchange="this.form.submit()">
                                <option value="">Toutes les marques</option>
                                <?php foreach ($marques as $m): ?>
                                    <option value="<?php echo htmlspecialchars($m['marque']); ?>" 
                                        <?php echo $marque == $m['marque'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($m['marque']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="filter-section">
                        <h4 class="filter-title">Prix</h4>
                        <div class="filter-group">
                            <div class="price-inputs">
                                <input type="number" name="min_price" class="filter-input" placeholder="Min" 
                                       value="<?php echo $min_price > 0 ? $min_price : ''; ?>">
                                <input type="number" name="max_price" class="filter-input" placeholder="Max" 
                                       value="<?php echo $max_price > 0 ? $max_price : ''; ?>">
                            </div>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary btn-block">Appliquer les filtres</button>
                    <a href="catalogue.php" class="btn btn-secondary btn-block">Réinitialiser</a>
                </form>
            </aside>

            <div class="catalog-content">
                <div class="catalog-actions">
                    <div class="sort-options">
                        <label for="sort">Trier par:</label>
                        <select name="sort" id="sort" class="sort-select" onchange="updateSort(this.value)">
                            <option value="date_desc" <?php echo $sort == 'date_desc' ? 'selected' : ''; ?>>Nouveautés</option>
                            <option value="price_asc" <?php echo $sort == 'price_asc' ? 'selected' : ''; ?>>Prix croissant</option>
                            <option value="price_desc" <?php echo $sort == 'price_desc' ? 'selected' : ''; ?>>Prix décroissant</option>
                            <option value="name_asc" <?php echo $sort == 'name_asc' ? 'selected' : ''; ?>>Nom A-Z</option>
                            <option value="name_desc" <?php echo $sort == 'name_desc' ? 'selected' : ''; ?>>Nom Z-A</option>
                        </select>
                    </div>
                </div>

                <div class="products-grid">
                    <?php if (empty($products)): ?>
                        <div class="no-products">
                            <div class="no-products-icon">📦</div>
                            <h3>Aucun produit trouvé</h3>
                            <p>Essayez de modifier vos critères de recherche ou de filtrage.</p>
                            <a href="catalogue.php" class="btn btn-primary">Voir tous les produits</a>
                        </div>
                    <?php else: ?>
                        <?php foreach ($products as $product): ?>
                            <?php
                            $prix_affichage = $product['est_en_promotion'] && $product['prix_promotion'] 
                                ? $product['prix_promotion'] 
                                : $product['prix'];
                            $prix_original = $product['est_en_promotion'] && $product['prix_promotion'] 
                                ? $product['prix'] 
                                : null;
                            
                            $stock_class = '';
                            if ($product['quantite_stock'] > 10) {
                                $stock_class = 'in-stock';
                            } elseif ($product['quantite_stock'] > 0) {
                                $stock_class = 'low-stock';
                            } else {
                                $stock_class = 'out-of-stock';
                            }
                            ?>
                            <a href="produit.php?id=<?php echo $product['id']; ?>" class="product-card">
                                <?php if ($product['est_en_promotion'] && $product['prix_promotion']): ?>
                                    <div class="product-badge">PROMO</div>
                                <?php endif; ?>
                                
                                <div class="product-image">
                                    <img src="<?php echo htmlspecialchars($product['image_principale'] ?: 'https://via.placeholder.com/300x200/3498db/ffffff?text=Image+Non+Disponible'); ?>" 
                                         alt="<?php echo htmlspecialchars($product['titre']); ?>">
                                </div>
                                
                                <div class="product-card-content">
                                    <div class="product-card-category">
                                        <?php echo htmlspecialchars($product['categorie_nom'] ?? 'Non catégorisé'); ?>
                                    </div>
                                    <h3 class="product-card-title"><?php echo htmlspecialchars($product['titre']); ?></h3>
                                    <div class="product-card-reference">Ref: <?php echo htmlspecialchars($product['reference']); ?></div>
                                    
                                    <div class="product-card-price">
                                        <?php echo number_format($prix_affichage, 0, ',', ' '); ?> FCFA
                                        <?php if ($prix_original): ?>
                                            <span class="product-card-original-price">
                                                <?php echo number_format($prix_original, 0, ',', ' '); ?> FCFA
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="product-card-stock <?php echo $stock_class; ?>">
                                        <?php
                                        if ($product['quantite_stock'] > 10) {
                                            echo '✓ En stock';
                                        } elseif ($product['quantite_stock'] > 0) {
                                            echo '⚠ Stock limité';
                                        } else {
                                            echo '✗ Rupture de stock';
                                        }
                                        ?>
                                    </div>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <?php if ($total_pages > 1): ?>
                <div class="pagination">
                    <?php if ($page > 1): ?>
                        <a href="<?php echo buildPaginationUrl($page - 1); ?>">&laquo; Précédent</a>
                    <?php endif; ?>
                    
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <?php if ($i == $page): ?>
                            <span class="current"><?php echo $i; ?></span>
                        <?php else: ?>
                            <a href="<?php echo buildPaginationUrl($i); ?>"><?php echo $i; ?></a>
                        <?php endif; ?>
                    <?php endfor; ?>
                    
                    <?php if ($page < $total_pages): ?>
                        <a href="<?php echo buildPaginationUrl($page + 1); ?>">Suivant &raquo;</a>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <footer>
        <div class="container">
            <div class="footer-content">
                <div class="footer-column">
                    <h3>MonShop</h3>
                    <p>Votre boutique en ligne de confiance pour tous vos besoins en électronique et high-tech.</p>
                </div>
                <div class="footer-column">
                    <h3>Catégories</h3>
                    <ul>
                        <li><a href="catalogue.php?categorie=1">Téléphones</a></li>
                        <li><a href="#">Ordinateurs</a></li>
                        <li><a href="#">Accessoires</a></li>
                        <li><a href="#">Électroménager</a></li>
                    </ul>
                </div>
                <div class="footer-column">
                    <h3>Aide</h3>
                    <ul>
                        <li><a href="contact.php">Contactez-nous</a></li>
                        <li><a href="#">Livraison</a></li>
                        <li><a href="#">Retours</a></li>
                        <li><a href="#">FAQ</a></li>
                    </ul>
                </div>
                <div class="footer-column">
                    <h3>Informations</h3>
                    <ul>
                        <li><a href="#">À propos</a></li>
                        <li><a href="#">Conditions générales</a></li>
                        <li><a href="#">Politique de confidentialité</a></li>
                        <li><a href="#">Mentions légales</a></li>
                    </ul>
                </div>
            </div>
            <div class="copyright">
                &copy; 2025 MonShop. Tous droits réservés.
            </div>
        </div>
    </footer>

    <script>
        function updateSort(sortValue) {
            const url = new URL(window.location.href);
            url.searchParams.set('sort', sortValue);
            window.location.href = url.toString();
        }
        
        function buildPaginationUrl(page) {
            const url = new URL(window.location.href);
            url.searchParams.set('page', page);
            return url.toString();
        }
        
        // Mettre à jour l'URL pour la pagination (fonction utilisée en PHP)
        <?php
        function buildPaginationUrl($page) {
            $params = $_GET;
            $params['page'] = $page;
            return 'catalogue.php?' . http_build_query($params);
        }
        ?>
    </script>
</body>
</html>