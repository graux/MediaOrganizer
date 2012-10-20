<?php
/**
 * Description of MediaItemMovie
 *
 * @author Fran
 */
class MediaItemMovie extends MediaItem {
    
    protected function __construct()
    {
        
    }
    
    public function getNewFilename()
    {
        return $this->originalTitle.' ('.$this->getYear().').'.$this->extension;
    }

    public function toString()
    {
        return $this->originalTitle.' ('.$this->getYear().')';
    }
}

