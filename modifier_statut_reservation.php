<?php
session_start();

if (!isset($_SESSION['utilisateur']) || $_SESSION['utilisateur']['role'] !== 'admin') {
    header('Location: ../index.php');
    exit();
}

session_start();

if (!isset($_SESSION['utilisateur']) || $_SESSION['utilisateur']['role'] !== 'admin') {
    // Redirection ou message d'erreur
    header("Location: ../login.php?erreur=Accès refusé");
    exit();
}
require_once '../includes/db.php';

 //Mise à jour du statut 
if (isset($_POST['reservation_id'], $_POST['nouveau_statut'])) {
    $id = intval($_POST['reservation_id']);
    $statut = $_POST['nouveau_statut'];

    $stmt = $pdo->prepare("UPDATE reservations SET status = ? WHERE id = ?");
    $stmt->execute([$statut, $id]);

    //Redirection avec message de confirmation
    header("Location: liste_reservations.php?message=modifie");
    exit();
} else {
    header("Location: liste_reservations.php?message=erreur");
    exit();
}