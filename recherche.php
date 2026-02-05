<?php
session_start();
require_once 'includes/db.php';

// Récupération des paramètres de recherche
$searchTerm = $_GET['searchTerm'] ?? '';
$propertyType = $_GET['propertyType'] ?? '';
$transactionType = $_GET['transactionType'] ?? 'vente'; // Par défaut sur 'vente' comme dans index.php
$priceRange = isset($_GET['priceRange']) ? (int)$_GET['priceRange'] : null;
$surfaceRange = isset($_GET['surfaceRange']) ? (int)$_GET['surfaceRange'] : null;

// Construction de la requête SQL de base
$table = ($transactionType === 'location') ? 'biens_en_location' : 'biens_à_vendre';
$sql = "SELECT * FROM `$table` WHERE 1=1";
$params = [];
$types = "";

// Filtre par terme de recherche (ville, quartier, code postal)
if (!empty($searchTerm)) {
    $sql .= " AND (localisation LIKE ?";
    $searchTermLike = '%' . $searchTerm . '%';
    $params[] = $searchTermLike;
    $types .= "s";
}

// Filtre par type de bien
if (!empty($propertyType)) {
    $sql .= " AND type_bien = ?";
    $params[] = $propertyType;
    $types .= "s";
}

// Filtre par prix
if ($priceRange !== null) {
    $priceColumn = ($transactionType === 'location') ? 'loyer' : 'prix';
    $sql .= " AND $priceColumn <= ?";
    $params[] = $priceRange;
    $types .= "i";
}

// Filtre par surface
if ($surfaceRange !== null) {
    $sql .= " AND surface >= ?";
    $params[] = $surfaceRange;
    $types .= "i";
}

// Exécution de la requête
$stmt = $conn->prepare($sql);
if ($stmt) {
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    $biens = $result->fetch_all(MYSQLI_ASSOC);
} else {
    die("Erreur SQL : " . $conn->error);
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Résultats de recherche - Pollux Immobilier</title>
    <link rel="stylesheet" href="styles.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <main class="container my-5">
        <h1 class="mb-4">Résultats de recherche</h1>
        
    <!-- Bouton retour à l'accueil -->
<div class="container mt-3 mb-4">
    <a href="index.php" class="btn btn-secondary">
        <i class="fas fa-arrow-left"></i> Retour à l'accueil
    </a>
</div>    
        <!-- Affichage du nombre de résultats -->
        <div class="mb-4">
            <p class="text-muted">
                <?= count($biens) ?> bien<?= count($biens) > 1 ? 's' : '' ?> trouvé<?= count($biens) > 1 ? 's' : '' ?>
                <?php if (!empty($searchTerm)): ?>
                    pour "<?= htmlspecialchars($searchTerm) ?>"
                <?php endif; ?>
            </p>
            
            <!-- Filtres actifs -->
            <div class="d-flex flex-wrap gap-2 mb-3">
                <?php if (!empty($propertyType)): ?>
                    <span class="badge bg-primary">
                        Type: <?= htmlspecialchars(ucfirst($propertyType)) ?>
                        <a href="?<?= http_build_query(array_merge($_GET, ['propertyType' => ''])) ?>" class="text-white ms-2">×</a>
                    </span>
                <?php endif; ?>
                
                <?php if (!empty($transactionType)): ?>
                    <span class="badge bg-success">
                        <?= $transactionType === 'location' ? 'Location' : 'Vente' ?>
                        <a href="?<?= http_build_query(array_merge($_GET, ['transactionType' => ''])) ?>" class="text-white ms-2">×</a>
                    </span>
                <?php endif; ?>
            </div>
        </div>

        <?php if (count($biens) > 0): ?>
            <div class="row g-4">
                <?php foreach ($biens as $bien): ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="card h-100">
                            <?php if (!empty($bien['image_principale'])): ?>
                                <img src="<?= htmlspecialchars($bien['image_principale']) ?>" class="card-img-top" alt="<?= htmlspecialchars($bien['titre']) ?>">
                            <?php else: ?>
                                <div class="bg-light text-center p-5">
                                    <i class="fas fa-home fa-4x text-muted"></i>
                                </div>
                            <?php endif; ?>
                            
                            <div class="card-body">
                                <h5 class="card-title"><?= htmlspecialchars($bien['titre']) ?></h5>
                                <p class="card-text text-muted">
                                    <i class="fas fa-map-marker-alt"></i> 
                                    <?= htmlspecialchars($bien['localisation'] ?? 'Localisation inconnue') ?>
                                    <?= $bien['surface'] ?? '—' ?> m²
                                    <?= $bien['nombre_pieces'] ?? '—' ?> pièces
                                </p>
                                <?php
                    // Détermine le bon montant selon le type de transaction
                    $montant = ($transactionType === 'location')
                        ? ($bien['loyer'] ?? null)
                        : ($bien['prix'] ?? null);
                ?>

            <p class="h5 text-primary">
            <?= $montant !== null ? number_format($montant, 0, ',', ' ') . ' €' : '—' ?>
    <?php if ($transactionType === 'location'): ?>
        <small class="text-muted">/mois</small>
    <?php endif; ?>
</p>
                                <div class="d-flex justify-content-between text-muted mb-3">
                                    <span><i class="fas fa-ruler-combined"></i> <?= $bien['surface'] ?> m²</span>
                                    <span><i class="fas fa-bed"></i> <?= $bien['nombre_pieces'] ?> pièces</span>
                                </div>
                                <div class="d-flex justify-content-between align-items-center">
                                <a href="utilisateurs/annonce.php?id=<?= $bien['ID'] ?>&type=vente" class="property-link">Voir ce bien</a>
                                    <?php if (isset($_SESSION['user_id'])): ?>
                                        <form method="POST" action="ajouter_favori.php" class="mb-0">
                                            <input type="hidden" name="annonce_id" value="<?= $bien['ID'] ?>">
                                            <input type="hidden" name="type_annonce" value="<?= $bien['type_annonce'] ?? 'vente' ?>">
                                            <button type="submit" class="btn btn-link text-danger" title="Ajouter aux favoris">
                                                <i class="far fa-heart"></i>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="text-center py-5">
                <i class="fas fa-search fa-4x text-muted mb-3"></i>
                <h3>Aucun bien ne correspond à votre recherche</h3>
                <p class="text-muted">Essayez de modifier vos critères de recherche</p>
                <a href="index.php" class="btn btn-primary mt-3">Nouvelle recherche</a>
            </div>
        <?php endif; ?>
    </main>

    <?php include 'includes/footer.php'; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
  
</body>
</html>