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

// Récupérer tous les produits
$stmt = $pdo->query("SELECT * FROM Produit ORDER BY nom ASC");
$produits = $stmt->fetchAll();

// Ajouter après la vérification de connexion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_product') {
    try {
        // Validation des données
        $required_fields = ['nom', 'categorie', 'reference', 'prix', 'quantite', 'fournisseur'];
        foreach ($required_fields as $field) {
            if (empty($_POST[$field])) {
                throw new Exception("Le champ " . ucfirst($field) . " est obligatoire");
            }
        }

        // Vérifier si la référence existe déjà
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM Produit WHERE reference = ?");
        $stmt->execute([$_POST['reference']]);
        if ($stmt->fetchColumn() > 0) {
            throw new Exception("Cette référence existe déjà");
        }

        // Validation du prix et de la quantité
        if (!is_numeric($_POST['prix']) || $_POST['prix'] <= 0) {
            throw new Exception("Le prix doit être un nombre positif");
        }
        if (!is_numeric($_POST['quantite']) || $_POST['quantite'] < 0) {
            throw new Exception("La quantité doit être un nombre positif ou nul");
        }

        // Insérer le nouveau produit
        $stmt = $pdo->prepare("INSERT INTO Produit (nom, categorie, reference, prix, quantite, fournisseur) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $_POST['nom'],
            $_POST['categorie'],
            $_POST['reference'],
            $_POST['prix'],
            $_POST['quantite'],
            $_POST['fournisseur']
        ]);

        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => 'Produit ajouté avec succès']);
        exit;

    } catch (Exception $e) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        exit;
    }
}

// Gestion de la recherche
if (isset($_GET['search'])) {
    $search = '%' . $_GET['search'] . '%';
    $stmt = $pdo->prepare("SELECT * FROM Produit WHERE nom LIKE ? OR reference LIKE ? OR categorie LIKE ? ORDER BY nom ASC");
    $stmt->execute([$search, $search, $search]);
    $produits = $stmt->fetchAll();
}

// Gestion de la suppression
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_product') {
    try {
        $stmt = $pdo->prepare("DELETE FROM Produit WHERE id = ?");
        $stmt->execute([$_POST['product_id']]);
        
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => 'Produit supprimé avec succès']);
        exit;
    } catch (Exception $e) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Erreur lors de la suppression']);
        exit;
    }
}

