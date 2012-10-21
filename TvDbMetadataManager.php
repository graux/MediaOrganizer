<?php
/**
 * Description of TvDbMetadataManager
 *
 * @author Fran
 */
class TvDbMetadataManager {
    private $ApiKey = null;
    private static $instance = null;
    private $mirrors = array();

    const URL_MIRRORS = 'http://www.thetvdb.com/api/{KEY}/mirrors.xml';
    const URL_SEARCH = 'http://www.thetvdb.com/api/GetSeries.php?seriesname={NAME}';
    const URL_SERIESDATA = 'http://www.thetvdb.com/api/{KEY}/series/{ID}/en.xml';
    const URL_SERIESEPISODES = 'http://www.thetvdb.com/api/{KEY}/series/{ID}/all/en.xml';
    const URL_POSTER = 'http://thetvdb.com/banners/{POSTER}';

    private $seriesData = array();

    private function __construct()
    {
        $this->ApiKey = $GLOBALS['THETVDB_KEY'];
        $mirrors = $this->requestXml(str_replace('{KEY}', $this->ApiKey, self::URL_MIRRORS));

        foreach($mirrors as $m){
            $mask = (string)$m->typemask;
            $this->mirrors[$mask] = $m->mirrorpath;
        }
    }
    /**
     *
     * @return TvDbMetadataManager
     */
    public static function getInstance(){
        if(is_null(self::$instance)){
            self::$instance = new TvDbMetadataManager();
        }
        return self::$instance;
    }

    private function requestXml($url){
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_HEADER, FALSE);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array("Accept: text/xml"));
        $response = curl_exec($ch);
        curl_close($ch);

        //error_log($response);

        return simplexml_load_string($response);
    }

    /**
     *
     * @param MediaItemSeries $mediaItem
     */
    public function fetchMediaItemData($mediaItem){
        $searchUrl = str_replace('{NAME}', urlencode($mediaItem->name), self::URL_SEARCH);
        $searchResults = $this->requestXml($searchUrl);

        $seriesData = $searchResults->Series;
        $mediaItem->id = intval($seriesData->seriesid);
        $mediaItem->overview = (string)$seriesData->Overview;

        if(empty($this->seriesData[$mediaItem->id])){
            $this->seriesData[$mediaItem->id] = array('Id' => $mediaItem->id,
                                        'Overview' => $mediaItem->overview,
                                        'Episodes' => array());
            $this->fetchMediaItemEpisodes($mediaItem);
        }
        $key = $mediaItem->getEpisodeKey();
        $series = $this->seriesData[$mediaItem->id];
        $episode = $series['Episodes'][$key];

        $mediaItem->episodeOverview = $episode['overview'];
        $mediaItem->id = $episode['id'];
        $mediaItem->episodeTitle = $episode['title'];
        $mediaItem->posterUrl = $series['Poster'];
        $mediaItem->rating = $series['Rating'];
        $mediaItem->released = $episode['aired'];
        $mediaItem->episodeRating = $episode['rating'];
        $mediaItem->runTime = $series['Runtime'];
    }
    public function fetchMediaItemEpisodes($mediaItem){
        $searchUrl = str_replace('{ID}', $mediaItem->id,str_replace('{KEY}', $this->ApiKey, self::URL_SERIESEPISODES));
        $searchResults = $this->requestXml($searchUrl);

        $poster = (string)$searchResults->Series->poster;
        $this->seriesData[$mediaItem->id]['Poster'] = str_replace('{POSTER}', $poster, self::URL_POSTER);
        $this->seriesData[$mediaItem->id]['Rating'] = floatval($searchResults->Series->Rating);
        $this->seriesData[$mediaItem->id]['Runtime'] = intval($searchResults->Series->Runtime);

        foreach($searchResults as $episode){
            if($episode->getName() == 'Episode'){
                $epData = array(
                    'id' => intval($episode->id),
                    'aired' => (string)$episode->FirstAired,
                    'season' => intval($episode->SeasonNumber),
                    'episode' => intval($episode->EpisodeNumber),
                    'title' => (string)$episode->EpisodeName,
                    'overview' => (string)$episode->Overview,
                    'rating' => floatval($episode->Rating)
                );
                $epCode = 'S'.sprintf('%02d',$epData['season']).'E'.sprintf('%02d',$epData['episode']);
                $this->seriesData[$mediaItem->id]['Episodes'][$epCode] = $epData;
            }
        }
    }
}

