<?php
require_once '../config/db.php';

header('Content-Type: application/json');

if (!isset($_GET['q']) || strlen($_GET['q']) < 2) {
    echo json_encode([]);
    exit;
}

$searchTerm = $_GET['q'];

try {
    $sql = "SELECT id, titre, reference, marque, image_principale, prix 
            FROM article 
            WHERE est_actif = 1 
            AND (titre LIKE :search OR marque LIKE :search)
            ORDER BY 
                CASE 
                    WHEN titre LIKE :search_exact THEN 1
                    WHEN titre LIKE :search_start THEN 2
                    ELSE 3
                END,
                titre
            LIMIT 8";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        'search' => '%' . $searchTerm . '%',
        'search_exact' => $searchTerm,
        'search_start' => $searchTerm . '%'
    ]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode($results);
    
} catch (PDOException $e) {
    error_log("Erreur suggestions recherche: " . $e->getMessage());
    echo json_encode([]);
}
?>