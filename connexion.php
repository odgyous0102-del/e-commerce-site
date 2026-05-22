<?php
session_start();

// Rediriger si déjà connecté
if (isset($_SESSION['utilisateur_id'])) {
    header('Location: index.php');
    exit;
}

$erreur = '';

// Traitement du formulaire de connexion
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $mot_de_passe = $_POST['mot_de_passe'] ?? '';

    // Validation des champs
    if (empty($email) || empty($mot_de_passe)) {
        $erreur = "Veuillez remplir tous les champs.";
    } else {
        try {
            // Connexion à la base de données - REMPLACEZ CES INFORMATIONS PAR LES VÔTRES
            $host = '127.0.0.1:3306';
            $dbname = 'e_commerce';
            $username = 'root'; // Remplacez par votre utilisateur MySQL
            $password = ''; // Remplacez par votre mot de passe MySQL
            
            $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            // Rechercher l'utilisateur par email
            $stmt = $pdo->prepare("SELECT * FROM utilisateurs WHERE email = ? AND est_actif = 1");
            $stmt->execute([$email]);
            $user= $stmt->fetch(PDO::FETCH_ASSOC);
            

            if ($user) {
                // Vérifier le mot de passe
                // Note: Certains utilisateurs dans votre base ont des mots de passe en clair ('admin123')
                // et d'autres ont des hachages. Nous devons gérer les deux cas.
                
                $mot_de_passe_correct = false;
                
                // Vérifier si c'est un mot de passe haché
                if (password_verify($mot_de_passe, $user['mot_de_passe'])) {
                    $mot_de_passe_correct = true;
                } 
                // Vérifier si c'est un mot de passe en clair (pour la transition)
                elseif ($user['mot_de_passe'] === $mot_de_passe) {
                    $mot_de_passe_correct = true;
                    // Optionnel : hacher le mot de passe pour la prochaine fois
                    $hashed_password = password_hash($mot_de_passe, PASSWORD_DEFAULT);
                    $update_pwd = $pdo->prepare("UPDATE utilisateurs SET mot_de_passe = ? WHERE id = ?");
                    $update_pwd->execute([$hashed_password, $user['id']]);
                }

                if ($mot_de_passe_correct) {
                    // Mettre à jour la dernière connexion
                    $update_stmt = $pdo->prepare("UPDATE utilisateurs SET derniere_connexion = NOW() WHERE id = ?");
                    $update_stmt->execute([$user['id']]);

                    // Créer la session
                    $_SESSION['utilisateur_id'] = $user['id'];
                    $_SESSION['user_email'] = $user['email'];
                    $_SESSION['user_nom'] = $user['nom'];
                    $_SESSION['user_prenom'] = $user['prenom'];
                    $_SESSION['user_role'] = $user['role'];
                    $_SESSION['user_id'] = $user['id'];
            
           
           
            
            
                    // Message de succès
                    $_SESSION['flash_message'] = "Connexion réussie ! Bienvenue " . $user['prenom'] . ".";
                    $_SESSION['flash_type'] = 'success';

                    // Redirection selon le rôle
                    if ($user['role'] === 'admin') {
                        header('Location: admin/index.php');
                    } else {
                        header('Location: index.php');
                    }
                    exit;
                } else {
                    $erreur = "Mot de passe incorrect.";
                }
            } else {
                $erreur = "Aucun compte trouvé avec cet email.";
            }
        } catch(PDOException $e) {
            $erreur = "Erreur de connexion à la base de données. Vérifiez la configuration.";
            // Pour le débogage, vous pouvez afficher le message détaillé :
            // $erreur = "Erreur de connexion : " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion - E-Commerce</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- Style personnalisé -->
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
        }
        
        .login-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .login-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem;
            text-align: center;
        }
        
        .login-body {
            padding: 2rem;
        }
        
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            padding: 0.75rem;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        
        .social-login {
            border-top: 1px solid #dee2e6;
            padding-top: 1.5rem;
            margin-top: 1.5rem;
        }
        
        .config-help {
            background: #f8f9fa;
            border-radius: 5px;
            padding: 10px;
            margin-top: 10px;
            font-size: 0.9em;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-5">
                <div class="login-card">
                    <!-- En-tête -->
                    <div class="login-header">
                        <h2><i class="fas fa-store"></i> E-Commerce</h2>
                        <p class="mb-0">Connectez-vous à votre compte</p>
                    </div>
                    
                    <!-- Corps du formulaire -->
                    <div class="login-body">
                        <!-- Messages d'erreur -->
                        <?php if (!empty($erreur)): ?>
                            <div class="alert alert-danger alert-dismissible fade show">
                                <i class="fas fa-exclamation-triangle"></i> <?= htmlspecialchars($erreur) ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                
                                <?php if (strpos($erreur, 'configuration') !== false): ?>
                                <div class="config-help mt-2">
                                    <strong>Configuration requise :</strong><br>
                                    Modifiez les variables <code>$username</code> et <code>$password</code> dans le fichier <code>connexion.php</code> avec vos identifiants MySQL.
                                </div>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>

                        <!-- Messages de succès (redirection depuis autre page) -->
                        <?php if (isset($_SESSION['flash_message'])): ?>
                            <div class="alert alert-<?= $_SESSION['flash_type'] ?? 'info' ?> alert-dismissible fade show">
                                <?= htmlspecialchars($_SESSION['flash_message']) ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                            <?php 
                            unset($_SESSION['flash_message']);
                            unset($_SESSION['flash_type']);
                            ?>
                        <?php endif; ?>

                        <!-- Formulaire de connexion -->
                        <form method="POST" action="">
                            <!-- Email -->
                            <div class="mb-3">
                                <label for="email" class="form-label">
                                    <i class="fas fa-envelope"></i> Adresse email
                                </label>
                                <input type="email" 
                                       class="form-control" 
                                       id="email" 
                                       name="email" 
                                       value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" 
                                       required 
                                       placeholder="votre@email.com">
                            </div>

                            <!-- Mot de passe -->
                            <div class="mb-3">
                                <label for="mot_de_passe" class="form-label">
                                    <i class="fas fa-lock"></i> Mot de passe
                                </label>
                                <div class="position-relative">
                                    <input type="password" 
                                           class="form-control" 
                                           id="mot_de_passe" 
                                           name="mot_de_passe" 
                                           required 
                                           placeholder="Votre mot de passe">
                                    <button type="button" class="btn btn-outline-secondary position-absolute top-50 end-0 translate-middle-y me-2" onclick="togglePassword()">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                            </div>

                            <!-- Options -->
                            <div class="mb-3 form-check">
                                <input type="checkbox" class="form-check-input" id="se_souvenir">
                                <label class="form-check-label" for="se_souvenir">
                                    Se souvenir de moi
                                </label>
                                <a href="mot-de-passe-oublie.php" class="float-end">
                                    Mot de passe oublié ?
                                </a>
                            </div>

                            <!-- Bouton de connexion -->
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="fas fa-sign-in-alt"></i> Se connecter
                                </button>
                            </div>
                        </form>

                        <!-- Comptes de test (pour le développement) -->
                        <div class="mt-4 p-3 bg-light rounded">
                            <h6 class="mb-2"><i class="fas fa-info-circle"></i> Comptes de test :</h6>
                            <div class="small">
                                <strong>Admin :</strong> admin@example.com / password<br>
                                <strong>Client :</strong> adm@gmail.com / admin123
                            </div>
                        </div>

                        <!-- Séparateur -->
                        <div class="social-login text-center">
                            <p class="text-muted mb-3">Ou connectez-vous avec</p>
                            <div class="d-grid gap-2 d-sm-flex justify-content-sm-center">
                                <button type="button" class="btn btn-outline-danger">
                                    <i class="fab fa-google"></i> Google
                                </button>
                                <button type="button" class="btn btn-outline-primary">
                                    <i class="fab fa-facebook"></i> Facebook
                                </button>
                            </div>
                        </div>

                        <!-- Lien vers l'inscription -->
                        <div class="text-center mt-4">
                            <p class="mb-0">Vous n'avez pas de compte ?
                                <a href="inscription.php" class="text-decoration-none fw-bold">
                                    Créer un compte
                                </a>
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Retour à l'accueil -->
                <div class="text-center mt-3">
                    <a href="index.php" class="text-white text-decoration-none">
                        <i class="fas fa-arrow-left"></i> Retour à l'accueil
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Script personnalisé -->
    <script>
        // Animation au chargement
        document.addEventListener('DOMContentLoaded', function() {
            const card = document.querySelector('.login-card');
            card.style.transform = 'translateY(20px)';
            card.style.opacity = '0';
            
            setTimeout(() => {
                card.style.transition = 'all 0.5s ease';
                card.style.transform = 'translateY(0)';
                card.style.opacity = '1';
            }, 100);
        });

        // Afficher/masquer le mot de passe
        function togglePassword() {
            const passwordInput = document.getElementById('mot_de_passe');
            const icon = document.querySelector('#mot_de_passe ~ button i');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }

        // Remplissage automatique pour le développement
        document.addEventListener('DOMContentLoaded', function() {
            // En développement, pré-remplir avec un compte de test
            const emailInput = document.getElementById('email');
            if (!emailInput.value) {
                // emailInput.value = 'admin@example.com'; // Décommentez pour pré-remplir
            }
        });
    </script>
</body>
</html>