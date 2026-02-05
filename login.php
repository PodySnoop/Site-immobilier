<?php
// Activer l'affichage des erreurs
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
// Démarrer la session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Inclure la connexion à la base de données
require_once 'connexion.php';

function redirectTo($path) {
    // Journalisation
    logMessage("Redirection vers: $path");

    // Nettoyer le chemin
    $path = ltrim($path, '/');

    // Construire l'URL complète
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    $base = rtrim(dirname($_SERVER['PHP_SELF']), '/');

    $url = "$protocol://$host$base/$path";
    $url = preg_replace('/([^:])\/\//', '$1/', $url);

    // Redirection
    header("Location: $url");
    exit();
}

// Fichier de log pour le débogage
$logFile = __DIR__ . '/login_debug.log';
function logMessage($message) {
    global $logFile;
    $timestamp = date('Y-m-d H:i:s');
    $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
    $caller = isset($backtrace[1]) ? $backtrace[1]['function'] : 'main';
    file_put_contents($logFile, "[$timestamp][$caller] $message\n", FILE_APPEND);
}

@file_put_contents($logFile, "=== Nouvelle session ===\n", FILE_APPEND);

if (!empty($_COOKIE['remember_token'])) {
    logMessage("Cookie remember_token détecté");

    $token = $_COOKIE['remember_token'];
    $stmt = $conn->prepare("SELECT * FROM utilisateurs WHERE remember_token = ?");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();

    if ($user) {
        logMessage("Utilisateur trouvé via remember_token");

        session_regenerate_id(true);
        $_SESSION['utilisateur_id'] = $user['id'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['nom'] = $user['nom'];
        $_SESSION['email'] = $user['email'];

        // Rafraîchir le token
        $newToken = bin2hex(random_bytes(32));
        $update = $conn->prepare("UPDATE utilisateurs SET remember_token = ? WHERE ID = ?");
        $update->bind_param("si", $newToken, $user['ID']);
        $update->execute();

        setcookie('remember_token', $newToken, time() + (86400 * 30), "/", "", false, true);

        redirectTo($user['role'] === 'admin' ? 'admin.php' : 'profil_utilisateur.php');
    } else {
        logMessage("Token invalide → suppression cookie");
        setcookie('remember_token', '', time() - 3600, "/");
    }
}
$error_message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    logMessage("Traitement du formulaire POST");

    $email = trim($_POST['email'] ?? '');
    $mot_de_passe = $_POST['mot_de_passe'] ?? '';
    $remember = isset($_POST['remember']);

    if (empty($email) || empty($mot_de_passe)) {
        $error_message = "Veuillez remplir tous les champs.";
    } else {
        $stmt = $conn->prepare("SELECT * FROM utilisateurs WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();

        if ($user && password_verify($mot_de_passe, $user['mot_de_passe'])) {
            logMessage("Connexion réussie pour $email");

            session_regenerate_id(true);
            $_SESSION['utilisateur_id'] = $user['id'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['nom'] = $user['nom'];
            $_SESSION['email'] = $user['email'];

if ($remember) {
                $token = bin2hex(random_bytes(32));
                $update = $conn->prepare("UPDATE utilisateurs SET remember_token = ? WHERE ID = ?");
                $update->bind_param("si", $token, $user['ID']);
                $update->execute();

                setcookie('remember_token', $token, time() + (86400 * 30), "/", "", false, true);
            }

            if ($_SESSION['role'] === 'admin') {
    redirectTo('admin.php');
} else {
    redirectTo('profil_utilisateur.php');
}
        } else {
            $error_message = "Email ou mot de passe incorrect.";
            logMessage("Échec connexion pour $email");
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion - Pollux Immobilier</title>
    <link rel="stylesheet" href="../style.css">
    <link rel="stylesheet" href="../pages-common.css">
    <style>
        .login-container {
            max-width: 400px;
            margin: 50px auto;
            padding: 20px;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        .form-group input {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .error-message {
            color: red;
            margin-bottom: 15px;
            padding: 10px;
            background: #ffebee;
            border-radius: 4px;
        }
        .remember-me {
            display: flex;
            align-items: center;
            margin: 15px 0;
        }
        .remember-me input {
            margin-right: 10px;
        }
        .btn {
            width: 100%;
            padding: 10px;
            background: #4CAF50;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        .btn:hover {
            background: #45a049;
        }
    </style>
</head>
<body>
    <?php include_once '../includes/header.php'; ?>
    
    <div class="login-container">
        <h2>Connexion</h2>
        
        <?php if (!empty($error_message)): ?>
            <div class="error-message"><?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="form-group">
                <label for="email">Email :</label>
                <input type="email" id="email" name="email" required 
                       value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
            </div>
            
            <div class="form-group">
                <label for="mot_de_passe">Mot de passe :</label>
                <div style="position: relative;">
                    <input type="password" id="mot_de_passe" name="mot_de_passe" required>
                    <button type="button" id="togglePassword" style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); background: none; border: none; cursor: pointer;">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
            </div>
            
            <div class="remember-me">
                <input type="checkbox" id="remember" name="remember" value="1" 
                       <?php echo isset($_POST['remember']) ? 'checked' : ''; ?>>
                <label for="remember">Se souvenir de moi</label>
            </div>
            
            <button type="submit" class="btn">Se connecter</button>
        </form>
        
        <p style="margin-top: 20px; text-align: center;">
            Pas encore de compte ? <a href="inscription_utilisateur.php">S'inscrire</a>
        </p>
    </div>
    
     
    <?php include_once '../includes/footer.php'; ?>
    
    <script>
        // Afficher/masquer le mot de passe
        document.addEventListener('DOMContentLoaded', function() {
            const togglePassword = document.querySelector('#togglePassword');
            const password = document.querySelector('#mot_de_passe');
            
            togglePassword.addEventListener('click', function() {
                const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
                password.setAttribute('type', type);
                this.querySelector('i').classList.toggle('fa-eye');
                this.querySelector('i').classList.toggle('fa-eye-slash');
            });

            // Faire défiler automatiquement vers la section de débogage
            const debugSection = document.querySelector('div[style*="background: #f0f0f0"]');
            if (debugSection) {
                debugSection.scrollIntoView({ behavior: 'smooth' });
            }
        });
    </script>
</body>
</html>