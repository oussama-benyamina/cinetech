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
    
    echo "<div class='anime-details'>";
    echo "<h1>" . htmlspecialchars($anime['title']) . "</h1>";
    echo "<img src='" . htmlspecialchars($anime['images']['jpg']['large_image_url']) . "' alt='". htmlspecialchars($anime['title']) ."'>";
    echo "<p><strong>Score:</strong> " . htmlspecialchars($anime['score'] ?? 'N/A') . "</p>";
    echo "<p><strong>Épisodes:</strong> " . htmlspecialchars($anime['episodes'] ?? 'N/A') . "</p>";
    echo "<p><strong>Statut:</strong> " . htmlspecialchars($anime['status'] ?? 'N/A') . "</p>";
    echo "<p><strong>Type:</strong> " . htmlspecialchars($anime['type'] ?? 'N/A') . "</p>";
    echo "<p><strong>Genres:</strong> " . implode(', ', array_map(function($genre) { return htmlspecialchars($genre['name']); }, $anime['genres'] ?? [])) . "</p>";
    echo "<p><strong>Studios:</strong> " . implode(', ', array_map(function($studio) { return htmlspecialchars($studio['name']); }, $anime['studios'] ?? [])) . "</p>";
    echo "<p><strong>Date de diffusion:</strong> " . htmlspecialchars($anime['aired']['string'] ?? 'N/A') . "</p>";
    echo "<p><strong>Synopsis:</strong> " . htmlspecialchars($anime['synopsis'] ?? 'Aucun synopsis disponible.') . "</p>";
    
    // Bouton Favoris
    if (isset($_SESSION['user_id'])) {
        $stmt = $pdo->prepare("SELECT * FROM favorites WHERE user_id = ? AND anime_id = ?");
        $stmt->execute([$_SESSION['user_id'], $anime['mal_id']]);
        $isFavorite = $stmt->fetch() !== false;
        
        echo "<button class='favorite-btn' data-anime-id='" . $anime['mal_id'] . "' data-is-favorite='" . ($isFavorite ? 'true' : 'false') . "'>";
        echo $isFavorite ? 'Retirer des favoris' : 'Ajouter aux favoris';
        echo "</button>";
    }
    echo "</div>";
    
    // Section commentaires
    echo "<div class='comments-section'>";
    echo "<h2>Commentaires</h2>";
    
    // Afficher les commentaires existants
    $stmt = $pdo->prepare("SELECT c.*, CONCAT(u.firstname, ' ', u.lastname) AS username 
                           FROM comments c 
                           JOIN users u ON c.user_id = u.id 
                           WHERE c.anime_id = ? AND c.parent_id IS NULL 
                           ORDER BY c.created_at DESC");
    $stmt->execute([$anime['mal_id']]);
    $comments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($comments as $comment) {
        echo "<div class='comment' id='comment-" . $comment['id'] . "'>";
        echo "<p><strong>" . htmlspecialchars($comment['username']) . "</strong> a dit:</p>";
        echo "<p>" . htmlspecialchars($comment['content']) . "</p>";
        echo "<small>Posté le " . htmlspecialchars($comment['created_at']) . "</small>";
        
        // Bouton pour répondre
        if (isset($_SESSION['user_id'])) {
            echo "<button class='reply-btn' data-comment-id='" . $comment['id'] . "'>Répondre</button>";
        }
        
        // Afficher les réponses
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
                echo "<p><strong>" . htmlspecialchars($reply['username']) . "</strong> a répondu:</p>";
                echo "<p>" . htmlspecialchars($reply['content']) . "</p>";
                echo "<small>Posté le " . htmlspecialchars($reply['created_at']) . "</small>";
                echo "</div>";
            }
            echo "</div>";
        }
        
        echo "</div>";
    }
    
    // Formulaire pour ajouter un commentaire
    if (isset($_SESSION['user_id'])) {
        echo "<form id='comment-form'>";
        echo "<textarea name='comment' placeholder='Ajouter un commentaire...'></textarea>";
        echo "<input type='hidden' name='anime_id' value='" . $anime['mal_id'] . "'>";
        echo "<button type='submit'>Poster le commentaire</button>";
        echo "</form>";
    } else {
        echo "<p><a href='login.php'>Connectez-vous</a> pour laisser un commentaire.</p>";
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

    // Gérer les réponses aux commentaires
    $(document).on('click', '.reply-btn', function() {
        var commentId = $(this).data('comment-id');
        var replyForm = $('<form class="reply-form">' +
            '<textarea name="reply" placeholder="Votre réponse..."></textarea>' +
            '<input type="hidden" name="parent_id" value="' + commentId + '">' +
            '<button type="submit">Répondre</button>' +
            '</form>');
        
        $(this).after(replyForm);
        $(this).remove(); // Supprimer le bouton après avoir affiché le formulaire
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
                    location.reload(); // Recharger la page pour afficher la nouvelle réponse
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