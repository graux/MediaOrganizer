<?php
/**
 * Description of MediaItemMovie
 *
 * @author Francisco Grau <grau.fran@gmail.com>
 */
class MediaItemMovie extends MediaItem {
    
    protected function __construct()
    {
        
    }
    
    public function toString()
    {
        return $this->originalTitle.' ('.$this->getYear().')';
    }
    
    public function getFolderStructure()
    {
        if(empty($GLOBALS['CREATE_MOVIE_DIRECTORY']) || $GLOBALS['CREATE_MOVIE_DIRECTORY'] !== true){
            return array();
        }
        return $this->originalTitle.' ('.$this->getYear().')';
    }

    /**
     * 
     * @param type $basePath
     * @todo Implement this method
     */
    public function getMetadata()
    {
        
    }
}

