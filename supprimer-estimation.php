<?php
require_once 'connexion.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
  $id = intval($_POST['id']);

  $stmt = $conn->prepare("DELETE FROM estimations WHERE id = ?");
  $stmt->bind_param("i", $id);
  $stmt->execute();

  header("Location: admin-estimations.php");
  exit();
} else {
  echo "Requête invalide.";
}
?>