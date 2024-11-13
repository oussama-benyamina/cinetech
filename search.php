<?php
require_once 'includes/header.php';
require_once 'includes/jikan_client.php';
require_once 'db_connect.php';

$query = $_GET['q'] ?? '';
$page = $_GET['page'] ?? 1;
$limit = 10; // Nombre d'animes par page

if ($query) {
    try {
        $searchResults = $jikan->getAnimeSearch($query, $page, $limit);
        
        if (isset($searchResults['data']) && is_array($searchResults['data'])) {
            echo "<h2>Résultats de recherche pour : " . htmlspecialchars($query) . "</h2>";
            echo "<div class='anime-grid'>";
            foreach ($searchResults['data'] as $anime) {
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

            // Pagination
            $totalPages = $searchResults['pagination']['last_visible_page'] ?? 1;
            echo "<div class='pagination'>";
            if ($page > 1) {
                echo "<a href='?q=" . urlencode($query) . "&page=" . ($page - 1) . "'>Précédent</a> ";
            }
            if ($page < $totalPages) {
                echo "<a href='?q=" . urlencode($query) . "&page=" . ($page + 1) . "'>Suivant</a>";
            }
            echo "</div>";
        } else {
            echo "<p>Aucun résultat trouvé.</p>";
        }
    } catch (Exception $e) {
        echo "<p>Une erreur est survenue : " . htmlspecialchars($e->getMessage()) . "</p>";
    }
} else {
    echo "<p>Veuillez entrer un terme de recherche.</p>";
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

<style>
.anime-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 20px;
    padding: 20px;
}

.anime-card {
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 10px;
    text-align: center;
}

.anime-card img {
    max-width: 100%;
    height: auto;
    border-radius: 4px;
}

.favorite-btn {
    background-color: #4CAF50;
    border: none;
    color: white;
    padding: 10px 20px;
    text-align: center;
    text-decoration: none;
    display: inline-block;
    font-size: 16px;
    margin: 4px 2px;
    cursor: pointer;
    border-radius: 4px;
}

.favorite-btn[data-is-favorite='true'] {
    background-color: #f44336;
}

.pagination {
    text-align: center;
    margin-top: 20px;
}

.pagination a {
    color: black;
    padding: 8px 16px;
    text-decoration: none;
    transition: background-color .3s;
    border: 1px solid #ddd;
    margin: 0 4px;
}

.pagination a:hover {
    background-color: #ddd;
}
</style>

<?php require_once 'includes/footer.php'; ?>