<?php
// Vérification de la session et des droits d'administration
session_start();
if (!isset($_SESSION['utilisateur_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: index.php');
    exit();
}

// Inclure la connexion à la base de données
require_once 'includes/db.php';

// Traitement des actions (marquer comme lu/non lu, supprimer)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    $message_id = intval($_POST['message_id'] ?? 0);
    
    if ($message_id > 0) {
        switch ($action) {
            case 'marquer_lu':
                $stmt = $conn->prepare("UPDATE messages_contact SET lu = 1, date_lecture = NOW() WHERE id = ?");
                $stmt->bind_param("i", $message_id);
                $stmt->execute();
                $_SESSION['success_message'] = "Le message a été marqué comme lu.";
                break;
                
            case 'marquer_non_lu':
                $stmt = $conn->prepare("UPDATE messages_contact SET lu = 0, date_lecture = NULL WHERE id = ?");
                $stmt->bind_param("i", $message_id);
                $stmt->execute();
                $_SESSION['success_message'] = "Le message a été marqué comme non lu.";
                break;
                
            case 'supprimer':
                $stmt = $conn->prepare("DELETE FROM messages_contact WHERE id = ?");
                $stmt->bind_param("i", $message_id);
                if ($stmt->execute()) {
                    $_SESSION['success_message'] = "Le message a été supprimé avec succès.";
                } else {
                    $_SESSION['error_message'] = "Une erreur est survenue lors de la suppression du message.";
                }
                break;
        }
        
        // Rediriger pour éviter la soumission multiple du formulaire
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit();
    }
}

// Récupérer les messages
$where_conditions = [];
$params = [];
$types = '';

// Filtres
$filtre_lu = $_GET['filtre_lu'] ?? '';
$filtre_date = $_GET['filtre_date'] ?? '';
$filtre_bien = intval($_GET['filtre_bien'] ?? 0);

if ($filtre_lu !== '') {
    $where_conditions[] = "m.lu = ?";
    $params[] = $filtre_lu;
    $types .= 'i';
}

if ($filtre_date === 'aujourdhui') {
    $where_conditions[] = "DATE(m.date_creation) = CURDATE()";
} elseif ($filtre_date === 'cette_semaine') {
    $where_conditions[] = "YEARWEEK(m.date_creation, 1) = YEARWEEK(CURDATE(), 1)";
} elseif ($filtre_date === 'ce_mois') {
    $where_conditions[] = "YEAR(m.date_creation) = YEAR(CURDATE()) AND MONTH(m.date_creation) = MONTH(CURDATE())";
}

if ($filtre_bien > 0) {
    $where_conditions[] = "m.bien_id = ?";
    $params[] = $filtre_bien;
    $types .= 'i';
}

// Construction de la requête
$query = "
    SELECT m.*, 
           b.titre as bien_titre,
           b.reference as bien_reference,
           b.type_offre
    FROM messages_contact m
    LEFT JOIN biens b ON m.bien_id = b.id
";

if (!empty($where_conditions)) {
    $query .= " WHERE " . implode(" AND ", $where_conditions);
}

$query .= " ORDER BY m.date_creation DESC";

// Exécution de la requête
$stmt = $conn->prepare($query);

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();
$messages = $result->fetch_all(MYSQLI_ASSOC);

