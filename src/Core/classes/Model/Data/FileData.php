<?php

namespace WN\Core\Model\Data;

use WN\Core\Core;
use WN\Core\Pattern\Settings;
use WN\Core\Model\Data\File as Model;

class FileData
{
    use Settings;

    public static $exts = ['wns', 'php', 'json', 'ini'];

    public static function get($file, $path = null, $settings = null)
    {
        if($settings !== null) static::settings($settings);

        $files = static::find_file($file, true);

        if(!$files) return null;

        $result = [];

        foreach(array_reverse($files) as $f)
        {
            $model = new Model($f);

            if(($res = $model->get($path)) !== null)
            {
                if(is_array($res)) $result = array_replace_recursive($result, $res);
                else $result = $res;
            }
        }

        return $result;
    }

    protected static function find_file($file, $merge = false)
    {
        foreach(static::$exts as $ext)
        {
            $filename = $file.'.'.$ext;
            $findfile = Core::find_file($filename, $merge);
            if(!empty($findfile)) return $findfile;
        }
    }
}