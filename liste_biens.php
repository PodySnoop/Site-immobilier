<?php
session_start();
require_once 'includes/db.php';

// Récupération des paramètres de filtrage
$type = isset($_GET['type']) ? $_GET['type'] : ''; // 'location' ou 'vente'
$tri = isset($_GET['tri']) ? $_GET['tri'] : 'date_desc'; // Valeur par défaut

// Construction de la requête SQL
$sql = "SELECT b.*, u.nom as nom_utilisateur, u.email as email_utilisateur 
        FROM biens b 
        JOIN utilisateurs u ON b.utilisateur_id = u.ID 
        WHERE b.est_valide = 1";

// Filtrage par type
if ($type === 'location' || $type === 'vente') {
    $sql .= " AND b.type_offre = ?";
    $params = [$type];
    $types = 's';
} else {
    $params = [];
    $types = '';
}

// Gestion du tri
switch ($tri) {
    case 'prix_asc':
        $sql .= " ORDER BY b.prix ASC";
        break;
    case 'prix_desc':
        $sql .= " ORDER BY b.prix DESC";
        break;
    case 'surface_asc':
        $sql .= " ORDER BY b.surface ASC";
        break;
    case 'surface_desc':
        $sql .= " ORDER BY b.surface DESC";
        break;
    case 'date_asc':
        $sql .= " ORDER BY b.date_creation ASC";
        break;
    default: // date_desc
        $sql .= " ORDER BY b.date_creation DESC";
}

// Préparation et exécution de la requête
$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
$biens = $result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Liste des biens - Pollux Immobilier</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
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
        .biens-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 25px;
            margin-top: 20px;
        }
        .bien-card {
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            overflow: hidden;
            transition: transform 0.3s;
        }
        .bien-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .bien-image {
            width: 100%;
            height: 200px;
            object-fit: cover;
        }
        .bien-info {
            padding: 15px;
        }
        .bien-type {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 0.8em;
            font-weight: bold;
            margin-bottom: 10px;
        }
        .location { background-color: #e3f2fd; color: #1976d2; }
        .vente { background-color: #e8f5e9; color: #2e7d32; }
        .bien-prix {
            font-size: 1.4em;
            font-weight: bold;
            color: #2c3e50;
            margin: 10px 0;
        }
        .bien-titre {
            font-size: 1.2em;
            margin: 10px 0;
        }
        .bien-details {
            display: flex;
            gap: 15px;
            color: #666;
            font-size: 0.9em;
            margin: 10px 0;
        }
        .bien-details span {
            display: flex;
            align-items: center;
            gap: 5px;
        }
        .btn-voir {
            display: inline-block;
            background: #3498db;
            color: white;
            padding: 8px 15px;
            text-decoration: none;
            border-radius: 4px;
            margin-top: 10px;
            text-align: center;
            width: 100%;
            box-sizing: border-box;
        }
        .btn-voir:hover {
            background: #2980b9;
        }
        .no-results {
            grid-column: 1 / -1;
            text-align: center;
            padding: 40px;
            color: #666;
        }
    </style>
</head>
<body>
    <?php include_once 'includes/header.php'; ?>
    
    <div class="container" style="max-width: 1200px; margin: 30px auto; padding: 0 15px;">
        <h1>Liste des biens immobiliers</h1>
        
        <div class="filters">
            <div class="filter-group">
                <label for="type">Type de bien</label>
                <select id="type" onchange="filterBiens()">
                    <option value="">Tous les biens</option>
                    <option value="location" <?php echo $type === 'location' ? 'selected' : ''; ?>>Location</option>
                    <option value="vente" <?php echo $type === 'vente' ? 'selected' : ''; ?>>Vente</option>
                </select>
            </div>
            
            <div class="filter-group">
                <label for="tri">Trier par</label>
                <select id="tri" onchange="filterBiens()">
                    <option value="date_desc" <?php echo $tri === 'date_desc' ? 'selected' : ''; ?>>Date (récent)</option>
                    <option value="date_asc" <?php echo $tri === 'date_asc' ? 'selected' : ''; ?>>Date (ancien)</option>
                    <option value="prix_asc" <?php echo $tri === 'prix_asc' ? 'selected' : ''; ?>>Prix croissant</option>
                    <option value="prix_desc" <?php echo $tri === 'prix_desc' ? 'selected' : ''; ?>>Prix décroissant</option>
                    <option value="surface_asc" <?php echo $tri === 'surface_asc' ? 'selected' : ''; ?>>Surface croissante</option>
                    <option value="surface_desc" <?php echo $tri === 'surface_desc' ? 'selected' : ''; ?>>Surface décroissante</option>
                </select>
            </div>
            
            <?php if (isset($_SESSION['utilisateur']) && $_SESSION['utilisateur']['role'] === 'admin'): ?>
                <a href="ajouter_bien.php" class="btn" style="margin-left: auto;">
                    <i class="fas fa-plus"></i> Ajouter un bien
                </a>
            <?php endif; ?>
        </div>
        
        <div class="biens-grid">
            <?php if (count($biens) > 0): ?>
                <?php foreach ($biens as $bien): 
                    $image_dir = 'images/' . ($bien['type_offre'] === 'location' ? 'location' : 'vente') . '/';
                    $images = glob($image_dir . $bien['id'] . '_*.{jpg,jpeg,png,gif}', GLOB_BRACE);
                    $image_url = !empty($images) ? $images[0] : 'images/placeholder.jpg';
                ?>
                    <div class="bien-card">
                        <img src="<?php echo htmlspecialchars($image_url); ?>" alt="<?php echo htmlspecialchars($bien['titre']); ?>" class="bien-image">
                        <div class="bien-info">
                            <span class="bien-type <?php echo $bien['type_offre']; ?>">
                                <?php echo $bien['type_offre'] === 'location' ? 'À louer' : 'À vendre'; ?>
                            </span>
                            <h3 class="bien-titre"><?php echo htmlspecialchars($bien['titre']); ?></h3>
                            <div class="bien-prix">
                                <?php echo number_format($bien['prix'], 0, ',', ' '); ?> €
                                <?php if ($bien['type_offre'] === 'location'): ?>/ mois<?php endif; ?>
                            </div>
                            <div class="bien-details">
                                <span title="Surface"><i class="fas fa-ruler-combined"></i> <?php echo $bien['surface']; ?> m²</span>
                                <span title="Pièces"><i class="fas fa-door-open"></i> <?php echo $bien['pieces']; ?> pièces</span>
                                <span title="Chambres"><i class="fas fa-bed"></i> <?php echo $bien['chambres']; ?></span>
                            </div>
                            <a href="bien-details.php?id=<?php echo $bien['id']; ?>" class="btn-voir">
                                Voir les détails <i class="fas fa-arrow-right"></i>
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="no-results">
                    <i class="fas fa-search" style="font-size: 3em; margin-bottom: 15px; opacity: 0.5;"></i>
                    <h3>Aucun bien ne correspond à votre recherche</h3>
                    <p>Essayez de modifier vos critères de recherche</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <?php include_once 'includes/footer.php'; ?>
    
    <script>
        function filterBiens() {
            const type = document.getElementById('type').value;
            const tri = document.getElementById('tri').value;
            let url = 'liste_bien.php?';
            
            if (type) url += `type=${type}&`;
            if (tri) url += `tri=${tri}`;
            
            window.location.href = url;
        }
    </script>
</body>
</html>
