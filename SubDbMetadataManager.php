<?php
/**
 * Description of TvDbMetadataManager
 *
 * @author Francisco Grau <grau.fran@gmail.com>
 */
class SubDbMetadataManager
{
    const CHUNK_SIZE = 65536; // 1024 * 64
    const MAX_FILE_SIZE = 2147483647;
    const URL_SEARCH = 'http://api.thesubdb.com/?action=search&hash={HASH}';
    const URL_DOWNLOAD = 'http://api.thesubdb.com/?action=download&hash={HASH}&language={LANG}';
    private static $instance = null;

    private function __construct()
    {

    }

    public static function getInstance()
    {
        if (is_null(self::$instance)) {
            self::$instance = new SubDbMetadataManager();
        }
        return self::$instance;
    }

    public function fetchMediaItemSubtitle($filePath, $langCode)
    {
        $hash = $this->createFileHash($filePath);
        if ($GLOBALS['DEBUG'] === true) {
            echo ' HASH: ' . $hash;
        }
        if (!is_null($hash)) {
            $subLanguages = $this->searchMediaSubtitle($hash);
            if (!is_null($subLanguages)) {
                if ($GLOBALS['DEBUG'] === true) {
                    echo ' LANGS: ' . implode(', ', $subLanguages);
                }
                if (in_array($langCode, $subLanguages) === true) {
                    $subtitle = $this->downloadMediaSubtitle($hash, $langCode);
                    if (!is_null($subtitle)) {
                        if ($GLOBALS['DEBUG'] === true) {
                            echo ' SUB: ' . strlen($subtitle);
                        }
                        return $subtitle;
                    }
                }
            }
        }
        if ($GLOBALS['DEBUG'] === true) {
            echo ' * NOT FOUND *';
        }
        return null;
    }

    public function createFileHash($filePath)
    {
        if (file_exists($filePath)) {
            if (is_dir($filePath) === false) {
                $prefixBytes = null;
                $sufixBytes = null;
                $fileSize = filesize($filePath);
                if (empty($fileSize) || $fileSize > self::MAX_FILE_SIZE) {
                    $prefixTmpFile = tempnam(sys_get_temp_dir(), 'MO_');
                    $sufixTmpFile = tempnam(sys_get_temp_dir(), 'MO_');
                    exec('head -c ' . self::CHUNK_SIZE . ' "' . $filePath . '" > ' . $prefixTmpFile);
                    exec('tail -c ' . self::CHUNK_SIZE . ' "' . $filePath . '" > ' . $sufixTmpFile);
                    $prefixBytes = file_get_contents($prefixTmpFile);
                    $sufixBytes = file_get_contents($sufixTmpFile);
                    unlink($prefixTmpFile);
                    unlink($sufixTmpFile);
                } else {
                    $handle = fopen($filePath, 'r');
                    if ($handle !== false) {
                        $length = filesize($filePath);
                        $prefixBytes = fread($handle, self::CHUNK_SIZE);
                        fseek($handle, $length - self::CHUNK_SIZE);
                        $sufixBytes = fread($handle, self::CHUNK_SIZE);
                        fclose($handle);
                    }
                }
                if (!is_null($prefixBytes) && !is_null($sufixBytes)) {
                    $hash = md5($prefixBytes . $sufixBytes);
                    return $hash;
                }
            }
        }
        return null;
    }

    /**
     * Searches in the SubDB a subtitle for a given hash
     * @param string $hash
     * @return null|array Returns an array of the available languages for downloading the subtitle.
     */
    public function searchMediaSubtitle($hash)
    {
        $url = str_replace('{HASH}', $hash, self::URL_SEARCH);

        $curl = $this->executeCurlRequest($url);
        if ($curl['code'] === 200) {
            return explode(',', $curl['content']);
        }
        return null;
    }

    private function executeCurlRequest($url)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt(
            $ch,
            CURLOPT_HTTPHEADER,
            array("User-Agent: SubDB/1.0 (MediaOrganizer/1.0; https://github.com/graux/MediaOrganizer)")
        );
        $response = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        return array('code' => $code, 'content' => $response);
    }

    public function downloadMediaSubtitle($hash, $lang = 'en')
    {
        $url = str_replace('{HASH}', $hash, self::URL_DOWNLOAD);
        $url = str_replace('{LANG}', $lang, $url);

        $response = $this->executeCurlRequest($url);

        if ($response['code'] === 200) {
            return $response['content'];
        }
        return null;
    }
}