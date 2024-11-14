<?php
require_once 'includes/header.php';
require_once 'includes/jikan_client.php';
require_once 'db_connect.php';

$jikan = new JikanAPI();

$anime_id = $_GET['id'] ?? null;

if (!$anime_id) {
    echo "<p>Aucun ID d'anime fourni.</p>";
    require_once 'includes/footer.php';
    exit;
}

try {
    $animeDetails = $jikan->getAnimeDetails($anime_id);
    
    if (isset($animeDetails['data'])) {
        $anime = $animeDetails['data'];
        
        echo "<div class='anime-details'>";
        echo "<h1>" . htmlspecialchars($anime['title']) . "</h1>";
        echo "<img src='" . htmlspecialchars($anime['images']['jpg']['large_image_url']) . "' alt='". htmlspecialchars($anime['title']) ."'>";
        echo "<p><strong>Score:</strong> " . htmlspecialchars($anime['score'] ?? 'N/A') . "</p>";
        echo "<p><strong>Épisodes:</strong> " . htmlspecialchars($anime['episodes'] ?? 'N/A') . "</p>";
        echo "<p><strong>Statut:</strong> " . htmlspecialchars($anime['status'] ?? 'N/A') . "</p>";
        echo "<p><strong>Synopsis:</strong> " . htmlspecialchars($anime['synopsis'] ?? 'Aucun synopsis disponible.') . "</p>";
        
        // Bouton Favoris
        if (isset($_SESSION['user_id'])) {
            $stmt = $pdo->prepare("SELECT * FROM favorites WHERE user_id = ? AND anime_id = ?");
            $stmt->execute([$_SESSION['user_id'], $anime_id]);
            $isFavorite = $stmt->fetch() !== false;
            
            echo "<button class='favorite-btn' data-anime-id='" . $anime_id . "' data-is-favorite='" . ($isFavorite ? 'true' : 'false') . "'>";
            echo $isFavorite ? 'Retirer des favoris' : 'Ajouter aux favoris';
            echo "</button>";
        }
        echo "</div>";
        
        // Section commentaires
        echo "<div class='comments-section'>";
        echo "<h2>Commentaires</h2>";
        
        // Afficher les commentaires existants
        $stmt = $pdo->prepare("SELECT c.*, u.username FROM comments c JOIN users u ON c.user_id = u.id WHERE c.anime_id = ? ORDER BY c.created_at DESC");
        $stmt->execute([$anime_id]);
        $comments = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($comments as $comment) {
            echo "<div class='comment'>";
            echo "<p><strong>" . htmlspecialchars($comment['username']) . "</strong> a dit:</p>";
            echo "<p>" . htmlspecialchars($comment['content']) . "</p>";
            echo "<small>Posté le " . htmlspecialchars($comment['created_at']) . "</small>";
            echo "</div>";
        }
        
        // Formulaire pour ajouter un commentaire
        if (isset($_SESSION['user_id'])) {
            echo "<form id='comment-form'>";
            echo "<textarea name='comment' placeholder='Ajouter un commentaire...'></textarea>";
            echo "<input type='hidden' name='anime_id' value='" . $anime_id . "'>";
            echo "<button type='submit'>Poster</button>";
            echo "</form>";
        } else {
            echo "<p>Connectez-vous pour laisser un commentaire.</p>";
        }
        
        echo "</div>";
    } else {
        echo "<p>Anime non trouvé.</p>";
    }
} catch (Exception $e) {
    error_log("Erreur dans anime.php: " . $e->getMessage());
    echo "<p>Une erreur est survenue lors du chargement des détails de l'anime. Veuillez réessayer plus tard.</p>";
}
?>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(document).ready(function() {
    $('.favorite-btn').click(function() {
        var button = $(this);
        var animeId = button.data('anime-id');
        var isFavorite = button.data('is-favorite') === 'true';

        $.ajax({
            url: 'toggle_favorite.php',
            type: 'POST',
            data: { anime_id: animeId },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    if (response.action === 'added') {
                        button.text('Retirer des favoris');
                        button.data('is-favorite', 'true');
                    } else {
                        button.text('Ajouter aux favoris');
                        button.data('is-favorite', 'false');
                    }
                } else {
                    alert('Une erreur est survenue : ' + (response.message || 'Erreur inconnue'));
                }
            },
            error: function() {
                alert('Une erreur est survenue lors de la communication avec le serveur.');
            }
        });
    });

    $('#comment-form').submit(function(e) {
        e.preventDefault();
        var form = $(this);
        var comment = form.find('textarea[name="comment"]').val();
        var animeId = form.find('input[name="anime_id"]').val();

        $.ajax({
            url: 'add_comment.php',
            type: 'POST',
            data: { anime_id: animeId, comment: comment },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    location.reload(); // Recharger la page pour afficher le nouveau commentaire
                } else {
                    alert('Une erreur est survenue : ' + (response.message || 'Erreur inconnue'));
                }
            },
            error: function() {
                alert('Une erreur est survenue lors de la communication avec le serveur.');
            }
        });
    });
});
</script>

<?php require_once 'includes/footer.php'; ?>