<?php
$servername = "https://cena-database.onrender.com";
$dbname = "cena_db";
$username = "enigme";
$password = "Enigme96@";

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