// Gestion de la modification
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit_product') {
    try {
        $stmt = $pdo->prepare("UPDATE Produit SET nom = ?, categorie = ?, reference = ?, prix = ?, quantite = ?, fournisseur = ? WHERE id = ?");
        $stmt->execute([
            $_POST['nom'],
            $_POST['categorie'],
            $_POST['reference'],
            $_POST['prix'],
            $_POST['quantite'],
            $_POST['fournisseur'],
            $_POST['product_id']
        ]);
        
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => 'Produit modifié avec succès']);
        exit;
    } catch (Exception $e) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Erreur lors de la modification']);
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stockyy - Produits</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/animate.css@4.1.1/animate.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11.0.18/dist/sweetalert2.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.0.18/dist/sweetalert2.all.min.js"></script>
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
                <div class="menu-item active">
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
                    <input type="text" id="searchInput" placeholder="Rechercher un produit..." 
                           value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                </div>
                <div class="header-actions">
                    <button class="btn-primary" onclick="showAddProductModal()">
                        <i class="fas fa-plus"></i> Nouveau Produit
                    </button>
                </div>
            </div>

            <div class="content-wrapper">
                <h1 class="page-title">Gestion des Produits</h1>

                <div class="products-grid">
                    <?php foreach ($produits as $produit): ?>
                    <div class="product-card">
                        <div class="product-header">
                            <h3><?php echo htmlspecialchars($produit['nom']); ?></h3>
                            <span class="reference"><?php echo htmlspecialchars($produit['reference']); ?></span>
                        </div>
                        <div class="product-body">
                            <div class="product-info">
                                <p>Catégorie: <?php echo htmlspecialchars($produit['categorie']); ?></p>
                                <p>Prix: <?php echo number_format($produit['prix'], 2); ?> €</p>
                                <p>Quantité: <?php echo $produit['quantite']; ?></p>
                                <p>Fournisseur: <?php echo htmlspecialchars($produit['fournisseur']); ?></p>
                            </div>
                        </div>
                        <div class="product-footer">
                            <button class="btn-edit" onclick="editProduct(<?php echo $produit['id']; ?>)">
                                <i class="fas fa-edit"></i> Modifier
                            </button>
                            <button class="btn-delete" onclick="deleteProduct(<?php echo $produit['id']; ?>)">
                                <i class="fas fa-trash"></i> Supprimer
                            </button>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="../assets/js/main.js"></script>

    <!-- Modal Nouveau Produit -->
    <div id="addProductModal" class="modal">
        <div class="modal-content">
            <span class="close-modal" onclick="closeAddProductModal()">&times;</span>
            <h2><i class="fas fa-box-open"></i> Nouveau Produit</h2>
            <form id="addProductForm">
                <input type="hidden" name="action" value="add_product">
                <div class="form-group">
                    <label for="nom"><i class="fas fa-tag"></i> Nom du produit</label>
                    <input type="text" id="nom" name="nom" required>
                </div>
                <div class="form-group">
                    <label for="categorie"><i class="fas fa-folder"></i> Catégorie</label>
                    <select id="categorie" name="categorie" required>
                        <option value="">Sélectionner une catégorie</option>
                        <option value="Catégorie A">Catégorie A</option>
                        <option value="Catégorie B">Catégorie B</option>
                        <option value="Catégorie C">Catégorie C</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="reference"><i class="fas fa-barcode"></i> Référence</label>
                    <input type="text" id="reference" name="reference" required>
                </div>
                <div class="form-group">
                    <label for="prix"><i class="fas fa-euro-sign"></i> Prix</label>
                    <input type="number" id="prix" name="prix" step="0.01" min="0" required>
                </div>
                <div class="form-group">
                    <label for="quantite"><i class="fas fa-boxes"></i> Quantité</label>
                    <input type="number" id="quantite" name="quantite" min="0" required>
                </div>
                <div class="form-group">
                    <label for="fournisseur"><i class="fas fa-truck"></i> Fournisseur</label>
                    <input type="text" id="fournisseur" name="fournisseur" required>
                </div>
                <button type="submit" class="btn-submit">
                    <i class="fas fa-save"></i> Enregistrer
                </button>
            </form>
        </div>
    </div>

    <!-- Modal Modifier Produit -->
    <div id="editProductModal" class="modal">
        <div class="modal-content">
            <span class="close-modal" onclick="closeEditProductModal()">&times;</span>
            <h2><i class="fas fa-edit"></i> Modifier le Produit</h2>
            <form id="editProductForm">
                <input type="hidden" name="action" value="edit_product">
                <input type="hidden" name="product_id" id="edit_product_id">
                <div class="form-group">
                    <label for="edit_nom"><i class="fas fa-tag"></i> Nom du produit</label>
                    <input type="text" id="edit_nom" name="nom" required>
                </div>
                <div class="form-group">
                    <label for="edit_categorie"><i class="fas fa-folder"></i> Catégorie</label>
                    <select id="edit_categorie" name="categorie" required>
                        <option value="Catégorie A">Catégorie A</option>
                        <option value="Catégorie B">Catégorie B</option>
                        <option value="Catégorie C">Catégorie C</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="edit_reference"><i class="fas fa-barcode"></i> Référence</label>
                    <input type="text" id="edit_reference" name="reference" required>
                </div>
                <div class="form-group">
                    <label for="edit_prix"><i class="fas fa-euro-sign"></i> Prix</label>
                    <input type="number" id="edit_prix" name="prix" step="0.01" min="0" required>
                </div>
                <div class="form-group">
                    <label for="edit_quantite"><i class="fas fa-boxes"></i> Quantité</label>
                    <input type="number" id="edit_quantite" name="quantite" min="0" required>
                </div>
                <div class="form-group">
                    <label for="edit_fournisseur"><i class="fas fa-truck"></i> Fournisseur</label>
                    <input type="text" id="edit_fournisseur" name="fournisseur" required>
                </div>
                <button type="submit" class="btn-submit">
                    <i class="fas fa-save"></i> Enregistrer les modifications
                </button>
            </form>
        </div>
    </div>

    <!-- Ajouter les styles CSS -->
    <style>
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
        background-color: white;
        margin: 5% auto;
        padding: 20px;
        width: 90%;
        max-width: 500px;
        border-radius: 10px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        position: relative;
    }

    .close-modal {
        position: absolute;
        right: 20px;
        top: 20px;
        font-size: 24px;
        cursor: pointer;
        color: #666;
        transition: color 0.3s ease;
    }

    .close-modal:hover {
        color: var(--danger-color);
    }

    .form-group {
        margin-bottom: 20px;
    }

    .form-group label {
        display: block;
        margin-bottom: 8px;
        color: var(--text-color);
        font-weight: 500;
    }

    .form-group label i {
        margin-right: 8px;
        color: var(--primary-color);
    }

    .form-group input,
    .form-group select {
        width: 100%;
        padding: 10px;
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

    .btn-submit {
        width: 100%;
        padding: 12px;
        background-color: var(--primary-color);
        color: white;
        border: none;
        border-radius: 5px;
        font-size: 16px;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
    }

    .btn-submit:hover {
        background-color: var(--secondary-color);
        transform: translateY(-2px);
    }

    .btn-submit:disabled {
        background-color: #ccc;
        cursor: not-allowed;
    }

    .animate__animated {
        animation-duration: 0.5s;
    }
    </style>

    <!-- Ajouter le script JavaScript -->
    <script>
    function showAddProductModal() {
        document.getElementById('addProductModal').style.display = 'block';
        document.querySelector('.modal-content').classList.add('animate__animated', 'animate__fadeInDown');
    }

    function closeAddProductModal() {
        const modalContent = document.querySelector('.modal-content');
        modalContent.classList.remove('animate__fadeInDown');
        modalContent.classList.add('animate__fadeOutUp');
        
        setTimeout(() => {
            document.getElementById('addProductModal').style.display = 'none';
            modalContent.classList.remove('animate__fadeOutUp');
            document.getElementById('addProductForm').reset();
        }, 300);
    }

    document.getElementById('addProductForm').addEventListener('submit', async function(event) {
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
                Swal.fire({
                    icon: 'success',
                    title: 'Succès!',
                    text: result.message,
                    showConfirmButton: false,
                    timer: 1500,
                    didClose: () => {
                        window.location.reload();
                    }
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
        const modal = document.getElementById('addProductModal');
        if (event.target == modal) {
            closeAddProductModal();
        }
    }

    // Animation des champs du formulaire
    document.querySelectorAll('.form-group input, .form-group select').forEach(input => {
        input.addEventListener('focus', function() {
            this.parentElement.classList.add('animate__animated', 'animate__pulse');
        });
        
        input.addEventListener('blur', function() {
            this.parentElement.classList.remove('animate__animated', 'animate__pulse');
        });
    });

    // Fonction de recherche
    const searchInput = document.getElementById('searchInput');
    let searchTimeout;

    searchInput.addEventListener('input', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            const searchValue = this.value.trim();
            window.location.href = `?search=${encodeURIComponent(searchValue)}`;
        }, 500);
    });

    // Fonction pour modifier un produit
    function editProduct(id) {
        // Récupérer les données du produit
        const productCard = document.querySelector(`[data-product-id="${id}"]`);
        const productData = {
            nom: productCard.querySelector('.product-name').textContent,
            categorie: productCard.querySelector('.product-category').textContent.replace('Catégorie: ', ''),
            reference: productCard.querySelector('.reference').textContent,
            prix: parseFloat(productCard.querySelector('.product-price').textContent.replace('Prix: ', '').replace('€', '')),
            quantite: parseInt(productCard.querySelector('.product-quantity').textContent.replace('Quantité: ', '')),
            fournisseur: productCard.querySelector('.product-supplier').textContent.replace('Fournisseur: ', '')
        };

        // Remplir le formulaire
        document.getElementById('edit_product_id').value = id;
        document.getElementById('edit_nom').value = productData.nom;
        document.getElementById('edit_categorie').value = productData.categorie;
        document.getElementById('edit_reference').value = productData.reference;
        document.getElementById('edit_prix').value = productData.prix;
        document.getElementById('edit_quantite').value = productData.quantite;
        document.getElementById('edit_fournisseur').value = productData.fournisseur;

        // Afficher le modal
        document.getElementById('editProductModal').style.display = 'block';
        document.querySelector('#editProductModal .modal-content').classList.add('animate__animated', 'animate__fadeInDown');
    }

    // Fonction pour supprimer un produit
    function deleteProduct(id) {
        Swal.fire({
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
                    formData.append('action', 'delete_product');
                    formData.append('product_id', id);

                    const response = await fetch('', {
                        method: 'POST',
                        body: formData
                    });

                    const result = await response.json();
                    if (!result.success) {
                        throw new Error(result.message);
                    }
                    return result;
                } catch (error) {
                    Swal.showValidationMessage(`Erreur: ${error.message}`);
                }
            }
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.fire({
                    title: 'Supprimé !',
                    text: 'Le produit a été supprimé avec succès.',
                    icon: 'success',
                    timer: 1500,
                    showConfirmButton: false
                }).then(() => {
                    window.location.reload();
                });
            }
        });
    }

    // Gestion du formulaire de modification
    document.getElementById('editProductForm').addEventListener('submit', async function(event) {
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
                Swal.fire({
                    icon: 'success',
                    title: 'Succès!',
                    text: result.message,
                    showConfirmButton: false,
                    timer: 1500,
                    didClose: () => {
                        window.location.reload();
                    }
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

    // Fermeture du modal de modification
    function closeEditProductModal() {
        const modalContent = document.querySelector('#editProductModal .modal-content');
        modalContent.classList.remove('animate__fadeInDown');
        modalContent.classList.add('animate__fadeOutUp');
        
        setTimeout(() => {
            document.getElementById('editProductModal').style.display = 'none';
            modalContent.classList.remove('animate__fadeOutUp');
            document.getElementById('editProductForm').reset();
        }, 300);
    }

    // Ajouter les attributs data aux cartes produits
    document.querySelectorAll('.product-card').forEach(card => {
        const id = card.querySelector('.btn-edit').getAttribute('onclick').match(/\d+/)[0];
        card.setAttribute('data-product-id', id);
        card.querySelector('h3').classList.add('product-name');
        card.querySelectorAll('.product-info p').forEach(p => {
            if (p.textContent.includes('Catégorie:')) p.classList.add('product-category');
            if (p.textContent.includes('Prix:')) p.classList.add('product-price');
            if (p.textContent.includes('Quantité:')) p.classList.add('product-quantity');
            if (p.textContent.includes('Fournisseur:')) p.classList.add('product-supplier');
        });
    });
    </script>
</body>
</html>