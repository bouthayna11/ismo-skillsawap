<?php
$host = '127.0.0.1';
$port = '3307';
$dbname = 'ismo_skillswap';
$username = 'root';
$password = '';
try {
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbname;charset=utf8", $username, $password);
} catch (PDOException $e) {
    die("Erreur de connexion : " . $e->getMessage());
}
?>