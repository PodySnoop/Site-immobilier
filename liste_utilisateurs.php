<?php
session_start();

// Vérification que l'utilisateur est admin
if (!isset($_SESSION['utilisateur']) || $_SESSION['utilisateur']['role'] !== 'admin') {
    header('Location: ../index.php');
    exit();
}

require_once '../includes/db.php';

// Récupération de la liste des utilisateurs non administrateurs
$tri = isset($_GET['tri']) ? $_GET['tri'] : 'id';
$query = "SELECT id, nom, prenom, email, role, statut, date_creation 
          FROM utilisateurs 
          WHERE role != 'admin' 
          ORDER BY " . ($tri === 'date' ? 'date_creation' : 'id') . " DESC";
          
$result = $conn->query($query);

$utilisateurs = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $utilisateurs[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des utilisateurs - Pollux Immobilier</title>
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
        .badge-admin {
            background-color: #3498db;
            color: white;
        }
        .badge-user {
            background-color: #2ecc71;
            color: white;
        }
        .badge-inactive {
            background-color: #e74c3c;
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
        .btn-edit {
            background-color: #f39c12;
        }
        .btn-delete {
            background-color: #e74c3c;
        }
        .btn-edit:hover, .btn-delete:hover {
            opacity: 0.9;
        }
    </style>
</head>
<body>
    <div class="container">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h1><i class="fas fa-users-cog"></i> Gestion des utilisateurs</h1>
            <a href="admin.php" class="btn" style="background-color: #3498db; color: white; padding: 8px 15px; text-decoration: none; border-radius: 4px; display: inline-flex; align-items: center;">
                <i class="fas fa-arrow-left" style="margin-right: 5px;"></i> Retour au tableau de bord
            </a>
        </div>

        <?php if (isset($_GET['message'])): ?>
    <?php
        $messages = [
            'modifie' => ['🔄 Utilisateur modifié avec succès.', 'alert-info'],
            'supprime' => ['✅ Utilisateur supprimé avec succès.', 'alert-success'],
            'erreur_id' => ['⚠️ ID utilisateur manquant ou invalide.', 'alert-warning'],
            'introuvable' => ['❌ Utilisateur introuvable.', 'alert-danger']
        ];
        $type = $_GET['message'];
        if (isset($messages[$type])) {
            [$texte, $classe] = $messages[$type];
            echo "<div class='alert $classe alert-dismissible fade show' role='alert'>$texte
                    <button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button>
                  </div>";
        }
    ?>
<?php endif; ?>
        <?php if (isset($_SESSION['message'])): ?>
            <div class="alert alert-success">
                <?php 
                echo $_SESSION['message']; 
                unset($_SESSION['message']);
                ?>
            </div>
        <?php endif; ?>

        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nom</th>
                        <th>Prénom</th>
                        <th>Email</th>
                        <th>Rôle</th>
                        <th>Statut</th>
                        <th>Inscrit le</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($utilisateurs as $utilisateur): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($utilisateur['id']); ?></td>
                        <td><?php echo htmlspecialchars($utilisateur['nom']); ?></td>
                        <td><?php echo htmlspecialchars($utilisateur['prenom']); ?></td>
                        <td><?php echo htmlspecialchars($utilisateur['email']); ?></td>
                        <td>
                            <span class="badge <?php echo $utilisateur['role'] === 'admin' ? 'badge-admin' : 'badge-user'; ?>">
                                <?php echo htmlspecialchars($utilisateur['role']); ?>
                            </span>
                        </td>
                        <td>
                        </td>
                        <td><?php echo date('d/m/Y', strtotime($utilisateur['date_creation'])); ?></td>
                        <td class="action-buttons">
                            <a href="modifier_utilisateur.php?id=<?php echo $utilisateur['id']; ?>" class="btn-edit">
                                <i class="fas fa-edit"></i> Modifier
                            </a>
                            <?php if ($utilisateur['id'] != $_SESSION['utilisateur']['id']): ?>
                                <a href="supprimer_utilisateur.php?id=<?php echo $utilisateur['id']; ?>" 
                                   class="btn-delete"
                                   onclick="return confirm('Êtes-vous sûr de vouloir supprimer cet utilisateur ?')">
                                    <i class="fas fa-trash-alt"></i> Supprimer
                                </a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
