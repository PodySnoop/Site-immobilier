<?php
session_start();

// Vérifier que l'utilisateur est connecté et est un admin
if (!isset($_SESSION['utilisateur_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['message_id'])) {
    require_once '../includes/db.php';
    
    $message_id = intval($_POST['message_id']);
    
    // Mettre à jour le statut du message
    $query = "UPDATE contact_messages SET is_read = 1, updated_at = NOW() WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $message_id);
    $stmt->execute();
    
    if ($stmt->affected_rows > 0) {

        // Enregistrer l'activité
        $user_id = $_SESSION['utilisateur_id'];
        $action = "Message marqué comme lu";
        $details = "ID du message: $message_id";
        $conn->query("INSERT INTO activites (utilisateur_id, action, details) VALUES ($user_id, '$action', '$details')");
        
        $_SESSION['success'] = "Le message a été marqué comme lu.";
    } else {
        $_SESSION['error'] = "Une erreur est survenue lors de la mise à jour du message.";
    }
        header("Location: contact.php?success=1");
    } else {
        header("Location: contact.php?error=update_failed");
    }
    
    $stmt->close();
    $conn->close();
    exit();


// Rediriger si l'accès n'est pas autorisé
header("Location: contact.php");
exit();
