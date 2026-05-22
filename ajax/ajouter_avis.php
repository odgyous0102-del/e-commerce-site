<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit;
}

if (!isset($_SESSION['utilisateur_id'])) {
    echo json_encode(['success' => false, 'message' => 'Veuillez vous connecter pour poster un avis']);
    exit;
}

$article_id = isset($_POST['article_id']) ? intval($_POST['article_id']) : 0;
$note = isset($_POST['note']) ? intval($_POST['note']) : 0;
$commentaire = isset($_POST['commentaire']) ? trim($_POST['commentaire']) : '';

if ($article_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Produit invalide']);
    exit;
}

if ($note < 1 || $note > 5) {
    echo json_encode(['success' => false, 'message' => 'Note invalide']);
    exit;
}

if (empty($commentaire)) {
    echo json_encode(['success' => false, 'message' => 'Veuillez saisir un commentaire']);
    exit;
}

if (strlen($commentaire) < 10) {
    echo json_encode(['success' => false, 'message' => 'Le commentaire doit contenir au moins 10 caractères']);
    exit;
}

try {
    // Vérifier si l'utilisateur a déjà posté un avis pour ce produit
    $sql_check = "SELECT id FROM avis WHERE article_id = ? AND utilisateur_id = ?";
    $stmt_check = $pdo->prepare($sql_check);
    $stmt_check->execute([$article_id, $_SESSION['utilisateur_id']]);
    
    if ($stmt_check->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Vous avez déjà donné votre avis sur ce produit']);
        exit;
    }

    // Insérer l'avis (par défaut non approuvé pour modération)
    $sql = "INSERT INTO avis (article_id, utilisateur_id, note, commentaire, est_approuve) 
            VALUES (?, ?, ?, ?, 1)"; // Mettez 0 pour nécessiter une modération
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$article_id, $_SESSION['utilisateur_id'], $note, $commentaire]);

    echo json_encode([
        'success' => true, 
        'message' => 'Votre avis a été publié avec succès!'
        // Si modération nécessaire : 'message' => 'Votre avis a été soumis et est en attente de modération.'
    ]);

} catch (Exception $e) {
    error_log("Erreur ajout avis: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erreur lors de l\'ajout de l\'avis']);
}
?>