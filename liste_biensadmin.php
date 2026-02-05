<?php
session_start();

// Vérification des droits d'administration
if (!isset($_SESSION['utilisateur']) || $_SESSION['utilisateur']['role'] !== 'admin') {
    header('Location: ../index.php');
    exit();
}

require_once '../includes/db.php';

// Connexion à la base de données
$conn = new mysqli("localhost", "root", "", "bd_pollux");
if ($conn->connect_error) {
    die("Échec de la connexion : " . $conn->connect_error);
}

// Requête pour récupérer les biens avec les informations du propriétaire
$sql = "
    SELECT b.id, b.titre, b.description, b.date_creation, b.type_annonce,
    b.prix AS montant, b.surface, 
    CASE 
        WHEN b.est_valide = 1 THEN 'validé'
        ELSE 'en_attente' 
    END AS statut_validation,
    b.nombre_pieces,
    u.nom AS nom, u.prenom AS prenom,
    'vente' AS type_annonce
    FROM biens_à_vendre b
    LEFT JOIN utilisateurs u ON b.utilisateur_id = u.id

    UNION ALL

    SELECT b.id, b.titre, b.description, b.date_creation, b.type_annonce,
    b.loyer AS montant, b.surface, 
    CASE 
        WHEN b.est_valide = 1 THEN 'validé'
        ELSE 'en_attente' 
    END AS statut_validation,
    b.nombre_pieces,
    u.nom AS nom, u.prenom AS prenom,
    'location' AS type_annonce
    FROM biens_en_location b
    LEFT JOIN utilisateurs u ON b.utilisateur_id = u.id

    ORDER BY date_creation DESC
";

$result = $conn->query($sql);
$biens = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $biens[] = $row;
    }
}

