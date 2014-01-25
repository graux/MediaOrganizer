<?php

/**
 * Description of Utils
 *
 * @author Francisco Grau <grau.fran@gmail.com>
 */
class Utils
{

    public static function getFileExtension($file)
    {
        return substr($file, strrpos($file, '.') + 1);
    }

    /**
     *
     * @param MediaItem $mItem
     */
    public static function fixName($mItem)
    {
        if (strpos($mItem->name, '720') > 0) {
            $mItem->name = preg_replace('/720p?/', '', $mItem->name);
            $mItem->format = '720p';
        } else {
            if (strpos($mItem->name, '1080') > 0) {
                $mItem->name = preg_replace('/1080p?/', '', $mItem->name);
                $mItem->format = '1080p';
            } else {
                if (strpos($mItem->filePath, '720') !== false) {
                    $mItem->format = '720p';
                } else {
                    if (strpos($mItem->filePath, '1080') !== false) {
                        $mItem->format = '1080p';
                    }
                }
            }
        }

        $mItem->name = preg_replace('/(\[.*?\]|\(.*?\))/', ' ', $mItem->name);
        $mItem->name = strtr(
            $mItem->name,
            array('.' => ' ', '_' => ' ', '-' => ' ', '(' => '', ')' => '', '{' => '', '}' => '', '[' => '', ']' => '')
        );
        $mItem->name = preg_replace(
            '/(\b|^)(DVDRip|BRRip|BluRayRip|HDTV|dts|x264|xvid|chd|Bdrip|Ext|Proper|Besthd|Bluray|Episode(\s\d+(\sand\d+)?)?)(\b|$)/i',
            '',
            $mItem->name
        );

        $match = array();
        if (preg_match('/[1-2]\d\d\d/', $mItem->name, $match) > 0) {
            $mItem->year = $match[0];
            $mItem->name = str_replace($mItem->year, '', $mItem->name);
        }
        $mItem->name = preg_replace('/\s+/', ' ', $mItem->name);
        $mItem->name = trim(ucwords($mItem->name));

        if (isset($GLOBALS['NAME_PATCHING'][$mItem->name])) {
            $newName = $GLOBALS['NAME_PATCHING'][$mItem->name];
            if ($GLOBALS['DEBUG']) {
                echo 'Name Exception Found, Replacing: ' . $mItem->name . ' => ' . $newName . "\n";
            }
            $mItem->name = $newName;
        }
    }

    public static function downloadThumbnail($url, $targetFile)
    {
        if ($GLOBALS['DEBUG']) {
            echo "[+] Download File: $url\n";
        }
        if ($GLOBALS['DRY_RUN'] === false) {
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_HEADER, 0);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_BINARYTRANSFER, 1);
            $raw = curl_exec($ch);
            curl_close($ch);
            file_put_contents($targetFile, $raw);
        }
    }

    public static function openSubtitlesHash($file)
    {
        $handle = fopen($file, "rb");
        $fsize = filesize($file);

        $hash = array(
            3 => 0,
            2 => 0,
            1 => ($fsize >> 16) & 0xFFFF,
            0 => $fsize & 0xFFFF
        );

        for ($i = 0; $i < 8192; $i++) {
            $tmp = self::readUint64($handle);
            $hash = self::addUint64($hash, $tmp);
        }

        $offset = $fsize - 65536;
        fseek($handle, $offset > 0 ? $offset : 0, SEEK_SET);

        for ($i = 0; $i < 8192; $i++) {
            $tmp = self::readUint64($handle);
            $hash = self::addUint64($hash, $tmp);
        }

        fclose($handle);
        return self::uint64FormatHex($hash);
    }

    private static function readUint64($handle)
    {
        $u = unpack("va/vb/vc/vd", fread($handle, 8));
        return array(0 => $u["a"], 1 => $u["b"], 2 => $u["c"], 3 => $u["d"]);
    }

    private static function addUint64($a, $b)
    {
        $o = array(0 => 0, 1 => 0, 2 => 0, 3 => 0);

        $carry = 0;
        for ($i = 0; $i < 4; $i++) {
            if (($a[$i] + $b[$i] + $carry) > 0xffff) {
                $o[$i] += ($a[$i] + $b[$i] + $carry) & 0xffff;
                $carry = 1;
            } else {
                $o[$i] += ($a[$i] + $b[$i] + $carry);
                $carry = 0;
            }
        }

        return $o;
    }

    private static function uint64FormatHex($number)
    {
        return sprintf("%04x%04x%04x%04x", $number[3], $number[2], $number[1], $number[0]);
    }

    public static function changeExtension($path, $newExtension)
    {
        $lastDot = strrpos($path, '.');
        return substr($path, 0, $lastDot) . '.' . $newExtension;
    }

    public static function getValidFileSystemString($orignal)
    {
        $output = strtr(
            $orignal,
            array(
                '?' => '',
                '[' => '(',
                ']' => ')',
                '/' => '-',
                '\\' => '-',
                '=' => ' ',
                '+' => ' ',
                '<' => ' ',
                '>' => ' ',
                ':' => '',
                ';' => '-',
                '"' => '\'',
                ',' => ' ',
                '*' => ' ',
                '|' => '-'
            )
        );
        $output = preg_replace('/\s+/', ' ', $output);
        return trim($output, ' _.');
    }

    public static function gzDecode($contents)
    {
        return gzinflate(substr($contents, 10, -8));

    }
}
