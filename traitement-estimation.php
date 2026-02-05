<?php
require_once './utilisateurs/connexion.php';
require_once './includes/functions.php';

$titre_page = "Confirmation de demande d'estimation";
$message = "";

/* -----------------------------------------------------------
   FONCTION DE CALCUL D’ESTIMATION
----------------------------------------------------------- */
function calculerEstimation($type_annonce, $surface, $adresse) {
    $prix_m2 = 0;

    switch (strtolower($type_annonce)) {
        case 'maison': $prix_m2 = 2500; break;
        case 'appartement': $prix_m2 = 3500; break;
        case 'local': $prix_m2 = 2000; break;
        default: $prix_m2 = 3000;
    }

    if (stripos($adresse, 'paris') !== false) {
        $prix_m2 *= 1.5;
    } elseif (stripos($adresse, 'lyon') !== false || stripos($adresse, 'marseille') !== false) {
        $prix_m2 *= 1.2;
    }

    return $surface * $prix_m2;
}

/* -----------------------------------------------------------
   FONCTION POUR NOTIFIER LES ADMINS
----------------------------------------------------------- */
function notifierAdmins($data) {
    global $conn;

    // Récupération des emails admin
    $query = "SELECT email FROM utilisateurs WHERE role = 'admin' AND statut = 'actif'";
    $result = $conn->query($query);

    $admins = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $admins[] = $row['email'];
        }
        $result->free();
    }

    if (empty($admins)) {
        return;
    }

    // Sujet + contenu
    $sujet = "Nouvelle demande d'estimation - " . $data['type_annonce'] . " à " . $data['adresse'];

    $contenu = "Une nouvelle demande d'estimation a été soumise :\n\n";
    $contenu .= "Nom : " . $data['nom'] . "\n";
    $contenu .= "Email : " . $data['email'] . "\n";
    $contenu .= "Téléphone : " . ($data['telephone'] ?: 'Non renseigné') . "\n";
    $contenu .= "Type d'annonce : " . $data['type_annonce'] . "\n";
    $contenu .= "Adresse : " . $data['adresse'] . "\n";
    $contenu .= "Surface : " . $data['surface'] . " m²\n";
    $contenu .= "Description : " . ($data['description'] ?: 'Aucune') . "\n\n";
    $contenu .= "Date de la demande : " . $data['date_demande'] . "\n";
    $contenu .= "Estimation automatique : " . number_format($data['estimation_calculee'], 0, ',', ' ') . " €\n";
    $contenu .= "À valider avant envoi à l'utilisateur.\n";

    $headers = "From: no-reply@pollux-immobilier.com\r\n";
    $headers .= "Reply-To: " . $data['email'] . "\r\n";
    $headers .= "X-Mailer: PHP/" . phpversion();

    foreach ($admins as $admin_email) {
        @mail($admin_email, $sujet, $contenu, $headers);
    }
}

