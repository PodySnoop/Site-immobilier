<?php
session_start();

if (!isset($_SESSION['utilisateur']) || $_SESSION['utilisateur']['role'] !== 'admin') {
    header('Location: ../index.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once '../includes/db.php'; // connexion à la base

    $titre = $_POST['titre'];
    $description = $_POST['description'];
    $admin_id = $_SESSION['utilisateur']['id'];

    $stmt = $pdo->prepare("INSERT INTO annonces (titre, description, cree_par) VALUES (?, ?, ?)");
    $stmt->execute([$titre, $description, $admin_id]);

    // Récupère l'ID de l'annonce qu'on vient de créer
$annonce_id = $pdo->lastInsertId();

//Message flash
$_SESSION['flash'] = "✅ Annonce créée avec succès.";
// Redirige vers la page de l'annonce
header("Location: annonce.php?id=" . $annonce_id);
exit();
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <head>
  <meta charset="UTF-8">
  <title>Créer une annonce</title>
  <style>
    body {
      font-family: 'Segoe UI', sans-serif;
      background-color: #f4f4f4;
      padding: 40px;
    }
    .form-container {
      background-color: white;
      padding: 30px;
      border-radius: 10px;
      max-width: 600px;
      margin: auto;
      box-shadow: 0 0 10px rgba(0,0,0,0.1);
    }
    h1 {
      color: #333;
      margin-bottom: 20px;
    }
    input, textarea {
      width: 100%;
      padding: 10px;
      margin-bottom: 15px;
      border: 1px solid #ccc;
      border-radius: 5px;
    }
    button {
      background-color: #007bff;
      color: white;
      padding: 10px 20px;
      border: none;
      border-radius: 5px;
      cursor: pointer;
    }
    button:hover {
      background-color: #0056b3;
    }
    .flash {
      color: green;
      margin-bottom: 15px;
    }
  </style>
</head>
</head>
<body>
  <div class="form-container">
    <h1>Créer une nouvelle annonce</h1>

    <?php if (isset($_SESSION['flash'])): ?>
      <p class="flash"><?= htmlspecialchars($_SESSION['flash']) ?></p>
      <?php unset($_SESSION['flash']); ?>
    <?php endif; ?>

    <form method="POST">
      <input type="text" name="titre" placeholder="Titre de l'annonce" required>
      <textarea name="description" placeholder="Description" required></textarea>
      <button type="submit">Créer l'annonce</button>
    </form>
  </div>
</body>
</html>