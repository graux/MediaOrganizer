<?php

/**
 * Description of MediaItemSeries
 *
 * @author Fran
 */
class MediaItemSeries extends MediaItem {
    public $season = null;
    public $episode = null;
    public $episodeOverview = null;
    public $episodeTitle = null;
    public $episodeRating = null;
    
    protected function __construct()
    {
        
    }
    
    public function getEpisodeKey()
    {
        return 'S' . sprintf('%02d', $this->season) . 'E' . sprintf('%02d', $this->episode);
    }

    public function getNewFilename()
    {
        return $this->originalTitle.' - '.$this->getEpisodeKey().' - '.$this->episodeTitle.'.'.$this->extension;
    }

    public function toString()
    {
        return $this->originalTitle.' - '.$this->getEpisodeKey().' - '.$this->episodeTitle;
    }
}
