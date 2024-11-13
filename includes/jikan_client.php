<?php
function getJikanClient() {
    return new class {
        private $baseUrl = 'https://api.jikan.moe/v4';

        public function getAnime($id) {
            $url = "{$this->baseUrl}/anime/{$id}";
            return $this->makeRequest($url);
        }

        public function getTopAnime($page = 1, $limit = 10) {
            $url = "{$this->baseUrl}/top/anime?page={$page}&limit={$limit}";
            return $this->makeRequest($url);
        }

        public function getAnimeSearch($query, $page = 1, $limit = 10) {
            $url = "{$this->baseUrl}/anime?q=" . urlencode($query) . "&page={$page}&limit={$limit}";
            return $this->makeRequest($url);
        }

        private function makeRequest($url) {
            $response = file_get_contents($url);
            if ($response === false) {
                throw new Exception("Erreur lors de la requête à l'API Jikan");
            }
            $data = json_decode($response, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception("Erreur lors du décodage de la réponse JSON");
            }
            return $data;
        }
    };
}

$jikan = getJikanClient();
?>