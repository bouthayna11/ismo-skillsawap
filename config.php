<?php
$host = 'localhost';
$port = '8888';
$dbname = 'ismo_skillswap';
$username = 'root';
$password = 'root';
try {
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbname;", $username, $password);
} catch (PDOException $e) {
    die("Erreur de connexion : " . $e->getMessage());
}
?>