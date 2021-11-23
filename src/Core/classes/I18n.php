<?php

namespace WN\Core;

use stdClass;
use WN\Core\Exception\WnException;
use WN\Core\Helper\{Accept, Cookie, HTTP, URL, CURL, Inflector};
use WN\Core\Model\Data\FileData;
use WN\Core\Request;

class I18n
{
    use Pattern\Settings;

    const NO_REDIRECT = 0;
    const LANG_TO_EMPTY = 1;
    const EMPTY_TO_LANG = 2;

    public static $lang;
    public static $langs;
    public static $base_lang;
    public static $default_lang = 'en';
    public static $is_browser_accept_allow = true;
    // public static $callback = 'WN\Core\Model\I18n\Files::get';
    public static $model = 'WN\Core\Model\I18n\Files';
    public static $method = 'get';
    public static $redirect = self::LANG_TO_EMPTY;
    public static $use_subdomain = false;
    public static $dir = 'i18n';
    public static $ext = 'php';

    public static function lang($lang = null)
    {
        if(static::$lang && !$lang) return static::$lang;

        static::instance();

        if($lang)
        {
            if(in_array($lang, static::$langs))
                static::$lang = $lang;
            elseif(Core::$errors)
                throw new WnException('":lang" not found in the languages list', [':lang'=>$lang]);
        }
        else static::$lang = static::detect_lang();

        static::reload();

        return static::$lang;
    }

    public static function get_href_array($except_current_lang = true)
    {
        static::instance();
        if(!static::$lang) static::$lang = static::detect_lang();

        $result = [];

        foreach(static::$langs as $lang)
        {
            if($lang === static::$lang && $except_current_lang === true) continue;

            if(($uri = static::url($lang)) === null) $uri = static::uri($lang);

            // $uri = static::url($lang);

            $result[$lang] = $uri;
        }

        return $result;
    }

    public static function langs($array = false)
    {
        static::instance();

        if(count(static::$langs) <= 1) return ($array) ? [] : '';
        else return ($array) ? static::$langs : implode('|', static::$langs);
    }

    public static function gettext($string, array $values = null, $lang = null)
    {
        if(!$lang) $lang = static::lang();

        if(is_callable([static::$model, static::$method]))
            $string = call_user_func([static::$model, static::$method], $string, $lang);

        if(is_array($string)) $string = $string[0];
        
        return empty($values) ? $string : strtr($string, $values);
    }

    public static function l10n($key = null, $lang = null)
    {
        if(!static::$lang) static::$lang = static::lang();
        if(!$lang) $lang = static::$lang;

        $result = FileData::get(static::$dir.'/l10n/'.$lang, $key);

        if(!$result) $result = FileData::get(static::$dir.'/l10n/'.static::$default_lang, $key);

        return ($result) ? $result : null;
    }

    public static function reload()
    {
        
            if(static::$lang === static::$base_lang && static::$redirect === self::LANG_TO_EMPTY && static::$use_subdomain === false)
                $lang = null;
            else $lang = static::$lang;

            $uri = static::url($lang);
            

            // if(!$uri)
            //     $uri = static::uri($lang);

            $request = Request::current();

            if($uri !== '/'.HTTP::detect_uri() 
                && rtrim($uri, '/') !== rtrim(HTTP::url(), '/')
                && !$request->is_ajax() 
                && $request->initial() 
                && in_array(SUBDOMAIN, static::$langs))
            {
                // var_dump($uri, HTTP::detect_uri(), HTTP::url());
                // exit;
                HTTP::redirect($uri, 301);
            }              
        
    }

    public static function detect_lang()
    {
        if(count(static::$langs) === 1)
            $lang = static::$base_lang;
        else
        {
            if(SUBDOMAIN && in_array(SUBDOMAIN, static::$langs)) $lang = SUBDOMAIN;
            elseif(!empty($lang = Request::initial()->params('lang')) && in_array($lang, static::$langs));
            elseif(!empty($lang = Request::initial()->query('lang')) && in_array($lang, static::$langs));

            if(!$lang)
            {
                if(isset($_COOKIE['lang'])) $lang = Cookie::get('lang');
                elseif(static::$is_browser_accept_allow && ($lang = Accept::language()) && in_array($lang, static::$langs));
                else $lang = static::$base_lang;
            }
        }
        return $lang;
    }

