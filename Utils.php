<?php

/**
 * Description of Utils
 *
 * @author Fran
 */
class Utils
{
    public static function getFileExtension($file) {
        return substr($file, strrpos($file, '.') + 1);
    }
    /**
     * 
     * @param MediaItem $mItem
     */
    public static function fixName($mItem){
        if (strpos($mItem->name, '720') > 0) {
            $mItem->name = preg_replace('/720p?/', '', $mItem->name);
            $mItem->format = '720p';
        } else if (strpos($mItem->name, '1080') > 0) {
            $mItem->name = preg_replace('/1080p?/', '', $mItem->name);
            $mItem->format = '1080p';
        } else {
            if (strpos($mItem->filePath, '720') !== false) {
                $mItem->format = '720p';
            } else if (strpos($mItem->filePath, '1080') !== false) {
                $mItem->format = '1080p';
            }
        }
        $mItem->name = preg_replace('/(DVDRip|BRRip|BluRayRip|HDTV|dts|x264|xvid)/i', '', $mItem->name);
        $mItem->name = preg_replace('/(\[.*?\]|\(.*?\))/', ' ', $mItem->name);
        $mItem->name = strtr($mItem->name, array('.' => ' ', '_' => ' ', '-' => ' ', '(' => '', ')' => '', '[' => '', ']' => ''));
        $mItem->name = preg_replace('/\s+/', ' ', $mItem->name);
        $mItem->name = trim(ucwords($mItem->name));
    }
}

