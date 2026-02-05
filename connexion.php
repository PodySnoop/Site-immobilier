<?php
$host = 'localhost';
$utilisateur = 'root';
$user = $utilisateur;
$pass = '';
$db   = 'bd_pollux';

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Erreur de connexion : " . $conn->connect_error);
}
?>