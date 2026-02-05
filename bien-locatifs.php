<?php
require_once 'includes/db.php';

session_start(); // IMPORTANT si pas déjà dans db.php

$utilisateur_id = $_SESSION['utilisateur_id'] ?? 0;

$favoris_utilisateur = [];
if (isset($_SESSION['utilisateur_id'])) {
    $stmt = $conn->prepare("SELECT annonce_id, type_annonce FROM favoris WHERE utilisateur_id = ?");
    $stmt->bind_param("i", $_SESSION['utilisateur_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $favoris_utilisateur[$row['annonce_id'] . '_' . $row['type_annonce']] = true;
    }
    $stmt->close();
}

// Requête enrichie
$query = "
SELECT b.*,
       (SELECT COUNT(*) 
        FROM favoris f 
        WHERE f.annonce_id = b.ID 
        AND f.utilisateur_id = $utilisateur_id
        AND f.type_annonce = 'location') AS est_favori
FROM biens_en_location b
WHERE b.est_valide = 1
ORDER BY b.date_creation DESC
";

$result = $conn->query($query);

$biens_location = [];

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {

        // Convertir est_favori en booléen
        $row['est_favori'] = $row['est_favori'] > 0;

        $biens_location[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Louer un bien - Pollux Immobilier</title>
  <link rel="stylesheet" href="style.css">
  <link rel="stylesheet" href="pages-common.css">
  <link rel="stylesheet" href="bien locatifs.css">
  <style>
    .page-title {
      background: linear-gradient(rgba(57, 132, 132, 0.84), rgba(0, 0, 0, 0.6)), url('images/background-location.jpg') center/cover;
      color: white;
      padding: 100px 0;
      text-align: center;
    }
    
    .page-title h1 {
      font-size: 3em;
      margin-bottom: 20px;
      color: white;
    }
    
    .page-title p {
      font-size: 1.2em;
      max-width: 800px;
      margin: 0 auto;
    }
    
    .property-listing {
      padding: 50px 0;
    }
    
    .property-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
      gap: 30px;
      padding: 20px;
    }
    
    .property-card {
      border: 1px solid #ddd;
      border-radius: 8px;
      overflow: hidden;
      transition: transform 0.3s ease;
      background: white;
      box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    }
    
    .property-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 5px 15px rgba(0,0,0,0.2);
    }
    
    .property-image-container {
      position: relative;
      width: 100%;
      padding-top: 75%; /* Ratio 4:3 pour les images */
      overflow: hidden;
    }
    
    .property-image-container img {
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      object-fit: cover;
      transition: transform 0.5s ease;
    }
    
    .property-card:hover .property-image-container img {
      transform: scale(1.05);
    }
    
    .property-details {
      padding: 20px;
    }
    
    .property-details h3 {
      color: #157f68;
      margin: 0 0 10px 0;
      font-size: 1.2em;
      min-height: 2.8em;
    }
    
    .property-info {
      margin: 8px 0;
      color: #444;
      display: flex;
      justify-content: space-between;
    }
    
    .property-info span:first-child {
      color: #7f8c8d;
      font-weight: 500;
    }
    
    .property-price {
      font-size: 1.5em;
      font-weight: bold;
      color: #0b1987;
      margin: 12px 0 8px 0;
      text-align: right;
    }
    
    .property-description {
      color: #666;
      margin: 10px 0;
      font-size: 0.9em;
      line-height: 1.4;
      min-height: 4.2em;
      overflow: hidden;
      text-overflow: ellipsis;
      display: -webkit-box;
      -webkit-line-clamp: 3;
      -webkit-box-orient: vertical;
    }
    
    .property-link {
      display: block;
      text-align: center;
      background-color: #157f68; /* Vert foncé */
      color: white;
      padding: 10px;
      margin-top: 15px;
      border-radius: 4px;
      text-decoration: none;
      font-weight: 500;
      transition: background-color 0.3s;
    }
    
    .property-link:hover {
      background-color: #0b1987; /* Bleu foncé au survol */
    }
    
    .no-properties {
      text-align: center;
      padding: 40px 20px;
      background: white;
      border-radius: 8px;
      grid-column: 1 / -1;
      box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    }
    
    @media (max-width: 768px) {
      .property-grid {
        grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
        gap: 20px;
        padding: 0 15px;
      }
    }
    
    @media (max-width: 480px) {
      .property-grid {
        grid-template-columns: 1fr;
        padding: 0 10px;
      }
    }
  </style>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
  <header>
    <div class="header-container">
      <img src="logo.png" alt="Logo Pollux Immobilier" class="logo">
      <h1>Pollux Immobilier</h1>
    </div>
    <nav>
      <ul>
        <li><a href="index.php">Accueil</a></li>
        <li class="active"><a href="bien-locatifs.php">Louer</a></li>
        <li><a href="bien-vente.php">Acheter</a></li>
        <li><a href="vendre-estimer.php">Vendre/Estimer</a></li>
        <li><a href="presentation.html">Notre histoire</a></li>
        <li class="login-link"><a href="utilisateurs/login.php">Se connecter</a></li>
      </ul>
    </nav>
    
  </header>

  <div class="page-title">
    <div class="container">
      <h1>Nos biens en location</h1>
    </div>
  </div>

  <main class="property-listing">
    <div class="container">
      <?php if (!empty($biens_location)): ?>
      <?php $type = 'Location'; ?>
        <div class="property-grid">
          <?php foreach ($biens_location as $bien): ?>
            <div class="property-card">
              <div class="property-image-container">
                <img src="<?= htmlspecialchars($bien['image']) ?>" alt="<?= htmlspecialchars($bien['titre']) ?>" />
                <form method="POST" action="ajouter_favori.php" class="form-favori">
    <input type="hidden" name="annonce_id" value="<?= $bien['ID'] ?>">
    <input type="hidden" name="type_annonce" value="location">
    <?php $estFavori = isset($favoris_utilisateur[$bien['ID'] . '_location']); ?>
    <button type="submit" class="coeur-favori <?= $estFavori ? 'actif' : '' ?>">
        <i class="fas fa-heart"></i>
    </button>
</form>
</div>
              <div class="property-details">
                <h3><?= htmlspecialchars($bien['titre']); ?></h3>
                <div class="property-info">
                  <span>Loyer:</span> <?= number_format($bien['loyer'], 0, ',', ' '); ?> €
                </div>
                <div class="property-info">
                  <span>Charges:</span> <?= isset($bien['charges']) ? number_format($bien['charges'], 0, ',', ' ') . ' €' : 'Non spécifié'; ?>
                </div>
                <div class="property-info">
                  <span>Surface:</span> <?= $bien['surface']; ?> m²
                </div>
                <div class="property-info">
                  <span>Disponible le:</span> <?= isset($bien['date_disponibilite']) ? date('d/m/Y', strtotime($bien['date_disponibilite'])) : 'Dès maintenant'; ?>
                </div>
                <p class="property-description">
    <?= htmlspecialchars(substr($bien['description'], 0, 100)) . '...'; ?>
</p>

<div class="property-link-container">
    <a class="property-link"
       href="utilisateurs/annonce.php?ID=<?= $bien['ID'] ?>&type=location&from=<?= urlencode($_SERVER['PHP_SELF']) ?>">
        Voir l'annonce
    </a>
</div>
      

              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php else: ?>
        <div class="no-properties">
          <p>Aucun bien en location n'est actuellement disponible.</p>
          <p>N'hésitez pas à nous contacter pour plus d'informations.</p>
        </div>
      <?php endif; ?>
    </div>
  </main>

  <footer>
    <div class="container">
      <div class="footer-content">
        <div class="footer-links">
          <a href="mentions-legales.html">Mentions Légales</a>
          <a href="cgv.html">Conditions Générales de Vente</a>
          <a href="index.php">Accueil</a>
        </div>
        <div class="footer-copyright">
          &copy; <?= date('Y') ?> Pollux Immobilier · Tous droits réservés.
        </div>
      </div>
    </div>
  </footer>
  <script>
document.addEventListener('DOMContentLoaded', () => {

    const boutonsFavoris = document.querySelectorAll('.coeur-favori');

    boutonsFavoris.forEach(bouton => {
        bouton.addEventListener('click', function () {

            const annonceId = this.dataset.annonceId;
            const type = this.dataset.type;
            const estActif = this.classList.contains('actif');
            const action = estActif ? 'supprimer' : 'ajouter';
            const coeurIcon = this.querySelector('i');

            this.classList.add('animating');

    }})
});
</script>
</body>
</html>