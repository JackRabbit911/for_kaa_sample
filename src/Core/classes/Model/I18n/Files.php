<?php

namespace WN\Core\Model\I18n;

use WN\Core\{Core};
use WN\Core\Model\Data\File;

class Files
{
    public static $dir = 'i18n';
    public static $ext = 'php';

    public static function get($string, $lang)
    {
        static $table;
        $table = ($table) ? $table : static::load_table($lang);

        if(isset($table[$string])) return $table[$string];
        elseif(isset($table[strtolower($string)])) return mb_ucfirst($table[strtolower($string)]);
        else return $string;
        // return $table[$string] ?? $string;
    }
    
    public static function load_table($lang)
    {
        $table = [];

        $lang = strtolower($lang);
        $mask = preg_replace('/(-..)$/', ",$lang", $lang);

        foreach(array_reverse(Core::paths()) as $path)
        {
            foreach(glob($path.'/'.static::$dir.'/{'.$mask.'}.'.static::$ext, GLOB_BRACE) as $file)
                $table = array_merge($table, (array) File::factory($file)->get());
        }

        return $table;
    }
}