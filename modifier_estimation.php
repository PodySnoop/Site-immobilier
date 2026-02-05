<?php
session_start();
require_once './utilisateurs/connexion.php';

// Vérification admin
if (!isset($_SESSION['utilisateur_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: utilisateurs/login.php');
    exit();
}

$id = intval($_GET['id'] ?? 0);
$stmt = $conn->prepare("SELECT * FROM estimations WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if (!$result || $result->num_rows === 0) {
    header('Location: liste_estimations.php');
    exit();
}

$estimation = $result->fetch_assoc();
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = trim($_POST['nom'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $telephone = trim($_POST['telephone'] ?? '');
    $type_annonce = $_POST['type_annonce'] ?? '';
    $adresse = trim($_POST['adresse'] ?? '');
    $surface = intval($_POST['surface'] ?? 0);
    $description = trim($_POST['description'] ?? '');
    $estimation_prix = floatval($_POST['estimation_prix'] ?? 0);
    
    // Validation
    if (empty($nom) || empty($email) || empty($surface) || empty($estimation_prix)) {
        $error = "Veuillez remplir tous les champs obligatoires.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "L'email n'est pas valide.";
    } elseif ($surface <= 0) {
        $error = "La surface doit être supérieure à 0.";
    } elseif ($estimation_prix <= 0) {
        $error = "Le prix estimé doit être supérieur à 0.";
    } else {
        // Mise à jour
        $stmt = $conn->prepare("UPDATE estimations SET nom = ?, email = ?, telephone = ?, type_annonce = ?, adresse = ?, surface = ?, description = ?, estimation_prix = ? WHERE id = ?");
        $stmt->bind_param("sssssisdi", $nom, $email, $telephone, $type_annonce, $adresse, $surface, $description, $estimation_prix, $id);
        
        if ($stmt->execute()) {
            $message = "L'estimation a été modifiée avec succès.";
            
            // Enregistrer l'activité
            $user_id = $_SESSION['utilisateur_id'];
            $action = "Estimation modifiée";
            $details = "ID de l'estimation: $id";
            $conn->query("INSERT INTO activites (utilisateur_id, action, details) VALUES ($user_id, '$action', '$details')");
            
            // Redirection après 2 secondes
            header("refresh:2;url=voir_estimation.php?id=$id");
        } else {
            $error = "Une erreur est survenue lors de la modification: " . $stmt->error;
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
    <title>Modifier estimation #<?= $estimation['id'] ?> - Pollux Immobilier</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <style>
        .modifier-estimation {
            max-width: 800px;
            margin: 30px auto;
            padding: 0 20px;
        }
        
        .form-header {
            background: linear-gradient(135deg, #f39c12 0%, #e67e22 100%);
            color: white;
            padding: 30px;
            border-radius: 10px 10px 0 0;
            position: relative;
        }
        
        .form-title {
            margin: 0;
            font-size: 2em;
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .form-subtitle {
            margin-top: 10px;
            opacity: 0.9;
            font-size: 0.9em;
        }
        
        .form-body {
            background: white;
            padding: 40px;
            border-radius: 0 0 10px 10px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 25px;
            margin-bottom: 30px;
        }
        
        .form-group {
            display: flex;
            flex-direction: column;
        }
        
        .form-group.full-width {
            grid-column: 1 / -1;
        }
        
        .form-label {
            font-weight: 600;
            color: #333;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .form-label .required {
            color: #e74c3c;
        }
        
        .form-input,
        .form-select,
        .form-textarea {
            padding: 12px 15px;
            border: 2px solid #e1e8ed;
            border-radius: 8px;
            font-size: 1em;
            transition: all 0.3s ease;
            background: #f8f9fa;
        }
        
        .form-input:focus,
        .form-select:focus,
        .form-textarea:focus {
            outline: none;
            border-color: #f39c12;
            background: white;
            box-shadow: 0 0 0 3px rgba(243, 156, 18, 0.1);
        }
        
        .form-textarea {
            min-height: 120px;
            resize: vertical;
        }
        
        .form-help {
            font-size: 0.85em;
            color: #666;
            margin-top: 5px;
        }
        
        .price-input-group {
            position: relative;
        }
        
        .price-input-group .form-input {
            padding-left: 35px;
        }
        
        .price-input-group::before {
            content: "€";
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #666;
            font-weight: 600;
        }
        
        .form-actions {
            display: flex;
            gap: 15px;
            margin-top: 30px;
            flex-wrap: wrap;
        }
        
        .btn {
            padding: 12px 25px;
            border: none;
            border-radius: 5px;
            font-weight: 500;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
            cursor: pointer;
            font-size: 1em;
        }
        
        .btn-primary {
            background: #f39c12;
            color: white;
        }
        
        .btn-primary:hover {
            background: #e67e22;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(243, 156, 18, 0.3);
        }
        
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        
        .btn-secondary:hover {
            background: #5a6268;
        }
        
        .btn-danger {
            background: #e74c3c;
            color: white;
        }
        
        .btn-danger:hover {
            background: #c0392b;
        }
        
        .message {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .message.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .message.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .estimation-preview {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
            border-left: 4px solid #f39c12;
        }
        
        .preview-title {
            font-weight: 600;
            color: #333;
            margin-bottom: 10px;
        }
        
        .preview-content {
            color: #666;
            font-size: 0.9em;
        }
        
        @media (max-width: 768px) {
            .form-header {
                padding: 20px;
            }
            
            .form-title {
                font-size: 1.5em;
            }
            
            .form-body {
                padding: 25px;
            }
            
            .form-grid {
                grid-template-columns: 1fr;
            }
            
            .form-actions {
                flex-direction: column;
            }
            
            .btn {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <?php include_once 'includes/header.php'; ?>
    
    <div class="modifier-estimation">
        <div class="form-header">
            <h1 class="form-title">
                <i class="fas fa-edit"></i>
                Modifier estimation #<?= $estimation['id'] ?>
            </h1>
            <div class="form-subtitle">
                Mettez à jour les informations de l'estimation
            </div>
        </div>
        
        <div class="form-body">
            <?php if ($message): ?>
                <div class="message success">
                    <i class="fas fa-check-circle"></i>
                    <?= htmlspecialchars($message) ?>
                </div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="message error">
                    <i class="fas fa-exclamation-circle"></i>
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>
            
            <div class="estimation-preview">
                <div class="preview-title">Informations actuelles</div>
                <div class="preview-content">
                    <strong>Demande initiale :</strong> <?= date('d/m/Y H:i', strtotime($estimation['date_demande'])) ?> | 
                    <strong>Type :</strong> <?= htmlspecialchars($estimation['type_annonce']) ?>
                </div>
            </div>
            
            <form method="POST" id="modifierForm">
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">
                            <i class="fas fa-user"></i>
                            Nom complet <span class="required">*</span>
                        </label>
                        <input type="text" name="nom" class="form-input" 
                               value="<?= htmlspecialchars($estimation['nom']) ?>" 
                               required placeholder="Nom du demandeur">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">
                            <i class="fas fa-envelope"></i>
                            Email <span class="required">*</span>
                        </label>
                        <input type="email" name="email" class="form-input" 
                               value="<?= htmlspecialchars($estimation['email']) ?>" 
                               required placeholder="email@exemple.com">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">
                            <i class="fas fa-phone"></i>
                            Téléphone
                        </label>
                        <input type="tel" name="telephone" class="form-input" 
                               value="<?= htmlspecialchars($estimation['telephone'] ?? '') ?>" 
                               placeholder="06 12 34 56 78">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">
                            <i class="fas fa-building"></i>
                            Type de bien <span class="required">*</span>
                        </label>
                        <select name="type_annonce" class="form-select" required>
                            <option value="location" <?= $estimation['type_annonce'] === 'location' ? 'selected' : '' ?>>Location</option>
                            <option value="vente" <?= $estimation['type_annonce'] === 'vente' ? 'selected' : '' ?>>Vente</option>
                        </select>
                    </div>
                    
                    <div class="form-group full-width">
                        <label class="form-label">
                            <i class="fas fa-map-marker-alt"></i>
                            Adresse <span class="required">*</span>
                        </label>
                        <input type="text" name="adresse" class="form-input" 
                               value="<?= htmlspecialchars($estimation['adresse']) ?>" 
                               required placeholder="123 rue de la République, 75001 Paris">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">
                            <i class="fas fa-ruler-combined"></i>
                            Surface (m²) <span class="required">*</span>
                        </label>
                        <input type="number" name="surface" class="form-input" 
                               value="<?= $estimation['surface'] ?>" 
                               required min="1" step="1" placeholder="85">
                        <div class="form-help">Surface habitable en mètres carrés</div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">
                            <i class="fas fa-euro-sign"></i>
                            Prix estimé (€) <span class="required">*</span>
                        </label>
                        <div class="price-input-group">
                            <input type="number" name="estimation_prix" class="form-input" 
                                   value="<?= $estimation['estimation_prix'] ?>" 
                                   required min="0" step="100" placeholder="250000">
                        </div>
                        <div class="form-help">Estimation du prix du bien en euros</div>
                    </div>
                    
                    <div class="form-group full-width">
                        <label class="form-label">
                            <i class="fas fa-align-left"></i>
                            Description
                        </label>
                        <textarea name="description" class="form-textarea" 
                                  placeholder="Description détaillée du bien..."><?= htmlspecialchars($estimation['description'] ?? '') ?></textarea>
                        <div class="form-help">Décrivez les caractéristiques principales du bien</div>
                    </div>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i>
                        Enregistrer les modifications
                    </button>
                    <a href="voir_estimation.php?id=<?= $estimation['id'] ?>" class="btn btn-secondary">
                        <i class="fas fa-times"></i>
                        Annuler
                    </a>
                    <form action="supprimer_estimation.php" method="POST" style="display: inline;">
                        <input type="hidden" name="id" value="<?= $estimation['id'] ?>">
                        <button type="submit" class="btn btn-danger" onclick="return confirm('Êtes-vous sûr de vouloir supprimer cette estimation ? Cette action est irréversible.')">
                            <i class="fas fa-trash"></i>
                            Supprimer
                        </button>
                    </form>
                </div>
            </form>
        </div>
    </div>
    
    <?php include_once 'includes/footer.php'; ?>
    
    <script>
        // Validation JavaScript
        document.getElementById('modifierForm').addEventListener('submit', function(e) {
            const surface = parseFloat(document.querySelector('input[name="surface"]').value);
            const prix = parseFloat(document.querySelector('input[name="estimation_prix"]').value);
            
            if (surface <= 0) {
                e.preventDefault();
                alert('La surface doit être supérieure à 0.');
                return false;
            }
            
            if (prix <= 0) {
                e.preventDefault();
                alert('Le prix estimé doit être supérieur à 0.');
                return false;
            }
        });
        
        // Formatage automatique du prix
        document.querySelector('input[name="estimation_prix"]').addEventListener('input', function(e) {
            let value = e.target.value.replace(/\s/g, '');
            if (!isNaN(value) && value !== '') {
                e.target.value = parseInt(value);
            }
        });
    </script>
</body>
</html>