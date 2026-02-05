<?php
session_start();

// Vérifier que l'utilisateur est connecté et est un admin
if (!isset($_SESSION['utilisateur_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// Après la vérification de session et avant la définition de $_SESSION['utilisateur']
require_once '../includes/db.php';

// Récupérer les informations de l'utilisateur connecté
$utilisateur_id = $_SESSION['utilisateur_id'];
$utilisateur_query = $conn->prepare("SELECT email, is_super_admin FROM utilisateurs WHERE id = ?");
$utilisateur_query->bind_param("i", $utilisateur_id);
$utilisateur_query->execute();
$utilisateur_result = $utilisateur_query->get_result();
$utilisateur_data = $utilisateur_result->fetch_assoc();

// Vérifier si l'utilisateur est super admin
$is_super_admin = ($utilisateur_data && isset($utilisateur_data['is_super_admin']) && $utilisateur_data['is_super_admin'] == 1);

// Si l'utilisateur n'est pas super admin et tente d'accéder à une page réservée aux super admins
if (strpos($_SERVER['REQUEST_URI'], 'gestion_admins.php') !== false && !$is_super_admin) {
    header("Location: admin.php?error=unauthorized");
    exit();
}

// Récupérer les statistiques
$stats = [
    'utilisateurs' => 0,
    'propriete' => 0,
    'reservations' => 0,
    'commentaires' => 0
];

// Compter les utilisateurs
$result = $conn->query("SELECT COUNT(*) as count FROM utilisateurs");
if ($result) {
    $stats['users'] = $result->fetch_assoc()['count'];
}

// Compter les biens immobiliers
$stats['properties'] = 0;

// Compter les biens en location
$result_location = $conn->query("SELECT COUNT(*) as count FROM `biens_en_location`");
if ($result_location) {
    $stats['properties'] += $result_location->fetch_assoc()['count'];
}

// Compter les biens à vendre
$result_vente = $conn->query("SELECT COUNT(*) as count FROM `biens_à_vendre`");
if ($result_vente) {
    $stats['properties'] += $result_vente->fetch_assoc()['count'];
}

// Compter les réservations
$result = $conn->query("SELECT COUNT(*) as count FROM reservation_visite");
if ($result) {
    $stats['reservations'] = $result->fetch_assoc()['count'];
}

// Compter les commentaires
$result = $conn->query("SELECT COUNT(*) as count FROM commentaires");
if ($result) {
    $stats['commentaires'] = $result->fetch_assoc()['count'];
}
// Ajouter l'information super admin à la session
$_SESSION['utilisateur'] = [
    'id' => $_SESSION['utilisateur_id'],
    'role' => $_SESSION['role'],
    'is_super_admin' => $is_super_admin,
    'email' => $utilisateur_data['email']
];

// Compter les estimations
$result = $conn->query("SELECT COUNT(*) as count FROM estimations");
if ($result) {
    $stats['estimations'] = $result->fetch_assoc()['count'];
}
// Section gestion des messages
$result = $conn->query("SELECT COUNT(*) as count FROM contact_messages");
if ($result) {
    $stats['messages'] = $result->fetch_assoc()['count'];
}

// Ajoutez cette variable pour suivre le nombre de messages non lus
$result = $conn->query("SELECT COUNT(*) as count FROM contact_messages WHERE is_read = 0");
$unread_messages = $result ? $result->fetch_assoc()['count'] : 0;
// Définir le titre de la page
$pageTitle = 'Tableau de bord Administrateur - Pollux Immobilier';

// Traitement de la validation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['valider_id']) && isset($_POST['type_bien'])) {
    $id = intval($_POST['valider_id']);
    $type_bien = $_POST['type_bien'] === 'location' ? 'biens_en_location' : 'biens_à_vendre';
    $sql = "UPDATE `$type_bien` SET est_valide = 1 WHERE id = $id";
    $conn->query($sql);
}

// Récupération des biens non validés
$query = "SELECT * FROM biens_en_location WHERE est_valide = 0 ORDER BY date_creation DESC";
$result = $conn->query($query);

// Inclure l'en-tête
include_once '../includes/header.php';
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
<div class="page-title">
    <div class="container">
        <h1>Tableau de bord Administrateur</h1>
        <p>Gérez l'ensemble du site et ses utilisateurs</p>
    </div>
</div>

<div class="container">
    <div class="admin-stats">
        <div class="stat-card">
            <h3>Utilisateurs</h3>
            <div class="stat-number"><?php echo htmlspecialchars($stats['users']); ?></div>
            <a href="liste_utilisateurs.php" class="btn">Voir les utilisateurs</a>
        </div>
        <div class="stat-card">
            <h3>Biens immobiliers</h3>
            <div class="stat-number"><?php echo htmlspecialchars($stats['properties']); ?></div>
            <a href="liste_biensadmin.php" class="btn">Voir les biens</a>
        </div>
        <div class="stat-card">
            <h3>Réservations</h3>
            <div class="stat-number"><?php echo htmlspecialchars($stats['reservations']); ?></div>
            <a href="liste_reservation.php" class="btn">Voir les réservations</a>
        </div>
        <div class="stat-card">
            <h3>Commentaires</h3>
            <div class="stat-number"><?php echo htmlspecialchars($stats['commentaires']); ?></div>
            <a href="liste_commentaire.php" class="btn">Voir les commentaires</a>
        </div>
        <div class="stat-card">
    <h3>Messages</h3>
    <div class="stat-number">
        <?php echo htmlspecialchars($stats['messages']); ?>
        <?php if ($unread_messages > 0): ?>
            <span class="badge"><?php echo $unread_messages; ?> non lus</span>
        <?php endif; ?>
    </div>
    <a href="contact.php" class="btn">Voir les messages</a>
</div>
    </div>
<div class="stat-card">
            <h3>Estimations</h3>
            <div class="stat-number"><?php echo htmlspecialchars($stats['estimations']); ?></div>
            <a href="/Pollux_immobilier/liste_estimations.php" class="btn">Voir les estimations</a>
        </div>
    <div class="admin-actions">
        <h2>Actions rapides</h2>
        <div class="action-buttons">
            <a href="ajouter_bien.php" class="btn">Ajouter un bien</a>
            <a href="ajouter_utilisateur.php" class="btn">Ajouter un utilisateur</a>
            <?php if ($is_super_admin): ?>
                <a href="gestion_admin.php" class="btn">Gérer les administrateurs</a>
            <?php endif; ?>
        </div>
    </div>

<?php
// Réutiliser la connexion existante
$mysqli = $conn;

// Requête pour récupérer les activités
$query = "SELECT date_activite, utilisateur_id, action, details FROM activites ORDER BY date_activite DESC LIMIT 20";
$result = $mysqli->query($query);

if ($result && $result->num_rows > 0) {
    echo '<div class="recent-activity">';
    echo '<h2>Activité récente</h2>';
    echo '<table class="activity-table">';
    echo '<thead>
            <tr>
                <th>Date</th>
                <th>Utilisateur</th>
                <th>Action</th>
                <th>Détails</th>
            </tr>
          </thead>';
    echo '<tbody>';

    while ($row = $result->fetch_assoc()) {
        echo '<tr>';
        echo '<td>' . date('d/m/Y H:i', strtotime($row['date_activite'])) . '</td>';
        echo '<td>' . htmlspecialchars("ID #" . $row['utilisateur_id']) . '</td>';
        echo '<td>' . htmlspecialchars($row['action']) . '</td>';
        echo '<td>' . htmlspecialchars($row['details']) . '</td>';
        echo '</tr>';
    }

    echo '</tbody>';
    echo '</table>';
    echo '</div>';
} else {
    echo '<p>Aucune activité enregistrée.</p>';
}
?>

<style>
.admin-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin: 20px 0;
}

.stat-card {
    background: white;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    text-align: center;
}

.stat-card h3 {
    color: #117c84;
    margin-bottom: 10px;
}

.stat-number {
    font-size: 2.5em;
    font-weight: bold;
    color: #2c3e50;
    margin: 15px 0;
}

.stat-card {
    background: white;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    text-align: center;
    transition: transform 0.3s ease;
}

.stat-card:hover {
    transform: translateY(-5px);
}

.stat-number {
    font-size: 2.5em;
    font-weight: bold;
    color: #117c84;
    margin: 10px 0;
}

.badge {
    background-color: #dc3545;
    color: white;
    border-radius: 10px;
    padding: 2px 6px;
    font-size: 0.7em;
    margin-left: 5px;
}

.btn {
    display: inline-block;
    background: #117c84;
    color: white;
    padding: 8px 15px;
    border-radius: 4px;
    text-decoration: none;
    transition: background 0.3s ease;
}

.btn:hover {
    background: #0d656b;
    color: white;
    text-decoration: none;
}

/* Style pour les tableaux */
.table {
    width: 100%;
    border-collapse: collapse;
    margin: 20px 0;
}

.table th, .table td {
    padding: 12px;
    text-align: left;
    border-bottom: 1px solid #ddd;
}

.table th {
    background-color: #117c84;
    color: white;
}

.table tr:hover {
    background-color: #f5f5f5;
}

/* Style pour les boutons d'action */
.btn-sm {
    padding: 3px 8px;
    font-size: 0.8em;
}

.btn-primary {
    background: #117c84;
}

.btn-danger {
    background: #dc3545;
}

.btn-info {
    background: #17a2b8;
}

/* Style pour les badges */
.badge {
    display: inline-block;
    padding: 3px 7px;
    font-size: 12px;
    font-weight: 700;
    line-height: 1;
    text-align: center;
    white-space: nowrap;
    vertical-align: middle;
    border-radius: 10px;
}

.bg-success {
    background-color: #28a745 !important;
}

.bg-warning {
    background-color: #ffc107 !important;
    color: #212529 !important;
}

/* Style pour les messages non lus */
.fw-bold {
    font-weight: bold;
}

/* Style pour les alertes */
.alert {
    padding: 15px;
    margin-bottom: 20px;
    border: 1px solid transparent;
    border-radius: 4px;
}

.alert-success {
    color: #3c763d;
    background-color: #dff0d8;
    border-color: #d6e9c6;
}

/* Responsive design */
@media (max-width: 768px) {
    .admin-stats {
        grid-template-columns: 1fr;
    }
    
    .table {
        display: block;
        overflow-x: auto;
    }
}
.admin-actions {
    margin: 40px 0;
    padding: 20px;
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
}

.action-buttons {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
    margin-top: 15px;
}

.btn {
    display: inline-block;
    padding: 10px 20px;
    background: #117c84;
    color: white;
    text-decoration: none;
    border-radius: 4px;
    transition: background 0.3s;
    border: none;
    cursor: pointer;
}

.btn:hover {
    background: #0e6a70;
}

.btn-danger {
    background: #dc3545;
}

.btn-danger:hover {
    background: #c82333;
}

.recent-activity {
    background: white;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    margin-top: 20px;
}

.activity-table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 15px;
}

