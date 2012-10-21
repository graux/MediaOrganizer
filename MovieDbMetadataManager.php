<?php

/**
 * Description of MovieDbMetadataManager
 *
 * @author Francisco Grau <grau.fran@gmail.com>
 */
class MovieDbMetadataManager {
    
    const URL_SEARCH = 'http://api.themoviedb.org/3/search/movie?api_key={KEY}&query={SEARCH}';
    const URL_MOVIE = 'http://api.themoviedb.org/3/movie/{ID}?api_key={KEY}';
    const URL_CONFIGURATION = 'http://api.themoviedb.org/3/configuration?api_key={KEY}';
    const URL_POSTER = '{BASE}{SIZE}{POSTER}';
    const POSTER_SIZE = 'original';

    private static $instance = null;
    private $baseUrl = null;
    private $ApiKey = null;

    private function __construct() {
        $this->ApiKey = $GLOBALS['THEMOVIEDB_KEY'];
        $configUrl = str_replace('{KEY}', $this->ApiKey, self::URL_CONFIGURATION);
        $config = $this->requestJson($configUrl);
        $this->baseUrl = $config['images']['base_url'];
        $this->ApiKey = $GLOBALS['THEMOVIEDB_KEY'];
    }

    /**
     * 
     * @return MovieDbMetadataManager
     */
    public static function getInstance() {
        if (is_null(self::$instance)) {
            self::$instance = new MovieDbMetadataManager();
        }
        return self::$instance;
    }

    private function requestJson($url) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_HEADER, FALSE);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array("Accept: application/json"));
        $response = curl_exec($ch);
        curl_close($ch);

        return json_decode($response, true);
    }

    /**
     * 
     * @param MediaItemMovie $mediaItem
     */
    public function fetchMediaItemData($mediaItem) {
        $searchUrl = str_replace('{KEY}', $this->ApiKey, self::URL_SEARCH);
        $searchUrl = str_replace('{SEARCH}', urlencode($mediaItem->name), $searchUrl);
        $searchResults = $this->requestJson($searchUrl);

        $firstResult = array_shift($searchResults['results']);
        $mediaItem->id = intval($firstResult['id']);

        $searchUrl = str_replace('{KEY}', $this->ApiKey, self::URL_MOVIE);
        $searchUrl = str_replace('{ID}', $mediaItem->id, $searchUrl);
        $firstResult = $this->requestJson($searchUrl);

        $mediaItem->originalTitle = (string) $firstResult['original_title'];
        $mediaItem->overview = (string) $firstResult['overview'];
        $mediaItem->released = (string) $firstResult['release_date'];
        $mediaItem->posterUrl = str_replace('{BASE}', $this->baseUrl, self::URL_POSTER);
        $mediaItem->posterUrl = str_replace('{SIZE}', self::POSTER_SIZE, $mediaItem->posterUrl);
        $mediaItem->posterUrl = str_replace('{POSTER}', (string) $firstResult['poster_path'], $mediaItem->posterUrl);
        $mediaItem->runTime = intval($firstResult['runtime']);
        $mediaItem->rating = floatval($firstResult['vote_average']);
        
        print_r($mediaItem);
    }

}

