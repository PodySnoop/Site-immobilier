<?php
// Démarrer la session
session_start();

// Inclure la configuration et la connexion à la base de données
require_once 'includes/db.php';

// Vérifier si un ID de bien a été fourni
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: index.php');
    exit();
}

$bien_id = intval($_GET['id']);

// Récupérer les informations du bien
$query = "SELECT b.*, u.nom as nom_utilisateur, u.email as email_utilisateur, 
                 v.nom as nom_validateur, v.email as email_validateur
          FROM biens b 
          JOIN utilisateurs u ON b.utilisateur_id = u.ID 
          LEFT JOIN utilisateurs v ON b.validateur_id = v.ID
          WHERE b.id = ? AND b.est_valide = 1";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $bien_id);
$stmt->execute();
$result = $stmt->get_result();

// Vérifier si le bien existe et est validé
if ($result->num_rows === 0) {
    header('HTTP/1.0 404 Not Found');
    include '404.php';
    exit();
}

$bien = $result->fetch_assoc();

// Récupérer les images du bien
$image_dir = 'images/' . ($bien['type_offre'] === 'location' ? 'location' : 'vente') . '/';
$images = [];

// Vérifier si le répertoire existe
if (is_dir($image_dir)) {
    $files = scandir($image_dir);
    foreach ($files as $file) {
        if (preg_match('/^' . $bien_id . '_(\d+)\.(jpg|jpeg|png|gif)$/i', $file, $matches)) {
            $images[] = $image_dir . $file;
        }
    }
}

