<?php
session_start();

// Vérifier que l'utilisateur est connecté et est un admin
if (!isset($_SESSION['utilisateur_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

require_once '../includes/db.php';

// Gestion de la pagination
$messages_par_page = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $messages_par_page;

// Compter le nombre total de messages
$count_query = "SELECT COUNT(*) as total FROM contact_messages";
$count_result = $conn->query($count_query);
$total_messages = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total_messages / $messages_par_page);

// Récupérer les messages de contact avec pagination
$query = "SELECT * FROM contact_messages ORDER BY created_at DESC LIMIT ? OFFSET ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $messages_par_page, $offset);
$stmt->execute();
$result = $stmt->get_result();

$pageTitle = 'Messages de contact - Administration';
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?></title>
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
            background-color: #117c84;
            color: white;
            font-weight: bold;
        }
        tr:hover {
            background-color: #f5f5f5;
        }
        .message-content {
            max-width: 300px;
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
        .btn-traiter {
            background-color: #3498db;
        }
        .btn-supprimer {
            background-color: #e74c3c;
        }
        .btn-traiter:hover, .btn-supprimer:hover {
            opacity: 0.9;
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
        .pagination {
            display: flex;
            justify-content: center;
            margin-top: 20px;
            gap: 5px;
        }
        .pagination a, .pagination span {
            padding: 8px 12px;
            border: 1px solid #ddd;
            text-decoration: none;
            color: #117c84;
            border-radius: 4px;
        }
        .pagination a:hover {
            background-color: #f0f0f0;
        }
        .pagination .active {
            background-color: #117c84;
            color: white;
            border-color: #117c84;
        }
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border: 1px solid transparent;
            border-radius: 4px;
        }
        .alert-success {
            color: #155724;
            background-color: #d4edda;
            border-color: #c3e6cb;
        }
    </style>
</head>
<body>
    <div class="container">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h1><i class="fas fa-envelope"></i> Messages de contact</h1>
            <a href="admin.php" class="btn-retour">
                <i class="fas fa-arrow-left"></i> Retour au tableau de bord
            </a>
        </div>
    
    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i> Le message a été marqué comme traité avec succès.
        </div>
    <?php endif; ?>

    <div class="table-responsive">
        <?php if ($result->num_rows > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Nom</th>
                        <th>Email</th>
                        <th>Sujet</th>
                        <th>Message</th>
                        <th>Statut</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?= date('d/m/Y H:i', strtotime($row['created_at'])) ?></td>
                            <td><?= htmlspecialchars($row['name']) ?></td>
                            <td><?= htmlspecialchars($row['email']) ?></td>
                            <td><?= htmlspecialchars($row['subject']) ?></td>
                            <td class="message-content" title="<?= htmlspecialchars($row['message']) ?>">
                                <?= htmlspecialchars($row['message']) ?>
                            </td>
                            <td>
                                
                            <?php if ($row['is_traite']): ?>
                    <span style="background-color: #2ecc71; color: white; padding: 3px 8px; border-radius: 4px; font-size: 0.8em;">
                        <i class="fas fa-check"></i> Traité
                    </span>
                            <?php else: ?>
                            <?php if ($row['is_read']): ?>
                                    <span style="background-color: #1a63d1ff; color: white; padding: 3px 8px; border-radius: 4px; font-size: 0.8em;">
                                        <i class="fas fa-check"></i> Vu
                                    </span>
                                <?php else: ?>
                                    <span style="background-color: #f39c12; color: white; padding: 3px 8px; border-radius: 4px; font-size: 0.8em;">
                                        <i class="fas fa-circle"></i> Nouveau
                                    </span>
                                <?php endif; ?>
                                <?php endif; ?>
                            </td>
                            <td class="action-buttons">
                                <a href="view_message.php?id=<?= $row['id'] ?>" class="btn-traiter" title="Voir le message">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="traitement.php?id=<?= $row['id'] ?>" class="btn-traiter" title="Marquer comme traité">
                                    <i class="fas fa-check"></i>
                                </a>
                                <a href="delete_message.php?id=<?= $row['id'] ?>" class="btn-supprimer" 
                                   onclick="return confirm('Êtes-vous sûr de vouloir supprimer ce message ?')" title="Supprimer">
                                    <i class="fas fa-trash"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>

            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
                <div class="pagination">
                    <?php if ($page > 1): ?>
                        <a href="?page=<?= $page - 1 ?>" aria-label="Précédent">
                            <i class="fas fa-chevron-left"></i>
                        </a>
                    <?php endif; ?>
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <a href="?page=<?= $i ?>" class="<?= $i == $page ? 'active' : '' ?>">
                            <?= $i ?>
                        </a>
                    <?php endfor; ?>
                    <?php if ($page < $total_pages): ?>
                        <a href="?page=<?= $page + 1 ?>" aria-label="Suivant">
                            <i class="fas fa-chevron-right"></i>
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        <?php else: ?>
            <div style="text-align: center; padding: 50px 20px; color: #666;">
                <i class="fas fa-inbox" style="font-size: 3em; color: #ddd; margin-bottom: 15px;"></i>
                <h3>Aucun message à afficher</h3>
                <p>Il n'y a aucun message de contact pour le moment.</p>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>

<style>
    .table th {
        background-color: #117c84;
        color: white;
    }
    
    .table td {
        vertical-align: middle;
    }
    
    .badge {
        font-size: 0.9em;
        padding: 0.35em 0.65em;
    }
    
    .btn-sm {
        margin: 2px 0;
    }
    
    .alert {
        margin: 20px 0;
        padding: 15px;
        border-radius: 4px;
    }
    
    .alert-success {
        background-color: #d4edda;
        color: #155724;
        border: 1px solid #c3e6cb;
    }
</style>

<?php include_once '../includes/footer.php'; ?>
