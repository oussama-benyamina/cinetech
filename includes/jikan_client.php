<?php

class JikanAPI {
    private $baseUrl = 'https://api.jikan.moe/v4';
    private $lastRequestTime = 0;
    private $requestDelay = 1; // 1 seconde entre chaque requête
    private $cache = [];
    private $cacheExpiration = 3600; // 1 heure

    private function makeRequest($endpoint, $params = []) {
        $cacheKey = $endpoint . '?' . http_build_query($params);
        
        // Vérifier le cache
        if (isset($this->cache[$cacheKey]) && $this->cache[$cacheKey]['expiration'] > time()) {
            return $this->cache[$cacheKey]['data'];
        }

        // Respecter le délai entre les requêtes
        $currentTime = microtime(true);
        $timeSinceLastRequest = $currentTime - $this->lastRequestTime;
        if ($timeSinceLastRequest < $this->requestDelay) {
            usleep((int)(($this->requestDelay - $timeSinceLastRequest) * 1000000));
        }

        $url = $this->baseUrl . $endpoint;
        if (!empty($params)) {
            $url .= '?' . http_build_query($params);
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $this->lastRequestTime = microtime(true);

        if ($httpCode == 429) {
            // Attendre 60 secondes avant de réessayer
            sleep(60);
            return $this->makeRequest($endpoint, $params);
        }

        $data = json_decode($response, true);

        // Mettre en cache
        $this->cache[$cacheKey] = [
            'data' => $data,
            'expiration' => time() + $this->cacheExpiration
        ];

        return $data;
    }

    public function getTopAnime($page = 1, $limit = 25) {
        return $this->makeRequest('/top/anime', ['page' => $page, 'limit' => $limit]);
    }

    public function getAnimeDetails($id) {
        return $this->makeRequest("/anime/$id");
    }

    public function searchAnime($query, $page = 1, $limit = 25) {
        return $this->makeRequest('/anime', ['q' => $query, 'page' => $page, 'limit' => $limit]);
    }

    public function getAnimeGenre($genreId, $page = 1, $limit = 25) {
        return $this->makeRequest('/anime', [
            'genres' => $genreId,
            'page' => $page,
            'limit' => $limit
        ]);
    }
    public function getAnimeGenres() {
        return $this->makeRequest('/genres/anime');
    }

    public function getSeasonalAnime($year, $season, $page = 1, $limit = 25) {
        return $this->makeRequest("/seasons/{$year}/{$season}", ['page' => $page, 'limit' => $limit]);
    }

    public function getSchedule($day = null) {
        $params = [];
        if ($day) {
            $params['filter'] = $day;
        }
        return $this->makeRequest('/schedules', $params);
    }

    public function getCharacterDetails($id) {
        return $this->makeRequest("/characters/{$id}");
    }

    public function getPeopleDetails($id) {
        return $this->makeRequest("/people/{$id}");
    }

    public function getReviews($type, $id, $page = 1) {
        return $this->makeRequest("/{$type}/{$id}/reviews", ['page' => $page]);
    }

    public function getRecommendations($type, $id) {
        return $this->makeRequest("/{$type}/{$id}/recommendations");
    }

    public function getUserProfile($username) {
        return $this->makeRequest("/users/{$username}");
    }

    public function getUserAnimeList($username, $status = 'all') {
        return $this->makeRequest("/users/{$username}/animelist", ['status' => $status]);
    }

    public function getUserMangaList($username, $status = 'all') {
        return $this->makeRequest("/users/{$username}/mangalist", ['status' => $status]);
    }
}