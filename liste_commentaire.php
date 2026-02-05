<?php
session_start();

if (!isset($_SESSION['utilisateur']) || $_SESSION['utilisateur']['role'] !== 'admin') {
    header('Location: ../index.php');
    exit();
}

require_once '../includes/db.php';

$conn = new mysqli("localhost", "root", "", "bd_pollux");
if ($conn->connect_error) {
    die("Échec de la connexion : " . $conn->connect_error);
}

// Récupère tous les commentaires avec le nom de l'auteur
$sql = "
    SELECT c.id, c.contenu, c.date_creation, u.nom AS nom_utilisateur
    FROM commentaires c
    LEFT JOIN utilisateurs u ON c.utilisateur_id = u.id
    ORDER BY c.date_creation DESC
";
$commentaires = [];
$result = $conn->query($sql);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $commentaires[] = $row;
    }
} else {
    echo "Erreur lors de la récupération des commentaires : " . $conn->error;
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des commentaires - Pollux Immobilier</title>
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
        .comment-content {
            max-width: 400px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .action-buttons a {
            color: #fff;
            padding: 5px 10px;
            border-radius: 4px;
            text-decoration: none;
            margin-right: 5px;
            font-size: 0.9em;
        }
        .btn-supprimer {
            background-color: #e74c3c;
        }
        .btn-supprimer:hover {
            opacity: 0.9;
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
            <h1><i class="fas fa-comments"></i> Gestion des commentaires</h1>
            <a href="admin.php" class="btn-retour">
                <i class="fas fa-arrow-left"></i> Retour au tableau de bord
            </a>
        </div>
        <?php if (isset($_GET['message'])): ?>
    <?php
        $messages = [
            'suppression_reussie' => ['✅ Commentaire supprimé avec succès.', '#2ecc71'],
            'commentaire_introuvable' => ['❌ Commentaire introuvable.', '#e74c3c'],
            'erreur_id' => ['⚠️ Aucun ID de commentaire fourni.', '#f39c12']
        ];
        $type = $_GET['message'];
        if (isset($messages[$type])) {
            [$texte, $couleur] = $messages[$type];
            echo "<div style='background-color: $couleur; color: white; padding: 12px 20px; border-radius: 6px; margin-bottom: 20px; font-weight: bold;'>$texte</div>";
        }
    ?>
<?php endif; ?>

        <?php if (!empty($commentaires)): ?>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Contenu</th>
                        <th>Utilisateur</th>
                        <th>Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($commentaires as $commentaire): ?>
                        <tr>
                            <td><?= $commentaire['id'] ?></td>
                            <td class="comment-content" title="<?= htmlspecialchars($commentaire['contenu']) ?>">
                                <?= htmlspecialchars($commentaire['contenu']) ?>
                            </td>
                            <td><?= $commentaire['nom_utilisateur'] ?? 'Anonyme' ?></td>
                            <td><?= date('d/m/Y H:i', strtotime($commentaire['date_creation'])) ?></td>
                            <td class="action-buttons">
                                <form action="supprimer_commentaire.php" method="POST" style="display:inline;">
                                    <input type="hidden" name="commentaire_id" value="<?= $commentaire['id'] ?>">
                                    <button type="submit" 
                                            class="btn-supprimer" 
                                            onclick="return confirm('Êtes-vous sûr de vouloir supprimer ce commentaire ?')"
                                            style="border: none; color: white; cursor: pointer; padding: 5px 10px; border-radius: 4px;">
                                        <i class="fas fa-trash"></i> Supprimer
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div style="text-align: center; padding: 50px 20px; color: #666;">
                <i class="fas fa-comment-slash" style="font-size: 3em; color: #ddd; margin-bottom: 15px;"></i>
                <h3>Aucun commentaire à afficher</h3>
                <p>Il n'y a aucun commentaire pour le moment.</p>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>