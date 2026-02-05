<?php
session_start();
require_once('connexion.php');

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['utilisateur_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['utilisateur_id'];
$message = '';

// Récupérer les biens de l'utilisateur (location et vente)
$sql = "
    SELECT 
        'location' AS type_bien,
        b.id,
        b.titre,
        b.surface,
        b.loyer AS prix,
        b.date_creation,
        CASE 
            WHEN b.est_valide = 1 THEN 'validé'
            ELSE 'en_attente' 
        END AS statut_validation,
        b.image,
        b.nombre_pieces,
        'Location' AS type_affichage,
        (SELECT COUNT(*) FROM reservation_visite WHERE annonce_id = b.id AND type_bien = 'location') AS nombre_visites
    FROM biens_en_location b
    WHERE b.utilisateur_id = ?

    UNION ALL

    SELECT 
        'vente' AS type_bien,
        b.id,
        b.titre,
        b.surface,
        b.prix,
        b.date_creation,
        CASE 
            WHEN b.est_valide = 1 THEN 'validé'
            ELSE 'en_attente' 
        END AS statut_validation,
        b.image,
        b.nombre_pieces,
        'Vente' AS type_affichage,
        (SELECT COUNT(*) FROM reservation_visite WHERE annonce_id = b.id AND type_bien = 'vente') AS nombre_visites
    FROM biens_à_vendre b
    WHERE b.utilisateur_id = ?
         
    ORDER BY date_creation DESC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $utilisateur_id, $utilisateur_id);
$stmt->execute();
$result = $stmt->get_result();
$biens = [];

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $biens[] = $row;
    }
}
$stmt->close();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mes annonces - Pollux Immobilier</title>
    <link rel="stylesheet" href="../style.css">
    <link rel="stylesheet" href="../pages-common.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        .biens-container {
            max-width: 1200px;
            margin: 30px auto;
            padding: 0 20px;
        }
        
        .page-title {
            text-align: center;
            margin-bottom: 30px;
            color: #157f68;
        }
        
        .biens-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 25px;
            margin-top: 20px;
        }
        
        .bien-card {
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            position: relative;
        }
        
        .bien-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.15);
        }
        
        .bien-image {
            width: 100%;
            height: 200px;
            object-fit: cover;
        }
        
        .bien-details {
            padding: 15px;
        }
        
        .bien-type {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: 500;
            margin-right: 5px;
        }
        
        .type-location {
            background-color: #e3f2fd;
            color: #1565c0;
        }
        
        .type-vente {
            background-color: #e8f5e9;
            color: #2e7d32;
        }
        
        .bien-titre {
            margin: 10px 0 5px;
            font-size: 1.2rem;
            color: #333;
        }
        
        .bien-prix {
            font-weight: bold;
            color: #157f68;
            font-size: 1.1rem;
            margin-bottom: 10px;
        }
        
        .bien-caracteristiques {
            display: flex;
            justify-content: space-between;
            color: #666;
            font-size: 0.9rem;
            margin: 10px 0;
        }
        
        .bien-caracteristiques span {
            display: flex;
            align-items: center;
            gap: 3px;
        }
        
        .bien-actions {
            display: flex;
            gap: 10px;
            margin-top: 15px;
            padding-top: 10px;
            border-top: 1px solid #eee;
        }
        
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 6px 12px;
            border-radius: 4px;
            text-decoration: none;
            font-size: 0.9rem;
            transition: all 0.2s;
        }
        
        .btn-primary {
            background-color: #157f68;
            color: white;
            border: 1px solid #157f68;
        }
        
        .btn-primary:hover {
            background-color: #0d5c4a;
            border-color: #0d5c4a;
        }
        
        .btn-edit {
            background-color: #e3f2fd;
            color: #1565c0;
            border: 1px solid #bbdefb;
        }
        
        .btn-edit:hover {
            background-color: #bbdefb;
        }
        
        .btn-delete {
            background-color: #ffebee;
            color: #c62828;
            border: 1px solid #ffcdd2;
        }
        
        .btn-delete:hover {
            background-color: #ffcdd2;
        }
        
        .no-biens {
            text-align: center;
            padding: 50px 20px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            grid-column: 1 / -1;
        }
        
        .no-biens i {
            font-size: 3rem;
            color: #ddd;
            margin-bottom: 15px;
        }
        
        .no-biens p {
            color: #666;
            margin-bottom: 20px;
        }
        
        .bien-statut {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: 500;
            margin-left: 10px;
        }
        
        .statut-actif {
            background-color: #e8f5e9;
            color: #1b5e20;
        }
        
        .statut-inactif {
            background-color: #fff3e0;
            color: #e65100;
        }
        
        .statut-validation {
            position: absolute;
            top: 10px;
            right: 10px;
            background-color: rgba(0, 0, 0, 0.7);
            color: white;
            padding: 3px 10px;
            border-radius: 15px;
            font-size: 0.8rem;
        }
    </style>
