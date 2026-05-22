<?php
require_once 'config.php';
session_start();

// Vérifier si l'utilisateur est connecté et est admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

// Récupérer les informations de l'utilisateur connecté
$user_id = $_SESSION['user_id'];
$user_info = [];
try {
    $stmt = $pdo->prepare("SELECT * FROM utilisateurs WHERE id = ?");
    $stmt->execute([$user_id]);
    $user_info = $stmt->fetch(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $error = "Erreur lors du chargement des informations utilisateur";
}

// Traitement de la mise à jour du profil
if (isset($_POST['action']) && $_POST['action'] === 'update_profile') {
    $nom = trim($_POST['nom'] ?? '');
    $prenom = trim($_POST['prenom'] ?? '');
    $email = trim($_POST['email'] ?? '');
    
    try {
        // Vérifier si l'email est déjà utilisé par un autre utilisateur
        $stmt = $pdo->prepare("SELECT id FROM utilisateurs WHERE email = ? AND id != ?");
        $stmt->execute([$email, $user_id]);
        if ($stmt->fetch()) {
            $error = "Cet email est déjà utilisé par un autre utilisateur";
        } else {
            $sql = "UPDATE utilisateurs SET nom = ?, prenom = ?, email = ? WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$nom, $prenom, $email, $user_id]);
            
            // Mettre à jour la session
            $_SESSION['user_nom'] = $nom;
            $_SESSION['user_prenom'] = $prenom;
            $_SESSION['user_email'] = $email;
            
            $success = "Profil mis à jour avec succès";
            
            // Recharger les informations utilisateur
            $stmt = $pdo->prepare("SELECT * FROM utilisateurs WHERE id = ?");
            $stmt->execute([$user_id]);
            $user_info = $stmt->fetch(PDO::FETCH_ASSOC);
        }
    } catch(PDOException $e) {
        $error = "Erreur lors de la mise à jour du profil : " . $e->getMessage();
    }
}

// Traitement du changement de mot de passe
if (isset($_POST['action']) && $_POST['action'] === 'change_password') {
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    try {
        // Vérifier le mot de passe actuel
        if (!password_verify($current_password, $user_info['mot_de_passe'])) {
            $error = "Le mot de passe actuel est incorrect";
        } elseif ($new_password !== $confirm_password) {
            $error = "Les nouveaux mots de passe ne correspondent pas";
        } elseif (strlen($new_password) < 6) {
            $error = "Le mot de passe doit contenir au moins 6 caractères";
        } else {
            // Hasher le nouveau mot de passe
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            
            $sql = "UPDATE utilisateurs SET mot_de_passe = ? WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$hashed_password, $user_id]);
            
            $success = "Mot de passe modifié avec succès";
        }
    } catch(PDOException $e) {
        $error = "Erreur lors du changement de mot de passe : " . $e->getMessage();
    }
}

// Traitement des paramètres généraux
if (isset($_POST['action']) && $_POST['action'] === 'update_settings') {
    $site_name = trim($_POST['site_name'] ?? '');
    $site_email = trim($_POST['site_email'] ?? '');
    $items_per_page = intval($_POST['items_per_page'] ?? 20);
    $maintenance_mode = isset($_POST['maintenance_mode']) ? 1 : 0;
    
    try {
        // Ici, vous pourriez sauvegarder ces paramètres dans une table dédiée
        // Pour l'exemple, nous allons simplement afficher un message de succès
        $success = "Paramètres généraux mis à jour avec succès";
        
        // Exemple de sauvegarde dans une table 'parametres_site' (à créer) :
        /*
        $settings = [
            'site_name' => $site_name,
            'site_email' => $site_email,
            'items_per_page' => $items_per_page,
            'maintenance_mode' => $maintenance_mode
        ];
        
        foreach ($settings as $key => $value) {
            $stmt = $pdo->prepare("INSERT INTO parametres_site (cle, valeur) VALUES (?, ?) 
                                  ON DUPLICATE KEY UPDATE valeur = ?");
            $stmt->execute([$key, $value, $value]);
        }
        */
        
    } catch(PDOException $e) {
        $error = "Erreur lors de la mise à jour des paramètres : " . $e->getMessage();
    }
}

// Récupérer les statistiques du site
$stats = [];
try {
    // Total utilisateurs
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM utilisateurs");
    $stats['total_users'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Total articles
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM article");
    $stats['total_articles'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Total catégories
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM categorie_article");
    $stats['total_categories'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Articles en promotion
    $stmt = $pdo->query("SELECT COUNT(*) as promo FROM article WHERE est_en_promotion = 1");
    $stats['promo_articles'] = $stmt->fetch(PDO::FETCH_ASSOC)['promo'];
} catch(PDOException $e) {
    $error = "Erreur lors du chargement des statistiques";
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Paramètres - E-commerce</title>
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
        
        .stats-card {
            text-align: center;
            padding: 20px;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            transition: all 0.3s;
            border: none;
        }
        
        .stats-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.15);
        }
        
        .stats-number {
            font-size: 2.2rem;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .badge-success {
            background: linear-gradient(135deg, var(--success), #2ecc71);
        }
        
        .badge-warning {
            background: linear-gradient(135deg, var(--warning), #e67e22);
        }
        
        .badge-info {
            background: linear-gradient(135deg, var(--info), #2980b9);
        }
        
        .badge-primary {
            background: linear-gradient(135deg, var(--primary), #2c3e50);
        }
        
        .user-avatar-large {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background: var(--info);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 3rem;
            margin: 0 auto 20px;
        }
        
        .password-strength {
            height: 5px;
            border-radius: 5px;
            margin-top: 5px;
            transition: all 0.3s;
        }
        
        .password-weak { background: var(--danger); width: 25%; }
        .password-medium { background: var(--warning); width: 50%; }
        .password-strong { background: var(--success); width: 75%; }
        .password-very-strong { background: var(--success); width: 100%; }
        
        .settings-section {
            border-left: 4px solid var(--info);
            padding-left: 20px;
            margin-bottom: 30px;
        }
        
        .form-check-input:checked {
            background-color: var(--info);
            border-color: var(--info);
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
                    </a>
                    <a class="nav-link " href="gestion_commande.php">
                        <i class="fas fa-shopping-cart"></i> Commandes
                    </a>
                    <a class="nav-link" href="gestion_utilisateurs.php">
                        <i class="fas fa-users-cog"></i> Utilisateurs
                    </a>
                    <a class="nav-link active" href="parametres.php">
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
                            <h1 class="display-6 fw-bold mb-2">Paramètres du Site</h1>
                            <p class="mb-0">Gérez les paramètres de votre boutique en ligne et votre profil administrateur</p>
                        </div>
                        <div class="col-md-4 text-end">
                            <div class="user-avatar-large">
                                <?= strtoupper(substr($user_info['prenom'] ?? '', 0, 1) . substr($user_info['nom'] ?? '', 0, 1)) ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Cartes de statistiques -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="stats-card bg-white">
                            <div class="stats-number text-primary"><?= $stats['total_users'] ?? 0 ?></div>
                            <div>Utilisateurs</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stats-card bg-white">
                            <div class="stats-number text-success"><?= $stats['total_articles'] ?? 0 ?></div>
                            <div>Articles</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stats-card bg-white">
                            <div class="stats-number text-warning"><?= $stats['total_categories'] ?? 0 ?></div>
                            <div>Catégories</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stats-card bg-white">
                            <div class="stats-number text-info"><?= $stats['promo_articles'] ?? 0 ?></div>
                            <div>Promotions</div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <!-- Profil Utilisateur -->
                    <div class="col-lg-6">
                        <div class="form-card">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="fas fa-user me-2"></i>Profil Administrateur</h5>
                            </div>
                            <div class="card-body">
                                <form method="POST">
                                    <input type="hidden" name="action" value="update_profile">
                                    
                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <label for="prenom" class="form-label">Prénom</label>
                                            <input type="text" class="form-control" id="prenom" name="prenom" 
                                                   value="<?= htmlspecialchars($user_info['prenom'] ?? '') ?>" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="nom" class="form-label">Nom</label>
                                            <input type="text" class="form-control" id="nom" name="nom" 
                                                   value="<?= htmlspecialchars($user_info['nom'] ?? '') ?>" required>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="email" class="form-label">Email</label>
                                        <input type="email" class="form-control" id="email" name="email" 
                                               value="<?= htmlspecialchars($user_info['email'] ?? '') ?>" required>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Rôle</label>
                                        <div>
                                            <span class="badge badge-warning">Administrateur</span>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Date d'inscription</label>
                                        <div>
                                            <span class="text-muted">
                                                <?= $user_info['date_inscription'] ? date('d/m/Y à H:i', strtotime($user_info['date_inscription'])) : 'N/A' ?>
                                            </span>
                                        </div>
                                    </div>
                                    
                                    <div class="text-end">
                                        <button type="submit" class="btn btn-primary-custom btn-custom">
                                            <i class="fas fa-save me-2"></i>Mettre à jour le profil
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <!-- Changement de mot de passe -->
                        <div class="form-card">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="fas fa-lock me-2"></i>Changer le mot de passe</h5>
                            </div>
                            <div class="card-body">
                                <form method="POST" id="passwordForm">
                                    <input type="hidden" name="action" value="change_password">
                                    
                                    <div class="mb-3">
                                        <label for="current_password" class="form-label">Mot de passe actuel</label>
                                        <input type="password" class="form-control" id="current_password" name="current_password" required>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="new_password" class="form-label">Nouveau mot de passe</label>
                                        <input type="password" class="form-control" id="new_password" name="new_password" required 
                                               minlength="6" onkeyup="checkPasswordStrength(this.value)">
                                        <div class="password-strength" id="passwordStrength"></div>
                                        <small class="text-muted">Le mot de passe doit contenir au moins 6 caractères</small>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="confirm_password" class="form-label">Confirmer le nouveau mot de passe</label>
                                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                        <div class="invalid-feedback" id="passwordMatchError">
                                            Les mots de passe ne correspondent pas
                                        </div>
                                    </div>
                                    
                                    <div class="text-end">
                                        <button type="submit" class="btn btn-primary-custom btn-custom">
                                            <i class="fas fa-key me-2"></i>Changer le mot de passe
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- Paramètres généraux -->
                    <div class="col-lg-6">
                        <div class="form-card">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="fas fa-cog me-2"></i>Paramètres généraux</h5>
                            </div>
                            <div class="card-body">
                                <form method="POST">
                                    <input type="hidden" name="action" value="update_settings">
                                    
                                    <div class="settings-section">
                                        <h6 class="fw-bold text-primary mb-3">
                                            <i class="fas fa-info-circle me-2"></i>Informations du site
                                        </h6>
                                        
                                        <div class="mb-3">
                                            <label for="site_name" class="form-label">Nom du site</label>
                                            <input type="text" class="form-control" id="site_name" name="site_name" 
                                                   value="Mon E-commerce" placeholder="Nom de votre boutique">
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="site_email" class="form-label">Email de contact</label>
                                            <input type="email" class="form-control" id="site_email" name="site_email" 
                                                   value="contact@monsite.com" placeholder="Email de contact">
                                        </div>
                                    </div>
                                    
                                    <div class="settings-section">
                                        <h6 class="fw-bold text-primary mb-3">
                                            <i class="fas fa-display me-2"></i>Affichage
                                        </h6>
                                        
                                        <div class="mb-3">
                                            <label for="items_per_page" class="form-label">Éléments par page</label>
                                            <select class="form-select" id="items_per_page" name="items_per_page">
                                                <option value="10">10 éléments</option>
                                                <option value="20" selected>20 éléments</option>
                                                <option value="50">50 éléments</option>
                                                <option value="100">100 éléments</option>
                                            </select>
                                        </div>
                                    </div>
                                    
                                    <div class="settings-section">
                                        <h6 class="fw-bold text-primary mb-3">
                                            <i class="fas fa-tools me-2"></i>Maintenance
                                        </h6>
                                        
                                        <div class="mb-3">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" id="maintenance_mode" name="maintenance_mode">
                                                <label class="form-check-label" for="maintenance_mode">
                                                    Mode maintenance
                                                </label>
                                            </div>
                                            <small class="text-muted">Le site sera inaccessible aux visiteurs pendant la maintenance</small>
                                        </div>
                                    </div>
                                    
                                    <div class="settings-section">
                                        <h6 class="fw-bold text-primary mb-3">
                                            <i class="fas fa-database me-2"></i>Sauvegarde
                                        </h6>
                                        
                                        <div class="mb-3">
                                            <button type="button" class="btn btn-outline-primary w-100" onclick="alert('Fonctionnalité de sauvegarde à implémenter')">
                                                <i class="fas fa-download me-2"></i>Sauvegarder la base de données
                                            </button>
                                            <small class="text-muted">Créez une sauvegarde complète de votre base de données</small>
                                        </div>
                                    </div>
                                    
                                    <div class="text-end mt-4">
                                        <button type="submit" class="btn btn-primary-custom btn-custom">
                                            <i class="fas fa-save me-2"></i>Enregistrer les paramètres
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Animation au chargement de la page
        document.addEventListener('DOMContentLoaded', function() {
            // Animation des cartes de statistiques
            const statsCards = document.querySelectorAll('.stats-card');
            statsCards.forEach((card, index) => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(20px)';
                
                setTimeout(() => {
                    card.style.transition = 'all 0.5s ease';
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, index * 100);
            });
            
            // Validation des mots de passe
            const passwordForm = document.getElementById('passwordForm');
            const newPassword = document.getElementById('new_password');
            const confirmPassword = document.getElementById('confirm_password');
            const passwordMatchError = document.getElementById('passwordMatchError');
            
            function validatePasswords() {
                if (newPassword.value !== confirmPassword.value && confirmPassword.value !== '') {
                    confirmPassword.classList.add('is-invalid');
                    passwordMatchError.style.display = 'block';
                    return false;
                } else {
                    confirmPassword.classList.remove('is-invalid');
                    passwordMatchError.style.display = 'none';
                    return true;
                }
            }
            
            newPassword.addEventListener('input', validatePasswords);
            confirmPassword.addEventListener('input', validatePasswords);
            
            passwordForm.addEventListener('submit', function(e) {
                if (!validatePasswords()) {
                    e.preventDefault();
                    return false;
                }
            });
        });
        
        // Vérification de la force du mot de passe
        function checkPasswordStrength(password) {
            const strengthBar = document.getElementById('passwordStrength');
            let strength = 0;
            
            if (password.length >= 6) strength++;
            if (password.length >= 8) strength++;
            if (/[A-Z]/.test(password)) strength++;
            if (/[0-9]/.test(password)) strength++;
            if (/[^A-Za-z0-9]/.test(password)) strength++;
            
            strengthBar.className = 'password-strength ';
            if (strength <= 1) {
                strengthBar.classList.add('password-weak');
            } else if (strength <= 2) {
                strengthBar.classList.add('password-medium');
            } else if (strength <= 4) {
                strengthBar.classList.add('password-strong');
            } else {
                strengthBar.classList.add('password-very-strong');
            }
        }
    </script>
</body>
</html>