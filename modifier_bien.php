<?php
session_start();
require_once 'connexion.php';

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['utilisateur_id'])) {
    header('Location: login.php');
    exit();
}

$message = '';
$errors = [];
$bien = null;

// Récupérer l'ID du bien à modifier
$bien_id = $_GET['id'] ?? 0;

// Récupérer les informations du bien
$query = "
SELECT 
    id, titre, description, prix, utilisateur_id, consommation_energetique, classe_energetique, statut_validation,'vente' AS type_annonce
FROM biens_à_vendre
WHERE id = ?

UNION

SELECT 
    id, titre, description, loyer, utilisateur_id, NULL AS consommation_energetique, NULL AS classe_energetique, statut_validation, 'location' AS type_annonce
FROM biens_en_location
WHERE id = ?
";

$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $bien_id, $bien_id);
$stmt->execute();
$result = $stmt->get_result();
$bien = $result->fetch_assoc();

// Vérifier si le bien existe et appartient à l'utilisateur (sauf si admin)
if (!$bien || ($bien['utilisateur_id'] != $_SESSION['utilisateur_id'] && $_SESSION['role'] !== 'admin')) {
    header('Location: mes_biens.php');
    exit();
}

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Récupération et validation des données (similaire à ajouter_bien.php)
    $titre = trim($_POST['titre'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $prix = floatval($_POST['prix'] ?? 0);
    $surface = intval($_POST['surface'] ?? 0);
    $pieces = intval($_POST['pieces'] ?? 0);
    $charges = !empty($_POST['charges']) ? floatval($_POST['charges']) : 0;
    $chambres = intval($_POST['chambres'] ?? 0);
    $localisation = trim($_POST['localisation'] ?? '');
    $type_annonce = trim($_POST['type_annonce'] ?? '');
    $statut = trim($_POST['statut'] ?? 'a_louer');
    $meuble = isset($_POST['meuble']) ? 1 : 0;
    $ascenseur = isset($_POST['ascenseur']) ? 1 : 0;
    $balcon = isset($_POST['balcon']) ? 1 : 0;
    $terrasse = isset($_POST['terrasse']) ? 1 : 0;
    $jardin = isset($_POST['jardin']) ? 1 : 0;
    $parking = isset($_POST['parking']) ? 1 : 0;
    $piscine = isset($_POST['piscine']) ? 1 : 0;
    $cheminee = isset($_POST['cheminee']) ? 1 : 0;
    $climatisation = isset($_POST['climatisation']) ? 1 : 0;
    $annee_construction = !empty($_POST['annee_construction']) ? intval($_POST['annee_construction']) : null;
    $taxe_fonciere = !empty($_POST['taxe_fonciere']) ? floatval($_POST['taxe_fonciere']) : 0;
    $statut_validation = isset($_POST['statut_validation']) && $_SESSION['role'] === 'admin' ? 1 : $bien['statut_validation'];

    // Validation des champs obligatoires
    if (empty($titre)) $errors[] = "Le titre est obligatoire";
    if (empty($description)) $errors[] = "La description est obligatoire";
    if ($prix <= 0) $errors[] = "Le prix doit être supérieur à 0";
    if ($surface <= 0) $errors[] = "La surface doit être supérieure à 0";
    if (empty($localisation)) $errors[] = "La localisation est obligatoire";

    if (empty($errors)) {
        try {
            $conn->begin_transaction();

            // Déterminer la table à mettre à jour
            $table = $statut === 'a_louer' ? 'biens_en_location' : 'biens_à_vendre';
            
            // Requête de mise à jour
           if ($bien['type_annonce'] === 'location') {
    $query = "UPDATE biens_en_location SET
        titre = ?, description = ?, loyer = ?, surface = ?, nombre_pieces = ?, localisation = ?,
        type_annonce = ?, meuble = ?, ascenseur = ?, balcon = ?, terrasse = ?, jardin = ?, parking = ?,
        piscine = ?, cheminee = ?, climatisation = ?, annee_construction = ?, charges = ?,
        statut_validation = ?, date_modification = NOW()
        WHERE id = ? AND utilisateur_id = ?";
    
    $params = [ $titre, $description, $prix, $surface, $pieces, $localisation,
        $type_annonce, $meuble, $ascenseur, $balcon, $terrasse, $jardin, $parking,
        $piscine, $cheminee, $climatisation, $annee_construction, $charges,
        $statut_validation, $bien_id, $_SESSION['utilisateur_id']
    ];
    $types = 'ssdissiiiiiiiissdsdii';
} else {
    $query = "UPDATE biens_à_vendre SET
        titre = ?, description = ?, prix = ?, surface = ?, nombre_pieces = ?, localisation = ?, annee_construction = ?, statut_validation = ?, date_modification = NOW()
        WHERE id = ? AND utilisateur_id = ?";
    
    $params = [ $titre, $description, $prix, $surface, $pieces, $localisation, $annee_construction, $statut_validation, $bien_id, $_SESSION['utilisateur_id']
    ];
    $types = 'ssdissdsii';
}
            $stmt = $conn->prepare($query);
            $stmt->bind_param($types, ...$params);
            
            if ($stmt->execute()) {
                $conn->commit();
                $message = "L'annonce a été mise à jour avec succès";
                // Recharger les données du bien
                $refreshQuery = "SELECT * FROM $table WHERE id = ?";
$stmt = $conn->prepare($refreshQuery);
$stmt->bind_param("i", $bien_id);
$stmt->execute();
$bien = $stmt->get_result()->fetch_assoc();
            } else {
                throw new Exception("Erreur lors de la mise à jour de l'annonce");
            }
        } catch (Exception $e) {
            $conn->rollback();
            $errors[] = "Une erreur est survenue : " . $e->getMessage();
        }
    }
}

// Si le bien n'a pas été trouvé
if (!$bien) {
    header('Location: mes_biens.php');
    exit();
}

// Inclure l'en-tête
$pageTitle = "Modifier l'annonce";
include '../includes/header.php';
?>

<div class="container">
    <h1>Modifier l'annonce</h1>
    
    <?php if (!empty($message)): ?>
        <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>
    
    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <ul>
                <?php foreach ($errors as $error): ?>
                    <li><?= htmlspecialchars($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data" class="form-horizontal">
        <!-- Copiez ici le formulaire de ajouter_bien.php mais pré-rempli avec les valeurs de $bien -->
        <div class="form-group">
            <label for="titre">Titre *</label>
            <input type="text" id="titre" name="titre" class="form-control" 
                   value="<?= htmlspecialchars($bien['titre'] ?? '') ?>" required>
        </div>

        <div class="form-group">
  <label for="type_annonce">Type de bien *</label>
  <select name="type_annonce" id="type_annonce" class="form-control" required>
    <option value="">Sélectionner</option>
    <option value="vente" <?= ($bien['type_annonce'] === 'vente') ? 'selected' : '' ?>>À vendre</option>
    <option value="location" <?= ($bien['type_annonce'] === 'location') ? 'selected' : '' ?>>À louer</option>
  </select>
</div>

<div class="form-group">
  <label for="statut">Statut *</label>
  <select name="statut" id="statut" class="form-control" required>
    <option value="">Sélectionner une valeur</option>
    <option value="a_vendre" <?= ($bien['type_annonce'] === 'vente') ? 'selected' : '' ?>>À vendre</option>
    <option value="a_louer" <?= ($bien['type_annonce'] === 'location') ? 'selected' : '' ?>>À louer</option>
  </select>
</div>

<div class="form-group">
  <label for="surface">Surface (m²) *</label>
  <input type="number" name="surface" id="surface" class="form-control" required min="1"
         value="<?= htmlspecialchars($bien['surface'] ?? '') ?>">
</div>

<div class="form-group">
  <label for="pieces">Nombre de pièces *</label>
  <input type="number" name="pieces" id="pieces" class="form-control" required min="1"
         value="<?= htmlspecialchars($bien['nombre_pieces'] ?? '') ?>">
</div>

<div class="form-group">
  <label for="chambres">Nombre de chambres</label>
  <input type="number" name="chambres" id="chambres" class="form-control" min="0"
         value="<?= htmlspecialchars($bien['nombre_chambres'] ?? '') ?>">
</div>

<div class="form-group">
  <label for="annee_construction">Année de construction</label>
  <input type="number" name="annee_construction" id="annee_construction" class="form-control"
         value="<?= htmlspecialchars($bien['annee_construction'] ?? '') ?>">
</div>

<div class="form-group">
  <label for="taxe_fonciere">Charges incluses (€)</label>
  <input type="number" name="taxe_fonciere" id="taxe_fonciere" class="form-control" step="0.01"
         value="<?= htmlspecialchars($bien['taxe_fonciere'] ?? '') ?>">
</div>

<div class="form-check">
  <input type="checkbox" name="meuble" id="meuble" class="form-check-input"
         <?= !empty($bien['meuble']) ? 'checked' : '' ?>>
  <label for="meuble" class="form-check-label">Meublé</label>
</div>

<div class="form-group">
  <label for="prix"><?= ($bien['type_annonce'] === 'location') ? 'Loyer' : 'Prix' ?> (€) *</label>
  <input type="number" name="prix" id="prix" class="form-control" required step="0.01"
         value="<?= htmlspecialchars($bien['type_annonce'] === 'location' ? $bien['loyer'] : $bien['prix']) ?>">
</div>

<div class="form-group">
  <label for="localisation">Localisation *</label>
  <input type="text" name="localisation" id="localisation" class="form-control" required
         value="<?= htmlspecialchars($bien['localisation'] ?? '') ?>">
</div>

<?php
$equipements = ['ascenseur', 'balcon', 'terrasse', 'jardin', 'parking', 'piscine', 'cheminee', 'climatisation'];
foreach ($equipements as $equipement): ?>
  <div class="form-check">
    <input type="checkbox" name="<?= $equipement ?>" id="<?= $equipement ?>" class="form-check-input"
           <?= !empty($bien[$equipement]) ? 'checked' : '' ?>>
    <label for="<?= $equipement ?>" class="form-check-label"><?= ucfirst($equipement) ?></label>
  </div>
<?php endforeach; ?>

<div class="form-group">
  <label for="chauffage">Type de chauffage</label>
  <input type="text" name="chauffage" id="chauffage" class="form-control"
         value="<?= htmlspecialchars($bien['chauffage'] ?? '') ?>">
</div>

<div class="form-group">
  <label for="consommation_energetique">Diagnostic de Performance Énergétique (DPE)</label>
  <input type="text" name="consommation_energetique" id="consommation_energetique" class="form-control"
         value="<?= htmlspecialchars($bien['consommation_energetique'] ?? '') ?>">
</div>

<div class="form-group">
  <label for="classe_energetique">Émission de gaz à effet de serre (GES)</label>
  <input type="text" name="classe_energetique" id="classe_energetique" class="form-control"
         value="<?= htmlspecialchars($bien['classe_energetique'] ?? '') ?>">
</div>

        <div class="form-group">
            <label for="description">Description *</label>
            <textarea id="description" name="description" class="form-control" 
                      rows="5" required><?= htmlspecialchars($bien['description'] ?? '') ?></textarea>
        </div>       

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Mettre à jour l'annonce</button>
            <a href="mes_biens.php" class="btn btn-secondary">Annuler</a>
            
            <?php if ($_SESSION['role'] === 'admin'): ?>
                <div class="form-check mt-3">
                    <input type="checkbox" id="statut_validation" name="statut_validation" 
                           class="form-check-input" <?= ($bien['statut_validation'] ?? 0) ? 'checked' : '' ?>>
                    <label class="form-check-label" for="statut_validation">Annonce validée</label>
                </div>
            <?php endif; ?>
        </div>
    </form>
</div>

<?php include '../includes/footer.php'; ?>