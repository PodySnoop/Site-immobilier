<?php
session_start();
require_once '../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_SESSION['utilisateur_id'])) {
        $_SESSION['flash'] = "Veuillez vous connecter pour commenter.";
        header("Location: login.php");
        exit();
    }

    $contenu = trim($_POST['contenu']);
    $utilisateur_id = $_SESSION['utilisateur_id']; // Utilisation de l'ID utilisateur
    $annonce_id = (int)$_POST['annonce_id'];
    $type_annonce = $_POST['type_annonce'] ?? 'location'; // Par défaut à 'location' si non spécifié


    // Vérifier que l'ID est valide avant redirection

    if ($annonce_id <= 0) {
        $_SESSION['flash'] = "❌ ID d'annonce invalide";
        header("Location: ../index.php"); // Rediriger vers une page valide
        exit();
    }

     if (empty($contenu)) {
        $_SESSION['flash'] = "❌ Le commentaire ne peut pas être vide.";
        header("Location: annonce.php?ID=" . $annonce_id . "&type=" . $type_annonce);
        exit();
    }
    $stmt = $conn->prepare("INSERT INTO commentaires (contenu, utilisateur_id, annonce_id, type_annonce) VALUES (?, ?, ?, ?)");
    if ($stmt === false) {
        die("Erreur de préparation : " . $conn->error);
    }

    $stmt->bind_param("siis", $contenu, $utilisateur_id, $annonce_id, $type_annonce);

    if ($stmt->execute()) {

        
        $_SESSION['flash'] = "✅ Commentaire ajouté avec succès.";
    } else {
        $_SESSION['flash'] = "❌ Erreur lors de l'ajout du commentaire : " . $stmt->error;
    }

    $stmt->close();
    header("Location: annonce.php?ID=" . $annonce_id . "&type=" . $type_annonce);
    exit();
}
?>