<?php
session_start();
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    
    try {
        $stmt = $pdo->prepare("SELECT * FROM utilisateurs WHERE email = ? AND est_actif = 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user && password_verify($password, $user['mot_de_passe'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_nom'] = $user['nom'];
            $_SESSION['user_prenom'] = $user['prenom'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_role'] = $user['role'];
            
            // Mettre à jour la dernière connexion
            $pdo->prepare("UPDATE utilisateurs SET derniere_connexion = NOW() WHERE id = ?")
                ->execute([$user['id']]);
            
            header('Location: index.php');
            exit();
        } else {
            $error = "Email ou mot de passe incorrect";
        }
    } catch(PDOException $e) {
        $error = "Erreur de connexion: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Connexion Admin - E-commerce</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container vh-100 d-flex align-items-center justify-content-center">
        <div class="card shadow" style="width: 400px;">
            <div class="card-body p-5">
                <h2 class="text-center mb-4">
                    <i class="fas fa-store text-primary"></i><br>
                    Connexion Admin
                </h2>
                
                <?php if (isset($error)): ?>
                    <div class="alert alert-danger"><?= $error ?></div>
                <?php endif; ?>
                
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Mot de passe</label>
                        <input type="password" name="password" class="form-control" required>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Se connecter</button>
                </form>
                
                <div class="text-center mt-3">
                    <small class="text-muted">Utilisez vos identifiants administrateur</small>
                </div>
            </div>
        </div>
    </div>
</body>
</html>