<?php
session_set_cookie_params([
  'lifetime' => 60 * 60 * 24 * 30, // 30 jours
  'path' => '/',
  'domain' => $_SERVER['HTTP_HOST'],
  'secure' => isset($_SERVER['HTTPS']),
  'httponly' => true,
  'samesite' => 'Lax'
]);

if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

// Vérifier si l'utilisateur a un token de rappel
if (!isset($_SESSION['utilisateur_id']) && isset($_COOKIE['remember_token'])) {
  require_once 'includes/db.php';
  $token = $_COOKIE['remember_token'];
  $stmt = $conn->prepare("SELECT * FROM utilisateurs WHERE remember_token = ?");
  $stmt->bind_param("s", $token);
  $stmt->execute();
  $result = $stmt->get_result();
  
  if ($user = $result->fetch_assoc()) {
      $_SESSION['utilisateur_id'] = $user['id'];
      $_SESSION['nom'] = $user['nom'];
      $_SESSION['email'] = $user['email'];
      $_SESSION['role'] = $user['role'];
  }
}
// Fin du changement

require_once 'includes/db.php';

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
// Récupération des biens à vendre
$query = "
    SELECT b.*,
           (SELECT COUNT(*) 
            FROM favoris f 
            WHERE f.annonce_id = b.ID 
            AND f.utilisateur_id = $utilisateur_id
            AND f.type_annonce = 'vente') AS est_favori
    FROM biens_à_vendre b
    WHERE b.est_valide = 1
    ORDER BY b.date_creation DESC
";
$result = $conn->query($query);
$biens_vente = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $row['est_favori'] = $row['est_favori'] > 0;
        $biens_vente[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Biens à vendre - Pollux Immobilier</title>
  <link rel="stylesheet" href="style.css">
  <link rel="stylesheet" href="pages-common.css">
  <style>
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
      color: #0b1987;
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
        <li><a href="bien-locatifs.php">Louer</a></li>
        <li class="active"><a href="bien-vente.php">Acheter</a></li>
        <li><a href="vendre-estimer.php">Vendre/Estimer</a></li>
        <li><a href="presentation.html">Notre histoire</a></li>
        <li class="login-link"><a href="utilisateurs/login.php">Se connecter</a></li>
      </ul>
    </nav>
  </header>

  <div class="page-title">
    <div class="container">
      <h1>Nos biens à vendre</h1>
      <p>Découvrez notre sélection de biens immobiliers à la vente</p>
    </div>
  </div>

  <main class="property-listing">
    <div class="container">
      <?php if (!empty($biens_vente)): ?>
        <div class="property-grid">
          <?php foreach ($biens_vente as $bien): ?>
            <div class="property-card">
              <div class="property-image-container">
                <img src="<?= htmlspecialchars($bien['image']) ?>" alt="<?= htmlspecialchars($bien['titre']) ?>" />
                <form method="POST" action="ajouter_favori.php" class="form-favori">
    <input type="hidden" name="annonce_id" value="<?= $bien['ID'] ?>">
    <input type="hidden" name="type_annonce" value="vente">
    <?php $estFavori = isset($favoris_utilisateur[$bien['ID'] . '_vente']); ?>
    <button type="submit" class="coeur-favori <?= $estFavori ? 'actif' : '' ?>">
        <i class="fas fa-heart"></i>
    </button>
</form>
</div>
              <div class="property-details">
                <h3><?= htmlspecialchars($bien['titre']) ?></h3>
                <div class="property-info">
                  <span>Prix:</span> <?= number_format($bien['prix'], 0, ',', ' '); ?> €
                </div>
                <?php if (!empty($bien['surface'])): ?>
                <div class="property-info">
                  <span>Surface:</span> <?= $bien['surface']; ?> m²
                </div>
                <?php endif; ?>
                <?php if (!empty($bien['ville'])): ?>
                <div class="property-info">
                  <span>Ville:</span> <?= htmlspecialchars($bien['ville']); ?>
                </div>
                <?php endif; ?>
                <p class="property-description"><?= htmlspecialchars(substr($bien['description'] ?? '', 0, 100)) . '...'; ?></p>
                <a href="utilisateurs/annonce.php?ID=<?=$bien['ID'] ?>&type=vente&from=<?= urlencode($_SERVER['PHP_SELF'])?>">Voir l'annonce</a>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php else: ?>
        <div class="no-properties">
          <p>Aucun bien à vendre n'est actuellement disponible.</p>
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
          &copy; 2025 Pollux Immobilier · Tous droits réservés.
        </div>
      </div>
    </div>
  </footer>
  <script>
document.addEventListener('DOMContentLoaded', () => {
    const boutonsFavoris = document.querySelectorAll('.coeur-favori');

    boutonsFavoris.forEach bouton => {
        bouton.addEventListener('click', function () {
            const annonceId = this.dataset.annonceId;
            const type = this.dataset.type;
            const estActif = this.classList.contains('actif');
            const action = estActif ? 'supprimer' : 'ajouter';
            const coeurIcon = this.querySelector('i');

            this.classList.add('animating');

        )};
    });
</script>
</body>
</html>