<?php
session_start();

error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once 'includes/db.php';


// Vérifier si l'utilisateur est connecté et récupérer ses favoris
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

// Afficher les messages de session
if (isset($_SESSION['message'])) {
    echo '<div class="alert alert-success">' . htmlspecialchars($_SESSION['message']) . '</div>';
    unset($_SESSION['message']); // Supprimer le message après l'avoir affiché
}

// Fonction pour récupérer les biens
function getBiens($conn, $table) {
    $biens = array();
    $query = "SELECT * FROM `$table` ORDER BY date_creation DESC";
    
    if ($result = $conn->query($query)) {
        while ($row = $result->fetch_assoc()) {
            $biens[] = $row;
        }
        $result->free();
    } else {
        echo "<p style='color:red;'>Erreur lors de la récupération des biens : " . $conn->error . "</p>";
    }
    
    return $biens;
}

// Récupérer les biens
$biens_location = getBiens($conn, 'biens_en_location');
$biens_vente = getBiens($conn, 'biens_à_vendre');
?>

<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Pollux Immobilier - Accueil</title>
  <link rel="stylesheet" href="style.css" />
  <link rel="stylesheet" href="pages-common.css" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
<?php
if (isset($_SESSION['message'])) {
    echo '<div class="alert alert-success">' . htmlspecialchars($_SESSION['message']) . '</div>';
    unset($_SESSION['message']);
}
?>
  <header>
    <div class="header-container">
      <img src="logo.png" alt="Logo Pollux Immobilier" class="logo">
      <h1>Pollux Immobilier</h1>
    </div>
    <nav>
      <ul>
        <li><a href="index.php">Accueil</a></li>
        <li><a href="bien-locatifs.php">Louer</a></li>
        <li><a href="bien-vente.php">Acheter</a></li>
        <li><a href="vendre-estimer.php">Vendre/Estimer</a></li>
        <li><a href="presentation.html">Notre histoire</a></li>
        <li class="login-link"><a href="utilisateurs/login.php">Se connecter</a></li>
      </ul>
    </nav>
  </header>

  <main>
    <div class="container">
      <!-- Barre de recherche -->
      <section class="search-bar">
        <h2 class="page-main-title">Trouvez le bien de vos rêves</h2>
        <form id="propertySearch" class="search-form" 
        action="recherche.php" method="GET">
          <div class="form-group">
            <label for="searchTerm">Recherche</label>
            <input type="text" id="searchTerm" name="searchTerm"
       placeholder="Localisation"
       value="<?= htmlspecialchars($_GET['searchTerm'] ?? '') ?>">
          </div>
          
          <div class="form-group">
            <label for="propertyType">Type de bien</label>
            <select id="propertyType" name="propertyType">
              <option value="">Tous les biens</option>
              <option value="maison" <?= (isset($_GET['propertyType']) && $_GET['propertyType'] === 'maison') ? 'selected' : '' ?>>Maison</option>
            <option value="appartement" <?= (isset($_GET['propertyType']) && $_GET['propertyType'] === 'appartement') ? 'selected' : '' ?>>Appartement</option>
            <option value="terrain" <?= (isset($_GET['propertyType']) && $_GET['propertyType'] === 'terrain') ? 'selected' : '' ?>>Terrain</option>
            <option value="local" <?= (isset($_GET['propertyType']) && $_GET['propertyType'] === 'local') ? 'selected' : '' ?>>Local commercial</option>
            </select>
          </div>
          
          <div class="form-group">
            <label for="transactionType">Transaction</label>
            <select id="transactionType" name="transactionType">
              <option value="vente" <?= (isset($_GET['transactionType']) && $_GET['transactionType'] === 'vente') ? 'selected' : '' ?>>Achat</option>
            <option value="location" <?= (isset($_GET['transactionType']) && $_GET['transactionType'] === 'location') ? 'selected' : '' ?>>Location</option>
            </select>
          </div>
          
          <div class="form-group range-group">
            <label for="priceRange">Prix max</label>
            <input type="range" id="priceRange" name="priceRange" min="0" max="1000000" step="10000" 
               value="<?= htmlspecialchars($_GET['priceRange'] ?? '500000') ?>">
        <span id="priceValue"><?= isset($_GET['priceRange']) ? number_format($_GET['priceRange'], 0, ',', ' ') : '500 000' ?> €</span>
          </div>
          
          <div class="form-group range-group">
            <label for="surfaceRange">Surface min (m²)</label>
            <input type="range" id="surfaceRange" name="surfaceRange" min="0" max="200" step="5" 
               value="<?= htmlspecialchars($_GET['surfaceRange'] ?? '50') ?>">
        <span id="surfaceValue"><?= $_GET['surfaceRange'] ?? '50' ?> m²</span>
          </div>
          
          <button type="submit" class="search-button">Rechercher</button>
        </form>
      </section>
      
      <section class="listing">
        <h2>Nos biens locatifs disponibles</h2>
        <div class="properties-grid">
          <?php foreach ($biens_location as $bien): 
            $estFavori = isset($favoris_utilisateur[$bien['ID'] . '_location']);
          ?>
            <div class="property" data-annonce-id="<?= $bien['ID'] ?>" data-type="location">
              <div class="property-image-container">
                <img src="<?= htmlspecialchars($bien['image']) ?>" alt="<?= htmlspecialchars($bien['titre']) ?>" />
                <form method="POST" action="ajouter_favori.php" class="form-favori">
    <input type="hidden" name="annonce_id" value="<?= $bien['ID'] ?>">
    <input type="hidden" name="type_annonce" value="location">

    <button type="submit" class="coeur-favori <?= $estFavori ? 'actif' : '' ?>">
        <i class="fas fa-heart"></i>
    </button>
