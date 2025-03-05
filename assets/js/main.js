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
});

// Fonctions pour la gestion des produits
function showAddProductModal() {
    // À implémenter : Afficher le modal d'ajout de produit
    alert('Fonctionnalité à venir : Ajouter un produit');
}

function editProduct(id) {
    // À implémenter : Éditer un produit
    alert('Fonctionnalité à venir : Éditer le produit ' + id);
}

function deleteProduct(id) {
    // À implémenter : Supprimer un produit
    if (confirm('Êtes-vous sûr de vouloir supprimer ce produit ?')) {
        alert('Fonctionnalité à venir : Supprimer le produit ' + id);
    }
}