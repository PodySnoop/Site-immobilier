<?php
session_start();
require_once 'includes/db.php';

if (!isset($_SESSION['utilisateur_id'])) {
    die("Utilisateur non connecté.");
}

$id_utilisateur = $_SESSION['utilisateur_id'];
$id_annonce = intval($_POST['id_annonce'] ?? 0);
$type_annonce = $_POST['type_annonce'] ?? '';

if ($id_annonce > 0 && in_array($type_annonce, ['location', 'vente'])) {
    // Supprimer le favori
    $stmt = $conn->prepare("DELETE FROM favoris WHERE id_utilisateur = ? AND id_annonce = ? AND type_annonce = ?");
    if ($stmt) {
        $stmt->bind_param("iis", $id_utilisateur, $id_annonce, $type_annonce);
        $stmt->execute();

        // Traçabilité : enregistrer l'action dans la table activites
        $log = $conn->prepare("INSERT INTO activites (acteur_id, action, cible_id, cible_type, date_action) VALUES (?, 'suppression_favori', ?, ?, NOW())");
        if ($log) {
            $log->bind_param("iis", $id_utilisateur, $id_annonce, $type_annonce);
            $log->execute();
        }
    } else {
        echo "Erreur SQL : " . $conn->error;
        exit;
    }
}

// Redirection vers la page des favoris
header("Location: mes_favoris.php");
exit;