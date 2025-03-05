<?php
session_start();
require_once '../includes/db.php';

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header('Location: ../oauth/login.php');
    exit;
}

// Vérifier si l'utilisateur est admin
$stmt = $pdo->prepare("SELECT * FROM Utilisateur WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

if ($user['role'] !== 'admin') {
    header('Location: ../index.php');
    exit;
}

// Configuration de la pagination
$users_par_page = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $users_par_page;

// Compter le nombre total d'utilisateurs
$stmt = $pdo->query("SELECT COUNT(*) FROM Utilisateur");
$total_users = $stmt->fetchColumn();
$total_pages = ceil($total_users / $users_par_page);

// Récupérer les utilisateurs pour la page actuelle
$stmt = $pdo->prepare("SELECT * FROM Utilisateur ORDER BY created_at DESC LIMIT :limit OFFSET :offset");
$stmt->bindValue(':limit', $users_par_page, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$utilisateurs = $stmt->fetchAll();

// Ajouter après la vérification du rôle admin
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_user') {
    try {
        // Validation des données
        if (empty($_POST['nom']) || empty($_POST['email']) || empty($_POST['password']) || empty($_POST['role'])) {
            throw new Exception("Tous les champs sont obligatoires");
        }

        // Vérifier si l'email existe déjà
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM Utilisateur WHERE email = ?");
        $stmt->execute([$_POST['email']]);
        if ($stmt->fetchColumn() > 0) {
            throw new Exception("Cet email est déjà utilisé");
        }

        // Hasher le mot de passe
        $hashed_password = password_hash($_POST['password'], PASSWORD_DEFAULT);

        // Insérer le nouvel utilisateur
        $stmt = $pdo->prepare("INSERT INTO Utilisateur (nom, email, mot_de_passe, role) VALUES (?, ?, ?, ?)");
        $stmt->execute([
            $_POST['nom'],
            $_POST['email'],
            $hashed_password,
            $_POST['role']
        ]);

        // Réponse JSON pour AJAX
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => 'Utilisateur ajouté avec succès']);
        exit;

    } catch (Exception $e) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        exit;
    }
}

