<?php
/**
 * Description of TvDbMetadataManager
 *
 * @author Francisco Grau <grau.fran@gmail.com>
 */
class OpenSubtitlesMetadataManager
{
    const BASE_URL = 'http://api.opensubtitles.org/xml-rpc';
    private static $instance = null;
    private static $loginToken = null;

    private function __construct()
    {

    }

    public static function getInstance()
    {
        if (is_null(self::$instance)) {
            self::$instance = new OpenSubtitlesMetadataManager();
        }
        return self::$instance;
    }

    public function fetchMediaItemSubtitle($filePath, $langCode = 'eng')
    {
        $langCode = $this->mapLanguage($langCode);
        $token = $this->logIn();
        $hash = $this->createFileHash($filePath);
        $size = $this->getFileSize($filePath);
        $params = array(
            $token,
            array(
                array(
                    'sublanguageid' => $langCode,
                    'moviehash' => $hash,
                    'moviebytesize' => $size
                )
            )
        );
        $response = $this->invokeXmlRpc('SearchSubtitles', $params);
        $subtittle = null;
        if ($response !== false) {
            $subtittle = true;
            $subItem = null;
            if ($response['data'] === false) {
                $mItem = MediaItem::createMediaItem($filePath);
                $mItem->fetchImdbId();
                $params = array(
                    $token,
                    array(
                        array(
                            'sublanguageid' => $langCode,
                            'imdbid' => $this->fixImdbId($mItem->imdbId)
                        )
                    )
                );
                $response = $this->invokeXmlRpc('SearchSubtitles', $params);
                if (is_array($response['data'])) {
                    if (is_a($mItem, 'MediaItemSeries')) {
                        foreach ($response['data'] as $sItem) {
                            if (isset($sItem['SeriesSeason']) && isset($sItem['SeriesEpisode'])) {
                                if ($sItem['SeriesSeason'] == $mItem->season && $sItem['SeriesEpisode'] == $mItem->episode) {
                                    $subItem = $sItem;
                                    break;
                                }
                            }
                        }
                    } else {
                        $subItem = $response['data'][0];
                    }
                }
            } else {
                $subItem = $response['data'][0];
            }
            if (!is_null($subItem)) {
                $subtittle = Utils::gzDecode(file_get_contents($subItem['SubDownloadLink']));
            }
        }
        return $subtittle;
    }

    public function mapLanguage($langCode)
    {
        $langCode = strtolower($langCode);
        if (strlen($langCode) === 2) {
            $mappings = array('en' => 'eng', 'es' => 'spa', 'fr' => 'fra', 'pt' => 'por');
            $langCode = strtr($langCode, $mappings);
        }
        return $langCode;
    }

    public function logIn()
    {
        $loginToken = self::$loginToken;
        if (is_null($loginToken)) {
            $params = array(
                $GLOBALS['OPENSUBTITLES_USERNAME'],
                $GLOBALS['OPENSUBTITLES_PASSWORD'],
                $GLOBALS['SUBTITLES_LANGUAGE'],
                'OS Test User Agent'
            );

            $token = null;
            $response = $this->invokeXmlRpc('LogIn', $params);
            if ($response !== false) {
                $token = $response['token'];
            }
            $loginToken = (string)$token;
            self::$loginToken = $loginToken;
        }
        return $loginToken;
    }

    private function invokeXmlRpc($methodName, $params)
    {
        $request = xmlrpc_encode_request($methodName, $params);
        $context = stream_context_create(
            array(
                'http' => array(
                    'method' => "POST",
                    'header' => "Content-Type: text/xml",
                    'content' => $request
                )
            )
        );

        $file = file_get_contents(OpenSubtitlesMetadataManager::BASE_URL, false, $context);
        $response = xmlrpc_decode($file);
        if ($response && xmlrpc_is_fault($response)) {
            if ($GLOBALS['DEBUG'] === true) {
                echo ' ERROR Invoking XML-RPC OpenSubtitles: ' . $response['faultString'] . '(' . $response['faultCode'] . ')';
            }
            return false;
        }
        if ($response['status'] === '200 OK') {
            return $response;
        }
        return false;
    }

    public function createFileHash($file)
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
            $tmp = $this->readUint64($handle);
            $hash = $this->addUint64($hash, $tmp);
        }

        $offset = $fsize - 65536;
        fseek($handle, $offset > 0 ? $offset : 0, SEEK_SET);

        for ($i = 0; $i < 8192; $i++) {
            $tmp = $this->readUint64($handle);
            $hash = $this->addUint64($hash, $tmp);
        }

        fclose($handle);
        return $this->uint64FormatHex($hash);
    }

    function readUint64($handle)
    {
        $u = unpack("va/vb/vc/vd", fread($handle, 8));
        return array(0 => $u["a"], 1 => $u["b"], 2 => $u["c"], 3 => $u["d"]);
    }

    private function addUint64($a, $b)
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

    private function uint64FormatHex($n)
    {
        return sprintf("%04x%04x%04x%04x", $n[3], $n[2], $n[1], $n[0]);
    }

    public function getFileSize($file)
    {
        return filesize($file);
    }

    private function fixImdbId($imdbId)
    {
        return str_replace('tt', '', $imdbId);
    }
}