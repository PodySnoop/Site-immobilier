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

// Vérifier que les paramètres requis sont présents
if (!isset($_POST['id']) || !isset($_POST['type']) || !isset($_POST['statut'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Paramètres manquants']);
    exit();
}

$bien_id = intval($_POST['id']);
$type_bien = $_POST['type'] === 'location' ? 'location' : 'vente';
$nouveau_statut = $_POST['statut'] === 'actif' ? 'actif' : 'inactif';
$utilisateur_id = $_SESSION['utilisateur_id'];

try {
    // Vérifier que le bien appartient bien à l'utilisateur
    $table = $type_bien === 'location' ? 'biens_location' : 'biens_vente';
    $check_sql = "SELECT id FROM $table WHERE id = ? AND utilisateur_id = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("ii", $bien_id, $user_id);
    $check_stmt->execute();
    $result = $check_stmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception("Bien non trouvé ou vous n'avez pas les droits pour le modifier.");
    }
    
    $check_stmt->close();
    
    // Mettre à jour le statut du bien
    $update_sql = "UPDATE $table SET statut = ? WHERE id = ?";
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bind_param("si", $nouveau_statut, $bien_id);
    
    if (!$update_stmt->execute()) {
        throw new Exception("Erreur lors de la mise à jour du statut du bien.");
    }
    
    $update_stmt->close();
    
    // Journaliser l'action
    $action = $nouveau_statut === 'actif' ? 'activation' : 'désactivation';
    $log_sql = "INSERT INTO logs_actions (utilisateur_id, action, details, date_creation) 
                VALUES (?, 'changement_statut_bien', ?, NOW())";
    $log_stmt = $conn->prepare($log_sql);
    $details = json_encode([
        'bien_id' => $bien_id,
        'type_bien' => $type_bien,
        'nouveau_statut' => $nouveau_statut
    ]);
    $log_stmt->bind_param("is", $user_id, $details);
    $log_stmt->execute();
    $log_stmt->close();
    
    echo json_encode([
        'success' => true, 
        'message' => 'Statut du bien mis à jour avec succès',
        'nouveau_statut' => $nouveau_statut
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false, 
        'message' => $e->getMessage()
    ]);
}

$conn->close();
?>
