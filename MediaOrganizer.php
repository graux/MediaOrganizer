<?php

/**
 * MediaOrganizer - https://github.com/graux/MediaOrganizer
 * Copyright (C) 2012 Francisco Grau <grau.fran@gmail.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */
require_once 'Utils.php';
require_once 'MediaItem.php';
require_once 'MovieDbMetadataManager.php';
require_once 'TvDbMetadataManager.php';
require_once 'MediaItemMovie.php';
require_once 'MediaItemSeries.php';

if (empty($argv[1])) {
    $argv[1] = '/Users/Fran/Movies';
}
$targetDir = $argv[1];

require_once 'config.php';

if (file_exists($targetDir)) {
    $targetDir = realpath($targetDir);
    $mediaManager = new MediaOrganizer($targetDir);
    $mediaManager->fetchMediaItemsData();
    $files = $mediaManager->getMediaItems();
    $mediaManager->organizeFiles($targetDir, $targetDir);
    $mediaManager->downloadPosters();
    $mediaManager->generateMetadata();
    foreach ($files as $f) {
        echo "Metadata and Images generated for: " . $f->toString() . "\n";
    }
}

/**
 * MediaOrganizer Class
 * 
 * @author Francisco Grau <grau.fran@gmail.com>
 */
class MediaOrganizer
{
    private $baseDir = null;
    private $mediaExtensions = array('avi', 'ogv', 'flv', 'mpg', 'xvid', 'mkv', 'mov', 'mp4', 'm4v');
    private $mediaFiles = array();
    private $mediaItems = array();
    private $downloadedFiles = array();

    public function __construct($baseDir)
    {
        echo "MEDIA MANAGER: " . $baseDir . "\n";
        $this->baseDir = $baseDir;
        $this->scanForMediaFiles($this->baseDir);
        if (empty($this->mediaFiles)) {
            echo "No Media Items found\n";
        } else if ($GLOBALS['DEBUG']) {
            echo "\n";
        }
        foreach ($this->mediaFiles as $file) {
            $this->mediaItems[] = MediaItem::createMediaItem($file);
        }
    }

    public function fetchMediaItemsData()
    {
        $tvDb = TvDbMetadataManager::getInstance();
        $tmDb = MovieDbMetadataManager::getInstance();
        foreach ($this->mediaItems as $item) {
            if ($GLOBALS['DEBUG']) {
                echo "Fetching Data for: " . $item->name . "\n";
            }
            if ($item instanceof MediaItemSeries) {
                $tvDb->fetchMediaItemData($item);
            } else {
                $tmDb->fetchMediaItemData($item);
            }
        }
    }

    private function scanForMediaFiles($dir)
    {
        $files = scandir($dir);

        foreach ($files as $file) {
            if (substr($file, 0, 1) === '.') {
                continue;
            }
            $fullPath = realpath($dir) . '/' . $file;
            if (is_dir($fullPath)) {
                $this->scanForMediaFiles($fullPath);
            } else {
                $extension = Utils::getFileExtension($file);
                if (in_array($extension, $this->mediaExtensions)) {
                    if (stripos($fullPath, 'sample') === false) {
                        if ($GLOBALS['DEBUG'] === true) {
                            echo "Media Item Found: $file\n";
                        }
                        $this->mediaFiles[] = $fullPath;
                    }
                }
            }
        }
    }

    public function getMediaFiles()
    {
        return $this->mediaFiles;
    }

    public function getMediaItems()
    {
        return $this->mediaItems;
    }

