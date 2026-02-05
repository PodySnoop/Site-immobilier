<?php
session_start();
require_once('connexion.php');

header('Content-Type: application/json');

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['utilisateur_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Non autorisé']);
    exit();
}

// Vérifier si l'ID de réservation est fourni
if (!isset($_POST['reservation_id']) || empty($_POST['reservation_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID de réservation manquant']);
    exit();
}

$reservation_id = intval($_POST['reservation_id']);
$user_id = $_SESSION['utilisateur_id'];

try {
    // Commencer une transaction
    $conn->begin_transaction();

    // Récupérer l'email de l'utilisateur connecté
    $user_sql = "SELECT email FROM utilisateurs WHERE id = ?";
    $user_stmt = $conn->prepare($user_sql);
    $user_stmt->bind_param("i", $user_id);
    $user_stmt->execute();
    $user_result = $user_stmt->get_result();
    
    if ($user_result->num_rows === 0) {
        throw new Exception("Utilisateur non trouvé.");
    }
    
    $user = $user_result->fetch_assoc();
    $user_email = $user['email'];
    $user_stmt->close();

    // Vérifier que la réservation appartient bien à l'utilisateur
    $check_sql = "SELECT id, date_visite, statut FROM reservation_visite 
                  WHERE id = ? AND email = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("is", $reservation_id, $user_email);
    $check_stmt->execute();
    $result = $check_stmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception("Réservation non trouvée ou vous n'avez pas les droits pour l'annuler.");
    }
    
    $reservation = $result->fetch_assoc();
    $check_stmt->close();
    
    // Vérifier que la réservation n'est pas déjà annulée
    if ($reservation['statut'] === 'annulee') {
        throw new Exception("Cette réservation a déjà été annulée.");
    }
    
    // Vérifier que la date de visite n'est pas déjà passée
    $now = new DateTime();
    $dateVisite = new DateTime($reservation['date_visite']);
    
    if ($dateVisite <= $now) {
        throw new Exception("Impossible d'annuler une visite dont la date est déjà passée.");
    }
    
    // Mettre à jour le statut de la réservation
    $update_sql = "UPDATE reservation_visite 
                  SET statut = 'annulee', 
                      notes = CONCAT(IFNULL(notes, ''), '\n\nAnnulée le ", NOW(), " par l\'utilisateur.')
                  WHERE id = ?";
    
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bind_param("i", $reservation_id);
    
    if (!$update_stmt->execute()) {
        throw new Exception("Erreur lors de la mise à jour de la réservation.");
    }
    
    $update_stmt->close();
    
    // Ici, vous pourriez ajouter une logique pour envoyer un email de confirmation
    // et/ou mettre à jour d'autres parties du système
    
    // Valider la transaction
    $conn->commit();
    
    echo json_encode([
        'success' => true, 
        'message' => 'Votre réservation de visite a été annulée avec succès.'
    ]);
    
} catch (Exception $e) {
    // Annuler la transaction en cas d'erreur
    if (isset($conn)) {
        $conn->rollback();
    }
    
    http_response_code(400);
    echo json_encode([
        'success' => false, 
        'message' => $e->getMessage()
    ]);
}

if (isset($conn)) {
    $conn->close();
}
?>
