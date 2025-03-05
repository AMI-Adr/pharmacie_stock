<?php
session_start();
require_once '../includes/db.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    exit(json_encode(['success' => false, 'message' => 'Non autorisé']));
}

$data = json_decode(file_get_contents('php://input'), true);
$id = $data['id'] ?? null;

if (!$id) {
    exit(json_encode(['success' => false, 'message' => 'ID de vente manquant']));
}

try {
    $pdo->beginTransaction();

    // Récupérer les informations de la vente
    $stmt = $pdo->prepare("SELECT product_id, quantite FROM Ventes WHERE id = ?");
    $stmt->execute([$id]);
    $vente = $stmt->fetch();

    if ($vente) {
        // Restaurer la quantité dans le stock
        $stmt = $pdo->prepare("UPDATE Produit SET quantite = quantite + ? WHERE id = ?");
        $stmt->execute([$vente['quantite'], $vente['product_id']]);

        // Supprimer la vente
        $stmt = $pdo->prepare("DELETE FROM Ventes WHERE id = ?");
        $stmt->execute([$id]);
    }

    $pdo->commit();
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => 'Erreur lors de la suppression']);
} 