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
    
    if (!isset($animeDetails['data'])) {
        throw new Exception("Données de l'anime non trouvées");
    }

    $anime = $animeDetails['data'];
    
    echo "<div class='anime-container'>";
    
    // Image section with zoom functionality
    echo "<div class='anime-image-container'>";
    echo "<img src='" . htmlspecialchars($anime['images']['jpg']['large_image_url']) . "' 
          alt='" . htmlspecialchars($anime['title']) . "'>";
    echo "</div>";
    
    // Anime information section
    echo "<div class='anime-info'>";
    echo "<h1>" . htmlspecialchars($anime['title']) . "</h1>";
    
    echo "<div class='anime-stats'>";
    echo "<div class='stat-item'><strong>Score</strong>" . htmlspecialchars($anime['score'] ?? 'N/A') . "</div>";
    echo "<div class='stat-item'><strong>Épisodes</strong>" . htmlspecialchars($anime['episodes'] ?? 'N/A') . "</div>";
    echo "<div class='stat-item'><strong>Statut</strong>" . htmlspecialchars($anime['status'] ?? 'N/A') . "</div>";
    echo "<div class='stat-item'><strong>Type</strong>" . htmlspecialchars($anime['type'] ?? 'N/A') . "</div>";
    echo "</div>";
    
    echo "<div class='stat-item'><strong>Genres</strong>" . implode(', ', array_map(function($genre) { 
        return htmlspecialchars($genre['name']); 
    }, $anime['genres'] ?? [])) . "</div>";
    
    echo "<div class='stat-item'><strong>Studios</strong>" . implode(', ', array_map(function($studio) { 
        return htmlspecialchars($studio['name']); 
    }, $anime['studios'] ?? [])) . "</div>";
    
    echo "<div class='stat-item'><strong>Date de diffusion</strong>" . htmlspecialchars($anime['aired']['string'] ?? 'N/A') . "</div>";
    
    echo "<div class='anime-synopsis'>";
    echo "<strong>Synopsis</strong>";
    echo "<p>" . htmlspecialchars($anime['synopsis'] ?? 'Aucun synopsis disponible.') . "</p>";
    echo "</div>";
    
    if (isset($_SESSION['user_id'])) {
        $stmt = $pdo->prepare("SELECT * FROM favorites WHERE user_id = ? AND anime_id = ?");
        $stmt->execute([$_SESSION['user_id'], $anime['mal_id']]);
        $isFavorite = $stmt->fetch() !== false;
        
        echo "<button class='favorite-btn' data-anime-id='" . $anime['mal_id'] . "' data-is-favorite='" . ($isFavorite ? 'true' : 'false') . "'>";
        echo $isFavorite ? 'Retirer des favoris' : 'Ajouter aux favoris';
        echo "</button>";
    }
    echo "</div>"; // Close anime-info
    echo "</div>"; // Close anime-container
    
    // Comments section
    echo "<div class='comments-section'>";
    echo "<h2>Commentaires</h2>";
    
    if (isset($_SESSION['user_id'])) {
        echo "<form id='comment-form'>";
        echo "<textarea name='comment' placeholder='Ajouter un commentaire...'></textarea>";
        echo "<input type='hidden' name='anime_id' value='" . $anime['mal_id'] . "'>";
        echo "<div class='form-actions'>";
        echo "<button type='submit' class='submit-comment'>Publier le commentaire</button>";
        echo "</div>";
        echo "</form>";
    }
    
    // Display existing comments
    $stmt = $pdo->prepare("SELECT c.*, CONCAT(u.firstname, ' ', u.lastname) AS username 
                          FROM comments c 
                          JOIN users u ON c.user_id = u.id 
                          WHERE c.anime_id = ? AND c.parent_id IS NULL 
                          ORDER BY c.created_at DESC");
    $stmt->execute([$anime['mal_id']]);
    $comments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($comments as $comment) {
        echo "<div class='comment' id='comment-" . $comment['id'] . "'>";
        echo "<div class='comment-header'>";
        echo "<strong>" . htmlspecialchars($comment['username']) . "</strong>";
        echo "</div>";
        
        echo "<div class='comment-content'>";
        echo htmlspecialchars($comment['content']);
        echo "</div>";
        
        echo "<div class='comment-footer'>";
        echo "<small>Posté le " . htmlspecialchars($comment['created_at']) . "</small>";
        if (isset($_SESSION['user_id'])) {
            echo "<button class='reply-btn' data-comment-id='" . $comment['id'] . "'>Répondre</button>";
        }
        echo "</div>";
        
        // Display replies
        $stmt = $pdo->prepare("SELECT c.*, CONCAT(u.firstname, ' ', u.lastname) AS username 
                              FROM comments c 
                              JOIN users u ON c.user_id = u.id 
                              WHERE c.parent_id = ? 
                              ORDER BY c.created_at ASC");
        $stmt->execute([$comment['id']]);
        $replies = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if ($replies) {
            echo "<div class='replies'>";
            foreach ($replies as $reply) {
                echo "<div class='reply'>";
                echo "<div class='comment-header'>";
                echo "<strong>" . htmlspecialchars($reply['username']) . "</strong>";
                echo "</div>";
                echo "<div class='comment-content'>";
                echo htmlspecialchars($reply['content']);
                echo "</div>";
                echo "<div class='comment-footer'>";
                echo "<small>Posté le " . htmlspecialchars($reply['created_at']) . "</small>";
                echo "</div>";
                echo "</div>";
            }
            echo "</div>";
        }
        echo "</div>";
    }
    echo "</div>";
    
} catch (Exception $e) {
    error_log("Erreur dans anime.php pour l'ID " . $anime_id . ": " . $e->getMessage());
    echo "<p>Une erreur est survenue lors du chargement des détails de l'anime. Veuillez réessayer plus tard.</p>";
    echo "<p>Détails de l'erreur (à supprimer en production) : " . $e->getMessage() . "</p>";
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
                    location.reload();
                } else {
                    alert('Une erreur est survenue : ' + (response.message || 'Erreur inconnue'));
                }
            },
            error: function() {
                alert('Une erreur est survenue lors de la communication avec le serveur.');
            }
        });
    });

    $(document).on('click', '.reply-btn', function() {
        var commentId = $(this).data('comment-id');
        var replyForm = $('<form class="reply-form">' +
            '<textarea name="reply" placeholder="Votre réponse..."></textarea>' +
            '<input type="hidden" name="parent_id" value="' + commentId + '">' +
            '<button type="submit">Répondre</button>' +
            '</form>');
        
        $(this).after(replyForm);
        $(this).remove();
    });

    $(document).on('submit', '.reply-form', function(e) {
        e.preventDefault();
        var form = $(this);
        var reply = form.find('textarea[name="reply"]').val();
        var parentId = form.find('input[name="parent_id"]').val();
        var animeId = $('input[name="anime_id"]').val();

        $.ajax({
            url: 'add_reply.php',
            type: 'POST',
            data: { anime_id: animeId, parent_id: parentId, comment: reply },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    location.reload();
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