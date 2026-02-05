<?php
session_start();
require_once('connexion.php');

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['utilisateur_id'])) {
    header('Location: login.php');
    exit();
}
$utilisateur_id = $_SESSION['utilisateur_id'];
$user_id = $_SESSION['utilisateur_id'];
$message = '';

// Initialisation des variables
$message = '';
$errors = [];
$est_valide = 0; // Par défaut, le bien est en attente de validation

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Récupération des données du formulaire
    // Variables communes
$titre = trim($_POST['titre'] ?? '');
$description = trim($_POST['description'] ?? '');
$surface = floatval($_POST['surface'] ?? 0);
$localisation = trim($_POST['localisation'] ?? '');
$type_annonce = trim($_POST['type_annonce'] ?? '');
$annee_construction = intval($_POST['annee_construction'] ?? 0);
$statut = trim($_POST['statut'] ?? 'a_louer');

if ($statut === 'a_louer') {
    $table = '`biens_en_location`';
} else {
    $table = '`biens_à_vendre`';
}
$taxe_fonciere = floatval($_POST['taxe_fonciere'] ?? 0);
$charges = floatval($_POST['charges'] ?? 0);
$etage = intval($_POST['etage'] ?? 0);
$nombre_pieces = intval($_POST['pieces'] ?? 0);
$garage = isset($_POST['parking']) ? 1 : 0;
$ascenseur = isset($_POST['ascenseur']) ? 1 : 0;
$balcon = isset($_POST['balcon']) ? 1 : 0;
$jardin = isset($_POST['jardin']) ? 1 : 0;
$meuble = isset($_POST['meuble']) ? 1 : 0;
$image = !empty($images[0]) ? basename($images[0]) : 'default.png';

// Variables spécifiques location
$loyer = floatval($_POST['loyer'] ?? 0);
$date_disponibilite = $_POST['date_disponibilite'] ?? date('Y-m-d');
$statut_validation = 'en attente';

// Variables spécifiques vente
$prix = floatval($_POST['prix'] ?? 0);
$consommation_energetique = $_POST['dpe'] ?? null;
$classe_energetique = $_POST['ges'] ?? null;
$est_viager = isset($_POST['est_viager']) ? 1 : 0;
$rente_viagere = floatval($_POST['rente_viagere'] ?? 0);
$occupation = isset($_POST['occupation']) ? 1 : 0;
$valeur_venale = floatval($_POST['valeur_venale'] ?? 0);
$bouquet = floatval($_POST['bouquet'] ?? 0);
    
    // Validation des champs obligatoires
    if (empty($titre)) {
        $errors[] = "Le titre est obligatoire";
    }
    if (empty($description)) {
        $errors[] = "La description est obligatoire";
    }
    if ($prix <= 0) {
        $errors[] = "Le prix doit être supérieur à 0";
    }
    if ($surface <= 0) {
        $errors[] = "La surface doit être supérieure à 0";
    }
    if (empty($localisation)) {
        $errors[] = "La localisation est obligatoire";
    }
    if (empty($type_annonce)) {
        $errors[] = "Le type de bien est obligatoire";
    }
    
    // Traitement des images
    $images = [];
    if (empty($errors) && !empty($_FILES['images']['name'][0])) {
        $uploadDir = '../uploads/biens/';
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        
        foreach ($_FILES['images']['tmp_name'] as $key => $tmp_name) {
            $fileName = $_FILES['images']['name'][$key];
            $fileTmpName = $_FILES['images']['tmp_name'][$key];
            $fileSize = $_FILES['images']['size'][$key];
            $fileError = $_FILES['images']['error'][$key];
            $fileType = $_FILES['images']['type'][$key];
            
            $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png', 'gif'];
            
            if (in_array($fileExt, $allowed)) {
                if ($fileError === 0) {
                    if ($fileSize < 5000000) { // 5MB max
                        $fileNameNew = uniqid('', true) . "." . $fileExt;
                        $fileDestination = $uploadDir . $fileNameNew;
                        
                        if (move_uploaded_file($fileTmpName, $fileDestination)) {
                            $images[] = $fileDestination;
                        } else {
                            $errors[] = "Erreur lors du téléchargement de l'image $fileName";
                        }
                    } else {
                        $errors[] = "L'image $fileName est trop volumineuse (max 5MB)";
                    }
                } else {
                    $errors[] = "Erreur lors du téléchargement de l'image $fileName";
                }
            } else {
                $errors[] = "Le format de l'image $fileName n'est pas autorisé";
            }
        }
    }
    
    // Si pas d'erreurs, on insère dans la base de données
    if (empty($errors)) {
        try {
            $conn->begin_transaction();
            
            if ($table === 'biens_en_location') {
                $query = "INSERT INTO $table (
    utilisateur_id, titre, description, localisation, surface, loyer, charges, type_annonce,
    annee_construction, meuble, date_disponibilite, image, etage, nombre_pieces,
    ascenseur, balcon, jardin, garage, date_creation, est_valide, statut_validation
) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?, ?)";

$stmt = $conn->prepare($query);
$stmt->bind_param(
    "isssdddsiissiiiiis",
    $utilisateur_id, $titre, $description, $localisation, $surface, $loyer, $charges, $type_annonce,
    $annee_construction, $meuble, $date_disponibilite, $image, $etage, $nombre_pieces,
    $ascenseur, $balcon, $jardin, $garage, $est_valide, $statut_validation
);

            } else {
                $table = '`biens_à_vendre`';
                $query = "INSERT INTO $table (
    utilisateur_id, titre, description, localisation, surface, prix, taxe_fonciere, charges,
    type_annonce, annee_construction, est_viager, consommation_energetique, image, etage,
    nombre_pieces, ascenseur, balcon, jardin, garage, classe_energetique,
    date_creation, rente_viagere, occupation, valeur_venale, bouquet, statut_validation
) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?, ?, ?, ?, ?)";

