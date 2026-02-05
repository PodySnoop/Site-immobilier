<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../includes/db.php';

if (!isset($_SESSION['utilisateur_id'])) {
    header('Location: login.php');
    exit();
}

$utilisateur_id = $_SESSION['utilisateur_id'];
$favoris = [];

$stmt = $conn->prepare("SELECT annonce_id, type_annonce FROM favoris WHERE utilisateur_id = ?");
if (!$stmt) {
    die("Erreur préparation favoris : " . $conn->error);
}
$stmt->bind_param("i", $utilisateur_id);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $table = ($row['type_annonce'] === 'location') ? 'biens_en_location' : 'biens_à_vendre';
    $id_annonce = $row['annonce_id'];

    $stmt_bien = $conn->prepare("SELECT * FROM `$table` WHERE id = ?");
    if (!$stmt_bien) {
        die("Erreur préparation bien : " . $conn->error);
    }
    $stmt_bien->bind_param("i", $id_annonce);
    $stmt_bien->execute();
    $res_bien = $stmt_bien->get_result();

    if ($bien = $res_bien->fetch_assoc()) {
        $bien['type_annonce'] = $row['type_annonce'];
        $favoris[] = $bien;
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mes Favoris - Pollux Immobilier</title>
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
            max-width: 1200px;
            margin: 0 auto;
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #2c3e50;
            text-align: center;
            margin-bottom: 30px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background-color: #f8f9fa;
            font-weight: bold;
        }
        tr:hover {
            background-color: #f5f5f5;
        }
        .badge {
            padding: 5px 10px;
            border-radius: 4px;
            font-size: 0.8em;
            font-weight: bold;
            text-transform: uppercase;
        }
        .badge-location {
            background-color: #3498db;
            color: white;
        }
        .badge-vente {
            background-color: #2ecc71;
            color: white;
        }
        .action-buttons a {
            color: #fff;
            padding: 5px 10px;
            border-radius: 4px;
            text-decoration: none;
            margin-right: 5px;
            font-size: 0.9em;
        }
        .btn-voir {
            background-color: #3498db;
        }
        .btn-supprimer {
            background-color: #e74c3c;
        }
        .btn-voir:hover, .btn-supprimer:hover {
            opacity: 0.9;
        }
        .property-image {
            width: 80px;
            height: 60px;
            object-fit: cover;
            border-radius: 4px;
        }
        .no-favorites {
            text-align: center;
            padding: 50px 20px;
            color: #666;
        }
        .no-favorites i {
            font-size: 3em;
            color: #ddd;
            margin-bottom: 15px;
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
    <div class="container">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h1><i class="fas fa-heart"></i> Mes Favoris</h1>
            <a href="profil_utilisateur.php" class="btn-retour">
                <i class="fas fa-arrow-left"></i> Retour au profil
            </a>
        </div>
<?php if (count($favoris) > 0): ?>
    <table>
        <thead>
            <tr>
                <th>Titre</th>
                <th>Détails</th>
                <th>Type</th>
                <th>Prix</th>
                <th>Localisation</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($favoris as $bien): 
                $isLocation = ($bien['type_annonce'] ?? 'vente') === 'location';
                $price = isset($bien['prix']) ? number_format($bien['prix'], 0, ',', ' ') : 'N/A';
                $imageUrl = !empty($bien['image']) ? '../uploads/biens/' . htmlspecialchars($bien['image']) : 'https://via.placeholder.com/80x60?text=Pollux';
            ?>
                <tr>
                    <td>
                        <img src="<?= $imageUrl ?>" alt="<?= htmlspecialchars($bien['titre'] ?? 'Bien immobilier') ?>" class="property-image">
                    </td>
                    <td>
                        <strong><?= htmlspecialchars($bien['titre'] ?? 'Sans titre') ?></strong><br>
                        <small><?= isset($bien['surface']) ? $bien['surface'] . ' m²' : '—' ?></small>
                    </td>
                    <td>
                        <span class="badge <?= $isLocation ? 'badge-location' : 'badge-vente' ?>">
                            <?= $isLocation ? 'Location' : 'Vente' ?>
                        </span>
                    </td>
                    <td>
                        <?= $price ?> €<?= $isLocation ? '/mois' : '' ?>
                    </td>
                    <td><?= htmlspecialchars($bien['localisation'] ?? 'Non spécifiée') ?></td>
                    <td class="action-buttons">
                        <a href="#" class="btn-voir" title="Voir le bien">
                            <i class="fas fa-eye"></i>
                        </a>
                        <form method="POST" action="../supprimer_favoris.php" style="display:inline;">
                            <input type="hidden" name="id_annonce" value="<?= $bien['annonce_id'] ?? $bien['ID'] ?>">
                            <input type="hidden" name="type_annonce" value="<?= $bien['type_annonce'] ?>">
                            <button type="submit" class="btn-supprimer" title="Retirer des favoris" style="border: none; cursor: pointer;">
                                <i class="fas fa-trash"></i>
                            </button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php else: ?>
    <div class="no-favorites">
        <i class="fas fa-heart-broken"></i>
        <h3>Aucun favori enregistré</h3>
        <p>Ajoutez des biens à vos favoris pour les retrouver facilement ici.</p>
        <a href="../index.php" class="btn-voir" style="padding: 8px 15px; display: inline-block; margin-top: 10px;">
            <i class="fas fa-search"></i> Retour à la recherche
        </a>
    </div>
<?php endif; ?>
    </div>
</body>
</html>