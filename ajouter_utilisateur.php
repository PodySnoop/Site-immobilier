<?php
session_start();

// Vérifier si l'utilisateur est connecté et est administrateur
if (!isset($_SESSION['utilisateur']) || $_SESSION['utilisateur']['role'] !== 'admin') {
    header('Location: ../index.php');
    exit();
}

require_once '../includes/db.php';

// Initialisation des variables
$message = '';
$errors = [];

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Récupération des données du formulaire
    $nom = trim($_POST['nom'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $mot_de_passe = $_POST['mot_de_passe'] ?? '';
    $confirmation_mot_de_passe = $_POST['confirmation_mot_de_passe'] ?? '';
    $role = trim($_POST['role'] ?? 'user');
    
    // Validation des champs
    if (empty($nom)) {
        $errors[] = "Le nom est obligatoire.";
    }
    
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Veuillez entrer une adresse email valide.";
    } else {
        // Vérifier si l'email existe déjà
        $stmt = $conn->prepare("SELECT id FROM utilisateurs WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $errors[] = "Cette adresse email est déjà utilisée.";
        }
    }
    
    if (strlen($mot_de_passe) < 8) {
        $errors[] = "Le mot de passe doit contenir au moins 8 caractères.";
    }
    
    if ($mot_de_passe !== $confirmation_mot_de_passe) {
        $errors[] = "Les mots de passe ne correspondent pas.";
    }
    
    // Si pas d'erreurs, on insère dans la base de données
    if (empty($errors)) {
        $mot_de_passe_hash = password_hash($mot_de_passe, PASSWORD_DEFAULT);
        $date_creation = date('Y-m-d H:i:s');
        
        $stmt = $conn->prepare("INSERT INTO utilisateurs (nom, email, mot_de_passe, role, date_creation) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", $nom, $email, $mot_de_passe_hash, $role, $date_creation);
        
        if ($stmt->execute()) {
            $message = "L'utilisateur a été ajouté avec succès.";
            // Réinitialisation du formulaire
            $_POST = [];
        } else {
            $errors[] = "Une erreur est survenue lors de l'ajout de l'utilisateur: " . $conn->error;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ajouter un utilisateur - Pollux Immobilier</title>
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
            max-width: 800px;
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
        input[type="email"],
        input[type="password"],
        select {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
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
        .password-strength {
            margin-top: 5px;
            font-size: 0.9em;
        }
        .strength-weak { color: #dc3545; }
        .strength-medium { color: #ffc107; }
        .strength-strong { color: #28a745; }
    </style>
</head>
<body>
    <div class="container">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h1><i class="fas fa-user-plus"></i> Ajouter un utilisateur</h1>
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

        <form action="" method="POST">
            <div class="form-group">
                <label for="nom">Nom complet *</label>
                <input type="text" id="nom" name="nom" value="<?= htmlspecialchars($_POST['nom'] ?? '') ?>" required>
            </div>
            
            <div class="form-group">
                <label for="email">Adresse email *</label>
                <input type="email" id="email" name="email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
            </div>
            
            <div class="form-group">
                <label for="mot_de_passe">Mot de passe *</label>
                <input type="password" id="mot_de_passe" name="mot_de_passe" minlength="8" required 
                       oninput="checkPasswordStrength(this.value)">
                <div id="password-strength" class="password-strength"></div>
            </div>
            
            <div class="form-group">
                <label for="confirmation_mot_de_passe">Confirmer le mot de passe *</label>
                <input type="password" id="confirmation_mot_de_passe" name="confirmation_mot_de_passe" minlength="8" required>
            </div>
            
            <div class="form-group">
                <label for="role">Rôle *</label>
                <select id="role" name="role" required>
                    <option value="user" <?= (isset($_POST['role']) && $_POST['role'] === 'user') ? 'selected' : '' ?>>Utilisateur</option>
                    <option value="admin" <?= (isset($_POST['role']) && $_POST['role'] === 'admin') ? 'selected' : '' ?>>Administrateur</option>
                </select>
            </div>
            
            <div class="form-actions">
                <button type="reset" class="btn btn-secondary">Réinitialiser</button>
                <button type="submit" class="btn">
                    <i class="fas fa-save"></i> Enregistrer l'utilisateur
                </button>
            </div>
        </form>
    </div>

    <script>
        // Vérification de la force du mot de passe
        function checkPasswordStrength(password) {
            const strengthText = document.getElementById('password-strength');
            let strength = 0;
            
            // Au moins 8 caractères
            if (password.length >= 8) strength++;
            
            // Contient des lettres minuscules
            if (password.match(/[a-z]+/)) strength++;
            
            // Contient des lettres majuscules
            if (password.match(/[A-Z]+/)) strength++;
            
            // Contient des chiffres
            if (password.match(/[0-9]+/)) strength++;
            
            // Contient des caractères spéciaux
            if (password.match(/[!@#$%^&*(),.?":{}|<>]+/)) strength++;
            
            // Déterminer le niveau de force
            let strengthLevel = '';
            if (password.length === 0) {
                strengthText.textContent = '';
                strengthText.className = 'password-strength';
                return;
            } else if (strength <= 2) {
                strengthLevel = 'Faible';
                strengthText.className = 'password-strength strength-weak';
            } else if (strength <= 4) {
                strengthLevel = 'Moyen';
                strengthText.className = 'password-strength strength-medium';
            } else {
                strengthLevel = 'Fort';
                strengthText.className = 'password-strength strength-strong';
            }
            
            strengthText.textContent = `Force du mot de passe : ${strengthLevel}`;
        }
        
        // Validation du formulaire
        document.querySelector('form').addEventListener('submit', function(e) {
            const password = document.getElementById('mot_de_passe').value;
            const confirmPassword = document.getElementById('confirmation_mot_de_passe').value;
            
            if (password !== confirmPassword) {
                e.preventDefault();
                alert('Les mots de passe ne correspondent pas.');
                return false;
            }
            
            if (password.length < 8) {
                e.preventDefault();
                alert('Le mot de passe doit contenir au moins 8 caractères.');
                return false;
            }
            
            return true;
        });
    </script>
</body>
</html>