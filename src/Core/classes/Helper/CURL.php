<?php

namespace WN\Core\Helper;

class CURL
{
    public static function get($url)
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_HEADER, false);
        $res = curl_exec($ch);
        curl_close($ch);

        return $res;
    }

    public static function url_exists($url=NULL)
    {
        // $referer = (isset($_SERVER['HTTP_REFERER'])) ? $_SERVER['HTTP_REFERER'] : $url;
        // echo $referer.'<br>'; 
        if($url == NULL) return false;  
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_AUTOREFERER, true);
        curl_setopt($ch, CURLOPT_REFERER, HTTP::url());
        curl_setopt($ch, CURLOPT_NOSIGNAL, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2);
        curl_setopt($ch, CURLOPT_TIMEOUT, 2);
        curl_setopt($ch, CURLOPT_NOBODY, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);  
        curl_exec($ch);
        // print_r(curl_getinfo($ch));
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        // $error = curl_error($ch);
        curl_close($ch);
        // return $httpcode;
        if($httpcode>=200 && $httpcode<300) return true;  
        else return false;
    }
}