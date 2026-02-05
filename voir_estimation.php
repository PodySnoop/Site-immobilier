<?php
session_start();
$message = '';
$error = '';
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
// Vérifier si l'estimation est déjà validée
$est_validee = !empty($estimation['date_validation']) && !empty($estimation['validateur_id']);

// Traitement de la validation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'valider') {
    if (!$est_validee) {
        // Valider l'estimation
        $stmt = $conn->prepare("UPDATE estimations SET est_validee = 1, date_validation = NOW(), validateur_id = ? WHERE id = ?");
        $stmt->bind_param("ii", $_SESSION['utilisateur_id'], $id);
        
        if ($stmt->execute()) {
            $message = "L'estimation a été validée avec succès.";
            $est_validee = true;
            
            // Enregistrer l'activité
            $utilisateur_id = $_SESSION['utilisateur_id'];
            $action = "Estimation validée";
            $details = "ID de l'estimation: $id";
            $conn->query("INSERT INTO activites (utilisateur_id, action, details) VALUES (?, ?, ?)", $utilisateur_id, $action, $details);
            bind_param("iss", $utilisateur_id, $action, $details);
            execute();
            
            // Rafraîchir les données de l'estimation
            $stmt_refresh = $conn->prepare("SELECT * FROM estimations WHERE id = ?");
            $stmt_refresh->bind_param("i", $id);
            $stmt_refresh->execute();
            $result_refresh = $stmt_refresh->get_result();
            $estimation = $result_refresh->fetch_assoc();
            
            // Redirection après 2 secondes
            header("refresh:2;url=voir_estimation.php?id=$id");
        } else {
            $error = "Erreur lors de la validation: " . $stmt->error;
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
    <title>Détails de l'estimation #<?= $estimation['id'] ?> - Pollux Immobilier</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <style>
        .estimation-detail {
            max-width: 800px;
            margin: 30px auto;
            padding: 0 20px;
        }
        
        .estimation-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            border-radius: 10px 10px 0 0;
            position: relative;
        }
        
        .estimation-title {
            margin: 0;
            font-size: 2em;
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .estimation-meta {
            margin-top: 15px;
            opacity: 0.9;
            font-size: 0.9em;
        }
        
        .estimation-body {
            background: white;
            padding: 30px;
            border-radius: 0 0 10px 10px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 25px;
            margin-bottom: 30px;
        }
        
        .info-item {
            display: flex;
            align-items: flex-start;
            gap: 15px;
        }
        
        .info-icon {
            width: 40px;
            height: 40px;
            background: #f8f9fa;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #667eea;
            flex-shrink: 0;
        }
        
        .info-content {
            flex: 1;
        }
        
        .info-label {
            font-weight: 600;
            color: #666;
            margin-bottom: 5px;
            font-size: 0.9em;
        }
        
        .info-value {
            color: #333;
            font-size: 1.1em;
        }
        
        .description-section {
            background: #f8f9fa;
            padding: 25px;
            border-radius: 8px;
            margin: 30px 0;
        }
        
        .description-title {
            font-weight: 600;
            color: #333;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .description-text {
            line-height: 1.6;
            color: #555;
        }
        
        .estimation-price {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
            margin: 30px 0;
        }
        
        .price-label {
            font-size: 0.9em;
            opacity: 0.9;
            margin-bottom: 5px;
        }
        
        .price-value {
            font-size: 2.5em;
            font-weight: bold;
        }
        
        .actions {
            display: flex;
            gap: 15px;
            margin-top: 30px;
            flex-wrap: wrap;
        }
        
        .btn {
            padding: 12px 25px;
            border: none;
            border-radius: 5px;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn-primary {
            background: #3498db;
            color: white;
        }
        
        .btn-primary:hover {
            background: #2980b9;
            transform: translateY(-2px);
        }
        
        .btn-warning {
            background: #f39c12;
            color: white;
        }
        
        .btn-warning:hover {
            background: #e67e22;
        }
        
        .btn-danger {
            background: #e74c3c;
            color: white;
        }
        
        .btn-danger:hover {
            background: #c0392b;
        }
        
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        
        .btn-secondary:hover {
            background: #5a6268;
        }
        
        @media (max-width: 768px) {
            .estimation-header {
                padding: 20px;
            }
            
            .estimation-title {
                font-size: 1.5em;
            }
            
            .info-grid {
                grid-template-columns: 1fr;
            }
            
            .actions {
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
    
        <div class="estimation-detail">
        <?php if ($message): ?>
            <div class="message success" style="margin: 20px 0; padding: 15px; border-radius: 8px; background: #d4edda; color: #155724; border: 1px solid #c3e6cb;">
                <i class="fas fa-check-circle"></i>
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="message error" style="margin: 20px 0; padding: 15px; border-radius: 8px; background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb;">
                <i class="fas fa-exclamation-circle"></i>
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>
        
        <div class="estimation-header">
        <div class="estimation-body">
            <div class="info-grid">
                <div class="info-item">
                    <div class="info-icon">
                        <i class="fas fa-user"></i>
                    </div>
                    <div class="info-content">
                        <div class="info-label">Nom complet</div>
                        <div class="info-value"><?= htmlspecialchars($estimation['nom']) ?></div>
                    </div>
                </div>
                
                <div class="info-item">
                    <div class="info-icon">
                        <i class="fas fa-envelope"></i>
                    </div>
                    <div class="info-content">
                        <div class="info-label">Email</div>
                        <div class="info-value"><?= htmlspecialchars($estimation['email']) ?></div>
                    </div>
                </div>
                
                <div class="info-item">
                    <div class="info-icon">
                        <i class="fas fa-building"></i>
                    </div>
                    <div class="info-content">
                        <div class="info-label">Type de bien</div>
                        <div class="info-value"><?= htmlspecialchars($estimation['type_annonce']) ?></div>
                    </div>
                </div>
                
                <div class="info-item">
                    <div class="info-icon">
                        <i class="fas fa-map-marker-alt"></i>
                    </div>
                    <div class="info-content">
                        <div class="info-label">Adresse</div>
                        <div class="info-value"><?= htmlspecialchars($estimation['adresse']) ?></div>
                    </div>
                </div>
                
                <div class="info-item">
                    <div class="info-icon">
                        <i class="fas fa-ruler-combined"></i>
                    </div>
                    <div class="info-content">
                        <div class="info-label">Surface</div>
                        <div class="info-value"><?= number_format($estimation['surface'], 0, ',', ' ') ?> m²</div>
                    </div>
                </div>
                
                <div class="info-item">
                    <div class="info-icon">
                        <i class="fas fa-phone"></i>
                    </div>
                    <div class="info-content">
                        <div class="info-label">Téléphone</div>
                        <div class="info-value"><?= htmlspecialchars($estimation['telephone'] ?? 'Non renseigné') ?></div>
                    </div>
                </div>
            </div>
            
            <div class="estimation-price">
                <div class="price-label">Estimation du bien</div>
                <div class="price-value">
                    <?= number_format($estimation['estimation_prix'], 0, ',', ' ') ?> €
                </div>
            </div>
            
                        </div>
            
            <!-- Section de validation -->
            <div class="validation-section">
                <div class="validation-header">
                    <h3 class="validation-title">
                        <i class="fas fa-clipboard-check"></i>
                        Statut de validation
                    </h3>
                </div>
                <div class="validation-content">
                    <?php if ($est_validee): ?>
                        <div class="validation-card validated">
                            <div class="validation-icon">
                                <i class="fas fa-check-circle"></i>
                            </div>
                            <div class="validation-info">
                                <h4>Estimation validée</h4>
                                <div class="validation-details">
                                    <p><i class="fas fa-calendar"></i> <?= date('d/m/Y H:i', strtotime($estimation['date_validation'])) ?></p>
                                    <p><i class="fas fa-user-check"></i> Validée par l'administrateur</p>
                                </div>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="validation-card pending">
                            <div class="validation-icon">
                                <i class="fas fa-clock"></i>
                            </div>
                            <div class="validation-info">
                                <h4>En attente de validation</h4>
                                <p>Cette estimation n'a pas encore été validée par un administrateur.</p>
                                <form method="POST" style="margin-top: 15px;">
                                    <input type="hidden" name="action" value="valider">
                                    <button type="submit" class="btn btn-success" 
                                            onclick="return confirm('Êtes-vous sûr de vouloir valider cette estimation ?')">
                                        <i class="fas fa-check"></i>
                                        Valider cette estimation
                                    </button>
                                </form>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
          
            <?php if (!empty($estimation['description'])): ?>
            <div class="description-section">
                <h3 class="description-title">
                    <i class="fas fa-align-left"></i>
                    Description du bien
                </h3>
                <div class="description-text">
                    <?= nl2br(htmlspecialchars($estimation['description'])) ?>
                </div>
            </div>
            <?php endif; ?>
            
            <div class="actions">
                <a href="liste_estimations.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i>
                    Retour à la liste
                </a>
                <a href="modifier_estimation.php?id=<?= $estimation['id'] ?>" class="btn btn-warning">
                    <i class="fas fa-edit"></i>
                    Modifier
                </a>
                <form action="supprimer_estimation.php" method="POST" style="display: inline;">
                    <input type="hidden" name="id" value="<?= $estimation['id'] ?>">
                    <button type="submit" class="btn btn-danger" onclick="return confirm('Êtes-vous sûr de vouloir supprimer cette estimation ?')">
                        <i class="fas fa-trash"></i>
                        Supprimer
                    </button>
                </form>
            </div>
        </div>
    </div>
    
    <?php include_once 'includes/footer.php'; ?>
</body>
</html>