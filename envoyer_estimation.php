<?php
require_once 'utilisateurs/connexion.php';
require_once 'includes/functions.php';

function envoyerEstimationClient($estimation_id) {
    global $conn;
    
    // Récupération des infos de l'estimation
    $stmt = $conn->prepare("
        SELECT e.*, u.prenom, u.nom 
        FROM estimations e
        LEFT JOIN utilisateurs u ON e.utilisateur_id = u.id
        WHERE e.id = ?
    ");
    $stmt->bind_param("i", $estimation_id);
    $stmt->execute();
    $estimation = $stmt->get_result()->fetch_assoc();
    
    if (!$estimation) {
        throw new Exception("Estimation introuvable");
    }
    
    // Préparation de l'email
    $to = $estimation['email'];
    $subject = "Votre estimation immobilière - Pollux Immobilier";
    
    $message = "Bonjour " . $estimation['prenom'] . ",\n\n";
    $message .= "Nous avons le plaisir de vous communiquer l'estimation de votre bien :\n\n";
    $message .= "Type de bien : " . $estimation['type_bien'] . "\n";
    $message .= "Adresse : " . $estimation['adresse'] . "\n";
    $message .= "Surface : " . $estimation['surface'] . " m²\n";
    $message .= "Estimation : " . number_format($estimation['estimation_validee'], 0, ',', ' ') . " €\n\n";
    $message .= "Un conseiller vous contactera sous 48h pour discuter de la suite à donner.\n\n";
    $message .= "Cordialement,\nL'équipe Pollux Immobilier";
    
    $headers = "From: contact@pollux-immobilier.com\r\n";
    $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
    
    // Envoi de l'email
    if (!mail($to, $subject, $message, $headers)) {
        error_log("Erreur lors de l'envoi de l'email à " . $to);
        return false;
    }
    
    return true;
}