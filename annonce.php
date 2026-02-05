<?php
session_start();

echo 'Connecté ? ' . (isset($_SESSION['utilisateur_id']) ? 'oui' : 'non');

$flash_message = null;
if (isset($_SESSION['flash'])) {
    $flash_message = $_SESSION['flash'];
    unset($_SESSION['flash']);
}
require_once '../includes/db.php';

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$origine = isset($_GET['from']) ? $_GET['from'] : '../index.php';
echo $origine;

// Vérifie que l'ID de l'annonce est présent
if (!isset($_GET['ID'])) {
    die("❌ Aucune annonce sélectionnée.");
}

// Récupérer l'annonce selon le type et son ID
$annonce_id = isset($_GET['ID']) ? (int)$_GET['ID'] : 0;
$type = $conn->real_escape_string($_GET['type'] ?? '');

if ($type === 'location') {
    $table = '`biens_en_location`';
} elseif ($type === 'vente') {
    $table = '`biens_à_vendre`';
} else {
    die("❌ Type d'annonce invalide.");
}

$query = "SELECT * FROM $table WHERE id = $annonce_id";
$result = $conn->query($query);

if (!$result || $result->num_rows === 0) {
    die("❌ Annonce introuvable.");
}

$annonce = $result->fetch_assoc();



// Récupérer les images liées à l'annonce
$query = "SELECT fichier FROM images_annonce WHERE annonce_id = $annonce_id AND type_annonce='$type'";
$images_result = $conn->query($query);
$images = [];
$seen = []; // Pour suivre les fichiers déjà ajoutés

if ($images_result && $images_result->num_rows > 0) {
    while ($row = $images_result->fetch_assoc()) {
        $fichier = $row['fichier'];
        if (!in_array($fichier, $seen)) {
            $images[] = $fichier;
            $seen[] = $fichier;
        }
    }
}



// Récupérer les commentaires
$annonce_id = isset($_GET['ID']) ? (int)$_GET['ID'] : 0;
$type_annonce = $_GET['type'] ?? 'location';

$commentaires = [];
$query = "SELECT c.*, u.Nom, u.Prenom 
          FROM commentaires c
          LEFT JOIN utilisateurs u ON c.utilisateur_id = u.ID
          WHERE c.annonce_id = ? AND c.type_annonce = ? 
          ORDER BY c.date_creation DESC";
