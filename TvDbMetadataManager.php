<?php

/**
 * Description of TvDbMetadataManager
 *
 * @author Francisco Grau <grau.fran@gmail.com>
 */
class TvDbMetadataManager
{
    private $ApiKey = null;
    private static $instance = null;
    private $mirrors = array();
    private $activeMirror = null;

    const URL_MIRRORS = 'http://www.thetvdb.com/api/{KEY}/mirrors.xml';
    const URL_SEARCH = 'http://www.thetvdb.com/api/GetSeries.php?seriesname={NAME}';
    const URL_SEARCH_BYID = 'http://www.thetvdb.com/api/GetSeriesByRemoteID.php?imdbid={ID}';
    const URL_SEARCH_IMDB = 'http://www.imdb.com/search/title?title={NAME}&title_type=tv_series';
    const REGEX_SEARCH_IMDB_ID = '/\/title\/(?P<ID>tt\d{7,7})\//';
    const URL_SERIESDATA = '{MIRROR}/api/{KEY}/series/{ID}/en.xml';
    const URL_SERIESEPISODE = '{MIRROR}/api/{KEY}/series/{ID}/default/{SEASON}/{EPISODE}/en.xml';
    const URL_SERIESPOSTERS = '{MIRROR}/api/{KEY}/series/{ID}/banners.xml';
    const URL_POSTER = '{MIRROR}/banners/{POSTER}';

    private static $ImdbCache = array();
    private static $SeriesCache = array();
    private static $SeriesDataCache = array();
    private $seriesData = array();

    private function __construct()
    {
        $this->ApiKey = $GLOBALS['THETVDB_KEY'];
        $mirrors = $this->requestXml(str_replace('{KEY}', $this->ApiKey, self::URL_MIRRORS));

        foreach ($mirrors as $m) {
            $mask = (string)$m->typemask;
            $this->mirrors[$mask] = $m->mirrorpath;
            if (empty($this->activeMirror)) {
                $this->activeMirror = $m->mirrorpath;
            }
        }
    }

    /**
     *
     * @return TvDbMetadataManager
     */
    public static function getInstance()
    {
        if (is_null(self::$instance)) {
            self::$instance = new TvDbMetadataManager();
        }
        return self::$instance;
    }

