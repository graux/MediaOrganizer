<?php
/**
 * Description for NetManager.php
 * @author Francisco Grau <grau.fran@gmail.com>
 * @copyright MediaOrganizer 2013 All Rights Reserved
 */

class NetManager
{
    public static function requestUrl($url)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array("Accept: text/xml"));
        curl_setopt(
            $ch,
            CURLOPT_USERAGENT,
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.7; rv:15.0) Gecko/20100101 Firefox/15.0.1'
        );
        $response = curl_exec($ch);
        curl_close($ch);

        return $response;
    }
} 