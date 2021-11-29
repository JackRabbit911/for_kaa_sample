<?php
namespace WN\Core\Exception;

use WN\Core\Helper\{Dir, HTTP};
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
            return is_file($file) ? file($file, FILE_SKIP_EMPTY_LINES) : [];
        }
        else
        {
            $file = $path.$hash;
            return (is_file($file)) ? unserialize(file_get_contents($file)) : '';
        }
    }
   
    public static function add(array $e)
    {
        $path = str_replace('/', DIRECTORY_SEPARATOR, static::$path);

        if(!is_dir($path)) mkdir($path, 0777, true);
        elseif(!is_writable($path)) chmod($path, 0777);
        $file = $path.static::$filename;

        $searilized = serialize($e);
        $hash = md5($searilized);

        if(static::is_unique($hash))
        {
            $str = static::text($e);
            static::sendmail($str);

            file_put_contents($file, $hash.'; '.$str, FILE_APPEND | LOCK_EX);
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
            $content = '';

            foreach($array AS $k => $str)
            {
                if(substr($str, 0, 32) === $hash && is_file($path.$hash))
                    unlink($path.$hash);
                else $content .= $str;               
            }

            file_put_contents($logfile, $content, LOCK_EX);
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

    protected static function text(array $e)
    {
        $file = Debug::path($e['file']);
        $code = ($e['code']) ? ' ['.$e['code'].'] ' : ' ';
        return $e['gmt'].' '.$e['class'].$code
                .$e['message'].' in '
                .$file.' ['.$e['line'].']'.' URI: "/'
                .HTTP::detect_uri().'"'.PHP_EOL;
    }
}