// Si aucune image n'est trouvée, utiliser l'image par défaut
if (empty($images)) {
    $images[] = 'images/placeholder.jpg';
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($bien['titre']); ?> - Pollux Immobilier</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="pages-common.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/lightgallery@2.4.0/css/lightgallery-bundle.min.css">
    <style>
        .property-detail {
            max-width: 1200px;
            margin: 30px auto;
            padding: 0 15px;
        }
        
        .property-header {
            margin-bottom: 30px;
        }
        
        .property-title {
            font-size: 2.2em;
            margin-bottom: 10px;
            color: #2c3e50;
        }
        
        .property-location {
            font-size: 1.2em;
            color: #7f8c8d;
            margin-bottom: 15px;
        }
        
        .property-price {
            font-size: 1.8em;
            font-weight: bold;
            color: #e74c3c;
            margin: 15px 0;
        }
        
        .gallery-container {
            margin-bottom: 30px;
        }
        
        .main-image {
            width: 100%;
            height: 500px;
            object-fit: cover;
            border-radius: 8px;
            margin-bottom: 10px;
            cursor: pointer;
        }
        
        .thumbnail-container {
            display: flex;
            gap: 10px;
            overflow-x: auto;
            padding-bottom: 15px;
        }
        
        .thumbnail {
            width: 100px;
            height: 75px;
            object-fit: cover;
            border-radius: 4px;
            cursor: pointer;
            opacity: 0.7;
            transition: opacity 0.3s;
        }
        
        .thumbnail:hover, .thumbnail.active {
            opacity: 1;
        }
        
        .property-details-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 30px;
            margin-top: 30px;
        }
        
        @media (max-width: 768px) {
            .property-details-grid {
                grid-template-columns: 1fr;
            }
        }
        
        .property-features {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 15px;
            margin: 20px 0;
            padding: 20px;
            background: #f9f9f9;
            border-radius: 8px;
        }
        
        .feature {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .feature i {
            color: #3498db;
            font-size: 1.2em;
            width: 24px;
            text-align: center;
        }
        
        .property-description {
            margin: 30px 0;
            line-height: 1.6;
        }
        
        .sidebar {
            background: #f9f9f9;
            padding: 20px;
            border-radius: 8px;
            height: fit-content;
        }
        
        .contact-form input,
        .contact-form textarea {
            width: 100%;
            padding: 10px;
            margin-bottom: 15px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        
        .contact-form button {
            background: #e74c3c;
            color: white;
            border: none;
            padding: 12px 20px;
            border-radius: 4px;
            cursor: pointer;
            width: 100%;
            font-size: 1.1em;
            transition: background 0.3s;
        }
        
        .contact-form button:hover {
            background: #c0392b;
        }
        
        .property-meta {
            margin: 20px 0;
            color: #7f8c8d;
            font-size: 0.9em;
        }
        
        .property-meta span {
            margin-right: 15px;
        }
        
        .section-title {
            font-size: 1.5em;
            margin: 30px 0 15px;
            color: #2c3e50;
            border-bottom: 2px solid #eee;
            padding-bottom: 10px;
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <main class="property-detail">
        <div class="property-header">
            <h1 class="property-title"><?php echo htmlspecialchars($bien['titre']); ?></h1>
            <div class="property-location">
                <i class="fas fa-map-marker-alt"></i> 
                <?php echo htmlspecialchars($bien['adresse'] . ', ' . $bien['code_postal'] . ' ' . $bien['ville'] . ', ' . $bien['pays']); ?>
            </div>
            <div class="property-price">
                <?php 
                if ($bien['type_offre'] === 'location') {
                    echo number_format($bien['prix'], 0, ',', ' ') . ' €/mois';
                } else {
                    echo number_format($bien['prix'], 0, ',', ' ') . ' €';
                }
                ?>
            </div>
        </div>
        
        <!-- Galerie d'images -->
        <div class="gallery-container">
            <img id="mainImage" src="<?php echo htmlspecialchars($images[0]); ?>" alt="<?php echo htmlspecialchars($bien['titre']); ?>" class="main-image">
            
            <?php if (count($images) > 1): ?>
                <div class="thumbnail-container">
                    <?php foreach ($images as $index => $image): ?>
                        <img src="<?php echo htmlspecialchars($image); ?>" 
                             alt="<?php echo htmlspecialchars($bien['titre'] . ' - ' . ($index + 1)); ?>" 
                             class="thumbnail <?php echo $index === 0 ? 'active' : ''; ?>"
                             onclick="changeMainImage('<?php echo htmlspecialchars($image); ?>', this)">
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="property-details-grid">
            <div class="property-main">
                <h2 class="section-title">Description</h2>
                <div class="property-description">
                    <?php echo nl2br(htmlspecialchars($bien['description'])); ?>
                </div>
                
                <h2 class="section-title">Caractéristiques</h2>
                <div class="property-features">
                    <div class="feature">
                        <i class="fas fa-ruler-combined"></i>
                        <span>Surface: <?php echo $bien['surface']; ?> m²</span>
                    </div>
                    <div class="feature">
                        <i class="fas fa-door-open"></i>
                        <span><?php echo $bien['pieces']; ?> pièces</span>
                    </div>
                    <div class="feature">
                        <i class="fas fa-bed"></i>
                        <span><?php echo $bien['chambres']; ?> chambres</span>
                    </div>
                    <div class="feature">
                        <i class="fas fa-bath"></i>
                        <span><?php echo $bien['salles_de_bain']; ?> salles de bain</span>
                    </div>
                    <div class="feature">
                        <i class="fas fa-home"></i>
                        <span>Type: <?php echo ucfirst($bien['type_bien']); ?></span>
                    </div>
                    <div class="feature">
                        <i class="fas fa-tag"></i>
                        <span>Référence: <?php echo $bien['reference']; ?></span>
                    </div>
                </div>
                
                <?php if (!empty($bien['equipements'])): ?>
                    <h2 class="section-title">Équipements</h2>
                    <div class="property-features">
                        <?php 
                        $equipements = explode(',', $bien['equipements']);
                        foreach ($equipements as $equipement): 
                            if (!empty(trim($equipement))): ?>
                                <div class="feature">
                                    <i class="fas fa-check"></i>
                                    <span><?php echo htmlspecialchars(trim($equipement)); ?></span>
                                </div>
                            <?php 
                            endif;
                        endforeach; 
                        ?>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="sidebar">
                <h3>Contacter l'agence</h3>
                <p>Pour plus d'informations sur ce bien, n'hésitez pas à nous contacter :</p>
                
                <form class="contact-form" action="envoyer_message.php" method="POST">
                    <input type="hidden" name="bien_id" value="<?php echo $bien['id']; ?>">
                    <input type="hidden" name="bien_titre" value="<?php echo htmlspecialchars($bien['titre']); ?>">
                    
                    <div class="form-group">
                        <label for="nom">Votre nom *</label>
                        <input type="text" id="nom" name="nom" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Votre email *</label>
                        <input type="email" id="email" name="email" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="telephone">Téléphone</label>
                        <input type="tel" id="telephone" name="telephone">
                    </div>
                    
                    <div class="form-group">
                        <label for="message">Votre message *</label>
                        <textarea id="message" name="message" rows="5" required>Je suis intéressé(e) par le bien "<?php echo htmlspecialchars($bien['titre']); ?>" (Réf: <?php echo $bien['reference']; ?>)</textarea>
                    </div>
                    
                    <button type="submit">Envoyer le message</button>
                </form>
                
                <div class="property-meta">
                    <p><strong>Référence:</strong> <?php echo $bien['reference']; ?></p>
                    <p><strong>Dernière mise à jour:</strong> <?php echo date('d/m/Y', strtotime($bien['date_mise_a_jour'])); ?></p>
                    <?php if ($bien['date_validation']): ?>
                        <p><strong>Validé le:</strong> <?php echo date('d/m/Y', strtotime($bien['date_validation'])); ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>
    
    <?php include 'includes/footer.php'; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/lightgallery@2.4.0/lightgallery.umd.min.js"></script>
    <script>
        // Changer l'image principale au clic sur une miniature
        function changeMainImage(src, element) {
            document.getElementById('mainImage').src = src;
            
            // Mettre à jour la classe active sur les miniatures
            const thumbnails = document.querySelectorAll('.thumbnail');
            thumbnails.forEach(thumb => {
                thumb.classList.remove('active');
            });
            
            element.classList.add('active');
        }
        
        // Initialiser la galerie lightbox
        document.addEventListener('DOMContentLoaded', function() {
            const mainImage = document.getElementById('mainImage');
            if (mainImage) {
                mainImage.addEventListener('click', function() {
                    const gallery = lightGallery(document.body, {
                        dynamic: true,
                        dynamicEl: [
                            <?php foreach ($images as $index => $image): ?>
                                {
                                    src: '<?php echo $image; ?>',
                                    thumb: '<?php echo $image; ?>',
                                    subHtml: '<?php echo htmlspecialchars($bien['titre'] . ' - ' . ($index + 1)); ?>'
                                }<?php echo $index < count($images) - 1 ? ',' : ''; ?>
                            <?php endforeach; ?>
                        ],
                        download: false,
                        share: false,
                        autoplay: false
                    });
                    
                    gallery.openGallery(0);
                });
            }
        });
    </script>
</body>
</html>
