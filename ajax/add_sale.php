<?php
session_start();
require_once '../includes/db.php';

// Vérifier si la requête est en POST et si l'utilisateur est connecté
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Accès non autorisé']);
    exit;
}

// Récupérer et décoder les données JSON
$data = json_decode(file_get_contents('php://input'), true);

try {
    // Début de la transaction
    $pdo->beginTransaction();

    // Vérifier le stock disponible
    $stmt = $pdo->prepare("SELECT prix, quantite, reference FROM Produit WHERE id = ? FOR UPDATE");
    $stmt->execute([$data['product_id']]);
    $produit = $stmt->fetch();

    if (!$produit) {
        throw new Exception('Produit non trouvé');
    }

    if ($produit['quantite'] < $data['quantity']) {
        throw new Exception('Stock insuffisant');
    }

    // Calculer le prix total
    $prix_total = $produit['prix'] * $data['quantity'];

    // Insérer la vente
    $stmt = $pdo->prepare("INSERT INTO Ventes (user_id, product_id, quantite, prix_total, created_at) VALUES (?, ?, ?, ?, NOW())");
    $stmt->execute([$_SESSION['user_id'], $data['product_id'], $data['quantity'], $prix_total]);
    $sale_id = $pdo->lastInsertId();

    // Mettre à jour le stock
    $stmt = $pdo->prepare("UPDATE Produit SET quantite = quantite - ? WHERE id = ?");
    $stmt->execute([$data['quantity'], $data['product_id']]);

    // Récupérer les informations du vendeur
    $stmt = $pdo->prepare("SELECT nom FROM Utilisateur WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $vendeur = $stmt->fetch();

    // Valider la transaction
    $pdo->commit();

    // Préparer la réponse
    $response = [
        'success' => true,
        'message' => 'Vente enregistrée avec succès',
        'sale' => [
            'id' => $sale_id,
            'reference' => $produit['reference'],
            'vendeur_nom' => $vendeur['nom'],
            'prix_total' => $prix_total
        ]
    ];

    echo json_encode($response);

} catch (Exception $e) {
    // Annuler la transaction en cas d'erreur
    $pdo->rollBack();
    
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?> 