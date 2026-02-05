<?php
session_start();

// Vérifie que l'utilisateur est bien admin
if (!isset($_SESSION['utilisateur']) || $_SESSION['utilisateur']['role'] !== 'admin') {
    header('Location: ../index.php');
    exit();
}

require_once '../includes/db.php';

$conn = new mysqli("localhost", "root", "", "bd_pollux");
if ($conn->connect_error) {
    die("Échec de la connexion : " . $conn->connect_error);
}

// Récupère toutes les réservations avec le nom de l'utilisateur
$sql = "
    SELECT r.id, r.utilisateur_id, r.date_visite, r.heure_debut, r.heure_fin,
           r.commentaire, r.annonce_id, r.type_annonce, COALESCE(r.statut, 'en attente') as statut,
           u.nom AS utilisateur_nom, u.prenom AS utilisateur_prenom
           FROM reservation_visite r
    JOIN utilisateurs u ON r.utilisateur_id = u.id
    ORDER BY r.date_visite DESC, r.heure_debut, r.heure_fin DESC
";

$result = $conn->query($sql);
$reservations = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $reservations[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Liste des réservations - Administration</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .card-header {
            background-color: #fff;
            border-bottom: 1px solid rgba(0,0,0,.125);
            padding: 1rem 1.25rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .card-title {
            margin: 0;
            color: #333;
            font-weight: 600;
        }
        .table-responsive {
            overflow-x: auto;
        }
        .table {
            margin-bottom: 0;
        }
        .table th {
            background-color: #f8f9fa;
            font-weight: 600;
            border-top: none;
            color: #495057;
        }
        .table td {
            vertical-align: middle;
        }
        .btn-action {
            padding: 0.25rem 0.5rem;
            margin: 0 0.15rem;
            font-size: 0.8rem;
        }
        .status-badge {
            padding: 0.35em 0.65em;
            font-size: 0.75em;
            font-weight: 700;
            border-radius: 0.25rem;
            text-transform: capitalize;
        }
        .badge-pending {
            background-color: #fff3cd;
            color: #856404;
        }
        .badge-confirmed {
            background-color: #d4edda;
            color: #155724;
        }
        .badge-cancelled {
            background-color: #f8d7da;
            color: #721c24;
        }
        .btn-ajouter {
            background-color: #0d6efd;
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 0.25rem;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        .btn-ajouter:hover {
            background-color: #0b5ed7;
            color: white;
        }
        .btn-retour {
            background-color: #6c757d;
            color: white;
            text-decoration: none;
            padding: 0.5rem 1rem;
            border-radius: 0.25rem;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        .btn-retour:hover {
            background-color: #5c636a;
            color: white;
        }
        .action-buttons {
            display: flex;
            gap: 0.5rem;
        }
    </style>
</head>
<body>
    <div class="container-fluid py-4">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Liste des réservations</h5>
                        <div class="d-flex gap-2">
                        <a href="admin.php" class="btn" style="background-color: #3498db; color: white; padding: 8px 15px; text-decoration: none; border-radius: 4px; display: inline-flex; align-items: center;">
                <i class="fas fa-arrow-left" style="margin-right: 5px;"></i> Retour au tableau de bord
                    </a>
                        </div>
                    </div>
                    <div class="card-body">
                        <?php if (isset($_GET['message']) && $_GET['message'] === 'supprime'): ?>
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                ✅ Réservation supprimée avec succès.
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endif; ?>

                        <?php if (isset($_GET['message']) && $_GET['message'] === 'modifie'): ?>
                            <div class="alert alert-info alert-dismissible fade show" role="alert">
                                🔄 Statut modifié avec succès.
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endif; ?>

                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Utilisateur</th>
                                        <th>Annonce</th>
                                        <th>Date et heure</th>
                                        <th>Statut</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (count($reservations) > 0): ?>
                                        <?php foreach ($reservations as $reservation): 
                                            $statutClass = 'badge-pending';
                                            if ($reservation['statut'] === 'confirmé') {
                                                $statutClass = 'badge-confirmed';
                                            } elseif ($reservation['statut'] === 'annulé') {
                                                $statutClass = 'badge-cancelled';
                                            }
                                        ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($reservation['id']); ?></td>
                                                <td>
                                                    <?php echo htmlspecialchars($reservation['utilisateur_prenom'] . ' ' . $reservation['utilisateur_nom']); ?>
                                                    <br>
                                                    <small class="text-muted">ID: <?php echo htmlspecialchars($reservation['utilisateur_id']); ?></small>
                                                </td>
                                                <td>
                                                    <?php 
                                                    echo !empty($reservation['annonce_titre']) 
                                                        ? htmlspecialchars($reservation['annonce_titre'])
                                                        : 'N/A';
                                                    ?>
                                                    <br>
                                                    <small class="text-muted">ID: <?php echo htmlspecialchars($reservation['annonce_id']); ?></small>
                                                </td>
                                               <td>
    <?php 
    $dateVisite = DateTime::createFromFormat('Y-m-d', $reservation['date_visite']);
    echo $dateVisite ? $dateVisite->format('d/m/Y') : 'N/A';
    ?>
    <br>
    <small class="text-muted">
        De <?php echo date('H:i', strtotime($reservation['heure_debut'])); ?>
        à <?php echo date('H:i', strtotime($reservation['heure_fin'])); ?>
    </small>
</td>
                                                <td>
    <div class="d-flex gap-2">
        <form action="traitement_reservation.php" method="POST" class="d-inline">
            <input type="hidden" name="reservation_id" value="<?php echo $reservation['id']; ?>">
            <input type="hidden" name="action" value="accepter">
            <button type="submit" class="btn btn-success btn-sm">
                <i class="fas fa-check"></i> Valider
            </button>
        </form>
        <form action="traitement_reservation.php" method="POST" class="d-inline">
            <input type="hidden" name="reservation_id" value="<?php echo $reservation['id']; ?>">
            <input type="hidden" name="action" value="refuser">
            <button type="submit" class="btn btn-danger btn-sm">
                <i class="fas fa-times"></i> Refuser
            </button>
        </form>
    </div>
</td>
                                                <td>
                                                    <span class="status-badge <?php echo $statutClass; ?>">
                                                        <?php echo ucfirst(htmlspecialchars($reservation['statut'])); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <div class="action-buttons">
                                                        <a href="modifier_reservation.php?id=<?php echo $reservation['id']; ?>" 
                                                           class="btn btn-sm btn-primary" 
                                                           title="Modifier">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                        <div class="action-buttons">
                                                        <a href="supprimer_reservation.php?id=<?php echo $reservation['id']; ?>" 
                                                           class="btn btn-sm btn-danger" 
                                                           title="Supprimer">
                                                            <i class="fas fa-trash"></i>
                                                        </a>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="6" class="text-center py-4">
                                                <i class="fas fa-inbox fa-3x text-muted mb-2"></i>
                                                <p class="mb-0">Aucune réservation trouvée</p>
                                            </td>
                                            <td>
            </div>
</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                            </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function confirmerSuppression(id) {
            if (confirm("Voulez-vous vraiment supprimer cette réservation ?")) {
                window.location.href = "supprimer_reservation.php?id=" + id;
            }
        }
    </script>
</body>
</html>