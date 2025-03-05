<?php
// ... existing code ...
session_start();
require_once '../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];

    $stmt = $pdo->prepare("SELECT * FROM Utilisateur WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['mot_de_passe'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['role'] = $user['role'];

        if ($user['role'] == 'admin') {
        header('Location: /pharmacie_stock/index.php');

        } else {
            header('Location: /pharmacie_stock/ventes.php');
        }
        exit;
    } else {
        $error = "Email ou mot de passe incorrect.";
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
    <title>Connexion</title>
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
            <h2>Connexion</h2>
            <div class="input-group">
                <i class="fas fa-envelope"></i>
                <input type="email" name="email" placeholder="Email" required>
            </div>
            <div class="input-group">
                <i class="fas fa-lock"></i>
                <input type="password" name="password" placeholder="Mot de passe" required>
            </div>
            <button type="submit">Se connecter</button>
            <?php if (isset($error)) echo "<p class='error'>$error</p>"; ?>
            <div class="redirect-link">
                <p>Pas encore de compte ? <a href="register.php"><i class="fas fa-user-plus"></i> S'inscrire</a></p>
            </div>
        </form>
    </div>
</body>
</html>
