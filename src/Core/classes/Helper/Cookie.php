<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace WN\Core\Helper;

/**
 * Description of Cookie
 *
 * @author JackRabbit
 */

class Cookie
{
    public static $lifetime = 0;
    public static $path = "/";
    // public static $domain = NULL;
    public static $secure = FALSE;
    public static $http_only = true;

    // public static $name = 'WNT';
    // public static $salt = 'webnigger';

    protected static $is_allowed = false;
    
    public static function get($key)
    {
        $value = filter_input(INPUT_COOKIE, $key, FILTER_SANITIZE_SPECIAL_CHARS);
        // if(!empty($value) && static::$is_allowed === false) static::$is_allowed = true;
        return $value;
    }
    
    public static function set($key, $value, $lifetime = null, $path = null, $domain = null)
    {
        if($lifetime === NULL) $lifetime = static::$lifetime;
        if($lifetime !== 0) $lifetime = time() + $lifetime;
        if($path === null) $path = static::$path;        
        if($domain === NULL) $domain = DOMAIN; // HTTP::domain();
        // else $domain = static::$domain;
        // $secure = static::$secure;
        // $http_only = static::$http_only;

        static::remove_cookie_header($key);

        setcookie($key, $value, $lifetime, $path, $domain, static::$secure, static::$http_only);
    }
    
    public static function delete($key)
    {        
        static::remove_cookie_header($key);
        unset($_COOKIE[$key]);
        static::set($key, FALSE, time()-100);
    }
    
    public static function is_sent($key, $value = NULL)
    {     
        return Headers::is_sent('set-cookie', $key.'='.$value);
    }

    public static function is_allowed($cookie_name = 'WNT', $value = 1, $lifetime = 0)
    {
        if(static::$is_allowed === false && empty($_COOKIE)) static::set($cookie_name, $value, $lifetime);

        if(empty($_COOKIE)) return false;
        else
        {
            static::$is_allowed = true;
            return true;
        }
    }

    // public static function set_test()
    // {
    //     if(!static::get(static::$name))
    //         static::set(static::$name, 1);
    // }
    
    protected static function remove_cookie_header($key)
    {
        if(static::is_sent($key))
        {
            $headers_to_restore = [];
            foreach(headers_list() AS $i=>$header)
            {
                if(stripos($header, 'set-cookie') !== FALSE)
                {
                    if(stripos($header, $key) === FALSE)
                    {
                        $headers_to_restore[] = $header;
                    }
                }
            }

            header_remove('set-cookie');

            foreach($headers_to_restore AS $header)
            {
                header($header);
            }
        }
    }
}
