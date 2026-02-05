<?php
require_once 'auth.php';
verifierConnexion();

// Inclure la connexion à la base de données
require_once 'connexion.php';

// Récupérer les informations de l'utilisateur
$utilisateur_id = $_SESSION['utilisateur_id'];
$query = "SELECT * FROM utilisateurs WHERE ID = ?";
$stmt = mysqli_prepare($conn, $query);

if (!$stmt) {
    die("Erreur de préparation de la requête: " . mysqli_error($conn));
}

mysqli_stmt_bind_param($stmt, "i", $utilisateur_id);

if (!mysqli_stmt_execute($stmt)) {
    die("Erreur d'exécution de la requête: " . mysqli_stmt_error($stmt));
}

$result = mysqli_stmt_get_result($stmt);
$utilisateur = mysqli_fetch_assoc($result);

// Si l'utilisateur n'est pas trouvé, déconnecter et rediriger
if (!$utilisateur) {
    session_destroy();
    if (!headers_sent()) {
        header("Location: /Pollux_immobilier/utilisateurs/login.php");
        exit();
    }
}

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = trim($_POST['nom'] ?? '');
    $prenom = trim($_POST['prenom'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $telephone = trim($_POST['telephone'] ?? '');
    $adresse = trim($_POST['adresse'] ?? '');
    $code_postal = trim($_POST['code_postal'] ?? '');
    $ville = trim($_POST['ville'] ?? '');
    $pays = trim($_POST['pays'] ?? '');

    // Validation des données
    $errors = [];
    
    if (empty($nom)) {
        $errors[] = "Le nom est obligatoire";
    }
    
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Veuillez entrer une adresse email valide";
    }

    // Mise à jour des informations si pas d'erreurs
    if (empty($errors)) {
        $update_query = "UPDATE utilisateurs SET 
                        nom = ?, 
                        prenom = ?,
                        email = ?, 
                        localisation =?
                        WHERE ID = ?";
        
        $stmt = mysqli_prepare($conn, $update_query);
        mysqli_stmt_bind_param($stmt, "ssssi", 
            $nom, $prenom, $email, $localisation, $user_id);
        
        if (mysqli_stmt_execute($stmt)) {
            $_SESSION['success_message'] = "Profil mis à jour avec succès";
            // Mettre à jour les informations de session
            $_SESSION['nom'] = $nom;
            $_SESSION['email'] = $email;
            // Recharger la page pour afficher les nouvelles données
            if (!headers_sent()) {
                header("Location: profil_utilisateur.php");
                exit();
            }
        } else {
            $errors[] = "Une erreur est survenue lors de la mise à jour du profil";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifier mon profil - Pollux Immobilier</title>
    <link rel="stylesheet" href="../style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <?php include('../includes/header.php'); ?>

    <div class="page-title">
        <div class="container">
            <h1>Modifier mon profil</h1>
            <p>Mettez à jour vos informations personnelles</p>
        </div>
    </div>

    <div class="container">
        <?php if (!empty($errors)): ?>
            <div class="error-message">
                <?php foreach ($errors as $error): ?>
                    <p><?= htmlspecialchars($error) ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <div class="profile-container">
            <div class="profile-sidebar">
                <div class="profile-photo-container">
                    <div class="profile-photo">
                        <?php if (!empty($user['photo_profil'])): ?>
                            <img src="../uploads/profils/<?php echo htmlspecialchars($user['photo_profil']); ?>" 
                                 alt="Photo de profil" class="profile-image">
                        <?php else: ?>
                            <div class="profile-initials">
                                <?php 
                                    $initials = '';
                                    if (!empty($utilisateur['prenom'])) $initials .= $utilisateur['prenom'][0];
                                    if (!empty($utilisateur['nom'])) $initials .= $utilisateur['nom'][0];
                                    echo strtoupper($initials ?: substr($utilisateur['email'], 0, 2));
                                ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <form method="POST" enctype="multipart/form-data" class="photo-upload-form">
                        <label for="photo_profil" class="btn-upload">
                            <i class="fas fa-camera"></i> Changer la photo
                            <input type="file" id="photo_profil" name="photo_profil" accept="image/*" 
                                   onchange="this.form.submit()" style="display: none;">
                        </label>
                    </form>
                </div>

                <div class="profile-actions">
                    <a href="profil_utilisateur.php" class="btn-action">
                        <i class="fas fa-arrow-left"></i> Retour au profil
                    </a>
                    <a href="changer_mot_de_passe.php" class="btn-action">
                        <i class="fas fa-key"></i> Changer le mot de passe
                    </a>
                </div>
            </div>

            <div class="profile-content">
                <form method="POST" action="" class="profile-form">
                    <div class="form-section">
                        <h2>Informations personnelles</h2>
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="nom">Nom</label>
                                <input type="text" id="nom" name="nom" 
                                       value="<?php echo htmlspecialchars($utilisateur['nom'] ?? ''); ?>" 
                                       class="form-control" required>
                            </div>
                            <div class="form-group">
                                <label for="prenom">Prénom</label>
                                <input type="text" id="prenom" name="prenom" 
                                       value="<?php echo htmlspecialchars($utilisateur['prenom'] ?? ''); ?>" 
                                       class="form-control" required>
                            </div>
                            <div class="form-group">
                                <label for="email">Email</label>
                                <input type="email" id="email" name="email" 
                                       value="<?php echo htmlspecialchars($utilisateur['email']); ?>" 
                                       class="form-control" required>
                            </div>
                        </div>
                    </div>

                    <div class="form-section">
                        <h2>Adresse</h2>
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="adresse">Adresse</label>
                                <input type="text" id="adresse" name="adresse" 
                                       value="<?php echo htmlspecialchars($user['adresse'] ?? ''); ?>" 
                                       class="form-control">
                            </div>
                            <div class="form-group">
                                <label for="code_postal">Code postal</label>
                                <input type="text" id="code_postal" name="code_postal" 
                                       value="<?php echo htmlspecialchars($user['code_postal'] ?? ''); ?>" 
                                       class="form-control">
                            </div>
                            <div class="form-group">
                                <label for="ville">Ville</label>
                                <input type="text" id="ville" name="ville" 
                                       value="<?php echo htmlspecialchars($user['ville'] ?? ''); ?>" 
                                       class="form-control">
                            </div>
                            <div class="form-group">
                                <label for="pays">Pays</label>
                                <input type="text" id="pays" name="pays" 
                                       value="<?php echo htmlspecialchars($user['pays'] ?? ''); ?>" 
                                       class="form-control">
                            </div>
                        </div>
                    </div>

                    <div class="form-actions">
                        <a href="profil_utilisateur.php" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Annuler
                        </a>
                        <button type="submit" name="update_profile" class="btn btn-primary">
                            <i class="fas fa-save"></i> Enregistrer les modifications
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <style>
    .profile-container {
        display: flex;
        gap: 30px;
        margin-top: 20px;
    }

    .profile-sidebar {
        width: 280px;
        flex-shrink: 0;
    }

    .profile-content {
        flex-grow: 1;
        background: #fff;
        border-radius: 8px;
        padding: 30px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }

    .profile-photo-container {
        text-align: center;
        margin-bottom: 20px;
    }

    .profile-photo {
        width: 150px;
        height: 150px;
        margin: 0 auto 15px;
        border-radius: 50%;
        overflow: hidden;
        background: #f5f5f5;
        display: flex;
        align-items: center;
        justify-content: center;
        position: relative;
    }

    .profile-image {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .profile-initials {
        font-size: 48px;
        font-weight: bold;
        color: #666;
    }

    .photo-upload-form {
        margin-top: 15px;
    }

    .btn-upload {
        display: inline-block;
        padding: 8px 15px;
        background: #f0f0f0;
        border: 1px solid #ddd;
        border-radius: 4px;
        cursor: pointer;
        font-size: 14px;
    }

    .btn-upload:hover {
        background: #e0e0e0;
    }

    .profile-actions {
        background: #fff;
        border-radius: 8px;
        padding: 20px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        margin-bottom: 20px;
    }

    .btn-action {
        display: flex;
        align-items: center;
        padding: 10px 15px;
        margin-bottom: 10px;
        color: #333;
        text-decoration: none;
        border-radius: 4px;
        transition: background-color 0.2s;
    }

    .btn-action:hover {
        background-color: #f5f5f5;
        text-decoration: none;
    }

    .btn-action i {
        margin-right: 10px;
        width: 20px;
        text-align: center;
    }

    .form-section {
        margin-bottom: 30px;
    }

    .form-section h2 {
        font-size: 18px;
        margin-bottom: 20px;
        color: #333;
        padding-bottom: 10px;
        border-bottom: 1px solid #eee;
    }

    .form-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
        gap: 20px;
    }

    .form-group {
        margin-bottom: 15px;
    }

    .form-group label {
        display: block;
        margin-bottom: 5px;
        font-weight: 500;
        color: #555;
    }

    .form-control {
        width: 100%;
        padding: 10px 12px;
        border: 1px solid #ddd;
        border-radius: 4px;
        font-size: 14px;
    }

    .form-actions {
        display: flex;
        justify-content: flex-end;
        gap: 15px;
        margin-top: 30px;
        padding-top: 20px;
        border-top: 1px solid #eee;
    }

    .btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 10px 20px;
        border: none;
        border-radius: 4px;
        font-size: 14px;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.2s;
        text-decoration: none;
    }

    .btn i {
        margin-right: 8px;
    }

    .btn-primary {
        background-color: #007bff;
        color: white;
    }

    .btn-primary:hover {
        background-color: #0069d9;
    }

    .btn-secondary {
        background-color: #6c757d;
        color: white;
    }

    .btn-secondary:hover {
        background-color: #5a6268;
    }
    </style>

    <script>
    // Script pour prévisualiser l'image de profil avant upload
    document.getElementById('photo_profil')?.addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                const img = document.querySelector('.profile-image');
                if (img) {
                    img.src = e.target.result;
                }
            }
            reader.readAsDataURL(file);
        }
    });
    </script>
</body>
</html>
