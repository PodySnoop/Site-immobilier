<?php
session_start();

// Vérification des droits d'admin
if (!isset($_SESSION['utilisateur']) || $_SESSION['utilisateur']['role'] !== 'admin') {
    header('Location: ../index.php');
    exit();
}

require_once '../includes/db.php'; // Connexion mysqli : $conn

// Vérification de l'ID de l'utilisateur
$id = $_POST['id'] ?? $_GET['id'] ?? null;
if (!isset($id) || !is_numeric($id)) {
    header("Location: liste_utilisateurs.php?message=erreur_id");
    exit();
}


$utilisateur_id = (int)$id;
$message = '';
$error = '';

// Récupération des données de l'utilisateur
$stmt = $conn->prepare("SELECT * FROM utilisateurs WHERE id = ?");
$stmt->bind_param("i", $utilisateur_id);
$stmt->execute();
$result = $stmt->get_result();
$utilisateur = $result->fetch_assoc();
$stmt->close();

if (!$utilisateur) {
    $_SESSION['message'] = "Utilisateur non trouvé";
    header('Location: liste_utilisateurs.php');
    exit();
}

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = trim($_POST['nom']);
    $prenom = trim($_POST['prenom']);
    $email = trim($_POST['email']);
    $role = $_POST['role'];
    $statut = $_POST['statut'];

    // Validation
    if (empty($nom) || empty($prenom) || empty($email)) {
        $error = "Tous les champs obligatoires doivent être remplis";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "L'adresse email n'est pas valide";
    } else {
        // Vérification de l'unicité de l'email
        $stmt = $conn->prepare("SELECT id FROM utilisateurs WHERE email = ? AND id != ?");
        $stmt->bind_param("si", $email, $utilisateur_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $error = "Cette adresse email est déjà utilisée par un autre compte";
        } else {
            // Mise à jour des informations
            $stmt = $conn->prepare("UPDATE utilisateurs SET nom = ?, prenom = ?, email = ?, role = ?, statut = ? WHERE id = ?");
            $stmt->bind_param("sssssi", $nom, $prenom, $email, $role, $statut, $utilisateur_id);

            if ($stmt->execute()) {
                $stmt->close();
                header("Location: liste_utilisateurs.php?message=modifie");

                exit();
            } else {
                $error = "Une erreur est survenue lors de la mise à jour";
            }
            $stmt->close();
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Modifier utilisateur</title>
    <style>
        body { font-family: Arial; background: #f4f4f4; padding: 20px; }
        .container { max-width: 600px; margin: auto; background: #fff; padding: 20px; border-radius: 8px; }
        h2 { text-align: center; color: #2c3e50; }
        label { display: block; margin-top: 10px; }
        input, select { width: 100%; padding: 8px; margin-top: 5px; }
        .btn { margin-top: 20px; background: #3498db; color: white; padding: 10px; border: none; cursor: pointer; width: 100%; }
        .error { color: red; margin-top: 10px; }
    </style>
</head>
<body>
    <div class="container">
        <h2>Modifier l'utilisateur #<?php echo htmlspecialchars($utilisateur['id']); ?></h2>

        <?php if (!empty($error)): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form method="post" action="">
            <input type="hidden" name="id" value="<?php echo htmlspecialchars($utilisateur['id']); ?>">

            <label for="nom">Nom :</label>
            <input type="text" name="nom" id="nom" value="<?php echo htmlspecialchars($utilisateur['nom']); ?>" required>

            <label for="prenom">Prénom :</label>
            <input type="text" name="prenom" id="prenom" value="<?php echo htmlspecialchars($utilisateur['prenom']); ?>" required>

            <label for="email">Email :</label>
            <input type="email" name="email" id="email" value="<?php echo htmlspecialchars($utilisateur['email']); ?>" required>

            <label for="role">Rôle :</label>
            <select name="role" id="role">
                <option value="client" <?php echo $utilisateur['role'] === 'client' ? 'selected' : ''; ?>>Client</option>
                <option value="admin" <?php echo $utilisateur['role'] === 'admin' ? 'selected' : ''; ?>>Admin</option>
            </select>

            <label for="statut">Statut :</label>
            <select name="statut" id="statut">
                <option value="actif" <?php echo $utilisateur['statut'] === 'actif' ? 'selected' : ''; ?>>Actif</option>
                <option value="inactif" <?php echo $utilisateur['statut'] === 'inactif' ? 'selected' : ''; ?>>Inactif</option>
            </select>

            <button type="submit" class="btn">Enregistrer les modifications</button>
        </form>
    </div>
</body>
</html>