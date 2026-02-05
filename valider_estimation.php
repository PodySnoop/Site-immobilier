<?php
session_start();
require_once '../utilisateurs/connexion.php';
require_once '../traitement_estimation.php'; // contient envoyerEstimationUtilisateur()

// Vérification du rôle admin
if (!isset($_SESSION['utilisateur']) || $_SESSION['utilisateur']['role'] !== 'admin') {
    echo "<h2>Accès interdit</h2><p>Vous devez être administrateur pour accéder à cette page.</p>";
    exit;
}

// Validation d'une estimation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['valider_id'])) {
    $id = intval($_POST['valider_id']);
    $estimation_validee = floatval($_POST['estimation_validee']);

    // Mise à jour en base
    $stmt = $conn->prepare("UPDATE estimations SET en_attente_validation = 0, estimation_validee = ? WHERE id = ?");
    $stmt->bind_param("di", $estimation_validee, $id);
    $stmt->execute();
    $stmt->close();

    // Récupération des infos utilisateur
    $result = $conn->query("SELECT nom, email FROM estimations WHERE id = $id");
    if ($result && $row = $result->fetch_assoc()) {
        envoyerEstimationUtilisateur($row['email'], $row['nom'], $estimation_validee);
        echo "<p style='color:green;'>Estimation validée et envoyée à " . htmlspecialchars($row['email']) . "</p>";
    }
}

// Affichage des estimations en attente
$result = $conn->query("SELECT * FROM estimations WHERE en_attente_validation = 1");

echo "<h2>Estimations en attente de validation</h2>";

if ($result->num_rows > 0) {
    echo "<table border='1' cellpadding='8' cellspacing='0'>";
    echo "<tr><th>ID</th><th>Nom</th><th>Email</th><th>Type</th><th>Adresse</th><th>Surface</th><th>Estimation auto</th><th>Valider</th></tr>";

    while ($row = $result->fetch_assoc()) {
        $estimation_auto = calculerEstimation($row['type_annonce'], $row['surface'], $row['adresse']);
        echo "<tr>
            <td>{$row['id']}</td>
            <td>{$row['nom']}</td>
            <td>{$row['email']}</td>
            <td>{$row['type_annonce']}</td>
            <td>{$row['adresse']}</td>
            <td>{$row['surface']} m²</td>
            <td>" . number_format($estimation_auto, 0, ',', ' ') . " €</td>
            <td>
                <form method='POST'>
                    <input type='hidden' name='valider_id' value='{$row['id']}'>
                    <input type='number' name='estimation_validee' value='$estimation_auto' step='1000' required>
                    <button type='submit'>Valider & Envoyer</button>
                </form>
            </td>
        </tr>";
    }

    echo "</table>";
} else {
    echo "<p>Aucune estimation en attente.</p>";
}
?>