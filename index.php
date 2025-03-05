<?php
session_start();
require_once 'includes/db.php';

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header('Location: oauth/login.php');
    exit;
}

// Récupérer les informations de l'utilisateur
$stmt = $pdo->prepare("SELECT * FROM Utilisateur WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

// Récupérer les statistiques pour le dashboard
// Nombre total de produits
$stmt = $pdo->query("SELECT COUNT(*) as total FROM Produit");
$totalProduits = $stmt->fetch()['total'];

// Valeur totale du stock
$stmt = $pdo->query("SELECT SUM(prix * quantite) as valeur FROM Produit");
$valeurStock = $stmt->fetch()['valeur'] ?? 0;

// Nombre de ventes aujourd'hui
$stmt = $pdo->query("SELECT COUNT(*) as total FROM Ventes WHERE DATE(created_at) = CURDATE()");
$ventesAujourdhui = $stmt->fetch()['total'];

// Chiffre d'affaires aujourd'hui
$stmt = $pdo->query("SELECT SUM(prix_total) as total FROM Ventes WHERE DATE(created_at) = CURDATE()");
$caAujourdhui = $stmt->fetch()['total'] ?? 0;

// Produits à faible stock (moins de 10 unités)
$stmt = $pdo->query("SELECT * FROM Produit WHERE quantite < 10 ORDER BY quantite ASC LIMIT 5");
$produitsFaibleStock = $stmt->fetchAll();

// Dernières ventes
$stmt = $pdo->query("SELECT v.*, p.nom as produit_nom, u.nom as vendeur_nom
                     FROM Ventes v
                     JOIN Produit p ON v.product_id = p.id
                     JOIN Utilisateur u ON v.user_id = u.id
                     ORDER BY v.created_at DESC LIMIT 5");
$dernieresVentes = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stockyy - Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/animate.css@4.1.1/animate.min.css">
    <style>
        :root {
            --primary-color: #4361ee;
            --secondary-color: #3f37c9;
            --accent-color: #4895ef;
            --text-color: #333;
            --text-light: #7b7b7b;
            --bg-color:rgba(217, 255, 244, 0.57);
            --sidebar-width: 250px;
            --header-height: 60px;
            --danger-color: #e63946;
            --success-color: #2a9d8f;
            --warning-color: #f9c74f;
            --card-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            --transition-speed: 0.3s;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--bg-color);
            color: var(--text-color);
            overflow-x: hidden;
        }

        .container {
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar */
        .sidebar {
            width: var(--sidebar-width);
            background: linear-gradient(to bottom, var(--primary-color), var(--secondary-color));
            color: white;
            position: fixed;
            height: 100vh;
            transition: all var(--transition-speed) ease;
            z-index: 1000;
            box-shadow: 2px 0 5px rgba(0, 0, 0, 0.1);
        }

        .sidebar-header {
            padding: 20px;
            text-align: center;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .sidebar-header h2 {
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 5px;
        }

        .sidebar-header p {
            font-size: 14px;
            opacity: 0.8;
        }

        .sidebar-menu {
            padding: 20px 0;
        }

        .menu-item {
            padding: 12px 20px;
            display: flex;
            align-items: center;
            transition: all 0.2s ease;
            cursor: pointer;
            border-left: 4px solid transparent;
        }

        .menu-item:hover, .menu-item.active {
            background-color: rgba(255, 255, 255, 0.1);
            border-left: 4px solid white;
        }

        .menu-item i {
            margin-right: 15px;
            font-size: 18px;
            width: 20px;
            text-align: center;
        }

        .menu-item span {
            font-size: 15px;
            font-weight: 500;
        }

        .sidebar-footer {
            position: absolute;
            bottom: 0;
            width: 100%;
            padding: 15px 20px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            display: flex;
            align-items: center;
        }

        .user-info {
            display: flex;
            align-items: center;
            flex: 1;
        }

        .user-avatar {
            width: 35px;
            height: 35px;
            border-radius: 50%;
            background-color: rgba(0, 0, 0, 0.33);
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 10px;
        }

        .user-name {
            font-size: 14px;
            font-weight: 500;
        }

        .user-role {
            font-size: 12px;
            opacity: 0.8;
        }

        .logout-btn {
            background: none;
            border: none;
            color: white;
            cursor: pointer;
            font-size: 18px;
            opacity: 0.8;
            transition: opacity 0.2s;
        }

        .logout-btn:hover {
            opacity: 1;
        }

        /* Main Content */
        .main-content {
            flex: 1;
            margin-left: var(--sidebar-width);
            transition: margin var(--transition-speed) ease;
        }

        .header {
            height: var(--header-height);
            background-color: white;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            display: flex;
            align-items: center;
            padding: 0 20px;
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .toggle-sidebar {
            background: none;
            border: none;
            font-size: 20px;
            cursor: pointer;
            color: var(--text-color);
            margin-right: 20px;
        }

        .search-bar {
            flex: 1;
            max-width: 400px;
            position: relative;
            margin: 0 20px;
        }

        .search-bar input {
            width: 100%;
            padding: 8px 15px 8px 35px;
            border: 1px solid #ddd;
            border-radius: 20px;
            font-size: 14px;
            outline: none;
            transition: all 0.2s;
        }

        .search-bar input:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 2px rgba(67, 97, 238, 0.2);
        }

        .search-bar i {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-light);
        }

        .header-actions {
            display: flex;
            align-items: center;
        }

        .header-actions button {
            background: none;
            border: none;
            font-size: 18px;
            color: var(--text-color);
            margin-left: 15px;
            cursor: pointer;
            position: relative;
        }

        .notification-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            background-color: var(--danger-color);
            color: white;
            font-size: 10px;
            width: 15px;
            height: 15px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* Dashboard Content */
        .dashboard {
            padding: 20px;
        }

        .dashboard-title {
            margin-bottom: 20px;
            font-weight: 600;
            color: var(--text-color);
        }

        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background-color: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: var(--card-shadow);
            transition: transform 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .stat-card .icon {
            width: 50px;
            height: 50px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            margin-bottom: 15px;
        }

        .stat-card .icon.products {
            background-color: rgba(67, 97, 238, 0.1);
            color: var(--primary-color);
        }

        .stat-card .icon.stock {
            background-color: rgba(42, 157, 143, 0.1);
            color: var(--success-color);
        }

        .stat-card .icon.sales {
            background-color: rgba(249, 199, 79, 0.1);
            color: var(--warning-color);
        }

        .stat-card .icon.revenue {
            background-color: rgba(230, 57, 70, 0.1);
            color: var(--danger-color);
        }

        .stat-card h3 {
            font-size: 14px;
            color: var(--text-light);
            margin-bottom: 5px;
        }

        .stat-card .value {
            font-size: 24px;
            font-weight: 600;
        }

        .dashboard-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 20px;
        }

        .card {
            background-color: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: var(--card-shadow);
            margin-bottom: 20px;
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .card-title {
            font-size: 18px;
            font-weight: 600;
        }

        .card-action {
            color: var(--primary-color);
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
        }

        .table th, .table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        .table th {
            font-weight: 500;
            color: var(--text-light);
            font-size: 14px;
        }

        .table tr:last-child td {
            border-bottom: none;
        }

        .status {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
        }

        .status.low {
            background-color: rgba(230, 57, 70, 0.1);
            color: var(--danger-color);
        }

        .status.medium {
            background-color: rgba(249, 199, 79, 0.1);
            color: var(--warning-color);
        }

        .status.good {
            background-color: rgba(42, 157, 143, 0.1);
            color: var(--success-color);
        }

        /* Animations */
        .animate__animated {
            animation-duration: 0.5s;
        }

        /* Responsive */
        @media (max-width: 992px) {
            .dashboard-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
            }

            .sidebar.active {
                transform: translateX(0);
            }

            .main-content {
                margin-left: 0;
            }

            .stats-container {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 576px) {
            .stats-container {
                grid-template-columns: 1fr;
            }

            .search-bar {
                display: none;
            }
        }

        .currency {
            display: inline-flex;
            align-items: center;
            gap: 3px;
            color: #ffd700;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.1); }
            100% { transform: scale(1); }
        }

        .value {
            display: flex;
            align-items: center;
            gap: 5px;
            font-size: 1.2em;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Sidebar -->
        <div class="sidebar animate__animated animate__slideInLeft">
            <div class="sidebar-header">
                <h2>Pharm-amy</h2>
                <p>Gestion de stock</p>
            </div>
            <div class="sidebar-menu">
                <div class="menu-item active">
                    <i class="fas fa-tachometer-alt"></i>
                    <span>Dashboard</span>
                </div>
                <div class="menu-item" onclick="window.location.href='pages/utilisateurs.php'">
                    <i class="fas fa-users"></i>
                    <span>Utilisateurs</span>
                </div>
                <div class="menu-item" onclick="window.location.href='pages/produits.php'">
                    <i class="fas fa-box"></i>
                    <span>Produits</span>
                </div>
                <div class="menu-item" onclick="window.location.href='pages/ventes.php'">
                    <i class="fas fa-shopping-cart"></i>
                    <span>Ventes</span>
                </div>
                <div class="menu-item" onclick="window.location.href='pages/reports.php'">
                    <i class="fas fa-chart-bar"></i>
                    <span>Rapports</span>
                </div>
                <div class="menu-item" onclick="window.location.href='pages/settings.php'">
                    <i class="fas fa-cog"></i>
                    <span>Paramètres</span>
                </div>
            </div>
            <div class="sidebar-footer">
                <div class="user-info">
                    <div class="user-avatar">
                        <i class="fas fa-user"></i>
                    </div>
                    <div>
                        <div class="user-name"><?php echo htmlspecialchars($user['nom']); ?></div>
                        <div class="user-role"><?php echo htmlspecialchars($user['role']); ?></div>
                    </div>
                </div>
                <a href="oauth/logout.php" class="logout-btn">
                    <i class="fas fa-sign-out-alt"></i>
                </a>
            </div>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <div class="header">
                <button class="toggle-sidebar" id="toggleSidebar">
                    <i class="fas fa-bars"></i>
                </button>
                <div class="search-bar">
                    <i class="fas fa-search"></i>
                    <input type="text" placeholder="Rechercher...">
                </div>
                <div class="header-actions">
                    <button>
                        <i class="far fa-bell"></i>
                        <span class="notification-badge">3</span>
                    </button>
                    <button>
                        <i class="far fa-envelope"></i>
                        <span class="notification-badge">5</span>
                    </button>
                </div>
            </div>

            <div class="dashboard">
                <h1 class="dashboard-title animate__animated animate__fadeIn">Tableau de bord</h1>

                <div class="stats-container">
                    <div class="stat-card animate__animated animate__fadeInUp" style="animation-delay: 0.1s">
                        <div class="icon products">
                            <i class="fas fa-box"></i>
                        </div>
                        <h3>Total Produits</h3>
                        <div class="value"><?php echo $totalProduits; ?></div>
                    </div>

                    <div class="stat-card animate__animated animate__fadeInUp" style="animation-delay: 0.2s">
                        <div class="icon stock">
                            <i class="fas fa-warehouse"></i>
                        </div>
                        <h3>Valeur du Stock</h3>
                        <div class="value">
                            <?php echo number_format($valeurStock * 4500, 0, ',', ' '); ?> 
                            <span class="currency"><i class="fas fa-coins"></i> Ar</span>
                        </div>
                    </div>

                    <div class="stat-card animate__animated animate__fadeInUp" style="animation-delay: 0.3s">
                        <div class="icon sales">
                            <i class="fas fa-shopping-cart"></i>
                        </div>
                        <h3>Ventes Aujourd'hui</h3>
                        <div class="value"><?php echo $ventesAujourdhui; ?></div>
                    </div>

                    <div class="stat-card animate__animated animate__fadeInUp" style="animation-delay: 0.4s">
                        <div class="icon revenue">
                            <i class="fas fa-money-bill-wave"></i>
                        </div>
                        <h3>CA Aujourd'hui</h3>
                        <div class="value">
                            <?php echo number_format($caAujourdhui * 4500, 0, ',', ' '); ?> 
                            <span class="currency"><i class="fas fa-coins"></i> Ar</span>
                        </div>
                    </div>
                </div>

                <div class="dashboard-grid">
                    <div class="card animate__animated animate__fadeIn" style="animation-delay: 0.5s">
                        <div class="card-header">
                            <h2 class="card-title">Dernières Ventes</h2>
                            <span class="card-action" onclick="window.location.href='pages/ventes.php'">Voir tout</span>
                        </div>
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Produit</th>
                                    <th>Vendeur</th>
                                    <th>Quantité</th>
                                    <th>Prix Total</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($dernieresVentes as $vente): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($vente['produit_nom']); ?></td>
                                    <td><?php echo htmlspecialchars($vente['vendeur_nom']); ?></td>
                                    <td><?php echo $vente['quantite']; ?></td>
                                    <td><?php echo number_format($vente['prix_total'], 2); ?> €</td>
                                    <td><?php echo date('d/m/Y H:i', strtotime($vente['created_at'])); ?></td>
                                </tr>
                                <?php endforeach; ?>
                                <?php if (empty($dernieresVentes)): ?>
                                <tr>
                                    <td colspan="5" style="text-align: center;">Aucune vente récente</td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <div class="card animate__animated animate__fadeIn" style="animation-delay: 0.6s">
                        <div class="card-header">
                            <h2 class="card-title">Stock Faible</h2>
                            <span class="card-action" onclick="window.location.href='pages/produits.php'">Voir tout</span>
                        </div>
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Produit</th>
                                    <th>Quantité</th>
                                    <th>Statut</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($produitsFaibleStock as $produit): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($produit['nom']); ?></td>
                                    <td><?php echo $produit['quantite']; ?></td>
                                    <td>
                                        <?php if ($produit['quantite'] <= 3): ?>
                                            <span class="status low">Critique</span>
                                        <?php elseif ($produit['quantite'] <= 7): ?>
                                            <span class="status medium">Faible</span>
                                        <?php else: ?>
                                            <span class="status good">OK</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php if (empty($produitsFaibleStock)): ?>
                                <tr>
                                    <td colspan="3" style="text-align: center;">Aucun produit en stock faible</td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Toggle sidebar
            const toggleBtn = document.getElementById('toggleSidebar');
            const sidebar = document.querySelector('.sidebar');
            const mainContent = document.querySelector('.main-content');

            toggleBtn.addEventListener('click', function() {
                sidebar.classList.toggle('active');

                if (window.innerWidth > 768) {
                    if (sidebar.classList.contains('active')) {
                        mainContent.style.marginLeft = '0';
                        sidebar.style.transform = 'translateX(-100%)';
                    } else {
                        mainContent.style.marginLeft = 'var(--sidebar-width)';
                        sidebar.style.transform = 'translateX(0)';
                    }
                }
            });

            // Responsive behavior
            function handleResize() {
                if (window.innerWidth <= 768) {
                    sidebar.classList.remove('active');
                    mainContent.style.marginLeft = '0';
                } else {
                    sidebar.style.transform = 'translateX(0)';
                    mainContent.style.marginLeft = 'var(--sidebar-width)';
                }
            }

            window.addEventListener('resize', handleResize);
            handleResize();

            // Menu item active state
            const menuItems = document.querySelectorAll('.menu-item');
            menuItems.forEach(item => {
                item.addEventListener('click', function() {
                    menuItems.forEach(i => i.classList.remove('active'));
                    this.classList.add('active');
                });
            });

            // Add hover animation to cards
            const cards = document.querySelectorAll('.card, .stat-card');
            cards.forEach(card => {
                card.addEventListener('mouseenter', function() {
                    this.classList.add('animate__pulse');
                });

                card.addEventListener('mouseleave', function() {
                    this.classList.remove('animate__pulse');
                });
            });
        });
    </script>
</body>
</html>
