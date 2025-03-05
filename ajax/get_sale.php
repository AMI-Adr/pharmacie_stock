<?php
session_start();
require_once '../includes/db.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    exit(json_encode(['error' => 'Non autorisé']));
}

$id = $_GET['id'] ?? null;

if (!$id) {
    exit(json_encode(['error' => 'ID manquant']));
}

$stmt = $pdo->prepare("SELECT * FROM Ventes WHERE id = ?");
$stmt->execute([$id]);
$vente = $stmt->fetch(PDO::FETCH_ASSOC);

echo json_encode($vente); 