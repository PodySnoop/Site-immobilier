<?php
require_once('connexion.php');
session_start();

$message = "";
echo "Page d'inscription OK";

// Traitement du formulaire
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nom = isset($_POST["nom"]) ? trim($_POST["nom"]) : "";
    $email = isset($_POST["email"]) ? trim($_POST["email"]) : "";
    $motdepasse = isset($_POST["motdepasse"]) ? $_POST["motdepasse"] : "";
}
    // 1. Vérification des champs
    if (empty($nom) || empty($email) || empty($motdepasse)) {
        $message = "Tous les champs sont obligatoires.";
    }
    // 2. Validation email
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Adresse email invalide.";
    }
    else {
        // 3. Vérifier si l'email existe déjà
        $stmt = $conn->prepare("SELECT id FROM utilisateurs WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $message = "Cet email est déjà utilisé.";
        } else {
            // 4. Hachage du mot de passe
            $motdepasse_hash = password_hash($motdepasse, PASSWORD_DEFAULT);

            // 5. Insertion dans la base
            $stmt = $conn->prepare("INSERT INTO utilisateurs (nom, email, mot_de_passe) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $nom, $email, $motdepasse_hash);

            if ($stmt->execute()) {
                $message = "✅ Inscription réussie ! Vous pouvez maintenant vous connecter.";
            
                 // ID du nouvel utilisateur
                $nouvel_utilisateur_id = $stmt->insert_id;

                // Enregistrer l'activité
                $log = $conn->prepare("
                    INSERT INTO activites (date_activite, titre_activite, utilisateur_id, action, details)
                    VALUES (NOW(), ?, ?, ?, ?)
                ");

                $titre   = "Nouvelle inscription";
                $action  = "inscription_utilisateur";
                $details = "L'utilisateur $nom s'est inscrit avec l'email $email";

                $log->bind_param("siss", $titre, $nouvel_utilisateur_id, $action, $details);
                $log->execute();
                
            } else {
                $message = "❌ Erreur lors de l'inscription.";
            }
        }

        $stmt->close();
    }

    $conn->close();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inscription - Pollux Immobilier</title>
    <link rel="stylesheet" href="../style.css">
    <link rel="stylesheet" href="../pages-common.css">
    <style>
        .registration-container {
            max-width: 500px;
            margin: 50px auto;
            padding: 30px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .registration-title {
            color: #157f68;
            text-align: center;
            margin-bottom: 30px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: bold;
        }
        
        .form-group input {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
        }
        
        .btn-submit {
            background-color: #157f68;
            color: white;
            border: none;
            padding: 12px 20px;
            border-radius: 4px;
            cursor: pointer;
            width: 100%;
            font-size: 16px;
            transition: background-color 0.3s;
        }
        
        .btn-submit:hover {
            background-color: #0b1987;
        }
        
        .login-link {
            text-align: center;
            margin-top: 20px;
        }
        
        .message {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
            text-align: center;
        }
        
        .success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        /*Style bouton connectez-vous*/
        body header nav ul li.login-link a {
            color: #ffffff !important;  /* Couleur blanche */
            background-color: #157f68 !important;
            padding: 8px 15px !important;
            border-radius: 4px !important;
            transition: background-color 0.3s !important;
        }
        
        nav ul li.login-link a:hover {
            background-color:rgb(244, 244, 246);
            text-decoration: none;
        }
        
        .password-container {
            position: relative;
            display: flex;
            align-items: center;
        }
        
        .password-container input {
            padding-right: 40px !important; /* Pour laisser de la place à l'icône */
        }
        
        .toggle-password {
            position: absolute;
            right: 10px;
            cursor: pointer;
            user-select: none;
            font-size: 18px;
        }
        
        .toggle-password:hover {
            opacity: 0.8;
        }
        
        #password-strength {
            margin-top: 10px;
            padding: 10px;
            background: #f8f9fa;
            border-radius: 4px;
            font-size: 14px;
        }
        
        .requirement {
            display: flex;
            align-items: center;
            margin: 5px 0;
            color: #6c757d;
        }
        
        .indicator {
            display: inline-block;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background-color: #e9ecef;
            margin-right: 10px;
            border: 1px solid #dee2e6;
        }
        
        .valid {
            color: #198754;
        }
        
        .valid .indicator {
            background-color: #198754;
            border-color: #198754;
        }
        
        .invalid {
            color: #6c757d;
        }
        
        .invalid .indicator {
            background-color: #e9ecef;
        }
    </style>
</head>
<body>
  <header>
    <div class="header-container">
      <img src="../logo.png" alt="Logo Pollux Immobilier" class="logo">
      <h1>Pollux Immobilier</h1>
    </div>
    <nav>
      <ul>
        <li><a href="../index.php">Accueil</a></li>
        <li><a href="../bien-locatifs.php">Louer</a></li>
        <li><a href="../bien-vente.php">Acheter</a></li>
        <li><a href="../vendre-estimer.php">Vendre/Estimer</a></li>
        <li><a href="../presentation.html">Notre histoire</a></li>
        <li class="login-link"><a href="login.php" style="color: #ffffff !important;">Se connecter</a></li>
      </ul>
    </nav>
  </header>

  <div class="page-title">
    <div class="container">
      <h1>Créer un compte</h1>
    </div>
  </div>

  <main class="registration-container">
    <?php if (!empty($message)): ?>
        <div class="message <?= strpos($message, '✅') !== false ? 'success' : 'error' ?>">
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

    <form method="post" action="">
        <div class="form-group">
            <label for="nom">Nom complet</label>
            <input type="text" id="nom" name="nom" placeholder="Votre nom et prénom" required>
        </div>
        
        <div class="form-group">
            <label for="email">Adresse email</label>
            <input type="email" id="email" name="email" placeholder="votre@email.com" required>
        </div>
        
        <div class="form-group">
            <label for="motdepasse">Mot de passe</label>
            <div class="password-container">
                <input type="password" id="motdepasse" name="motdepasse" placeholder="Créez un mot de passe" required onkeyup="checkPasswordStrength(this.value)">
                <span class="toggle-password" onclick="togglePassword('motdepasse')">👁️</span>
            </div>
            <div id="password-strength">
                <div class="requirement" id="length">
                    <span class="indicator"></span>
                    <span>8 caractères minimum</span>
                </div>
                <div class="requirement" id="uppercase">
                    <span class="indicator"></span>
                    <span>1 lettre majuscule</span>
                </div>
                <div class="requirement" id="number">
                    <span class="indicator"></span>
                    <span>1 chiffre</span>
                </div>
                <div class="requirement" id="special">
                    <span class="indicator"></span>
                    <span>1 caractère spécial (!@#$%^&*)</span>
                </div>
            </div>
        </div>
        
        <button type="submit" class="btn-submit">Créer mon compte</button>
    </form>
    
    <div class="login-link">
        Vous avez déjà un compte ? <a href="login.php">Connectez-vous ici</a>
    </div>
  </main>

  <footer>
    <div class="container">
      <p>&copy; <?= date('Y') ?> Pollux Immobilier - Tous droits réservés</p>
    </div>
  </footer>
  <script>
        function togglePassword(inputId) {
            const input = document.getElementById(inputId);
            const toggleButton = event.currentTarget;
            
            if (input.type === 'password') {
                input.type = 'text';
                toggleButton.textContent = '👁️';
            } else {
                input.type = 'password';
                toggleButton.textContent = '👁️';
            }
        }
        
        function checkPasswordStrength(password) {
            // Vérifier la longueur
            const hasLength = password.length >= 8;
            // Vérifier les majuscules
            const hasUppercase = /[A-Z]/.test(password);
            // Vérifier les chiffres
            const hasNumber = /[0-9]/.test(password);
            // Vérifier les caractères spéciaux
            const hasSpecial = /[!@#$%^&*]/.test(password);
            
            // Mettre à jour les indicateurs
            updateIndicator('length', hasLength);
            updateIndicator('uppercase', hasUppercase);
            updateIndicator('number', hasNumber);
            updateIndicator('special', hasSpecial);
        }
        
        function updateIndicator(id, isValid) {
            const element = document.getElementById(id);
            if (isValid) {
                element.classList.add('valid');
                element.classList.remove('invalid');
            } else {
                element.classList.add('invalid');
                element.classList.remove('valid');
            }
        }
    </script>
</body>
</html>