// Ajouter après les routes existantes
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    
    // Suppression d'utilisateur
    if (isset($_POST['action']) && $_POST['action'] === 'delete_user') {
        try {
            $stmt = $pdo->prepare("DELETE FROM Utilisateur WHERE id = ?");
            $stmt->execute([$_POST['user_id']]);
            echo json_encode(['success' => true, 'message' => 'Utilisateur supprimé avec succès']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Erreur lors de la suppression']);
        }
        exit;
    }

    // Modification d'utilisateur
    if (isset($_POST['action']) && $_POST['action'] === 'edit_user') {
        try {
            // Validation
            if (empty($_POST['nom']) || empty($_POST['email']) || empty($_POST['role'])) {
                throw new Exception("Tous les champs sont obligatoires");
            }

            // Vérifier si l'email existe déjà pour un autre utilisateur
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM Utilisateur WHERE email = ? AND id != ?");
            $stmt->execute([$_POST['email'], $_POST['user_id']]);
            if ($stmt->fetchColumn() > 0) {
                throw new Exception("Cet email est déjà utilisé");
            }

            // Mise à jour
            $sql = "UPDATE Utilisateur SET nom = ?, email = ?, role = ? WHERE id = ?";
            $params = [$_POST['nom'], $_POST['email'], $_POST['role'], $_POST['user_id']];

            // Ajouter le mot de passe si fourni
            if (!empty($_POST['password'])) {
                $sql = "UPDATE Utilisateur SET nom = ?, email = ?, role = ?, mot_de_passe = ? WHERE id = ?";
                $params = [$_POST['nom'], $_POST['email'], $_POST['role'], 
                          password_hash($_POST['password'], PASSWORD_DEFAULT), $_POST['user_id']];
            }

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);

            echo json_encode(['success' => true, 'message' => 'Utilisateur modifié avec succès']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }
}

// Route pour la recherche AJAX
if (isset($_GET['search_ajax'])) {
    $search = '%' . $_GET['search_term'] . '%';
    $stmt = $pdo->prepare("SELECT * FROM Utilisateur WHERE nom LIKE ? OR email LIKE ? ORDER BY created_at DESC");
    $stmt->execute([$search, $search]);
    $users = $stmt->fetchAll();
    
    $html = '';
    foreach ($users as $user) {
        $html .= generateUserRow($user); // Fonction helper à définir
    }
    
    echo $html;
    exit;
}

// Fonction helper pour générer une ligne utilisateur
function generateUserRow($user) {
    return '<tr class="animate__animated animate__fadeIn">
        <td>
            <div style="display: flex; align-items: center; gap: 10px;">
                <div class="user-avatar" style="width: 35px; height: 35px;">
                    <i class="fas fa-user"></i>
                </div>
                '.htmlspecialchars($user['nom']).'
            </div>
        </td>
        <td>'.htmlspecialchars($user['email']).'</td>
        <td>
            <span class="role-badge '.($user['role'] === 'admin' ? 'role-admin' : 'role-vendeur').'">
                <i class="fas '.($user['role'] === 'admin' ? 'fa-user-shield' : 'fa-user-tag').'"></i>
                '.ucfirst($user['role']).'
            </span>
        </td>
        <td>'.date('d/m/Y H:i', strtotime($user['created_at'])).'</td>
        <td>
            <div class="action-buttons">
                <button class="btn-action btn-edit" onclick="editUser('.$user['id'].')">
                    <i class="fas fa-edit"></i> Modifier
                </button>
                <button class="btn-action btn-delete" onclick="deleteUser('.$user['id'].')">
                    <i class="fas fa-trash"></i> Supprimer
                </button>
            </div>
        </td>
    </tr>';
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stockyy - Utilisateurs</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/animate.css@4.1.1/animate.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11.0.18/dist/sweetalert2.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.0.18/dist/sweetalert2.all.min.js"></script>
    <style>
        .table-container {
            background-color: white;
            border-radius: 10px;
            box-shadow: var(--card-shadow);
            margin: 20px 0;
            overflow: hidden;
            animation: fadeIn 0.5s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
        }

        .data-table th,
        .data-table td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        .data-table th {
            background-color: #f8f9fa;
            font-weight: 600;
            color: var(--text-color);
        }

        .data-table tr {
            transition: all 0.3s ease;
        }

        .data-table tr:hover {
            background-color: #f8f9fa;
            transform: scale(1.01);
        }

        .role-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .role-admin {
            background-color: rgba(67, 97, 238, 0.1);
            color: var(--primary-color);
        }

        .role-vendeur {
            background-color: rgba(42, 157, 143, 0.1);
            color: var(--success-color);
        }

        .action-buttons {
            display: flex;
            gap: 8px;
        }

        .btn-action {
            padding: 6px 12px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 5px;
            transition: all 0.3s ease;
        }

        .btn-edit {
            background-color: var(--warning-color);
            color: var(--text-color);
        }

        .btn-delete {
            background-color: var(--danger-color);
            color: white;
        }

        .btn-action:hover {
            transform: translateY(-2px);
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }

        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: var(--card-shadow);
            display: flex;
            align-items: center;
            gap: 15px;
            transition: all 0.3s ease;
            animation: slideIn 0.5s ease;
        }

        @keyframes slideIn {
            from { opacity: 0; transform: translateX(-20px); }
            to { opacity: 1; transform: translateX(0); }
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
        }

        .stat-info h3 {
            font-size: 14px;
            color: var(--text-light);
            margin-bottom: 5px;
        }

        .stat-info .value {
            font-size: 24px;
            font-weight: 600;
            color: var(--text-color);
        }

        /* Modal styles */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 1000;
        }

        .modal-content {
            position: relative;
            background-color: white;
            margin: 10% auto;
            padding: 20px;
            width: 90%;
            max-width: 500px;
            border-radius: 10px;
            box-shadow: var(--card-shadow);
            animation: modalSlideIn 0.3s ease;
        }

        @keyframes modalSlideIn {
            from { transform: translateY(-100px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        .close-modal {
            position: absolute;
            right: 20px;
            top: 20px;
            font-size: 24px;
            cursor: pointer;
            color: var(--text-light);
            transition: all 0.3s ease;
        }

        .close-modal:hover {
            color: var(--danger-color);
            transform: rotate(90deg);
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            color: var(--text-color);
        }

        .form-group input,
        .form-group select {
            width: 80%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
            transition: all 0.3s ease;
        }

        .form-group input:focus,
        .form-group select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 2px rgba(67, 97, 238, 0.1);
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Sidebar reste le même -->
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
                <div class="menu-item active">
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
                <div class="menu-item" onclick="window.location.href='reports.php'">
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
                    <input type="text" placeholder="Rechercher un utilisateur...">
                </div>
                <div class="header-actions">
                    <button class="btn-primary" onclick="showAddUserModal()">
                        <i class="fas fa-user-plus"></i> Nouvel Utilisateur
                    </button>
                </div>
            </div>

            <div class="content-wrapper">
                <h1 class="page-title">Gestion des Utilisateurs</h1>

                <!-- Statistiques -->
                <div class="stats-container">
                    <div class="stat-card">
                        <div class="stat-icon" style="background-color: rgba(67, 97, 238, 0.1); color: var(--primary-color);">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="stat-info">
                            <h3>Total Utilisateurs</h3>
                            <div class="value"><?php echo $total_users; ?></div>
                        </div>
                    </div>

                    <?php
                    // Compter les admins
                    $stmt = $pdo->query("SELECT COUNT(*) FROM Utilisateur WHERE role = 'admin'");
                    $total_admins = $stmt->fetchColumn();
                    ?>
                    <div class="stat-card">
                        <div class="stat-icon" style="background-color: rgba(230, 57, 70, 0.1); color: var(--danger-color);">
                            <i class="fas fa-user-shield"></i>
                        </div>
                        <div class="stat-info">
                            <h3>Administrateurs</h3>
                            <div class="value"><?php echo $total_admins; ?></div>
                        </div>
                    </div>

                    <?php
                    // Compter les vendeurs
                    $stmt = $pdo->query("SELECT COUNT(*) FROM Utilisateur WHERE role = 'vendeur'");
                    $total_vendeurs = $stmt->fetchColumn();
                    ?>
                    <div class="stat-card">
                        <div class="stat-icon" style="background-color: rgba(42, 157, 143, 0.1); color: var(--success-color);">
                            <i class="fas fa-user-tag"></i>
                        </div>
                        <div class="stat-info">
                            <h3>Vendeurs</h3>
                            <div class="value"><?php echo $total_vendeurs; ?></div>
                        </div>
                    </div>
                </div>

                <!-- Table des utilisateurs -->
                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th><i class="fas fa-user"></i> Nom</th>
                                <th><i class="fas fa-envelope"></i> Email</th>
                                <th><i class="fas fa-user-tag"></i> Rôle</th>
                                <th><i class="fas fa-calendar-alt"></i> Date création</th>
                                <th><i class="fas fa-cog"></i> Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($utilisateurs as $utilisateur): ?>
                            <tr class="animate__animated animate__fadeIn">
                                <td>
                                    <div style="display: flex; align-items: center; gap: 10px;">
                                        <div class="user-avatar" style="width: 35px; height: 35px;">
                                            <i class="fas fa-user"></i>
                                        </div>
                                        <?php echo htmlspecialchars($utilisateur['nom']); ?>
                                    </div>
                                </td>
                                <td><?php echo htmlspecialchars($utilisateur['email']); ?></td>
                                <td>
                                    <span class="role-badge <?php echo $utilisateur['role'] === 'admin' ? 'role-admin' : 'role-vendeur'; ?>">
                                        <i class="fas <?php echo $utilisateur['role'] === 'admin' ? 'fa-user-shield' : 'fa-user-tag'; ?>"></i>
                                        <?php echo ucfirst($utilisateur['role']); ?>
                                    </span>
                                </td>
                                <td><?php echo date('d/m/Y H:i', strtotime($utilisateur['created_at'])); ?></td>
                                <td>
                                    <div class="action-buttons">
                                        <button class="btn-action btn-edit" onclick="editUser(<?php echo $utilisateur['id']; ?>)">
                                            <i class="fas fa-edit"></i> Modifier
                                        </button>
                                        <button class="btn-action btn-delete" onclick="deleteUser(<?php echo $utilisateur['id']; ?>)">
                                            <i class="fas fa-trash"></i> Supprimer
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="pagination">
                    <button
                        class="pagination-btn <?php echo $page <= 1 ? 'disabled' : ''; ?>"
                        onclick="window.location.href='?page=<?php echo $page-1; ?>'"
                        <?php echo $page <= 1 ? 'disabled' : ''; ?>
                    >
                        <i class="fas fa-chevron-left"></i> Précédent
                    </button>

                    <span class="page-info">
                        Page <?php echo $page; ?> sur <?php echo $total_pages; ?>
                    </span>

                    <button
                        class="pagination-btn <?php echo $page >= $total_pages ? 'disabled' : ''; ?>"
                        onclick="window.location.href='?page=<?php echo $page+1; ?>'"
                        <?php echo $page >= $total_pages ? 'disabled' : ''; ?>
                    >
                        Suivant <i class="fas fa-chevron-right"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Nouvel Utilisateur -->
    <div id="addUserModal" class="modal">
        <div class="modal-content">
            <span class="close-modal" onclick="closeAddUserModal()">&times;</span>
            <h2><i class="fas fa-user-plus"></i> Nouvel Utilisateur</h2>
            <form id="addUserForm">
                <input type="hidden" name="action" value="add_user">
                <div class="form-group">
                    <label for="nom"><i class="fas fa-user"></i> Nom</label>
                    <input type="text" id="nom" name="nom" required>
                </div>
                <div class="form-group">
                    <label for="email"><i class="fas fa-envelope"></i> Email</label>
                    <input type="email" id="email" name="email" required>
                </div>
                <div class="form-group">
                    <label for="password"><i class="fas fa-lock"></i> Mot de passe</label>
                    <input type="password" id="password" name="password" required>
                </div>
                <div class="form-group">
                    <label for="role"><i class="fas fa-user-tag"></i> Rôle</label>
                    <select id="role" name="role" required>
                        <option value="vendeur">Vendeur</option>
                        <option value="admin">Administrateur</option>
                    </select>
                </div>
                <button type="submit" class="btn-submit">
                    <i class="fas fa-save"></i> Enregistrer
                </button>
            </form>
        </div>
    </div>

    <script src="../assets/js/main.js"></script>
    <script>
        function showAddUserModal() {
            document.getElementById('addUserModal').style.display = 'block';
            document.querySelector('.modal-content').classList.add('animate__animated', 'animate__fadeInDown');
        }

        function closeAddUserModal() {
            const modalContent = document.querySelector('.modal-content');
            modalContent.classList.remove('animate__fadeInDown');
            modalContent.classList.add('animate__fadeOutUp');

            setTimeout(() => {
                document.getElementById('addUserModal').style.display = 'none';
                modalContent.classList.remove('animate__fadeOutUp');
            }, 300);
        }

        document.getElementById('addUserForm').addEventListener('submit', async function(event) {
            event.preventDefault();

            const submitBtn = this.querySelector('.btn-submit');
            const originalContent = submitBtn.innerHTML;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Traitement...';
            submitBtn.disabled = true;

            try {
                const formData = new FormData(this);
                const response = await fetch('', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    // Afficher le message de succès
                    Swal.fire({
                        icon: 'success',
                        title: 'Succès!',
                        text: result.message,
                        showConfirmButton: false,
                        timer: 1500
                    }).then(() => {
                        // Recharger la page
                        window.location.reload();
                    });
                } else {
                    throw new Error(result.message);
                }

            } catch (error) {
                Swal.fire({
                    icon: 'error',
                    title: 'Erreur!',
                    text: error.message,
                    confirmButtonColor: '#3085d6'
                });
            } finally {
                submitBtn.innerHTML = originalContent;
                submitBtn.disabled = false;
            }
        });

        // Fermer le modal si on clique en dehors
        window.onclick = function(event) {
            const modal = document.getElementById('addUserModal');
            if (event.target == modal) {
                closeAddUserModal();
            }
        }

        // Animation des lignes du tableau au scroll
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('animate__fadeIn');
                }
            });
        });

        document.querySelectorAll('.data-table tr').forEach((tr) => {
            observer.observe(tr);
        });

        // Fonction de recherche avec debounce
        const searchInput = document.querySelector('.search-bar input');
        let searchTimeout;

        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                const searchTerm = this.value.trim();
                searchUsers(searchTerm);
            }, 300);
        });

        async function searchUsers(term) {
            try {
                const response = await fetch(`?search_ajax=1&search_term=${encodeURIComponent(term)}`);
                const html = await response.text();
                document.querySelector('.data-table tbody').innerHTML = html;
            } catch (error) {
                console.error('Erreur de recherche:', error);
            }
        }

        // Fonction de suppression
        async function deleteUser(id) {
            const result = await Swal.fire({
                title: 'Êtes-vous sûr ?',
                text: "Cette action est irréversible !",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Oui, supprimer',
                cancelButtonText: 'Annuler',
                showLoaderOnConfirm: true,
                preConfirm: async () => {
                    try {
                        const formData = new FormData();
                        formData.append('action', 'delete_user');
                        formData.append('user_id', id);

                        const response = await fetch('', {
                            method: 'POST',
                            body: formData
                        });

                        const result = await response.json();
                        if (!result.success) throw new Error(result.message);
                        return result;
                    } catch (error) {
                        Swal.showValidationMessage(`Erreur: ${error.message}`);
                    }
                }
            });

            if (result.isConfirmed) {
                const row = document.querySelector(`[data-user-id="${id}"]`).closest('tr');
                row.classList.add('animate__fadeOutRight');

                await new Promise(resolve => setTimeout(resolve, 500));

                Swal.fire({
                    title: 'Supprimé !',
                    text: 'L\'utilisateur a été supprimé avec succès.',
                    icon: 'success',
                    timer: 1500,
                    showConfirmButton: false
                });

                await searchUsers(searchInput.value.trim());
            }
        }

        // Fonction de modification
        async function editUser(id) {
            const userRow = document.querySelector(`[data-user-id="${id}"]`).closest('tr');
            const userData = {
                nom: userRow.querySelector('td:first-child').textContent.trim(),
                email: userRow.querySelector('td:nth-child(2)').textContent.trim(),
                role: userRow.querySelector('.role-badge').textContent.trim().toLowerCase()
            };

            const { value: formValues } = await Swal.fire({
                title: 'Modifier l\'utilisateur',
                html: `
                    <div class="form-group">
                        <label><i class="fas fa-user"></i> Nom</label>
                        <input id="swal-nom" class="swal2-input" value="${userData.nom}">
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-envelope"></i> Email</label>
                        <input id="swal-email" class="swal2-input" value="${userData.email}">
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-lock"></i> Nouveau mot de passe (optionnel)</label>
                        <input type="password" id="swal-password" class="swal2-input">
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-user-tag"></i> Rôle</label>
                        <select id="swal-role" class="swal2-select">
                            <option value="vendeur" ${userData.role === 'vendeur' ? 'selected' : ''}>Vendeur</option>
                            <option value="admin" ${userData.role === 'admin' ? 'selected' : ''}>Administrateur</option>
                        </select>
                    </div>
                `,
                focusConfirm: false,
                showCancelButton: true,
                confirmButtonText: 'Modifier',
                cancelButtonText: 'Annuler',
                preConfirm: () => {
                    return {
                        nom: document.getElementById('swal-nom').value,
                        email: document.getElementById('swal-email').value,
                        password: document.getElementById('swal-password').value,
                        role: document.getElementById('swal-role').value
                    }
                }
            });

            if (formValues) {
                try {
                    const formData = new FormData();
                    formData.append('action', 'edit_user');
                    formData.append('user_id', id);
                    formData.append('nom', formValues.nom);
                    formData.append('email', formValues.email);
                    formData.append('role', formValues.role);
                    if (formValues.password) {
                        formData.append('password', formValues.password);
                    }

                    const response = await fetch('', {
                        method: 'POST',
                        body: formData
                    });

                    const result = await response.json();

                    if (result.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Succès!',
                            text: result.message,
                            timer: 1500,
                            showConfirmButton: false
                        });
                        await searchUsers(searchInput.value.trim());
                    } else {
                        throw new Error(result.message);
                    }
                } catch (error) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Erreur!',
                        text: error.message
                    });
                }
            }
        }

        // Ajouter les attributs data aux lignes du tableau
        document.querySelectorAll('.data-table tr').forEach(tr => {
            const userId = tr.querySelector('.btn-edit')?.getAttribute('onclick')?.match(/\d+/)?.[0];
            if (userId) tr.setAttribute('data-user-id', userId);
        });
    </script>
</body>
</html>