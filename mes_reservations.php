<?php
session_start();
require_once('connexion.php');

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['utilisateur_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['utilisateur_id'];
$message = '';
$reservations = [];

// Récupérer les réservations de l'utilisateur
$sql = "
    (
    SELECT r.*, u.nom AS utilisateur_nom, u.prenom AS utilisateur_prenom,
           b.id AS bien_ID, b.titre, b.localisation,
           'location' AS type_annonce
    FROM reservation_visite r
    JOIN utilisateurs u ON r.utilisateur_id = u.id
    JOIN biens_en_location b ON r.annonce_id = b.id
    WHERE r.utilisateur_id = ?
    AND r.type_annonce = 'location'
)
UNION
(
    SELECT r.*, u.nom AS utilisateur_nom, u.prenom AS utilisateur_prenom,
           b.id AS bien_ID, b.titre, b.localisation,
           'vente' AS type_annonce
    FROM reservation_visite r
    JOIN utilisateurs u ON r.utilisateur_id = u.id
    JOIN biens_à_vendre b ON r.annonce_id = b.id
    WHERE r.utilisateur_id = ?
    AND r.type_annonce = 'vente'
)
ORDER BY date_visite DESC
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $user_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();
$reservations = [];

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $reservations[] = $row;
    }
}

$stmt->close();

