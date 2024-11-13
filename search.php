<?php
require_once 'includes/header.php';
require_once 'includes/jikan_client.php';

$query = $_GET['q'] ?? '';

if ($query) {
    try {
        $searchResults = $jikan->getAnimeSearch($query);
        
        if (isset($searchResults['data']) && is_array($searchResults['data'])) {
            echo "<h2>Résultats de recherche pour : " . htmlspecialchars($query) . "</h2>";
            echo "<div class='anime-grid'>";
            foreach ($searchResults['data'] as $anime) {
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
            echo "<p>Aucun résultat trouvé.</p>";
        }
    } catch (Exception $e) {
        echo "<p>Une erreur est survenue : " . htmlspecialchars($e->getMessage()) . "</p>";
    }
} else {
    echo "<p>Veuillez entrer un terme de recherche.</p>";
}

require_once 'includes/footer.php';
?>