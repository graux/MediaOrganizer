<?php

/**
 * Description of MovieDbMetadataManager
 *
 * @author Francisco Grau <grau.fran@gmail.com>
 */
class MovieDbMetadataManager
{

    const URL_SEARCH = 'http://api.themoviedb.org/3/search/movie?api_key={KEY}&query={SEARCH}';
    const URL_SEARCH_YEAR = 'http://api.themoviedb.org/3/search/movie?api_key={KEY}&query={SEARCH}&year={YEAR}';
    const URL_MOVIE = 'http://api.themoviedb.org/3/movie/{ID}?api_key={KEY}';
    const URL_MOVIE_CAST = 'http://api.themoviedb.org/3/movie/{ID}/casts?api_key={KEY}';
    const URL_MOVIE_IMAGES = 'http://api.themoviedb.org/3/movie/{ID}/images?api_key={KEY}';
    const URL_CONFIGURATION = 'http://api.themoviedb.org/3/configuration?api_key={KEY}';
    const URL_POSTER = '{BASE}{SIZE}{POSTER}';
    const POSTER_SIZE = 'original';

    private static $instance = null;
    private $baseUrl = null;
    private $ApiKey = null;

    private function __construct()
    {
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
    public static function getInstance()
    {
        if (is_null(self::$instance)) {
            self::$instance = new MovieDbMetadataManager();
        }
        return self::$instance;
    }

    private function requestJson($url)
    {
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
    public function fetchMediaItemData($mediaItem)
    {
        $mediaItem->error = true;

        $searchUrl = empty($mediaItem->year) ? self::URL_SEARCH : self::URL_SEARCH_YEAR;

        $searchUrl = str_replace('{KEY}', $this->ApiKey, $searchUrl);
        $searchUrl = str_replace('{SEARCH}', urlencode($mediaItem->name), $searchUrl);
        $searchUrl = str_replace('{YEAR}', urlencode($mediaItem->year), $searchUrl);
        $searchResults = $this->requestJson($searchUrl);

        $firstResult = array_shift($searchResults['results']);
        $mediaItem->id = intval($firstResult['id']);

        if ($mediaItem->id > 0) {
            $searchUrl = str_replace('{KEY}', $this->ApiKey, self::URL_MOVIE);
            $searchUrl = str_replace('{ID}', $mediaItem->id, $searchUrl);
            $firstResult = $this->requestJson($searchUrl);

            if (!empty($firstResult['title'])) {
                $mediaItem->originalTitle = (string)$firstResult['original_title'];
                $mediaItem->title = (string)$firstResult['title'];
                $mediaItem->tagline = (string)$firstResult['tagline'];
                $mediaItem->overview = (string)$firstResult['overview'];
                $mediaItem->released = (string)$firstResult['release_date'];
                $mediaItem->posterUrl = str_replace('{BASE}', $this->baseUrl, self::URL_POSTER);
                $mediaItem->posterUrl = str_replace('{SIZE}', self::POSTER_SIZE, $mediaItem->posterUrl);
                $mediaItem->posterUrl = str_replace('{POSTER}', (string)$firstResult['poster_path'], $mediaItem->posterUrl);
                $mediaItem->runTime = intval($firstResult['runtime']);
                $mediaItem->rating = floatval($firstResult['vote_average']);
                $mediaItem->metadataProcessed = true;
                $mediaItem->error = false;
                echo('Movie Information Fetched for: ' . $mediaItem->toString(true) . "\n");

                $searchUrl = str_replace('{KEY}', $this->ApiKey, self::URL_MOVIE_CAST);
                $searchUrl = str_replace('{ID}', $mediaItem->id, $searchUrl);
                $castInfo = $this->requestJson($searchUrl);

                foreach ($castInfo['cast'] as $actor) {
                    $mediaItem->actors[] = (string)$actor['name'];
                }
                $directors = array();
                foreach ($castInfo['crew'] as $crew) {
                    if (strcasecmp($crew['job'], 'director') == 0) {
                        $directors[] = $crew['name'];
                    }
                }
                if (!empty($directors)) {
                    $mediaItem->directors = $directors;
                }

                $searchUrl = str_replace('{KEY}', $this->ApiKey, self::URL_MOVIE_IMAGES);
                $searchUrl = str_replace('{ID}', $mediaItem->id, $searchUrl);
                $images = $this->requestJson($searchUrl);
                $maxBackdrops = empty($GLOBALS['NUM_BACKDROPS']) ? 5 : $GLOBALS['NUM_BACKDROPS'];

                foreach ($images['backdrops'] as $backdrop) {
                    $backdropUrl = $this->baseUrl . self::POSTER_SIZE . (string)$backdrop['file_path'];
                    $mediaItem->backdrops[] = $backdropUrl;
                    if (count($mediaItem->backdrops) > $maxBackdrops) {
                        break;
                    }
                }
            }
        }

        if ($mediaItem->metadataProcessed === false) {
            $mediaItem->error = true;
            error_log('Error retrieving information for: ' . $mediaItem->originalFileName . ' (' . $searchUrl . ')' . "\n");
        }
    }
}

