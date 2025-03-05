<?php
session_start();
require_once 'includes/db.php';

// Vérifier si l'utilisateur est connecté et est un vendeur
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'vendeur') {
    header('Location: oauth/login.php');
    exit;
}

// Traitement de l'enregistrement d'une nouvelle vente
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $product_id = $_POST['product_id'];
    $quantite = $_POST['quantite'];
    
    // Récupérer les informations du produit
    $stmt = $pdo->prepare("SELECT prix, quantite FROM Produit WHERE id = ?");
    $stmt->execute([$product_id]);
    $produit = $stmt->fetch();
    
    if ($produit && $produit['quantite'] >= $quantite) {
        $prix_total = $produit['prix'] * $quantite;
        
        // Enregistrer la vente
        $stmt = $pdo->prepare("INSERT INTO Ventes (user_id, product_id, quantite, prix_total) VALUES (?, ?, ?, ?)");
        $stmt->execute([$_SESSION['user_id'], $product_id, $quantite, $prix_total]);
        
        // Mettre à jour le stock
        $stmt = $pdo->prepare("UPDATE Produit SET quantite = quantite - ? WHERE id = ?");
        $stmt->execute([$quantite, $product_id]);
        
        $success = "Vente enregistrée avec succès!";
    } else {
        $error = "Stock insuffisant!";
    }
}

// Récupérer la liste des produits
$stmt = $pdo->query("SELECT * FROM Produit WHERE quantite > 0");
$produits = $stmt->fetchAll();

// Récupérer les dernières ventes du vendeur
$stmt = $pdo->prepare("
    SELECT v.*, p.nom as produit_nom, p.reference 
    FROM Ventes v 
    JOIN Produit p ON v.product_id = p.id 
    WHERE v.user_id = ? 
    ORDER BY v.created_at DESC 
    LIMIT 5
");
$stmt->execute([$_SESSION['user_id']]);
$dernieres_ventes = $stmt->fetchAll();

// Récupérer les informations du vendeur connecté
$stmt = $pdo->prepare("SELECT nom, email FROM Utilisateur WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$vendeur = $stmt->fetch();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Vendeur</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="styles.css">
    <style>
        .dashboard {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            max-width: 1200px;
            margin: 20px auto;
            padding: 20px;
        }

        .card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }

        .card:hover {
            transform: translateY(-5px);
        }

        .ventes-form {
            animation: slideIn 0.5s ease-out;
        }

        .derniere-ventes {
            animation: fadeIn 0.5s ease-out;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        .success {
            color: green;
            text-align: center;
            margin: 10px 0;
        }

        @keyframes slideIn {
            from { transform: translateX(-100%); }
            to { transform: translateX(0); }
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .header {
            background: white;
            padding: 15px 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            animation: slideDown 0.5s ease-out;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .user-info i {
            font-size: 1.5em;
            color: #007bff;
        }

        .user-details {
            line-height: 1.2;
        }

        .user-details .name {
            font-weight: bold;
            color: #333;
        }

        .user-details .email {
            font-size: 0.9em;
            color: #666;
        }

        .logout-btn {
            background: #dc3545;
            color: white;
            padding: 8px 15px;
            border-radius: 4px;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 5px;
            transition: all 0.3s ease;
        }

        .logout-btn:hover {
            background: #c82333;
            transform: scale(1.05);
        }

        @keyframes slideDown {
            from { transform: translateY(-100%); }
            to { transform: translateY(0); }
        }

        /* Ajuster le dashboard pour qu'il soit en dessous du header */
        body {
            display: block;
            height: auto;
            padding-top: 20px;
        }

        .currency {
            color: #ffd700;
            animation: shimmer 2s infinite;
        }

        @keyframes shimmer {
            0% { opacity: 0.5; }
            50% { opacity: 1; }
            100% { opacity: 0.5; }
        }

        .input-group {
            position: relative;
            margin-bottom: 15px;
        }

        .input-group input:focus + i,
        .input-group select:focus + i {
            color: var(--primary-color);
            transform: scale(1.1);
        }

        .input-group i {
            transition: all 0.3s ease;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="user-info">
            <i class="fas fa-user-circle"></i>
            <div class="user-details">
                <div class="name"><?= htmlspecialchars($vendeur['nom']) ?></div>
                <div class="email"><?= htmlspecialchars($vendeur['email']) ?></div>
            </div>
        </div>
        <a href="oauth/logout.php" class="logout-btn">
            <i class="fas fa-sign-out-alt"></i>
            Déconnexion
        </a>
    </div>

    <div class="dashboard">
        <div class="card ventes-form">
            <h2><i class="fas fa-shopping-cart"></i> Nouvelle Vente</h2>
            <form method="POST" action="">
                <div class="input-group">
                    <i class="fas fa-box"></i>
                    <select name="product_id" required>
                        <option value="">Sélectionner un produit</option>
                        <?php foreach($produits as $produit): ?>
                            <option value="<?= $produit['id'] ?>">
                                <?= $produit['nom'] ?> - Stock: <?= $produit['quantite'] ?> - Prix: 
                                <?= number_format($produit['prix'] * 4500, 0, ',', ' ') ?> 
                                <i class="fas fa-coins"></i> Ar
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="input-group">
                    <i class="fas fa-sort-numeric-up"></i>
                    <input type="number" name="quantite" min="1" placeholder="Quantité" required>
                </div>
                <button type="submit"><i class="fas fa-save"></i> Enregistrer la vente</button>
                <?php if (isset($success)) echo "<p class='success'>$success</p>"; ?>
                <?php if (isset($error)) echo "<p class='error'>$error</p>"; ?>
            </form>
        </div>

        <div class="card derniere-ventes">
            <h2><i class="fas fa-history"></i> Dernières Ventes</h2>
            <table>
                <thead>
                    <tr>
                        <th>Produit</th>
                        <th>Quantité</th>
                        <th>Prix Total</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($dernieres_ventes as $vente): ?>
                        <tr>
                            <td><?= $vente['produit_nom'] ?></td>
                            <td><?= $vente['quantite'] ?></td>
                            <td>
                                <?= number_format($vente['prix_total'] * 4500, 0, ',', ' ') ?> 
                                <span class="currency"><i class="fas fa-coins"></i> Ar</span>
                            </td>
                            <td><?= date('d/m/Y H:i', strtotime($vente['created_at'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