</form>
              </div>
              <div class="property-details">
                <h3><?= htmlspecialchars($bien['titre']) ?></h3>
                <p class="property-description"><?= htmlspecialchars($bien['description']) ?></p>
                <div class="property-footer">
                  <span class="property-price"><?= number_format($bien['loyer'] ?? 0, 0, ',', ' ') ?> €</span>
                  <a href="utilisateurs/annonce.php?ID=<?= $bien['ID'] ?>&type=location&from=<?= urlencode($_SERVER['PHP_SELF']) ?>" class="property-link">Voir ce bien</a>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </section>

      <section class="listing">
        <h2>Nos biens à vendre disponibles</h2>
        <div class="properties-grid">
          <?php foreach ($biens_vente as $bien): 
            $estFavori = isset($favoris_utilisateur[$bien['ID'] . '_vente']);
          ?>
            <div class="property" data-annonce-id="<?= $bien['ID'] ?>" data-type="vente">
              <div class="property-image-container">
                <img src="<?= htmlspecialchars($bien['image']) ?>" alt="<?= htmlspecialchars($bien['titre']) ?>" />
                <form method="POST" action="ajouter_favori.php" class="form-favori">
    <input type="hidden" name="annonce_id" value="<?= $bien['ID'] ?>">
    <input type="hidden" name="type_annonce" value="vente">

    <button type="submit" class="coeur-favori <?= $estFavori ? 'actif' : '' ?>">
        <i class="fas fa-heart"></i>
    </button>
</form>
              </div>
              <div class="property-details">
                <h3><?= htmlspecialchars($bien['titre']) ?></h3>
                <p class="property-description"><?= htmlspecialchars($bien['description']) ?></p>
                <div class="property-footer">
                  <span class="property-price"><?= number_format($bien['prix'] ?? 0, 0, ',', ' ') ?> €</span>
                  <a href="utilisateurs/annonce.php?ID=<?= $bien['ID'] ?>&type=vente&from=<?= urlencode($_SERVER['PHP_SELF']) ?>" class="property-link">Voir ce bien</a>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </section>
    </div>
  </main>

  <footer>
    <div class="footer-content">
      <div class="footer-section">
        <h3>Contact</h3>
        <p>Email: contact@pollux-immobilier.fr</p>
        <p>Tél: 01 23 45 67 89</p>
      </div>
      <div class="footer-section">
        <h3>Liens rapides</h3>
        <ul>
          <li><a href="mentions-legales.html">Mentions légales</a></li>
          <li><a href="cgv.html">CGV</a></li>
          <li><a href="notre-histoire.html">Notre histoire</a></li>
        </ul>
      </div>
      <div class="footer-section">
        <h3>Suivez-nous</h3>
        <div class="social-links">
          <a href="https://www.facebook.com/ABimmobilier/?locale=fr_FR" class="social-icon"><i class="fab fa-facebook"></i></a>
          <a href="https://x.com/cabinet_ravier" class="social-icon"><i class="fab fa-twitter"></i></a>
          <a href="https://www.instagram.com/lachouetteagenceimmobiliere/" class="social-icon"><i class="fab fa-instagram"></i></a>
        </div>
      </div>
    </div>
    <div class="footer-bottom">
      <p>&copy; <?php echo date('Y'); ?> Pollux Immobilier. Tous droits réservés.</p>
    </div>
  </footer>

  <script>
document.addEventListener('DOMContentLoaded', () => {

    

    // Sélectionner tous les boutons favoris
    const boutonsFavoris = document.querySelectorAll('.btn-favori');

    boutonsFavoris.forEach(bouton => {
        bouton.addEventListener('click', function () {

            const annonceId = this.dataset.annonceId;
            const type = this.dataset.type;
            const estActif = this.classList.contains('actif');
            const action = estActif ? 'supprimer' : 'ajouter';
            const coeurIcon = this.querySelector('i');

            this.classList.add('animating');

            fetch('ajax/favoris.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    annonceId: annonceId,
                    type: type,
                    action: action
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    this.classList.toggle('actif');
                    coeurIcon.style.transform = 'scale(1.5)';
                    setTimeout(() => {
                        coeurIcon.style.transform = 'scale(1)';
                    }, 300);
                } else {
                    alert('Une erreur est survenue : ' + (data.message || 'Veuillez réessayer.'));
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                alert('Une erreur est survenue lors de la mise à jour des favoris.');
            })
            .finally(() => {
                this.classList.remove('animating');
            });

        });
    });

});
</script>
  <script>
  const priceSlider = document.getElementById('priceRange');
  const priceValue = document.getElementById('priceValue');
  priceSlider.addEventListener('input', () => {
    priceValue.textContent = parseInt(priceSlider.value).toLocaleString('fr-FR') + ' €';
  });

  const surfaceSlider = document.getElementById('surfaceRange');
  const surfaceValue = document.getElementById('surfaceValue');
  surfaceSlider.addEventListener('input', () => {
    surfaceValue.textContent = surfaceSlider.value + ' m²';
  });
</script>
  
</body>
</html>