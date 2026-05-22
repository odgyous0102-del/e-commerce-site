<?php
// config.php
$host = '127.0.0.1:3306';
$dbname = 'e_commerce';
$username = 'root'; // À adapter selon votre configuration
$password = ''; // À adapter selon votre configuration

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Erreur de connexion : " . $e->getMessage());
}
?>