/* -----------------------------------------------------------
   TRAITEMENT DU FORMULAIRE
----------------------------------------------------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Champs requis
    $required = ['nom', 'email', 'type_annonce', 'adresse', 'surface'];
    $errors = [];

    foreach ($required as $field) {
        if (empty($_POST[$field])) {
            $errors[] = "Le champ " . ucfirst($field) . " est requis.";
        }
    }

    if (!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = "L'email n'est pas valide.";
    }

    if (empty($errors)) {

        // Nettoyage
        $nom = $conn->real_escape_string(trim($_POST['nom']));
        $email = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
        $telephone = !empty($_POST['telephone']) ? $conn->real_escape_string(trim($_POST['telephone'])) : null;
        $type_annonce = $conn->real_escape_string(trim($_POST['type_annonce']));
        $adresse = $conn->real_escape_string(trim($_POST['adresse']));
        $surface = intval($_POST['surface']);
        $description = !empty($_POST['description']) ? $conn->real_escape_string(trim($_POST['description'])) : '';
        $date_demande = date('Y-m-d H:i:s');

        // Calcul estimation
        $estimation_calculee = calculerEstimation($type_annonce, $surface, $adresse);
        $estimation_prix = $estimation_calculee;

        try {
            // Insertion SQL
            $stmt = $conn->prepare("INSERT INTO estimations 
                (nom, email, type_annonce, adresse, surface, description, date_demande, estimation_prix)
                VALUES (?, ?, ?, ?, ?, ?, ?,?)");

            if ($stmt === false) {
                throw new Exception("Erreur de préparation : " . $conn->error);
            }

            $stmt->bind_param("sssisssi", 
                $nom, $email, $type_annonce, $adresse, $surface, $description, $date_demande, $estimation_prix
            );

            if (!$stmt->execute()) {
                throw new Exception("Erreur d'exécution : " . $stmt->error);
            }

            $stmt->close();

            // Préparation des données pour l’email admin
            $data = [
                'nom' => $nom,
                'email' => $email,
                'telephone' => $telephone,
                'type_annonce' => $type_annonce,
                'adresse' => $adresse,
                'surface' => $surface,
                    'description' => $description,
                'date_demande' => $date_demande,
                'estimation_calculee' => $estimation_calculee
            ];

            notifierAdmins($data);

            /* -----------------------------------------------------------
               MESSAGE FINAL POUR L’UTILISATEUR
            ----------------------------------------------------------- */
            $titre_page = "Demande envoyée avec succès";

            $estConnecte = isset($_SESSION['utilisateur']);

            $message = "
                <div class='confirmation-message'>
                    <h2>Merci $nom !</h2>
                    <p>Votre demande d'estimation pour votre bien à <strong>$adresse</strong> a bien été enregistrée.</p>
                    <p>Notre équipe va l'analyser et vous recontactera rapidement.</p>

                    <div class='recap-details'>
                        <h3>Récapitulatif :</h3>
                        <ul>
                            <li><strong>Type de bien :</strong> $type_annonce</li>
                            <li><strong>Surface :</strong> $surface m²</li>
                            <li><strong>Email :</strong> $email</li>
                            <li><strong>Téléphone :</strong> " . ($telephone ?: 'Non renseigné') . "</li>
                        </ul>
                    </div>";

            if (!$estConnecte) {
                $message .= "
                    <div class='inscription-suggestion'>
                        <h3>Créez un compte pour suivre vos demandes</h3>
                        <p>En créant un compte, vous pourrez :</p>
                        <ul>
                            <li>Suivre vos demandes d'estimation</li>
                            <li>Enregistrer vos biens favoris</li>
                            <li>Recevoir des offres personnalisées</li>
                        </ul>
                        <a href='utilisateurs/inscription.php?email=" . urlencode($email) . "&nom=" . urlencode($nom) . "' class='btn'>
                            Créer un compte
                        </a>
                        <p>Déjà un compte ? <a href='utilisateurs/login.php'>Connectez-vous</a></p>
                    </div>";
            }

            $message .= "
                    <div class='center'>
                        <a href='index.php' class='btn-retour'>Retour à l'accueil</a>
                    </div>
                </div>";

        } catch (Exception $e) {
            $titre_page = "Erreur lors de l'enregistrement";
            $message = "<div class='error-message'>
                            <h2>Une erreur est survenue</h2>
                            <p>" . htmlspecialchars($e->getMessage()) . "</p>
                            <a href='vendre-estimer.php' class='btn-retour'>Retour au formulaire</a>
                        </div>";
        }

    } else {
        // Champs manquants
        $titre_page = "Champs manquants";
        $message = "<div class='error-message'>
                        <h2>Champs obligatoires manquants</h2>
                        <p>Veuillez remplir tous les champs requis.</p>
                        <a href='vendre-estimer.php' class='btn-retour'>Retour au formulaire</a>
                    </div>";
    }

} else {
    // Accès direct interdit
    $titre_page = "Accès non autorisé";
    $message = "<div class='error-message'>
                    <h2>Accès refusé</h2>
                    <p>Cette page ne peut pas être accédée directement.</p>
                    <a href='vendre-estimer.php' class='btn-retour'>Retour au formulaire</a>
                </div>";
}

?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($titre_page) ?> - Pollux Immobilier</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="pages-common.css">
    <style>
        .confirmation-container {
            max-width: 800px;
            margin: 40px auto;
            padding: 30px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
        }
        
        .confirmation-message h2 {
            color: #157f68;
            margin-bottom: 20px;
        }
        
        .recap-details {
            text-align: left;
            max-width: 500px;
            margin: 20px auto;
            padding: 20px;
            background: #f9f9f9;
            border-radius: 4px;
        }
        
        .recap-details h3 {
            margin-top: 0;
            color: #2c3e50;
        }
        
        .recap-details ul {
            padding-left: 20px;
        }
        
        .btn-retour {
            display: inline-block;
            background: #157f68;
            color: white;
            padding: 10px 20px;
            border-radius: 4px;
            text-decoration: none;
            margin-top: 20px;
        }
        
        .btn-retour:hover {
            background: #126a56;
        }
        
        .error-message {
            color: #721c24;
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
            padding: 15px;
            border-radius: 4px;
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <main class="confirmation-container">
        <?= $message ?>
    </main>
    
    <?php include 'includes/footer.php'; ?>
</body>
</html>