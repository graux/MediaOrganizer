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
require_once 'SubDbMetadataManager.php';
require_once 'MediaItemMovie.php';
require_once 'MediaItemSeries.php';

$shortOpts = array('s', 't:', 'h');

MediaOrganizer::showVersionInfo();

$configFile = dirname(__FILE__) . '/config.php';
if (file_exists($configFile) === false) {
    error_log("ERROR: Missing config.php file...");
    error_log("Please copy sample-config.php to config.php and adjust your settings.\n");
    exit();
}

require_once $configFile;

$parameters = getopt(implode('', $shortOpts));
if ($parameters === false || isset($parameters['h'])) {
    MediaOrganizer::showHelp($parameters === false);
} elseif ($GLOBALS['DEBUG'] === true && isset($parameters['t']) === false) {
    $parameters['t'] = '/Users/Fran/Movies';
}
$targetDir = $parameters['t'];
$subtitleMode = isset($parameters['s']) ? true : false;
$GLOBALS['$targetPath'] = $subtitleMode;

if ($GLOBALS['DEBUG'] === true) {
    echo "\nRunning MediaOrganizer, target directory: $targetDir\n";
}

date_default_timezone_set($GLOBALS['TIMEZONE']);

if (file_exists($targetDir)) {
    $targetDir = realpath($targetDir);
    $mediaManager = new MediaOrganizer($targetDir);
    $files = array();
    if ($subtitleMode === false) {
        $mediaManager->fetchMediaItemsData();
        $files = $mediaManager->getMediaItems();
        if ($GLOBALS['MOVE_FILES'] === true) {
            $mediaManager->organizeFiles($GLOBALS['SERIES_FOLDER'], $GLOBALS['MOVIES_FOLDER']);
        }
        if ($GLOBALS['DOWNLOAD_POSTERS'] === true) {
            $mediaManager->downloadPosters();
        }
        $mediaManager->generateMetadata();
        if ($GLOBALS['DOWNLOAD_SUBTITLES'] === true) {
            $mediaManager->downloadSubtitles();
        }
    } else {
        $files = $mediaManager->getMediaItems();
        if ($GLOBALS['DEBUG'] === true) {
            echo "\nSubtitle only mode enabled...\n";
        }
        $mediaManager->downloadSubtitles();
    }
    echo "\nFiles Processed: \n";
    $errorItems = array();
    $skippedItems = array();
    foreach ($files as $f) {
        if ($f->metadataProcessed === true) {
            echo "Metadata and Images generated for: " . $f->toString() . "\n";
        } elseif ($f->skip == true) {
            $skippedItems[] = $f;
        } else {
            $errorItems[] = $f;
        }
    }
    if (!empty($skippedItems)) {
        echo "\nSkipped: \n";
        foreach ($skippedItems as $f) {
            echo "Item already processed: " . $f->originalFileName . "\n";
        }
    }
    if (!empty($errorItems)) {
        echo "\nErrors: \n";
        foreach ($errorItems as $f) {
            echo "Error identifying or retrieving data for: " . $f->originalFileName . "\n";
        }
    }
    echo "\n\n";
}

/**
 * MediaOrganizer Class
 *
 * @author Francisco Grau <grau.fran@gmail.com>
 */
class MediaOrganizer
{
    const VERSION = '1.2';
    private $baseDir = null;
    private $mediaExtensions = array('avi', 'ogv', 'flv', 'mpg', 'xvid', 'mkv', 'mov', 'mp4', 'm4v');
    /** @var string[] */
    private $mediaFiles = array();
    /** @var MediaItem[] */
    private $mediaItems = array();
    private $downloadedFiles = array();

