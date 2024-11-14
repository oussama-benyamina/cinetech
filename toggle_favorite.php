<?php
session_start();
require_once 'db_connect.php';
require_once 'includes/jikan_client.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || !isset($_POST['anime_id'])) {
    echo json_encode(['success' => false, 'message' => 'Utilisateur non connecté ou ID d\'anime manquant']);
    exit;
}

$user_id = $_SESSION['user_id'];
$mal_id = $_POST['anime_id'];

try {
    // Vérifier si l'anime existe dans la table animes
    $stmt = $pdo->prepare("SELECT id FROM animes WHERE mal_id = ?");
    $stmt->execute([$mal_id]);
    $anime = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$anime) {
        // L'anime n'existe pas, on doit l'ajouter
        $jikan = new JikanAPI();
        $animeDetails = $jikan->getAnimeDetails($mal_id);

        if (isset($animeDetails['data'])) {
            $animeData = $animeDetails['data'];
            $stmt = $pdo->prepare("INSERT INTO animes (mal_id, title, image_url, score, synopsis) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([
                $animeData['mal_id'],
                $animeData['title'],
                $animeData['images']['jpg']['image_url'],
                $animeData['score'],
                $animeData['synopsis']
            ]);
            $anime_id = $pdo->lastInsertId();
        } else {
            throw new Exception("Impossible de récupérer les détails de l'anime");
        }
    } else {
        $anime_id = $anime['id'];
    }

    // Vérifier si l'anime est déjà en favori
    $stmt = $pdo->prepare("SELECT id FROM favorites WHERE user_id = ? AND anime_id = ?");
    $stmt->execute([$user_id, $anime_id]);
    $favorite = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($favorite) {
        // Si l'anime est déjà en favori, le supprimer
        $stmt = $pdo->prepare("DELETE FROM favorites WHERE id = ?");
        $stmt->execute([$favorite['id']]);
        echo json_encode(['success' => true, 'action' => 'removed']);
    } else {
        // Sinon, l'ajouter aux favoris
        $stmt = $pdo->prepare("INSERT INTO favorites (user_id, anime_id) VALUES (?, ?)");
        $stmt->execute([$user_id, $anime_id]);
        echo json_encode(['success' => true, 'action' => 'added']);
    }
} catch (Exception $e) {
    error_log("Erreur dans toggle_favorite.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Une erreur est survenue lors de la mise à jour des favoris']);
}