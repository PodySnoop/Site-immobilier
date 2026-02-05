<?php
// Démarrer la session si pas déjà démarrée
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Inclure le fichier de connexion à la base de données
require_once 'connexion.php';

// Initialiser les messages d'erreur
if (!isset($_SESSION['error_message'])) {
    $_SESSION['error_message'] = '';
}

// Vérifier si le formulaire a été soumis
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Récupérer et nettoyer les données du formulaire
    $email = trim($_POST['email'] ?? '');
    $mot_de_passe = $_POST['mot_de_passe'] ?? '';
    $se_souvenir = isset($_POST['remember']);

    // Vérification des champs obligatoires
    if (empty($email) || empty($mot_de_passe)) {
        $_SESSION['error_message'] = "Veuillez remplir tous les champs.";
        if (!headers_sent()) {
            header("Location: /Pollux_immobilier/utilisateurs/login.php");
            exit();
        }
    }

    // Préparer et exécuter la requête pour récupérer l'utilisateur
    $req = mysqli_prepare($conn, "SELECT * FROM utilisateurs WHERE email = ?");
    
    if ($req === false) {
        $_SESSION['error_message'] = "Erreur lors de la préparation de la requête.";
        if (!headers_sent()) {
            header("Location: /Pollux_immobilier/utilisateurs/login.php");
            exit();
        }
    }
    
    mysqli_stmt_bind_param($req, "s", $email);
    
    if (!mysqli_stmt_execute($req)) {
        $_SESSION['error_message'] = "Erreur lors de l'exécution de la requête.";
        if (!headers_sent()) {
            header("Location: /Pollux_immobilier/utilisateurs/login.php");
            exit();
        }
    }
    
    $result = mysqli_stmt_get_result($req);
    $user = mysqli_fetch_assoc($result);

    // Vérifier si l'utilisateur existe et que le mot de passe est correct
    if ($user && password_verify($mot_de_passe, $user['mot_de_passe'])) {
        // Mettre à jour la session
        $_SESSION['utilisateur_id'] = $user['ID'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['nom'] = $user['nom'];
        $_SESSION['email'] = $user['email'];

        // Gestion du "Se souvenir de moi"
        if ($se_souvenir) {
            $token = bin2hex(random_bytes(32));
            $updateStmt = mysqli_prepare($conn, "UPDATE utilisateurs SET remember_token = ? WHERE ID = ?");
            
            if ($updateStmt) {
                mysqli_stmt_bind_param($updateStmt, "si", $token, $user['ID']);
                if (mysqli_stmt_execute($updateStmt)) {
                    setcookie(
                        'remember_token',
                        $token,
                        time() + (86400 * 30), // 30 jours
                        "/Pollux_immobilier",
                        "",
                        false,
                        true
                    );
                }
            }
        }

        // Redirection après connexion réussie
        if (!headers_sent()) {
            if ($user['role'] === 'admin') {
                header("Location: /Pollux_immobilier/utilisateurs/admin.php");
            } else {
                header("Location: /Pollux_immobilier/utilisateurs/profil_utilisateur.php");
            }
            exit();
        }
    } else {
        $_SESSION['error_message'] = "Email ou mot de passe incorrect.";
        if (!headers_sent()) {
            header("Location: /Pollux_immobilier/utilisateurs/login.php");
            exit();
        }
    }
}

// Redirection par défaut si accès direct au fichier
if (!headers_sent()) {
    header("Location: /Pollux_immobilier/utilisateurs/login.php");
    exit();
}

// Fermer la connexion à la base de données
mysqli_close($conn);
?>