$stmt = $conn->prepare($query);
if (!$stmt) {
    die("Erreur prepare : " . $conn->error);
}

$consommation_energetique = $consommation_energetique ?? '';
$classe_energetique = $classe_energetique ?? '';
$statut_validation = $statut_validation ?? 'en attente';

echo 'len types = ' . strlen("isssddddsiissiiiiiisdidds") . '<br>';
echo 'count vars = ' . 25 . '<br>';
$stmt->bind_param(
    "isssddddsiissiiiiiisdidds",
    $utilisateur_id, $titre, $description, $localisation, $surface, $prix, $taxe_fonciere, $charges,
    $type_annonce, $annee_construction, $est_viager, $consommation_energetique, $image, $etage,
    $nombre_pieces, $ascenseur, $balcon, $jardin, $garage, $classe_energetique,
    $rente_viagere, $occupation, $valeur_venale, $bouquet, $statut_validation
);
            }
            
            if ($stmt->execute()) {
                $bien_id = $conn->insert_id;

                // Enregistrer l'activité
$log = $conn->prepare("
    INSERT INTO activites (date_activite, titre_activite, utilisateur_id, action, details)
    VALUES (NOW(), ?, ?, ?, ?)
");

$titre   = "Ajout d'un bien";
$action  = "ajout_bien";
$details = "L'utilisateur ID $utilisateur_id a ajouté le bien ID $bien_id : $titre";

$log->bind_param("siss", $titre, $utilisateur_id, $action, $details);
$log->execute();
$conn->commit();
$message = "Bien ajouté avec succès !";
                
                // Insertion des images
                if (!empty($images)) {
                    $image_query = "INSERT INTO images_annonce (annonce_id, fichier) VALUES (?, ?)";
                    $image_stmt = $conn->prepare($image_query);
                    
                    foreach ($images as $index => $image_path) {
                        $is_main = ($index === 0) ? 1 : 0;
                        $image_stmt->bind_param("is", $bien_id, $image_path);
                        $image_stmt->execute();
                    }
                }
                
                $conn->commit();
                $message = "Le bien a été ajouté à la liste pour validation avec succès";
                
                // Réinitialisation du formulaire
                $_POST = [];
            } else {
                throw new Exception("Erreur lors de l'ajout du bien: " . $conn->error);
            }
        } catch (Exception $e) {
            $conn->rollback();
            $errors[] = "Une erreur est survenue: " . $e->getMessage();
            
            // Suppression des images téléchargées en cas d'erreur
            foreach ($images as $image) {
                if (file_exists($image)) {
                    unlink($image);
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ajouter un bien - Pollux Immobilier</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 20px;
            background-color: #f4f4f4;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #2c3e50;
            text-align: center;
            margin-bottom: 20px;
        }
        .form-group {
            margin-bottom: 15px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        input[type="text"],
        input[type="number"],
        input[type="email"],
        input[type="tel"],
        input[type="date"],
        select,
        textarea {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }
        textarea {
            min-height: 100px;
            resize: vertical;
        }
        .btn {
            display: inline-block;
            background: #3498db;
            color: #fff;
            padding: 10px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            font-size: 16px;
        }
        .btn:hover {
            opacity: 0.9;
        }
        .btn-secondary {
            background: #6c757d;
        }
        .form-actions {
            margin-top: 20px;
            text-align: right;
        }
        .error-message {
            color: #dc3545;
            margin-bottom: 15px;
            padding: 10px;
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
            border-radius: 4px;
        }
        .success-message {
            color: #155724;
            margin-bottom: 15px;
            padding: 10px;
            background-color: #d4edda;
            border: 1px solid #c3e6cb;
            border-radius: 4px;
        }
        .checkbox-group {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }
        .checkbox-item {
            display: flex;
            align-items: center;
            margin-right: 15px;
        }
        .checkbox-item input {
            width: auto;
            margin-right: 5px;
        }
        .form-section {
            margin-bottom: 30px;
            padding: 20px;
            border: 1px solid #eee;
            border-radius: 4px;
        }
        .form-section h2 {
            margin-top: 0;
            color: #2c3e50;
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
        }
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 15px;
        }
        .image-preview {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 10px;
        }
        .image-preview-item {
            position: relative;
            width: 100px;
            height: 100px;
            border: 1px solid #ddd;
            border-radius: 4px;
            overflow: hidden;
        }
        .image-preview-item img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .remove-image {
            position: absolute;
            top: 5px;
            right: 5px;
            background: rgba(0,0,0,0.5);
            color: white;
            border: none;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
        }
    </style>
</head>
<body>
    <div class="container">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h1><i class="fas fa-plus-circle"></i> Ajouter un bien immobilier</h1>
            <a href="admin.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Retour au tableau de bord
            </a>
        </div>

        <?php if (!empty($errors)): ?>
            <div class="error-message">
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?= htmlspecialchars($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <?php if (!empty($message)): ?>
            <div class="success-message">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <form action="" method="POST" enctype="multipart/form-data">
            <div class="form-section">
                <h2><i class="fas fa-info-circle"></i> Informations générales</h2>
                <div class="form-grid">
                    <div class="form-group">
                        <label for="titre">Titre du bien *</label>
                        <input type="text" id="titre" name="titre" value="<?= htmlspecialchars($_POST['titre'] ?? '') ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="type_annonce">Type d'annonce *</label>
                        <select id="type_annonce" name="type_annonce" required>
                            <option value="">Sélectionnez un type</option>
                            <option value="appartement" <?= (isset($_POST['type_annonce']) && $_POST['type_annonce'] === 'appartement') ? 'selected' : '' ?>>Appartement</option>
                            <option value="maison" <?= (isset($_POST['type_annonce']) && $_POST['type_annonce'] === 'maison') ? 'selected' : '' ?>>Maison</option>
                            <option value="villa" <?= (isset($_POST['type_annonce']) && $_POST['type_annonce'] === 'villa') ? 'selected' : '' ?>>Villa</option>
                            <option value="bureau" <?= (isset($_POST['type_bien']) && $_POST['type_bien'] === 'bureau') ? 'selected' : '' ?>>Bureau</option>
                            <option value="local" <?= (isset($_POST['type_bien']) && $_POST['type_bien'] === 'local') ? 'selected' : '' ?>>Local commercial</option>
                            <option value="terrain" <?= (isset($_POST['type_bien']) && $_POST['type_bien'] === 'terrain') ? 'selected' : '' ?>>Terrain</option>
                            <option value="autre" <?= (isset($_POST['type_bien']) && $_POST['type_bien'] === 'autre') ? 'selected' : '' ?>>Autre</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="statut">Statut *</label>
                        <select id="statut" name="statut" required>
                            <option value="a_louer" <?= (isset($_POST['statut']) && $_POST['statut'] === 'a_louer') ? 'selected' : '' ?>>À louer</option>
                            <option value="a_vendre" <?= (isset($_POST['statut']) && $_POST['statut'] === 'a_vendre') ? 'selected' : '' ?>>À vendre</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="surface">Surface (m²) *</label>
                        <input type="number" id="surface" name="surface" min="0" value="<?= htmlspecialchars($_POST['surface'] ?? '') ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="pieces">Nombre de pièces *</label>
                        <input type="number" id="pieces" name="pieces" min="0" value="<?= htmlspecialchars($_POST['pieces'] ?? '1') ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="chambres">Nombre de chambres</label>
                        <input type="number" id="chambres" name="chambres" min="0" value="<?= htmlspecialchars($_POST['chambres'] ?? '0') ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="annee_construction">Année de construction</label>
                        <input type="number" id="annee_construction" name="annee_construction" min="1000" max="<?= date('Y') + 1 ?>" value="<?= htmlspecialchars($_POST['annee_construction'] ?? '') ?>">
                    </div>
                </div>

            </div>
            <!-- Champs spécifiques à la location -->
<div class="form-group location-field">
    <label for="charges">Charges incluses (€)</label>
    <input type="number" id="charges" name="charges" min="0" step="0.01" 
           value="<?= htmlspecialchars($_POST['charges'] ?? '0') ?>">
</div>

<div class="form-group location-field">
    <label for="meuble">Meublé</label>
    <input type="checkbox" id="meuble" name="meuble" value="1" 
           <?= isset($_POST['meuble']) ? 'checked' : '' ?>>
</div>
<div class="form-group location-field">
    <label for="Loyer">Loyer (€)</label>
    <input type="number" id="Loyer" name="Loyer" min="0" step="0.01" 
           value="<?= htmlspecialchars($_POST['Loyer'] ?? '0') ?>">
</div>

<!-- Champs spécifiques à la vente -->
<div class="form-group vente-field">
    <label for="taxe_fonciere">Taxe foncière (€/mois)</label>
    <input type="number" id="taxe_fonciere" name="taxe_fonciere" 
           min="0" step="0.01" 
          value="<?= htmlspecialchars($_POST['taxe_fonciere'] ?? '') ?>"
           oninput="calculerTaxeAnnuelle()"
           required>
    <p class="help-text">Montant annuel de la taxe foncière: <span id="taxe_annuelle">0.00</span> €</p>
</div>

<div class="form-group vente-field">
    <label for="Prix">Prix (€)</label>
    <input type="number" id="prix" name="prix" min="0" step="0.01" value="<?= htmlspecialchars($_POST['prix'] ?? '') ?>" required>
</div>

<div class="form-group vente-field">
    <label for="consommation_energetique">Classe énergétique</label>
    <select id="consommation_energetique" name="consommation_energetique" class="form-control">
        <option value="">Sélectionnez...</option>
        <option value="A" <?= ($_POST['consommation_energetique'] ?? '') === 'A' ? 'selected' : '' ?>>A</option>
        <option value="B" <?= ($_POST['consommation_energetique'] ?? '') === 'B' ? 'selected' : '' ?>>B</option>
        <option value="C" <?= ($_POST['consommation_energetique'] ?? '') === 'C' ? 'selected' : '' ?>>C</option>
        <option value="D" <?= ($_POST['consommation_energetique'] ?? '') === 'D' ? 'selected' : '' ?>>D</option>
        <option value="E" <?= ($_POST['consommation_energetique'] ?? '') === 'E' ? 'selected' : '' ?>>E</option>
        <option value="F" <?= ($_POST['consommation_energetique'] ?? '') === 'F' ? 'selected' : '' ?>>F</option>
        <option value="G" <?= ($_POST['consommation_energetique'] ?? '') === 'G' ? 'selected' : '' ?>>G</option>
    </select>
</div>
            <div class="form-section">
                <h2><i class="fas fa-map-marker-alt"></i> Localisation</h2>
                <div class="form-group">
            <label for="localisation">Localisation *</label>
            <input type="text" id="localisation" name="localisation" 
               value="<?= htmlspecialchars($_POST['localisation'] ?? '') ?>" 
               placeholder="Ex: 123 Rue Exemple, 75000 Paris" required>
            <p class="help-text">Veuillez saisir l'adresse complète du bien</p>
        </div>
                </div>
            </div>
            
            <div class="form-section">
                <h2><i class="fas fa-home"></i> Caractéristiques</h2>
                <div class="form-grid">
                    <div class="form-group">
                        <label>Équipements :</label>
                        <div class="checkbox-group">
                            <div class="checkbox-item">
                                <input type="checkbox" id="ascenseur" name="ascenseur" value="1" <?= (isset($_POST['ascenseur']) && $_POST['ascenseur'] == 1) ? 'checked' : '' ?>>
                                <label for="ascenseur">Ascenseur</label>
                            </div>
                            <div class="checkbox-item">
                                <input type="checkbox" id="balcon" name="balcon" value="1" <?= (isset($_POST['balcon']) && $_POST['balcon'] == 1) ? 'checked' : '' ?>>
                                <label for="balcon">Balcon</label>
                            </div>
                            <div class="checkbox-item">
                                <input type="checkbox" id="terrasse" name="terrasse" value="1" <?= (isset($_POST['terrasse']) && $_POST['terrasse'] == 1) ? 'checked' : '' ?>>
                                <label for="terrasse">Terrasse</label>
                            </div>
                            <div class="checkbox-item">
                                <input type="checkbox" id="jardin" name="jardin" value="1" <?= (isset($_POST['jardin']) && $_POST['jardin'] == 1) ? 'checked' : '' ?>>
                                <label for="jardin">Jardin</label>
                            </div>
                            <div class="checkbox-item">
                                <input type="checkbox" id="parking" name="parking" value="1" <?= (isset($_POST['parking']) && $_POST['parking'] == 1) ? 'checked' : '' ?>>
                                <label for="parking">Parking</label>
                            </div>
                            <div class="checkbox-item">
                                <input type="checkbox" id="piscine" name="piscine" value="1" <?= (isset($_POST['piscine']) && $_POST['piscine'] == 1) ? 'checked' : '' ?>>
                                <label for="piscine">Piscine</label>
                            </div>
                            <div class="checkbox-item">
                                <input type="checkbox" id="cheminee" name="cheminee" value="1" <?= (isset($_POST['cheminee']) && $_POST['cheminee'] == 1) ? 'checked' : '' ?>>
                                <label for="cheminee">Cheminée</label>
                            </div>
                            <div class="checkbox-item">
                                <input type="checkbox" id="climatisation" name="climatisation" value="1" <?= (isset($_POST['climatisation']) && $_POST['climatisation'] == 1) ? 'checked' : '' ?>>
                                <label for="climatisation">Climatisation</label>
                            </div>
                            <div class="checkbox-item">
                                <input type="checkbox" id="meuble" name="meuble" value="1" <?= (isset($_POST['meuble']) && $_POST['meuble'] == 1) ? 'checked' : '' ?>>
                                <label for="meuble">Meublé</label>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="chauffage">Type de chauffage</label>
                        <select id="chauffage" name="chauffage">
                            <option value="">Non spécifié</option>
                            <option value="electrique" <?= (isset($_POST['chauffage']) && $_POST['chauffage'] === 'electrique') ? 'selected' : '' ?>>Électrique</option>
                            <option value="gaz" <?= (isset($_POST['chauffage']) && $_POST['chauffage'] === 'gaz') ? 'selected' : '' ?>>Gaz</option>
                            <option value="fioul" <?= (isset($_POST['chauffage']) && $_POST['chauffage'] === 'fioul') ? 'selected' : '' ?>>Fioul</option>
                            <option value="bois" <?= (isset($_POST['chauffage']) && $_POST['chauffage'] === 'bois') ? 'selected' : '' ?>>Bois</option>
                            <option value="pompe_chaleur" <?= (isset($_POST['chauffage']) && $_POST['chauffage'] === 'pompe_chaleur') ? 'selected' : '' ?>>Pompe à chaleur</option>
                            <option value="autre" <?= (isset($_POST['chauffage']) && $_POST['chauffage'] === 'autre') ? 'selected' : '' ?>>Autre</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="dpe">Diagnostic de Performance Énergétique (DPE)</label>
                        <select id="dpe" name="dpe">
                            <option value="">Non spécifié</option>
                            <option value="A" <?= (isset($_POST['dpe']) && $_POST['dpe'] === 'A') ? 'selected' : '' ?>>A</option>
                            <option value="B" <?= (isset($_POST['dpe']) && $_POST['dpe'] === 'B') ? 'selected' : '' ?>>B</option>
                            <option value="C" <?= (isset($_POST['dpe']) && $_POST['dpe'] === 'C') ? 'selected' : '' ?>>C</option>
                            <option value="D" <?= (isset($_POST['dpe']) && $_POST['dpe'] === 'D') ? 'selected' : '' ?>>D</option>
                            <option value="E" <?= (isset($_POST['dpe']) && $_POST['dpe'] === 'E') ? 'selected' : '' ?>>E</option>
                            <option value="F" <?= (isset($_POST['dpe']) && $_POST['dpe'] === 'F') ? 'selected' : '' ?>>F</option>
                            <option value="G" <?= (isset($_POST['dpe']) && $_POST['dpe'] === 'G') ? 'selected' : '' ?>>G</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="ges">Émission de gaz à effet de serre (GES)</label>
                        <select id="ges" name="ges">
                            <option value="">Non spécifié</option>
                            <option value="A" <?= (isset($_POST['ges']) && $_POST['ges'] === 'A') ? 'selected' : '' ?>>A</option>
                            <option value="B" <?= (isset($_POST['ges']) && $_POST['ges'] === 'B') ? 'selected' : '' ?>>B</option>
                            <option value="C" <?= (isset($_POST['ges']) && $_POST['ges'] === 'C') ? 'selected' : '' ?>>C</option>
                            <option value="D" <?= (isset($_POST['ges']) && $_POST['ges'] === 'D') ? 'selected' : '' ?>>D</option>
                            <option value="E" <?= (isset($_POST['ges']) && $_POST['ges'] === 'E') ? 'selected' : '' ?>>E</option>
                            <option value="F" <?= (isset($_POST['ges']) && $_POST['ges'] === 'F') ? 'selected' : '' ?>>F</option>
                            <option value="G" <?= (isset($_POST['ges']) && $_POST['ges'] === 'G') ? 'selected' : '' ?>>G</option>
                        </select>
                    </div>
                </div>
            </div>
            
            <div class="form-section">
                <h2><i class="fas fa-image"></i> Photos du bien</h2>
                <div class="form-group">
                    <label for="images">Sélectionner des images (max 10, formats: JPG, PNG, GIF, max 5MB par image)</label>
                    <input type="file" id="images" name="images[]" multiple accept="image/*" onchange="previewImages(this)">
                    <div id="imagePreview" class="image-preview"></div>
                </div>
            </div>
            
            <div class="form-section">
                <h2><i class="fas fa-align-left"></i> Description détaillée</h2>
                <div class="form-group">
                    <label for="description">Description complète du bien *</label>
                    <textarea id="description" name="description" required><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
                </div>
            </div>
            
            <div class="form-actions">
                <button type="reset" class="btn btn-secondary">Réinitialiser</button>
                <button type="submit" class="btn">
                    <i class="fas fa-save"></i> Enregistrer le bien
                </button>
            </div>
        </form>
    </div>

    <script>
        // Aperçu des images sélectionnées
        function previewImages(input) {
            const preview = document.getElementById('imagePreview');
            preview.innerHTML = '';
            
            if (input.files) {
                const files = Array.from(input.files);
                
                // Limiter à 10 images
                if (files.length > 10) {
                    alert('Vous ne pouvez sélectionner que 10 images maximum.');
                    input.value = '';
                    return;
                }
                
                files.forEach((file, index) => {
                    const reader = new FileReader();
                    
                    reader.onload = function(e) {
                        const div = document.createElement('div');
                        div.className = 'image-preview-item';
                        
                        const img = document.createElement('img');
                        img.src = e.target.result;
                        
                        const removeBtn = document.createElement('button');
                        removeBtn.type = 'button';
                        removeBtn.className = 'remove-image';
                        removeBtn.innerHTML = '&times;';
                        removeBtn.onclick = function() {
                            div.remove();
                            
                            // Créer un nouveau DataTransfer pour mettre à jour les fichiers sélectionnés
                            const dataTransfer = new DataTransfer();
                            const newFiles = Array.from(input.files).filter((_, i) => i !== index);
                            
                            newFiles.forEach(file => {
                                dataTransfer.items.add(file);
                            });
                            
                            input.files = dataTransfer.files;
                        };
                        
                        div.appendChild(img);
                        div.appendChild(removeBtn);
                        preview.appendChild(div);
                    };
                    
                    reader.readAsDataURL(file);
                });
            }
        }
        
        // Validation du formulaire
        document.querySelector('form').addEventListener('submit', function(e) {
            const requiredFields = this.querySelectorAll('[required]');
            let isValid = true;
            
            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    isValid = false;
                    field.style.borderColor = 'red';
                } else {
                    field.style.borderColor = '#ddd';
                }
            });
            
            if (!isValid) {
                e.preventDefault();
                alert('Veuillez remplir tous les champs obligatoires.');
            }
        });
    </script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const statutSelect = document.getElementById('statut');
    const locationFields = document.querySelectorAll('.location-field');
    const venteFields = document.querySelectorAll('.vente-field');
    
    function toggleFields() {
        const isLocation = statutSelect.value === 'a_louer';
        
        // Afficher/masquer les champs spécifiques à la location
        locationFields.forEach(field => {
            field.style.display = isLocation ? 'block' : 'none';
        });
        
        // Afficher/masquer les champs spécifiques à la vente
        venteFields.forEach(field => {
            field.style.display = isLocation ? 'none' : 'block';
        });
        
        // Mettre à jour le libellé du prix
        const prixLabel = document.querySelector('label[for="prix"]');
        if (prixLabel) {
            prixLabel.textContent = isLocation ? 'Loyer (€) *' : 'Prix (€) *';
        }
    }
    
    // Écouter les changements de statut
    if (statutSelect) {
        statutSelect.addEventListener('change', toggleFields);
        // Appeler la fonction au chargement de la page
        toggleFields();
    }
});

function calculerTaxeAnnuelle() {
    const taxeMensuelle = parseFloat(document.getElementById('taxe_fonciere').value) || 0;
    const taxeAnnuelle = (taxeMensuelle * 12).toFixed(2);
    document.getElementById('taxe_annuelle').textContent = taxeAnnuelle;
}

// Calculer la taxe annuelle au chargement si une valeur existe déjà
document.addEventListener('DOMContentLoaded', function() {
    calculerTaxeAnnuelle();
});
</script>
</body>
</html>
