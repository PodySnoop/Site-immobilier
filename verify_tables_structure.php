<?php
// Activer l'affichage des erreurs
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Tester la connexion à la base de données
require_once 'includes/db.php';

echo "<h2>Vérification de la structure des tables</h2>";

try {
    // 1. Vérifier les tables existantes
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    
    if (empty($tables)) {
        echo "Aucune table trouvée dans la base de données.<br>";
    } else {
        echo "<h3>Tables existantes :</h3>";
        echo "<ul>";
        foreach ($tables as $table) {
            echo "<li>" . htmlspecialchars($table) . "</li>";
        }
        echo "</ul>";
        
        // 2. Pour chaque table, afficher la structure
        foreach ($tables as $table) {
            echo "<h3>Structure de la table : " . htmlspecialchars($table) . "</h3>";
            $stmt = $pdo->query("DESCRIBE `$table`");
            echo "<pre>";
            print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
            echo "</pre>";
        }
    }
    
} catch (PDOException $e) {
    echo "Erreur : " . $e->getMessage();
}
?>
