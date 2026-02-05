<?php
session_start();

// Vérification que l'utilisateur est un super admin
if (!isset($_SESSION['utilisateur']) || !isset($_SESSION['utilisateur']['is_super_admin']) || !$_SESSION['utilisateur']['is_super_admin']) {
    $_SESSION['message'] = [
        'type' => 'error',
        'text' => 'Accès refusé. Vous devez être super administrateur pour accéder à cette page.'
    ];
    header("Location: admin.php");
    exit();
}

require_once '../includes/db.php';

// Initialisation des variables
$message = [];

// Traitement de la suppression d'un admin
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_admin']) && isset($_POST['admin_id'])) {
    $admin_id = intval($_POST['admin_id']);
    $current_admin_id = $_SESSION['utilisateur']['id'];
    
    // Empêcher l'auto-suppression
    if ($admin_id === $current_admin_id) {
        $message = [
            'type' => 'error',
            'text' => 'Vous ne pouvez pas supprimer votre propre compte.'
        ];
    } else {
        // Supprimer l'administrateur
        $delete_query = $conn->prepare("DELETE FROM utilisateurs WHERE id = ? AND role = 'admin'");
        $delete_query->bind_param("i", $admin_id);
        
        if ($delete_query->execute()) {
            $message = [
                'type' => 'success',
                'text' => 'Administrateur supprimé avec succès.'
            ];
        } else {
            $message = [
                'type' => 'error',
                'text' => 'Une erreur est survenue lors de la suppression.'
            ];
        }
    }
}

// Récupération de la liste des administrateurs
$query = "SELECT id, nom, prenom, email, date_creation, is_super_admin 
          FROM utilisateurs 
          WHERE role = 'admin' 
          ORDER BY is_super_admin DESC, nom, prenom";
          
$result = $conn->query($query);

$admins = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $admins[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des administrateurs - Pollux Immobilier</title>
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
            margin-bottom: 20px;
        }
        .btn {
            display: inline-block;
            background: #3498db;
            color: #fff;
            padding: 8px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            font-size: 14px;
        }
        .btn-danger {
            background: #e74c3c;
        }
        .btn:hover {
            opacity: 0.9;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        th, td {
            padding: 12px;
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
        .action-buttons {
            display: flex;
            gap: 10px;
        }
        .super-admin {
            color: #e67e22;
            font-weight: bold;
        }
        .message {
            padding: 10px;
            margin: 10px 0;
            border-radius: 4px;
            text-align: center;
        }
        .message.success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .message.error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
    </style>
</head>
<body>
    <div class="container">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h1><i class="fas fa-user-shield"></i> Gestion des administrateurs</h1>
            <div>
                <a href="ajouter_utilisateur.php?role=admin" class="btn">
                    <i class="fas fa-plus"></i> Ajouter un administrateur
                </a>
                <a href="admin.php" class="btn" style="background-color: #6c757d;">
                    <i class="fas fa-arrow-left"></i> Retour au tableau de bord
                </a>
            </div>
        </div>

        <?php if (!empty($message)): ?>
            <div class="message <?= $message['type'] ?>">
                <?= htmlspecialchars($message['text']) ?>
            </div>
        <?php endif; ?>

        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nom</th>
                    <th>Prénom</th>
                    <th>Email</th>
                    <th>Date d'inscription</th>
                    <th>Rôle</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($admins as $admin): ?>
                    <tr>
                        <td><?= htmlspecialchars($admin['id']) ?></td>
                        <td><?= htmlspecialchars($admin['nom']) ?></td>
                        <td><?= htmlspecialchars($admin['prenom']) ?></td>
                        <td><?= htmlspecialchars($admin['email']) ?></td>
                        <td><?= date('d/m/Y', strtotime($admin['date_creation'])) ?></td>
                        <td>
                            <?php if ($admin['is_super_admin']): ?>
                                <span class="super-admin">Super Admin</span>
                            <?php else: ?>
                                Administrateur
                            <?php endif; ?>
                        </td>
                        <td class="action-buttons">
                            <?php if (!$admin['is_super_admin'] || $admin['id'] == $_SESSION['utilisateur']['id']): ?>
                                <a href="modifier_utilisateur.php?id=<?= $admin['id'] ?>" class="btn">
                                    <i class="fas fa-edit"></i>
                                </a>
                            <?php endif; ?>
                            
                            <?php if (!$admin['is_super_admin'] && $admin['id'] != $_SESSION['utilisateur']['id']): ?>
                                <form method="POST" style="display: inline;" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer cet administrateur ?');">
                                    <input type="hidden" name="admin_id" value="<?= $admin['id'] ?>">
                                    <button type="submit" name="delete_admin" class="btn btn-danger">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <script>
        // Script pour confirmer la suppression
        function confirmDelete(adminId, isSuperAdmin) {
            if (isSuperAdmin) {
                alert('Impossible de supprimer un super administrateur.');
                return false;
            }
            return confirm('Êtes-vous sûr de vouloir supprimer cet administrateur ?');
        }
    </script>
</body>
</html>