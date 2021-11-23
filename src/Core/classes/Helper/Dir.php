<?php
/**
 * Directory helper class.
 *
 * @package    WN
 * @category   Helpers
 * @author     WN Team
 * @copyright  (c) 2020 WN Team
 * @license    http://webnigger.ru/license
 */
namespace WN\Core\Helper;

class Dir
{
    public static $mask = '{,.}*';
    public static $path = APPPATH;

    public static function get($path, $mask = null, $lifetime = null, $recursive = false, $fullname = true, $prepare = true)
    {
        $path = rtrim($path, '/');
        if($prepare) $path = static::prepare($path);
        if($mask === null) $mask = static::$mask;

        $result = [];

        if($recursive)
            foreach(glob("$path/*", GLOB_ONLYDIR|GLOB_NOSORT) AS $dir)
            {
                if(fnmatch($mask, $dir)) 
                    $result[] = $dir;

                $r = static::get($dir, $mask, $lifetime, $recursive, $fullname, $prepare);
                $result = array_merge($result, $r);

                // var_dump($r);
                // var_dump($result);
            }

        foreach(glob($path.'/'.$mask, GLOB_BRACE) AS $p)
        {
            if(substr($p, -1) === '.') continue;
            elseif($lifetime !== null && $lifetime < (time() - filemtime($p))) continue;
            elseif(is_file($p) || !$recursive) 
            // else    
                $result[] = ($fullname) ? $p : basename($p);
        }

        // if(!empty($result)) echo $path.'/'.$mask, '<br>';

        return $result;
    }

    public static function glob_recursive($mask, $flags = 0)
    {
        $m = basename($mask);
        $m1 = dirname($mask).'/*';
        $result = [];

        foreach(glob($m1, GLOB_ONLYDIR|GLOB_NOSORT) AS $dir)
            $result += static::glob_recursive("$dir/$m", $flags);

        foreach(glob($mask, $flags) AS $file)
            if(is_file($file))
                $result[] = $file;

        return $result;
    }

    // public static function remove($path)
    // {
    //     return static::rm($path);
    // }

    // public static function clean($path, $mask = null, $lifetime = null)
    // {
    //     return static::rm($path, $mask, $lifetime, false);
    // }

    public static function prepare($path)
    {
        if(is_dir($path)) return $path;
        else return static::$path.$path;
    }

    public static function clean($path, $mask = null, $lifetime = null, $recursive = false, $rm = false)
    {
        $path = rtrim($path, '/');
        $result = [];

        $path = static::prepare($path);

        if(!is_dir($path)) return false;

        if($mask === null) $mask = static::$mask;

        if($recursive)
            foreach(glob("$path/*", GLOB_ONLYDIR|GLOB_NOSORT) AS $dir)
                $result += static::clean($dir, $mask, $lifetime, true);

        foreach(glob("$path/$mask", GLOB_BRACE) AS $file)
        {            
            if(is_file($file) && ($lifetime === null || ($lifetime < (time() - filemtime($file)))))
            {
                $result[] = $file;
                unlink($file);
            }
        }

        if($rm && count(glob("$path/*")) == 0) rmdir($path);

        return $result;
    }
}