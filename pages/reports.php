<?php
session_start();
require_once '../includes/db.php';

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header('Location: ../oauth/login.php');
    exit;
}

// Récupérer les informations de l'utilisateur
$stmt = $pdo->prepare("SELECT * FROM Utilisateur WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

// Récupérer les ventes par mois pour l'année en cours
$stmt = $pdo->query("
    SELECT 
        DATE_FORMAT(created_at, '%Y-%m') as mois,
        COUNT(*) as nombre_ventes,
        SUM(prix_total) as total_ventes
    FROM Ventes 
    WHERE YEAR(created_at) = YEAR(CURRENT_DATE)
    GROUP BY DATE_FORMAT(created_at, '%Y-%m')
    ORDER BY mois ASC
");
$ventes_mensuelles = $stmt->fetchAll();

// Récupérer les ventes des 7 derniers jours
$stmt = $pdo->query("
    SELECT 
        DATE(created_at) as jour,
        COUNT(*) as nombre_ventes,
        SUM(prix_total) as total_ventes
    FROM Ventes 
    WHERE created_at >= DATE_SUB(CURRENT_DATE, INTERVAL 7 DAY)
    GROUP BY DATE(created_at)
    ORDER BY jour ASC
");
$ventes_journalieres = $stmt->fetchAll();

// Récupérer les statistiques globales
$stmt = $pdo->query("SELECT 
    COUNT(*) as total_ventes,
    SUM(prix_total) as ca_total,
    AVG(prix_total) as panier_moyen
FROM Ventes");
$stats_globales = $stmt->fetch();

// Récupérer les meilleurs vendeurs
$stmt = $pdo->query("
    SELECT 
        u.nom,
        COUNT(*) as nombre_ventes,
        SUM(v.prix_total) as ca_total
    FROM Ventes v
    JOIN Utilisateur u ON v.user_id = u.id
    GROUP BY u.id
    ORDER BY ca_total DESC
    LIMIT 5
");
$meilleurs_vendeurs = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stockyy - Rapports</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/animate.css@4.1.1/animate.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: var(--card-shadow);
            transition: transform 0.3s ease;
            animation: fadeInUp 0.5s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            margin-bottom: 15px;
        }

        .charts-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(500px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .chart-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: var(--card-shadow);
            animation: fadeIn 0.5s ease;
        }

        .chart-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .chart-title {
            font-size: 18px;
            font-weight: 600;
            color: var(--text-color);
        }

        .best-sellers {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: var(--card-shadow);
            margin-bottom: 30px;
            animation: fadeInUp 0.5s ease;
        }

        .seller-item {
            display: flex;
            align-items: center;
            padding: 15px;
            border-bottom: 1px solid #eee;
            transition: background-color 0.3s ease;
        }

        .seller-item:hover {
            background-color: #f8f9fa;
        }

        .seller-rank {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background-color: var(--primary-color);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
            font-weight: 600;
        }

        .seller-info {
            flex: 1;
        }

        .seller-name {
            font-weight: 500;
            margin-bottom: 5px;
        }

        .seller-stats {
            font-size: 14px;
            color: var(--text-light);
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
            }
            to {
                opacity: 1;
            }
        }

        @media (max-width: 768px) {
            .charts-container {
                grid-template-columns: 1fr;
            }
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
                <div class="menu-item" onclick="window.location.href='../index.php'">
                    <i class="fas fa-tachometer-alt"></i>
                    <span>Dashboard</span>
                </div>
                <div class="menu-item" onclick="window.location.href='utilisateurs.php'">
                    <i class="fas fa-users"></i>
                    <span>Utilisateurs</span>
                </div>
                <div class="menu-item" onclick="window.location.href='produits.php'">
                    <i class="fas fa-box"></i>
                    <span>Produits</span>
                </div>
                <div class="menu-item" onclick="window.location.href='ventes.php'">
                    <i class="fas fa-shopping-cart"></i>
                    <span>Ventes</span>
                </div>
                <div class="menu-item active">
                    <i class="fas fa-chart-bar"></i>
                    <span>Rapports</span>
                </div>
                <div class="menu-item" onclick="window.location.href='settings.php'">
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
                <a href="../oauth/logout.php" class="logout-btn">
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
            </div>

            <div class="content-wrapper">
                <h1 class="page-title">Rapports des Ventes</h1>

                <!-- Statistiques globales -->
                <div class="stats-container">
                    <div class="stat-card">
                        <div class="stat-icon" style="background-color: rgba(67, 97, 238, 0.1); color: var(--primary-color);">
                            <i class="fas fa-shopping-cart"></i>
                        </div>
                        <h3>Total Ventes</h3>
                        <div class="value"><?php echo number_format($stats_globales['total_ventes']); ?></div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-icon" style="background-color: rgba(42, 157, 143, 0.1); color: var(--success-color);">
                            <i class="fas fa-euro-sign"></i>
                        </div>
                        <h3>Chiffre d'Affaires</h3>
                        <div class="value"><?php echo number_format($stats_globales['ca_total'], 2); ?> €</div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-icon" style="background-color: rgba(230, 57, 70, 0.1); color: var(--danger-color);">
                            <i class="fas fa-shopping-basket"></i>
                        </div>
                        <h3>Panier Moyen</h3>
                        <div class="value"><?php echo number_format($stats_globales['panier_moyen'], 2); ?> €</div>
                    </div>
                </div>

                <!-- Graphiques -->
                <div class="charts-container">
                    <div class="chart-card">
                        <div class="chart-header">
                            <h2 class="chart-title">
                                <i class="fas fa-chart-line"></i>
                                Ventes Mensuelles
                            </h2>
                        </div>
                        <canvas id="monthlyChart"></canvas>
                    </div>

                    <div class="chart-card">
                        <div class="chart-header">
                            <h2 class="chart-title">
                                <i class="fas fa-chart-bar"></i>
                                Ventes Journalières
                            </h2>
                        </div>
                        <canvas id="dailyChart"></canvas>
                    </div>
                </div>

                <!-- Meilleurs vendeurs -->
                <div class="best-sellers">
                    <div class="chart-header">
                        <h2 class="chart-title">
                            <i class="fas fa-trophy"></i>
                            Top 5 Vendeurs
                        </h2>
                    </div>
                    <?php foreach ($meilleurs_vendeurs as $index => $vendeur): ?>
                    <div class="seller-item">
                        <div class="seller-rank"><?php echo $index + 1; ?></div>
                        <div class="seller-info">
                            <div class="seller-name"><?php echo htmlspecialchars($vendeur['nom']); ?></div>
                            <div class="seller-stats">
                                <?php echo $vendeur['nombre_ventes']; ?> ventes |
                                CA: <?php echo number_format($vendeur['ca_total'], 2); ?> €
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="../assets/js/main.js"></script>
    <script>
        // Configuration des graphiques
        const monthlyData = <?php echo json_encode($ventes_mensuelles); ?>;
        const dailyData = <?php echo json_encode($ventes_journalieres); ?>;

        // Graphique mensuel
        const monthlyCtx = document.getElementById('monthlyChart').getContext('2d');
        new Chart(monthlyCtx, {
            type: 'line',
            data: {
                labels: monthlyData.map(item => {
                    const date = new Date(item.mois + '-01');
                    return date.toLocaleDateString('fr-FR', { month: 'long' });
                }),
                datasets: [{
                    label: 'Chiffre d\'affaires (€)',
                    data: monthlyData.map(item => item.total_ventes),
                    borderColor: '#4361ee',
                    backgroundColor: 'rgba(67, 97, 238, 0.1)',
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    title: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true
                    }
                },
                animation: {
                    duration: 2000,
                    easing: 'easeInOutQuart'
                }
            }
        });

        // Graphique journalier
        const dailyCtx = document.getElementById('dailyChart').getContext('2d');
        new Chart(dailyCtx, {
            type: 'bar',
            data: {
                labels: dailyData.map(item => {
                    const date = new Date(item.jour);
                    return date.toLocaleDateString('fr-FR', { weekday: 'short', day: 'numeric' });
                }),
                datasets: [{
                    label: 'Nombre de ventes',
                    data: dailyData.map(item => item.nombre_ventes),
                    backgroundColor: '#2a9d8f',
                    borderRadius: 5
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    title: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                },
                animation: {
                    duration: 2000,
                    easing: 'easeInOutQuart'
                }
            }
        });
    </script>
</body>
</html>