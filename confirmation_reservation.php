<?php
session_set_cookie_params(['path' => '/']);
session_start();


if (isset($_SESSION['error']) && isset($_SESSION['utilisateur_id'])) {
    unset($_SESSION['error']); // 🔥 On efface l’erreur si l’utilisateur est bien connecté
}

require_once '../includes/db.php';
require_once '../includes/header.php';

// -------------------------------------------------------------
// 1. Vérification connexion utilisateur
// -------------------------------------------------------------
if (!isset($_SESSION['utilisateur_id'])) {
    $_SESSION['error'] = "Veuillez vous connecter pour voir cette page.";
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['utilisateur_id'];
$reservation_id = intval($_GET['id'] ?? 0);
$origine = $_GET['from'] ?? '../index.php';

// -------------------------------------------------------------
// 2. Vérifier que l'ID de réservation est présent
// -------------------------------------------------------------
if ($reservation_id <= 0) {
    $_SESSION['error'] = "Aucune réservation spécifiée.";
    header("Location: mes_reservations.php");
    exit();
}

// -------------------------------------------------------------
// 3. TRAITEMENT DU FORMULAIRE DE MESSAGE (POST)
// -------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $message = trim($_POST['message'] ?? '');

    if ($message === '') {
        $_SESSION['error'] = "Le message ne peut pas être vide.";
        header("Location: confirmation_reservation.php?id=$reservation_id");
        exit();
    }

    $query = "INSERT INTO contact_messages (reservation_visite_id, message, created_at, is_read)
              VALUES (?, ?, NOW(), 0)";  // 0 = non lu, 1 = lu

    $stmt = $conn->prepare($query);
    $stmt->bind_param("is", $reservation_id, $message);

    if ($stmt->execute()) {
        $_SESSION['success'] = "Votre message a bien été envoyé !";
    } else {
        error_log("Erreur SQL message : " . $stmt->error);
        $_SESSION['error'] = "Une erreur est survenue lors de l'envoi du message.";
    }

    header("Location: confirmation_reservation.php?id=$reservation_id");
    exit();
}

// -------------------------------------------------------------
// 4. Récupération des détails de la réservation
// -------------------------------------------------------------
$query = "SELECT r.*, 
          CONCAT(u.prenom, ' ', u.nom) AS nom_utilisateur,
          u.email,
          CASE 
            WHEN r.type_annonce = 'vente' THEN bv.titre
            WHEN r.type_annonce = 'location' THEN bl.titre
            ELSE 'titre inconnu'
          END AS adresse_bien
          FROM reservation_visite r
          JOIN utilisateurs u ON r.utilisateur_id = u.id
          LEFT JOIN biens_à_vendre bv ON (r.annonce_id = bv.id AND r.type_annonce = 'vente')
          LEFT JOIN biens_en_location bl ON (r.annonce_id = bl.id AND r.type_annonce = 'location')
          WHERE r.id = ? AND r.utilisateur_id = ?";

$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $reservation_id, $user_id);
$stmt->execute();
$reservation = $stmt->get_result()->fetch_assoc();

if (!$reservation) {
    $_SESSION['error'] = "Réservation introuvable ou vous n'avez pas les droits pour la consulter.";
    header("Location: mes_reservations.php");
    exit();
}

// -------------------------------------------------------------
// 5. Formatage des dates
// -------------------------------------------------------------
$date_visite = new DateTime($reservation['date_visite']);
$date_creation = new DateTime($reservation['date_creation']);

?>

<!-- -------------------------------------------------------------
     6. AFFICHAGE HTML
-------------------------------------------------------------- -->

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-header bg-success text-white">
                    <h4 class="mb-0">
                        <i class="fas fa-check-circle me-2"></i>Réservation confirmée
                    </h4>
                </div>
                <div class="card-body">

                    <?php if (isset($_SESSION['success'])): ?>
                        <div class="alert alert-success"><?= $_SESSION['success']; ?></div>
                        <?php unset($_SESSION['success']); ?>
                    <?php endif; ?>

                    <?php if (isset($_SESSION['error'])): ?>
                        <div class="alert alert-danger"><?= $_SESSION['error']; ?></div>
                        <?php unset($_SESSION['error']); ?>
                    <?php endif; ?>

                    <div class="alert alert-success">
                        <h5 class="alert-heading">Merci pour votre réservation !</h5>
                        <p class="mb-0">Votre demande de visite a bien été enregistrée sous le numéro #<?= $reservation_id ?>.</p>
                    </div>

                    <!-- Formulaire de message -->
                    <div class="card mt-4">
                        <div class="card-header">
                            <h5>Un message à ajouter ?</h5>
                        </div>
                        <div class="card-body">
                            <form method="post" action="">
                                <div class="mb-3">
                                    <label for="message" class="form-label">Votre message</label>
                                    <textarea class="form-control" id="message" name="message" rows="3"
                                              placeholder="Ajoutez un message ou des précisions..."></textarea>
                                </div>
                                <button type="submit" class="btn btn-primary">Envoyer le message</button>
                            </form>
                        </div>
                    </div>

                    <div class="row mb-4 mt-4">
                        <div class="col-md-6">
                            <h5><i class="fas fa-calendar-alt text-primary me-2"></i>Détails de la visite</h5>
                            <ul class="list-unstyled">
                                <li><strong>Date :</strong> <?= $date_visite->format('d/m/Y') ?></li>
                                <li><strong>Heure :</strong> <?= date('H:i', strtotime($reservation['heure_debut'])) ?></li>
                                <li><strong>Durée :</strong> Environ 1 heure</li>
                                <li><strong>Adresse :</strong> <?= htmlspecialchars($reservation['adresse_bien']) ?></li>
                            </ul>
                        </div>

                        <div class="col-md-6">
                            <h5><i class="fas fa-user text-primary me-2"></i>Vos informations</h5>
                            <ul class="list-unstyled">
                                <li><strong>Nom :</strong> <?= htmlspecialchars($reservation['nom_utilisateur']) ?></li>
                                <li><strong>Email :</strong> <?= htmlspecialchars($reservation['email']) ?></li>
                            </ul>
                        </div>
                    </div>

                    <div class="alert alert-info">
                        <h6><i class="fas fa-info-circle me-2"></i>À savoir :</h6>
                        <ul class="mb-0">
                            <li>Vous recevrez un email de confirmation avec tous les détails de votre réservation.</li>
                            <li>Un conseiller vous contactera dans les 24 heures pour confirmer votre rendez-vous.</li>
                            <li>Vous pouvez annuler ou modifier votre réservation depuis votre espace personnel jusqu'à 24h avant la visite.</li>
                        </ul>
                    </div>

                    <div class="d-flex justify-content-between mt-4">
                        <a href="mes_reservations.php" class="btn btn-outline-primary">
                            <i class="fas fa-list me-1"></i> Voir toutes mes réservations
                        </a>

                        <a href="<?= htmlspecialchars($origine) ?>" class="btn btn-info text-white">
                            <i class="fas fa-arrow-left me-1"></i> Retour aux annonces
                        </a>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>

<?php include_once '../includes/footer.php'; ?>