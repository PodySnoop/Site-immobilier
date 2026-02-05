<?php
// Démarrer la session
session_start();

// Inclure la configuration et la connexion à la base de données
require_once 'includes/db.php';

// Initialisation des variables
$message = '';
$error = '';
$success = false;

// Vérifier si le formulaire a été soumis
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Récupération et validation des données du formulaire
    $bien_id = intval($_POST['bien_id'] ?? 0);
    $bien_titre = trim($_POST['bien_titre'] ?? '');
    $nom = trim($_POST['nom'] ?? '');
    $email = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
    $message_text = trim($_POST['message'] ?? '');
    $date_visite = $_POST['date_visite'] ?? '';
    $heure_debut = $_POST['heure_debut'] ?? '';
    $duree = intval($_POST['duree'] ?? 0);
    $reservation_id = intval($_POST['reservation_id'] ?? 0); // utile si tu modifies une réservation existante
}
    // Validation des champs obligatoires
    if (empty($nom) || !$email || empty($message_text) || $bien_id <= 0) {
        $error = 'Tous les champs obligatoires doivent être remplis correctement.';
    } else {
        try {
            // Vérifier si la table messages_contact existe, sinon la créer
            $check_table = $conn->query("SHOW TABLES LIKE 'contact_messages'");
            
            // Préparer la requête d'insertion du message
            $stmt = $conn->prepare("
    INSERT INTO contact_messages 
    (bien_id, name, email, subject, message, ip, is_read, created_at, updated_at) 
    VALUES (?, ?, ?, ?, ?, ?, 0, NOW(), NOW())
            ") or die("Erreur de préparation: " . $conn->error);
            
            $ip = $_SERVER['REMOTE_ADDR'];
            $stmt->bind_param("ssss", $nom, $email, $bien_titre, $message_text);
            
            if ($stmt->execute()) {
                $message_id = $conn->insert_id;
                $success = true;
                $message = 'Votre message a bien été envoyé. Nous vous contacterons dès que possible.';
                
                // Envoyer un email de notification à l'administrateur
                $to = 'contact@pollux-immobilier.fr'; // Remplacer par l'email de l'administrateur
                $subject = "Nouveau message concernant le bien #$bien_id - $bien_titre";
                
                $email_message = "Nouveau message de contact :\n\n";
                $email_message .= "Bien : $bien_titre (ID: $bien_id)\n";
                $email_message .= "Nom : $nom\n";
                $email_message .= "Email : $email\n";
                $email_message .= "Message :\n$message_text\n\n";
                $email_message .= "---\n";
                $email_message .= "Cet email a été envoyé depuis le formulaire de contact du site Pollux Immobilier.\n";
                
                $headers = "From: $email\r\n";
                $headers .= "Reply-To: $email\r\n";
                $headers .= "X-Mailer: PHP/" . phpversion();
                
                // Envoyer l'email (à décommenter en production)
                // mail($to, $subject, $email_message, $headers);
                
                // Pour le débogage, on peut enregistrer l'email dans un fichier
                file_put_contents('emails.log', date('Y-m-d H:i:s') . " - " . $email_message . "\n\n", FILE_APPEND);
                
            } else {
                $error = 'Une erreur est survenue lors de l\'envoi du message. Veuillez réessayer.';
                // Journalisation de l'erreur
                error_log("Erreur d'envoi de message: " . $stmt->error);
            }
            
            $stmt->close();
            
        } catch (Exception $e) {
            $error = 'Une erreur inattendue est survenue. Veuillez réessayer plus tard.';
            error_log("Erreur: " . $e->getMessage());
        }
    }
    
    // Si c'est une requête AJAX, renvoyer une réponse JSON
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => $success,
            'message' => $success ? $message : $error,
            'errors' => $success ? [] : ['form' => $error]
        ]);
        exit();
    }
    
    // Stocker le message dans la session pour l'affichage après redirection
    if ($success) {
        $_SESSION['success_message'] = $message;
    } else {
        $_SESSION['error_message'] = $error;
        // Conserver les données du formulaire pour les réafficher
        $_SESSION['form_data'] = [
            'nom' => $nom,
            'email' => $email,
            'message' => $message_text,
            'bien_id' => $bien_id
        ];
    }
    
    // Rediriger vers la page précédente
    $redirect_url = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'contact.php';
    header("Location: $redirect_url");
    exit();

// Si on arrive ici, c'est que la méthode n'est pas POST
header('Location: index.php');
exit();
?>
