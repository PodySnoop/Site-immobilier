<?php
session_start();

// Vérifier que l'utilisateur est connecté et est un admin
if (!isset($_SESSION['utilisateur_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

if (isset($_GET['id'])) {


    require_once '../includes/db.php';
    
    $message_id = intval($_GET['id']);

    
    // Supprimer le message
    $query = "DELETE FROM contact_messages WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $message_id);
    $stmt->execute();
    
 if ($stmt->affected_rows > 0) {
        // Enregistrer l'activité
        $user_id = $_SESSION['utilisateur_id'];
        $action = "Message supprimé";
        $details = "ID du message: $message_id";
        $conn->query("INSERT INTO activites (utilisateur_id, action, details) VALUES ($user_id, '$action', '$details')");
        
        $_SESSION['success'] = "Le message a été supprimé avec succès.";
    } else {
        $_SESSION['error'] = "Une erreur est survenue lors de la suppression du message.";
    }
    
    $stmt->close();
    $conn->close();
}

// Rediriger vers la page précédente
header("Location: " . $_SERVER['HTTP_REFERER'] ?? 'contact.php');
exit();