    public function organizeFiles($seriesFolder, $moviesFolder)
    {
        $itemPath = null;
        foreach ($this->mediaItems as $mItem) {
            if (get_class($mItem) == 'MediaItemSeries') {
                if (file_exists($seriesFolder) === false) {
                    mkdir($seriesFolder);
                }
                $itemPath = $seriesFolder;
            } else {
                if (file_exists($moviesFolder) === false) {
                    mkdir($moviesFolder);
                }
                $itemPath = $moviesFolder;
            }

            $folderStructure = $mItem->getFolderStructure();
            foreach ($folderStructure as $folder) {
                $itemPath = $itemPath . '/' . $folder;
                if (file_exists($itemPath) === false) {
                    mkdir($itemPath);
                }
            }
            $itemPath = $itemPath . '/' . $mItem->getNewFilename();
            if ($mItem->filePath != $itemPath) {
                rename($mItem->filePath, $itemPath);
                foreach ($mItem->subtitles as $sub) {
                    $itemExt = Utils::getFileExtension($sub);
                    $targetSub = Utils::changeExtension($itemPath, $itemExt);
                    rename($sub, $targetSub);
                }
                $oldDir = dirname($mItem->filePath);
                $files = scandir($oldDir);
                if (count($files) == 2) {
                    rmdir($oldDir);
                }
                $mItem->filePath = $itemPath;
            }
        }
    }

    public function downloadPosters()
    {
        foreach ($this->mediaItems as $mItem) {
            $targetPath = $mItem->getThumbPath();
            if (empty($this->downloadedFiles[$targetPath])) {
                if (file_exists($targetPath) === false) {
                    Utils::downloadThumbnail($mItem->posterUrl, $targetPath);
                }
                $this->downloadedFiles[$mItem->posterUrl] = $targetPath;
            } else {
                copy($this->downloadedFiles[$mItem->posterUrl], $targetPath);
            }
            $dirPath = dirname($mItem->filePath);
            if (get_class($mItem) == 'MediaItemSeries' && $GLOBALS['CREATE_SERIES_DIRECTORY']) {
                $dirPath = dirname($targetPath);
                if($GLOBALS['CREATE_SEASON_DIRECTORY']){
                    $filename = $dirPath.'/folder.jpg';
                    if (file_exists($filename) === false) {
                        copy($targetPath, $filename);
                    }
                    $dirPath = dirname($dirPath);
                }
                $filename = $dirPath.'/folder.jpg';
                if (file_exists($filename) === false) {
                    copy($targetPath, $dirPath.'/folder.jpg');
                }
            }
            if ($GLOBALS['DOWNLOAD_BACKDROPS']) {
                $index = 1;
                $dir = $dirPath . '/.backdrops';
                if (file_exists($dir) === false) {
                    mkdir($dir);
                }
                $newBackdrops = array();
                foreach ($mItem->backdrops as $url) {
                    $backdropFilePath = $dir . '/' . $mItem->originalTitle . ' - ' . $index . '.jpg';
                    if (file_exists($backdropFilePath) === false) {
                        Utils::downloadThumbnail($url, $backdropFilePath);
                    }
                    $newBackdrops[] = $backdropFilePath;
                    $index++;
                }
                $mItem->backdrops = $newBackdrops;
            }
        }
    }

    public function generateMetadata()
    {
        foreach ($this->mediaItems as $mItem) {
            $targetPath = $mItem->getMetadataPath();
            $metadata = $mItem->getMetadata();
            file_put_contents($targetPath, $metadata);

            if ($GLOBALS['CREATE_SERIES_DIRECTORY'] && $GLOBALS['WDLIVETV_FOLDERS']) {
                if (get_class($mItem) == 'MediaItemSeries') {
                    $dir = dirname($mItem->filePath);
                    if ($GLOBALS['CREATE_SEASON_DIRECTORY']) {
                        $seasonMetadataFilePath = Utils::changeExtension($dir, 'xml');
                        if (file_exists($seasonMetadataFilePath) === false) {
                            $seasonMetadata = $mItem->getSeriesSeasonMetadata();
                            file_put_contents($seasonMetadataFilePath, $seasonMetadata);
                        }
                        $dir = dirname($dir);
                    }
                    $seriesMetadataFilePath = Utils::changeExtension($dir, 'xml');
                    if (file_exists($seriesMetadataFilePath) === false) {
                        $seriesMetadata = $mItem->getSeriesMetadata($dir);
                        file_put_contents($seriesMetadataFilePath, $seriesMetadata);
                    }
                }
            }
        }
    }
}