// Récupérer la liste des biens pour le filtre
$biens = [];
$biens_result = $conn->query("SELECT id, titre, reference FROM biens WHERE est_valide = 1 ORDER BY titre");
if ($biens_result) {
    $biens = $biens_result->fetch_all(MYSQLI_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des messages - Administration</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="pages-common.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        .container {
            max-width: 1200px;
            margin: 20px auto;
            padding: 0 15px;
        }
        
        .filters {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            align-items: flex-end;
        }
        
        .filter-group {
            margin-right: 15px;
        }
        
        .filter-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
        }
        
        .filter-group select, 
        .filter-group input[type="text"] {
            padding: 8px 12px;
            border-radius: 4px;
            border: 1px solid #ced4da;
            min-width: 200px;
        }
        
        .btn {
            padding: 8px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            font-size: 0.9em;
        }
        
        .btn-primary {
            background: #3498db;
            color: white;
        }
        
        .btn-success {
            background: #28a745;
            color: white;
        }
        
        .btn-warning {
            background: #ffc107;
            color: #212529;
        }
        
        .btn-danger {
            background: #dc3545;
            color: white;
        }
        
        .btn-sm {
            padding: 4px 8px;
            font-size: 0.8em;
        }
        
        .message-card {
            background: white;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            margin-bottom: 15px;
            overflow: hidden;
        }
        
        .message-header {
            background: #f8f9fa;
            padding: 12px 15px;
            border-bottom: 1px solid #dee2e6;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 10px;
        }
        
        .message-header h3 {
            margin: 0;
            font-size: 1.1em;
            color: #2c3e50;
        }
        
        .message-meta {
            display: flex;
            gap: 15px;
            font-size: 0.9em;
            color: #6c757d;
            flex-wrap: wrap;
        }
        
        .message-body {
            padding: 15px;
        }
        
        .message-text {
            white-space: pre-wrap;
            line-height: 1.6;
            margin-bottom: 15px;
        }
        
        .message-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid #eee;
        }
        
        .badge {
            display: inline-block;
            padding: 3px 7px;
            border-radius: 10px;
            font-size: 0.8em;
            font-weight: bold;
        }
        
        .badge-success {
            background: #28a745;
            color: white;
        }
        
        .badge-warning {
            background: #ffc107;
            color: #212529;
        }
        
        .no-messages {
            text-align: center;
            padding: 40px 20px;
            color: #6c757d;
            background: #f8f9fa;
            border-radius: 8px;
        }
        
        .unread {
            background: #e9f7fe;
        }
        
        .unread .message-header {
            background: #e3f2fd;
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container">
        <h1>Gestion des messages</h1>
        
        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="alert alert-success">
                <?php 
                echo htmlspecialchars($_SESSION['success_message']); 
                unset($_SESSION['success_message']);
                ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="alert alert-danger">
                <?php 
                echo htmlspecialchars($_SESSION['error_message']); 
                unset($_SESSION['error_message']);
                ?>
            </div>
        <?php endif; ?>
        
        <!-- Filtres -->
        <form method="get" class="filters">
            <div class="filter-group">
                <label for="filtre_lu">Statut</label>
                <select name="filtre_lu" id="filtre_lu">
                    <option value="">Tous les messages</option>
                    <option value="0" <?php echo $filtre_lu === '0' ? 'selected' : ''; ?>>Non lus</option>
                    <option value="1" <?php echo $filtre_lu === '1' ? 'selected' : ''; ?>>Lus</option>
                </select>
            </div>
            
            <div class="filter-group">
                <label for="filtre_date">Période</label>
                <select name="filtre_date" id="filtre_date">
                    <option value="">Toutes les périodes</option>
                    <option value="aujourdhui" <?php echo $filtre_date === 'aujourdhui' ? 'selected' : ''; ?>>Aujourd'hui</option>
                    <option value="cette_semaine" <?php echo $filtre_date === 'cette_semaine' ? 'selected' : ''; ?>>Cette semaine</option>
                    <option value="ce_mois" <?php echo $filtre_date === 'ce_mois' ? 'selected' : ''; ?>>Ce mois-ci</option>
                </select>
            </div>
            
            <div class="filter-group">
                <label for="filtre_bien">Bien</label>
                <select name="filtre_bien" id="filtre_bien">
                    <option value="0">Tous les biens</option>
                    <?php foreach ($biens as $bien): ?>
                        <option value="<?php echo $bien['id']; ?>" <?php echo $filtre_bien == $bien['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($bien['reference'] . ' - ' . $bien['titre']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-filter"></i> Filtrer
            </button>
            
            <a href="admin_messages.php" class="btn btn-warning">
                <i class="fas fa-redo"></i> Réinitialiser
            </a>
        </form>
        
        <?php if (empty($messages)): ?>
            <div class="no-messages">
                <p><i class="fas fa-inbox" style="font-size: 3em; opacity: 0.5; margin-bottom: 15px;"></i></p>
                <h3>Aucun message trouvé</h3>
                <p>Aucun message ne correspond à vos critères de recherche.</p>
            </div>
        <?php else: ?>
            <div class="messages-list">
                <?php foreach ($messages as $msg): ?>
                    <div class="message-card <?php echo !$msg['lu'] ? 'unread' : ''; ?>" id="message-<?php echo $msg['id']; ?>">
                        <div class="message-header">
                            <h3>
                                <?php if (!$msg['lu']): ?>
                                    <span class="badge badge-warning">Nouveau</span>
                                <?php endif; ?>
                                <?php echo htmlspecialchars($msg['nom']); ?>
                                <small><?php echo htmlspecialchars($msg['email']); ?></small>
                            </h3>
                            <div class="message-meta">
                                <?php if ($msg['bien_id']): ?>
                                    <span>
                                        <i class="fas fa-home"></i> 
                                        <a href="bien-details.php?id=<?php echo $msg['bien_id']; ?>">
                                            <?php echo htmlspecialchars($msg['bien_reference'] . ' - ' . $msg['bien_titre']); ?>
                                        </a>
                                        (<?php echo $msg['type_offre'] === 'location' ? 'Location' : 'Vente'; ?>)
                                    </span>
                                <?php endif; ?>
                                <span><i class="far fa-calendar-alt"></i> <?php echo date('d/m/Y H:i', strtotime($msg['date_creation'])); ?></span>
                                <?php if ($msg['telephone']): ?>
                                    <span><i class="fas fa-phone"></i> <?php echo htmlspecialchars($msg['telephone']); ?></span>
                                <?php endif; ?>
                                <span><i class="fas fa-laptop"></i> <?php echo htmlspecialchars($msg['ip']); ?></span>
                                <?php if ($msg['lu'] && $msg['date_lecture']): ?>
                                    <span><i class="far fa-eye"></i> Lu le <?php echo date('d/m/Y H:i', strtotime($msg['date_lecture'])); ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="message-body">
                            <div class="message-text"><?php echo nl2br(htmlspecialchars($msg['message'])); ?></div>
                            
                            <div class="message-actions">
                                <?php if ($msg['lu']): ?>
                                    <form method="post" style="display: inline;">
                                        <input type="hidden" name="message_id" value="<?php echo $msg['id']; ?>">
                                        <input type="hidden" name="action" value="marquer_non_lu">
                                        <button type="submit" class="btn btn-warning btn-sm">
                                            <i class="far fa-envelope"></i> Marquer comme non lu
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <form method="post" style="display: inline;">
                                        <input type="hidden" name="message_id" value="<?php echo $msg['id']; ?>">
                                        <input type="hidden" name="action" value="marquer_lu">
                                        <button type="submit" class="btn btn-success btn-sm">
                                            <i class="far fa-envelope-open"></i> Marquer comme lu
                                        </button>
                                    </form>
                                <?php endif; ?>
                                
                                <a href="mailto:<?php echo htmlspecialchars($msg['email']); ?>?subject=RE: <?php echo rawurlencode($msg['bien_titre'] ?? 'Votre message'); ?>" class="btn btn-primary btn-sm">
                                    <i class="fas fa-reply"></i> Répondre
                                </a>
                                
                                <form method="post" style="display: inline;" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer ce message ? Cette action est irréversible.');">
                                    <input type="hidden" name="message_id" value="<?php echo $msg['id']; ?>">
                                    <input type="hidden" name="action" value="supprimer">
                                    <button type="submit" class="btn btn-danger btn-sm">
                                        <i class="far fa-trash-alt"></i> Supprimer
                                    </button>
                                </form>
                                
                                <?php if ($msg['bien_id']): ?>
                                    <a href="bien-details.php?id=<?php echo $msg['bien_id']; ?>" class="btn btn-primary btn-sm">
                                        <i class="fas fa-home"></i> Voir le bien
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    
    <?php include 'includes/footer.php'; ?>
    
    <script>
        // Marquer automatiquement le message comme lu quand il est affiché
        document.addEventListener('DOMContentLoaded', function() {
            // Si on a un hash dans l'URL (ex: #message-123)
            if (window.location.hash) {
                const messageId = window.location.hash.substring(1); // Enlève le #
                const messageElement = document.getElementById(messageId);
                
                if (messageElement && messageElement.classList.contains('unread')) {
                    // Trouver le formulaire de marquage comme lu et le soumettre
                    const form = messageElement.querySelector('form input[name="action"][value="marquer_lu"]')?.closest('form');
                    if (form) {
                        form.submit();
                    }
                }
            }
            
            // Confirmation avant suppression
            const deleteForms = document.querySelectorAll('form[action*="supprimer"]');
            deleteForms.forEach(form => {
                form.addEventListener('submit', function(e) {
                    if (!confirm('Êtes-vous sûr de vouloir supprimer ce message ? Cette action est irréversible.')) {
                        e.preventDefault();
                    }
                });
            });
        });
    </script>
</body>
</html>
