<?php

namespace WN\Core;

use WN\Core\Pattern\{Singletone, Options};
use WN\Core\Helper\{Text, Cookie};
use WN\Core\Exception\WnException;

class Session
{
    use Singletone, Options;

    const DEL_CURRENT = 0;
    const DEL_ALL = 1;
    const DEL_OTHERS = 2;

    public static $cookie_name = 'WNSID1';
    public static $cookie_path = '/';
    public static $driver = 'mysql';
    public static $model;
    public static $long = 26000000;
    public static $short = 3600;
    public static $regenerate = true;

    protected static $is_saved = false;
    protected static $is_generated = false;

    public $id;
    public $is_long;
    public $user_id;
    public $data = [];
    protected $old_id;

    public static function create_table($settings = null)
    {
        static::settings($settings);
        static::set_model_name();
        return static::$model::create_table(static::$driver);
    }

    public static function online($lifetime = null, $settings = null)
    {
        static::settings($settings);
        static::set_model_name();
        if(!$lifetime) $lifetime = static::$short;
        return static::$model::online($lifetime);
    }

    public static function gd($lifetime = null, $settings = null)
    {
        static::settings($settings);
        static::set_model_name();
        if(!$lifetime) $lifetime = static::$short;
        return static::$model::gd($lifetime);
    }

    public static function get_last($user_id, $settings = null)
    {
        static::settings($settings);
        static::set_model_name();
        return static::$model::get_last($user_id);
    }

    public static function data($id, $settings = null)
    {
        static::settings($settings);
        static::set_model_name();
        return static::$model::get($id)['data'] ?? false;
    }

    protected static function set_model_name()
    {
        if(static::$model) return;

        $prefix =  'WN\Core\Model\Session\\';

        if(static::$driver === 'file')
        {
            static::$model = $prefix.ucfirst(static::$driver);
        }
        else
        {
            static::$model = $prefix.'DB';
            static::$model::set_pdo(static::$driver);
        }
    }

    protected function __construct()
    {
        static::set_model_name();
        $this->id = $this->old_id = filter_input(INPUT_COOKIE, static::$cookie_name, FILTER_SANITIZE_SPECIAL_CHARS);

        if($this->id)
        {           
            $data = static::$model::get($this->id);

            if(!empty($data))
            {
                $this->user_id = $data['user_id'];
                $this->data = $data['data'];
            }
            else
            {
                Cookie::delete(static::$cookie_name);
                $this->id = null;
                $this->old_id = null;
            }
        }
    }

    public function __get($name)
    {
        return $this->data[$name] ?? null;
    }

    public function __set($name, $value)
    {
        $this->data[$name] = $value;
        static::$is_saved = false;
    }

    public function __unset($name)
    {
        unset($this->data[$name]);
        static::$is_saved = false;
    }

    public function save()
    {
        if(!static::$is_saved)
        {
            $this->generate_id();
            if(!$this->id) return;

            if($this->is_long) $expires = time() + static::$long;
            else $expires = 0;

            Cookie::set(static::$cookie_name, $this->id, $expires, static::$cookie_path);

            $data['id'] = $this->id;
            $data['is_long'] = ($this->is_long) ? 1 : 0;
            $data['user_id'] = $this->user_id ?? 0;
            $data['data'] = $this->data;
            $data['old_id'] = ($this->old_id) ? $this->old_id : $this->id;

            static::$model::set($data);
            static::$is_saved = true;
        }       
    }

    public function destroy($mode = self::DEL_CURRENT)
    {
        static::$model::delete($this->id, $this->old_id, $this->user_id, $mode);

        if($mode !== self::DEL_OTHERS)
        {
            $this->id = null;
            $this->old_id = null;
            $this->data = [];
            $this->is_long = false;
            Cookie::delete(static::$cookie_name);
        }

        static::$is_saved = true;
    }

    protected function generate_id()
    {
        // static $is_generated = false;

        if(static::$is_generated)
        {
            $this->old_id = $this->id;
            return;
        }

        if(($this->user_id && static::$regenerate) || ($this->data && !$this->id))
        {
            static::$is_generated = true;
            $salt = $_SERVER['HTTP_USER_AGENT'] ?? Text::random();
            $this->id = md5($salt.time().bin2hex(random_bytes(12)));
        }
    }
}