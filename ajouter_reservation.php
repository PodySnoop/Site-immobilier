<?php
session_start();
require_once '../includes/db.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/php_errors.log');


// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['utilisateur_id'])) {
    header("Location: ../utilisateurs/login.php");
    exit();
}

// Vérifier si le formulaire a été soumis
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../index.php");
    exit();
}

// Récupération des données
$utilisateur_id = $_SESSION['utilisateur_id'];
$annonce_id     = filter_input(INPUT_POST, 'annonce_id', FILTER_VALIDATE_INT);
$type_annonce   = strtolower(trim($_POST['type_annonce'] ?? ''));
$date_visite    = $_POST['date_visite'] ?? '';
$heure_debut    = $_POST['heure_debut'] ?? '';
$duree_visite   = (int)($_POST['duree'] ?? 30);
$commentaire    = trim($_POST['commentaire'] ?? '');
$date_creation  = date('Y-m-d H:i:s');
$statut         = 'en attente';
$origine        = $_POST['origine'] ?? 'index.php';

// Validation basique
$errors = [];

if (!$annonce_id) {
    $errors[] = "Identifiant de l'annonce invalide.";
}

if (!$date_visite || !$heure_debut) {
    $errors[] = "Date ou heure de visite manquante.";
}

// Normaliser la date
$date_visite = date('Y-m-d', strtotime($date_visite));

// Normaliser l'heure
$heure_debut = date('H:i:s', strtotime($heure_debut));

// Calculer l'heure de fin
$datetime_fin = new DateTime($date_visite . ' ' . $heure_debut);
$datetime_fin->add(new DateInterval('PT' . $duree_visite . 'M'));
$heure_fin = $datetime_fin->format('H:i:s');

// Vérifier que la visite n'est pas dans le passé
$now = new DateTime();
if (new DateTime($date_visite . ' ' . $heure_debut) < $now) {
    $errors[] = "La visite ne peut pas être programmée dans le passé.";
}

// Vérification de disponibilité
if (empty($errors)) {
    $check_query = "
        SELECT 1 FROM reservation_visite
        WHERE annonce_id = ?
        AND type_annonce = ?
        AND statut != 'annulee'
        AND date_visite = ?
        AND NOT (
            ? <= heure_debut OR ? >= heure_fin
        )
        LIMIT 1
    ";

    $stmt = $conn->prepare($check_query);
    $stmt->bind_param("issss",
        $annonce_id,
        $type_annonce,
        $date_visite,
        $heure_fin,
        $heure_debut
    );
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $errors[] = "Ce créneau est déjà réservé.";
    }
}

// Si erreurs → redirection vers confirmation avec erreur
if (!empty($errors)) {
    $_SESSION['error'] = implode('<br>', $errors);
    header("Location: confirmation_reservation.php?error=1");
    exit();
}

// Insertion en base
try {
    $conn->begin_transaction();

    $insert_query = "
        INSERT INTO reservation_visite 
        (utilisateur_id, annonce_id, type_annonce, date_visite, heure_debut, heure_fin, statut, commentaire, date_creation)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ";

    $stmt = $conn->prepare($insert_query);
    $stmt->bind_param(
        "iisssssss",
        $utilisateur_id,
        $annonce_id,
        $type_annonce,
        $date_visite,
        $heure_debut,
        $heure_fin,
        $statut,
        $commentaire,
        $date_creation
    );

    if (!$stmt->execute()) {
        throw new Exception("Erreur SQL : " . $stmt->error);
    }

    $reservation_id = $conn->insert_id;
    $conn->commit();

    // Succès → redirection
    header("Location: confirmation_reservation.php?id=$reservation_id");
    exit();

} catch (Exception $e) {
    $conn->rollback();
    error_log("Erreur réservation : " . $e->getMessage());
    $_SESSION['error'] = "Une erreur est survenue lors de l'enregistrement.";
    header("Location: confirmation_reservation.php?error=1");
    exit();
}