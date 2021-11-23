<?php

namespace WN\Core\Model\Config;

use WN\Core\Core;
use WN\Core\Helper\Arr;
use WN\Core\Model\Data\File as Model;
use WN\Core\Model\Data\FileData;

class File extends FileData
{
    // public static $exts = ['wns', 'php', 'json', 'ini'];
    public static $dir = 'config';
    public static $joined_file = 'joined-config.wns';

    public static $model_jf;

    // public static $is_file_cache = false;

    // public function __construct()
    // {
    // }

    public static function get_once($file, $key = null)
    {
        $file = static::find_file(static::$dir.'/'.$file);

        if(!$file) return null;

        $model = new Model($file);
        $value = $model->get($key);

        return $value;
    }

    public static function get($file, $path = null, $settings = null)
    {
        if(Core::$cache)
        {
            static::$model_jf = new Model(static::$joined_file, ['dir' => static::$dir]);

            $k[] = $file;
            if($path) $k[] = $path;
            $composit_key = implode('.', $k);
            $value = static::$model_jf->get($composit_key);
            if($value) return $value;
        }

        // var_dump(static::$dir.'/'.$file);

        // if(is_file($file))
        // {
        //     $model = new Model($file);
        //     return $model->get($path);
        // }
        
        $files = static::find_file(static::$dir.'/'.$file, true);

        if(!$files) return null;

        $result = $array = [];

        foreach(array_reverse($files) as $f)
        {
            $model = new Model($f);
            $res = $model->get($path);
            if(!is_array($res)) $result = $res;
            else $result = array_replace_recursive($result, $res);
        }

        if(Core::$cache)
        {
            Arr::set_path($array, $composit_key, $result);
            static::$model_jf->set($array);
        }

        return $result;
    }
}