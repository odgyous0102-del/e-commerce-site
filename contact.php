<?php
// Activation de la session pour les messages d'erreur/succès
session_start();

// Configuration
define('DESTINATAIRE', 'contact@votre-boutique.com');
define('SITE_NOM', 'TechShop');
define('SUJET_SITE', 'Nouveau message depuis le formulaire de contact');

// Traitement du formulaire
$erreurs = [];
$succes = "";
$donnees = [
    'nom' => '',
    'email' => '',
    'telephone' => '',
    'sujet' => '',
    'message' => '',
    'newsletter' => false
];

// Catégories de sujet prédéfinies
$categories_sujet = [
    'question' => 'Question sur un produit',
    'commande' => 'Suivi de commande',
    'livraison' => 'Problème de livraison',
    'retour' => 'Retour produit',
    'facturation' => 'Question de facturation',
    'autre' => 'Autre demande'
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Protection CSRF
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $erreurs[] = "Erreur de sécurité. Veuillez réessayer.";
    }

    // Nettoyage et validation des données
    $donnees['nom'] = htmlspecialchars(trim($_POST['nom'] ?? ''));
    $donnees['email'] = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
    $donnees['telephone'] = htmlspecialchars(trim($_POST['telephone'] ?? ''));
    $donnees['sujet'] = htmlspecialchars(trim($_POST['sujet'] ?? ''));
    $donnees['categorie'] = $_POST['categorie'] ?? '';
    $donnees['message'] = htmlspecialchars(trim($_POST['message'] ?? ''));
    $donnees['newsletter'] = isset($_POST['newsletter']);

    // Validation avancée
    if (empty($donnees['nom']) || strlen($donnees['nom']) < 2) {
        $erreurs[] = "Le nom doit contenir au moins 2 caractères";
    }

    if (empty($donnees['email']) || !filter_var($donnees['email'], FILTER_VALIDATE_EMAIL)) {
        $erreurs[] = "Veuillez saisir une adresse email valide";
    }

    if (!empty($donnees['telephone']) && !preg_match('/^[0-9+\-\s()]{10,20}$/', $donnees['telephone'])) {
        $erreurs[] = "Le format du téléphone n'est pas valide";
    }

    if (empty($donnees['categorie']) || !array_key_exists($donnees['categorie'], $categories_sujet)) {
        $erreurs[] = "Veuillez sélectionner une catégorie";
    }

    if (empty($donnees['sujet']) || strlen($donnees['sujet']) < 5) {
        $erreurs[] = "Le sujet doit contenir au moins 5 caractères";
    }

    if (empty($donnees['message']) || strlen($donnees['message']) < 10) {
        $erreurs[] = "Le message doit contenir au moins 10 caractères";
    }

    // Si pas d'erreurs, envoi d'email
    if (empty($erreurs)) {
        $sujet_email = "[Contact] " . $categories_sujet[$donnees['categorie']] . " - " . $donnees['sujet'];
        
        $headers = "From: " . DESTINATAIRE . "\r\n";
        $headers .= "Reply-To: " . $donnees['email'] . "\r\n";
        $headers .= "Content-Type: text/html; charset=utf-8\r\n";
        $headers .= "X-Mailer: PHP/" . phpversion();

        $message_html = "
        <!DOCTYPE html>
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #2563eb; color: white; padding: 20px; text-align: center; }
                .content { background: #f9f9f9; padding: 20px; border: 1px solid #ddd; }
                .field { margin-bottom: 10px; }
                .label { font-weight: bold; color: #2563eb; }
                .footer { margin-top: 20px; padding: 10px; background: #ecf0f1; text-align: center; font-size: 12px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h2>Nouveau message de contact</h2>
                    <p>" . SITE_NOM . "</p>
                </div>
                <div class='content'>
                    <div class='field'><span class='label'>Catégorie:</span> " . $categories_sujet[$donnees['categorie']] . "</div>
                    <div class='field'><span class='label'>Sujet:</span> " . $donnees['sujet'] . "</div>
                    <div class='field'><span class='label'>Nom:</span> " . $donnees['nom'] . "</div>
                    <div class='field'><span class='label'>Email:</span> " . $donnees['email'] . "</div>
                    <div class='field'><span class='label'>Téléphone:</span> " . ($donnees['telephone'] ?: 'Non renseigné') . "</div>
                    <div class='field'><span class='label'>Message:</span></div>
                    <div style='background: white; padding: 15px; border-left: 4px solid #2563eb; margin-top: 10px;'>
                        " . nl2br($donnees['message']) . "
                    </div>
                </div>
                <div class='footer'>
                    <p>Message envoyé le " . date('d/m/Y à H:i') . " depuis le formulaire de contact</p>
                </div>
            </div>
        </body>
        </html>";

        if (mail(DESTINATAIRE, $sujet_email, $message_html, $headers)) {
            $succes = "Votre message a été envoyé avec succès ! Nous vous répondrons dans les plus brefs délais.";
            
            // Réinitialisation des champs
            $donnees = array_fill_keys(array_keys($donnees), '');
            $donnees['newsletter'] = false;
            
            // Régénération du token CSRF
            unset($_SESSION['csrf_token']);
        } else {
            $erreurs[] = "Une erreur technique est survenue lors de l'envoi. Veuillez réessayer ou nous contacter directement.";
        }
    }
}

// Génération du token CSRF
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact - Support Technique | <?php echo SITE_NOM; ?></title>
    <meta name="description" content="Contactez l'équipe support de TechShop pour toute question technique, suivi de commande ou assistance produit.">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- AOS CSS -->
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    
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

        .contact-hero {
            background: var(--gradient-primary);
            color: white;
            padding: 100px 0 80px;
            position: relative;
            overflow: hidden;
        }

        .contact-hero::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1000 1000"><polygon fill="rgba(255,255,255,0.05)" points="0,1000 1000,0 1000,1000"/></svg>');
            background-size: cover;
        }

        .contact-hero h1 {
            font-size: 3.5rem;
            font-weight: 700;
            margin-bottom: 1rem;
            text-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }

        .contact-hero p {
            font-size: 1.25rem;
            opacity: 0.9;
            margin-bottom: 0;
        }

        .contact-section {
            padding: 80px 0;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            position: relative;
        }

        .contact-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 24px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .contact-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 30px 80px rgba(0, 0, 0, 0.15);
        }

        .form-section {
            padding: 3rem;
        }

        .info-section {
            background: var(--gradient-primary);
            color: white;
            padding: 3rem;
            position: relative;
            overflow: hidden;
        }

        .info-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1000 1000"><circle fill="rgba(255,255,255,0.1)" cx="200" cy="200" r="200"/><circle fill="rgba(255,255,255,0.05)" cx="800" cy="800" r="300"/></svg>');
        }

        .section-title {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 2rem;
            color: var(--dark-color);
            position: relative;
        }

        .section-title::after {
            content: '';
            position: absolute;
            bottom: -10px;
            left: 0;
            width: 60px;
            height: 4px;
            background: var(--gradient-accent);
            border-radius: 2px;
        }

        .info-section .section-title {
            color: white;
        }

        .info-section .section-title::after {
            background: var(--accent-color);
        }

        .form-group {
            margin-bottom: 1.5rem;
            position: relative;
        }

        .form-label {
            font-weight: 600;
            color: var(--dark-color);
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .form-label.required::after {
            content: '*';
            color: #ef4444;
            font-weight: bold;
        }

        .form-control {
            border: 2px solid var(--border-color);
            border-radius: 12px;
            padding: 0.75rem 1rem;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: rgba(255, 255, 255, 0.8);
        }

        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
            background: white;
            transform: translateY(-2px);
        }

        .form-control-lg {
            padding: 1rem 1.25rem;
            font-size: 1.1rem;
        }

        .form-select {
            border: 2px solid var(--border-color);
            border-radius: 12px;
            padding: 0.75rem 1rem;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: rgba(255, 255, 255, 0.8);
        }

        .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
            background: white;
        }

        textarea.form-control {
            min-height: 150px;
            resize: vertical;
        }

        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 1rem;
            background: rgba(37, 99, 235, 0.05);
            border-radius: 12px;
            border: 1px solid var(--border-color);
        }

        .checkbox-group input[type="checkbox"] {
            width: 20px;
            height: 20px;
            border-radius: 6px;
            border: 2px solid var(--border-color);
            cursor: pointer;
        }

        .checkbox-group label {
            margin: 0;
            cursor: pointer;
            font-weight: 500;
            color: var(--dark-color);
        }

        .btns-primary {
            background: var(--gradient-primary);
            border: none;
            border-radius: 12px;
            padding: 1rem 2rem;
            font-size: 1.1rem;
            font-weight: 600;
            color: white;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
            width: 100%;
        }

        .btns-primary::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: left 0.6s ease;
        }

        .btns-primary:hover::before {
            left: 100%;
        }

        .btns-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 30px rgba(37, 99, 235, 0.4);
        }

        .info-item {
            display: flex;
            align-items: flex-start;
            gap: 1.25rem;
            margin-bottom: 2rem;
            padding-bottom: 2rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
            position: relative;
            z-index: 2;
        }

        .info-item:last-child {
            border-bottom: none;
            margin-bottom: 0;
        }

        .info-icon {
            background: rgba(255, 255, 255, 0.2);
            color: white;
            width: 60px;
            height: 60px;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            font-size: 1.5rem;
            transition: all 0.3s ease;
        }

        .info-item:hover .info-icon {
            background: rgba(255, 255, 255, 0.3);
            transform: scale(1.1);
        }

        .info-content h3 {
            color: white;
            font-size: 1.25rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .info-content p {
            color: rgba(255, 255, 255, 0.9);
            margin-bottom: 0.5rem;
        }

        .hours-grid {
            display: grid;
            gap: 0.75rem;
            margin-top: 0.5rem;
        }

        .hour-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.5rem 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .hour-item:last-child {
            border-bottom: none;
        }

        .hour-item span:last-child {
            font-weight: 600;
            color: white;
        }

        .social-links {
            display: flex;
            gap: 1rem;
            margin-top: 1rem;
        }

        .social-link {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 45px;
            height: 45px;
            background: rgba(255, 255, 255, 0.2);
            color: white;
            border-radius: 12px;
            text-decoration: none;
            transition: all 0.3s ease;
            font-size: 1.25rem;
        }

        .social-link:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: translateY(-3px);
            color: white;
        }

        .alert {
            border: none;
            border-radius: 16px;
            padding: 1.25rem 1.5rem;
            margin-bottom: 2rem;
            border-left: 6px solid;
            backdrop-filter: blur(10px);
            animation: slideInRight 0.5s ease-out;
        }

        .alert-danger {
            background: linear-gradient(135deg, #fef2f2, #fee2e2);
            border-left-color: #ef4444;
            color: #991b1b;
        }

        .alert-success {
            background: linear-gradient(135deg, #f0fdf4, #dcfce7);
            border-left-color: var(--success-color);
            color: #065f46;
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

        .floating-shapes {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: 1;
        }

        .floating-shape {
            position: absolute;
            background: var(--gradient-accent);
            border-radius: 50%;
            opacity: 0.1;
            animation: float 6s ease-in-out infinite;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0) rotate(0deg); }
            50% { transform: translateY(-20px) rotate(180deg); }
        }

        /* Responsive */
        @media (max-width: 768px) {
            .contact-hero {
                padding: 80px 0 60px;
            }

            .contact-hero h1 {
                font-size: 2.5rem;
            }

            .contact-section {
                padding: 60px 0;
            }

            .form-section,
            .info-section {
                padding: 2rem;
            }

            .section-title {
                font-size: 1.75rem;
            }
        }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>

    <!-- Hero Section -->
    <section class="contact-hero">
        <div class="container">
            <div class="row justify-content-center text-center">
                <div class="col-lg-8" data-aos="fade-up">
                    <h1>Support Technique</h1>
                    <p class="lead">Notre équipe d'experts est à votre écoute pour répondre à toutes vos questions techniques</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Contact Section -->
    <section class="contact-section">
        <div class="floating-shapes">
            <div class="floating-shape" style="width: 100px; height: 100px; top: 10%; left: 5%; animation-delay: 0s;"></div>
            <div class="floating-shape" style="width: 150px; height: 150px; top: 60%; right: 10%; animation-delay: 2s;"></div>
            <div class="floating-shape" style="width: 80px; height: 80px; bottom: 20%; left: 15%; animation-delay: 4s;"></div>
        </div>
        
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-10">
                    <div class="contact-card">
                        <div class="row g-0">
                            <!-- Formulaire -->
                            <div class="col-lg-7">
                                <div class="form-section">
                                    <h2 class="section-title" data-aos="fade-right">Envoyez-nous un message</h2>

                                    <?php if (!empty($erreurs)): ?>
                                        <div class="alert alert-danger" data-aos="fade-up">
                                            <div class="d-flex align-items-center">
                                                <i class="fas fa-exclamation-triangle me-2 fs-5"></i>
                                                <div>
                                                    <strong class="d-block mb-1">Veuillez corriger les erreurs suivantes :</strong>
                                                    <ul class="mb-0 mt-2">
                                                        <?php foreach ($erreurs as $erreur): ?>
                                                            <li><?= $erreur ?></li>
                                                        <?php endforeach; ?>
                                                    </ul>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endif; ?>

                                    <?php if ($succes): ?>
                                        <div class="alert alert-success" data-aos="fade-up">
                                            <div class="d-flex align-items-center">
                                                <i class="fas fa-check-circle me-2 fs-5"></i>
                                                <span class="fw-medium"><?= $succes ?></span>
                                            </div>
                                        </div>
                                    <?php endif; ?>

                                    <form method="POST" id="contactForm" data-aos="fade-up" data-aos-delay="200">
                                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                        
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="nom" class="form-label required">
                                                        <i class="fas fa-user"></i>Nom complet
                                                    </label>
                                                    <input type="text" class="form-control form-control-lg" id="nom" name="nom" value="<?= $donnees['nom'] ?>" required>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="email" class="form-label required">
                                                        <i class="fas fa-envelope"></i>Adresse email
                                                    </label>
                                                    <input type="email" class="form-control form-control-lg" id="email" name="email" value="<?= $donnees['email'] ?>" required>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="form-group">
                                            <label for="telephone" class="form-label">
                                                <i class="fas fa-phone"></i>Numéro de téléphone
                                            </label>
                                            <input type="tel" class="form-control form-control-lg" id="telephone" name="telephone" value="<?= $donnees['telephone'] ?>">
                                        </div>

                                        <div class="form-group">
                                            <label for="categorie" class="form-label required">
                                                <i class="fas fa-tag"></i>Catégorie de demande
                                            </label>
                                            <select class="form-select form-control-lg" id="categorie" name="categorie" required>
                                                <option value="">Sélectionnez une option</option>
                                                <?php foreach ($categories_sujet as $value => $label): ?>
                                                    <option value="<?= $value ?>" <?= $donnees['categorie'] === $value ? 'selected' : '' ?>>
                                                        <?= $label ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>

                                        <div class="form-group">
                                            <label for="sujet" class="form-label required">
                                                <i class="fas fa-pen"></i>Sujet du message
                                            </label>
                                            <input type="text" class="form-control form-control-lg" id="sujet" name="sujet" value="<?= $donnees['sujet'] ?>" required>
                                        </div>

                                        <div class="form-group">
                                            <label for="message" class="form-label required">
                                                <i class="fas fa-comment"></i>Votre message
                                            </label>
                                            <textarea class="form-control form-control-lg" id="message" name="message" rows="6" required><?= $donnees['message'] ?></textarea>
                                        </div>

                                        <div class="form-group">
                                            <div class="checkbox-group">
                                                <input type="checkbox" id="newsletter" name="newsletter" <?= $donnees['newsletter'] ? 'checked' : '' ?>>
                                                <label for="newsletter">
                                                    Je souhaite m'abonner à la newsletter pour recevoir les offres spéciales
                                                </label>
                                            </div>
                                        </div>

                                        <button type="submit" class="btn btns-primary">
                                            <i class="fas fa-paper-plane me-2"></i>Envoyer le message
                                        </button>
                                    </form>
                                </div>
                            </div>

                            <!-- Informations -->
                            <div class="col-lg-5">
                                <div class="info-section">
                                    <h2 class="section-title" data-aos="fade-left">Nos coordonnées</h2>
                                    
                                    <div class="info-item" data-aos="fade-left" data-aos-delay="100">
                                        <div class="info-icon">
                                            <i class="fas fa-map-marker-alt"></i>
                                        </div>
                                        <div class="info-content">
                                            <h3>Adresse</h3>
                                            <p>123 Avenue du Commerce<br>75001 Paris, France</p>
                                        </div>
                                    </div>

                                    <div class="info-item" data-aos="fade-left" data-aos-delay="200">
                                        <div class="info-icon">
                                            <i class="fas fa-phone"></i>
                                        </div>
                                        <div class="info-content">
                                            <h3>Téléphone</h3>
                                            <p>01 23 45 67 89</p>
                                            <small>Lundi au Vendredi, 9h-18h</small>
                                        </div>
                                    </div>

                                    <div class="info-item" data-aos="fade-left" data-aos-delay="300">
                                        <div class="info-icon">
                                            <i class="fas fa-envelope"></i>
                                        </div>
                                        <div class="info-content">
                                            <h3>Email</h3>
                                            <p><?= DESTINATAIRE ?></p>
                                        </div>
                                    </div>

                                    <div class="info-item" data-aos="fade-left" data-aos-delay="400">
                                        <div class="info-icon">
                                            <i class="fas fa-clock"></i>
                                        </div>
                                        <div class="info-content">
                                            <h3>Horaires d'ouverture</h3>
                                            <div class="hours-grid">
                                                <div class="hour-item"><span>Lundi - Vendredi</span><span>9h00 - 18h00</span></div>
                                                <div class="hour-item"><span>Samedi</span><span>10h00 - 17h00</span></div>
                                                <div class="hour-item"><span>Dimanche</span><span>Fermé</span></div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="info-item" data-aos="fade-left" data-aos-delay="500">
                                        <div class="info-icon">
                                            <i class="fas fa-share-alt"></i>
                                        </div>
                                        <div class="info-content">
                                            <h3>Suivez-nous</h3>
                                            <div class="social-links">
                                                <a href="#" class="social-link"><i class="fab fa-facebook-f"></i></a>
                                                <a href="#" class="social-link"><i class="fab fa-twitter"></i></a>
                                                <a href="#" class="social-link"><i class="fab fa-instagram"></i></a>
                                                <a href="#" class="social-link"><i class="fab fa-linkedin-in"></i></a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <?php include 'footer.php'; ?>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    
    <script>
        // Initialiser AOS
        AOS.init({
            duration: 800,
            once: true,
            offset: 50
        });

        // Validation côté client améliorée
        document.getElementById('contactForm').addEventListener('submit', function(e) {
            let valid = true;
            const requiredFields = this.querySelectorAll('[required]');
            
            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    valid = false;
                    field.style.borderColor = '#ef4444';
                    // Ajouter une animation de shake
                    field.style.animation = 'shake 0.5s ease-in-out';
                    setTimeout(() => {
                        field.style.animation = '';
                    }, 500);
                } else {
                    field.style.borderColor = '#e2e8f0';
                }
            });

            // Validation email
            const emailField = document.getElementById('email');
            if (emailField.value && !isValidEmail(emailField.value)) {
                valid = false;
                emailField.style.borderColor = '#ef4444';
                showFieldError(emailField, 'Veuillez saisir une adresse email valide');
            }

            // Validation téléphone
            const phoneField = document.getElementById('telephone');
            if (phoneField.value && !isValidPhone(phoneField.value)) {
                valid = false;
                phoneField.style.borderColor = '#ef4444';
                showFieldError(phoneField, 'Le format du téléphone n\'est pas valide');
            }
            
            if (!valid) {
                e.preventDefault();
                // Animation du formulaire
                this.style.animation = 'shake 0.5s ease-in-out';
                setTimeout(() => {
                    this.style.animation = '';
                }, 500);
            }
        });

        function isValidEmail(email) {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return emailRegex.test(email);
        }

        function isValidPhone(phone) {
            const phoneRegex = /^[0-9+\-\s()]{10,20}$/;
            return phoneRegex.test(phone);
        }

        function showFieldError(field, message) {
            // Retirer les anciens messages d'erreur
            const existingError = field.parentNode.querySelector('.field-error');
            if (existingError) {
                existingError.remove();
            }

            // Ajouter le nouveau message d'erreur
            const errorDiv = document.createElement('div');
            errorDiv.className = 'field-error text-danger small mt-1';
            errorDiv.innerHTML = `<i class="fas fa-exclamation-circle me-1"></i>${message}`;
            field.parentNode.appendChild(errorDiv);
        }

        // Animation de shake
        const style = document.createElement('style');
        style.textContent = `
            @keyframes shake {
                0%, 100% { transform: translateX(0); }
                25% { transform: translateX(-5px); }
                75% { transform: translateX(5px); }
            }
            .field-error {
                animation: fadeIn 0.3s ease-in;
            }
            @keyframes fadeIn {
                from { opacity: 0; transform: translateY(-10px); }
                to { opacity: 1; transform: translateY(0); }
            }
        `;
        document.head.appendChild(style);

        // Amélioration UX : focus states
        document.querySelectorAll('.form-control, .form-select').forEach(field => {
            field.addEventListener('focus', function() {
                this.parentNode.classList.add('focused');
            });
            
            field.addEventListener('blur', function() {
                this.parentNode.classList.remove('focused');
            });
        });
    </script>
</body>
</html>