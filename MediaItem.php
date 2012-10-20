<?php

/**
 * Description of MediaItem
 *
 * @author Fran
 */
abstract class MediaItem
{
    public $filePath = null;
    public $year = null;
    public $name = null;
    public $format = null;
    public $extension = null;
    public $overview = null;
    public $released = null;
    public $posterUrl = null;
    public $id = null;
    public $originalTitle = null;
    public $runTime = null;
    public $rating = null;
    private $error = false;

    const REGEX_SERIES = '/^(?P<Name>.*?)(s|S)\(?(?P<Season>\d\d)_?(e|E)(?P<Episode>\d\d)\)?.*?(?P<Format>(720p)|(1080p))?.*?\.[a-z0-9]{1,4}$/';
    const REGEX_MOVIES = '/^(?P<Name>.*?)(\(?(?P<Year>\d\d\d\d)\)?)(.*?)(?P<Format>(720p)|(1080p))?(.*?)\.[a-z0-9]{1,4}$/';

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
            $mediaItem->season = $matches['Season'];
            $mediaItem->episode = $matches['Episode'];
            if (!empty($matches['Format'])) {
                $mediaItem->format = $matches['Format'];
            }
        } else if (preg_match(self::REGEX_MOVIES, $fileName, $matches) > 0) {
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
        $mediaItem->filePath = $filePath;
        $mediaItem->extension = Utils::getFileExtension($mediaItem->filePath);
        $mediaItem->fixName();
        
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
            $this->year = date('YY', $time);
        }
        return $this->year;
    }

    /**
     * @return string String representation
     */
    abstract public function toString();

    /**
     * @return string Fixed file name
     */
    abstract public function getNewFilename();
}
