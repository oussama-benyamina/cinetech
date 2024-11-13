<?php
session_start();
require_once 'db_connect.php';

if (!isset($_SESSION['user_id']) || !isset($_POST['anime_id'])) {
    echo json_encode(['success' => false, 'message' => 'Utilisateur non connecté ou ID d\'anime manquant']);
    exit;
}

$user_id = $_SESSION['user_id'];
$anime_id = $_POST['anime_id'];

try {
    // Vérifier si l'anime est déjà en favori
    $stmt = $pdo->prepare("SELECT * FROM favorites WHERE user_id = ? AND anime_id = ?");
    $stmt->execute([$user_id, $anime_id]);
    $favorite = $stmt->fetch();

    if ($favorite) {
        // Si l'anime est déjà en favori, le supprimer
        $stmt = $pdo->prepare("DELETE FROM favorites WHERE user_id = ? AND anime_id = ?");
        $stmt->execute([$user_id, $anime_id]);
        echo json_encode(['success' => true, 'action' => 'removed']);
    } else {
        // Sinon, l'ajouter aux favoris
        $stmt = $pdo->prepare("INSERT INTO favorites (user_id, anime_id) VALUES (?, ?)");
        $stmt->execute([$user_id, $anime_id]);
        echo json_encode(['success' => true, 'action' => 'added']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}