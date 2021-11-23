<?php

namespace WN\Core;

use WN\Core\Model\Config\Ram;
use WN\Core\Exception\WnException;
use WN\Core\Helper\Arr;


class Config
{
    use Pattern\Singletone;
    // use Pattern\Settings;

    const DEFAULT = '_default';
    const BOOT = '_boot';
    const SETTINGS = '_settings';

    public static $dir = 'config';
    
    // public static $cache = 'Ram';
    // public static $data = 'File';

    public static $class_cache = 'WN\Core\Model\Config\Ram';
    public static $class_data = 'WN\Core\Model\Config\File';

    public $model_cache;
    public $model_data;

    public static function settings($settings = null)
    {
        static $is_set = false;

        if($is_set && static::$is_once) return;
        
        // if($settings === null) $settings = Model\Config\File::get('config', null);

        if(is_array($settings) && !empty($settings))
        {
            foreach($settings as $name => $value)
                static::$$name = $value;
        }

        $is_set = true; 
    }

    public static function chdir($dir = null)
    {
        static $stor;

        if(!$dir && $stor)
        {
            static::$dir = $stor;
            static::$class_data::$dir = $stor;
            $stor = null;
        }
        else
        {
            $stor = static::$dir;
            static::$dir = $dir;
            static::$class_data::$dir = $dir;
        }
    }

    protected function __construct()
    {   
        // $model_cache = 'WN\Core\Model\Config\\'.static::$cache;
        // $this->model_cache = new $model_cache;

        // $model_data = 'WN\Core\Model\Config\\'.static::$data;
        // $this->model_data = new $model_data;

        $this->model_cache = new static::$class_cache;
        $this->model_data = new static::$class_data;

        $this->model_data::$dir = static::$dir;
    }

    public function __get($name)
    {
        return $this->get($name);
    }

    public function get($file, $key = null, $flag = Config::DEFAULT)
    {
        return $this->$flag($file, $key);
    }

    protected function _boot($file, $key = null)
    {
        $model_cache = new \WN\Core\Model\Config\Ram();
        $model_data = new \WN\Core\Model\Config\File($file);

        $content = $model_data->get_once($file);
        if($content)
        {
            $model_cache->set(null, null, $content);
            if($key !== null) $content = Arr::path($content, $key);
        }
        elseif(strpos($file, 'default') === false)
        {
            $file = str_replace(DOMAIN, 'default', $file);
            $content = $this->_boot($file, $key);
        }

        return $content;
    }

    protected function _default($file, $key = null)
    {
        list($file, $path) = $this->_parse_filename($file);

        $pathkey = trim($path.'.'.$key, '.');

        $value = $this->model_cache->get($file, $pathkey);
        if($value) return $value;

        $content = $this->model_data->get($file, $path);

        if($content !== null)
        {
            $this->model_cache->set($file, $path, $content);
            if($key !== null) $content = Arr::path($content, $key);
        }

        return $content;
    }

    protected function _settings($file, $key = null)
    {
        list($file, $path) = $this->_parse_filename($file);

        $pathkey = trim($path.'.'.$key, '.');

        if(stripos(static::$class_cache, 'Ram') === false)
        {
            $value = $this->model_cache->get($file, $pathkey);
            if($value) return $value;
        }
        
        $content = $this->model_data->get($file, $path);

        if($content !== null)
        {
            if(stripos(static::$class_cache, 'Ram') === false)
                $this->model_cache->set($file, $path, $content);

            if($key !== null) $content = Arr::path($content, $key);
        }

        return ($content === null) ? [] : $content;
    }

    /**
     * parse parameter $file, if we use dot notation
     * Example: ->_parse_filename('file.key1.key11)
     * return [$file = "file", $path = "key1.key11"]
     *
     * @param $file
     * @param $key
     * @return array
     */
    public function _parse_filename(string $file)
    {
        $key = null;

        $pos = strpos($file, '.');

        if($pos !== FALSE)
        {
            $key = substr($file, $pos+1);

            if(!$key) $key = NULL;
            // else $key .= '.';

            $file = substr($file, 0, $pos);
        }

        return [$file, $key];
    }
}