<?php
// Démarrer la session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Inclure la connexion à la base de données
require_once 'connexion.php';

// Réinitialiser le token dans la base de données si l'utilisateur est connecté
if (!empty($_SESSION['utilisateur_id'])) {
    $update = $conn->prepare("UPDATE utilisateurs SET remember_token = NULL WHERE ID = ?");
    $update->bind_param("i", $_SESSION['utilisateur_id']);
    $update->execute();
}

// Détruire toutes les données de session
$_SESSION = array();

// Supprimer le cookie de session
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Détruire la session
session_destroy();

// Supprimer le cookie remember_token
setcookie('remember_token', '', time() - 3600, "/Pollux_immobilier");

// Rediriger vers la page de connexion
header("Location: login.php");
exit();
?>