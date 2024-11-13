<?php
require_once 'includes/header.php';
require_once 'db_connect.php';
require_once 'includes/jikan_client.php'; // Assurez-vous d'avoir ce fichier pour les appels API

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

try {
    // Récupérer les informations de l'utilisateur
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // Récupérer les IDs des animes favoris
    $stmt = $pdo->prepare("SELECT anime_id FROM favorites WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $favorites = $stmt->fetchAll(PDO::FETCH_COLUMN);

    // Récupérer les commentaires récents
    $stmt = $pdo->prepare("SELECT * FROM comments WHERE user_id = ? ORDER BY created_at DESC LIMIT 5");
    $stmt->execute([$_SESSION['user_id']]);
    $comments = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    echo "Erreur : " . $e->getMessage();
    exit();
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil - <?php echo htmlspecialchars($user['firstname'] . ' ' . $user['lastname']); ?></title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .profile-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        .profile-header {
            text-align: center;
            margin-bottom: 20px;
        }
        .profile-avatar {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
        }
        .profile-info, .profile-actions, .favorite-animes, .recent-comments {
            margin-bottom: 30px;
        }
        .btn {
            display: inline-block;
            padding: 10px 20px;
            background-color: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 5px;
        }
        .comment {
            background-color: #f8f9fa;
            padding: 10px;
            margin-bottom: 10px;
            border-radius: 5px;
        }
    </style>
</head>
<body>
    <div class="profile-container">
        <div class="profile-header">
            <img src="<?php echo $user['avatar_url'] ?? 'images/default-avatar.png'; ?>" alt="Avatar" class="profile-avatar">
            <h1><?php echo htmlspecialchars($user['firstname'] . ' ' . $user['lastname']); ?></h1>
        </div>

        <div class="profile-info">
            <p><strong>Email :</strong> <?php echo htmlspecialchars($user['email']); ?></p>
            <p><strong>Membre depuis :</strong> <?php echo htmlspecialchars($user['created_at']); ?></p>
        </div>

        <div class="profile-actions">
            <a href="edit_profile.php" class="btn">Modifier le profil</a>
        </div>

        <div class="favorite-animes">
            <h2>Animes favoris</h2>
            <?php if ($favorites): ?>
                <ul>
                    <?php foreach ($favorites as $animeId): 
                        $animeInfo = $jikan->getAnime($animeId);
                        $animeTitle = $animeInfo['data']['title'] ?? 'Anime inconnu';
                    ?>
                        <li><?php echo htmlspecialchars($animeTitle); ?></li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <p>Aucun anime favori pour le moment.</p>
            <?php endif; ?>
        </div>

        <div class="recent-comments">
            <h2>Commentaires récents</h2>
            <?php if ($comments): ?>
                <?php foreach ($comments as $comment): 
                    $animeInfo = $jikan->getAnime($comment['anime_id']);
                    $animeTitle = $animeInfo['data']['title'] ?? 'Anime inconnu';
                ?>
                    <div class="comment">
                        <p><strong><?php echo htmlspecialchars($animeTitle); ?></strong></p>
                        <p><?php echo htmlspecialchars($comment['content']); ?></p>
                        <small>Posté le <?php echo htmlspecialchars($comment['created_at']); ?></small>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p>Aucun commentaire pour le moment.</p>
            <?php endif; ?>
        </div>
    </div>

    <?php require_once 'includes/footer.php'; ?>
</body>
</html>