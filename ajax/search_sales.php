<?php
session_start();
require_once '../includes/db.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    exit(json_encode(['error' => 'Non autorisé']));
}

$searchTerm = isset($_GET['term']) ? $_GET['term'] : '';

$sql = "SELECT v.*, p.nom as produit_nom, p.reference, u.nom as vendeur_nom
        FROM Ventes v
        JOIN Produit p ON v.product_id = p.id
        JOIN Utilisateur u ON v.user_id = u.id
        WHERE p.nom LIKE :term
        OR p.reference LIKE :term
        OR u.nom LIKE :term
        ORDER BY v.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute(['term' => "%$searchTerm%"]);
$ventes = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($ventes);