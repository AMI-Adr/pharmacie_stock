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

// Configuration de la pagination
$ventes_par_page = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $ventes_par_page;

// Compter le nombre total de ventes
$stmt = $pdo->query("SELECT COUNT(*) FROM Ventes");
$total_ventes = $stmt->fetchColumn();
$total_pages = ceil($total_ventes / $ventes_par_page);

// Récupérer les ventes pour la page actuelle
$stmt = $pdo->prepare("SELECT v.*, p.nom as produit_nom, p.reference, u.nom as vendeur_nom
                       FROM Ventes v
                       JOIN Produit p ON v.product_id = p.id
                       JOIN Utilisateur u ON v.user_id = u.id
                       ORDER BY v.created_at DESC
                       LIMIT :limit OFFSET :offset");
$stmt->bindValue(':limit', $ventes_par_page, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$ventes = $stmt->fetchAll();

// Récupérer la liste des produits pour le formulaire d'ajout
$stmt = $pdo->query("SELECT id, nom, prix, quantite FROM Produit WHERE quantite > 0");
$produits_disponibles = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stockyy - Ventes</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/animate.css@4.1.1/animate.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11.0.19/dist/sweetalert2.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.0.19/dist/sweetalert2.all.min.js"></script>
    <style>
        /* Styles spécifiques à la page ventes */
        .table-container {
            background-color: white;
            border-radius: 10px;
            box-shadow: var(--card-shadow);
            margin: 20px 0;
            overflow: hidden;
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

        .data-table tr:hover {
            background-color: #f8f9fa;
        }

        .data-table td {
            color: var(--text-color);
        }

        .status-badge {
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 12px;
            font-weight: 500;
        }

        .status-success {
            background-color: rgba(42, 157, 143, 0.1);
            color: var(--success-color);
        }

        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            margin: 20px 0;
            gap: 10px;
        }

        .pagination-btn {
            padding: 8px 15px;
            border: 1px solid var(--primary-color);
            border-radius: 5px;
            background-color: white;
            color: var(--primary-color);
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .pagination-btn:hover,
        .pagination-btn.active {
            background-color: var(--primary-color);
            color: white;
        }

        .pagination-btn.disabled {
            border-color: #ddd;
            color: #999;
            cursor: not-allowed;
        }

        .pagination-btn.disabled:hover {
            background-color: white;
            color: #999;
        }

        .page-info {
            color: var(--text-light);
            font-size: 14px;
        }

        /* Styles du modal existants... */
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
        }

        .close-modal {
            position: absolute;
            right: 20px;
            top: 20px;
            font-size: 24px;
            cursor: pointer;
            color: var(--text-light);
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            color: var(--text-color);
        }

        .form-group select,
        .form-group input {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
        }

        .btn-submit {
            width: 100%;
            padding: 10px;
            background-color: var(--primary-color);
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            margin-top: 10px;
        }

        .btn-submit:hover {
            background-color: var(--secondary-color);
        }

        .btn-export {
            background-color: #e74c3c;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 5px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            margin-right: 10px;
            transition: all 0.3s ease;
        }

        .btn-export:hover {
            background-color: #c0392b;
            transform: translateY(-2px);
        }

        .btn-export i {
            font-size: 1.1em;
        }

        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }

        .exporting {
            animation: pulse 1s infinite;
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
                <div class="menu-item active">
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
                    <input type="text" placeholder="Rechercher une vente...">
                </div>
                <div class="header-actions">
                    <button class="btn-export" onclick="exportSalesPDF()">
                        <i class="fas fa-file-pdf"></i> Exporter PDF
                    </button>
                    <button class="btn-primary" onclick="showAddSaleModal()">
                        <i class="fas fa-plus"></i> Nouvelle Vente
                    </button>
                </div>
            </div>

            <div class="content-wrapper">
                <h1 class="page-title">Gestion des Ventes</h1>

                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Date</th>
                                <th>Produit</th>
                                <th>Référence</th>
                                <th>Vendeur</th>
                                <th>Quantité</th>
                                <th>Total</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($ventes as $vente): ?>
                            <tr>
                                <td>#<?php echo $vente['id']; ?></td>
                                <td><?php echo date('d/m/Y H:i', strtotime($vente['created_at'])); ?></td>
                                <td><?php echo htmlspecialchars($vente['produit_nom']); ?></td>
                                <td><?php echo htmlspecialchars($vente['reference']); ?></td>
                                <td><?php echo htmlspecialchars($vente['vendeur_nom']); ?></td>
                                <td><?php echo $vente['quantite']; ?></td>
                                <td><?php echo number_format($vente['prix_total'], 2); ?> €</td>
                                <td>
                                    <button class="btn-edit" onclick="editSale(<?php echo $vente['id']; ?>)">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn-delete" onclick="deleteSale(<?php echo $vente['id']; ?>)">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($ventes)): ?>
                            <tr>
                                <td colspan="8" style="text-align: center;">Aucune vente trouvée</td>
                            </tr>
                            <?php endif; ?>
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

    <!-- Modal Nouvelle Vente -->
    <div id="addSaleModal" class="modal">
        <div class="modal-content">
            <span class="close-modal" onclick="closeAddSaleModal()">&times;</span>
            <h2>Nouvelle Vente</h2>
            <form id="addSaleForm" onsubmit="return handleAddSale(event)">
                <div class="form-group">
                    <label for="product">Produit</label>
                    <select id="product" name="product" required>
                        <option value="">Sélectionner un produit</option>
                        <?php foreach ($produits_disponibles as $produit): ?>
                        <option value="<?php echo $produit['id']; ?>" 
                                data-prix="<?php echo $produit['prix']; ?>"
                                data-max="<?php echo $produit['quantite']; ?>">
                            <?php echo htmlspecialchars($produit['nom']); ?> 
                            (Stock: <?php echo $produit['quantite']; ?>)
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="quantity">Quantité</label>
                    <input type="number" id="quantity" name="quantity" min="1" required>
                </div>
                <button type="submit" class="btn-submit">Enregistrer la vente</button>
            </form>
        </div>
    </div>

    <script src="../assets/js/main.js"></script>
    <script>
        // Fonctions pour la gestion des ventes
        function showAddSaleModal() {
            document.getElementById('addSaleModal').style.display = 'block';
        }

        function closeAddSaleModal() {
            document.getElementById('addSaleModal').style.display = 'none';
        }

        function handleAddSale(event) {
            event.preventDefault();
            
            const productSelect = document.getElementById('product');
            const quantityInput = document.getElementById('quantity');
            const selectedOption = productSelect.options[productSelect.selectedIndex];
            
            // Vérification de base
            if (!productSelect.value || !quantityInput.value) {
                Swal.fire({
                    icon: 'error',
                    title: 'Erreur',
                    text: 'Veuillez remplir tous les champs',
                    showConfirmButton: false,
                    timer: 1500
                });
                return false;
            }

            // Vérification de la quantité disponible
            const maxQuantity = parseInt(selectedOption.getAttribute('data-max'));
            if (parseInt(quantityInput.value) > maxQuantity) {
                Swal.fire({
                    icon: 'error',
                    title: 'Stock insuffisant',
                    text: `La quantité maximum disponible est de ${maxQuantity} unités`,
                    showConfirmButton: false,
                    timer: 2000
                });
                return false;
            }

            // Animation de chargement
            const submitBtn = document.querySelector('.btn-submit');
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Traitement...';
            submitBtn.disabled = true;

            // Préparation des données
            const formData = {
                product_id: productSelect.value,
                quantity: quantityInput.value
            };

            // Envoi de la requête AJAX
            fetch('../ajax/add_sale.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(formData)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Animation de succès
                    Swal.fire({
                        icon: 'success',
                        title: 'Vente enregistrée !',
                        text: 'La vente a été enregistrée avec succès',
                        showConfirmButton: false,
                        timer: 1500
                    });

                    // Ajout de la nouvelle ligne avec animation
                    const tbody = document.querySelector('.data-table tbody');
                    const newRow = document.createElement('tr');
                    newRow.classList.add('animate__animated', 'animate__fadeInDown');
                    
                    newRow.innerHTML = `
                        <td>#${data.sale.id}</td>
                        <td>${new Date().toLocaleString()}</td>
                        <td>${escapeHtml(selectedOption.text.split('(')[0].trim())}</td>
                        <td>${escapeHtml(data.sale.reference)}</td>
                        <td>${escapeHtml(data.sale.vendeur_nom)}</td>
                        <td>${quantityInput.value}</td>
                        <td>${(data.sale.prix_total * 4500).toLocaleString()} Ar</td>
                        <td>
                            <button class="btn-edit" onclick="editSale(${data.sale.id})">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="btn-delete" onclick="deleteSale(${data.sale.id})">
                                <i class="fas fa-trash"></i>
                            </button>
                        </td>
                    `;

                    // Insérer la nouvelle ligne au début du tableau
                    if (tbody.firstChild) {
                        tbody.insertBefore(newRow, tbody.firstChild);
                    } else {
                        tbody.appendChild(newRow);
                    }

                    // Mise à jour du stock dans le select
                    const updatedQuantity = maxQuantity - parseInt(quantityInput.value);
                    selectedOption.setAttribute('data-max', updatedQuantity);
                    selectedOption.text = `${selectedOption.text.split('(')[0].trim()} (Stock: ${updatedQuantity})`;

                    // Réinitialisation du formulaire
                    document.getElementById('addSaleForm').reset();
            closeAddSaleModal();
                } else {
                    throw new Error(data.message || 'Erreur lors de l\'enregistrement');
                }
            })
            .catch(error => {
                Swal.fire({
                    icon: 'error',
                    title: 'Erreur !',
                    text: error.message,
                    showConfirmButton: false,
                    timer: 2000
                });
            })
            .finally(() => {
                // Restauration du bouton
                submitBtn.innerHTML = 'Enregistrer la vente';
                submitBtn.disabled = false;
            });

            return false;
        }

        function editSale(id) {
            // À implémenter : Logique d'édition de vente
            alert('Fonctionnalité à venir : Modifier la vente ' + id);
        }

        function deleteSale(id) {
            if (confirm('Êtes-vous sûr de vouloir supprimer cette vente ?')) {
                // À implémenter : Logique de suppression de vente
                alert('Fonctionnalité à venir : Supprimer la vente ' + id);
            }
        }

        // Fermer le modal si on clique en dehors
        window.onclick = function(event) {
            const modal = document.getElementById('addSaleModal');
            if (event.target == modal) {
                closeAddSaleModal();
            }
        }

        // Mise à jour de la quantité maximum en fonction du produit sélectionné
        document.getElementById('product').addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            const maxQuantity = selectedOption.getAttribute('data-max');
            const quantityInput = document.getElementById('quantity');
            quantityInput.max = maxQuantity;
            quantityInput.placeholder = `Max: ${maxQuantity}`;
        });

        // Fonction de recherche en temps réel
        const searchInput = document.querySelector('.search-bar input');
        let searchTimeout;

        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                const searchTerm = this.value;
                searchSales(searchTerm);
            }, 300);
        });

        function searchSales(searchTerm) {
            fetch(`../ajax/search_sales.php?term=${encodeURIComponent(searchTerm)}`)
                .then(response => response.json())
                .then(data => {
                    const tbody = document.querySelector('.data-table tbody');
                    tbody.innerHTML = '';

                    if (data.length === 0) {
                        tbody.innerHTML = `
                            <tr>
                                <td colspan="8" style="text-align: center;">Aucune vente trouvée</td>
                            </tr>`;
                        return;
                    }

                    data.forEach(vente => {
                        tbody.innerHTML += `
                            <tr class="animate__animated animate__fadeIn">
                                <td>#${vente.id}</td>
                                <td>${new Date(vente.created_at).toLocaleString()}</td>
                                <td>${escapeHtml(vente.produit_nom)}</td>
                                <td>${escapeHtml(vente.reference)}</td>
                                <td>${escapeHtml(vente.vendeur_nom)}</td>
                                <td>${vente.quantite}</td>
                                <td>${parseFloat(vente.prix_total).toFixed(2)} €</td>
                                <td>
                                    <button class="btn-edit" onclick="editSale(${vente.id})">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn-delete" onclick="deleteSale(${vente.id})">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>`;
                    });
                })
                .catch(error => {
                    console.error('Erreur:', error);
                    Swal.fire({
                        icon: 'error',
                        title: 'Erreur',
                        text: 'Une erreur est survenue lors de la recherche'
                    });
                });
        }

        // Fonction de suppression
        function deleteSale(id) {
            Swal.fire({
                title: 'Êtes-vous sûr ?',
                text: "Cette action est irréversible !",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Oui, supprimer',
                cancelButtonText: 'Annuler'
            }).then((result) => {
                if (result.isConfirmed) {
                    fetch('../ajax/delete_sale.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({ id: id })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            const row = document.querySelector(`tr:has(button[onclick="deleteSale(${id})"])`);
                            row.classList.add('animate__animated', 'animate__fadeOutRight');
                            setTimeout(() => row.remove(), 500);
                            Swal.fire(
                                'Supprimé !',
                                'La vente a été supprimée avec succès.',
                                'success'
                            );
                        } else {
                            throw new Error(data.message || 'Erreur lors de la suppression');
                        }
                    })
                    .catch(error => {
                        Swal.fire(
                            'Erreur !',
                            error.message,
                            'error'
                        );
                    });
                }
            });
        }

        // Fonction de modification
        function editSale(id) {
            fetch(`../ajax/get_sale.php?id=${id}`)
                .then(response => response.json())
                .then(sale => {
                    Swal.fire({
                        title: 'Modifier la vente',
                        html: `
                            <select id="edit-product" class="swal2-input">
                                ${Array.from(document.getElementById('product').options)
                                    .map(opt => `<option value="${opt.value}" ${sale.product_id == opt.value ? 'selected' : ''}>${opt.text}</option>`)
                                    .join('')}
                            </select>
                            <input type="number" id="edit-quantity" class="swal2-input" value="${sale.quantite}" min="1">
                        `,
                        showCancelButton: true,
                        confirmButtonText: 'Modifier',
                        cancelButtonText: 'Annuler',
                        preConfirm: () => {
                            return {
                                product_id: document.getElementById('edit-product').value,
                                quantity: document.getElementById('edit-quantity').value,
                                sale_id: id
                            }
                        }
                    }).then((result) => {
                        if (result.isConfirmed) {
                            updateSale(result.value);
                        }
                    });
                });
        }

        function updateSale(data) {
            fetch('../ajax/update_sale.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(data)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Succès',
                        text: 'La vente a été mise à jour avec succès',
                        showConfirmButton: false,
                        timer: 1500
                    });
                    // Rafraîchir la ligne modifiée
                    searchSales(searchInput.value);
                } else {
                    throw new Error(data.message || 'Erreur lors de la mise à jour');
                }
            })
            .catch(error => {
                Swal.fire({
                    icon: 'error',
                    title: 'Erreur',
                    text: error.message
                });
            });
        }

        // Fonction utilitaire pour échapper le HTML
        function escapeHtml(unsafe) {
            return unsafe
                .replace(/&/g, "&amp;")
                .replace(/</g, "&lt;")
                .replace(/>/g, "&gt;")
                .replace(/"/g, "&quot;")
                .replace(/'/g, "&#039;");
        }

        function exportSalesPDF() {
            // Animation du bouton
            const exportBtn = document.querySelector('.btn-export');
            const originalContent = exportBtn.innerHTML;
            exportBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Export en cours...';
            exportBtn.classList.add('exporting');
            exportBtn.disabled = true;

            // Afficher une notification
            Swal.fire({
                title: 'Export en cours',
                html: 'Préparation du PDF...',
                timer: 2000,
                timerProgressBar: true,
                didOpen: () => {
                    Swal.showLoading();
                    // Déclencher le téléchargement
                    window.location.href = '../ajax/export_sales_pdf.php';

                    // Restaurer le bouton après un délai
                    setTimeout(() => {
                        exportBtn.innerHTML = originalContent;
                        exportBtn.classList.remove('exporting');
                        exportBtn.disabled = false;

                        Swal.fire({
                            icon: 'success',
                            title: 'Export réussi !',
                            text: 'Le PDF a été généré avec succès',
                            showConfirmButton: false,
                            timer: 1500
                        });
                    }, 2000);
                }
            });
        }
    </script>
</body>
</html>