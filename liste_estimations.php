<?php
session_start();
require_once './utilisateurs/connexion.php';

// Vérification admin
if (!isset($_SESSION['utilisateur_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// Récupération des paramètres de tri
$order_by = $_GET['order_by'] ?? 'date_demande';
$order_dir = isset($_GET['order_dir']) && strtoupper($_GET['order_dir']) === 'DESC' ? 'DESC' : 'ASC';

// Requête de base
$query = "SELECT e.*, 
          (SELECT COUNT(*) FROM favoris f WHERE f.annonce_id = e.id AND f.type_annonce = 'estimation') as favoris_count
          FROM estimations e
          ORDER BY $order_by $order_dir";

$result = $conn->query($query);
$estimations = $result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Gestion des estimations - Administration</title>
    <link rel="stylesheet" href="/Pollux_immobilier/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<style>
        .filters {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            align-items: center;
        }
        .filter-group {
            margin-right: 20px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        select, button {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .estimations-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 25px;
            margin-top: 20px;
        }
        .estimation-card {
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            overflow: hidden;
            transition: transform 0.3s;
            background: white;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .estimation-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.15);
        }
        .estimation-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px;
            position: relative;
        }
        .estimation-type {
            display: inline-block;
            background: rgba(255,255,255,0.2);
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 0.8em;
            font-weight: bold;
            margin-bottom: 10px;
        }
        .estimation-date {
            font-size: 0.9em;
            opacity: 0.9;
        }
        .estimation-info {
            padding: 15px;
        }
        .estimation-prix {
            font-size: 1.4em;
            font-weight: bold;
            color: #2c3e50;
            margin: 10px 0;
        }
        .estimation-titre {
            font-size: 1.2em;
            margin: 10px 0;
            color: #333;
        }
        .estimation-details {
            display: flex;
            gap: 15px;
            color: #666;
            font-size: 0.9em;
            margin: 10px 0;
            flex-wrap: wrap;
        }
        .estimation-details span {
            display: flex;
            align-items: center;
            gap: 5px;
        }
        .estimation-actions {
            display: flex;
            gap: 10px;
            margin-top: 15px;
        }
        .btn-action {
            flex: 1;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 8px 12px;
            text-decoration: none;
            border-radius: 4px;
            font-size: 0.9em;
            transition: background-color 0.3s;
        }
        .btn-view {
            background: #3498db;
            color: white;
        }
        .btn-view:hover {
            background: #2980b9;
        }
        .btn-edit {
            background: #f39c12;
            color: white;
        }
        .btn-edit:hover {
            background: #e67e22;
        }
        .btn-delete {
            background: #e74c3c;
            color: white;
        }
        .btn-delete:hover {
            background: #c0392b;
        }
        .no-results {
            grid-column: 1 / -1;
            text-align: center;
            padding: 40px;
            color: #666;
        }
        .favoris-badge {
            background: #27ae60;
            color: white;
            padding: 2px 6px;
            border-radius: 10px;
            font-size: 0.7em;
            margin-left: 5px;
        }
    </style>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="container">
        <h1>Gestion des estimations</h1>
        
        
        <div class="admin-actions"> 
            <a href="./utilisateurs/admin.php" class="btn"><i class="fas fa-arrow-left"></i> Retour au tableau de bord</a>
        </div>

        <div class="filters">
            <form method="get" class="filter-form">
                <select name="order_by">
                    <option value="date_demande" <?= $order_by === 'date_demande' ? 'selected' : '' ?>>Date de création</option>
                    <option value="nom" <?= $order_by === 'nom' ? 'selected' : '' ?>>Nom</option>
                    <option value="estimation_auto" <?= $order_by === 'estimation_auto' ? 'selected' : '' ?>>Montant estimé</option>
                </select>
                <select name="order_dir">
                    <option value="ASC" <?= $order_dir === 'ASC' ? 'selected' : '' ?>>Croissant</option>
                    <option value="DESC" <?= $order_dir === 'DESC' ? 'selected' : '' ?>>Décroissant</option>
                </select>
                <button type="submit" class="btn">Trier</button>
            </form>
        </div>

                <div class="estimations-grid">
            <?php if (count($estimations) > 0): ?>
                <?php foreach ($estimations as $estimation): ?>
                    <div class="estimation-card">
                        <div class="estimation-header">
                            <span class="estimation-type">Estimation</span>
                            <div class="estimation-date">
                                <i class="fas fa-calendar"></i>
                                <?= date('d/m/Y H:i', strtotime($estimation['date_demande'])) ?>
                            </div>
                        </div>
                        
                        <div class="estimation-info">
                            <h3 class="estimation-titre">
                                <?= htmlspecialchars($estimation['nom']) ?>
                                <?php if ($estimation['favoris_count'] > 0): ?>
                                    <span class="favoris-badge"><?= $estimation['favoris_count'] ?> ❤️</span>
                                <?php endif; ?>
                            </h3>
                            
                            <div class="estimation-prix">
                                <?= number_format($estimation['estimation_prix'], 0, ',', ' ') ?> €
                            </div>
                            
                            <div class="estimation-details">
                                <span title="Type">
                                    <i class="fas fa-home"></i> 
                                    <?= htmlspecialchars($estimation['type_annonce']) ?>
                                </span>
                                <span title="Surface">
                                    <i class="fas fa-ruler-combined"></i> 
                                    <?= number_format($estimation['surface'], 0, ',', ' ') ?> m²
                                </span>
                                <span title="Email">
                                    <i class="fas fa-envelope"></i> 
                                    <?= htmlspecialchars($estimation['email']) ?>
                                </span>
                            </div>
                            
                            <div class="estimation-actions">
                                <a href="voir_estimation.php?id=<?= $estimation['id'] ?>" class="btn-action btn-view">
                                    <i class="fas fa-eye"></i> Voir
                                </a>
                                <a href="modifier_estimation.php?id=<?= $estimation['id'] ?>" class="btn-action btn-edit">
                                    <i class="fas fa-edit"></i> Modifier
                                </a>
                                <form action="supprimer_estimation.php" method="POST" style="flex: 1;" onsubmit="return confirm('Confirmer la suppression ?')">
                                    <input type="hidden" name="id" value="<?= $estimation['id'] ?>">
                                    <button type="submit" class="btn-action btn-delete">
                                        <i class="fas fa-trash"></i> Supprimer
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="no-results">
                    <i class="fas fa-search" style="font-size: 3em; margin-bottom: 15px; opacity: 0.5;"></i>
                    <h3>Aucune estimation trouvée</h3>
                    <p>Aucune demande d'estimation n'a été soumise pour le moment</p>
                </div>
            <?php endif; ?>
        </div>

        
    <?php include 'includes/footer.php'; ?>
    
    <script>
    // Script pour la confirmation de suppression
    document.querySelectorAll('.confirm-delete').forEach(button => {
        button.addEventListener('click', (e) => {
            if (!confirm('Êtes-vous sûr de vouloir supprimer cette estimation ?')) {
                e.preventDefault();
            }
        });
    });
    </script>
</body>
</html>