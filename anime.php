<?php
require_once 'includes/header.php';
require_once 'includes/jikan_client.php';

$id = $_GET['id'] ?? '';

if ($id) {
    try {
        $animeDetails = $jikan->getAnime($id);
        
        if (isset($animeDetails['data'])) {
            $anime = $animeDetails['data'];
            echo "<h1>" . htmlspecialchars($anime['title']) . "</h1>";
            echo "<img src='" . htmlspecialchars($anime['images']['jpg']['large_image_url']) . "' 
                  alt='" . htmlspecialchars($anime['title']) . "' 
                  onerror=\"this.onerror=null;this.src='images/default-image.jpg';\">";
            echo "<p>" . htmlspecialchars($anime['synopsis'] ?? 'Aucune synopsis disponible.') . "</p>";
            echo "<p>Note : " . htmlspecialchars($anime['score'] ?? 'N/A') . "</p>";
            echo "<p>Épisodes : " . htmlspecialchars($anime['episodes'] ?? 'N/A') . "</p>";
            // Ajoutez d'autres détails selon vos besoins
        } else {
            echo "<p>Anime non trouvé.</p>";
        }
    } catch (Exception $e) {
        echo "<p>Une erreur est survenue : " . htmlspecialchars($e->getMessage()) . "</p>";
    }
} else {
    echo "<p>Aucun ID d'anime fourni.</p>";
}

require_once 'includes/footer.php';
?>