<?php
$username = "sql109.infinityfree.com";
$servername = "f0_40133473";
$password = "o8rCfqeOTkH";
$dbname = "if0_40133473_cena_db";

try {
    $pdo = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => "Erreur serveur lors de la modification du candidat"
    ]);
}
?>