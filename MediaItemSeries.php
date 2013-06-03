<?php

/**
 * Description of MediaItemSeries
 *
 * @author Francisco Grau <grau.fran@gmail.com>
 */
class MediaItemSeries extends MediaItem
{
    public $season = null;
    public $episode = null;
    public $episodeOverview = null;
    public $episodeTitle = null;
    public $episodeRating = null;
    public $episodeId = null;
    public $episodeDirector = array();

    protected function __construct()
    {

    }

    public function getFolderStructure()
    {
        $dirSeries = empty($GLOBALS['CREATE_SERIES_DIRECTORY']) ? true : $GLOBALS['CREATE_SERIES_DIRECTORY'] !== false;
        $dirSeason = empty($GLOBALS['CREATE_SEASON_DIRECTORY']) ? true : $GLOBALS['CREATE_SEASON_DIRECTORY'] !== false;
        $structure = array();
        if ($dirSeries) {
            $folder = $this->title;
            if ($GLOBALS['WDLIVETV_FOLDERS'] === true) {
                $folder .= '.mkv';
            }
            $structure[] = Utils::getValidFileSystemString($folder);
        }
        if ($dirSeason) {
            $pattern = empty($GLOBALS['SEASON_DIRECTORY_PATERN']) ? 'Season {N}' : $GLOBALS['SEASON_DIRECTORY_PATERN'];
            $folder = str_replace('{N}', $this->season, $pattern);
            if ($GLOBALS['WDLIVETV_FOLDERS'] === true) {
                $folder .= '.mkv';
            }
            $structure[] = $folder;
        }
        return $structure;
    }

    public function getMetadata()
    {
        $details = new SimpleXMLElement('<details></details>');
        $details->addChild('id', $this->episodeId);
        $details->addChild('title', $this->toString());
        $details->addChild('series_name', $this->title);
        $details->addChild('episode_name', $this->episodeTitle);
        $details->addChild('season_number', $this->season);
        $details->addChild('episode_number', $this->episode);
        $details->addChild('firstaired', $this->released);
        $details->addChild('runtime', $this->runTime);
        $details->addChild('rating', $this->episodeRating);
        $details->addChild('director', implode(', ', $this->episodeDirector));
        foreach ($this->genere as $genre) {
            $details->addChild('genre', $genre);
        }
        foreach ($this->actors as $actor) {
            $actorXml = $details->addChild('actor');
            $actorXml->addChild('name', $actor);
        }
        $details->addChild('overview', $this->episodeOverview);
        $details->addChild('imdb_id', $this->imdbId);

        $baseDir = './';
        if ($GLOBALS['CREATE_SEASON_DIRECTORY']) {
            $baseDir .= '../';
        }
        $baseDir .= '.backdrops';

        foreach ($this->backdrops as $backdrop) {
            if (strpos($backdrop, 'http') === false) {
                $backdrop = $baseDir . '/' . basename($backdrop);
            }
            $details->addChild('backdrop', $backdrop);
        }

        return $details->asXML();
    }

    public function toString()
    {
        if (!empty($this->episodeTitle)) {
            return $this->title . ' - ' . $this->getEpisodeKey() . ' - ' . $this->episodeTitle;
        } elseif (!empty($this->title)) {
            return $this->title . ' - ' . $this->getEpisodeKey();
        } else {
            return $this->name . ' - ' . $this->getEpisodeKey();
        }
    }

    public function getEpisodeKey()
    {
        return 'S' . sprintf('%02d', $this->season) . 'E' . sprintf('%02d', $this->episode);
    }

    public function getSeriesMetadata($basePath)
    {
        $details = new SimpleXMLElement('<details></details>');
        $details->addChild('id', $this->id);
        $details->addChild('title', $this->title);
        $details->addChild('year', $this->released);
        $details->addChild('runtime', $this->runTime);
        $details->addChild('rating', $this->rating);
        // $details->addChild('director', $this->director);
        $details->addChild('overview', $this->overview);
        $details->addChild('imdb_id', $this->imdbId);
        foreach ($this->genere as $genre) {
            $details->addChild('genre', $genre);
        }
        foreach ($this->actors as $actor) {
            $actorXml = $details->addChild('actor');
            $actorXml->addChild('name', $actor);
        }

        $basePath = str_replace(dirname($basePath), '', $basePath);
        foreach ($this->backdrops as $backdrop) {
            if (strpos($backdrop, 'http') === false) {
                $backdrop = '.' . $basePath . '/.backdrops/' . basename($backdrop);
            }
            $details->addChild('backdrop', $backdrop);
        }
        return $details->asXML();
    }

    public function getSeriesSeasonMetadata()
    {
        $details = new SimpleXMLElement('<details></details>');
        $details->addChild('id', $this->id);
        $details->addChild('title', $this->title . ' - Season ' . $this->season);
        $details->addChild('year', $this->released);
        $details->addChild('runtime', $this->runTime);
        $details->addChild('rating', $this->rating);
        $details->addChild('overview', $this->overview);
        $details->addChild('imdb_id', $this->imdbId);
        foreach ($this->genere as $genre) {
            $details->addChild('genre', $genre);
        }
        foreach ($this->actors as $actor) {
            $actorXml = $details->addChild('actor');
            $actorXml->addChild('name', $actor);
        }

        foreach ($this->backdrops as $backdrop) {
            if (strpos($backdrop, 'http') === false) {
                $backdrop = './.backdrops/' . basename($backdrop);
            }
            $details->addChild('backdrop', $backdrop);
        }
        return $details->asXML();
    }
}

