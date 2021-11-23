<?php

namespace WN\Page;

use WN\Core\Pattern\{Entity};
use WN\Core\Model\Data\File;
use WN\Core\{Core, Request};
use WN\Core\Exception\WnError;
use WN\Core\Helper\{HTTP, Text};
use WN\Image\Image;
use WN\Core\Exception\WnException;
use WN\User\User;

class Page extends Entity
{
    const DELETED = 0;
    const DRAFT = 1;
    const INVISIBLE = 2;
    const DEPRICATED = 3;
    const READY = 4;
    const PUBLISHED = 5;

    const VIEW = 1;
    const READ = 2;
    const READ_COMMENTS = 3;
    const RATE = 4;
    const COMMENT = 5;
    const ADD = 6;
    const EDIT = 7;
    const MODIFY = 8;
    const DELETE = 9;

    public static function factory($id = null, $settings = null)
    {
        $page = new static($id, $settings);

        if($page->id === 0)
        {
            $data = $page::$model->get('dep_url', $id);

            return (!empty($data)) ? new static($data, $settings) : false;
        }
        else return new static($id, $settings);
    }

    public static function collection($where = null, $columns = [], $order_by = null)
    {
        static::model_instance();
        return static::$model->getAll($where, $columns, $order_by);
    }

    public static function uri($param)
    {
        if(is_array($param)) return false;
        static::model_instance();
        return static::$model->get_uri($param);
    }

    public function __construct($id = null, $settings = null)
    {
        parent::__construct($id, $settings);

        if(!empty($this->data))
            foreach($this->data AS &$item)
            {
                $item = Text::unserialize($item);
                $item = Text::json_decode($item);
            }
    }

    public function childern($level = null, $columns = [])
    {
        return static::$model->get_children($this->id, $columns, $level, false);
    }

    public function parents()
    {
        return static::$model->get_parents($this->id, func_get_args(), false);
    }

    public function &__get($name)
    {
        if(isset($this->data[$name]))
        {
            if(is_string($this->data[$name]) && strpos($this->data[$name], '<@') !== false)
                return static::parser($this->data[$name]);
            else
                return $this->data[$name];
        }
        else
        {
            $x = null;
            return $x;
        }
    }

    public function save()
    {
        $restore = $this->data;

        foreach($this->data AS &$item)
            if(is_array($item) || is_object($item))
                $item = serialize($item);

        // foreach($this->_data AS &$item)
        //     if(is_array($item) || is_object($item))
        //         $item = serialize($item);

        if($this->id) $this->data['id'] = $this->id;

        // $data = array_diff_assoc($this->data, $this->_data);

        $id = static::$model->set($this->data);
        
        if($this->id)
            $this->data = $restore;
        else $this->__construct($id);

        unset($restore);
    }

    public function title($prefix = null)
    {
        if(!$prefix)
            if(isset($this->data['site_name']))
               $prefix = $this->data['site_name'];

        if($prefix) $prefix .= ' ';

        return $prefix.$this->data['title'];
    }

    public function access($action = self::READ, $group = 0)
    {
        if(empty($this->access))
        {
            if($action <= self::READ) return true;

            $group = 0;
            $rule = '76522';
        }
        else list($group, $rule) = explode('.', $this->access);

        $rule = str_pad($rule, 5, '0');

        $keys = [40, 30, 20, 10, 0];

        $role = User::auth()->role((int)$group);

        $max = max($keys);
        if($role > $max) $role = $max;

        $key = array_search($role, $keys);

        return $rule[$key] >= $action;
    }

    public static function parser($str)
    {

        $pattern = '/\<@(?P<func>.+?)\>(?P<param>.*?)\<\/@\>/si';
    
        return preg_replace_callback($pattern, function($matches) use($str) {

            $func = $matches['func'];

            if(!is_callable($func)) $func = [__CLASS__, $func];

            $args = explode(', ', $matches['param']);

            return (is_callable($func)) ? call_user_func_array($func, $args) : $str;

        }, $str);
       
    }

    protected static function ffile($filename)
    {
        if(!is_file($filename))
            $filename = 'src/App/data/'.$filename;

        if(!is_file($filename))
            $filename = Core::find_file($filename);

        return (is_file($filename)) ? File::factory($filename)->get() : null;
    }

    protected static function markdown($str)
    {
        return Text::markdown(trim($str));
    }

    protected static function request($url)
    {
        if(str_ends_with($url, '*'))
            $url = substr($url, 0, -1).HTTP::detect_uri();

        return Request::factory($url)->execute();
    }

    protected static function table()
    {
        $args = func_get_args();
        $table_name = array_shift($args);

        $table = static::$model::$db->table($table_name);
        $result = call_user_func_array([$table, 'getColumn'], $args);

        return static::parser($result);
    }

    protected static function text($id)
    {
        static::model_instance();
        $text = static::$model->get_blob($id);
        return static::parser($text);
        // return $text;
    }

    // public static function img()
    // {
    //     $args = func_get_args();
    //     $type = $args[1] ?? 'original';
    //     $width = $args[2] ?? null;
    //     $height = $args[3] ?? null;

    //     return Image::factory($args[0]); //->html($type, $width, $height);
    // }
}