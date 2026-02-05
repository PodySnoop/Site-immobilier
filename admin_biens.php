<?php
// Vérification de la session et des droits d'administration
session_start();
if (!isset($_SESSION['utilisateur_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: index.php');
    exit();
}

// Inclure la connexion à la base de données
require_once 'connexion.php';
$message = '';
$error = '';

// Traitement des actions (validation/rejet)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && isset($_POST['bien_id'])) {
        $bien_id = intval($_POST['bien_id']);
        $action = $_POST['action'];
        
        if ($action === 'valider') {
            // 1. Valider le bien
            $stmt = $conn->prepare("UPDATE biens SET est_valide = 1, date_validation = NOW(), validateur_id = ? WHERE id = ?");
            if ($stmt) {
                $stmt->bind_param("ii", $_SESSION['utilisateur_id'], $bien_id);
                if ($stmt->execute()) {
                    $message = "Le bien a été validé avec succès.";
        
                    // 2. Récupérer le proposeur du bien
                    $stmt_proposeur = $conn->prepare("SELECT utilisateur_id FROM biens WHERE id = ?");
                    $stmt_proposeur->bind_param("i", $bien_id);
                    $stmt_proposeur->execute();
                    $result = $stmt_proposeur->get_result();
                    $row = $result->fetch_assoc();
                    $id_proposeur = $row['utilisateur_id'];
        
                    // 3. Enregistrer l'activité
                    $titre = "Validation de bien";
                    $details = "Le bien ID $bien_id proposé par utilisateur ID $id_proposeur a été validé par admin ID " . $_SESSION['utilisateur_id'];
                    enregistrerActivite($conn, $_SESSION['utilisateur_id'], $id_proposeur, $titre, $details);
                } else {
                    $error = "Erreur lors de la validation du bien: " . $stmt->error;
                }
            }
        }
    }
}

function getBiensEnAttente($conn, $table) {
    $biens = [];
    $sql = "
        SELECT b.*, 
               u.nom AS nom_proposeur, 
               u.email AS email_proposeur
        FROM `$table` b
        JOIN utilisateurs u ON b.utilisateur_id = u.id
        WHERE b.est_valide = 0
        ORDER BY b.date_creation ASC
    ";
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->execute();
        $result = $stmt->get_result();
        $biens = $result->fetch_all(MYSQLI_ASSOC);
    }
    return $biens;
}

function getBiensValides($conn, $table) {
    $biens = [];
    $sql = "
        SELECT b.*, 
               u.nom AS nom_proposeur, 
               u.email AS email_proposeur,
               a.date_activite AS date_validation,
               admin.nom AS nom_validateur, 
               admin.email AS email_validateur
        FROM `$table` b
        JOIN utilisateurs u ON b.utilisateur_id = u.id
        JOIN activites a ON a.proposeur_id = b.utilisateur_id AND a.details LIKE CONCAT('%ID ', b.id, '%')
        JOIN utilisateurs admin ON a.utilisateur_id = admin.id
        WHERE b.est_valide = 1
        AND a.titre_activite = 'Validation de bien'
        ORDER BY a.date_activite DESC
        LIMIT 20
    ";
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->execute();
        $result = $stmt->get_result();
        $biens = $result->fetch_all(MYSQLI_ASSOC);
    }
    return $biens;
}

function enregistrerActivite($conn, $admin_id, $proposeur_id, $titre, $details) {
    $sql = "INSERT INTO activites (date_activite, utilisateur_id, proposeur_id, titre_activite, details)
            VALUES (NOW(), ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("iiss", $admin_id, $proposeur_id, $titre, $details);
        $stmt->execute();
    }
}

// Appels pour chaque type de bien
$biens_en_attente = array_merge(
    getBiensEnAttente($conn, 'biens_en_location'),
    getBiensEnAttente($conn, 'biens_à_vendre')
);

$biens_valides = array_merge(
    getBiensValides($conn, 'biens_en_location'),
    getBiensValides($conn, 'biens_à_vendre')
);

?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des biens - Administration</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="pages-common.css">
    <style>
        .container {
            max-width: 1200px;
            margin: 20px auto;
            padding: 0 15px;
        }
        .section {
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            margin-bottom: 30px;
            overflow: hidden;
        }
        .section-header {
            background: #2c3e50;
            color: white;
            padding: 15px 20px;
            margin: 0;
        }
        .section-body {
            padding: 20px;
        }
        .bien-card {
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 15px;
            margin-bottom: 15px;
            position: relative;
        }
        .bien-card h3 {
            margin-top: 0;
            color: #2c3e50;
        }
        .bien-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            margin: 10px 0;
            color: #666;
            font-size: 0.9em;
        }
        .bien-actions {
            margin-top: 15px;
            display: flex;
            gap: 10px;
        }
        .btn {
            padding: 8px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            font-size: 0.9em;
        }
        .btn-success {
            background: #28a745;
            color: white;
        }
        .btn-danger {
            background: #dc3545;
            color: white;
        }
        .btn-info {
            background: #17a2b8;
            color: white;
        }
        .message {
            padding: 15px;
            margin: 15px 0;
            border-radius: 4px;
        }
        .success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .badge {
            display: inline-block;
            padding: 3px 7px;
            border-radius: 10px;
            font-size: 0.8em;
            font-weight: bold;
            margin-left: 10px;
        }
        .badge-warning {
            background: #ffc107;
            color: #000;
        }
        .badge-success {
            background: #28a745;
            color: white;
        }
    </style>