</head>
<body>
    <header>
        <div class="header-container">
            <img src="../logo.png" alt="Logo Pollux Immobilier" class="logo">
            <h1>Pollux Immobilier</h1>
        </div>
        <nav>
            <ul>
                <li><a href="../index.php">Accueil</a></li>
                <li><a href="../bien-locatifs.php">Louer</a></li>
                <li><a href="../bien-vente.php">Acheter</a></li>
                <li><a href="../vendre-estimer.php">Vendre/Estimer</a></li>
                <li><a href="../presentation.html">Notre histoire</a></li>
                <li class="login-link"><a href="deconnexion.php" style="color: #ffffff !important;">Déconnexion</a></li>
            </ul>
        </nav>
    </header>

    <div class="page-title">
        <div class="container">
            <h1>Mes annonces immobilières</h1>
            <p>Gérez toutes vos annonces de location et de vente en un seul endroit</p>
        </div>
    </div>

    <div class="biens-container">
        <div style="text-align: right; margin-bottom: 20px;">
            <a href="ajouter_bien.php" class="btn btn-primary">
                <i class="fas fa-plus"></i> Déposer une nouvelle annonce
            </a>
        </div>
        <div style="margin: 20px 0;">
        <a href="profil_utilisateur.php" class="btn" style="background-color: #3498db; color: white; padding: 8px 15px; text-decoration: none; border-radius: 4px; display: inline-flex; align-items: center;">
    <i class="fas fa-arrow-left" style="margin-right: 5px;"></i> Retour au profil
        </a>
        </div>
        
        <?php if (!empty($biens)): ?>
            <div class="biens-grid">
                <?php foreach ($biens as $bien): 
                    // Déterminer la classe du type de bien
                    $typeClass = $bien['type_bien'] === 'location' ? 'type-location' : 'type-vente';
                    $typeText = $bien['type_affichage'];
                    
                    // Déterminer le statut du bien
                    $statutClass = ($bien['statut'] ?? 'inactif') === 'actif' ? 'statut-actif' : 'statut-inactif';
                    $statutText = ($bien['statut'] ?? 'inactif') === 'actif' ? 'En ligne' : 'Hors ligne';
                    
                    // Formater le prix
                    $prix = isset($bien['prix']) ? number_format($bien['prix'], 0, ',', ' ') : 'N/A';
                    $prix .= ' €' . ($bien['type_bien'] === 'location' ? ' / mois' : '');
                    
                    // Déterminer le statut de validation
                    $statutValidation = $bien['statut_validation'] ?? 'en attente';
                ?>
                    <div class="bien-card">
                        <img src="<?= !empty($bien['image_principale']) ? htmlspecialchars($bien['image_principale']) : '../images/no-image.jpg' ?>" 
                             alt="<?= htmlspecialchars($bien['titre'] ?? '') ?>" 
                             class="bien-image">
                        <div class="statut-validation"><?= htmlspecialchars(ucfirst($statutValidation)) ?></div>
                        <div class="bien-details">
                            <div>
                                <span class="bien-type <?= $typeClass ?>"><?= htmlspecialchars($typeText) ?></span>
                                <span class="bien-statut <?= $statutClass ?>"><?= $statutText ?></span>
                            </div>
                            <h3 class="bien-titre"><?= htmlspecialchars($bien['titre'] ?? '') ?></h3>
                            <div class="bien-prix"><?= $prix ?></div>
                            
                            <div class="bien-caracteristiques">
                                <span title="Surface">
                                    <i class="fas fa-ruler-combined"></i> 
                                    <?= htmlspecialchars($bien['surface'] ?? '0') ?> m²
                                </span>
                                <?php if (isset($bien['nombre_pieces'])): ?>
                                <span title="Pièces">
                                    <i class="fas fa-door-open"></i> 
                                    <?= (int)$bien['nombre_pieces'] ?> pièce<?= $bien['nombre_pieces'] > 1 ? 's' : '' ?>
                                </span>
                                <?php endif; ?>
                                <span title="Visites">
                                    <i class="fas fa-calendar-check"></i> 
                                    <?= (int)($bien['nombre_visites'] ?? 0) ?>
                                </span>
                            </div>
                            
                            <div class="bien-actions">
                                <a href="modifier_bien.php?type=<?= urlencode($bien['type_bien']) ?>&id=<?= (int)$bien['id'] ?>" 
                                   class="btn btn-edit">
                                    <i class="fas fa-edit"></i> Modifier
                                </a>
                                <a href="#" 
                                   class="btn btn-delete" 
                                   onclick="return confirmDelete(<?= (int)$bien['id'] ?>, '<?= addslashes($bien['type_bien']) ?>')">
                                    <i class="fas fa-trash"></i> Supprimer
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="no-biens">
                <i class="far fa-folder-open"></i>
                <h2>Vous n'avez pas encore d'annonces</h2>
                <p>Commencez par déposer votre première annonce pour la mettre en ligne.</p>
                <a href="ajouter_bien.php" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Déposer une annonce
                </a>
            </div>
        <?php endif; ?>
    </div>

    <footer>
        <div class="container">
            <p>&copy; <?= date('Y') ?> Pollux Immobilier - Tous droits réservés</p>
        </div>
    </footer>

    <script>
        function confirmDelete(bienId, typeBien) {
            if (confirm('Êtes-vous sûr de vouloir supprimer cette annonce ? Cette action est irréversible.')) {
                // Rediriger vers le script de suppression avec l'ID et le type de bien
                window.location.href = `supprimer_bien.php?type=${typeBien}&id=${bienId}`;
            }
            return false;
        }
        
        // Script pour gérer l'activation/désactivation des annonces
        function toggleBienStatus(bienId, typeBien, currentStatus) {
            const newStatus = currentStatus === 'actif' ? 'inactif' : 'actif';
            
            fetch('changer_statut_bien.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `id=${bienId}&type=${typeBien}&statut=${newStatus}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Recharger la page pour afficher le nouveau statut
                    window.location.reload();
                } else {
                    alert('Une erreur est survenue lors de la mise à jour du statut.');
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                alert('Une erreur est survenue lors de la communication avec le serveur.');
            });
        }
    </script>
</body>
</html>