<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Vérifie si l'utilisateur est connecté
function estConnecte() {
    return isset($_SESSION['utilisateur_id']);
}

// Vérifie si l'utilisateur est un administrateur
function estAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

// Redirige vers la page de connexion si l'utilisateur n'est pas connecté
function verifierConnexion() {
    if (!estConnecte() && !headers_sent()) {
        header("Location: /Pollux_immobilier/utilisateurs/login.php");
        exit();
    }
}

// Vérifie si l'utilisateur est admin, sinon redirige
function verifierAdmin() {
    verifierConnexion();
    if (!estAdmin() && !headers_sent()) {
        header("Location: /Pollux_immobilier/utilisateurs/profil_utilisateur.php");
        exit();
    }
}
?>