</head>
<body>
    <?php include_once '../includes/header.php'; ?>
    
    <div class="container">
        <h1>Gestion des biens immobiliers</h1>
        
     <!-- Bouton de retour au tableau de bord -->
<div class="admin-actions">
    <a href="admin.php" class="btn btn-info">
        <i class="fas fa-arrow-left"></i> 
        Retour au tableau de bord
    </a>
</div>   
        <?php if ($message): ?>
            <div class="message success"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="message error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <!-- Section des biens en attente de validation -->
        <div class="section">
            <h2 class="section-header">
                Biens en attente de validation 
                <span class="badge badge-warning"><?php echo is_array($biens_en_attente) ? count($biens_en_attente) : 0; ?></span>
            </h2>
            <div class="section-body">
                <?php if (empty($biens_en_attente)): ?>
                    <p>Aucun bien en attente de validation.</p>
                <?php else: ?>
                    <?php foreach ($biens_en_attente as $bien): ?>
                        <div class="bien-card">
                            <h3><?php echo htmlspecialchars($bien['titre'] ?? 'Sans titre'); ?></h3>

<div class="bien-meta">
    <span>Type: <?php echo ucfirst(htmlspecialchars($bien['type_bien'] ?? 'Non renseigné')); ?></span>

    <span>Surface: 
        <?php echo htmlspecialchars($bien['surface'] ?? '—'); ?> m²
    </span>

    <span>Pièces: 
        <?php echo htmlspecialchars($bien['nombre_pieces'] ?? '—'); ?>
    </span>

    <?php if (isset($bien['loyer'])): ?>
        <span>Loyer: 
            <?php echo number_format((float)$bien['loyer'], 0, ',', ' '); ?> €
        </span>
    <?php elseif (isset($bien['prix'])): ?>
        <span>Prix: 
            <?php echo number_format((float)$bien['prix'], 0, ',', ' '); ?> €
        </span>
    <?php endif; ?>

    <span>Localisation: 
        <?php echo htmlspecialchars($bien['localisation'] ?? 'Non renseignée'); ?>
    </span>

    <span>Déposé par: 
        <?php echo htmlspecialchars($bien['nom_proposeur'] ?? 'Inconnu'); ?>
        (<?php echo htmlspecialchars($bien['email_proposeur'] ?? '—'); ?>)
    </span>

    <span>Date: 
        <?php echo isset($bien['date_creation']) 
            ? date('d/m/Y H:i', strtotime($bien['date_creation'])) 
            : '—'; ?>
    </span>
</div>

<p><?php echo nl2br(htmlspecialchars($bien['description'] ?? 'Aucune description.')); ?></p>
            </div>
        </div>
        
        <div class="bien-actions">
            <form method="POST" action="" style="display: inline;">
                <input type="hidden" name="bien_id" value="<?php echo $bien['id'] ?? $bien['ID'] ?? 0; ?>">
                <input type="hidden" name="action" value="valider">
                <button type="submit" class="btn btn-success">Valider</button>
            </form>

            <form method="POST" action="" style="display: inline;">
                <input type="hidden" name="bien_id" value="<?php echo $bien['id'] ?? $bien['ID'] ?? 0; ?>">
                <input type="hidden" name="action" value="rejeter">
                <button type="submit" class="btn btn-danger"
                    onclick="return confirm('Êtes-vous sûr de vouloir rejeter ce bien ? Cette action est irréversible.');">
                    Rejeter
                </button>
            </form>
        </div>
    </div>
<?php endforeach; ?>
<?php endif; ?>

        <!-- Section des biens validés récemment -->
        <div class="section">
            <h2 class="section-header">
                Derniers biens validés
                <span class="badge badge-success"><?php echo is_array($biens_valides) ? count($biens_valides) : 0; ?></span>
            </h2>
            <div class="section-body">
                <?php if (empty($biens_valides)): ?>
                    <p>Aucun bien validé pour le moment.</p>
                <?php else: ?>
                    <?php foreach ($biens_valides as $bien): ?>
                        <div class="bien-card">
                            <h3>
                                <?php echo htmlspecialchars($bien['titre']); ?>
                                <span class="badge badge-success">Validé</span>
                            </h3>
                            <div class="bien-meta">
                                <span>Type: <?php echo ucfirst(htmlspecialchars($bien['type_bien'] ?? 'Non renseigné')); ?></span>

<span>Surface: <?php echo htmlspecialchars($bien['surface'] ?? '—'); ?> m²</span>

<span>Localisation: <?php echo htmlspecialchars($bien['localisation'] ?? 'Non renseignée'); ?></span>

<span>Validé le: 
    <?php echo isset($bien['date_validation']) 
        ? date('d/m/Y H:i', strtotime($bien['date_validation'])) 
        : '—'; ?>
</span>

<?php if (!empty($bien['utilisateur_id'])): ?>
    <span>Par: <?php echo htmlspecialchars($bien['utilisateur_id']); ?></span>
<?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <?php include_once '../includes/footer.php'; ?>
    
    <script>
        // Script pour confirmer le rejet d'un bien
        function confirmerRejet(event) {
            if (!confirm('Êtes-vous sûr de vouloir rejeter ce bien ? Cette action est irréversible.')) {
                event.preventDefault();
            }
        }
    </script>
</body>
</html>