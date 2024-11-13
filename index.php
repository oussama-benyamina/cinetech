<?php
require_once 'includes/header.php';
require_once 'includes/jikan_client.php';
require_once 'db_connect.php';

try {
    $topAnime = $jikan->getTopAnime(1, 10);
    
    if (isset($topAnime['data']) && is_array($topAnime['data'])) {
        echo "<h1>Animes populaires</h1>";
        echo "<div class='anime-grid'>";
        foreach ($topAnime['data'] as $anime) {
            $isFavorite = false;
            if (isset($_SESSION['user_id'])) {
                $stmt = $pdo->prepare("SELECT * FROM favorites WHERE user_id = ? AND anime_id = ?");
                $stmt->execute([$_SESSION['user_id'], $anime['mal_id']]);
                $isFavorite = $stmt->fetch() !== false;
            }
            
            echo "<div class='anime-card'>";
            echo "<img src='" . htmlspecialchars($anime['images']['jpg']['image_url']) . "' 
                  alt='" . htmlspecialchars($anime['title']) . "' 
                  onerror=\"this.onerror=null;this.src='images/default-image.jpg';\">";
            echo "<h3>" . htmlspecialchars($anime['title']) . "</h3>";
            echo "<p>Note : " . htmlspecialchars($anime['score'] ?? 'N/A') . "</p>";
            echo "<a href='anime.php?id=" . htmlspecialchars($anime['mal_id']) . "'>Voir plus</a>";
            if (isset($_SESSION['user_id'])) {
                echo "<button class='favorite-btn' data-anime-id='" . $anime['mal_id'] . "' data-is-favorite='" . ($isFavorite ? 'true' : 'false') . "'>";
                echo $isFavorite ? 'Retirer des favoris' : 'Ajouter aux favoris';
                echo "</button>";
            }
            echo "</div>";
        }
        echo "</div>";
    } else {
        echo "<p>Aucun anime trouvé.</p>";
    }
} catch (Exception $e) {
    echo "<p>Une erreur est survenue : " . htmlspecialchars($e->getMessage()) . "</p>";
}

// Ajoutez ce script JavaScript à la fin de votre fichier
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
                    alert('Une erreur est survenue.');
                }
            },
            error: function() {
                alert('Une erreur est survenue.');
            }
        });
    });
});
</script>

<?php
require_once 'includes/footer.php';
?>