<?php
require_once 'connexion.php';

$sql = "SELECT * FROM estimations ORDER BY date_demande DESC";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>Demandes d'estimation</title>
  <link rel="stylesheet" href="style.css"> <!-- adapte selon ton fichier CSS -->
  <style>
    table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 20px;
    }
    th, td {
      border: 1px solid #ccc;
      padding: 10px;
      text-align: left;
    }
    th {
      background-color: #f5f5f5;
    }
  </style>
</head>
<body>
  <main class="container">
    <h1>Demandes d'estimation reçues</h1>

    <?php if ($result->num_rows > 0): ?>
      <table>
        <thead>
          <tr>
            <th>Nom</th>
            <th>Email</th>
            <th>Téléphone</th>
            <th>Type de bien</th>
            <th>Adresse</th>
            <th>Surface</th>
            <th>Description</th>
            <th>Date</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody>
          <?php while ($row = $result->fetch_assoc()): ?>
            <tr>
              <td><?= htmlspecialchars($row['nom']) ?></td>
              <td><?= htmlspecialchars($row['email']) ?></td>
              <td><?= htmlspecialchars($row['telephone']) ?></td>
              <td><?= htmlspecialchars($row['type_bien']) ?></td>
              <td><?= htmlspecialchars($row['adresse']) ?></td>
              <td><?= htmlspecialchars($row['surface']) ?> m²</td>
              <td><?= nl2br(htmlspecialchars($row['description'])) ?></td>
              <td><?= htmlspecialchars($row['date_demande']) ?></td>
              <td>
  <form method="POST" action="supprimer-estimation.php" onsubmit="return confirm('Confirmer la suppression ?');">
    <input type="hidden" name="id" value="<?= $row['id'] ?>">
    <button type="submit">🗑️ Supprimer</button>
  </form>
</td>
            </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    <?php else: ?>
      <p>Aucune demande d'estimation pour le moment.</p>
    <?php endif; ?>
  </main>
</body>
</html>