<?php
session_start();
require_once('connexion.php');

$message = '';
$success = false;

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['utilisateur_id'])) {
    header('Location: login.php');
    exit();
}

// Traitement du formulaire
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_id = $_SESSION['utilisateur_id'];
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Vérifier que les champs ne sont pas vides
    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $message = "Tous les champs sont obligatoires.";
    } 
    // Vérifier que les nouveaux mots de passe correspondent
    elseif ($new_password !== $confirm_password) {
        $message = "Les nouveaux mots de passe ne correspondent pas.";
    }
    // Vérifier que le nouveau mot de passe est différent de l'ancien
    elseif ($current_password === $new_password) {
        $message = "Le nouveau mot de passe doit être différent de l'actuel.";
    }
    // Vérifier la longueur minimale
    elseif (strlen($new_password) < 8) {
        $message = "Le nouveau mot de passe doit contenir au moins 8 caractères.";
    }
    else {
        // Vérifier le mot de passe actuel
        $stmt = $conn->prepare("SELECT mot_de_passe FROM utilisateurs WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        
        if ($user && password_verify($current_password, $user['mot_de_passe'])) {
            // Mettre à jour le mot de passe
            $new_password_hash = password_hash($new_password, PASSWORD_DEFAULT);
            $update_stmt = $conn->prepare("UPDATE utilisateurs SET mot_de_passe = ? WHERE id = ?");
            $update_stmt->bind_param("si", $new_password_hash, $user_id);
            
            if ($update_stmt->execute()) {
                $message = "Votre mot de passe a été mis à jour avec succès.";
                $success = true;
            } else {
                $message = "Une erreur est survenue lors de la mise à jour du mot de passe.";
            }
            $update_stmt->close();
        } else {
            $message = "Le mot de passe actuel est incorrect.";
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Changer le mot de passe - Pollux Immobilier</title>
    <link rel="stylesheet" href="../style.css">
    <link rel="stylesheet" href="../pages-common.css">
    <style>
        .password-container {
            position: relative;
            display: flex;
            align-items: center;
            margin-bottom: 15px;
        }
        
        .password-container input {
            width: 100%;
            padding: 12px 40px 12px 12px !important;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
        }
        
        .toggle-password {
            position: absolute;
            right: 10px;
            cursor: pointer;
            user-select: none;
            font-size: 18px;
        }
        
        .form-container {
            max-width: 500px;
            margin: 50px auto;
            padding: 30px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
            color: #333;
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
        
        #password-strength {
            margin: 10px 0;
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
                <li class="login-link"><a href="deconnexion.php" style="color: #ffffff !important;">Déconnexion</a></li>
            </ul>
        </nav>
    </header>

    <div class="page-title">
        <div class="container">
            <h1>Changer mon mot de passe</h1>
        </div>
    </div>

    <main class="form-container">
        <?php if (!empty($message)): ?>
            <div class="message <?= $success ? 'success' : 'error' ?>">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <?php if (!$success): ?>
        <form method="post" action="" onsubmit="return validateForm()">
            <div class="form-group">
                <label for="current_password">Mot de passe actuel</label>
                <div class="password-container">
                    <input type="password" id="current_password" name="current_password" required>
                    <span class="toggle-password" onclick="togglePassword('current_password')">👁️</span>
                </div>
            </div>
            
            <div class="form-group">
                <label for="new_password">Nouveau mot de passe</label>
                <div class="password-container">
                    <input type="password" id="new_password" name="new_password" required 
                           onkeyup="checkPasswordStrength(this.value)">
                    <span class="toggle-password" onclick="togglePassword('new_password')">👁️</span>
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
            
            <div class="form-group">
                <label for="confirm_password">Confirmer le nouveau mot de passe</label>
                <div class="password-container">
                    <input type="password" id="confirm_password" name="confirm_password" required>
                    <span class="toggle-password" onclick="togglePassword('confirm_password')">👁️</span>
                </div>
                <div id="password-match" style="color: #dc3545; font-size: 0.875em; margin-top: 5px; display: none;">
                    Les mots de passe ne correspondent pas
                </div>
            </div>
            
            <button type="submit" class="btn-submit">Changer le mot de passe</button>
        </form>
        <?php else: ?>
            <div style="text-align: center; margin-top: 20px;">
                <p>Votre mot de passe a été mis à jour avec succès.</p>
                <p><a href="profil_utilisateur.php">Retour au profil</a></p>
            </div>
        <?php endif; ?>
    </main>

    <footer>
        <div class="container">
            <p>&copy; <?= date('Y') ?> Pollux Immobilier - Tous droits réservés</p>
        </div>
    </footer>

    <script>
        function togglePassword(inputId) {
            const input = document.getElementById(inputId);
            if (input.type === 'password') {
                input.type = 'text';
            } else {
                input.type = 'password';
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
        
        function validateForm() {
            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            const passwordMatch = document.getElementById('password-match');
            
            if (newPassword !== confirmPassword) {
                passwordMatch.style.display = 'block';
                return false;
            }
            
            passwordMatch.style.display = 'none';
            return true;
        }
        
        // Vérifier la correspondance des mots de passe en temps réel
        document.getElementById('confirm_password').addEventListener('input', function() {
            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = this.value;
            const passwordMatch = document.getElementById('password-match');
            
            if (newPassword && confirmPassword) {
                if (newPassword !== confirmPassword) {
                    passwordMatch.style.display = 'block';
                } else {
                    passwordMatch.style.display = 'none';
                }
            } else {
                passwordMatch.style.display = 'none';
            }
        });
    </script>
</body>
</html>