    public function __construct($baseDir)
    {
        echo "MEDIA MANAGER: " . $baseDir . "\n";
        $this->baseDir = $baseDir;
        $this->scanForMediaFiles($this->baseDir);
        if (empty($this->mediaFiles)) {
            echo "No Media Items found\n";
        } else {
            if ($GLOBALS['DEBUG']) {
                echo "\n";
            }
        }
        foreach ($this->mediaFiles as $file) {
            $this->mediaItems[] = MediaItem::createMediaItem($file);
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

    public static function showVersionInfo()
    {
        echo("\nMedia Organizer " . MediaOrganizer::VERSION . "\n");
        echo("Francisco Grau - 2013 - GPL3 - http://github.com/graux/MediaOrganizer\n");
    }

    public static function showHelp($paramError)
    {
        if ($paramError === true) {
            error_log("\nInvalid Parameters.");
        }
        error_log("\nUsage: php MediaOrganizer.php --target [DIR]\n");
        error_log("Parameters:");
        error_log("  -t\t\t\tDefines the target directory to search files.");
        error_log("  -h\t\t\tDisplays this help.");
        error_log("  -s\t\t\tSearches for subtitles only.");
        error_log("\n");
        exit();
    }

    public function fetchMediaItemsData()
    {
        $tvDb = TvDbMetadataManager::getInstance();
        $tmDb = MovieDbMetadataManager::getInstance();
        $totalItems = count($this->mediaItems);
        $index = 1;
        if ($GLOBALS['DEBUG']) {
            echo "\nProcessing Media Items (" . $totalItems . "):\n";
        }

        $errors = 0;
        $skipped = 0;
        foreach ($this->mediaItems as $item) {
            $metadataFilePath = Utils::changeExtension($item->filePath, 'xml');
            if (file_exists($metadataFilePath) && $GLOBALS['SKIP_IF_METADATA_PRESENT'] === true) {
                $item->skip = true;
                if ($GLOBALS['DEBUG']) {
                    echo "\nItem Skipped: " . $item->name . ' (' . $index . '/' . $totalItems . ')' . "\n";
                }
                $skipped++;
            } else {
                if ($GLOBALS['DEBUG']) {
                    echo "\nFetching Data for: " . $item->name . ' (' . $index . '/' . $totalItems . ')' . "\n";
                }
                if ($item instanceof MediaItemSeries) {
                    $tvDb->fetchMediaItemData($item);
                } else {
                    $tmDb->fetchMediaItemData($item);
                }
                if ($item->error) {
                    $errors++;
                }
            }
            $index++;
        }
        if ($GLOBALS['DEBUG']) {
            echo "\n\nMetadata for " . ($totalItems - ($errors + $skipped)) . " of " . $totalItems . " items found.\n\n";
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
            if ($mItem->metadataProcessed == false || $mItem->error == true || $mItem->skip == true) {
                continue;
            }
            if (get_class($mItem) == 'MediaItemSeries') {
                if (file_exists($seriesFolder) === false) {
                    $this->mkDir($seriesFolder);
                }
                $itemPath = $seriesFolder;
            } else {
                if (file_exists($moviesFolder) === false) {
                    $this->mkDir($moviesFolder);
                }
                $itemPath = $moviesFolder;
            }

            $folderStructure = $mItem->getFolderStructure();
            foreach ($folderStructure as $folder) {
                $itemPath = $itemPath . '/' . $folder;
                if (file_exists($itemPath) === false) {
                    $this->mkDir($itemPath);
                }
            }
            $itemPath = $itemPath . '/' . $mItem->getNewFilename();
            if ($mItem->filePath != $itemPath) {
                $this->moveFile($mItem->filePath, $itemPath);
                foreach ($mItem->subtitles as $sub) {
                    $itemExt = Utils::getFileExtension($sub);
                    $targetSub = $mItem->getSubtitlePath($itemExt);
                    $this->moveFile($sub, $targetSub);
                }
                $oldDir = dirname($mItem->filePath);
                $this->rmDir($oldDir);
                $mItem->filePath = $itemPath;
            }
        }
    }

    private function mkDir(&$dirPath)
    {
        if ($GLOBALS['DRY_RUN'] === false) {
            mkdir($dirPath);
        }
        if ($GLOBALS['DEBUG'] === true) {
            echo '[+] MkDir: ' . $dirPath . "\n";
        }
    }

    private function moveFile($source, &$target)
    {
        if ($source == $target) {
            return;
        }

        if ($GLOBALS['DRY_RUN'] === false) {
            $dotPos = strrpos($target, '.');
            $originalTarget = substr($target, 0, $dotPos);
            $extension = substr($target, $dotPos + 1);
            $index = 2;
            while (file_exists($target)) {
                $target = $originalTarget . ' (' . $index . ').' . $extension;
                $index++;
            }
            rename($source, $target);
        }
        if ($GLOBALS['DEBUG'] === true) {
            echo '[*] MoveFile: ' . $source . ' => ' . $target . "\n";
        }
    }

    private function rmDir($dirPath)
    {
        if ($GLOBALS['DRY_RUN'] === false) {
            $files = scandir($dirPath);
            if (count($files) == 2) {
                rmdir($dirPath);
            }
        }
        if ($GLOBALS['DEBUG'] === true) {
            echo '[-] RmDir: ' . $dirPath . "\n";
        }
    }

    public function downloadPosters()
    {
        foreach ($this->mediaItems as $mItem) {
            if ($mItem->error === true || $mItem->skip === true) {
                continue;
            }
            $targetPath = $mItem->getThumbPath();
            if (empty($this->downloadedFiles[$targetPath])) {
                if (file_exists($targetPath) === false) {
                    Utils::downloadThumbnail($mItem->posterUrl, $targetPath);
                }
                $this->downloadedFiles[$mItem->posterUrl] = $targetPath;
            } else {
                $this->copyFile($this->downloadedFiles[$mItem->posterUrl], $targetPath);
            }
            $dirPath = dirname($mItem->filePath);
            if (get_class($mItem) == 'MediaItemSeries' && $GLOBALS['CREATE_SERIES_DIRECTORY']) {
                $dirPath = dirname($targetPath);
                if ($GLOBALS['CREATE_SEASON_DIRECTORY']) {
                    $filename = $dirPath . '/folder.jpg';
                    if (file_exists($filename) === false) {
                        $this->copyFile($targetPath, $filename);
                    }
                    $dirPath = dirname($dirPath);
                }
                $filename = $dirPath . '/folder.jpg';
                if (file_exists($filename) === false) {
                    $this->copyFile($targetPath, $dirPath . '/folder.jpg');
                }
            }
            if ($GLOBALS['DOWNLOAD_BACKDROPS']) {
                $index = 1;
                $dir = $dirPath . '/.backdrops';
                if (file_exists($dir) === false) {
                    $this->mkDir($dir);
                }
                $newBackdrops = array();
                foreach ($mItem->backdrops as $url) {
                    $backdropFilePath = $dir . '/' . $mItem->title . ' - ' . $index . '.jpg';
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

    private function copyFile($source, $target)
    {
        if ($GLOBALS['DRY_RUN'] === false) {
            copy($source, $target);
        }
        if ($GLOBALS['DEBUG'] === true) {
            echo '[*] CopyFile: ' . $source . ' => ' . $target . "\n";
        }
    }

    public function generateMetadata()
    {
        foreach ($this->mediaItems as $mItem) {
            if ($mItem->error === true || $mItem->skip === true) {
                continue;
            }
            $targetPath = $mItem->getMetadataPath();
            $metadata = $mItem->getMetadata();
            $this->createFile($targetPath, $metadata);

            if ($GLOBALS['CREATE_SERIES_DIRECTORY'] && $GLOBALS['WDLIVETV_FOLDERS']) {
                if (get_class($mItem) == 'MediaItemSeries') {
                    $dir = dirname($mItem->filePath);
                    if ($GLOBALS['CREATE_SEASON_DIRECTORY']) {
                        $seasonMetadataFilePath = Utils::changeExtension($dir, 'xml');
                        if (file_exists($seasonMetadataFilePath) === false) {
                            $seasonMetadata = $mItem->getSeriesSeasonMetadata();
                            $this->createFile($seasonMetadataFilePath, $seasonMetadata);
                        }
                        $dir = dirname($dir);
                    }
                    $seriesMetadataFilePath = Utils::changeExtension($dir, 'xml');
                    if (file_exists($seriesMetadataFilePath) === false) {
                        $seriesMetadata = $mItem->getSeriesMetadata($dir);
                        $this->createFile($seriesMetadataFilePath, $seriesMetadata);
                    }
                }
            }
        }
    }

    private function createFile($filePath, $contents)
    {
        if ($GLOBALS['DRY_RUN'] === false) {
            file_put_contents($filePath, $contents);
        }
        if ($GLOBALS['DEBUG'] === true) {
            echo '[+] CreateFile: ' . $filePath . ' : ' . substr($contents, 0, 100) . "\n";
        }
    }

    public function downloadSubtitles()
    {
        $subDb = SubDbMetadataManager::getInstance();
        $subLang = $GLOBALS['SUBTITLES_LANGUAGE'];
        if ($GLOBALS['DEBUG'] === true) {
            echo "\nSearching and downloading Subtitles...\n\n";
        }
        foreach ($this->mediaItems as $mItem) {
            if ($mItem->error === true || $mItem->skip === true) {
                continue;
            }
            $targetPath = $mItem->getSubtitlePath();
            if (file_exists($targetPath)) {
                if ($GLOBALS['subtitleMode'] === true) {
                    $mItem->skip = true;
                }
                continue;
            }
            if ($GLOBALS['DEBUG'] === true) {
                echo '[?] Searching Subtitle for: ' . $mItem->toString() . " ...";
            }
            $subtitle = $subDb->fetchMediaItemSubtitle($mItem->filePath, $subLang);
            if (!is_null($subtitle)) {
                $targetPath = $mItem->getSubtitlePath();
                echo "\n";
                $this->createFile($targetPath, $subtitle);
                if ($GLOBALS['subtitleMode'] === true) {
                    $mItem->metadataProcessed = true;
                }
            } else {
                if ($GLOBALS['DEBUG'] === true) {
                    echo "\n";
                }
            }
        }
    }
}
