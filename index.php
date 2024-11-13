<?php
require_once 'includes/header.php';
require_once 'includes/jikan_client.php';

try {
    $topAnime = $jikan->getTopAnime(1, 10);
    
    if (isset($topAnime['data']) && is_array($topAnime['data'])) {
        echo "<h1>Animes populaires</h1>";
        echo "<div class='anime-grid'>";
        foreach ($topAnime['data'] as $anime) {
            echo "<div class='anime-card'>";
            echo "<img src='" . htmlspecialchars($anime['images']['jpg']['image_url']) . "' 
                  alt='" . htmlspecialchars($anime['title']) . "' 
                  onerror=\"this.onerror=null;this.src='images/default-image.jpg';\">";
            echo "<h3>" . htmlspecialchars($anime['title']) . "</h3>";
            echo "<p>Note : " . htmlspecialchars($anime['score'] ?? 'N/A') . "</p>";
            echo "<a href='anime.php?id=" . htmlspecialchars($anime['mal_id']) . "'>Voir plus</a>";
            echo "</div>";
        }
        echo "</div>";
    } else {
        echo "<p>Aucun anime trouvé.</p>";
    }
} catch (Exception $e) {
    echo "<p>Une erreur est survenue : " . htmlspecialchars($e->getMessage()) . "</p>";
}

require_once 'includes/footer.php';
?>