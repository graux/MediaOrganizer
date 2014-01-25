<?php

/**
 * Description of MediaItem
 *
 * @author Francisco Grau <grau.fran@gmail.com>
 */
abstract class MediaItem
{
    const REGEX_SERIES = '/^(?P<Name>.*?)(s|S)\(?(?P<Season>\d\d)_?(e|E)(?P<Episode>\d\d)\)?.*?(?P<Format>(720p)|(1080p))?.*?\.[a-z0-9]{1,4}$/';
    const REGEX_MOVIES = '/^(?P<Name>.*?)(\(?(?P<Year>\d\d\d\d)\)?)(.*?)(?P<Format>(720p)|(1080p))?(.*?)\.[a-z0-9]{1,4}$/';
    const URL_SEARCH_IMDB = 'http://www.imdb.com/search/title?title={NAME}&title_type=tv_series';
    const REGEX_SEARCH_IMDB_RESULTS = '/<table class="results">(?P<RESULTS>(.|\n)*?)<\/table>/';
    const REGEX_SEARCH_IMDB_ID = '/\/title\/(?P<ID>tt\d{7,7})\//';
    public static $mediaSubtitlesExtensions = array('srt', 'ass', 'ssa', 'sub', 'smi');
    private static $ImdbCache = array();
    public $filePath = null;
    public $year = null;
    public $name = null;
    public $originalFileName = null;
    public $format = null;
    public $extension = null;
    public $overview = null;
    public $released = null;
    public $posterUrl = null;
    public $id = null;
    public $originalTitle = null;
    public $title = null;
    public $runTime = null;
    public $rating = null;
    public $imdbId = null;
    public $genere = array();
    public $actors = array();
    public $directors = array();
    public $backdrops = array();
    public $subtitles = array();
    public $metadataProcessed = false;
    public $error = false;
    public $skip = false;

    /**
     *
     * @param string $filePath
     * @return MediaItem
     */
    public static function createMediaItem($filePath)
    {
        $fileName = basename($filePath);

        $matches = array();
        $mediaItem = null;

        if (preg_match(self::REGEX_SERIES, $fileName, $matches) > 0) {
            $mediaItem = new MediaItemSeries();
            $mediaItem->name = $matches['Name'];
            $mediaItem->season = intval($matches['Season']);
            $mediaItem->episode = intval($matches['Episode']);
            if (!empty($matches['Format'])) {
                $mediaItem->format = $matches['Format'];
            }
        } else {
            if (preg_match(self::REGEX_MOVIES, $fileName, $matches) > 0) {
                $mediaItem = new MediaItemMovie();
                $mediaItem->name = $matches['Name'];
                $mediaItem->year = $matches['Year'];
                if (!empty($matches['Format'])) {
                    $mediaItem->format = $matches['Format'];
                }
            } else {
                $mediaItem = new MediaItemMovie();
                $mediaItem->name = substr($fileName, 0, strpos($fileName, '.'));
            }
        }
        $mediaItem->filePath = $filePath;
        $mediaItem->extension = Utils::getFileExtension($mediaItem->filePath);
        $mediaItem->originalFileName = $fileName;
        $mediaItem->fixName();

        if ($GLOBALS['DEBUG']) {
            echo "Item '" . $mediaItem->originalFileName . " identified as '" . $mediaItem->name . "'\n";
        }

        // Search subtitles
        $dir = dirname($mediaItem->filePath);
        if (file_exists($dir)) {
            $files = scandir($dir);
            foreach ($files as $file) {
                if (is_dir($file) === false) {
                    $extension = Utils::getFileExtension($file);
                    if (in_array($extension, self::$mediaSubtitlesExtensions)) {
                        $assocMedia = Utils::changeExtension($file, $mediaItem->extension);
                        if ($assocMedia === $mediaItem->originalFileName) {
                            $mediaItem->subtitles[] = realpath($dir . '/' . $file);
                        }
                    }
                }
            }
        }

        return $mediaItem;
    }

    protected function fixName()
    {
        Utils::fixName($this);
    }

    public function getYear()
    {
        if (empty($this->year)) {
            $time = strtotime($this->released);
            $this->year = date('Y', $time);
        }
        return $this->year;
    }

    public function getThumbPath()
    {
        $extension = empty($GLOBALS['THUMB_EXTENSION']) ? 'jpg' : $GLOBALS['THUMB_EXTENSION'];
        return Utils::changeExtension($this->filePath, $extension);
    }

    public function getMetadataPath()
    {
        return Utils::changeExtension($this->filePath, 'xml');
    }

    public function getSubtitlePath($extension = 'srt')
    {
        return Utils::changeExtension($this->filePath, $extension);
    }

    /**
     * @return string Fixed file name
     */
    public function getNewFilename()
    {
        $str = $this->toString() . '.' . $this->extension;
        return Utils::getValidFileSystemString($str);
    }

    /**
     * @return string String representation
     */
    abstract public function toString();

    /**
     * @return array Retrieves all the directories that has to be created to organize this item
     */
    abstract public function getFolderStructure();

    /**
     * @return string XML Metadata for the current media item
     */
    abstract public function getMetadata();

    public function fetchImdbId()
    {
        $searchUrl = str_replace('{NAME}', urlencode($this->name), MediaItem::URL_SEARCH_IMDB);
        if (!empty($this->year)) {
            $searchUrl .= '&year=' . $this->year;
        }

        if (empty(MediaItem::$ImdbCache[$searchUrl])) {
            $searchHtml = NetManager::requestUrl($searchUrl);
            $matches = array();
            $pos = stripos($searchHtml, '<table class="results">');
            if ($pos === false) {
                return error_log("Cannot find Metadata for " . $this->filePath . ' (' . $searchUrl . ')' . "\n");
            }
            $results = substr($searchHtml, $pos);
            $pos = stripos($results, '<div class="leftright">');
            if ($pos !== false) {
                $results = substr($results, 0, $pos);
            }
            if (preg_match(self::REGEX_SEARCH_IMDB_ID, $results, $matches) === 0) {
                return error_log("Cannot find Metadata for " . $this->filePath . ' (' . $searchUrl . ')' . "\n");
            }
            $this->imdbId = $matches['ID'];
            if ($GLOBALS['DEBUG']) {
                echo('IMDB ID: ' . $this->imdbId . "\n");
            }
            MediaItem::$ImdbCache[$searchUrl] = $this->imdbId;
        } else {
            $this->imdbId = MediaItem::$ImdbCache[$searchUrl];
        }

        return $this->imdbId;
    }
}