?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mes réservations - Pollux Immobilier</title>
    <link rel="stylesheet" href="../style.css">
    <link rel="stylesheet" href="../pages-common.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        .reservations-container {
            max-width: 1200px;
            margin: 30px auto;
            padding: 0 20px;
        }
        
        .page-title {
            text-align: center;
            margin-bottom: 30px;
            color: #157f68;
        }
        
        .reservation-card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
            overflow: hidden;
            display: flex;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .reservation-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.15);
        }
        
        .reservation-image {
            width: 300px;
            height: 200px;
            object-fit: cover;
        }
        
        .reservation-details {
            padding: 20px;
            flex: 1;
        }
        
        .reservation-title {
            font-size: 1.5rem;
            margin: 0 0 10px 0;
            color: #333;
        }
        
        .reservation-location {
            color: #666;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
        }
        
        .reservation-location i {
            margin-right: 5px;
        }
        
        .reservation-dates {
            display: flex;
            margin-bottom: 15px;
            gap: 20px;
        }
        
        .date-group {
            display: flex;
            flex-direction: column;
        }
        
        .date-label {
            font-size: 0.9rem;
            color: #666;
        }
        
        .date-value {
            font-weight: bold;
            color: #333;
        }
        
        .reservation-status {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 0.9rem;
            font-weight: 500;
            margin-top: 10px;
        }
        
        .status-pending {
            background-color: #fff3cd;
            color: #856404;
        }
        
        .status-confirmed {
            background-color: #d4edda;
            color: #155724;
        }
        
        .status-cancelled {
            background-color: #f8d7da;
            color: #721c24;
        }
        
        .reservation-price {
            font-size: 1.2rem;
            font-weight: bold;
            color: #157f68;
            margin: 10px 0;
        }
        
        .no-reservations {
            text-align: center;
            padding: 50px 20px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .no-reservations i {
            font-size: 3rem;
            color: #ddd;
            margin-bottom: 15px;
        }
        
        .no-reservations p {
            color: #666;
            margin-bottom: 20px;
        }
        
        .btn-primary {
            background-color: #157f68;
            color: white;
            padding: 10px 20px;
            border-radius: 4px;
            text-decoration: none;
            display: inline-block;
            transition: background-color 0.3s;
        }
        
        .btn-primary:hover {
            background-color: #0b1987;
            color: white;
        }
        
        .reservation-actions {
            margin-top: 15px;
            display: flex;
            gap: 10px;
        }
        
        .btn {
            padding: 8px 15px;
            border-radius: 4px;
            text-decoration: none;
            font-size: 0.9rem;
            cursor: pointer;
            border: none;
            transition: all 0.3s;
        }
        
        .btn-cancel {
            background-color: #f8d7da;
            color: #721c24;
        }
        
        .btn-cancel:hover {
            background-color: #f1b0b7;
        }
        
        .btn-details {
            background-color: #e2e3e5;
            color: #383d41;
        }
        
        .btn-details:hover {
            background-color: #c6c8ca;
        }
        
        @media (max-width: 768px) {
            .reservation-card {
                flex-direction: column;
            }
            
            .reservation-image {
                width: 100%;
                height: 200px;
            }
        }
   .reservations-container {
    display: flex;
    flex-direction: column;
    align-items: center; /* ✅ Centre horizontalement */
}

.reservation-card {
    width: 100%;
    max-width: 800px; /* ✅ Largeur fixe et centrée */
}     
.btn-retour {
            background-color: #3498db;
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
        
    </style>
</head>
<body>
    <header>
        <div class="header-container">
            <img src="../logo.png" alt="Logo Pollux Immobilier" class="logo">
            <h1>Pollux Immobilier</h1>
        </div>
        <nav>
            <ul>
                <li><a href="../index.php">Accueil</a></li>
                <li><a href="../bien-locatifs.php">Louer</a></li>
                <li><a href="../bien-vente.php">Acheter</a></li>
                <li><a href="../vendre-estimer.php">Vendre/Estimer</a></li>
                <li><a href="../presentation.html">Notre histoire</a></li>
                <li class="login-link"><a href="deconnexion.php" style="color: #ffffff !important;">Déconnexion</a></li>
            </ul>
        </nav>
    </header>

    <div class="container">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h1><i class="fas fa-heart"></i> Mes Réservations</h1>
            <a href="profil_utilisateur.php" class="btn-retour">
                <i class="fas fa-arrow-left"></i> Retour au profil
            </a>
    </div>

    <?php if (count($reservations) > 0): ?>
    <table>
        <thead>
            <tr>
                <th>Titre</th>
                <th>Détails</th>
                <th>Type</th>
                <th>Date/Heure</th>
                <th>Statut</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($reservations as $reservation): 
                // Déterminer la classe du statut
                $statusClass = '';
                $statusText = '';
                
                switch ($reservation['statut']) {
                    case 'confirmee':
                        $statusClass = 'status-confirmed';
                        $statusText = 'Confirmée';
                        break;
                    case 'annulee':
                        $statusClass = 'status-cancelled';
                        $statusText = 'Annulée';
                        break;
                    default:
                        $statusClass = 'status-pending';
                        $statusText = 'En attente';
                }

                // Date de visite - Nettoyage et validation de la date
$dateVisiteStr = trim($reservation['date_visite']);
if (empty($dateVisiteStr) || $dateVisiteStr === '0000-00-00 00:00:00') {
    $dateVisite = new DateTime(); // Date actuelle comme valeur par défaut
} else {
    // Si la date contient un double format, on prend uniquement la première partie
    if (preg_match('/(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})/', $dateVisiteStr, $matches)) {
        $dateVisiteStr = $matches[1];
    }
    try {
        $dateVisite = new DateTime($dateVisiteStr);
    } catch (Exception $e) {
        error_log("Erreur de format de date: " . $e->getMessage());
        $dateVisite = new DateTime(); // Fallback sur la date actuelle
    }
}

// Formatage pour affichage
$dateStr = $dateVisite->format('d/m/Y');
$heureDebutStr = $dateVisite->format('H:i');

// Heure de début - Vérification et nettoyage
if (!empty($reservation['heure_debut']) && !empty($reservation['heure_fin'])) {
    try {
        $heureDebut = new DateTime($dateVisite->format('Y-m-d') . ' ' . $reservation['heure_debut']);
        $heureFin   = new DateTime($dateVisite->format('Y-m-d') . ' ' . $reservation['heure_fin']);
        $heureFinStr = $heureFin->format('H:i');
    } catch (Exception $e) {
        error_log("Erreur de format d'heure: " . $e->getMessage());
        $heureDebut = new DateTime(); // Fallback sur l'heure actuelle
        $heureFin   = clone $heureDebut;
        $heureFin->modify('+1 hour');
        $heureFinStr = $heureFin->format('H:i');
    }
}else {
    $heureDebut = new DateTime(); // Fallback sur l'heure actuelle
    $heureFin = clone $heureDebut;
    $heureFin->modify('+1 hour');
    $heureFinStr = $heureFin->format('H:i');
}

// Heure actuelle
$now = new DateTime();


// Condition d'annulation possible : avant le début ET statut non annulé
$canCancel = $now < $heureDebut && $reservation['statut'] !== 'annulee';

// Statut temporel dynamique
if ($now < $heureDebut) {
    $statutTemporel = '🕒 À venir';
} elseif ($now <= $heureFin) {
    $statutTemporel = '🔄 En cours';
} else {
    $statutTemporel = '✅ Terminée';
}

            ?>
               <div class="reservation-card">
    <img src="<?= !empty($reservation['image_principale']) ? htmlspecialchars($reservation['image_principale']) : 'chemin/vers/image/par/defaut.jpg' ?>" 
         alt="<?= !empty($reservation['titre']) ? htmlspecialchars($reservation['titre']) : 'Image du bien' ?>" 
         class="reservation-image">

    <div class="reservation-details">
        <h2 class="reservation-title">
            <?= isset($reservation['titre']) ? htmlspecialchars($reservation['titre']) : 'Titre inconnu' ?>
        </h2>

        <div class="reservation-location">
            <i class="fas fa-map-marker-alt"></i>
            <?= isset($reservation['localisation']) ? htmlspecialchars($reservation['localisation']) : 'Localisation non renseignée' ?>
        </div>
    </div>
</div>
                        <div class="reservation-dates">
    <div class="date-group">
        <span class="date-label">Date de visite</span>
        <span class="date-value">
            <?= isset($dateVisite) ? $dateVisite->format('d/m/Y') : 'Date inconnue' ?> à 
            <?= isset($heureDebut) ? $heureDebut->format('H:i') : 'Heure inconnue' ?>
        </span>
    </div>
    <div class="date-group">
        <span class="date-label">Type de bien</span>
        <span class="date-value">
            <?= isset($reservation['type_annonce']) ? ($reservation['type_annonce'] === 'location' ? 'Location' : 'Vente') : 'Type inconnu' ?>
        </span>
    </div>

</div>

<span class="reservation-status <?= isset($statusClass) ? $statusClass : 'statut-inconnu' ?>">
    <?= isset($statusText) ? $statusText : 'Statut inconnu' ?>
</span>

<?php if (!empty($reservation['notes'])): ?>
    <div class="reservation-notes">
        <strong>Notes :</strong> <?= htmlspecialchars($reservation['notes']) ?>
    </div>
<?php endif; ?>
                        
                        <div class="reservation-actions">
                            <a href="../bien-<?= $reservation['type_annonce'] ?>.php?id=<?= $reservation['bien_ID'] ?>" class="btn btn-details">
                                <i class="fas fa-eye"></i> Voir le bien
                            </a>
                            <?php if ($canCancel): ?>
                                <button class="btn btn-cancel" onclick="annulerReservation(<?= $reservation['id'] ?>)">
                                    <i class="fas fa-times"></i> Annuler
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="no-reservations">
                <i class="far fa-calendar-alt"></i>
                <h2>Vous n'avez pas encore de réservation de visite</h2>
                <p>Parcourez nos biens disponibles et réservez une visite !</p>
                <a href="../bien-locatifs.php" class="btn-primary">Voir nos biens à louer</a>
                <a href="../bien-vente.php" class="btn-primary" style="margin-left: 10px;">Voir nos biens en vente</a>
            </div>
        <?php endif; ?>
    </div>

    <footer>
        <div class="container">
            <p>&copy; <?= date('Y') ?> Pollux Immobilier - Tous droits réservés</p>
        </div>
    </footer>

    <script>
       function annulerReservation(reservationId) {
            if (confirm('Êtes-vous sûr de vouloir annuler cette réservation ? Cette action est irréversible.')) {
                // Envoyer une requête AJAX pour annuler la réservation
                fetch('annuler_reservation.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'reservation_id=' + reservationId
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Recharger la page pour afficher la mise à jour
                        window.location.reload();
                    } else {
                        alert('Une erreur est survenue lors de l\'annulation de la réservation.');
                    }
                })
                .catch(error => {
                    console.error('Erreur:', error);
                    alert('Une erreur est survenue lors de la communication avec le serveur.');
                });
            }
        }
    </script>
</body>
</html>