    private function requestXml($url)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_HEADER, FALSE);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array("Accept: text/xml"));
        $response = curl_exec($ch);
        curl_close($ch);

        //error_log('XML for : '.$url."\n".$response."\n");

        return simplexml_load_string($response);
    }

    private function requestUrl($url)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_HEADER, FALSE);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array("Accept: text/xml"));
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.7; rv:15.0) Gecko/20100101 Firefox/15.0.1');
        $response = curl_exec($ch);
        curl_close($ch);

        return $response;
    }

    /**
     * @param MediaItemSeries $mediaItem
     */
    public function fetchMediaItemData($mediaItem)
    {
        // The TV DB Does not provide a search, only exact match. Using IMDB search instead
        /*
          $searchUrl = str_replace('{NAME}', urlencode($mediaItem->name), self::URL_SEARCH);
          $searchResults = $this->requestXml($searchUrl);
         */

        $searchUrl = str_replace('{NAME}', urlencode($mediaItem->name), self::URL_SEARCH_IMDB);
        if (!empty($mediaItem->year)) {
            $searchUrl .= '&year=' . $mediaItem->year;
        }

        if (empty(TvDbMetadataManager::$ImdbCache[$searchUrl])) {
            $searchHtml = $this->requestUrl($searchUrl);
            $matches = array();
            if (preg_match(self::REGEX_SEARCH_IMDB_ID, $searchHtml, $matches) == false) {
                return error_log("Cannot find Metadata for " . $mediaItem->filePath . "\n");
            }
            $mediaItem->imdbId = $matches['ID'];
            if ($GLOBALS['DEBUG']) {
                echo('IMDB ID: ' . $mediaItem->imdbId . "\n");
            }
            TvDbMetadataManager::$ImdbCache[$searchUrl] = $mediaItem->imdbId;
        } else {
            $mediaItem->imdbId = TvDbMetadataManager::$ImdbCache[$searchUrl];
        }

        $searchUrl = str_replace('{ID}', urlencode($mediaItem->imdbId), self::URL_SEARCH_BYID);
        if (empty(TvDbMetadataManager::$SeriesCache[$searchUrl])) {
            echo 'Fetching TvDB show information for IMDB ID: ' . $mediaItem->imdbId . "\n";
            $searchResults = $this->requestXml($searchUrl);
            TvDbMetadataManager::$SeriesCache[$searchUrl] = $searchResults;
        } else {
            $searchResults = TvDbMetadataManager::$SeriesCache[$searchUrl];
        }

        $seriesData = $searchResults->Series;
        $mediaItem->id = intval($seriesData->seriesid);

        if ($mediaItem->id > 0) {
            $seriesUrl = str_replace('{MIRROR}', $this->activeMirror, self::URL_SERIESDATA);
            $seriesUrl = str_replace('{KEY}', $this->ApiKey, $seriesUrl);
            $seriesUrl = str_replace('{ID}', $mediaItem->id, $seriesUrl);

            if (empty(TvDbMetadataManager::$SeriesDataCache[$seriesUrl])) {
                echo 'Fetching Show Information for ' . $mediaItem->name . ' / ' . $mediaItem->id . "\n";
                $seriesXml = $this->requestXml($seriesUrl);
                TvDbMetadataManager::$SeriesDataCache[$seriesUrl] = $seriesXml;
            } else {
                $seriesXml = TvDbMetadataManager::$SeriesDataCache[$seriesUrl];
            }

            $seriesData = $seriesXml->Series;
            $mediaItem->overview = (string)$seriesData->Overview;
            $mediaItem->title = (string)$seriesData->SeriesName;
            $actors = (string)$seriesData->Actors;
            $mediaItem->actors = explode('|', trim($actors, '|'));
            $genere = (string)$seriesData->Genre;
            $mediaItem->genere = explode('|', trim($genere, '|'));
            $mediaItem->rating = floatval((string)$seriesData->Rating);
            $mediaItem->directors = (string)$seriesData->Director;

            $mediaItem->runTime = intval($seriesData->Runtime);

            $poster = (string)$seriesData->poster;
            $mediaItem->posterUrl = str_replace('{MIRROR}', $this->activeMirror, str_replace('{POSTER}', $poster, self::URL_POSTER));

            if (empty($this->seriesData[$mediaItem->id])) {
                $this->seriesData[$mediaItem->id] = array(
                    'Id' => $mediaItem->id,
                    'Title' => $mediaItem->title,
                    'Backdrops' => array());
            }
            $key = $mediaItem->getEpisodeKey();
            $series = $this->seriesData[$mediaItem->id];
            $this->fetchMediaItemEpisode($mediaItem);
            $this->fetchSeriesBackdrops($mediaItem);

            if (!empty($mediaItem->episodeOverview)) {
                $mediaItem->episodeOverview = preg_replace('/\n+/', "\n", $mediaItem->episodeOverview);
            }
            if (!empty($mediaItem->overview)) {
                $mediaItem->overview = preg_replace('/\n+/', "\n", $mediaItem->overview);
            }
        } else {
            $mediaItem->error = true;
        }
        return $mediaItem;
    }

    public function fetchMediaItemEpisode($mediaItem)
    {
        $searchUrl = str_replace('{ID}', $mediaItem->id, str_replace('{KEY}', $this->ApiKey, self::URL_SERIESEPISODE));
        $searchUrl = str_replace('{MIRROR}', $this->activeMirror, $searchUrl);
        $searchUrl = str_replace('{SEASON}', $mediaItem->season, $searchUrl);
        $searchUrl = str_replace('{EPISODE}', $mediaItem->episode, $searchUrl);
        if ($GLOBALS['DEBUG'] === true) {
            echo 'Fetching Episode information: ' . $mediaItem->toString() . "\n";
        }
        $searchResults = $this->requestXml($searchUrl);

        $loaded = false;
        if (!empty($searchResults->Episode)) {
            $episode = $searchResults->Episode;
            $episodeNumber = intval($episode->EpisodeNumber);
            $seasonNumber = intval($episode->SeasonNumber);
            if ($seasonNumber == $mediaItem->season && $episodeNumber == $mediaItem->episode) {
                $mediaItem->episodeOverview = (string)$episode->Overview;
                $mediaItem->episodeId = intval($episode->id);
                $mediaItem->episodeTitle = (string)$episode->EpisodeName;
                $mediaItem->released = (string)$episode->FirstAired;
                $mediaItem->episodeRating = floatval($episode->Rating);
                $directors = (string)$episode->Director;
                $mediaItem->episodeDirector = explode('|', trim($directors, '|'));
                $loaded = true;
                if ($GLOBALS['DEBUG'] === true) {
                    echo 'Episode information fetched: ' . $mediaItem->toString() . "\n";
                }
            }
        }
        if ($loaded == false) {
            $mediaItem->error = true;
            error_log("No information for episode: " . $mediaItem->originalFileName . ' (' . $searchUrl . ')' . "\n");
        }
    }

    public function fetchSeriesBackdrops($mItem)
    {
        $backdrops = array();
        if (empty($this->seriesData[$mItem->id]['Backdrops'])) {
            if ($GLOBALS['DEBUG'] === true) {
                echo 'Fetching Backdrops...' . "\n";
            }
            $backdropsUrl = str_replace('{MIRROR}', $this->activeMirror, self::URL_SERIESPOSTERS);
            $backdropsUrl = str_replace('{KEY}', $this->ApiKey, $backdropsUrl);
            $backdropsUrl = str_replace('{ID}', $mItem->id, $backdropsUrl);
            $backdropsXml = $this->requestXml($backdropsUrl);

            $backdropBaseUrl = str_replace('{MIRROR}', $this->activeMirror, self::URL_POSTER);
            $maxBackdrops = empty($GLOBALS['NUM_BACKDROPS']) ? 5 : $GLOBALS['NUM_BACKDROPS'];
            foreach ($backdropsXml->Banner as $backdropXml) {
                if (count($backdrops) >= $maxBackdrops) {
                    break;
                }
                $backdrop = (string)$backdropXml->BannerPath;
                $backdrops[] = str_replace('{POSTER}', $backdrop, $backdropBaseUrl);
            }
            $this->seriesData[$mItem->id]['Backdrops'] = $backdrops;
            if ($GLOBALS['DEBUG'] === true) {
                echo count($backdrops) . " backdrops fetched...\n";
            }
        }

        $mItem->backdrops = $this->seriesData[$mItem->id]['Backdrops'];
    }
}

