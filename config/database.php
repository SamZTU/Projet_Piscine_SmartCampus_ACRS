<?php
// Connexion à la base de données MySQL (MAMP)
$host     = 'localhost';
$dbname   = 'smartcampus';
$username = 'root';
$password = 'root'; // mot de passe MAMP par défaut

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Erreur de connexion : " . $e->getMessage());
}
