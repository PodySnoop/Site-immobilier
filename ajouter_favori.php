<?php
session_start();
require_once 'includes/db.php';

if (isset($_POST['annonce_id'], $_POST['type_annonce']) && isset($_SESSION['utilisateur_id'])) {
    $annonce_id = intval($_POST['annonce_id']);
    $type_annonce = $_POST['type_annonce'];
    $utilisateur_id = $_SESSION['utilisateur_id'];

    if (in_array($type_annonce, ['location', 'vente'])) {
        // Déterminer la table source
        $table = ($type_annonce === 'location') ? 'biens_en_location' : 'biens_à_vendre';

        // Récupérer les infos du bien
        $stmt_bien = $conn->prepare("SELECT titre, description, image, localisation FROM `$table` WHERE id = ?");
        if ($stmt_bien) {
            $stmt_bien->bind_param("i", $annonce_id);
            $stmt_bien->execute();
            $res_bien = $stmt_bien->get_result();
            $bien = $res_bien->fetch_assoc();

            if ($bien) {
                // Insertion enrichie dans favoris
                $stmt_insert = $conn->prepare("
    INSERT IGNORE INTO favoris 
    (utilisateur_id, annonce_id, type_annonce, image, description, titre, localisation) 
    VALUES (?, ?, ?, ?, ?, ?, ?)
");

if ($stmt_insert) {
    $stmt_insert->bind_param(
        "iisssss",
        $utilisateur_id,
        $annonce_id,
        $type_annonce,
        $bien['image'],
        $bien['description'],
        $bien['titre'],
        $bien['localisation']
    );
    $stmt_insert->execute();
}
                } else {
                    echo "Erreur préparation insertion : " . $conn->error;
                }
            } else {
                echo "Bien introuvable.";
            }
        } else {
            echo "Erreur préparation sélection : " . $conn->error;
        }
    } else {
        echo "Type d'annonce invalide.";
    }

$_SESSION['message'] = "Annonce ajoutée aux favoris.";
header("Location: utilisateurs/mes_favoris.php");
exit;