// Fermeture de la connexion
$conn->close();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Liste des biens - Administration</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        /* Styles inchangés */
        body { background-color: #f8f9fa; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .card { border: none; border-radius: 10px; box-shadow: 0 0 15px rgba(0,0,0,0.1); margin-bottom: 20px; }
        .card-header { background-color: #fff; border-bottom: 1px solid rgba(0,0,0,.125); padding: 1rem 1.25rem; }
        .card-title { margin: 0; color: #333; font-weight: 600; }
        .table-responsive { overflow-x: auto; }
        .table { margin-bottom: 0; }
        .table th { background-color: #f8f9fa; font-weight: 600; border-top: none; color: #495057; }
        .table td { vertical-align: middle; }
        .btn-action { padding: 0.25rem 0.5rem; margin: 0 0.15rem; font-size: 0.8rem; }
        .status-badge { padding: 0.35em 0.65em; font-size: 0.75em; font-weight: 700; border-radius: 0.25rem; text-transform: capitalize; }
        .badge-refuse { background-color: #d4edda; color: #155724; }
        .badge-attente { background-color: #f8d7da; color: #721c258e; }
        .badge-inconnu { background-color: #f8d7da; color: #721c24; }
        .badge-valide { background-color: #e2e3e5; color: #383d41; }
        .btn-ajouter { background-color: #0d6efd; color: white; border: none; padding: 0.5rem 1rem; border-radius: 0.25rem; text-decoration: none; }
        .btn-ajouter:hover { background-color: #0b5ed7; color: white; }
        .btn-retour { background-color: #6c757d; color: white; text-decoration: none; padding: 0.5rem 1rem; border-radius: 0.25rem; }
        .btn-retour:hover { background-color: #5c636a; color: white; }
        .action-buttons { display: flex; gap: 0.5rem; }
        .property-image { width: 80px; height: 60px; object-fit: cover; border-radius: 4px; }
        .price-tag { font-weight: 600; color: #0d6efd; }
    </style>
</head>
<body>
    <div class="container-fluid py-4">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">Liste des biens immobiliers</h5>
                        <div class="d-flex gap-2">
                        <a href="admin.php" class="btn" style="background-color: #3498db; color: white; padding: 8px 15px; text-decoration: none; border-radius: 4px; display: inline-flex; align-items: center;">
                <i class="fas fa-arrow-left" style="margin-right: 5px;"></i> Retour au tableau de bord
                </a>
                            <a href="ajouter_bien.php" class="btn btn-primary">
                                <i class="fas fa-plus me-1"></i> Ajouter un bien
                            </a>
                        </div>
                    </div>
                    <div class="card-body">
                        <?php if (isset($_GET['message'])): ?>
                            <div class="alert alert-<?php echo $_GET['message'] === 'supprime' ? 'danger' : 'success'; ?> alert-dismissible fade show" role="alert">
                                <?php 
                                if ($_GET['message'] === 'supprime') {
                                    echo '<i class="fas fa-check-circle me-2"></i>Bien supprimé avec succès.';
                                } elseif ($_GET['message'] === 'ajoute') {
                                    echo '<i class="fas fa-check-circle me-2"></i>Bien ajouté avec succès.';
                                } elseif ($_GET['message'] === 'modifie') {
                                    echo '<i class="fas fa-check-circle me-2"></i>Bien modifié avec succès.';
                                }
                                ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endif; ?>

                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Image</th>
                                        <th>Titre</th>
                                        <th>Type</th>
                                        <th>Prix</th>
                                        <th>Surface</th>
                                        <th>Nombre de Pièces</th>
                                        <th>Statut</th>
                                        <th>Propriétaire</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php if (!empty($biens)): ?>
                        <?php foreach ($biens as $bien): 
                     // Valeur par défaut si la clé 'statut' est absente
                   $statut = isset($bien['statut_validation']) ? $bien['statut_validation'] : 'inconnu';

                     // Déterminer la classe CSS selon le statut_validation
switch (strtolower($bien['statut_validation'])) {
    case 'valide':
        $statutClass = 'badge-valide';
        break;
    case 'en_attente':
        $statutClass = 'badge-attente';
        break;
    case 'refusé':
        $statutClass = 'badge-refuse';
        break;
    default:
        $statutClass = 'badge-inconnu';
}
                 ?>


                                            <tr>
                                                <td><?php echo htmlspecialchars($bien['id']); ?></td>
                                                <td>
                                                    <?php if (!empty($bien['image_principale'])): ?>
                                                        <img src="../uploads/biens/<?php echo htmlspecialchars($bien['image_principale']); ?>" 
                                                             alt="<?php echo htmlspecialchars($bien['titre']); ?>" 
                                                             class="property-image">
                                                    <?php else: ?>
                                                        <div class="bg-light d-flex align-items-center justify-content-center" 
                                                             style="width: 80px; height: 60px; border-radius: 4px;">
                                                            <i class="fas fa-home text-muted"></i>
                                                        </div>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php echo htmlspecialchars($bien['titre']); ?>
                                                    <br>
                                                    <small class="text-muted">
                                                        <?php 
                                                        $localisation = array_filter([
                                                            $bien['localisation'] ?? null
                                                        ]);
                                                        echo htmlspecialchars(implode(', ', $localisation));
                                                        ?>
                                                    </small>
                                                </td>
                                                <td><?php echo htmlspecialchars($bien['type_bien'] ?? 'Non spécifié'); ?></td>
                                                <td class="price-tag">
                                                    <?php 
                                                    if (isset($bien['montant'])) {
                                                        echo number_format($bien['montant'], 0, ',', ' ') . ' €';
                                                        if (($bien['type_annonce'] ?? '') === 'location') {
                                                            echo '<small class="text-muted d-block">/mois</small>';
                                                        }
                                                    } else {
                                                        echo 'N/A';
                                                    }
                                                    ?>
                                                </td>
                                                <td>
                                                    <?php 
                                                    echo isset($bien['surface']) ? number_format($bien['surface'], 0, ',', ' ') . ' m²' : 'N/A';
                                                    ?>
                                                </td>
                                             <td>
                                                <?php echo isset($bien['nombre_pieces']) ? $bien['nombre_pieces'] : 'N/A'; 
                                                ?>
                                                </td>


                                                <td>
                                                    <span class="status-badge <?php echo $statutClass; ?>">
                                                         <?php echo ucfirst($bien['statut_validation']); ?>

                                                    </span>
                                                </td>
                                                <td>
                                                    <?php if (!empty($bien['utilisateur_id'])): ?>
                                                        <?php echo htmlspecialchars(trim($bien['prenom'] . ' ' . $bien['nom'])); ?>
                                                        <br>
                                                        <small class="text-muted">
                                                            ID: <?php echo htmlspecialchars($bien['utilisateur_id'] ?? 'N/A'); ?>
                                                        </small>
                                                    <?php else: ?>
                                                        <span class="text-muted">Non spécifié</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <div class="action-buttons">
                                                        <a href="modifier_bien.php?id=<?php echo $bien['id']; ?>" 
                                                           class="btn btn-sm btn-primary" 
                                                           title="Modifier">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                        <button type="button" 
                                                                class="btn btn-sm btn-danger" 
                                                                title="Supprimer"
                                                                onclick="confirmerSuppression(<?php echo $bien['id']; ?>)">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="10" class="text-center py-4">
                                                <i class="fas fa-inbox fa-3x text-muted mb-2"></i>
                                                <p class="mb-3">Aucun bien trouvé</p>
                                                <a href="ajouter_bien.php" class="btn btn-primary">
                                                    <i class="fas fa-plus me-1"></i> Ajouter un bien
                                                </a>
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
            if (confirm("Voulez-vous vraiment supprimer ce bien ? Cette action est irréversible.")) {
                window.location.href = "supprimer_bien.php?id=" + id;
            }
        }
    </script>
</body>
</html>