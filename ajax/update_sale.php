<?php
session_start();
require_once '../includes/db.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    exit(json_encode(['success' => false, 'message' => 'Non autorisé']));
}

$data = json_decode(file_get_contents('php://input'), true);

try {
    $pdo->beginTransaction();

    // Récupérer l'ancienne vente
    $stmt = $pdo->prepare("SELECT product_id, quantite FROM Ventes WHERE id = ?");
    $stmt->execute([$data['sale_id']]);
    $oldSale = $stmt->fetch();

    // Restaurer l'ancien stock
    $stmt = $pdo->prepare("UPDATE Produit SET quantite = quantite + ? WHERE id = ?");
    $stmt->execute([$oldSale['quantite'], $oldSale['product_id']]);

    // Vérifier le nouveau stock disponible
    $stmt = $pdo->prepare("SELECT quantite, prix FROM Produit WHERE id = ?");
    $stmt->execute([$data['product_id']]);
    $produit = $stmt->fetch();

    if ($produit['quantite'] < $data['quantity']) {
        throw new Exception('Stock insuffisant');
    }

    // Mettre à jour le stock
    $stmt = $pdo->prepare("UPDATE Produit SET quantite = quantite - ? WHERE id = ?");
    $stmt->execute([$data['quantity'], $data['product_id']]);

    // Mettre à jour la vente
    $prix_total = $produit['prix'] * $data['quantity'];
    $stmt = $pdo->prepare("UPDATE Ventes SET product_id = ?, quantite = ?, prix_total = ? WHERE id = ?");
    $stmt->execute([$data['product_id'], $data['quantity'], $prix_total, $data['sale_id']]);

    $pdo->commit();
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} 