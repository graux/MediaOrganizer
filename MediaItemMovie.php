<?php
/**
 * Description of MediaItemMovie
 *
 * @author Francisco Grau <grau.fran@gmail.com>
 */
class MediaItemMovie extends MediaItem
{

    public $tagline = null;

    protected function __construct()
    {

    }

    public function toString($includeTagLine = false)
    {
        $str = (empty($this->title) ? $this->name : $this->title) . ' (' . $this->getYear() . ')';
        if ($includeTagLine == true && !empty($this->tagline)) {
            $str .= ' - ' . $this->tagline;
        }
        return $str;
    }

    public function getFolderStructure()
    {
        if (empty($GLOBALS['CREATE_MOVIE_DIRECTORY']) || $GLOBALS['CREATE_MOVIE_DIRECTORY'] !== true) {
            return array();
        }
        return Utils::getValidFileSystemString($this->title . ' (' . $this->getYear() . ')');
    }

    public function getMetadata()
    {
        $details = new SimpleXMLElement('<details></details>');
        $details->addChild('id', $this->id);
        $details->addChild('title', $this->title);
        $details->addChild('original_title', $this->originalTitle);
        $details->addChild('year', $this->released);
        $details->addChild('runtime', $this->runTime);
        $details->addChild('rating', $this->rating);
        $details->addChild('overview', $this->overview);
        $details->addChild('imdb_id', $this->imdbId);
        $details->addChild('director', implode(', ', $this->directors));
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

