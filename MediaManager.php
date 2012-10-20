<?php

require_once 'Utils.php';
require_once 'MediaItem.php';
require_once 'MovieDbMetadataManager.php';
require_once 'TvDbMetadataManager.php';
require_once 'MediaItemMovie.php';
require_once 'MediaItemSeries.php';

if(empty($argv[1])){
    $argv[1] = '/Users/Fran/Movies';
}
$targetDir = $argv[1];

require_once 'config.php';

if (file_exists($targetDir)) {
    $mediaManger = new MediaManager($targetDir);
    $mediaManger->fetchMediaItemsData();
    $files = $mediaManger->getMediaItems();
    foreach($files as $f){
        echo $f->toString()."\n";
    }
}


class MediaManager {

    private $baseDir = null;
    private $mediaExtensions = array('avi', 'ogv', 'flv', 'mpg', 'xvid', 'mkv', 'mov', 'mp4', 'm4v');
    private $mediaFiles = array();
    private $mediaItems = array();
    
    public function __construct($baseDir) {
        echo "MEDIA MANAGER: ".$baseDir."\n";
        $this->baseDir = $baseDir;
        $this->scanForMediaFiles($this->baseDir);
        foreach($this->mediaFiles as $file){
            if(stripos($file, 'sample') === false){
                $this->mediaItems[] = MediaItem::createMediaItem($file);
            }
        }
    }
    
    public function fetchMediaItemsData(){
        $tvDb = TvDbMetadataManager::getInstance();
        $tmDb = MovieDbMetadataManager::getInstance();
        foreach($this->mediaItems  as $item){
            if($item instanceof MediaItemSeries){
               $tvDb->fetchMediaItemData($item);
            }else{
                $tmDb->fetchMediaItemData($item);
            }
        }
    }

    private function scanForMediaFiles($dir) {
        $files = scandir($dir);

        foreach ($files as $file) {
            if (substr($file, 0, 1) === '.') {
                continue;
            }
            $fullPath = realpath($dir).'/'.$file;
            if (is_dir($fullPath)) {
                $this->scanForMediaFiles($fullPath);
            } else {
                $extension = $this->getFileExtension($file);
                if (in_array($extension, $this->mediaExtensions)) {
                    $this->mediaFiles[] = $fullPath;
                }
            }
        }
    }

    public function getMediaFiles(){
        return $this->mediaFiles;
    }
    public function getMediaItems(){
        return $this->mediaItems;
    }
}