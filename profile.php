<?php
require_once 'includes/header.php';
require_once 'db_connect.php';
require_once 'includes/jikan_client.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$jikan = new JikanAPI();

try {
    // Récupérer les informations de l'utilisateur
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // Récupérer les animes favoris
    $stmt = $pdo->prepare("SELECT a.* FROM favorites f JOIN animes a ON f.anime_id = a.id WHERE f.user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $favorites = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Récupérer les commentaires récents
    $stmt = $pdo->prepare("SELECT c.*, a.title as anime_title FROM comments c JOIN animes a ON c.anime_id = a.id WHERE c.user_id = ? ORDER BY c.created_at DESC LIMIT 5");
    $stmt->execute([$_SESSION['user_id']]);
    $comments = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("Erreur dans profile.php: " . $e->getMessage());
    echo "<p>Une erreur est survenue lors du chargement du profil. Veuillez réessayer plus tard.</p>";
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
                <div class="favorites-grid">
                <?php foreach ($favorites as $anime): ?>
                    <div class="anime-card">
                        <img src="<?php echo htmlspecialchars($anime['image_url']); ?>" alt="<?php echo htmlspecialchars($anime['title']); ?>">
                        <h3><?php echo htmlspecialchars($anime['title']); ?></h3>
                        <a href="anime.php?id=<?php echo $anime['mal_id']; ?>">Voir détails</a>
                    </div>
                <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p>Aucun anime favori pour le moment.</p>
            <?php endif; ?>
        </div>

        <div class="recent-comments">
            <h2>Commentaires récents</h2>
            <?php if ($comments): ?>
                <?php foreach ($comments as $comment): ?>
                    <div class="comment">
                        <p><strong>Anime :</strong> <?php echo htmlspecialchars($comment['anime_title']); ?></p>
                        <p><?php echo htmlspecialchars($comment['content']); ?></p>
                        <small>Posté le <?php echo htmlspecialchars($comment['created_at']); ?></small>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p>Aucun commentaire pour le moment.</p>
            <?php endif; ?>
        </div>
    </div>

    <style>
    .profile-container {
        max-width: 800px;
        margin: 0 auto;
        padding: 20px;
    }
    .profile-header {
        text-align: center;
    }
    .profile-avatar {
        width: 150px;
        height: 150px;
        border-radius: 50%;
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
    .favorites-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
        gap: 20px;
    }
    .anime-card {
        border: 1px solid #ddd;
        padding: 10px;
        text-align: center;
    }
    .anime-card img {
        max-width: 100%;
        height: auto;
    }
    .comment {
        background-color: #f8f9fa;
        border: 1px solid #eee;
        padding: 10px;
        margin-bottom: 10px;
    }
    </style>

    <?php require_once 'includes/footer.php'; ?>
</body>
</html>