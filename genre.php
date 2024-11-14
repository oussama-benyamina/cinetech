<?php
require_once 'includes/header.php';
require_once 'includes/jikan_client.php';
require_once 'db_connect.php';

$jikan = new JikanAPI();

// Récupérer l'ID du genre depuis l'URL
$genre_id = isset($_GET['id']) ? intval($_GET['id']) : null;
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;

if (!$genre_id) {
    echo "<p>Aucun genre spécifié.</p>";
    require_once 'includes/footer.php';
    exit;
}

try {
    // Récupérer les animes du genre spécifié
    $animeGenre = $jikan->getAnimeGenre($genre_id, $page);
    
    if (isset($animeGenre['data']) && !empty($animeGenre['data'])) {
        $genre_name = $animeGenre['data'][0]['genres'][0]['name'] ?? 'Genre inconnu';
        echo "<h1>Animes du genre : " . htmlspecialchars($genre_name) . "</h1>";
        
        echo "<div class='anime-grid'>";
        foreach ($animeGenre['data'] as $anime) {
            echo "<div class='anime-card'>";
            echo "<img src='" . htmlspecialchars($anime['images']['jpg']['image_url']) . "' 
                  alt='" . htmlspecialchars($anime['title']) . "' 
                  onerror=\"this.onerror=null;this.src='images/default-image.jpg';\">";
            echo "<h3>" . htmlspecialchars($anime['title']) . "</h3>";
            echo "<p>Note : " . htmlspecialchars($anime['score'] ?? 'N/A') . "</p>";
            echo "<a href='anime.php?id=" . htmlspecialchars($anime['mal_id']) . "'>Voir plus</a>";
            
            // Bouton Favoris (si l'utilisateur est connecté)
            if (isset($_SESSION['user_id'])) {
                $stmt = $pdo->prepare("SELECT * FROM favorites WHERE user_id = ? AND anime_id = ?");
                $stmt->execute([$_SESSION['user_id'], $anime['mal_id']]);
                $isFavorite = $stmt->fetch() !== false;
                
                echo "<button class='favorite-btn' data-anime-id='" . $anime['mal_id'] . "' data-is-favorite='" . ($isFavorite ? 'true' : 'false') . "'>";
                echo $isFavorite ? 'Retirer des favoris' : 'Ajouter aux favoris';
                echo "</button>";
            }
            
            echo "</div>";
        }
        echo "</div>";
        
        // Pagination
        if (isset($animeGenre['pagination'])) {
            $totalPages = $animeGenre['pagination']['last_visible_page'];
            echo "<div class='pagination'>";
            if ($page > 1) {
                echo "<a href='?id=$genre_id&page=" . ($page - 1) . "'>Précédent</a> ";
            }
            if ($page < $totalPages) {
                echo "<a href='?id=$genre_id&page=" . ($page + 1) . "'>Suivant</a>";
            }
            echo "</div>";
        }
        
    } else {
        echo "<p>Aucun anime trouvé pour ce genre.</p>";
    }
} catch (Exception $e) {
    error_log("Erreur dans genre.php: " . $e->getMessage());
    echo "<p>Une erreur est survenue lors du chargement des animes. Veuillez réessayer plus tard.</p>";
}
?>

<!-- Le reste du code (CSS et JavaScript) reste inchangé -->

<?php require_once 'includes/footer.php'; ?>