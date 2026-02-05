<?php
// Activer l'affichage des erreurs
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Tester la connexion à la base de données
require_once 'includes/db.php';

echo "<h2>Vérification de la table 'biens'</h2>";

try {
    // 1. Vérifier si la table 'biens' existe
    $stmt = $pdo->query("SHOW TABLES LIKE 'biens'");
    if ($stmt->rowCount() === 0) {
        die("La table 'biens' n'existe pas dans la base de données.");
    }
    
    // 2. Afficher la structure de la table
    echo "<h3>Structure de la table 'biens' :</h3>";
    $stmt = $pdo->query("DESCRIBE `biens`");
    echo "<pre>";
    print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
    echo "</pre>";
    
    // 3. Afficher les 5 premières entrées
    echo "<h3>Contenu de la table 'biens' :</h3>";
    $stmt = $pdo->query("SELECT * FROM `biens` LIMIT 5");
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($results)) {
        echo "Aucune donnée trouvée dans la table 'biens'.";
    } else {
        echo "<pre>";
        print_r($results);
        echo "</pre>";
    }
    
    // 4. Vérifier si la colonne 'type' existe et quelles valeurs elle contient
    echo "<h3>Valeurs distinctes de la colonne 'type' :</h3>";
    try {
        $stmt = $pdo->query("SELECT DISTINCT `type` FROM `biens`");
        $types = $stmt->fetchAll(PDO::FETCH_COLUMN);
        echo "<pre>";
        print_r($types);
        echo "</pre>";
    } catch (PDOException $e) {
        echo "La colonne 'type' n'existe pas ou une erreur est survenue : " . $e->getMessage();
    }
    
} catch (PDOException $e) {
    echo "Erreur : " . $e->getMessage();
}
?>