.activity-table th,
.activity-table td {
    padding: 12px 15px;
    text-align: left;
    border-bottom: 1px solid #eee;
}

.activity-table th {
    background-color: #f8f9fa;
    font-weight: 600;
    color: #117c84;
}

.activity-table tr:hover {
    background-color: #f8f9fa;
}
/*Validation biens*/
body { font-family: Arial, sans-serif; margin: 40px; }
        .bien { border: 1px solid #ccc; padding: 20px; margin-bottom: 20px; }
        .bien h3 { margin-top: 0; }
        button { background-color: #28a745; color: white; border: none; padding: 10px 15px; cursor: pointer; }
        button:hover { background-color: #218838; }
</style>

<h1>🔐 Interface d'administration</h1>
<h2>Biens à valider</h2>

<?php
function safe($array, $key, $suffix = '') {
    return isset($array[$key]) && $array[$key] !== null
        ? htmlspecialchars($array[$key]) . $suffix
        : "<em>Non renseigné</em>";
}

function getBiens($conn, $table) {
    $biens = array();
    $query = "SELECT * FROM `$table` WHERE est_valide = 0 ORDER BY date_creation DESC";

    if ($result = $conn->query($query)) {
        while ($row = $result->fetch_assoc()) {
            $biens[] = $row;
        }
        $result->free();
    }
    return $biens;
}

$biens_location = getBiens($conn, 'biens_en_location');
$biens_vente = getBiens($conn, 'biens_à_vendre');


if (!empty($biens_location)) {
    foreach ($biens_location as $bien) {
        echo "<div class='bien'>";
        echo "<h3>" . safe($bien, 'titre') . "</h3>";
        echo "<p><strong>Description :</strong> " . safe($bien, 'description') . "</p>";
        echo "<p><strong>Localisation :</strong> " . safe($bien, 'localisation') . "</p>";
        echo "<p><strong>Surface :</strong> " . safe($bien, 'surface', ' m²') . "</p>";
        echo afficherMontant($bien, 'Loyer');
        echo "</div>";
        echo "<form method='post'>
                <input type='hidden' name='valider_id' value='" . htmlspecialchars($bien['ID']) . "'>
                <input type='hidden' name='type_bien' value='location'>
                <button type='submit'>✅ Valider ce bien</button>
              </form>";
        echo "</div>";
    }
}

if (!empty($biens_vente)) {
    foreach ($biens_vente as $bien) {
        echo "<div class='bien'>";
        echo "<h3>" . safe($bien, 'titre') . "</h3>";
        echo "<p><strong>Description :</strong> " . safe($bien, 'description') . "</p>";
        echo "<p><strong>Localisation :</strong> " . safe($bien, 'localisation') . "</p>";
        echo "<p><strong>Surface :</strong> " . safe($bien, 'surface', ' m²') . "</p>";
        echo afficherMontant($bien, 'Prix');
        echo "</div>";
        echo "<form method='post'>
                <input type='hidden' name='valider_id' value='" . htmlspecialchars($bien['ID']) . "'>
                <input type='hidden' name='type_bien' value='vente'>
                <button type='submit'>✅ Valider ce bien</button>
              </form>";
        echo "</div>";
    }
}else {
    echo "<p>Aucun bien à valider pour le moment.</p>";
}


function afficherMontant($bien, $label = 'Prix') {
    $montant = $bien['prix'] ?? null;
    return "<p><strong>$label :</strong> " . 
           (!empty($montant) ? htmlspecialchars($montant) . " €" : "<em>Non renseigné</em>") . "</p>";
}
 

$conn->close();
?>
</body>
</html>

<?php include_once '../includes/footer.php'; ?>