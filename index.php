<?php
require_once 'includes/header.php';
require_once 'includes/jikan_client.php';
require_once 'db_connect.php';

$jikan = new JikanAPI();

try {
    $topAnime = $jikan->getTopAnime(1,16 );
    $genres = $jikan->getAnimeGenres();
    
    echo "<h1>Bienvenue sur Cinetech Anime</h1>";

    if (isset($topAnime['data']) && is_array($topAnime['data'])) {
        echo "<h2>Animes populaires</h2>";
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
        echo "<p>Aucun anime trouvé pour le moment.</p>";
    }

    if (isset($genres['data']) && is_array($genres['data'])) {
        echo "<h2>Genres d'anime</h2>";
        echo "<ul class='genre-list'>";
        foreach ($genres['data'] as $genre) {
            echo "<li><a href='genre.php?id=" . $genre['mal_id'] . "'>" . htmlspecialchars($genre['name']) . "</a></li>";
        }
        echo "</ul>";
    }

} catch (Exception $e) {
    error_log("Erreur dans index.php: " . $e->getMessage());
    echo "<p>Une erreur est survenue lors du chargement de la page. Veuillez réessayer plus tard.</p>";
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
});
</script>

<?php require_once 'includes/footer.php'; ?>