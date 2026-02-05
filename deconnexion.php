<?php
// Démarrer la session si pas déjà démarrée
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Détruire toutes les données de session
$_SESSION = array();

// Supprimer le cookie de session
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), 
              '', 
              time() - 42000,
              $params["path"], 
              $params["domain"],
              $params["secure"], 
              $params["httponly"]
    );
}

// Supprimer le cookie remember_me
if (isset($_COOKIE['remember_token'])) {
    setcookie('remember_token', 
              '', 
              time() - 3600, 
              "/Pollux_immobilier",
              "",
              false,
              true
    );
}

// Détruire la session
session_destroy();

// Rediriger vers la page d'accueil
if (!headers_sent()) {
    header('Location: /Pollux_immobilier/index.php?deconnexion=1');
    exit();
} else {
    echo "<script>window.location.href='/Pollux_immobilier/index.php?deconnexion=1';</script>";
    exit();
}
?>
