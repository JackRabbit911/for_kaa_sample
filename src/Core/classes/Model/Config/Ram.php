<?php

namespace WN\Core\Model\Config;

use WN\Core\Helper\Arr;

class Ram
{
    // public static $cache = [];

    public function get($file, $key = null)
    {
        global $config_cache;

        $config = $config_cache[$file] ?? null;
        if($key)
            return (is_array($config)) ? Arr::path($config, $key) : $config;
        else return $config;
    }

    public function set($file, $key, $value)
    {
        global $config_cache;

        if($file === null && $key == null && empty($config_cache))
        {
            $config_cache = $value;
            return;
        }
        
        if($key !== null) $key = '.'.$key;
            Arr::set_path($config_cache, $file.$key, $value);
    }
}