$stmt = $conn->prepare($query);
$stmt->bind_param("is", $annonce_id, $type_annonce);
$stmt->execute();
$result = $stmt->get_result();

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $commentaires[] = $row;
    }
}
$stmt->close();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($annonce['titre']) ?></title>
    <link rel="stylesheet" href="annonce.css">
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 0; line-height: 1.6; }
        .container { max-width: 1200px; margin: 0 auto; padding: 20px; }
        .property-header { margin-bottom: 30px; }
        .property-price { 
            font-size: 1.5em; 
            color: #0b1987;
            margin: 10px 0;
            font-weight: bold;
        }
        .property-meta {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 20px;
            margin: 20px 0;
            padding: 20px;
            background: #f9f9f9;
            border-radius: 8px;
        }
        .meta-item {
            margin-bottom: 10px;
        }
        .meta-label {
            font-weight: bold;
            color: #157f68;
            display: block;
            margin-bottom: 5px;
        }
        .swiper {
            width: 100%;
            height: 500px;
            margin: 20px 0;
        }
        .swiper-slide img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 8px;
        }
        .property-description {
            margin: 30px 0;
            padding: 20px;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .section-title {
            color: #157f68;
            border-bottom: 2px solid #157f68;
            padding-bottom: 10px;
            margin-top: 30px;
        }
        .back-link {
            display: inline-block;
            margin: 20px 0;
            color: #157f68;
            text-decoration: none;
            font-weight: bold;
        }
        .back-link:hover {
            text-decoration: underline;
        }
        /* Styles existants */
        .commentaire { border-bottom: 1px solid #ccc; padding: 15px 0; }
        textarea { width: 100%; height: 100px; padding: 10px; border: 1px solid #ddd; border-radius: 4px; }
        .formulaire { margin: 30px 0; padding: 20px; background: #f9f9f9; border-radius: 8px; }
        button { 
            background-color: #157f68; 
            color: white; 
            border: none; 
            padding: 10px 20px; 
            border-radius: 4px; 
            cursor: pointer; 
            margin-top: 10px;
        }
        button:hover { background-color: #0f5e4c; }
    </style>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@10/swiper-bundle.min.css" />
</head>
<body>

    <div class="container">
    <a href="<?= htmlspecialchars($origine) ?>" class="btn" style="background-color: #3498db; color: white; padding: 8px 15px; text-decoration: none; border-radius: 4px; display: inline-flex; align-items: center;">
    <i class="fas fa-arrow-left" style="margin-right: 5px;"></i> Retour aux annonces
    </a>
        
        <?php if ($flash_message): ?>
        <div style="background-color: #dff0d8; color: #3c763d; padding: 15px; border-radius: 5px; margin: 20px 0;">
            <?= htmlspecialchars($flash_message) ?>
        </div>
        <?php endif; ?>

        <!-- Carrousel d'images -->
        <?php if ($images): ?>
            <?php $type_annonce = $type === 'location' ? 'Location' : 'Vente'; ?>
            <div class="swiper mySwiper">
                <div class="swiper-wrapper">
                    <?php foreach ($images as $img): ?>
                        <div class="swiper-slide">
                            <img src="../images/<?= htmlspecialchars($type_annonce) ?>/<?= htmlspecialchars($img) ?>" alt="Photo du bien">
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="swiper-button-next"></div>
                <div class="swiper-button-prev"></div>
                <div class="swiper-pagination"></div>
            </div>
        <?php endif; ?>

        <!-- En-tête et informations principales -->
        <div class="property-header">
            <h1><?= htmlspecialchars($annonce['titre']) ?></h1>
            <div class="property-price">
                <?php if ($type === 'location'): ?>
                    <?= number_format($annonce['loyer'], 0, ',', ' ') ?> €/mois
                    <?php if (!empty($annonce['charges'])): ?>
                        + <?= number_format($annonce['charges'], 0, ',', ' ') ?> € de charges
                    <?php endif; ?>
                <?php else: ?>
                    <?= number_format($annonce['prix'], 0, ',', ' ') ?> €
                <?php endif; ?>
            </div>
            <?php if (!empty($annonce['adresse'])): ?>
                <p><i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($annonce['adresse']) ?></p>
            <?php endif; ?>
        </div>

        <!-- Métadonnées du bien -->
        <div class="property-meta">
            <?php if ($type === 'location'): ?>
                <div class="meta-item">
                    <span class="meta-label">Surface</span>
                    <?= $annonce['surface'] ?> m²
                </div>
                <div class="meta-item">
                    <span class="meta-label">Disponibilité</span>
                    <?= !empty($annonce['date_disponibilite']) ? date('d/m/Y', strtotime($annonce['date_disponibilite'])) : 'Dès maintenant' ?>
                </div>
                <?php if (!empty($annonce['charges'])): ?>
                    <div class="meta-item">
                        <span class="meta-label">Charges comprises</span>
                        Non
                    </div>
                <?php endif; ?>
                <div class="meta-item">
                    <span class="meta-label">Type de bien</span>
                    <?= htmlspecialchars($annonce['type_bien'] ?? 'Non spécifié') ?>
                </div>
            <?php else: ?>
                <div class="meta-item">
                    <span class="meta-label">Surface</span>
                    <?= $annonce['surface'] ?? 'Non spécifiée' ?> m²
                </div>
                <div class="meta-item">
                    <span class="meta-label">Pièces</span>
                    <?= $annonce['nb_pieces'] ?? 'Non spécifié' ?>
                </div>
                <div class="meta-item">
                    <span class="meta-label">Type de bien</span>
                    <?= htmlspecialchars($annonce['type_bien'] ?? 'Non spécifié') ?>
                </div>
            <?php endif; ?>
            <div class="meta-item">
                <span class="meta-label">Référence</span>
                #<?= $annonce_id ?>
            </div>
        </div>

        <!-- Description -->
        <div class="property-description">
            <h2 class="section-title">Description</h2>
            <?= nl2br(htmlspecialchars($annonce['description'])) ?>
        </div>

        <?php if ($type === 'location'): ?>
            <!-- Détails spécifiques à la location -->
            <div class="property-details">
                <h2 class="section-title">Détails de la location</h2>
                <div class="property-meta">
                    <div class="meta-item">
                        <span class="meta-label">Dépôt de garantie</span>
                        <?= !empty($annonce['depot_garantie']) ? number_format($annonce['depot_garantie'], 0, ',', ' ') . ' €' : 'Non spécifié' ?>
                    </div>
                    <div class="meta-item">
                        <span class="meta-label">Charges comprises</span>
                        <?= !empty($annonce['charges_comprises']) ? 'Oui' : 'Non' ?>
                    </div>
                    <div class="meta-item">
                        <span class="meta-label">Meublé</span>
                        <?= !empty($annonce['meuble']) ? 'Oui' : 'Non' ?>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <!-- Détails spécifiques à la vente -->
            <div class="property-details">
                <h2 class="section-title">Détails du bien</h2>
                <div class="property-meta">
                    <div class="meta-item">
                        <span class="meta-label">Étage</span>
                        <?= $annonce['etage'] ?? 'Non spécifié' ?>
                    </div>
                    <div class="meta-item">
                        <span class="meta-label">Année de construction</span>
                        <?= $annonce['annee_construction'] ?? 'Non spécifiée' ?>
                    </div>
                    <div class="meta-item">
                        <span class="meta-label">État</span>
                        <?= $annonce['etat'] ?? 'Non spécifié' ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>

<?php if (isset($_SESSION['utilisateur_id'])): ?>

<form id="reservationForm" method="POST" action="ajouter_reservation.php" class="reservation-form">

    <!-- Champs cachés communs -->
    <input type="hidden" name="action" value="reservation">
    <input type="hidden" name="annonce_id" value="<?= $annonce_id ?>">
    <input type="hidden" name="type_annonce" value="<?= htmlspecialchars($type) ?>">
    <input type="hidden" name="origine" value="<?= htmlspecialchars($origine) ?>">

    <!-- Section Réservation -->
    <div class="reservation-section">
        <div class="row">
            <div class="col-md-6 mb-3">
                <label for="date_visite" class="form-label">Date de la visite *</label>
                <input type="date" class="form-control" id="date_visite" name="date_visite" min="<?= date('Y-m-d') ?>">
            </div>

            <div class="col-md-6 mb-3">
                <label for="heure_debut" class="form-label">Heure de début *</label>
                <select class="form-select" id="heure_debut" name="heure_debut">
                    <option value="">Sélectionnez une heure</option>
                    <?php
                    for ($h = 8; $h < 20; $h++) {
                        for ($m = 0; $m < 60; $m += 30) {
                            $heure = sprintf('%02d:%02d', $h, $m);
                            echo "<option value='$heure'>$heure</option>";
                        }
                    }
                    ?>
                </select>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6 mb-3">
                <label for="duree" class="form-label">Durée de la visite *</label>
                <select class="form-select" id="duree" name="duree">
                    <option value="30">30 minutes</option>
                    <option value="60" selected>1 heure</option>
                    <option value="90">1 heure 30</option>
                    <option value="120">2 heures</option>
                </select>
            </div>

            <div class="col-md-6 mb-3">
                <label for="heure_fin" class="form-label">Heure de fin</label>
                <input type="text" class="form-control fw-bold text-primary bg-light" id="heure_fin" readonly>
            </div>
        </div>
    </div>

    <!-- Bouton -->
    <div class="d-flex justify-content-between mt-4">
        <button type="submit" class="btn btn-primary">
            <i class="fas fa-calendar-plus me-2"></i>Réserver
        </button>
    </div>
</form>

<?php else: ?>

<div class="alert alert-info">
    <p>Vous devez être connecté pour effectuer une réservation.</p>
    <a href="login.php?redirect=<?= urlencode("annonce.php?id=$annonce_id&type=$type") ?>" class="btn btn-primary">
        Se connecter
    </a>
</div>
<?php endif; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {

    // RÉFÉRENCES AUX CHAMPS
    const dateVisite = document.getElementById('date_visite');
    const heureDebut = document.getElementById('heure_debut');
    const duree = document.getElementById('duree');
    const heureFin = document.getElementById('heure_fin');

    // FONCTION DE CALCUL
    function updateHeureFin() {
        if (!heureDebut.value || !duree.value) return;

        const [h, m] = heureDebut.value.split(':').map(Number);
        const d = parseInt(duree.value);

        let total = h * 60 + m + d;
        let hFin = Math.floor(total / 60) % 24;
        let mFin = total % 60;

        heureFin.value =
            String(hFin).padStart(2, '0') + ':' +
            String(mFin).padStart(2, '0');

        console.log("Heure de fin :", heureFin.value);
    }

    // DÉCLENCHEURS
    dateVisite.addEventListener('change', updateHeureFin);
    heureDebut.addEventListener('change', updateHeureFin);
    duree.addEventListener('change', updateHeureFin);

    // CALCUL INITIAL SI CHAMPS DÉJÀ REMPLIS
    updateHeureFin();

    // DÉSACTIVER LES DATES PASSÉES
    const today = new Date().toISOString().split('T')[0];
    dateVisite.min = today;

});
</script>

        <!-- Section des commentaires -->
        <div class="comments-section">
            <h2 class="section-title">💬 Commentaires</h2>
            <?php if (!empty($commentaires)): ?>
                <?php foreach ($commentaires as $c): ?>
                    <div class="commentaire">
                        <strong><?= !empty($c['Prenom']) ? htmlspecialchars($c['Prenom']) . ' ' . htmlspecialchars($c['Nom']) : 'Utilisateur inconnu' ?></strong>
                        <small style="color: #666; margin-left: 10px;"><?= date('d/m/Y H:i', strtotime($c['date_creation'])) ?></small>
                        <p><?= nl2br(htmlspecialchars($c['contenu'])) ?></p>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p>Aucun commentaire pour cette annonce.</p>
            <?php endif; ?>

            <div class="formulaire">
                <h3>Ajouter un commentaire</h3>
                <?php if (isset($_SESSION['utilisateur_id'])): ?>
                    <form method="POST" action="ajouter_commentaire.php">
                        <textarea name="contenu" placeholder="Votre commentaire" required></textarea><br>
                        <input type="hidden" name="annonce_id" value="<?= $annonce_id ?>">
                        <input type="hidden" name="type_annonce" value="<?= $type ?>">
                        <button type="submit">Envoyer le commentaire</button>
                    </form>
                <?php else: ?>
                    <p>🔒 Vous devez être connecté pour commenter. <a href="connexion.php?redirect=annonce.php?id=<?= $annonce_id ?>&type=<?= $type ?>">Se connecter</a></p>
                <?php endif; ?>
            </div>

            <div class="container">
    <?php if (isset($_SESSION['flash'])): ?>
        <div class="alert <?= strpos($_SESSION['flash'], '✅') !== false ? 'alert-success' : 'alert-danger' ?>" 
             style="padding: 15px; margin: 20px 0; border-radius: 4px; text-align: center;">
            <?= htmlspecialchars($_SESSION['flash']) ?>
        </div>
        <?php unset($_SESSION['flash']); ?>
    <?php endif; ?>
    

    <?php if (isset($bien['ID']) && isset($type_bien)): ?>
    <form method="POST" action="ajouter_favori.php" style="display:inline;">
        <input type="hidden" name="annonce_id" value="<?= htmlspecialchars($bien['ID']); ?>">
        <input type="hidden" name="type_annonce" value="<?= htmlspecialchars($type_bien); ?>"> <!-- 'location' ou 'vente' -->
        <button type="submit" class="btn btn-light" title="Ajouter aux favoris">❤️</button>
    </form>

    <?php endif; ?>
    <?php if (isset($_SESSION['message'])): ?>
    <div class="alert alert-success text-center">
        <?= htmlspecialchars($_SESSION['message']); ?>
    </div>
    <?php unset($_SESSION['message']); ?>
    <?php endif; ?>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/swiper@10/swiper-bundle.min.js"></script>
    <script>
        const swiper = new Swiper(".mySwiper", {
            navigation: {
                nextEl: ".swiper-button-next",
                prevEl: ".swiper-button-prev",
            },
            pagination: {
                el: ".swiper-pagination",
                clickable: true,
            },
            autoplay: {
                delay: 5000,
                disableOnInteraction: false,
            },
            loop: true,
            effect: 'fade',
            fadeEffect: {
                crossFade: true
            },
        });
    </script>
</body>
</html>