<?php

namespace WN\Core\Exception;

use WN\Core\Core;
use WN\Core\Helper\{Dir, HTTP};
use WN\Core\Exception\Handler;
use WN\Core\Request;

class Logger
{
    public static $detail = true;

    public static $is_log = false;

    public static $date_format = 'd.m.y H:i';

    public static $to;

    public static $from;

    public static $path = APPPATH.'tmp/errors/';

    public static $filename = 'log.txt';

    public static function get($hash = NULL)
    {
        $path = Dir::prepare(static::$path);

        if($hash === NULL)
        {
            $file = $path.static::$filename;
            return is_file($file) ? file($file) : [];
        }
        else
        {
            $file = $path.$hash;
            return (is_file($file)) ? unserialize(file_get_contents($file)) : '';
        }
    }
   
    public static function add($e)
    {
        if(!static::$is_log) return;

        $path = str_replace('/', DIRECTORY_SEPARATOR, static::$path);

        if(!is_dir($path)) mkdir($path, 0777, true);
        elseif(!is_writable($path)) chmod($path, 0777);
        $file = $path.static::$filename;

        $searilized = serialize((string)$e);
        $hash = md5($searilized);

        if(static::is_unique($hash))
        {
            $str = date(static::$date_format).'; '.Handler::text($e).'; URI: "/'.HTTP::detect_uri().'"'.PHP_EOL;

            file_put_contents($file, $hash.'; '.$str, FILE_APPEND | LOCK_EX);

            static::sendmail($str);

            if(static::$detail) file_put_contents($path.$hash, $searilized, LOCK_EX);
        }
    }

    public static function delete($hash = NULL)
    {
        if($hash === NULL) Dir::clean(static::$path);
        else
        {
            $array = static::get();
            $path = Dir::prepare(static::$path);
            $logfile = $path.static::$filename;
            file_put_contents($logfile, '');

            foreach($array AS $k => $str)
            {
                if(substr($str, 0, 32) !== $hash) file_put_contents($logfile, $str, FILE_APPEND | LOCK_EX);
            }

            if(is_file($path.$hash)) unlink($path.$hash);
        }

    }

    public static function alarm($message)
    {

    }

    public static function sendmail($message)
    {

    }

    protected static function is_unique($hash)
    {
        foreach(static::get() AS $str)
        {
            if(substr($str, 0, 32) == $hash)
                return false;
        }
        return true;
    }
}