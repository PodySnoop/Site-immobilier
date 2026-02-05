<?php
session_start();

// Vérification que l'utilisateur est un administrateur
if (!isset($_SESSION['utilisateur']) || $_SESSION['utilisateur']['role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

require_once '../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Traitement de la modification
    require_once '../includes/db.php';

    $id = intval($_POST['reservation_id']);
    $date_visite = $_POST['date_visite'];
    $heure_debut = $_POST['heure_debut'];
    $heure_fin = $_POST['heure_fin'];

    // Validation simple (tu peux renforcer selon tes besoins)
    if (!$date_visite || !$heure_debut || !$heure_fin) {
        header("Location: modifier_reservation.php?id=$id&message=champs_invalides");
        exit();
    }

    $stmt = $conn->prepare("UPDATE reservation_visite SET date_visite = ?, heure_debut = ?, heure_fin = ? WHERE id = ?");
    $stmt->bind_param("sssi", $date_visite, $heure_debut, $heure_fin, $id);
    $stmt->execute();

    header("Location: liste_reservation.php?message=modifie");
    exit();
}
// Vérifier que l'ID de réservation est fourni
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: liste_reservation.php?message=erreur_id');
    exit();
}

$reservation_id = intval($_GET['id']);

// Récupérer les données de la réservation
$stmt = $conn->prepare("SELECT * FROM reservation_visite WHERE id = ?");
$stmt->bind_param("i", $reservation_id);
$stmt->execute();
$result = $stmt->get_result();
$reservation = $result->fetch_assoc();

if (!$reservation) {
    header('Location: liste_reservation.php?message=reservation_introuvable');
    exit();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifier la réservation</title>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 20px;
            background-color: #f4f4f4;
        }

        .container {
            max-width: 700px;
            margin: 0 auto;
            background: #fff;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }

        h1 {
            color: #2c3e50;
            text-align: center;
            margin-bottom: 30px;
        }

        .btn-retour {
            background-color: #117c84;
            color: white;
            padding: 8px 15px;
            text-decoration: none;
            border-radius: 4px;
            display: inline-flex;
            align-items: center;
            margin-bottom: 20px;
        }

        .btn-retour i {
            margin-right: 5px;
        }

        label {
            font-weight: bold;
            color: #2c3e50;
            margin-top: 15px;
            display: block;
        }

        input[type="date"],
        input[type="time"] {
            width: 100%;
            padding: 12px;
            margin-top: 5px;
            border: 1px solid #ccc;
            border-radius: 6px;
            background-color: #fafafa;
            font-size: 16px;
        }

        button[type="submit"] {
            background-color: #117c84;
            color: white;
            padding: 12px 20px;
            border: none;
            border-radius: 6px;
            font-size: 16px;
            cursor: pointer;
            width: 100%;
            margin-top: 25px;
        }

        button[type="submit"]:hover {
            opacity: 0.9;
        }

        .btn-annuler {
            display: block;
            text-align: center;
            margin-top: 15px;
            color: #117c84;
            text-decoration: none;
        }

        .btn-annuler:hover {
            text-decoration: underline;
        }
    </style>
</head>

<body>
    <div class="container">

        <a href="liste_reservation.php" class="btn-retour">
            <i class="fas fa-arrow-left"></i> Retour à la liste
        </a>

        <h1><i class="fas fa-calendar-check"></i> Modifier la réservation #<?= htmlspecialchars($reservation['id']) ?></h1>

        <form action="modifier_reservation.php" method="POST">
            <input type="hidden" name="reservation_id" value="<?= $reservation['id'] ?>">

            <label for="date_visite">Date :</label>
            <input type="date" name="date_visite" id="date_visite" value="<?= htmlspecialchars($reservation['date_visite']) ?>" required>

            <label for="heure_debut">Heure de début :</label>
            <input type="time" name="heure_debut" id="heure_debut" value="<?= htmlspecialchars($reservation['heure_debut']) ?>" required>

            <label for="heure_fin">Heure de fin :</label>
            <input type="time" name="heure_fin" id="heure_fin" value="<?= htmlspecialchars($reservation['heure_fin']) ?>" required>

            <button type="submit">Enregistrer les modifications</button>

            <a href="liste_reservation.php" class="btn-annuler">Annuler</a>
        </form>
    </div>
</body>
</html>