    public static function url($lang)
    {
        if(static::$use_subdomain === false) return null;

        // $subdomain = ($lang) ? $lang : static::$base_lang;
        // $server_name = $subdomain.'.'.DOMAIN;
        // $url = $server_name.static::uri(null);

        // if((static::$use_subdomain === true && $_SERVER['SERVER_NAME'] !== $server_name && CURL::url_exists($url) === false) 
        //     || (is_array(static::$use_subdomain) && !in_array($lang, static::$use_subdomain))) return null;

        if(static::$redirect === self::LANG_TO_EMPTY && $lang === static::$base_lang)
            $lang = null;
        else $lang .= '.';

        return HTTP::scheme().'://'.$lang.DOMAIN.preg_replace('/^('.I18n::langs().')\//', '', $_SERVER['REQUEST_URI']);
    }

    public static function uri($lang)
    {
        if(!in_array($lang, static::$langs)) $lang = null;

        if(static::$redirect === self::LANG_TO_EMPTY && $lang === static::$base_lang)
            $lang = null;

        $route = Route::current();
        $params = $route->params;



        if($route->param_exists('lang')) $params['lang'] = $lang.'/';
        else $params['query'] = URL::query(['lang' => $lang]);

        // var_dump($params); echo '<br>';

        $uri = $route->uri($params);

        // var_dump($uri, $route);

        return (SUBDOMAIN && $lang && SUBDOMAIN === static::$lang) ? HTTP::scheme().'://'.DOMAIN.$uri : $uri;
    }

    public static function plural($count, $word, $show = true, $lang = null)
    {
        $out = ($show) ?  $count . ' ' : '';

        if(!$lang) $lang = static::lang();

        if($lang == 'ru')
        {
            $words = call_user_func([static::$model, static::$method], $word, $lang);

            $num = $count % 100;
            if ($num > 19) { 
                $num = $num % 10; 
            }
            
            switch ($num) {
                case 1:  $out .= $words[0]; break;
                case 2: 
                case 3: 
                case 4:  $out .= $words[1]; break;
                default: $out .= $words[2]; break;
            }        
        }
        else $out .= Inflector::plural($word, $count);

        return $out;
    }

    public static function object()
    {
        $obj = new stdClass();
        $obj->current = static::lang();

        // if(isset($_GET['block'])) unset($_GET['block']);

        $obj->hreflangs = static::get_href_array(false);

        if(func_num_args() > 0)
        {
            $keys = func_get_args();
            array_walk($obj->hreflangs, function(&$v) use ($keys) {
                $v = URL::remove_query($v, $keys);
            });
        }

        // var_dump($obj->hreflangs);

        foreach($obj->hreflangs AS $key => $href)
        {
            if($key === static::$lang) continue;

            $obj->langs[$key]['href'] = $href;
            $obj->langs[$key]['name'] = static::l10n('lang_name', $key);
        }

        // $obj->langs = $arr_href;
        // $obj->hreflangs = static::get_href_array(false);
        return $obj;
    }

    protected static function instance()
    {
        static $instance = false;

        if(!$instance)
        {
            static::$is_once = true;
            static::settings();

            if(empty(static::$langs)) static::$langs[0] = static::$default_lang;
            elseif(is_string(static::$langs)) static::$langs = explode('|', static::$langs);

            static::$base_lang = static::$langs[0];

            if(is_string(static::$use_subdomain)) static::$use_subdomain = explode('|', static::$use_subdomain);

            static::$model::$dir = static::$dir;
            static::$model::$ext = static::$ext;

            $instance = true;
        }        
    }


}