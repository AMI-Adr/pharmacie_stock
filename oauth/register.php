<?php
// ... existing code ...
session_start();
require_once '../includes/db.php';


if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nom = $_POST['nom'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_BCRYPT);
    $role = $_POST['role'];

    $stmt = $pdo->prepare("INSERT INTO Utilisateur (nom, email, mot_de_passe, role) VALUES (?, ?, ?, ?)");
    if ($stmt->execute([$nom, $email, $password, $role])) {
        header('Location: /pharmacie_stock/oauth/login.php');
        exit;
    } else {
        $error = "Erreur lors de l'inscription.";
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="../styles.css">
    <title>Inscription</title>
    <style>
        .redirect-link {
            text-align: center;
            margin-top: 15px;
            font-size: 14px;
        }
        .redirect-link a {
            color: var(--primary-color);
            text-decoration: none;
            transition: color 0.3s ease;
        }
        .redirect-link a:hover {
            color: var(--secondary-color);
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="form-container">
        <form method="POST" action="">
            <h2>Inscription</h2>
            <div class="input-group">
                <i class="fas fa-user"></i>
                <input type="text" name="nom" placeholder="Nom" required>
            </div>
            <div class="input-group">
                <i class="fas fa-envelope"></i>
                <input type="email" name="email" placeholder="Email" required>
            </div>
            <div class="input-group">
                <i class="fas fa-lock"></i>
                <input type="password" name="password" placeholder="Mot de passe" required>
            </div>
            <?php
                    // Compter les admins
                    $stmt = $pdo->query("SELECT COUNT(*) FROM Utilisateur WHERE role = 'admin'");
                    $total_admins = $stmt->fetchColumn();

                    // Compter les admins
                    $stmt = $pdo->query("SELECT COUNT(*) FROM Utilisateur WHERE role = 'vendeur'");
                    $total_vendeur = $stmt->fetchColumn();
             ?>
            <div class="input-group">
                <i class="fas fa-user-tag"></i>
                <select name="role" required>
                    <option value="vendeur"<?php echo $total_vendeur >= 20 ? 'disabled' : ''; ?>>Vendeur</option>
                    <option value="admin" <?php echo $total_admins >= 1 ? 'disabled' : ''; ?>>Admin</option>
                </select>
            </div>
            <button type="submit">S'inscrire</button>
            <?php if (isset($error)) echo "<p class='error'>$error</p>"; ?>
            <div class="redirect-link">
                <p>Déjà un compte ? <a href="login.php"><i class="fas fa-sign-in-alt"></i> Se connecter</a></p>
            </div>
        </form>
    </div>
</body>
</html>
