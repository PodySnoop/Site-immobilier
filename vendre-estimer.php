<?php
session_start();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Vendre ou Estimer votre bien - Pollux Immobilier</title>
  <link rel="stylesheet" href="style.css">
  <link rel="stylesheet" href="pages-common.css">
  <style>
    .estimation-container {
      max-width: 800px;
      margin: 0 auto;
      padding: 40px 20px;
    }
    
    .form-estimation {
      background: white;
      padding: 30px;
      border-radius: 8px;
      box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }
    
    .form-estimation label {
      display: block;
      margin: 15px 0 5px;
      font-weight: bold;
      color: #157f68;
    }
    
    .form-estimation input[type="text"],
    .form-estimation input[type="email"],
    .form-estimation input[type="tel"],
    .form-estimation input[type="number"],
    .form-estimation select,
    .form-estimation textarea {
      width: 100%;
      padding: 10px;
      margin-bottom: 15px;
      border: 1px solid #ddd;
      border-radius: 4px;
    }
    
    .form-estimation button {
      background-color: #157f68;
      color: white;
      border: none;
      padding: 12px 25px;
      border-radius: 4px;
      cursor: pointer;
      font-size: 1em;
      margin-top: 15px;
    }
    
    .form-estimation button:hover {
      background-color: #0b1987;
    }
  </style>
</head>
<body>
  <header>
    <div class="header-container">
      <img src="logo.png" alt="Logo Pollux Immobilier" class="logo">
      <h1>Pollux Immobilier</h1>
    </div>
    <nav>
      <ul>
        <li><a href="index.php">Accueil</a></li>
        <li><a href="bien-locatifs.php">Louer</a></li>
        <li><a href="bien-vente.php">Acheter</a></li>
        <li class="active"><a href="vendre-estimer.php">Vendre/Estimer</a></li>
        <li><a href="presentation.html">Notre histoire</a></li>
        <li class="login-link"><a href="utilisateurs/login.php">Se connecter</a></li>
      </ul>
    </nav>
  </header>

  <div class="page-title">
    <div class="container">
      <h1>Vendre ou estimer votre bien</h1>
    </div>
  </div>

  <main class="estimation-container">
    <div class="container">
      <p class="lead-text">Vous souhaitez vendre votre bien ou simplement connaître sa valeur ? Remplissez le formulaire ci-dessous et notre équipe vous recontactera rapidement.</p>

    <form action="traitement-estimation.php" method="POST" class="form-estimation">
      <label for="nom">Votre nom :</label>
      <input type="text" name="nom" id="nom" required>

      <label for="email">Votre email :</label>
      <input type="email" name="email" id="email" required>

      <label for="telephone">Téléphone :</label>
      <input type="tel" name="telephone" id="telephone">

      <label for="type_annonce">Type d'annonce :</label>
      <select name="type_annonce" id="type_annonce" required>
        <option value="">-- Choisissez --</option>
        <option value="Appartement">Appartement</option>
        <option value="Maison">Maison</option>
        <option value="Terrain">Terrain</option>
        <option value="Autre">Autre</option>
      </select>

      <input type="hidden" name="debug_source" value="version corrigée Iris">
      
      <label for="adresse">Adresse du bien :</label>
      <input type="text" name="adresse" id="adresse" required>

      <label for="surface">Surface (m²) :</label>
      <input type="number" name="surface" id="surface" required>

      <label for="description">Description :</label>
      <textarea name="description" id="description" rows="5"></textarea>

      <button type="submit">Envoyer ma demande</button>
    </form>
    </div>
  </main>

  <footer>
    <div class="container">
      <div class="footer-content">
        <div class="footer-links">
          <a href="mentions-legales.html">Mentions Légales</a>
          <a href="cgv.html">Conditions Générales de Vente</a>
          <a href="index.php">Accueil</a>
        </div>
        <div class="footer-copyright">
          &copy; <?= date('Y') ?> Pollux Immobilier · Tous droits réservés.
        </div>
      </div>
    </div>
  </footer>
</body>
</html>